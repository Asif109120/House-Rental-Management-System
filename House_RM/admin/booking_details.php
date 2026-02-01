<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo "Unauthorized access";
    exit();
}

// Check if booking ID is provided
if (!isset($_GET['id'])) {
    echo "Booking ID not provided";
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Fetch booking details with property and user information
    $query = "SELECT b.*, 
              p.title as property_title, p.address, p.city, p.price,
              p.bedrooms, p.bathrooms, p.area,
              u1.username as tenant_name, u1.email as tenant_email,
              u1.phone as tenant_phone, u1.full_name as tenant_full_name,
              u2.username as landlord_name, u2.email as landlord_email,
              u2.phone as landlord_phone, u2.full_name as landlord_full_name
              FROM bookings b 
              LEFT JOIN properties p ON b.property_id = p.id
              LEFT JOIN users u1 ON b.tenant_id = u1.id
              LEFT JOIN users u2 ON p.landlord_id = u2.id
              WHERE b.id = :booking_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':booking_id', $_GET['id'], PDO::PARAM_INT);
    $stmt->execute();
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo "Booking not found";
        exit();
    }
?>

<div class="container-fluid p-4">
    <div class="row">
        <!-- Property Details -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-home me-2"></i>Property Details</h5>
                </div>
                <div class="card-body">
                    <h6 class="fw-bold"><?php echo htmlspecialchars($booking['property_title']); ?></h6>
                    <p class="text-muted mb-2">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        <?php echo htmlspecialchars($booking['address'] . ', ' . $booking['city']); ?>
                    </p>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <small class="text-muted d-block">Price</small>
                            <strong>$<?php echo number_format($booking['price'], 2); ?>/month</strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Area</small>
                            <strong><?php echo $booking['area']; ?> sq ft</strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Bedrooms</small>
                            <strong><?php echo $booking['bedrooms']; ?></strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Bathrooms</small>
                            <strong><?php echo $booking['bathrooms']; ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Booking Details -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Booking Information</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <small class="text-muted d-block">Start Date</small>
                            <strong><?php echo date('M j, Y', strtotime($booking['start_date'])); ?></strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">End Date</small>
                            <strong><?php echo date('M j, Y', strtotime($booking['end_date'])); ?></strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Status</small>
                            <span class="badge bg-<?php 
                                echo $booking['status'] === 'approved' ? 'success' : 
                                    ($booking['status'] === 'pending' ? 'warning' : 
                                    ($booking['status'] === 'rejected' ? 'danger' : 'secondary')); 
                            ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Created On</small>
                            <strong><?php echo date('M j, Y', strtotime($booking['created_at'])); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tenant Details -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Tenant Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Full Name</small>
                        <strong><?php echo htmlspecialchars($booking['tenant_full_name']); ?></strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Username</small>
                        <strong><?php echo htmlspecialchars($booking['tenant_name']); ?></strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Email</small>
                        <strong><?php echo htmlspecialchars($booking['tenant_email']); ?></strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Phone</small>
                        <strong><?php echo htmlspecialchars($booking['tenant_phone'] ?? 'Not provided'); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Landlord Details -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>Landlord Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Full Name</small>
                        <strong><?php echo htmlspecialchars($booking['landlord_full_name']); ?></strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Username</small>
                        <strong><?php echo htmlspecialchars($booking['landlord_name']); ?></strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Email</small>
                        <strong><?php echo htmlspecialchars($booking['landlord_email']); ?></strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Phone</small>
                        <strong><?php echo htmlspecialchars($booking['landlord_phone'] ?? 'Not provided'); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    echo "Error fetching booking details";
}
?>
