<?php
session_start();
require_once 'config/db.php';

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
        header("Location: register.php");
        exit();
    }

    // Check if it's a Gmail address
    if (!preg_match('/@gmail\.com$/i', $email)) {
        $_SESSION['error'] = "Please use a valid Gmail address";
        header("Location: register.php");
        exit();
    }

    // Validate password strength
    if (strlen($password) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters long";
        header("Location: register.php");
        exit();
    }

    // Validate phone number
    if (!preg_match('/^\d{10}$/', $phone)) {
        $_SESSION['error'] = "Please enter a valid 10-digit phone number";
        header("Location: register.php");
        exit();
    }

    // Check if username already exists
    $query = "SELECT id FROM users WHERE username = :username";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":username", $username);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Username already exists";
        header("Location: register.php");
        exit();
    }

    // Check if email already exists
    $query = "SELECT id FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Email already exists";
        header("Location: register.php");
        exit();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $query = "INSERT INTO users (username, password, email, full_name, user_type, phone, status) 
              VALUES (:username, :password, :email, :full_name, :user_type, :phone, 'active')";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":username", $username);
    $stmt->bindParam(":password", $hashed_password);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":full_name", $full_name);
    $stmt->bindParam(":user_type", $user_type);
    $stmt->bindParam(":phone", $phone);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Registration successful! Please login with your credentials.";
        header("Location: login.php");
    } else {
        $_SESSION['error'] = "Registration failed. Please try again.";
        header("Location: register.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRMS - Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .main-content {
            padding: 2rem 0;
            display: flex;
            align-items: center;
            min-height: calc(100vh - 76px);
        }
        .card {
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .card-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
        }
        .btn-primary {
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            background: #667eea;
            border: none;
        }
        .btn-primary:hover {
            background: #764ba2;
        }
        .welcome-text {
            text-align: center;
            color: #fff;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="welcome-text">
                        <h1>Create Your Account</h1>
                        <p>Join our community today</p>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h2 class="text-center mb-4">Register</h2>

                            <?php if (isset($_SESSION['error'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php 
                                    echo $_SESSION['error'];
                                    unset($_SESSION['error']);
                                    ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" id="registerForm">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" class="form-control" id="username" name="username" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Gmail Address</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            <input type="email" class="form-control" id="email" name="email" required>
                                        </div>
                                        <small class="text-muted">Only Gmail addresses are accepted</small>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" id="password" name="password" required>
                                            <button type="button" class="btn btn-outline-secondary password-toggle">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">Minimum 8 characters</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="full_name" class="form-label">Full Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="user_type" class="form-label">Account Type</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-users"></i></span>
                                            <select class="form-select" id="user_type" name="user_type" required>
                                                <option value="">Select account type</option>
                                                <option value="tenant">Tenant</option>
                                                <option value="landlord">Landlord</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                            <input type="tel" class="form-control" id="phone" name="phone" required>
                                        </div>
                                        <small class="text-muted">10 digits only</small>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Register</button>
                                    <a href="login.php" class="btn btn-outline-primary">Back to Login</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Toggle password visibility
            $('.password-toggle').click(function() {
                const passwordInput = $('#password');
                const icon = $(this).find('i');
                
                if (passwordInput.attr('type') === 'password') {
                    passwordInput.attr('type', 'text');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    passwordInput.attr('type', 'password');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });
        });
    </script>
</body>
</html>
