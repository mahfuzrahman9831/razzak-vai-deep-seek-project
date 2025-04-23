<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: a_a_admin.php');
    exit();
}
include 'db.php';

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $package = $_POST['package'];
    $expiry_date = $_POST['expiry_date'];

    $stmt = $conn->prepare("INSERT INTO clients (email, package, expiry_date) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $package, $expiry_date);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Client added successfully";
        header("Location: client_list.php");
        exit();
    } else {
        $message = "<div class='alert alert-danger'>Error: Could not add client.</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Client</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css"> -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --sidebar-width: 220px;
            --sidebar-bg: #2f3e47;
            --sidebar-header-bg: #1b252b;
            --sidebar-hover: #1b252b;
            --sidebar-active: #3498db;
            --topbar-bg: #3498db;
        }
        
        body {
            margin: 0;
            font-family: "Segoe UI", sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            color: white;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            transition: all 0.3s;
            overflow-y: auto;
        }
        
        .sidebar h2 {
            text-align: center;
            padding: 20px 0;
            background: var(--sidebar-header-bg);
            margin: 0;
            font-size: 1.2rem;
            position: sticky;
            top: 0;
        }
        
        .sidebar ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar ul li {
            padding: 12px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s;
        }
        
        .sidebar ul li a {
            color: white;
            text-decoration: none;
            display: block;
        }
        
        .sidebar ul li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .sidebar ul li:hover {
            background: var(--sidebar-hover);
        }
        
        .sidebar ul li.active {
            background: var(--sidebar-active);
        }
        
        /* Mobile Toggle Button */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1100;
            background: var(--topbar-bg);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
        }
        
        /* Topbar Styles */
        .topbar {
            height: 60px;
            background: var(--topbar-bg);
            color: white;
            margin-left: var(--sidebar-width);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            position: sticky;
            top: 0;
            z-index: 900;
            transition: all 0.3s;
        }
        
        /* Content Area */
        .content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s;
        }
        
        /* Form Styles */
        .form-container {
            background: white;
            padding: 30px;
            max-width: 500px;
            margin: 0 auto;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        
        .form-container h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: #3498db;
            outline: none;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #2980b9;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .sidebar {
                left: -100%;
            }
            
            .sidebar.active {
                left: 0;
                width: 80%;
                max-width: 300px;
            }
            
            .topbar, .content {
                margin-left: 0;
            }
            
            .sidebar-toggle {
                display: block;
            }
            
            .form-container {
                padding: 20px;
            }
        }
        
        @media (max-width: 576px) {
            .content {
                padding: 15px;
            }
            
            .form-container {
                padding: 15px;
            }
        }
    </style>
</head>
<body>

<!-- Mobile Toggle Button -->
<button class="sidebar-toggle" id="sidebarToggle">
    <i class="fas fa-bars"></i>
</button>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <h2>Admin Panel</h2>
    <ul>
        <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="add_file.php"><i class="fas fa-file-upload"></i> Add Firmware</a></li>
        <li><a href="firmware_list.php"><i class="fas fa-list"></i> Firmware List</a></li>
        <li class="active"><a href="add_client.php"><i class="fas fa-user-plus"></i> Add Client</a></li>
        <li><a href="manage_client.php"><i class="fas fa-users"></i> Manage Client</a></li>
        <li><a href="account_manage.php"><i class="fas fa-cogs"></i> Account Manage</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- Topbar -->
<div class="topbar">
    <div>Add Client</div>
    <div><i class="fas fa-user-shield"></i> <?= htmlspecialchars($_SESSION['admin']); ?></div>
</div>

<!-- Main Content -->
<div class="content">
    <div class="form-container">
        <h2><i class="fas fa-user-plus"></i> Add New Client</h2>
        <?= $message ?>
        <form method="POST">
            <div class="form-group">
                <input type="email" name="email" class="form-control" placeholder="Client Email" required>
            </div>
            <div class="form-group">
                <input type="text" name="package" class="form-control" placeholder="Package Name" required>
            </div>
            <div class="form-group">
                <input type="date" name="expiry_date" class="form-control" required>
            </div>
            <button type="submit" class="btn"><i class="fas fa-plus-circle"></i> Add Client</button>
        </form>
    </div>
</div>

<!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/js/adminlte.min.js"></script>

<script>
    $(document).ready(function() {
        // Mobile sidebar toggle
        $('#sidebarToggle').click(function() {
            $('#sidebar').toggleClass('active');
        });
        
        // Close sidebar when clicking outside on mobile
        $(document).click(function(e) {
            if ($(window).width() <= 768) {
                if (!$(e.target).closest('#sidebar, #sidebarToggle').length) {
                    $('#sidebar').removeClass('active');
                }
            }
        });
    });
</script>

</body>
</html>