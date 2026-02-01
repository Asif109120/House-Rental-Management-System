<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'];

        switch ($action) {
            case 'update_status':
                if (!isset($_POST['booking_id']) || !isset($_POST['status'])) {
                    throw new Exception('Missing required parameters');
                }

                $allowed_statuses = ['pending', 'approved', 'rejected', 'cancelled'];
                if (!in_array($_POST['status'], $allowed_statuses)) {
                    throw new Exception('Invalid status');
                }

                $query = "UPDATE bookings SET status = :status WHERE id = :booking_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':status', $_POST['status'], PDO::PARAM_STR);
                $stmt->bindParam(':booking_id', $_POST['booking_id'], PDO::PARAM_INT);

                if ($stmt->execute()) {
                    // If approved, update property status to rented
                    if ($_POST['status'] === 'approved') {
                        $query = "UPDATE properties p 
                                 JOIN bookings b ON p.id = b.property_id 
                                 SET p.status = 'rented' 
                                 WHERE b.id = :booking_id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':booking_id', $_POST['booking_id'], PDO::PARAM_INT);
                        $stmt->execute();
                    }

                    echo json_encode(['success' => true]);
                } else {
                    throw new Exception('Error updating booking status');
                }
                break;

            default:
                throw new Exception('Invalid action');
        }
    } else {
        throw new Exception('Invalid request method');
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
