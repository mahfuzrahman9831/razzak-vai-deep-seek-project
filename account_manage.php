<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: a_a_admin.php');
    exit();
}
include 'db.php';

$admin_username = $_SESSION['admin'];
$message = "";

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    
    // Telegram 2FA fields
    $telegram_username = trim($_POST['telegram_username'] ?? '');
    $telegram_chat_id = trim($_POST['telegram_chat_id'] ?? '');
    $telegram_bot_token = trim($_POST['telegram_bot_token'] ?? '');

    $update = $conn->prepare("UPDATE admin SET username = ?, email = ?, telegram_username = ?, telegram_chat_id = ?, telegram_bot_token = ? WHERE username = ?");
    $update->bind_param("ssssss", $new_username, $new_email, $telegram_username, $telegram_chat_id, $telegram_bot_token, $admin_username);

    if ($update->execute()) {
        $_SESSION['success_message'] = "Profile updated successfully";
        $_SESSION['admin'] = $new_username;
        $admin_username = $new_username;
    } else {
        $_SESSION['error_message'] = "Failed to update profile";
    }
    $update->close();
    header("Location: account_manage.php");
    exit();
}

// Fetch updated data
$stmt = $conn->prepare("SELECT username, email, role, telegram_username, telegram_chat_id, telegram_bot_token FROM admin WHERE username = ?");
$stmt->bind_param("s", $admin_username);
$stmt->execute();
$stmt->bind_result($username, $email, $role, $telegram_username, $telegram_chat_id, $telegram_bot_token);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Account Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet"> -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="css/account_manage.css">
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
        <li><a href="add_client.php"><i class="fas fa-user-plus"></i> Add Client</a></li>
        <li><a href="manage_client.php"><i class="fas fa-users"></i> Manage Client</a></li>
        <li class="active"><a href="account_manage.php"><i class="fas fa-cogs"></i> Account Manage</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- Topbar -->
<div class="topbar">
    <div>--------------Account Management--------------</div>
    <div><i class="fas fa-user-shield"></i> <?= htmlspecialchars($_SESSION['admin']); ?></div>
</div>

<!-- Main Content -->
<div class="content">
    <div class="section">
        <h2><i class="fas fa-id-badge"></i> Manage Profile Details</h2>

        <form method="post" id="profileForm">
            <div class="form-group">
                <label for="username"><strong>Username</strong></label>
                <input type="text" class="form-control" id="username" name="username" required value="<?= htmlspecialchars($username) ?>">
            </div>
            <div class="form-group">
                <label for="email"><strong>Email</strong></label>
                <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($email) ?>">
            </div>
            <div class="form-group">
                <label><strong>Role</strong></label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($role) ?>" disabled>
            </div>
            <button type="submit" name="save_profile" class="btn-primary"><i class="fas fa-save"></i> Save Changes</button>

            <hr>
            <h2><i class="fas fa-shield-alt"></i> Manage 2FA Security</h2>
            
            <div class="form-group">
                <label for="telegram_username"><strong>Telegram Username</strong></label>
                <input type="text" class="form-control" id="telegram_username" name="telegram_username" 
                       placeholder="e.g., @yourusername" value="<?= htmlspecialchars($telegram_username ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="telegram_chat_id"><strong>Telegram Chat ID</strong></label>
                <input type="text" class="form-control" id="telegram_chat_id" name="telegram_chat_id" 
                       placeholder="e.g., 123456789" value="<?= htmlspecialchars($telegram_chat_id ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="telegram_bot_token"><strong>Telegram Bot Token</strong></label>
                <input type="text" class="form-control" id="telegram_bot_token" name="telegram_bot_token" 
                       placeholder="e.g., 123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11" 
                       value="<?= htmlspecialchars($telegram_bot_token ?? '') ?>">
            </div>
            
            <button type="submit" name="save_2fa" class="btn-primary"><i class="fas fa-save"></i> Update 2FA Settings</button>
        </form>
    </div>
</div>

<!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

<script>
$(document).ready(function() {
    // Show success/error messages
    <?php if (isset($_SESSION['success_message'])): ?>
        Swal.fire({
            title: 'Success!',
            text: '<?= $_SESSION['success_message'] ?>',
            icon: 'success',
            confirmButtonText: 'OK',
            allowOutsideClick: false,
            allowEscapeKey: false
        });
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        Swal.fire({
            title: 'Error!',
            text: '<?= $_SESSION['error_message'] ?>',
            icon: 'error',
            confirmButtonText: 'OK',
            allowOutsideClick: false,
            allowEscapeKey: false
        });
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    
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