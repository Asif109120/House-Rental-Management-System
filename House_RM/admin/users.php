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

    // Get all users
    $query = "SELECT * FROM users ORDER BY created_at DESC";
    $stmt = $db->query($query);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching users: " . $e->getMessage();
    $users = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .table td, .table th {
            vertical-align: middle;
        }
    </style>
</head>
<body class="bg-light">
    <?php include '../includes/admin_header.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manage Users</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-plus"></i> Add New User
            </button>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>User Type</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $user['user_type'] === 'admin' ? 'danger' : 
                                                ($user['user_type'] === 'landlord' ? 'primary' : 'success');
                                        ?>">
                                            <?php echo ucfirst($user['user_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                            <button class="btn btn-sm btn-<?php echo $user['status'] === 'active' ? 'warning' : 'success'; ?>"
                                                    onclick="toggleUserStatus(<?php echo $user['id']; ?>, '<?php echo $user['status']; ?>')">
                                                <i class="fas fa-<?php echo $user['status'] === 'active' ? 'ban' : 'check'; ?>"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="user_actions.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">User Type</label>
                            <select class="form-select" name="user_type" required>
                                <option value="tenant">Tenant</option>
                                <option value="landlord">Landlord</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="user_actions.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" id="edit_username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" placeholder="Leave blank to keep current password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" id="edit_full_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">User Type</label>
                            <select class="form-select" name="user_type" id="edit_user_type" required>
                                <option value="tenant">Tenant</option>
                                <option value="landlord">Landlord</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function editUser(user) {
        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('edit_username').value = user.username;
        document.getElementById('edit_full_name').value = user.full_name;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_user_type').value = user.user_type;
        
        new bootstrap.Modal(document.getElementById('editUserModal')).show();
    }

    function toggleUserStatus(userId, currentStatus) {
        if (confirm('Are you sure you want to ' + (currentStatus === 'active' ? 'deactivate' : 'activate') + ' this user?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'user_actions.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'toggle_status';
            
            const userInput = document.createElement('input');
            userInput.type = 'hidden';
            userInput.name = 'user_id';
            userInput.value = userId;
            
            form.appendChild(actionInput);
            form.appendChild(userInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    function deleteUser(userId) {
        if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'user_actions.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete';
            
            const userInput = document.createElement('input');
            userInput.type = 'hidden';
            userInput.name = 'user_id';
            userInput.value = userId;
            
            form.appendChild(actionInput);
            form.appendChild(userInput);
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>

    <?php require_once '../includes/footer.php'; ?>
</body>
</html>
