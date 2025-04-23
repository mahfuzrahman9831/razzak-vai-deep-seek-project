<?php
session_start();
include 'db.php';

if (!isset($_SESSION['temp_user'])) {
    header("Location: admin.php");
    exit();
}

$username = $_SESSION['temp_user'];
$message = "";

// Handle OTP resend
if (isset($_GET['resend'])) {
    // Generate new OTP
    $new_otp = rand(100000, 999999);
    $expiry_time = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    
    // Update database with new OTP
    $update = $conn->prepare("UPDATE admin SET otp_code = ?, otp_expires_at = ? WHERE username = ?");
    $update->bind_param("sss", $new_otp, $expiry_time, $username);
    $update->execute();
    
    // Get user's Telegram chat ID from database
    $stmt = $conn->prepare("SELECT telegram_chat_id FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($telegram_chat_id);
    $stmt->fetch();
    $stmt->close();
    
    if ($telegram_chat_id) {
        // Send OTP via Telegram bot
        $bot_token = '7669042399:AAHJMPjIeV4xXVI5CVAmMeDbHtPyh1Ab2Jc';
        $text = "Your new OTP is: $new_otp\nValid for 5 minutes";
        
        $telegram_url = "https://api.telegram.org/bot$bot_token/sendMessage";
        $data = [
            'chat_id' => $telegram_chat_id,
            'text' => $text
        ];
        
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        
        $context = stream_context_create($options);
        @file_get_contents($telegram_url, false, $context);
    }
    
    $_SESSION['otp_alert'] = [
        'title' => 'OTP Resent!',
        'text' => 'A new OTP has been sent to your Telegram.',
        'icon' => 'success',
        'draggable' => true
    ];
    
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input_otp = $_POST['otp'];

    $stmt = $conn->prepare("SELECT otp_code, otp_expires_at FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($otp_code, $otp_expires_at);
    $stmt->fetch();
    $stmt->close();

    if ($input_otp === $otp_code && strtotime($otp_expires_at) > time()) {
        $_SESSION['admin'] = $username;
        unset($_SESSION['temp_user']);

        // Clear OTP
        $clear = $conn->prepare("UPDATE admin SET otp_code = NULL, otp_expires_at = NULL WHERE username = ?");
        $clear->bind_param("s", $username);
        $clear->execute();

        $_SESSION['otp_alert'] = [
            'title' => 'Verified Successfully!',
            'text' => 'You have been logged in.',
            'icon' => 'success',
            'draggable' => true
        ];
        
        header("Location: dashboard.php");
        exit();
    } else {
        $_SESSION['otp_alert'] = [
            'title' => 'Invalid OTP!',
            'text' => 'The OTP you entered is invalid or expired.',
            'icon' => 'error',
            'draggable' => true
        ];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>OTP Verification</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #007bff;
            --primary-hover: #0056b3;
            --error-bg: #ffebee;
            --success-bg: #e8f5e9;
            --text-color: #333;
            --light-gray: #f5f5f5;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            margin: 0;
            padding: 0;
            background: var(--primary-color);
            font-family: 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-color);
        }
        
        .otp-container {
            width: 100%;
            max-width: 500px;
            padding: 0 20px;
        }
        
        .otp-box {
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .otp-box h2 {
            margin-bottom: 25px;
            color: var(--primary-color);
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .otp-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .input-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            text-align: left;
        }
        
        .input-group label {
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .otp-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1.1rem;
            transition: border-color 0.3s;
        }
        
        .otp-input:focus {
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: var(--light-gray);
            color: var(--text-color);
            text-decoration: none;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .resend-info {
            margin-top: 20px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .resend-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
        }
        
        .resend-link:hover {
            text-decoration: underline;
        }
        
        .error-message {
            color: #d32f2f;
            background: var(--error-bg);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: none;
        }
        
        @media (max-width: 600px) {
            .otp-box {
                padding: 30px 20px;
            }
            
            .otp-box h2 {
                font-size: 1.5rem;
            }
            
            .otp-input {
                padding: 12px 14px;
            }
            
            .btn {
                padding: 12px;
            }
        }
        
        @media (max-width: 400px) {
            .otp-box {
                padding: 25px 15px;
            }
            
            .otp-box h2 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
<div class="otp-container">
    <div class="otp-box">
        <h2>OTP Verification</h2>
        <div class="error-message" id="errorMessage"></div>
        
        <form method="post" class="otp-form">
            <div class="input-group">
                <label for="otp">Enter 6-Digit Verification Code</label>
                <input type="number" 
                       id="otp" 
                       name="otp" 
                       class="otp-input" 
                       placeholder="" 
                       required 
                       pattern="\d*"
                       inputmode="numeric"
                       minlength="6"
                       maxlength="6"
                       oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6)">
            </div>
            
            <div class="action-buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check-circle"></i> Verify OTP
                </button>
                <a href="?resend=1" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Resend OTP
                </a>
            </div>
        </form>
        
        <div class="resend-info">
            Didn't receive code? <a href="?resend=1" class="resend-link">Resend OTP</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Show alert if exists
    <?php if (isset($_SESSION['otp_alert'])): ?>
        Swal.fire({
            title: "<?= $_SESSION['otp_alert']['title'] ?>",
            text: "<?= $_SESSION['otp_alert']['text'] ?>",
            icon: "<?= $_SESSION['otp_alert']['icon'] ?>",
            draggable: <?= $_SESSION['otp_alert']['draggable'] ? 'true' : 'false' ?>,
            timer: 3000,
            timerProgressBar: true
        });
        <?php unset($_SESSION['otp_alert']); ?>
    <?php endif; ?>

    // Input validation
    document.getElementById('otp').addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
    });

    // Form submission validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const otpInput = document.getElementById('otp');
        const errorElement = document.getElementById('errorMessage');
        
        if (otpInput.value.length !== 6) {
            e.preventDefault();
            errorElement.textContent = 'Please enter a 6-digit OTP code';
            errorElement.style.display = 'block';
            otpInput.focus();
        } else {
            errorElement.style.display = 'none';
        }
    });

    // Resend OTP confirmation
    document.querySelectorAll('.resend-link, .btn-secondary').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Resend OTP?',
                text: 'A new verification code will be sent to your Telegram',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#007bff',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, resend',
                cancelButtonText: 'Cancel',
                draggable: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = this.getAttribute('href');
                }
            });
        });
    });
</script>
</body>
</html>