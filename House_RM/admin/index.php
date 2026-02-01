<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Redirect to admin dashboard
header("Location: dashboard.php");
exit();
?>
