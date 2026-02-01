<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is a landlord
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'landlord') {
    header("Location: ../auth/login.php");
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get maintenance requests for landlord's properties
    $sql = "SELECT mr.*, p.title as property_title, u.full_name as tenant_name, u.email as tenant_email 
            FROM maintenance_requests mr
            INNER JOIN properties p ON mr.property_id = p.id
            INNER JOIN users u ON mr.tenant_id = u.id
            WHERE p.landlord_id = :landlord_id
            ORDER BY 
                CASE mr.status
                    WHEN 'pending' THEN 1
                    WHEN 'in_progress' THEN 2
                    WHEN 'completed' THEN 3
                    WHEN 'cancelled' THEN 4
                END,
                mr.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':landlord_id', $_SESSION['user_id'], PDO::PARAM_INT);
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
    $_SESSION['error'] = "Error fetching maintenance requests. Please try again later.";
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
    <title>Maintenance Requests - House Rental Management System</title>
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
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container py-4">
        <h2 class="mb-4">
            <i class="fas fa-tools me-2"></i>Maintenance Requests
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
                <div class="card stats-card bg-warning h-100">
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
                <div class="card stats-card bg-info text-white h-100">
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
                <div class="card stats-card bg-success text-white h-100">
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
                <div class="card stats-card bg-danger text-white h-100">
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
            <div class="row">
                <?php foreach ($requests as $request): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><?php echo htmlspecialchars($request['title']); ?></h5>
                                <span class="badge status-<?php echo $request['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">
                                    <?php echo htmlspecialchars($request['property_title']); ?>
                                </h6>
                                <p class="card-text">
                                    <?php echo nl2br(htmlspecialchars($request['description'])); ?>
                                </p>
                                <div class="text-muted small mb-3">
                                    <i class="fas fa-user me-1"></i>
                                    Tenant: <?php echo htmlspecialchars($request['tenant_name']); ?><br>
                                    <i class="fas fa-envelope me-1"></i>
                                    Email: <?php echo htmlspecialchars($request['tenant_email']); ?><br>
                                    <i class="fas fa-clock me-1"></i>
                                    Submitted: <?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?>
                                </div>
                                <?php if ($request['status'] === 'pending'): ?>
                                    <form action="maintenance_actions.php" method="POST" class="d-inline">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <input type="hidden" name="action" value="start">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-play me-1"></i>Start Work
                                        </button>
                                    </form>
                                <?php elseif ($request['status'] === 'in_progress'): ?>
                                    <form action="maintenance_actions.php" method="POST" class="d-inline">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <input type="hidden" name="action" value="complete">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-check me-1"></i>Mark as Completed
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
