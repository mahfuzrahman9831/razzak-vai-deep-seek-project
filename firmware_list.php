<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: a_a_admin.php');
    exit();
}
include 'db.php';

// Toggle status
if (isset($_GET['toggle_id'])) {
    $id = (int)$_GET['toggle_id'];
    $stmt = $conn->prepare("SELECT status FROM files WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $newStatus = $row['status'] === 'Active' ? 'Inactive' : 'Active';
        $u = $conn->prepare("UPDATE files SET status = ? WHERE id = ?");
        $u->bind_param("si", $newStatus, $id);
        $u->execute();
        
        $_SESSION['alert'] = [
            'type' => 'success',
            'title' => $newStatus === 'Active' ? 'Activated Successfully!' : 'Deactivated Successfully!',
            'text' => 'The firmware status has been updated.',
            'draggable' => true
        ];
    }
    header('Location: firmware_list.php');
    exit();
}

// Delete
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    $d = $conn->prepare("DELETE FROM files WHERE id = ?");
    $d->bind_param("i", $id);
    $d->execute();
    
    $_SESSION['alert'] = [
        'type' => 'success',
        'title' => 'Deleted!',
        'text' => 'The firmware has been deleted.',
        'draggable' => true
    ];
    
    header('Location: firmware_list.php');
    exit();
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $limit;
$totalRows = $conn->query("SELECT COUNT(*) as total FROM files")->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);
$result = $conn->query("SELECT * FROM files ORDER BY id DESC LIMIT $offset, $limit");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Firmware List</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"> -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5/themes.css" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="css/firmware_list.css">
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
        <li class="active"><a href="firmware_list.php"><i class="fas fa-list"></i> Firmware List</a></li>
        <li><a href="add_client.php"><i class="fas fa-user-plus"></i> Add Client</a></li>
        <li><a href="manage_client.php"><i class="fas fa-users"></i> Manage Client</a></li>
        <li><a href="account_manage.php"><i class="fas fa-cogs"></i> Account Manage</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- Topbar -->
<div class="topbar">
    <div class="topbar-title">Firmware List</div>
    <div class="topbar-admin"><i class="fas fa-user-shield"></i> <?= htmlspecialchars($_SESSION['admin']); ?></div>
</div>

<!-- Main Content -->
<div class="content">
    <div class="section">
        <h2 class="mb-3"><i class="fas fa-microchip"></i> Firmware Files</h2>

        <div class="search-box">
            <!-- <input type="text" id="searchInput" onkeyup="searchFirmware()" placeholder="Search firmware..." class="form-control"> -->
            <input type="text" id="searchInput" onkeyup="searchFirmware()" placeholder="Search firmware..." class="input">
            <!-- <input type="text" placeholder="Search Firmware" class="input" /> -->
        </div>

        <div class="table-container">
            <table id="firmwareTable">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>File ID</th>
                        <th>File Name</th>
                        <th>Password</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $serial = $offset + 1;
                    while ($row = $result->fetch_assoc()):
                        $statusClass = $row['status'] === 'Active' ? 'status-active' : 'status-inactive';
                    ?>
                    <tr>
                        <td data-label="S.No"><?= $serial++; ?></td>
                        <td data-label="File ID"><?= htmlspecialchars($row['file_id']); ?></td>
                        <td data-label="File Name"><?= htmlspecialchars($row['file_name']); ?></td>
                        <td data-label="Password"><?= htmlspecialchars($row['password']); ?></td>
                        <td data-label="Status">
                            <span class="status-badge <?= $statusClass; ?>"><?= $row['status']; ?></span>
                        </td>
                        <td data-label="Actions">
                            <div class="action-buttons">
                                <a href="?toggle_id=<?= $row['id']; ?>" 
                                   class="action-btn toggle-btn">
                                   <i class="fas fa-power-off"></i>
                                   <?= $row['status'] === 'Active' ? 'Deactivate' : 'Activate'; ?>
                                </a>
                                <a href="#" 
                                   onclick="confirmDelete(<?= $row['id']; ?>)" 
                                   class="action-btn delete-btn">
                                   <i class="fas fa-trash-alt"></i>
                                   Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

      
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="join mt-2">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1; ?>" class="join-item btn btn-outline btn-error">Previous</a>
            <?php endif; ?>
            
            <div class="join">
            <?php 
            // Show page numbers
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            
            for ($i = $start; $i <= $end; $i++): ?>
                <a href="?page=<?= $i; ?>" class="btn <?= $page === $i ? 'join-item btn' : 'join-item btn' ?>"><?= $i; ?></a>
            <?php endfor; ?>
            </div>
            
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1; ?>" class="join-item btn btn-outline btn-secondary">Next</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

<script>
    // Mobile sidebar toggle
    $(document).ready(function() {
        $('#sidebarToggle').click(function(e) {
            e.stopPropagation();
            $('#sidebar').toggleClass('active');
        });
        
        // Close sidebar when clicking outside
        $(document).click(function(e) {
            if ($(window).width() <= 992) {
                if (!$(e.target).closest('#sidebar').length && !$(e.target).is('#sidebarToggle')) {
                    $('#sidebar').removeClass('active');
                }
            }
        });
        
        // Close sidebar when a menu item is clicked on mobile
        $('.sidebar a').click(function() {
            if ($(window).width() <= 992) {
                $('#sidebar').removeClass('active');
            }
        });
        
        // Show alert if exists
        <?php if (isset($_SESSION['alert'])): ?>
            Swal.fire({
                title: "<?= $_SESSION['alert']['title']; ?>",
                text: "<?= $_SESSION['alert']['text']; ?>",
                icon: "<?= $_SESSION['alert']['type']; ?>",
                draggable: <?= $_SESSION['alert']['draggable'] ? 'true' : 'false'; ?>
            });
            <?php unset($_SESSION['alert']); ?>
        <?php endif; ?>
    });
    
    function searchFirmware() {
        const input = document.getElementById("searchInput").value.toLowerCase();
        const rows = document.querySelectorAll("#firmwareTable tbody tr");
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(input) ? "" : "none";
        });
    }
    
    function confirmDelete(id) {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "?delete_id=" + id;
            }
        });
    }
</script>

</body>
</html>