<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    header("Location: ../auth/login.php");
    exit();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand" href="../index.php">
            <i class="fas fa-home me-2"></i>HRMS
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
                    <a class="nav-link <?php echo $current_page === 'bookings.php' ? 'active' : ''; ?>" href="bookings.php">
                        <i class="fas fa-calendar-alt me-1"></i>My Bookings
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
