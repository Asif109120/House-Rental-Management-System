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
    fputcsv($output, [
        'ID',
        'Title',
        'Address',
        'City',
        'Type',
        'Price',
        'Status',
        'Landlord',
        'Email',
        'Active Rentals',
        'Added Date',
        'Bedrooms',
        'Bathrooms',
        'Area',
        'Is Featured'
    ]);

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
            $property['created_at'],
            $property['bedrooms'],
            $property['bathrooms'],
            $property['area'],
            $property['is_featured'] ? 'Yes' : 'No'
        ]);
    }

    fclose($output);
} catch (Exception $e) {
    header('Content-Type: text/plain');
    echo 'Error exporting data: ' . $e->getMessage();
}
?>
