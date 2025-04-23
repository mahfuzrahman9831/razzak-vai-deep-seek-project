<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $file_id = trim($_POST['file_id']);
    $file_name = trim($_POST['file_name']);

    $host = 'localhost';
    $db   = 'cbymewng_pass_gsmserver_org';
    $user = 'cbymewng_pass_gsmserver_org';
    $pass = '7NLVGRYndWkBzUxBkGyC';
    $charset = 'utf8';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    echo "<!DOCTYPE html><html><head>
    <title>Check Result</title>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <style>
        :root {
            --primary-color: #00bcd4;
            --primary-hover: #0097a7;
            --success-color: #76ff03;
            --error-color: #ff5252;
            --text-color: #fff;
            --bg-gradient-start: #0d1b2a;
            --bg-gradient-mid: #1b263b;
            --bg-gradient-end: #415a77;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(-45deg, var(--bg-gradient-start), var(--bg-gradient-mid), var(--bg-gradient-end), var(--bg-gradient-mid));
            background-size: 600% 600%;
            animation: navyBG 20s ease infinite;
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        @keyframes navyBG {
            0% {background-position: 0% 50%;}
            50% {background-position: 100% 50%;}
            100% {background-position: 0% 50%;}
        }
        
        .container {
            background: rgba(255, 255, 255, 0.1);
            width: 100%;
            max-width: 500px;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            text-align: center;
        }
        
        h2, h3 {
            margin-bottom: 20px;
        }
        
        .success { 
            color: var(--success-color); 
        }
        
        .error { 
            color: var(--error-color); 
        }
        
        .info-text {
            margin: 15px 0;
            line-height: 1.6;
        }
        
        .password-box {
            font-size: 22px;
            font-weight: bold;
            margin: 20px 0;
            padding: 15px;
            background: rgba(0,0,0,0.2);
            border-radius: 6px;
            word-break: break-all;
        }
        
        .copy-btn {
            padding: 12px 24px;
            background: var(--primary-color);
            color: var(--text-color);
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
            width: 100%;
            max-width: 200px;
        }
        
        .copy-btn:hover {
            background: var(--primary-hover);
        }
        
        #copyMsg {
            margin-top: 10px;
            color: var(--success-color);
            font-size: 14px;
            display: none;
        }
        
        .powered-by {
            margin-top: 30px;
            font-size: 14px;
            color: #ccc;
        }
        
        a { 
            color: #82b1ff; 
            text-decoration: none;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .password-box {
                font-size: 18px;
                padding: 10px;
            }
            
            .copy-btn {
                padding: 10px 20px;
                font-size: 15px;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
                align-items: flex-start;
            }
            
            .container {
                padding: 15px;
            }
            
            h2 {
                font-size: 1.5rem;
            }
            
            h3 {
                font-size: 1.2rem;
            }
            
            .info-text {
                font-size: 0.9rem;
            }
        }
    </style>
    </head><body><div class='container'>";

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);

        $stmtClient = $pdo->prepare("SELECT * FROM clients WHERE email = ? AND status = 'active'");
        $stmtClient->execute([$email]);
        $client = $stmtClient->fetch();

        if ($client) {
            if (!empty($file_id)) {
                $stmtFile = $pdo->prepare("SELECT * FROM files WHERE file_id = ? AND status = 'active'");
                $stmtFile->execute([$file_id]);
            } elseif (!empty($file_name)) {
                $stmtFile = $pdo->prepare("SELECT * FROM files WHERE file_name = ? AND status = 'active'");
                $stmtFile->execute([$file_name]);
            } else {
                echo "<h3 class='error'>Please provide File ID or File Name</h3>";
                echo "<div class='powered-by'>Powered by <a href='https://gsmserver.org'>GSMSERVER.ORG</a></div></div></body></html>";
                exit;
            }

            $file = $stmtFile->fetch();

            if ($file) {
                $password = htmlspecialchars($file['password']);
                echo "<h2 class='success'>Password Found</h2>";
                echo "<p class='info-text'><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
                echo "<p class='info-text'><strong>File:</strong> " . htmlspecialchars($file['file_name'] ?? '') . "</p>";
                echo "<div class='password-box' id='passwordBox'>$password</div>";
                echo "<button class='copy-btn' onclick='copyPassword()'>Copy Password</button>";
                echo "<div id='copyMsg'>Password copied to clipboard!</div>";
            } else {
                echo "<h3 class='error'>File not found or not active.</h3>";
            }
        } else {
            echo "<h3 class='error'>Email not registered or not active.</h3>";
        }
    } catch (PDOException $e) {
        echo "<h3 class='error'>Database Error</h3>";
        echo "<p class='info-text'>" . htmlspecialchars($e->getMessage()) . "</p>";
    }

    echo "<div class='powered-by'>Powered by <a href='https://gsmserver.org'>GSMSERVER.ORG</a></div></div>
    <script>
        function copyPassword() {
            var password = document.getElementById('passwordBox').innerText;
            navigator.clipboard.writeText(password).then(function() {
                var msg = document.getElementById('copyMsg');
                msg.style.display = 'block';
                setTimeout(function() {
                    msg.style.display = 'none';
                }, 3000);
            });
        }
    </script>
    </body></html>";
} else {
    echo '<!DOCTYPE html><html><head>
    <title>GSMSERVER Password Checker</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --primary-color: #00bcd4;
            --primary-hover: #0097a7;
            --text-color: #fff;
            --input-bg: rgba(255,255,255,0.9);
            --bg-gradient-start: #0d1b2a;
            --bg-gradient-mid: #1b263b;
            --bg-gradient-end: #415a77;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(-45deg, var(--bg-gradient-start), var(--bg-gradient-mid), var(--bg-gradient-end), var(--bg-gradient-mid));
            background-size: 600% 600%;
            animation: navyBG 20s ease infinite;
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        @keyframes navyBG {
            0% {background-position: 0% 50%;}
            50% {background-position: 100% 50%;}
            100% {background-position: 0% 50%;}
        }
        
        .form-container {
            background: rgba(255, 255, 255, 0.1);
            width: 100%;
            max-width: 500px;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            text-align: center;
        }
        
        h2 {
            color: #82b1ff;
            margin-bottom: 20px;
        }
        
        input[type="email"],
        input[type="text"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            background: var(--input-bg);
        }
        
        button {
            background-color: var(--primary-color);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
            width: 100%;
            max-width: 200px;
            margin-top: 10px;
        }
        
        button:hover {
            background-color: var(--primary-hover);
        }
        
        .powered-by {
            margin-top: 30px;
            font-size: 14px;
            color: #ccc;
        }
        
        a { 
            color: #82b1ff; 
            text-decoration: none;
        }
        
        @media (max-width: 768px) {
            .form-container {
                padding: 20px;
            }
            
            h2 {
                font-size: 1.5rem;
            }
            
            input[type="email"],
            input[type="text"] {
                padding: 10px;
                font-size: 15px;
            }
            
            button {
                padding: 10px 20px;
                font-size: 15px;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
                align-items: flex-start;
            }
            
            .form-container {
                padding: 15px;
            }
            
            h2 {
                font-size: 1.3rem;
                margin-bottom: 15px;
            }
            
            input[type="email"],
            input[type="text"] {
                padding: 10px;
                font-size: 14px;
            }
        }
    </style>
    </head><body>
    <div class="form-container">
        <h2>Check File Password</h2>
        <form method="POST" onsubmit="return validateForm()">
            <input type="email" name="email" placeholder="Enter your email" required><br>
            <input type="text" name="file_id" id="file_id" placeholder="Enter File ID (or leave blank)"><br>
            <input type="text" name="file_name" id="file_name" placeholder="Enter File Name (if ID is blank)"><br>
            <button type="submit">Check Password</button>
        </form>
        <div class="powered-by"><a href="https://gsmserver.org" target="_blank">GSMSERVER.ORG</a></div>
    </div>
    <script>
        function validateForm() {
            const id = document.getElementById("file_id").value.trim();
            const name = document.getElementById("file_name").value.trim();
            if (id === "" && name === "") {
                alert("Please enter either File ID or File Name.");
                return false;
            }
            return true;
        }
    </script>
    </body></html>';
}
?>