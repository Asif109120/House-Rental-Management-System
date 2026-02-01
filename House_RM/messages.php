<?php
require_once 'includes/header.php';
require_once 'config/db.php';

$database = new Database();
$db = $database->getConnection();

// Get the selected user to chat with
$selected_user = isset($_GET['user']) ? $_GET['user'] : null;

// Get list of users this user has chatted with
$query = "SELECT DISTINCT u.id, u.username, u.full_name, u.user_type
          FROM users u
          INNER JOIN messages m ON (m.sender_id = u.id OR m.receiver_id = u.id)
          WHERE (m.sender_id = :user_id OR m.receiver_id = :user_id)
          AND u.id != :user_id";

$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $_SESSION['user_id']);
$stmt->execute();
$chat_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If a user is selected, get chat messages
if ($selected_user) {
    $query = "SELECT m.*, u.username, u.full_name
              FROM messages m
              JOIN users u ON m.sender_id = u.id
              WHERE (m.sender_id = :user_id AND m.receiver_id = :selected_user)
              OR (m.sender_id = :selected_user AND m.receiver_id = :user_id)
              ORDER BY m.created_at ASC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $_SESSION['user_id']);
    $stmt->bindParam(":selected_user", $selected_user);
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mark messages as read
    $query = "UPDATE messages 
              SET is_read = true 
              WHERE sender_id = :selected_user 
              AND receiver_id = :user_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":selected_user", $selected_user);
    $stmt->bindParam(":user_id", $_SESSION['user_id']);
    $stmt->execute();
}
?>

<div class="container-fluid">
    <div class="row">
        <!-- Chat Users List -->
        <div class="col-md-4 col-lg-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Messages</h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach ($chat_users as $user): ?>
                        <a href="?user=<?php echo $user['id']; ?>" 
                           class="list-group-item list-group-item-action <?php echo $selected_user == $user['id'] ? 'active' : ''; ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($user['full_name']); ?></h6>
                                    <small><?php echo ucfirst($user['user_type']); ?></small>
                                </div>
                                <?php
                                // Get unread message count
                                $query = "SELECT COUNT(*) FROM messages 
                                         WHERE sender_id = :sender_id 
                                         AND receiver_id = :receiver_id 
                                         AND is_read = false";
                                $stmt = $db->prepare($query);
                                $stmt->bindParam(":sender_id", $user['id']);
                                $stmt->bindParam(":receiver_id", $_SESSION['user_id']);
                                $stmt->execute();
                                $unread = $stmt->fetchColumn();
                                
                                if ($unread > 0):
                                ?>
                                <span class="badge bg-primary rounded-pill"><?php echo $unread; ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Chat Messages -->
        <div class="col-md-8 col-lg-9">
            <?php if ($selected_user): ?>
                <div class="card">
                    <div class="card-body" style="height: 70vh; display: flex; flex-direction: column;">
                        <!-- Messages Container -->
                        <div class="flex-grow-1 overflow-auto mb-3" id="messagesContainer">
                            <?php foreach ($messages as $message): ?>
                                <div class="d-flex mb-3 <?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'justify-content-end' : 'justify-content-start'; ?>">
                                    <div class="<?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'bg-primary text-white' : 'bg-light'; ?> 
                                                rounded p-3" style="max-width: 70%;">
                                        <div class="small mb-1">
                                            <?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'You' : htmlspecialchars($message['full_name']); ?>
                                        </div>
                                        <?php echo htmlspecialchars($message['message']); ?>
                                        <div class="small text-end">
                                            <?php echo date('M j, g:i a', strtotime($message['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Message Input -->
                        <form id="messageForm" class="mt-auto">
                            <input type="hidden" name="receiver_id" value="<?php echo $selected_user; ?>">
                            <div class="input-group">
                                <input type="text" class="form-control" name="message" placeholder="Type your message..." required>
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-paper-plane"></i> Send
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center">
                        <h5>Select a user to start chatting</h5>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const messagesContainer = document.getElementById('messagesContainer');
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    const messageForm = document.getElementById('messageForm');
    if (messageForm) {
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('message_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error sending message');
                }
            });
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
