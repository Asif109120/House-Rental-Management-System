<?php
session_start();
require_once '../config/db.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        try {
            $db->beginTransaction();
            
            $property_id = $_POST['id'];
            $landlord_id = $_SESSION['user_id'];
            
            // Check if property exists and belongs to the landlord
            $query = "SELECT id FROM properties WHERE id = :id AND landlord_id = :landlord_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $property_id);
            $stmt->bindParam(":landlord_id", $landlord_id);
            $stmt->execute();
            
            if (!$stmt->fetch()) {
                throw new Exception("Property not found or you don't have permission to delete it.");
            }
            
            // Get property images to delete files
            $query = "SELECT image_path FROM property_images WHERE property_id = :property_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":property_id", $property_id);
            $stmt->execute();
            $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Delete image files from filesystem
            foreach ($images as $image) {
                $file_path = '../uploads/' . $image['image_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            
            // Delete the property (this will cascade to delete related records)
            $query = "DELETE FROM properties WHERE id = :id AND landlord_id = :landlord_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $property_id);
            $stmt->bindParam(":landlord_id", $landlord_id);
            
            if ($stmt->execute()) {
                $db->commit();
                $_SESSION['success'] = "Property deleted successfully.";
            } else {
                throw new Exception("Failed to delete property.");
            }
            
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = "Error deleting property: " . $e->getMessage();
        }
        
        header("Location: properties.php");
        exit();
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit') {
        // Edit existing property
        try {
            $db->beginTransaction();
            
            $property_id = $_POST['property_id'];
            
            // Update property details
            $query = "UPDATE properties 
                     SET title = :title, 
                         description = :description, 
                         type = :type, 
                         address = :address, 
                         city = :city, 
                         price = :price, 
                         bedrooms = :bedrooms, 
                         bathrooms = :bathrooms, 
                         area = :area,
                         status = :status
                     WHERE id = :id AND landlord_id = :landlord_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(":title", $_POST['title']);
            $stmt->bindParam(":description", $_POST['description']);
            $stmt->bindParam(":type", $_POST['type']);
            $stmt->bindParam(":address", $_POST['address']);
            $stmt->bindParam(":city", $_POST['city']);
            $stmt->bindParam(":price", $_POST['price']);
            $stmt->bindParam(":bedrooms", $_POST['bedrooms']);
            $stmt->bindParam(":bathrooms", $_POST['bathrooms']);
            $stmt->bindParam(":area", $_POST['area']);
            $stmt->bindParam(":status", $_POST['status']);
            $stmt->bindParam(":id", $property_id);
            $stmt->bindParam(":landlord_id", $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                // Handle image deletions
                if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
                    foreach ($_POST['delete_images'] as $image_id) {
                        // Get image path before deleting
                        $query = "SELECT image_path FROM property_images WHERE id = :id AND property_id = :property_id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(":id", $image_id);
                        $stmt->bindParam(":property_id", $property_id);
                        $stmt->execute();
                        $image = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($image) {
                            // Delete file
                            $file_path = '../uploads/' . $image['image_path'];
                            if (file_exists($file_path)) {
                                unlink($file_path);
                            }
                            
                            // Delete record
                            $query = "DELETE FROM property_images WHERE id = :id AND property_id = :property_id";
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(":id", $image_id);
                            $stmt->bindParam(":property_id", $property_id);
                            $stmt->execute();
                        }
                    }
                }
                
                // Handle primary image
                if (isset($_POST['primary_image'])) {
                    // Reset all images to non-primary
                    $query = "UPDATE property_images SET is_primary = false WHERE property_id = :property_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(":property_id", $property_id);
                    $stmt->execute();
                    
                    // Set selected image as primary
                    $query = "UPDATE property_images SET is_primary = true WHERE id = :id AND property_id = :property_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(":id", $_POST['primary_image']);
                    $stmt->bindParam(":property_id", $property_id);
                    $stmt->execute();
                }
                
                // Handle new image uploads
                if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                    $upload_dir = '../uploads/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                        $file_name = time() . '_' . $_FILES['images']['name'][$key];
                        $file_path = $upload_dir . $file_name;
                        
                        if (move_uploaded_file($tmp_name, $file_path)) {
                            // Insert image record
                            $query = "INSERT INTO property_images (property_id, image_path, is_primary) 
                                     VALUES (:property_id, :image_path, :is_primary)";
                            
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(":property_id", $property_id);
                            $stmt->bindParam(":image_path", $file_name);
                            $is_primary = false; // New images are never primary when editing
                            $stmt->bindParam(":is_primary", $is_primary);
                            $stmt->execute();
                        }
                    }
                }
                
                $db->commit();
                $_SESSION['success'] = "Property updated successfully!";
            }
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = "Error updating property: " . $e->getMessage();
        }
        
        header("Location: properties.php");
        exit();
    } else {
        // Add new property
        try {
            $db->beginTransaction();
            
            // Insert property
            $query = "INSERT INTO properties (landlord_id, title, description, type, address, city, price, bedrooms, bathrooms, area) 
                      VALUES (:landlord_id, :title, :description, :type, :address, :city, :price, :bedrooms, :bathrooms, :area)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(":landlord_id", $_SESSION['user_id']);
            $stmt->bindParam(":title", $_POST['title']);
            $stmt->bindParam(":description", $_POST['description']);
            $stmt->bindParam(":type", $_POST['type']);
            $stmt->bindParam(":address", $_POST['address']);
            $stmt->bindParam(":city", $_POST['city']);
            $stmt->bindParam(":price", $_POST['price']);
            $stmt->bindParam(":bedrooms", $_POST['bedrooms']);
            $stmt->bindParam(":bathrooms", $_POST['bathrooms']);
            $stmt->bindParam(":area", $_POST['area']);
            
            if ($stmt->execute()) {
                $property_id = $db->lastInsertId();
                
                // Handle image uploads
                if (isset($_FILES['images'])) {
                    $upload_dir = '../uploads/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                        $file_name = time() . '_' . $_FILES['images']['name'][$key];
                        $file_path = $upload_dir . $file_name;
                        
                        if (move_uploaded_file($tmp_name, $file_path)) {
                            // Insert image record
                            $query = "INSERT INTO property_images (property_id, image_path, is_primary) 
                                     VALUES (:property_id, :image_path, :is_primary)";
                            
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(":property_id", $property_id);
                            $stmt->bindParam(":image_path", $file_name);
                            $is_primary = ($key === 0); // First image is primary
                            $stmt->bindParam(":is_primary", $is_primary);
                            $stmt->execute();
                        }
                    }
                }
                
                $db->commit();
                $_SESSION['success'] = "Property added successfully!";
            }
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = "Error adding property: " . $e->getMessage();
        }
        
        header("Location: properties.php");
        exit();
    }
}
