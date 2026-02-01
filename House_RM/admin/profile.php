<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get user details
    $query = "SELECT * FROM users WHERE id = :user_id AND user_type = 'admin'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = "User not found";
        header("Location: dashboard.php");
        exit();
    }

    // Handle profile update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $current_password = $_POST['current_password'];
        
        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            $updates = [];
            $params = [':user_id' => $_SESSION['user_id']];

            // Update basic info
            if (!empty($_POST['full_name'])) {
                $updates[] = "full_name = :full_name";
                $params[':full_name'] = $_POST['full_name'];
            }
            if (!empty($_POST['email'])) {
                $updates[] = "email = :email";
                $params[':email'] = $_POST['email'];
            }
            if (!empty($_POST['phone'])) {
                $updates[] = "phone = :phone";
                $params[':phone'] = $_POST['phone'];
            }

            // Update password if provided
            if (!empty($_POST['new_password'])) {
                $updates[] = "password = :password";
                $params[':password'] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            }

            if (!empty($updates)) {
                $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = :user_id";
                $stmt = $db->prepare($sql);
                $stmt->execute($params);

                $_SESSION['success'] = "Profile updated successfully";
                header("Location: profile.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Current password is incorrect";
        }
    }
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $_SESSION['error'] = "Error updating profile";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #4e73df;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="bg-light">
    <?php include '../includes/admin_header.php'; ?>

    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="profile-header text-center">
                    <div class="profile-avatar mx-auto">
                        <i class="fas fa-user"></i>
                    </div>
                    <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                    <p class="mb-0">Administrator</p>
                </div>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="" class="needs-validation" novalidate>
                            <div class="mb-4">
                                <h5>Basic Information</h5>
                                <hr>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Username</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                        <small class="text-muted">Username cannot be changed</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone</label>
                                        <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <h5>Change Password</h5>
                                <hr>
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label">Current Password</label>
                                        <input type="password" class="form-control" name="current_password" required>
                                        <small class="text-muted">Required to save any changes</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">New Password</label>
                                        <input type="password" class="form-control" name="new_password" minlength="6">
                                        <small class="text-muted">Leave blank to keep current password</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" name="confirm_password">
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Form validation
    (function () {
        'use strict'
        const forms = document.querySelectorAll('.needs-validation')
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }

                // Check if passwords match
                const newPassword = form.querySelector('input[name="new_password"]')
                const confirmPassword = form.querySelector('input[name="confirm_password"]')
                if (newPassword.value && newPassword.value !== confirmPassword.value) {
                    event.preventDefault()
                    alert('New passwords do not match!')
                }

                form.classList.add('was-validated')
            }, false)
        })
    })()
    </script>
</body>
</html>
