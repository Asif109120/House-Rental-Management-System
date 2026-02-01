<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'update_profile') {
    // Validate input
    if (empty($_POST['full_name']) || empty($_POST['email'])) {
        $_SESSION['error'] = "Full name and email are required";
        header("Location: profile.php");
        exit();
    }

    // Check if email is already used by another user
    $query = "SELECT id FROM users WHERE email = :email AND id != :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $_POST['email']);
    $stmt->bindParam(":user_id", $_SESSION['user_id']);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Email is already in use by another user";
        header("Location: profile.php");
        exit();
    }

    // Start building update query
    $query = "UPDATE users SET full_name = :full_name, email = :email";
    $params = [
        ":full_name" => $_POST['full_name'],
        ":email" => $_POST['email'],
        ":user_id" => $_SESSION['user_id']
    ];

    // Handle password change if requested
    if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
        // Verify current password
        $query_check = "SELECT password FROM users WHERE id = :user_id";
        $stmt = $db->prepare($query_check);
        $stmt->bindParam(":user_id", $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($_POST['current_password'], $user['password'])) {
            $_SESSION['error'] = "Current password is incorrect";
            header("Location: profile.php");
            exit();
        }

        // Validate new password
        if ($_POST['new_password'] !== $_POST['confirm_password']) {
            $_SESSION['error'] = "New passwords do not match";
            header("Location: profile.php");
            exit();
        }

        // Add password to update query
        $query .= ", password = :password";
        $params[":password"] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    }

    // Complete the update query
    $query .= " WHERE id = :user_id";

    // Execute update
    $stmt = $db->prepare($query);
    if ($stmt->execute($params)) {
        $_SESSION['success'] = "Profile updated successfully";
    } else {
        $_SESSION['error'] = "Error updating profile";
    }

    header("Location: profile.php");
    exit();
}
?>
