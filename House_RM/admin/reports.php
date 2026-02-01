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

    // Get overall statistics
    $stats = [];

    // Users statistics
    $query = "SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN user_type = 'landlord' THEN 1 ELSE 0 END) as total_landlords,
                SUM(CASE WHEN user_type = 'tenant' THEN 1 ELSE 0 END) as total_tenants,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users
              FROM users WHERE user_type != 'admin'";
    $stmt = $db->query($query);
    $stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Properties statistics
    $query = "SELECT 
                COUNT(*) as total_properties,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_properties,
                SUM(CASE WHEN status = 'rented' THEN 1 ELSE 0 END) as rented_properties,
                SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_properties,
                AVG(price) as avg_price
              FROM properties";
    $stmt = $db->query($query);
    $stats['properties'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Bookings statistics
    $query = "SELECT 
                COUNT(*) as total_bookings,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_bookings,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_bookings
              FROM bookings";
    $stmt = $db->query($query);
    $stats['bookings'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get monthly booking trends
    $query = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as total_bookings,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_bookings
              FROM bookings
              GROUP BY DATE_FORMAT(created_at, '%Y-%m')
              ORDER BY month DESC
              LIMIT 6";
    $stmt = $db->query($query);
    $monthly_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get top properties by bookings
    $query = "SELECT 
                p.title, p.address, p.city, p.price,
                COUNT(b.id) as total_bookings,
                SUM(CASE WHEN b.status = 'approved' THEN 1 ELSE 0 END) as successful_bookings
              FROM properties p
              LEFT JOIN bookings b ON p.id = b.property_id
              GROUP BY p.id
              ORDER BY successful_bookings DESC
              LIMIT 5";
    $stmt = $db->query($query);
    $top_properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get most active landlords
    $query = "SELECT 
                u.username, u.email,
                COUNT(p.id) as total_properties,
                COUNT(b.id) as total_bookings
              FROM users u
              LEFT JOIN properties p ON u.id = p.landlord_id
              LEFT JOIN bookings b ON p.id = b.property_id
              WHERE u.user_type = 'landlord'
              GROUP BY u.id
              ORDER BY total_bookings DESC
              LIMIT 5";
    $stmt = $db->query($query);
    $top_landlords = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $_SESSION['error'] = "Error generating reports";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .trend-card {
            height: 400px;
        }
    </style>
</head>
<body class="bg-light">
    <?php include '../includes/admin_header.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-chart-line me-2"></i>System Reports</h2>
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Print Report
            </button>
        </div>

        <!-- Overall Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="text-primary stats-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3><?php echo $stats['users']['total_users']; ?></h3>
                    <p class="text-muted mb-0">Total Users</p>
                    <small class="text-success">
                        <?php echo $stats['users']['active_users']; ?> Active Users
                    </small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="text-success stats-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <h3><?php echo $stats['properties']['total_properties']; ?></h3>
                    <p class="text-muted mb-0">Total Properties</p>
                    <small class="text-success">
                        <?php echo $stats['properties']['available_properties']; ?> Available
                    </small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="text-info stats-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3><?php echo $stats['bookings']['total_bookings']; ?></h3>
                    <p class="text-muted mb-0">Total Bookings</p>
                    <small class="text-success">
                        <?php echo $stats['bookings']['approved_bookings']; ?> Approved
                    </small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="text-warning stats-icon">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <h3>₹<?php echo number_format($stats['properties']['avg_price'], 2); ?></h3>
                    <p class="text-muted mb-0">Average Rent</p>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card trend-card">
                    <div class="card-body">
                        <h5 class="card-title">Monthly Booking Trends</h5>
                        <canvas id="bookingTrends"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card trend-card">
                    <div class="card-body">
                        <h5 class="card-title">User Distribution</h5>
                        <canvas id="userDistribution"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Properties and Landlords -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Top Properties</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Location</th>
                                        <th>Price</th>
                                        <th>Bookings</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_properties as $property): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($property['title']); ?></td>
                                            <td><?php echo htmlspecialchars($property['city']); ?></td>
                                            <td>$<?php echo number_format($property['price'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <?php echo $property['successful_bookings']; ?> Successful
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Most Active Landlords</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Landlord</th>
                                        <th>Properties</th>
                                        <th>Bookings</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_landlords as $landlord): ?>
                                        <tr>
                                            <td>
                                                <?php echo htmlspecialchars($landlord['username']); ?>
                                                <small class="d-block text-muted">
                                                    <?php echo htmlspecialchars($landlord['email']); ?>
                                                </small>
                                            </td>
                                            <td><?php echo $landlord['total_properties']; ?></td>
                                            <td><?php echo $landlord['total_bookings']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Monthly Booking Trends Chart
        const monthlyData = <?php echo json_encode(array_reverse($monthly_trends)); ?>;
        new Chart(document.getElementById('bookingTrends'), {
            type: 'line',
            data: {
                labels: monthlyData.map(item => item.month),
                datasets: [{
                    label: 'Total Bookings',
                    data: monthlyData.map(item => item.total_bookings),
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }, {
                    label: 'Approved Bookings',
                    data: monthlyData.map(item => item.approved_bookings),
                    borderColor: 'rgb(54, 162, 235)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // User Distribution Chart
        new Chart(document.getElementById('userDistribution'), {
            type: 'doughnut',
            data: {
                labels: ['Landlords', 'Tenants', 'Inactive Users'],
                datasets: [{
                    data: [
                        <?php echo $stats['users']['total_landlords']; ?>,
                        <?php echo $stats['users']['total_tenants']; ?>,
                        <?php echo $stats['users']['total_users'] - $stats['users']['active_users']; ?>
                    ],
                    backgroundColor: [
                        'rgb(255, 99, 132)',
                        'rgb(54, 162, 235)',
                        'rgb(255, 205, 86)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
</body>
</html>
