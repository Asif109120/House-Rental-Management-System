<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($_POST['action'] === 'update_status') {
        $booking_id = $_POST['booking_id'];
        $status = $_POST['status'];
        $landlord_id = $_SESSION['user_id'];
        
        try {
            $db->beginTransaction();
            
            // Verify that this booking belongs to a property owned by this landlord
            $query = "SELECT b.*, p.title as property_title 
                     FROM bookings b 
                     JOIN properties p ON b.property_id = p.id 
                     WHERE b.id = :booking_id AND p.landlord_id = :landlord_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(":booking_id", $booking_id);
            $stmt->bindParam(":landlord_id", $landlord_id);
            $stmt->execute();
            
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$booking) {
                throw new Exception("Booking not found or you don't have permission to update it.");
            }
            
            // Update booking status
            $query = "UPDATE bookings SET status = :status WHERE id = :booking_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":booking_id", $booking_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update booking status.");
            }
            
            // If approved, update property status to rented
            if ($status === 'approved') {
                $query = "UPDATE properties p 
                         JOIN bookings b ON p.id = b.property_id 
                         SET p.status = 'rented' 
                         WHERE b.id = :booking_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":booking_id", $booking_id);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update property status.");
                }
                
                // Cancel other pending bookings for this property
                $query = "UPDATE bookings 
                         SET status = 'cancelled' 
                         WHERE property_id = :property_id 
                         AND id != :booking_id 
                         AND status = 'pending'";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":property_id", $booking['property_id']);
                $stmt->bindParam(":booking_id", $booking_id);
                $stmt->execute();
            }
            
            // Get tenant information for notification
            $query = "SELECT u.id as tenant_id, u.full_name as tenant_name 
                     FROM bookings b
                     JOIN users u ON b.tenant_id = u.id
                     WHERE b.id = :booking_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":booking_id", $booking_id);
            $stmt->execute();
            $tenant = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($tenant) {
                // Send notification to tenant
                $message = "Your booking request for {$booking['property_title']} has been " . strtoupper($status);
                $query = "INSERT INTO messages (sender_id, receiver_id, message, created_at) 
                         VALUES (:sender_id, :receiver_id, :message, NOW())";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(":sender_id", $landlord_id);
                $stmt->bindParam(":receiver_id", $tenant['tenant_id']);
                $stmt->bindParam(":message", $message);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to send notification to tenant.");
                }
            }
            
            $db->commit();
            echo json_encode(['success' => true]);
            exit();
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Error in booking_actions.php: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit();
        }
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
exit();
