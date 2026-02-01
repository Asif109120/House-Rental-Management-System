<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg fixed-top bg-white">
    <div class="container">
        <a class="navbar-brand" href="<?php echo $current_page === 'index.php' ? '#' : 'index.php'; ?>">
            <i class="fas fa-home me-2"></i>HRMS
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>" href="<?php echo $current_page === 'index.php' ? '#' : 'index.php'; ?>">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'about.php' ? 'active' : ''; ?>" href="<?php echo $current_page === 'about.php' ? '#' : 'about.php'; ?>">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'contact.php' ? 'active' : ''; ?>" href="<?php echo $current_page === 'contact.php' ? '#' : 'contact.php'; ?>">Contact</a>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="<?php echo $_SESSION['user_type']; ?>/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo $_SESSION['user_type']; ?>/profile.php">
                                    <i class="fas fa-user-circle me-1"></i>Profile
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="auth/logout.php">
                                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'register.php' ? 'active' : ''; ?>" href="<?php echo $current_page === 'register.php' ? '#' : 'register.php'; ?>">Register</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'login.php' ? 'active' : ''; ?>" href="<?php echo $current_page === 'login.php' ? '#' : 'login.php'; ?>">Login</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Add some spacing after navbar -->
<div style="margin-top: 76px;"></div>

<style>
.navbar {
    background-color: rgba(255, 255, 255, 0.95) !important;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.nav-link {
    position: relative;
    padding: 0.5rem 1rem;
    color: #333 !important;
}
.nav-link::after {
    content: '';
    position: absolute;
    width: 0;
    height: 2px;
    bottom: 0;
    left: 50%;
    background-color: #3498db;
    transition: all 0.3s ease;
    transform: translateX(-50%);
}
.nav-link:hover::after,
.nav-link.active::after {
    width: 100%;
}
.navbar-brand {
    font-weight: bold;
    color: #3498db !important;
}
.navbar-toggler {
    border: none;
    padding: 0.5rem;
}
.navbar-toggler:focus {
    box-shadow: none;
    outline: none;
}
.dropdown-menu {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.dropdown-item {
    padding: 0.5rem 1rem;
}
.dropdown-item:hover {
    background-color: #f8f9fa;
}
.dropdown-item i {
    width: 20px;
}
@media (max-width: 991.98px) {
    .navbar-collapse {
        background: white;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-top: 0.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
}
</style>
