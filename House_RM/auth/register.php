<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();

    $username = trim($_POST['username']);
    $email = trim(strtolower($_POST['email']));
    $password = $_POST['password'];
    $full_name = trim($_POST['full_name']);
    $user_type = $_POST['user_type'];
    $phone = trim($_POST['phone']);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format";
        header("Location: ../register.php");
        exit();
    }

    // Check if it's a Gmail address
    if (!preg_match('/@gmail\.com$/i', $email)) {
        $_SESSION['error'] = "Please use a valid Gmail address";
        header("Location: ../register.php");
        exit();
    }

    // Validate password strength
    if (strlen($password) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters long";
        header("Location: ../register.php");
        exit();
    }

    // Validate phone number
    if (!preg_match('/^\d{10}$/', $phone)) {
        $_SESSION['error'] = "Please enter a valid 10-digit phone number";
        header("Location: ../register.php");
        exit();
    }

    // Check if username already exists
    $query = "SELECT id FROM users WHERE username = :username";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":username", $username);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Username already exists";
        header("Location: ../register.php");
        exit();
    }

    // Check if email already exists
    $query = "SELECT id FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Email already exists";
        header("Location: ../register.php");
        exit();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $query = "INSERT INTO users (username, password, email, full_name, user_type, phone) 
              VALUES (:username, :password, :email, :full_name, :user_type, :phone)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":username", $username);
    $stmt->bindParam(":password", $hashed_password);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":full_name", $full_name);
    $stmt->bindParam(":user_type", $user_type);
    $stmt->bindParam(":phone", $phone);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Registration successful! Please login.";
        header("Location: ../index.php");
    } else {
        $_SESSION['error'] = "Registration failed. Please try again.";
        header("Location: ../register.php");
    }
    exit();
}
