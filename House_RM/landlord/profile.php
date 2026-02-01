<?php
require_once '../includes/header.php';
require_once '../config/db.php';

// Check if user is logged in and is a landlord
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'landlord') {
    header("Location: ../auth/login.php");
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get user details
    $query = "SELECT * FROM users WHERE id = :user_id AND user_type = 'landlord'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = "User not found";
        header("Location: dashboard.php");
        exit();
    }

    // Get landlord statistics
    $stats = [];

    // Get total properties
    $query = "SELECT COUNT(*) as total_properties FROM properties WHERE landlord_id = :landlord_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':landlord_id', $_SESSION['user_id']);
    $stmt->execute();
    $stats['properties'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_properties'];

    // Get total bookings
    $query = "SELECT COUNT(*) as total_bookings FROM bookings b 
              JOIN properties p ON b.property_id = p.id 
              WHERE p.landlord_id = :landlord_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':landlord_id', $_SESSION['user_id']);
    $stmt->execute();
    $stats['bookings'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_bookings'];

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

<style>
.profile-section {
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    overflow: hidden;
    margin-bottom: 2rem;
}

.profile-header {
    background: linear-gradient(135deg, #0061f2 0%, #6c47ef 100%);
    color: white;
    padding: 3rem 2rem;
    text-align: center;
    position: relative;
}

.profile-avatar {
    width: 150px;
    height: 150px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 4rem;
    color: #0061f2;
    margin: 0 auto 1.5rem;
    border: 5px solid rgba(255,255,255,0.3);
    transition: transform 0.3s;
}

.profile-avatar:hover {
    transform: scale(1.05);
}

.stats-card {
    padding: 1.5rem;
    text-align: center;
    border-radius: 10px;
    background: white;
    box-shadow: 0 0 15px rgba(0,0,0,0.05);
    transition: transform 0.3s;
}

.stats-card:hover {
    transform: translateY(-5px);
}

.stats-icon {
    width: 60px;
    height: 60px;
    background: #e8f0fe;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 1.5rem;
}

.stats-card.properties .stats-icon {
    color: #0061f2;
    background: #e8f0fe;
}

.stats-card.bookings .stats-icon {
    color: #00ac69;
    background: #e7f6ec;
}

.form-section {
    background: white;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    padding: 2rem;
}

.form-section h5 {
    color: #363d47;
    font-weight: 600;
    margin-bottom: 1.5rem;
}

.form-control {
    border-radius: 8px;
    padding: 0.75rem 1rem;
    border: 2px solid #e3e6ec;
    transition: border-color 0.3s;
}

.form-control:focus {
    border-color: #0061f2;
    box-shadow: none;
}

.btn-save {
    padding: 0.75rem 2rem;
    font-weight: 600;
    border-radius: 8px;
    transition: all 0.3s;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,97,242,0.3);
}
</style>

<!-- Main Content -->
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
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

            <!-- Profile Header -->
            <div class="profile-section">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <h2 class="mb-2"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                    <p class="mb-0 opacity-75">Property Owner</p>
                </div>

                <!-- Statistics -->
                <div class="container">
                    <div class="row" style="margin-top: -30px;">
                        <div class="col-md-6 mb-4">
                            <div class="stats-card properties">
                                <div class="stats-icon">
                                    <i class="fas fa-building"></i>
                                </div>
                                <h3 class="mb-1"><?php echo $stats['properties']; ?></h3>
                                <p class="text-muted mb-0">Total Properties</p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="stats-card bookings">
                                <div class="stats-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <h3 class="mb-1"><?php echo $stats['bookings']; ?></h3>
                                <p class="text-muted mb-0">Total Bookings</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Form -->
            <div class="form-section">
                <form method="POST" action="" class="needs-validation" novalidate>
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h5><i class="fas fa-user-circle me-2"></i>Basic Information</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
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
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <h5><i class="fas fa-lock me-2"></i>Change Password</h5>
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
                    </div>

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-primary btn-save">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

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
            if (newPassword.value || confirmPassword.value) {
                if (newPassword.value !== confirmPassword.value) {
                    event.preventDefault()
                    confirmPassword.setCustomValidity('Passwords do not match')
                } else {
                    confirmPassword.setCustomValidity('')
                }
            }

            form.classList.add('was-validated')
        }, false)
    })
})()
</script>

<?php require_once '../includes/footer.php'; ?>
