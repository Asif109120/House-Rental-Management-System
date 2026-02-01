<?php
session_start();
require_once 'config/db.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!isset($_GET['id'])) {
        header("Location: index.php");
        exit();
    }

    $property_id = $_GET['id'];

    // Fetch property details
    $query = "SELECT p.*, u.username as landlord_name, u.email as landlord_email, u.phone as landlord_phone 
              FROM properties p 
              LEFT JOIN users u ON p.landlord_id = u.id 
              WHERE p.id = :id AND p.status = 'available'";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $property_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("Property not found");
    }

    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch property images
    $query = "SELECT * FROM property_images WHERE property_id = :property_id ORDER BY is_primary DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":property_id", $property_id);
    $stmt->execute();
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($property['title']); ?> - House Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .property-images img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 10px;
        }
        .thumbnail {
            width: 100px;
            height: 100px;
            object-fit: cover;
            cursor: pointer;
            border-radius: 5px;
            transition: opacity 0.3s;
        }
        .thumbnail:hover {
            opacity: 0.8;
        }
        .features i {
            color: #3498db;
            margin-right: 10px;
        }
        .contact-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8">
                <!-- Property Images -->
                <div class="property-images mb-4">
                    <?php if (!empty($images)): ?>
                        <img src="uploads/<?php echo htmlspecialchars($images[0]['image_path']); ?>" 
                             id="mainImage" 
                             class="mb-3" 
                             alt="<?php echo htmlspecialchars($property['title']); ?>">
                        
                        <div class="d-flex gap-2 overflow-auto">
                            <?php foreach ($images as $image): ?>
                                <img src="uploads/<?php echo htmlspecialchars($image['image_path']); ?>" 
                                     class="thumbnail" 
                                     onclick="changeMainImage('uploads/<?php echo htmlspecialchars($image['image_path']); ?>')"
                                     alt="Property Image">
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <img src="assets/img/placeholder.jpg" class="mb-3" alt="No Image Available">
                    <?php endif; ?>
                </div>

                <!-- Property Details -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="card-title"><?php echo htmlspecialchars($property['title']); ?></h2>
                        <h4 class="text-primary mb-3">₹<?php echo number_format($property['price']); ?>/month</h4>
                        
                        <div class="features mb-4">
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($property['address'] . ', ' . $property['city']); ?></p>
                            <p><i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?> Bedrooms</p>
                            <p><i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?> Bathrooms</p>
                            <p><i class="fas fa-ruler-combined"></i> <?php echo $property['area']; ?> sq.ft</p>
                        </div>

                        <h5>Description</h5>
                        <p><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Contact Landlord -->
                <div class="contact-box mb-4">
                    <h4>Contact Landlord</h4>
                    <div class="mb-3">
                        <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($property['landlord_name']); ?></p>
                        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($property['landlord_email']); ?></p>
                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($property['landlord_phone']); ?></p>
                    </div>
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'tenant'): ?>
                        <button class="btn btn-primary w-100 mb-2" onclick="requestBooking(<?php echo $property['id']; ?>)">
                            Request Booking
                        </button>
                        <a href="messages.php?user=<?php echo $property['landlord_id']; ?>" class="btn btn-outline-primary w-100">
                            Send Message
                        </a>
                    <?php elseif (!isset($_SESSION['user_id'])): ?>
                        <div class="alert alert-info">
                            Please <a href="auth/login.php">login</a> as a tenant to request booking or contact the landlord.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Request Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="tenant/booking_actions.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="property_id" id="property_id">
                        <div class="mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message to Landlord</label>
                            <textarea class="form-control" name="message" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Send Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function changeMainImage(src) {
            document.getElementById('mainImage').src = src;
        }

        function requestBooking(propertyId) {
            document.getElementById('property_id').value = propertyId;
            new bootstrap.Modal(document.getElementById('bookingModal')).show();
        }
    </script>
</body>
</html>
