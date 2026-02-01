<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new Database();
        $db = $database->getConnection();

        switch ($_POST['action']) {
            case 'toggle_status':
                if (!isset($_POST['property_id'])) {
                    throw new Exception('Property ID not provided');
                }

                // Get current status
                $query = "SELECT status FROM properties WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":id", $_POST['property_id']);
                $stmt->execute();
                $property = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$property) {
                    throw new Exception('Property not found');
                }

                // Toggle status
                $new_status = $property['status'] === 'available' ? 'maintenance' : 'available';
                
                $query = "UPDATE properties SET status = :status WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":status", $new_status);
                $stmt->bindParam(":id", $_POST['property_id']);
                $stmt->execute();

                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
                break;

            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// Handle GET requests for CSV export
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['export'])) {
    try {
        $database = new Database();
        $db = $database->getConnection();

        $query = "SELECT p.*, u.username as landlord_name, u.email as landlord_email,
                  (SELECT COUNT(*) FROM bookings b WHERE b.property_id = p.id AND b.status = 'approved') as active_rentals
                  FROM properties p 
                  LEFT JOIN users u ON p.landlord_id = u.id 
                  ORDER BY p.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="properties_export_' . date('Y-m-d') . '.csv"');

        // Create CSV file
        $output = fopen('php://output', 'w');
        
        // Add headers
        fputcsv($output, ['ID', 'Title', 'Address', 'City', 'Type', 'Price', 'Status', 'Landlord', 'Email', 'Active Rentals', 'Added Date']);

        // Add data
        foreach ($properties as $property) {
            fputcsv($output, [
                $property['id'],
                $property['title'],
                $property['address'],
                $property['city'],
                $property['type'],
                $property['price'],
                $property['status'],
                $property['landlord_name'],
                $property['landlord_email'],
                $property['active_rentals'],
                $property['created_at']
            ]);
        }

        fclose($output);
        exit();
    } catch (Exception $e) {
        header('Content-Type: text/plain');
        echo 'Error exporting data: ' . $e->getMessage();
    }
    exit();
}
?>
