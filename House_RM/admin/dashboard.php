<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get statistics
$stats = [
    'total_users' => 0,
    'total_properties' => 0,
    'active_bookings' => 0,
    'total_landlords' => 0,
    'total_tenants' => 0,
    'available_properties' => 0,
    'rented_properties' => 0
];

// Get statistics queries
$queries = [
    'total_users' => "SELECT COUNT(*) FROM users WHERE status = 'active'",
    'total_properties' => "SELECT COUNT(*) FROM properties",
    'active_bookings' => "SELECT COUNT(*) FROM bookings WHERE status = 'approved'",
    'total_landlords' => "SELECT COUNT(*) FROM users WHERE user_type = 'landlord' AND status = 'active'",
    'total_tenants' => "SELECT COUNT(*) FROM users WHERE user_type = 'tenant' AND status = 'active'",
    'available_properties' => "SELECT COUNT(*) FROM properties WHERE status = 'available'",
    'rented_properties' => "SELECT COUNT(*) FROM properties WHERE status = 'rented'"
];

foreach ($queries as $key => $query) {
    $stmt = $db->query($query);
    $stats[$key] = $stmt->fetchColumn();
}

// Recent activities
$query = "SELECT b.*, p.title as property_title, u.username as tenant_name 
          FROM bookings b
          JOIN properties p ON b.property_id = p.id
          JOIN users u ON b.tenant_id = u.id
          ORDER BY b.created_at DESC
          LIMIT 5";
$stmt = $db->query($query);
$recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent properties
$query = "SELECT p.*, u.username as landlord_name 
          FROM properties p
          JOIN users u ON p.landlord_id = u.id
          ORDER BY p.created_at DESC 
          LIMIT 5";
$stmt = $db->query($query);
$recent_properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stats-card {
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .activity-timeline {
            position: relative;
            padding-left: 30px;
        }
        .activity-timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            height: 100%;
            width: 2px;
            background: #dee2e6;
        }
        .activity-item {
            position: relative;
            margin-bottom: 1.5rem;
        }
        .activity-item::before {
            content: '';
            position: absolute;
            left: -30px;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #0d6efd;
        }
    </style>
</head>
<body class="bg-light">
    <?php include '../includes/admin_header.php'; ?>

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <!-- Statistics Cards -->
            <div class="col-md-3 mb-4">
                <div class="card stats-card bg-primary text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Total Users</h6>
                                <h3 class="mb-0"><?php echo $stats['total_users']; ?></h3>
                            </div>
                            <div class="fs-1">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="card stats-card bg-success text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Properties</h6>
                                <h3 class="mb-0"><?php echo $stats['total_properties']; ?></h3>
                            </div>
                            <div class="fs-1">
                                <i class="fas fa-building"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="card stats-card bg-info text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Active Bookings</h6>
                                <h3 class="mb-0"><?php echo $stats['active_bookings']; ?></h3>
                            </div>
                            <div class="fs-1">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="card stats-card bg-warning text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Available Properties</h6>
                                <h3 class="mb-0"><?php echo $stats['available_properties']; ?></h3>
                            </div>
                            <div class="fs-1">
                                <i class="fas fa-home"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Properties -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Properties</h5>
                        <a href="properties.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Landlord</th>
                                        <th>Status</th>
                                        <th>Added</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_properties as $property): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($property['title']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($property['address']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($property['landlord_name']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $property['status'] === 'available' ? 'success' : 
                                                        ($property['status'] === 'rented' ? 'warning' : 'secondary');
                                                ?>">
                                                    <?php echo ucfirst($property['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($property['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Bookings</h5>
                    </div>
                    <div class="card-body">
                        <div class="activity-timeline">
                            <?php foreach ($recent_bookings as $booking): ?>
                                <div class="activity-item">
                                    <div class="mb-1">
                                        <strong><?php echo htmlspecialchars($booking['tenant_name']); ?></strong> 
                                        booked 
                                        <strong><?php echo htmlspecialchars($booking['property_title']); ?></strong>
                                    </div>
                                    <div class="text-muted">
                                        <small>
                                            From: <?php echo date('M j, Y', strtotime($booking['start_date'])); ?> -
                                            To: <?php echo date('M j, Y', strtotime($booking['end_date'])); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-<?php 
                                        echo $booking['status'] === 'approved' ? 'success' : 
                                            ($booking['status'] === 'pending' ? 'warning' : 'danger');
                                    ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
