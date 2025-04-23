<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: a_a_admin.php');
    exit();
}
include 'db.php';

// Get client statistics
$totalClientsResult = $conn->query("SELECT COUNT(*) AS total FROM clients");
$totalClients = $totalClientsResult->fetch_assoc()['total'];

$activeClientsResult = $conn->query("SELECT COUNT(*) AS active FROM clients WHERE status = 'Active'");
$activeClients = $activeClientsResult->fetch_assoc()['active'];

$inactiveClientsResult = $conn->query("SELECT COUNT(*) AS inactive FROM clients WHERE status = 'Inactive'");
$inactiveClients = $inactiveClientsResult->fetch_assoc()['inactive'];

// Get firmware statistics
$totalFirmwareResult = $conn->query("SELECT COUNT(*) AS total FROM files");
$totalFirmware = $totalFirmwareResult->fetch_assoc()['total'];

$activeFirmwareResult = $conn->query("SELECT COUNT(*) AS active FROM files WHERE status = 'Active'");
$activeFirmware = $activeFirmwareResult->fetch_assoc()['active'];

$inactiveFirmwareResult = $conn->query("SELECT COUNT(*) AS inactive FROM files WHERE status = 'Inactive'");
$inactiveFirmware = $inactiveFirmwareResult->fetch_assoc()['inactive'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
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
        <li class="active"><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="add_file.php"><i class="fas fa-file-upload"></i> Add Firmware</a></li>
        <li><a href="firmware_list.php"><i class="fas fa-list"></i> Firmware List</a></li>
        <li><a href="add_client.php"><i class="fas fa-user-plus"></i> Add Client</a></li>
        <li><a href="manage_client.php"><i class="fas fa-users"></i> Manage Client</a></li>
        <li><a href="account_manage.php"><i class="fas fa-cogs"></i> Account Manage</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- Topbar -->
<!-- <div class="topbar">
    <div class="topbar-title">GSMSERVER.ORG Dashboard</div>
    <div class="topbar-admin"><i class="fas fa-user-shield"></i> <?= htmlspecialchars($_SESSION['admin']); ?></div>
</div> -->

<!-- Topbar -->
<div class="topbar">
    <div class="topbar-title">GSMSERVER.ORG Dashboard</div>
    <div class="topbar-admin"><i class="fas fa-user-shield"></i> <?= htmlspecialchars($_SESSION['admin']); ?></div>
</div>

<!-- Main Content -->
<div class="content">
    <h2>Dashboard Overview</h2>
    <div class="grid">
        <!-- Client Stats -->
        <div class="card">
            <i class="fas fa-users"></i>
            <a href="client_list.php">Total Clients: <?= $totalClients ?></a>
        </div>
        <div class="card">
            <i class="fas fa-user-check"></i>
            <a href="client_list.php">Active Clients: <?= $activeClients ?></a>
        </div>
        <div class="card">
            <i class="fas fa-user-times"></i>
            <a href="client_list.php">Inactive Clients: <?= $inactiveClients ?></a>
        </div>

        <!-- Firmware Stats -->
        <div class="card">
            <i class="fas fa-file-alt"></i>
            <a href="firmware_list.php">Total Firmware: <?= $totalFirmware ?></a>
        </div>
        <div class="card">
            <i class="fas fa-check-circle"></i>
            <a href="firmware_list.php">Active Firmware: <?= $activeFirmware ?></a>
        </div>
        <div class="card">
            <i class="fas fa-times-circle"></i>
            <a href="firmware_list.php">Inactive Firmware: <?= $inactiveFirmware ?></a>
        </div>
    </div>
</div>

<!-- Footer -->
<div class="footer">
    &copy; <?= date("Y"); ?> Admin Dashboard. All rights reserved.
</div>

<!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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