<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    header("Location: ../auth/login.php");
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get user info for the header
    $sql = "SELECT username FROM users WHERE id = :user_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $_SESSION['username'] = $user['username'];

    // First, check if user has any approved bookings
    $booking_sql = "SELECT b.*, p.title as property_title 
                   FROM bookings b 
                   JOIN properties p ON b.property_id = p.id 
                   WHERE b.tenant_id = :tenant_id 
                   AND b.status = 'approved'";
    
    $stmt = $db->prepare($booking_sql);
    $stmt->bindParam(':tenant_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $active_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get maintenance requests for the tenant
    $sql = "SELECT mr.*, p.title as property_title 
            FROM maintenance_requests mr
            INNER JOIN properties p ON mr.property_id = p.id
            WHERE mr.tenant_id = :tenant_id
            ORDER BY mr.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':tenant_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get tenant's rented properties for the new request form
    $properties = $active_bookings; // Use the active bookings as properties

    // Only log debug info, don't show on page
    error_log("User ID: " . $_SESSION['user_id']);
    error_log("Username: " . $_SESSION['username']);
    error_log("Properties found: " . count($properties));

} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $_SESSION['error'] = "Error fetching data. Please try again later.";
    $requests = [];
    $properties = [];
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
        .status-badge {
            font-size: 0.8rem;
            padding: 0.3rem 0.6rem;
            border-radius: 1rem;
        }
        .status-pending { background-color: #ffc107; color: #000; }
        .status-in_progress { background-color: #17a2b8; color: #fff; }
        .status-completed { background-color: #28a745; color: #fff; }
        .status-cancelled { background-color: #dc3545; color: #fff; }
        .user-dropdown {
            position: absolute;
            top: 1rem;
            right: 1rem;
        }
        .user-dropdown .dropdown-menu {
            margin-top: 0.5rem;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-tools me-2"></i>My Maintenance Requests</h2>
            <?php if (!empty($properties)): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newRequestModal">
                    <i class="fas fa-plus me-2"></i>New Request
                </button>
            <?php endif; ?>
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

        <?php if (empty($properties)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>You need to have an active rental to submit maintenance requests.
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
                                <span class="status-badge status-<?php echo $request['status']; ?>">
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
                                <div class="text-muted small">
                                    <i class="fas fa-clock me-1"></i>
                                    Submitted: <?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?>
                                </div>
                            </div>
                            <?php if ($request['status'] === 'pending'): ?>
                                <div class="card-footer">
                                    <form action="maintenance_actions.php" method="POST" class="d-inline">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <input type="hidden" name="action" value="cancel">
                                        <button type="submit" class="btn btn-danger btn-sm" 
                                                onclick="return confirm('Are you sure you want to cancel this request?')">
                                            <i class="fas fa-times me-1"></i>Cancel Request
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- New Request Modal -->
        <div class="modal fade" id="newRequestModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">New Maintenance Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="maintenance_actions.php" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="create">
                            <div class="mb-3">
                                <label class="form-label">Property</label>
                                <select name="property_id" class="form-select" required>
                                    <?php foreach ($properties as $property): ?>
                                        <option value="<?php echo $property['property_id']; ?>">
                                            <?php echo htmlspecialchars($property['property_title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <!-- Debug info -->
                                <small class="text-muted">Debug: Property IDs available: 
                                    <?php foreach ($properties as $p) echo $p['property_id'] . ', '; ?>
                                </small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-control" required 
                                       minlength="5" maxlength="100" placeholder="e.g., Leaking Faucet">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4" required 
                                          minlength="20" maxlength="1000" 
                                          placeholder="Please describe the issue in detail..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Submit Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
