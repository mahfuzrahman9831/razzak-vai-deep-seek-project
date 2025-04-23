<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: a_a_admin.php');
    exit();
}
include 'db.php';

// Auto-deactivate expired clients
$today = date('Y-m-d');
$conn->query("UPDATE clients SET status = 'Inactive' WHERE expiry_date IS NOT NULL AND expiry_date < '$today' AND status = 'Active'");

// Toggle status if requested
if (isset($_GET['toggle_id'])) {
    $id = (int)$_GET['toggle_id'];
    $stmt = $conn->prepare("SELECT status FROM clients WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $newStatus = $res['status'] === 'Active' ? 'Inactive' : 'Active';
    $u = $conn->prepare("UPDATE clients SET status = ? WHERE id = ?");
    $u->bind_param("si", $newStatus, $id);
    $u->execute();
    
    $_SESSION['status_alert'] = [
        'title' => $newStatus === 'Active' ? 'Activated!' : 'Deactivated!',
        'text' => 'Client status has been updated',
        'icon' => 'success',
        'draggable' => true
    ];
    
    header('Location: client_list.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Client List</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous"> -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/manage_client.css">
</head>
<body>

<!-- Mobile Toggle Button -->
<button class="sidebar-toggle" id="sidebarToggle">
    <i class="fas fa-bars"></i>
</button>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h4 class="mb-0">Admin Panel</h4>
    </div>
    <ul class="sidebar-nav">
        <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="add_file.php"><i class="fas fa-file-upload"></i> Add Firmware</a></li>
        <li><a href="firmware_list.php"><i class="fas fa-list"></i> Firmware List</a></li>
        <li><a href="add_client.php"><i class="fas fa-user-plus"></i> Add Client</a></li>
        <li class="active"><a href="client_list.php"><i class="fas fa-users"></i> Manage Clients</a></li>
        <li><a href="account_manage.php"><i class="fas fa-cogs"></i> Account Settings</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- Topbar -->
<div class="topbar">
    <div class="d-flex align-items-center">
        <h5 class="mb-1">---------Client Management--------</h5>
    </div>
    <div class="d-flex align-items-center">
        <span class="me-2"><i class="fas fa-user-shield"></i></span>
        <span><?= htmlspecialchars($_SESSION['admin']) ?></span>
    </div>
</div>

<!-- Main Content -->
<div class="content">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-users me-2"></i>Client List</h5>
        </div>
        <div class="card-body">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input id="searchInput" type="text" placeholder="Search clients...">
            </div>
            
            <div class="table-responsive">
                <table id="clientTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Package</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                        $res = $conn->query("SELECT * FROM clients ORDER BY id DESC");
                        while ($c = $res->fetch_assoc()):
                        $badgeClass = $c['status'] === 'Active' ? 'badge-success' : 'badge-danger';
                    ?>
                        <tr>
                            <td data-label="ID"><?= $c['id'] ?></td>
                            <td data-label="Email"><?= htmlspecialchars($c['email']) ?></td>
                            <td data-label="Package"><?= htmlspecialchars($c['package']) ?></td>
                            <td data-label="Expiry Date"><?= htmlspecialchars($c['expiry_date'] ?? 'N/A') ?></td>
                            <td data-label="Status"><span class="badge <?= $badgeClass ?>"><?= $c['status'] ?></span></td>
                            <td>
                                <a href="?toggle_id=<?= $c['id'] ?>" 
                                   class="btn btn-sm btn-action <?= $c['status']==='Active' ? 'btn-danger' : 'btn-success' ?> toggle-status">
                                    <?= $c['status']==='Active' ? 'Deactivate' : 'Activate' ?>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        // Show status change alert if exists
        <?php if (isset($_SESSION['status_alert'])): ?>
            Swal.fire({
                title: "<?= $_SESSION['status_alert']['title'] ?>",
                text: "<?= $_SESSION['status_alert']['text'] ?>",
                icon: "<?= $_SESSION['status_alert']['icon'] ?>",
                draggable: <?= $_SESSION['status_alert']['draggable'] ? 'true' : 'false' ?>
            });
            <?php unset($_SESSION['status_alert']); ?>
        <?php endif; ?>

        // Initialize DataTable
        $('#clientTable').DataTable({
            responsive: true,
            lengthChange: false,
            dom: '<"top"f>rt<"bottom"lip><"clear">',
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
                paginate: {
                    previous: "&laquo;",
                    next: "&raquo;"
                }
            }
        });
        
        // Search functionality
        $('#searchInput').on('keyup', function() {
            $('#clientTable').DataTable().search(this.value).draw();
        });
        
        // Mobile sidebar toggle
        $('#sidebarToggle').click(function() {
            $('#sidebar').toggleClass('active');
        });
        
        // Close sidebar when clicking outside on mobile
        $(document).click(function(e) {
            if ($(window).width() <= 992) {
                if (!$(e.target).closest('#sidebar, #sidebarToggle').length) {
                    $('#sidebar').removeClass('active');
                }
            }
        });
        
        // Status toggle confirmation
        $(document).on('click', '.toggle-status', function(e) {
            e.preventDefault();
            const url = $(this).attr('href');
            const action = $(this).text().toLowerCase();
            
            Swal.fire({
                title: `Confirm ${action}?`,
                text: `Are you sure you want to ${action} this client?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: `Yes, ${action}`,
                cancelButtonText: 'Cancel',
                draggable: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    });
</script>

</body>
</html>