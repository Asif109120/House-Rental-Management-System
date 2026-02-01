<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Debug information
    error_log("Login attempt - Username: " . $username);

    // Check if username is an email
    if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
        // If it's an email, check if it's a Gmail address
        if (!preg_match('/@gmail\.com$/i', $username)) {
            $_SESSION['error'] = "Please use a valid Gmail address";
            header("Location: ../index.php");
            exit();
        }
        // Use email to find user
        $query = "SELECT * FROM users WHERE email = :username AND status = 'active'";
    } else {
        // Use username to find user
        $query = "SELECT * FROM users WHERE username = :username AND status = 'active'";
    }

    $stmt = $db->prepare($query);
    $stmt->bindParam(":username", $username);
    $stmt->execute();

    error_log("Query executed - Found rows: " . $stmt->rowCount());

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("User type: " . $user['user_type']);
        
        if (password_verify($password, $user['password'])) {
            error_log("Password verified successfully");
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];
            
            // Debug session
            error_log("Session data set - Type: " . $_SESSION['user_type']);
            
            // Redirect based on user type
            switch($user['user_type']) {
                case 'admin':
                    error_log("Redirecting to admin dashboard");
                    header("Location: ../admin/dashboard.php");
                    break;
                case 'landlord':
                    header("Location: ../landlord/dashboard.php");
                    break;
                case 'tenant':
                    header("Location: ../tenant/dashboard.php");
                    break;
                default:
                    header("Location: ../index.php");
            }
            exit();
        } else {
            error_log("Password verification failed");
            $_SESSION['error'] = "Invalid password";
        }
    } else {
        error_log("User not found or inactive");
        $_SESSION['error'] = "Account not found or inactive";
    }
    
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - House Rental Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .btn-primary {
            background: #667eea;
            border-color: #667eea;
        }
        .btn-primary:hover {
            background: #764ba2;
            border-color: #764ba2;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card">
                    <div class="card-body p-5">
                        <h3 class="text-center mb-4">Login</h3>
                        
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php 
                                echo $_SESSION['error'];
                                unset($_SESSION['error']);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form action="login.php" method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username or Email</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Login</button>
                            </div>
                            <div class="text-center mt-3">
                                <p class="mb-0">Don't have an account? <a href="register.php">Register</a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
