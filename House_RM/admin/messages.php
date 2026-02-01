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

    // Handle message status updates
    if (isset($_POST['message_id']) && isset($_POST['action'])) {
        if ($_POST['action'] === 'mark_read') {
            $sql = "UPDATE contact_messages SET status = 'read', updated_at = NOW() WHERE id = :id";
        } elseif ($_POST['action'] === 'delete') {
            $sql = "DELETE FROM contact_messages WHERE id = :id";
        }

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $_POST['message_id'], PDO::PARAM_INT);
        $stmt->execute();

        $_SESSION['success'] = "Message updated successfully.";
        header("Location: messages.php");
        exit();
    }

    // Fetch messages with pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Get total count
    $stmt = $db->query("SELECT COUNT(*) FROM contact_messages");
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // Get messages
    $sql = "SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get counts
    $stmt = $db->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'");
    $unread_count = $stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM contact_messages");
    $total_count = $stmt->fetchColumn();

} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching messages.";
    error_log("Message fetch error: " . $e->getMessage());
    $messages = [];
    $total_pages = 0;
    $unread_count = 0;
    $total_count = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .message-card {
            transition: transform 0.2s;
            border-radius: 10px;
        }
        .message-card:hover {
            transform: translateY(-5px);
        }
        .message-card.unread {
            border-left: 4px solid #0d6efd;
        }
        .message-card .card-header {
            background-color: rgba(0,0,0,0.03);
            border-bottom: none;
        }
        .pagination {
            margin-bottom: 0;
        }
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
    </style>
</head>
<body class="bg-light">
    <?php include '../includes/admin_header.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-envelope me-2"></i>Contact Messages</h2>
            <div>
                <span class="badge bg-primary me-2">
                    <i class="fas fa-envelope me-1"></i>
                    Total: <?php echo $total_count; ?>
                </span>
                <span class="badge bg-danger">
                    <i class="fas fa-envelope-open me-1"></i>
                    Unread: <?php echo $unread_count; ?>
                </span>
            </div>
        </div>

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

        <?php if (empty($messages)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>No messages found.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($messages as $message): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card message-card <?php echo $message['status'] === 'unread' ? 'unread' : ''; ?>">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($message['name']); ?></h5>
                                    <span class="text-muted small">
                                        <?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="small text-muted"><?php echo htmlspecialchars($message['email']); ?></div>
                                <span class="status-badge badge bg-<?php echo $message['status'] === 'unread' ? 'danger' : 'success'; ?>">
                                    <?php echo ucfirst($message['status']); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Subject: <?php echo htmlspecialchars($message['subject']); ?></h6>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                <div class="d-flex justify-content-end mt-3">
                                    <?php if ($message['status'] === 'unread'): ?>
                                        <form method="POST" class="me-2">
                                            <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                            <input type="hidden" name="action" value="mark_read">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fas fa-check me-1"></i>Mark as Read
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this message?');">
                                        <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash-alt me-1"></i>Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page-1; ?>">Previous</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page+1; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
