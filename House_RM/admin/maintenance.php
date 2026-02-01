<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get all maintenance requests with related information
    $sql = "SELECT mr.*, 
            p.title as property_title, 
            p.address,
            u_tenant.full_name as tenant_name,
            u_tenant.email as tenant_email,
            u_landlord.full_name as landlord_name,
            u_landlord.email as landlord_email
            FROM maintenance_requests mr
            INNER JOIN properties p ON mr.property_id = p.id
            INNER JOIN users u_tenant ON mr.tenant_id = u_tenant.id
            INNER JOIN users u_landlord ON p.landlord_id = u_landlord.id
            ORDER BY 
                CASE mr.status
                    WHEN 'pending' THEN 1
                    WHEN 'in_progress' THEN 2
                    WHEN 'completed' THEN 3
                    WHEN 'cancelled' THEN 4
                END,
                mr.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get statistics
    $stats = [
        'total' => count($requests),
        'pending' => 0,
        'in_progress' => 0,
        'completed' => 0,
        'cancelled' => 0
    ];

    foreach ($requests as $request) {
        $stats[$request['status']]++;
    }

} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $_SESSION['error'] = "Error fetching data. Please try again later.";
    $requests = [];
    $stats = [
        'total' => 0,
        'pending' => 0,
        'in_progress' => 0,
        'completed' => 0,
        'cancelled' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Requests - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stats-card {
            transition: transform 0.2s;
            border-radius: 10px;
            cursor: pointer;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .status-pending { background-color: #ffc107; color: #000; }
        .status-in_progress { background-color: #17a2b8; color: #fff; }
        .status-completed { background-color: #28a745; color: #fff; }
        .status-cancelled { background-color: #dc3545; color: #fff; }
        .contact-info {
            font-size: 0.9rem;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
            margin-top: 10px;
        }
    </style>
</head>
<body class="bg-light">
    <?php include '../includes/admin_header.php'; ?>

    <div class="container-fluid py-4">
        <h2 class="mb-4">
            <i class="fas fa-tools me-2"></i>Maintenance Requests Overview
        </h2>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md mb-3">
                <div class="card stats-card bg-primary text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Total Requests</h6>
                                <h2 class="mb-0"><?php echo $stats['total']; ?></h2>
                            </div>
                            <i class="fas fa-clipboard-list fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md mb-3">
                <div class="card stats-card bg-warning h-100" onclick="filterByStatus('pending')">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Pending</h6>
                                <h2 class="mb-0"><?php echo $stats['pending']; ?></h2>
                            </div>
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md mb-3">
                <div class="card stats-card bg-info text-white h-100" onclick="filterByStatus('in_progress')">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">In Progress</h6>
                                <h2 class="mb-0"><?php echo $stats['in_progress']; ?></h2>
                            </div>
                            <i class="fas fa-hammer fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md mb-3">
                <div class="card stats-card bg-success text-white h-100" onclick="filterByStatus('completed')">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Completed</h6>
                                <h2 class="mb-0"><?php echo $stats['completed']; ?></h2>
                            </div>
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md mb-3">
                <div class="card stats-card bg-danger text-white h-100" onclick="filterByStatus('cancelled')">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Cancelled</h6>
                                <h2 class="mb-0"><?php echo $stats['cancelled']; ?></h2>
                            </div>
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($requests)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>No maintenance requests found.
            </div>
        <?php else: ?>
            <!-- Requests Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Property</th>
                                    <th>Issue</th>
                                    <th>Tenant</th>
                                    <th>Landlord</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $request): ?>
                                    <tr class="request-row" data-status="<?php echo $request['status']; ?>">
                                        <td>#<?php echo $request['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($request['property_title']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($request['address']); ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($request['title']); ?></strong><br>
                                            <small class="text-muted">
                                                <?php echo substr(htmlspecialchars($request['description']), 0, 50) . '...'; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($request['tenant_name']); ?><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($request['tenant_email']); ?></small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($request['landlord_name']); ?><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($request['landlord_email']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge status-<?php echo $request['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-info btn-sm" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#detailsModal<?php echo $request['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Details Modals -->
            <?php foreach ($requests as $request): ?>
                <div class="modal fade" id="detailsModal<?php echo $request['id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-tools me-2"></i>Maintenance Request #<?php echo $request['id']; ?>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Property Details</h6>
                                        <p>
                                            <strong><?php echo htmlspecialchars($request['property_title']); ?></strong><br>
                                            <?php echo htmlspecialchars($request['address']); ?>
                                        </p>

                                        <h6>Issue Details</h6>
                                        <p>
                                            <strong><?php echo htmlspecialchars($request['title']); ?></strong><br>
                                            <?php echo nl2br(htmlspecialchars($request['description'])); ?>
                                        </p>

                                        <h6>Status</h6>
                                        <span class="badge status-<?php echo $request['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                        </span>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Tenant Information</h6>
                                        <div class="contact-info">
                                            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($request['tenant_name']); ?><br>
                                            <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($request['tenant_email']); ?>
                                        </div>

                                        <h6 class="mt-3">Landlord Information</h6>
                                        <div class="contact-info">
                                            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($request['landlord_name']); ?><br>
                                            <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($request['landlord_email']); ?>
                                        </div>

                                        <h6 class="mt-3">Timeline</h6>
                                        <div class="contact-info">
                                            <i class="fas fa-clock me-2"></i>Created: 
                                            <?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterByStatus(status) {
            const rows = document.querySelectorAll('.request-row');
            rows.forEach(row => {
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
