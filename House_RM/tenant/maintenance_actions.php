<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new Database();
        $db = $database->getConnection();

        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'create':
                // Validate input
                $property_id = filter_input(INPUT_POST, 'property_id', FILTER_VALIDATE_INT);
                $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
                $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);

                // Debug information
                error_log("Maintenance Request Debug:");
                error_log("Property ID: " . var_export($property_id, true));
                error_log("User ID: " . var_export($_SESSION['user_id'], true));

                if (!$property_id || !$title || !$description) {
                    throw new Exception("All fields are required.");
                }

                // Verify tenant has access to this property
                $sql = "SELECT b.*, p.title as property_title 
                        FROM bookings b 
                        JOIN properties p ON b.property_id = p.id
                        WHERE b.property_id = :property_id 
                        AND b.tenant_id = :tenant_id 
                        AND b.status = 'approved'";
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':property_id', $property_id, PDO::PARAM_INT);
                $stmt->bindParam(':tenant_id', $_SESSION['user_id'], PDO::PARAM_INT);
                $stmt->execute();

                $booking = $stmt->fetch(PDO::FETCH_ASSOC);
                error_log("Booking found: " . var_export($booking, true));

                if (!$booking) {
                    throw new Exception("You don't have access to this property.");
                }

                // Create maintenance request
                $sql = "INSERT INTO maintenance_requests (property_id, tenant_id, title, description, status) 
                        VALUES (:property_id, :tenant_id, :title, :description, 'pending')";
                
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':property_id', $property_id, PDO::PARAM_INT);
                $stmt->bindParam(':tenant_id', $_SESSION['user_id'], PDO::PARAM_INT);
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':description', $description);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Maintenance request submitted successfully.";
                } else {
                    throw new Exception("Failed to submit maintenance request.");
                }
                break;

            case 'cancel':
                $request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);

                if (!$request_id) {
                    throw new Exception("Invalid request ID.");
                }

                // Verify tenant owns this request and it's still pending
                $sql = "SELECT COUNT(*) FROM maintenance_requests 
                        WHERE id = :request_id 
                        AND tenant_id = :tenant_id 
                        AND status = 'pending'";
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
                $stmt->bindParam(':tenant_id', $_SESSION['user_id'], PDO::PARAM_INT);
                $stmt->execute();

                if ($stmt->fetchColumn() == 0) {
                    throw new Exception("You cannot cancel this request.");
                }

                // Cancel the request
                $sql = "UPDATE maintenance_requests 
                        SET status = 'cancelled' 
                        WHERE id = :request_id";
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Maintenance request cancelled successfully.";
                } else {
                    throw new Exception("Failed to cancel maintenance request.");
                }
                break;

            default:
                throw new Exception("Invalid action.");
        }

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        error_log("Maintenance request error: " . $e->getMessage());
    }
}

header("Location: maintenance.php");
exit();
