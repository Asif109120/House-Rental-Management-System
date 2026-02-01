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

    // Fetch all bookings with property and user details
    $query = "SELECT b.*, 
              p.title as property_title, p.address, p.city, p.price,
              u1.username as tenant_name, u1.email as tenant_email,
              u2.username as landlord_name, u2.email as landlord_email
              FROM bookings b 
              LEFT JOIN properties p ON b.property_id = p.id
              LEFT JOIN users u1 ON b.tenant_id = u1.id
              LEFT JOIN users u2 ON p.landlord_id = u2.id
              ORDER BY b.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get booking statistics
    $stats = [
        'total' => 0,
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0,
        'cancelled' => 0
    ];

    foreach ($bookings as $booking) {
        $stats['total']++;
        $stats[$booking['status']]++;
    }

} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $error = "Error fetching bookings";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stats-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stats-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
        }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        .status-cancelled { background-color: #e2e3e5; color: #383d41; }
    </style>
</head>
<body class="bg-light">
    <?php include '../includes/admin_header.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-calendar-check me-2"></i>Booking Management</h2>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="text-primary stats-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <h3><?php echo $stats['total']; ?></h3>
                    <p class="text-muted mb-0">Total Bookings</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="text-warning stats-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3><?php echo $stats['pending']; ?></h3>
                    <p class="text-muted mb-0">Pending Bookings</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="text-success stats-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3><?php echo $stats['approved']; ?></h3>
                    <p class="text-muted mb-0">Approved Bookings</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="text-danger stats-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h3><?php echo $stats['rejected'] + $stats['cancelled']; ?></h3>
                    <p class="text-muted mb-0">Rejected/Cancelled</p>
                </div>
            </div>
        </div>

        <!-- Bookings Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Location</th>
                                <th>Price</th>
                                <th>Tenant</th>
                                <th>Landlord</th>
                                <th>Dates</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($booking['property_title']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($booking['address']); ?><br>
                                        <?php echo htmlspecialchars($booking['city']); ?>
                                    </td>
                                    <td>₹<?php echo number_format($booking['price'], 2); ?>/month</td>
                                    <td>
                                        <?php echo htmlspecialchars($booking['tenant_name']); ?><br>
                                        <small><?php echo htmlspecialchars($booking['tenant_email']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($booking['landlord_name']); ?><br>
                                        <small><?php echo htmlspecialchars($booking['landlord_email']); ?></small>
                                    </td>
                                    <td>
                                        <div>From: <?php echo date('M j, Y', strtotime($booking['start_date'])); ?></div>
                                        <div>To: <?php echo date('M j, Y', strtotime($booking['end_date'])); ?></div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $booking['status']; ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <?php if ($booking['status'] === 'pending'): ?>
                                                <button class="btn btn-sm btn-success" onclick="updateBookingStatus(<?php echo $booking['id']; ?>, 'approved')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="updateBookingStatus(<?php echo $booking['id']; ?>, 'rejected')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-primary" onclick="viewBookingDetails(<?php echo $booking['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Details Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Booking Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="bookingDetails">
                    <!-- Details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewBookingDetails(bookingId) {
            fetch(`booking_details.php?id=${bookingId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('bookingDetails').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('bookingModal')).show();
                });
        }

        function updateBookingStatus(bookingId, status) {
            if (confirm('Are you sure you want to ' + status + ' this booking?')) {
                fetch('booking_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=update_status&booking_id=${bookingId}&status=${status}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Error updating booking status');
                    }
                });
            }
        }
    </script>
</body>
</html>
