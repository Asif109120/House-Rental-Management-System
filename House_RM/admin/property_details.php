<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    echo "Property ID not provided";
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Fetch property details with landlord information and booking history
    $query = "SELECT p.*, u.username as landlord_name, u.email as landlord_email, u.phone as landlord_phone
              FROM properties p 
              LEFT JOIN users u ON p.landlord_id = u.id 
              WHERE p.id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $_GET['id']);
    $stmt->execute();
    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$property) {
        echo "Property not found";
        exit();
    }

    // Fetch booking history
    $query = "SELECT b.*, u.username as tenant_name, u.email as tenant_email
              FROM bookings b
              LEFT JOIN users u ON b.tenant_id = u.id
              WHERE b.property_id = :property_id
              ORDER BY b.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":property_id", $_GET['id']);
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col-md-6">
        <h5>Property Information</h5>
        <table class="table">
            <tr>
                <th>Title</th>
                <td><?php echo htmlspecialchars($property['title']); ?></td>
            </tr>
            <tr>
                <th>Address</th>
                <td><?php echo htmlspecialchars($property['address'] . ', ' . $property['city']); ?></td>
            </tr>
            <tr>
                <th>Type</th>
                <td><?php echo htmlspecialchars($property['type']); ?></td>
            </tr>
            <tr>
                <th>Price</th>
                <td>₹<?php echo number_format($property['price'], 2); ?>/month</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>
                    <span class="badge bg-<?php 
                        echo $property['status'] === 'available' ? 'success' : 
                            ($property['status'] === 'rented' ? 'warning' : 'secondary');
                    ?>">
                        <?php echo ucfirst($property['status']); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>Added Date</th>
                <td><?php echo date('M j, Y', strtotime($property['created_at'])); ?></td>
            </tr>
        </table>

        <h5 class="mt-4">Landlord Information</h5>
        <table class="table">
            <tr>
                <th>Name</th>
                <td><?php echo htmlspecialchars($property['landlord_name']); ?></td>
            </tr>
            <tr>
                <th>Email</th>
                <td><?php echo htmlspecialchars($property['landlord_email']); ?></td>
            </tr>
            <tr>
                <th>Phone</th>
                <td><?php echo htmlspecialchars($property['landlord_phone']); ?></td>
            </tr>
        </table>
    </div>

    <div class="col-md-6">
        <h5>Booking History</h5>
        <?php if (empty($bookings)): ?>
            <div class="alert alert-info">No booking history found.</div>
        <?php else: ?>
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Tenant</th>
                            <th>Period</th>
                            <th>Status</th>
                            <th>Booked On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td>
                                    <div><?php echo htmlspecialchars($booking['tenant_name']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($booking['tenant_email']); ?></small>
                                </td>
                                <td>
                                    <div>From: <?php echo date('M j, Y', strtotime($booking['start_date'])); ?></div>
                                    <div>To: <?php echo date('M j, Y', strtotime($booking['end_date'])); ?></div>
                                </td>
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
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
} catch (PDOException $e) {
    echo "Error fetching property details: " . $e->getMessage();
}
?>
