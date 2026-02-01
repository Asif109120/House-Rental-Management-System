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

    // Fetch all properties with landlord information
    $query = "SELECT p.*, u.username as landlord_name, u.email as landlord_email, 
              (SELECT COUNT(*) FROM bookings b WHERE b.property_id = p.id AND b.status = 'approved') as active_rentals
              FROM properties p 
              LEFT JOIN users u ON p.landlord_id = u.id 
              ORDER BY p.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get some statistics
    $total_properties = count($properties);
    $rented_properties = array_sum(array_map(function($p) { return $p['active_rentals'] > 0 ? 1 : 0; }, $properties));
    $available_properties = $total_properties - $rented_properties;

} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $error = "Error fetching properties";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Management - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .property-card {
            transition: transform 0.2s;
        }
        .property-card:hover {
            transform: translateY(-5px);
        }
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
    </style>
</head>
<body class="bg-light">
    <?php include '../includes/admin_header.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-building me-2"></i>Property Management</h2>
            <div class="btn-group">
                <button type="button" class="btn btn-primary" onclick="exportToCSV()">
                    <i class="fas fa-download me-2"></i>Export Data
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="text-primary stats-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <h3><?php echo $total_properties; ?></h3>
                    <p class="text-muted mb-0">Total Properties</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="text-success stats-icon">
                        <i class="fas fa-key"></i>
                    </div>
                    <h3><?php echo $rented_properties; ?></h3>
                    <p class="text-muted mb-0">Rented Properties</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="text-info stats-icon">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <h3><?php echo $available_properties; ?></h3>
                    <p class="text-muted mb-0">Available Properties</p>
                </div>
            </div>
        </div>

        <!-- Properties Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Landlord</th>
                                <th>Status</th>
                                <th>Price</th>
                                <th>Added Date</th>
                                <th>Active Rentals</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($properties as $property): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($property['title']); ?></div>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($property['address'] . ', ' . $property['city']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($property['landlord_name']); ?></div>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($property['landlord_email']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $property['status'] === 'available' ? 'success' : 
                                                ($property['status'] === 'rented' ? 'warning' : 'secondary');
                                        ?>">
                                            <?php echo ucfirst($property['status']); ?>
                                        </span>
                                    </td>
                                    <td>₹<?php echo number_format($property['price'], 2); ?>/month</td>
                                    <td><?php echo date('M j, Y', strtotime($property['created_at'])); ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo $property['active_rentals']; ?> Active
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="viewDetails(<?php echo $property['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-warning" onclick="toggleStatus(<?php echo $property['id']; ?>)">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Property Details Modal -->
    <div class="modal fade" id="propertyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Property Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="propertyDetails">
                    <!-- Details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewDetails(propertyId) {
            fetch(`property_details.php?id=${propertyId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('propertyDetails').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('propertyModal')).show();
                });
        }

        function toggleStatus(propertyId) {
            if (confirm('Are you sure you want to change the status of this property?')) {
                fetch('property_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=toggle_status&property_id=${propertyId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Error updating property status');
                    }
                });
            }
        }

        function exportToCSV() {
            window.location.href = 'export_properties.php';
        }
    </script>
</body>
</html>
