<?php
require_once '../includes/header.php';
require_once '../config/db.php';

// Check if user is tenant
if ($_SESSION['user_type'] !== 'tenant') {
    header("Location: ../index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get tenant statistics
$stats = [
    'total_bookings' => 0,
    'active_bookings' => 0,
    'pending_bookings' => 0,
    'favorite_properties' => 0
];

// Total bookings
$query = "SELECT COUNT(*) FROM bookings WHERE tenant_id = :tenant_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":tenant_id", $_SESSION['user_id']);
$stmt->execute();
$stats['total_bookings'] = $stmt->fetchColumn();

// Active bookings
$query = "SELECT COUNT(*) FROM bookings WHERE tenant_id = :tenant_id AND status = 'approved'";
$stmt = $db->prepare($query);
$stmt->bindParam(":tenant_id", $_SESSION['user_id']);
$stmt->execute();
$stats['active_bookings'] = $stmt->fetchColumn();

// Pending bookings
$query = "SELECT COUNT(*) FROM bookings WHERE tenant_id = :tenant_id AND status = 'pending'";
$stmt = $db->prepare($query);
$stmt->bindParam(":tenant_id", $_SESSION['user_id']);
$stmt->execute();
$stats['pending_bookings'] = $stmt->fetchColumn();

// Get recent bookings
$query = "SELECT b.*, p.title as property_title, p.address, p.city, u.full_name as landlord_name 
          FROM bookings b
          JOIN properties p ON b.property_id = p.id
          JOIN users u ON p.landlord_id = u.id
          WHERE b.tenant_id = :tenant_id
          ORDER BY b.created_at DESC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(":tenant_id", $_SESSION['user_id']);
$stmt->execute();
$recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h2 class="mb-4">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Total Bookings</h6>
                            <h2 class="mb-0"><?php echo $stats['total_bookings']; ?></h2>
                        </div>
                        <i class="fas fa-calendar fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Active Bookings</h6>
                            <h2 class="mb-0"><?php echo $stats['active_bookings']; ?></h2>
                        </div>
                        <i class="fas fa-calendar-check fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="card bg-warning text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Pending Requests</h6>
                            <h2 class="mb-0"><?php echo $stats['pending_bookings']; ?></h2>
                        </div>
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">New Properties</h6>
                            <h2 class="mb-0"><?php 
                                $query = "SELECT COUNT(*) FROM properties WHERE status = 'available'";
                                $stmt = $db->query($query);
                                echo $stmt->fetchColumn();
                            ?></h2>
                        </div>
                        <i class="fas fa-home fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Quick Actions -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-3">
                        <a href="properties.php" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i> Find Properties
                        </a>
                        <a href="my-bookings.php" class="btn btn-info">
                            <i class="fas fa-list me-2"></i> View My Bookings
                        </a>
                        <a href="../messages.php" class="btn btn-success">
                            <i class="fas fa-envelope me-2"></i> Contact Landlords
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Bookings</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Property</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_bookings as $booking): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($booking['property_title']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['city']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $booking['status'] === 'approved' ? 'success' : 
                                                    ($booking['status'] === 'pending' ? 'warning' : 'danger');
                                            ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($booking['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recent_bookings)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No bookings found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
