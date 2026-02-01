<?php
session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $sender_id = $_SESSION['user_id'];
    $receiver_id = $_POST['receiver_id'];
    $message = $_POST['message'];
    
    $query = "INSERT INTO messages (sender_id, receiver_id, message) 
              VALUES (:sender_id, :receiver_id, :message)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":sender_id", $sender_id);
    $stmt->bindParam(":receiver_id", $receiver_id);
    $stmt->bindParam(":message", $message);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit();
}
