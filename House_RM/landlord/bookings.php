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

// Get all bookings for landlord's properties
$query = "SELECT b.*, p.title as property_title, p.price, u.full_name as tenant_name, u.email as tenant_email, u.phone as tenant_phone
          FROM bookings b
          JOIN properties p ON b.property_id = p.id
          JOIN users u ON b.tenant_id = u.id
          WHERE p.landlord_id = :landlord_id
          ORDER BY b.created_at DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(":landlord_id", $_SESSION['user_id']);
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h2 class="mb-4">Booking Requests</h2>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Tenant</th>
                            <th>Contact</th>
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
                                <td><?php echo htmlspecialchars($booking['tenant_name']); ?></td>
                                <td>
                                    <div>Email: <?php echo htmlspecialchars($booking['tenant_email']); ?></div>
                                    <div>Phone: <?php echo htmlspecialchars($booking['tenant_phone']); ?></div>
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
                                        <button class="btn btn-sm btn-success" onclick="updateBookingStatus(<?php echo $booking['id']; ?>, 'approved')">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="updateBookingStatus(<?php echo $booking['id']; ?>, 'rejected')">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-primary" onclick="contactTenant(<?php echo $booking['tenant_id']; ?>)">
                                        <i class="fas fa-envelope"></i> Message
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No booking requests found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function updateBookingStatus(bookingId, status) {
    if (confirm('Are you sure you want to ' + status + ' this booking?')) {
        fetch('booking_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=update_status&booking_id=' + bookingId + '&status=' + status
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error updating booking status');
            }
        });
    }
}

function contactTenant(tenantId) {
    window.location.href = '../messages.php?user=' + tenantId;
}
</script>

<?php require_once '../includes/footer.php'; ?>
