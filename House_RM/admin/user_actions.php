<?php
session_start();
require_once '../config/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'];

        switch ($action) {
            case 'add':
                // Validate input
                if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['email']) || 
                    empty($_POST['full_name']) || empty($_POST['user_type'])) {
                    $_SESSION['error'] = "All fields are required";
                    header("Location: users.php");
                    exit();
                }

                // Check if username or email already exists
                $query = "SELECT id FROM users WHERE username = :username OR email = :email";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':username', $_POST['username'], PDO::PARAM_STR);
                $stmt->bindParam(':email', $_POST['email'], PDO::PARAM_STR);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $_SESSION['error'] = "Username or email already exists";
                    header("Location: users.php");
                    exit();
                }

                // Insert new user
                $query = "INSERT INTO users (username, password, email, full_name, user_type) 
                         VALUES (:username, :password, :email, :full_name, :user_type)";
                $stmt = $db->prepare($query);
                
                $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                $stmt->bindParam(':username', $_POST['username'], PDO::PARAM_STR);
                $stmt->bindParam(':password', $password_hash, PDO::PARAM_STR);
                $stmt->bindParam(':email', $_POST['email'], PDO::PARAM_STR);
                $stmt->bindParam(':full_name', $_POST['full_name'], PDO::PARAM_STR);
                $stmt->bindParam(':user_type', $_POST['user_type'], PDO::PARAM_STR);

                if ($stmt->execute()) {
                    $_SESSION['success'] = "User added successfully";
                } else {
                    $_SESSION['error'] = "Error adding user";
                }
                break;

            case 'edit':
                // Validate input
                if (empty($_POST['username']) || empty($_POST['email']) || 
                    empty($_POST['full_name']) || empty($_POST['user_type'])) {
                    $_SESSION['error'] = "Required fields cannot be empty";
                    header("Location: users.php");
                    exit();
                }

                // Check if username or email already exists for other users
                $query = "SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':username', $_POST['username'], PDO::PARAM_STR);
                $stmt->bindParam(':email', $_POST['email'], PDO::PARAM_STR);
                $stmt->bindParam(':user_id', $_POST['user_id'], PDO::PARAM_INT);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $_SESSION['error'] = "Username or email already exists";
                    header("Location: users.php");
                    exit();
                }

                // Update user
                $query = "UPDATE users SET 
                         username = :username,
                         email = :email,
                         full_name = :full_name,
                         user_type = :user_type";
                
                // Add password to update if provided
                if (!empty($_POST['password'])) {
                    $query .= ", password = :password";
                }
                
                $query .= " WHERE id = :user_id";
                
                $stmt = $db->prepare($query);
                
                $stmt->bindParam(':username', $_POST['username'], PDO::PARAM_STR);
                $stmt->bindParam(':email', $_POST['email'], PDO::PARAM_STR);
                $stmt->bindParam(':full_name', $_POST['full_name'], PDO::PARAM_STR);
                $stmt->bindParam(':user_type', $_POST['user_type'], PDO::PARAM_STR);
                $stmt->bindParam(':user_id', $_POST['user_id'], PDO::PARAM_INT);
                
                if (!empty($_POST['password'])) {
                    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt->bindParam(':password', $password_hash, PDO::PARAM_STR);
                }

                if ($stmt->execute()) {
                    $_SESSION['success'] = "User updated successfully";
                } else {
                    $_SESSION['error'] = "Error updating user";
                }
                break;

            case 'toggle_status':
                if (!isset($_POST['user_id'])) {
                    $_SESSION['error'] = "User ID not provided";
                    break;
                }

                $user_id = $_POST['user_id'];

                // Get current status
                $query = "SELECT status FROM users WHERE id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->execute();
                $current_status = $stmt->fetchColumn();

                // Toggle status
                $new_status = ($current_status === 'active') ? 'inactive' : 'active';
                $query = "UPDATE users SET status = :status WHERE id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':status', $new_status, PDO::PARAM_STR);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "User status updated successfully";
                } else {
                    $_SESSION['error'] = "Error updating user status";
                }
                break;

            case 'delete':
                if (!isset($_POST['user_id'])) {
                    $_SESSION['error'] = "User ID not provided";
                    break;
                }

                $user_id = $_POST['user_id'];
                
                // Check if user has any related records before deletion
                $safe_to_delete = true;
                $error_message = "";

                // Check properties if landlord
                $query = "SELECT COUNT(*) FROM properties WHERE landlord_id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->execute();
                if ($stmt->fetchColumn() > 0) {
                    $safe_to_delete = false;
                    $error_message = "Cannot delete user: Has associated properties";
                }

                // Check bookings if any
                $query = "SELECT COUNT(*) FROM bookings WHERE tenant_id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->execute();
                if ($stmt->fetchColumn() > 0) {
                    $safe_to_delete = false;
                    $error_message = "Cannot delete user: Has associated bookings";
                }

                // Check messages
                $query = "SELECT COUNT(*) FROM messages WHERE sender_id = :user_id OR receiver_id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->execute();
                if ($stmt->fetchColumn() > 0) {
                    $safe_to_delete = false;
                    $error_message = "Cannot delete user: Has associated messages";
                }

                if ($safe_to_delete) {
                    // Delete the user
                    $query = "DELETE FROM users WHERE id = :user_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "User deleted successfully";
                    } else {
                        $_SESSION['error'] = "Error deleting user";
                    }
                } else {
                    $_SESSION['error'] = $error_message;
                }
                break;
        }

        header("Location: users.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: users.php");
    exit();
}
?>
