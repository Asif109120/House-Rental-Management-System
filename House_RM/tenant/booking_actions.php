<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    if (isset($_POST['action']) && $_POST['action'] === 'cancel') {
        try {
            $booking_id = $_POST['booking_id'];
            $tenant_id = $_SESSION['user_id'];
            
            // Check if booking exists and belongs to tenant
            $query = "SELECT id, status FROM bookings WHERE id = :id AND tenant_id = :tenant_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $booking_id);
            $stmt->bindParam(":tenant_id", $tenant_id);
            $stmt->execute();
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking) {
                throw new Exception("Booking not found or you don't have permission to cancel it.");
            }
            
            if ($booking['status'] !== 'pending') {
                throw new Exception("Only pending bookings can be cancelled.");
            }
            
            // Cancel the booking
            $query = "UPDATE bookings SET status = 'cancelled' WHERE id = :id AND tenant_id = :tenant_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $booking_id);
            $stmt->bindParam(":tenant_id", $tenant_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception("Failed to cancel booking.");
            }
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }
    
    $property_id = $_POST['property_id'];
    $tenant_id = $_SESSION['user_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    try {
        // Check if property is still available
        $query = "SELECT status FROM properties WHERE id = :property_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":property_id", $property_id);
        $stmt->execute();
        $property = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($property['status'] !== 'available') {
            throw new Exception("Property is no longer available");
        }
        
        // Check for overlapping bookings
        $query = "SELECT COUNT(*) FROM bookings 
                 WHERE property_id = :property_id 
                 AND status = 'approved'
                 AND (
                     (start_date <= :start_date AND end_date >= :start_date)
                     OR (start_date <= :end_date AND end_date >= :end_date)
                     OR (start_date >= :start_date AND end_date <= :end_date)
                 )";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":property_id", $property_id);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Selected dates are not available");
        }
        
        // Create booking
        $query = "INSERT INTO bookings (property_id, tenant_id, start_date, end_date, status) 
                 VALUES (:property_id, :tenant_id, :start_date, :end_date, 'pending')";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":property_id", $property_id);
        $stmt->bindParam(":tenant_id", $tenant_id);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        
        if ($stmt->execute()) {
            // Send notification to landlord
            $query = "SELECT landlord_id FROM properties WHERE id = :property_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":property_id", $property_id);
            $stmt->execute();
            $landlord = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($landlord) {
                $message = "New booking request received for your property.";
                $query = "INSERT INTO messages (sender_id, receiver_id, message) 
                         VALUES (:sender_id, :receiver_id, :message)";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(":sender_id", $tenant_id);
                $stmt->bindParam(":receiver_id", $landlord['landlord_id']);
                $stmt->bindParam(":message", $message);
                $stmt->execute();
            }
            
            $_SESSION['success'] = "Booking request sent successfully!";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: properties.php");
    exit();
}
