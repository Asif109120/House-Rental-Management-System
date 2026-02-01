<?php
require_once '../includes/header.php';
require_once '../config/db.php';

// Check if user is landlord
if ($_SESSION['user_type'] !== 'landlord') {
    header("Location: ../index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get landlord statistics
$stats = [
    'total_properties' => 0,
    'active_bookings' => 0,
    'pending_bookings' => 0,
    'total_tenants' => 0
];

// Total properties
$query = "SELECT COUNT(*) FROM properties WHERE landlord_id = :landlord_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":landlord_id", $_SESSION['user_id']);
$stmt->execute();
$stats['total_properties'] = $stmt->fetchColumn();

// Active bookings
$query = "SELECT COUNT(*) FROM bookings b 
          JOIN properties p ON b.property_id = p.id 
          WHERE p.landlord_id = :landlord_id AND b.status = 'approved'";
$stmt = $db->prepare($query);
$stmt->bindParam(":landlord_id", $_SESSION['user_id']);
$stmt->execute();
$stats['active_bookings'] = $stmt->fetchColumn();

// Pending bookings
$query = "SELECT COUNT(*) FROM bookings b 
          JOIN properties p ON b.property_id = p.id 
          WHERE p.landlord_id = :landlord_id AND b.status = 'pending'";
$stmt = $db->prepare($query);
$stmt->bindParam(":landlord_id", $_SESSION['user_id']);
$stmt->execute();
$stats['pending_bookings'] = $stmt->fetchColumn();

// Get recent bookings
$query = "SELECT b.*, p.title as property_title, u.full_name as tenant_name 
          FROM bookings b
          JOIN properties p ON b.property_id = p.id
          JOIN users u ON b.tenant_id = u.id
          WHERE p.landlord_id = :landlord_id
          ORDER BY b.created_at DESC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(":landlord_id", $_SESSION['user_id']);
$stmt->execute();
$recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h2 class="mb-4">Landlord Dashboard</h2>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Total Properties</h6>
                            <h2 class="mb-0"><?php echo $stats['total_properties']; ?></h2>
                        </div>
                        <i class="fas fa-building fa-2x"></i>
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
                            <h6 class="mb-0">Pending Bookings</h6>
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
                            <h6 class="mb-0">Monthly Revenue</h6>
                            <h2 class="mb-0">₹<?php 
                                $query = "SELECT SUM(price) FROM properties 
                                         WHERE landlord_id = :landlord_id AND status = 'rented'";
                                $stmt = $db->prepare($query);
                                $stmt->bindParam(":landlord_id", $_SESSION['user_id']);
                                $stmt->execute();
                                echo number_format($stmt->fetchColumn(), 2);
                            ?></h2>
                        </div>
                        <i class="fas fa-rupee-sign fa-2x"></i>
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
                            <i class="fas fa-plus-circle me-2"></i> Add New Property
                        </a>
                        <a href="bookings.php" class="btn btn-info">
                            <i class="fas fa-list me-2"></i> View All Bookings
                        </a>
                        <a href="../messages.php" class="btn btn-success">
                            <i class="fas fa-envelope me-2"></i> Check Messages
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Booking Requests</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Property</th>
                                    <th>Tenant</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_bookings as $booking): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($booking['property_title']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['tenant_name']); ?></td>
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
                                        <td colspan="4" class="text-center">No recent booking requests</td>
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
