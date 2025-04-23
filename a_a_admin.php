<?php
session_start();
include 'db.php';

$ip = $_SERVER['REMOTE_ADDR'];
$hwid = $_POST['hwid'] ?? 'unknown_device';
$max_attempts = 5;
$block_duration = 1800; // 30 minutes

// Auto logout after 30 minutes
if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > $block_duration) {
    session_unset();
    session_destroy();
}
$_SESSION['last_activity'] = time();

// Create login_attempts table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45),
    hwid VARCHAR(100),
    attempts INT DEFAULT 0,
    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    blocked_until TIMESTAMP NULL
)");

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if blocked
    $stmt = $conn->prepare("SELECT * FROM login_attempts WHERE ip = ? AND hwid = ?");
    $stmt->bind_param("ss", $ip, $hwid);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $now = time();
    if ($row && $row['blocked_until'] && strtotime($row['blocked_until']) > $now) {
        $error = "🔒 Temporarily Blocked: Too many failed login attempts. Try again later.";
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();

        if ($admin && password_verify($password, $admin['password'])) {
            // Successful login
            $stmt = $conn->prepare("DELETE FROM login_attempts WHERE ip = ? AND hwid = ?");
            $stmt->bind_param("ss", $ip, $hwid);
            $stmt->execute();

            // Generate OTP and send to Telegram
            $otp = rand(100000, 999999);
            $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

            $update = $conn->prepare("UPDATE admin SET otp_code = ?, otp_expires_at = ? WHERE username = ?");
            $update->bind_param("sss", $otp, $expires_at, $username);
            $update->execute();

            // Get Telegram credentials from admin record
            $chat_id = $admin['telegram_chat_id'] ?? '';
            $botToken = $admin['telegram_bot_token'] ?? '';

            // Send OTP via Telegram if credentials are available
            if (!empty($chat_id) && !empty($botToken)) {
                $message = "Your 2FA OTP is: $otp";
                file_get_contents("https://api.telegram.org/bot$botToken/sendMessage?chat_id=$chat_id&text=" . urlencode($message));
            } else {
                $error = "❌ Telegram credentials not configured. Please contact administrator.";
                header("refresh:2;url=a_a_admin.php");
                exit;
            }

            // Go to OTP verification
            $_SESSION['temp_user'] = $username;
            $success = "✅ Login Successful. Redirecting to OTP...";
            header("refresh:1;url=verify_otp.php");
            exit;
        } else {
            // Failed login
            if ($row) {
                $attempts = $row['attempts'] + 1;
                $blocked_until = $attempts >= $max_attempts ? date("Y-m-d H:i:s", $now + $block_duration) : null;
                $stmt = $conn->prepare("UPDATE login_attempts SET attempts=?, last_attempt=NOW(), blocked_until=? WHERE ip=? AND hwid=?");
                $stmt->bind_param("isss", $attempts, $blocked_until, $ip, $hwid);
            } else {
                $attempts = 1;
                $blocked_until = null;
                $stmt = $conn->prepare("INSERT INTO login_attempts (ip, hwid, attempts, blocked_until) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssis", $ip, $hwid, $attempts, $blocked_until);
            }
            $stmt->execute();
            $error = "❌ Login Failed. Attempts: $attempts / $max_attempts";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --primary-color: #007bff;
            --primary-hover: #0056b3;
            --error-color: #dc3545;
            --success-color: #28a745;
            --text-color: #333;
            --border-color: #ddd;
            --box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', sans-serif;
            background: var(--primary-color);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
        }
        
        .login-box {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: var(--box-shadow);
            text-align: center;
        }
        
        .login-box h2 {
            margin-bottom: 25px;
            color: var(--primary-color);
            font-size: 1.8rem;
        }
        
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            text-align: left;
        }
        
        .form-group label {
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .btn {
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }
        
        .alert-error {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--error-color);
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(40, 167, 69, 0.2);
        }
        
        @media (max-width: 480px) {
            .login-box {
                padding: 25px 20px;
            }
            
            .login-box h2 {
                font-size: 1.5rem;
                margin-bottom: 20px;
            }
            
            .form-control {
                padding: 12px 14px;
            }
            
            .btn {
                padding: 12px;
            }
        }
        
        @media (max-width: 360px) {
            .login-box {
                padding: 20px 15px;
            }
            
            .login-box h2 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-box">
        <h2>Admin Login</h2>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php elseif (!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST" class="login-form">
            <input type="hidden" name="hwid" value="<?= htmlspecialchars($hwid) ?>">
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Enter username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Continue</button>
        </form>
    </div>
</div>
</body>
</html>