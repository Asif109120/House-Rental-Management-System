<?php
session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new Database();
        $db = $database->getConnection();

        // Sanitize input data
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
        $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
        
        // Validate required fields
        if (!$name || !$email || !$subject || !$message) {
            $_SESSION['error'] = "All fields are required.";
            header("Location: contact.php");
            exit();
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Invalid email format.";
            header("Location: contact.php");
            exit();
        }

        // Insert message into database
        $sql = "INSERT INTO contact_messages (name, email, subject, message, status, created_at) 
                VALUES (:name, :email, :subject, :message, 'unread', NOW())";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':subject' => $subject,
            ':message' => $message
        ]);

        if ($result) {
            // Send email notification (you can configure this based on your email settings)
            $to = "admin@hrms.com";
            $headers = "From: " . $email . "\r\n";
            $headers .= "Reply-To: " . $email . "\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            $emailBody = "New Contact Form Submission\n\n";
            $emailBody .= "Name: " . $name . "\n";
            $emailBody .= "Email: " . $email . "\n";
            $emailBody .= "Subject: " . $subject . "\n";
            $emailBody .= "Message:\n" . $message;

            mail($to, "New Contact Form Submission: " . $subject, $emailBody, $headers);

            $_SESSION['success'] = "Thank you for your message. We will get back to you soon!";
        } else {
            throw new Exception("Failed to insert message into database.");
        }

    } catch (Exception $e) {
        error_log("Contact form error: " . $e->getMessage());
        $_SESSION['error'] = "Sorry, there was an error processing your request. Please try again later.";
    }
} else {
    $_SESSION['error'] = "Invalid request method.";
}

header("Location: contact.php");
exit();
?>
