<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'landlord') {
    header("Location: ../auth/login.php");
    exit();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand" href="../index.php">
            <i class="fas fa-building me-2"></i>HRMS
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'properties.php' ? 'active' : ''; ?>" href="properties.php">
                        <i class="fas fa-building me-1"></i>Properties
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'bookings.php' ? 'active' : ''; ?>" href="bookings.php">
                        <i class="fas fa-calendar-alt me-1"></i>Bookings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'maintenance.php' ? 'active' : ''; ?>" href="maintenance.php">
                        <i class="fas fa-tools me-1"></i>Maintenance
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                        <i class="fas fa-user me-1"></i>Profile
                    </a>
                </li>
            </ul>
            <div class="d-flex">
                <div class="dropdown">
                    <button class="btn btn-link text-dark text-decoration-none dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user me-2"></i>Profile
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="../auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<style>
.navbar {
    padding: 0.5rem 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.navbar-brand {
    font-weight: bold;
}
.nav-link {
    padding: 0.8rem 1rem !important;
    position: relative;
}
.nav-link::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    width: 0;
    height: 2px;
    background-color: #fff;
    transition: all 0.3s ease;
    transform: translateX(-50%);
}
.nav-link:hover::after,
.nav-link.active::after {
    width: 100%;
}
.dropdown-item {
    padding: 0.5rem 1rem;
}
.dropdown-item.active,
.dropdown-item:active {
    background-color: #0d6efd;
}
</style>
