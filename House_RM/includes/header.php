<?php
// Only start session if it hasn't been started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Don't redirect if we're on the index or login page
$current_page = basename($_SERVER['PHP_SELF']);
if (!isset($_SESSION['user_id']) && !in_array($current_page, ['index.php', 'login.php'])) {
    header("Location: /House_RM/index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>House Rental Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #2c3e50;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 1rem;
        }
        .sidebar .nav-link:hover {
            color: white;
            background: rgba(255,255,255,.1);
        }
        .sidebar .nav-link.active {
            background: rgba(255,255,255,.2);
        }
        .content {
            padding: 2rem;
        }
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
        }
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
        .user-dropdown .dropdown-item {
            padding: 0.5rem 1rem;
        }
        .user-dropdown .dropdown-item i {
            width: 20px;
        }
    </style>
</head>
<body>
    <!-- User Dropdown -->
    <div class="user-dropdown">
        <div class="dropdown">
            <button class="btn btn-light dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown">
                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li>
                        <a class="dropdown-item" href="/House_RM/<?php echo $_SESSION['user_type']; ?>/profile.php">
                            <i class="fas fa-user-circle me-1"></i>Profile
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="/House_RM/auth/logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="d-flex flex-column">
                    <a class="navbar-brand p-3 text-white" href="#">HRMS</a>
                    <hr class="text-white">
                    <ul class="nav flex-column">
                        <?php if ($_SESSION['user_type'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/House_RM/admin/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/House_RM/admin/users.php">
                                    <i class="fas fa-users me-2"></i> Manage Users
                                </a>
                            </li>
                        <?php elseif ($_SESSION['user_type'] === 'landlord'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/House_RM/landlord/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/House_RM/landlord/properties.php">
                                    <i class="fas fa-building me-2"></i> My Properties
                                </a>
                            </li>
                            <li class="nav-item">
                            <a class="nav-link" href="/House_RM/landlord/maintenance.php">
        <i class="fas fa-wrench me-2"></i> Maintenance
    </a>
</li>
                            <li class="nav-item">
                                <a class="nav-link" href="/House_RM/landlord/bookings.php">
                                    <i class="fas fa-calendar-check me-2"></i> Bookings
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/House_RM/tenant/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/House_RM/tenant/properties.php">
                                    <i class="fas fa-search me-2"></i> Find Properties
                                </a>
                            </li>
                            <li class="nav-item">
                            <a class="nav-link" href="/House_RM/tenant/maintenance.php">
        <i class="fas fa-wrench me-2"></i> Maintenance
    </a>
</li>
                            <li class="nav-item">
                                <a class="nav-link" href="/House_RM/tenant/my-bookings.php">
                                    <i class="fas fa-calendar-check me-2"></i> My Bookings
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/House_RM/messages.php">
                                <i class="fas fa-envelope me-2"></i> Messages
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/House_RM/auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 content">
                <!-- Content will be injected here -->
