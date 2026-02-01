<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is a landlord
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'landlord') {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new Database();
        $db = $database->getConnection();

        $action = $_POST['action'] ?? '';
        $request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);

        if (!$request_id) {
            throw new Exception("Invalid request ID.");
        }

        // Verify landlord owns the property associated with this request
        $sql = "SELECT COUNT(*) FROM maintenance_requests mr
                INNER JOIN properties p ON mr.property_id = p.id
                WHERE mr.id = :request_id AND p.landlord_id = :landlord_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
        $stmt->bindParam(':landlord_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->fetchColumn() == 0) {
            throw new Exception("You don't have permission to modify this request.");
        }

        switch ($action) {
            case 'start':
                // Update status to in_progress
                $sql = "UPDATE maintenance_requests 
                        SET status = 'in_progress' 
                        WHERE id = :request_id AND status = 'pending'";
                $message = "Work started on maintenance request.";
                break;

            case 'complete':
                // Update status to completed
                $sql = "UPDATE maintenance_requests 
                        SET status = 'completed' 
                        WHERE id = :request_id AND status = 'in_progress'";
                $message = "Maintenance request marked as completed.";
                break;

            default:
                throw new Exception("Invalid action.");
        }

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = $message;
        } else {
            throw new Exception("Failed to update maintenance request.");
        }

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        error_log("Maintenance action error: " . $e->getMessage());
    }
}

header("Location: maintenance.php");
exit();
?>
