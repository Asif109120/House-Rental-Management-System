<?php
session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Check if username is an email
    if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
        // If it's an email, check if it's a Gmail address
        if (!preg_match('/@gmail\.com$/i', $username)) {
            $_SESSION['error'] = "Please use a valid Gmail address";
            header("Location: index.php");
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

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];
            
            // Redirect based on user type
            switch($user['user_type']) {
                case 'admin':
                    header("Location: admin/dashboard.php");
                    break;
                case 'landlord':
                    header("Location: landlord/dashboard.php");
                    break;
                case 'tenant':
                    header("Location: tenant/dashboard.php");
                    break;
                default:
                    header("Location: index.php");
            }
            exit();
        } else {
            $_SESSION['error'] = "Invalid password";
        }
    } else {
        $_SESSION['error'] = "Account not found or inactive";
    }
    
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRMS - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar {
            background-color: rgba(255, 255, 255, 0.95) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-weight: bold;
            color: #667eea !important;
        }
        .nav-link {
            color: #2c3e50 !important;
            font-weight: 500;
        }
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            padding: 2rem 0;
            margin-top: 60px;
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
        .login-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="welcome-text">
                        <h1>Welcome Back!</h1>
                        <p>Please login to access your account</p>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <i class="fas fa-user-circle login-icon"></i>
                                <h2 class="text-center">Login</h2>
                            </div>
                            
                            <?php if (isset($_SESSION['error'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php 
                                    echo $_SESSION['error'];
                                    unset($_SESSION['error']);
                                    ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($_SESSION['success'])): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?php 
                                    echo $_SESSION['success'];
                                    unset($_SESSION['success']);
                                    ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username or Gmail</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="username" name="username" required>
                                    </div>
                                    <small class="text-muted">You can use your Gmail address to login</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <button type="button" class="btn btn-outline-secondary password-toggle">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="mb-3 d-flex justify-content-between align-items-center">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                        <label class="form-check-label" for="remember">Remember me</label>
                                    </div>
                                    
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Login</button>
                                    <div class="text-center">
                                        Don't have an account? <a href="register.php" class="text-decoration-none">Register here</a>
                                    </div>
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
