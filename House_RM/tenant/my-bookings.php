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

// Get all bookings for this tenant
$query = "SELECT b.*, p.title as property_title, p.address, p.city, p.price,
          u.id as landlord_id, u.full_name as landlord_name, u.email as landlord_email, u.phone as landlord_phone
          FROM bookings b
          JOIN properties p ON b.property_id = p.id
          JOIN users u ON p.landlord_id = u.id
          WHERE b.tenant_id = :tenant_id
          ORDER BY b.created_at DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(":tenant_id", $_SESSION['user_id']);
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h2 class="mb-4">My Bookings</h2>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Location</th>
                            <th>Landlord</th>
                            <th>Dates</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['property_title']); ?></td>
                                <td>
                                    <div><?php echo htmlspecialchars($booking['address']); ?></div>
                                    <div><?php echo htmlspecialchars($booking['city']); ?></div>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($booking['landlord_name']); ?></div>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($booking['landlord_email']); ?><br>
                                        <?php echo htmlspecialchars($booking['landlord_phone']); ?>
                                    </small>
                                </td>
                                <td>
                                    <div>From: <?php echo date('M j, Y', strtotime($booking['start_date'])); ?></div>
                                    <div>To: <?php echo date('M j, Y', strtotime($booking['end_date'])); ?></div>
                                </td>
                                <td>₹<?php echo number_format($booking['price'], 2); ?>/month</td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $booking['status'] === 'approved' ? 'success' : 
                                            ($booking['status'] === 'pending' ? 'warning' : 'danger');
                                    ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($booking['status'] === 'pending'): ?>
                                        <button class="btn btn-sm btn-danger" onclick="cancelBooking(<?php echo $booking['id']; ?>)">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    <?php endif; ?>
                                    <a href="../messages.php?user=<?php echo $booking['landlord_id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-envelope"></i> Message
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No bookings found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function cancelBooking(bookingId) {
    if (confirm('Are you sure you want to cancel this booking?')) {
        fetch('booking_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=cancel&booking_id=' + bookingId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error canceling booking: ' + (data.message || 'Unknown error'));
            }
        });
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
