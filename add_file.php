<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: a_a_admin.php');
    exit();
}
include 'db.php';

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $file_id = $_POST['file_id'];
    $file_name = $_POST['file_name'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("INSERT INTO files (file_id, file_name, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $file_id, $file_name, $password);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "File added successfully";
        header("Location: firmware_list.php");
        exit();
    } else {
        $message = "<div class='alert alert-danger'>Error: Could not add file.</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add File</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css"> -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="css/add_file.css">
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
        <li  class="active"><a href="add_file.php"><i class="fas fa-file-upload"></i> Add Firmware</a></li>
        <li><a href="firmware_list.php"><i class="fas fa-list"></i> Firmware List</a></li>
        <li><a href="add_client.php"><i class="fas fa-user-plus"></i> Add Client</a></li>
        <li><a href="manage_client.php"><i class="fas fa-users"></i> Manage Client</a></li>
        <li><a href="account_manage.php"><i class="fas fa-cogs"></i> Account Manage</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- Topbar -->
<div class="topbar">
    <div class="topbar-title">Add Firmware</div>
    <div class="topbar-admin"><i class="fas fa-user-shield"></i> <?= htmlspecialchars($_SESSION['admin']); ?></div>
</div>

<!-- Main Content -->
<div class="content">
    <div class="form-container">
        <h2><i class="fas fa-file-circle-plus"></i> Add New Firmware</h2>
        <?= $message ?>
        <form method="POST">
            <div class="form-group">
                <input type="text" name="file_id" class="form-control" placeholder="File ID" required>
            </div>
            <div class="form-group">
                <input type="text" name="file_name" class="form-control" placeholder="File Name" required>
            </div>
            <div class="form-group">
                <input type="text" name="password" class="form-control" placeholder="Password" required>
            </div>
            <button type="submit" class="btn"><i class="fas fa-plus-circle"></i> Add File</button>
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