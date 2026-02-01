<?php
require_once '../includes/header.php';
require_once '../config/db.php';

// Check if user is landlord
if ($_SESSION['user_type'] !== 'landlord') {
    header("Location: ../index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$property_id = isset($_GET['id']) ? $_GET['id'] : 0;

// Get property details
$query = "SELECT * FROM properties WHERE id = :id AND landlord_id = :landlord_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $property_id);
$stmt->bindParam(":landlord_id", $_SESSION['user_id']);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    header("Location: properties.php");
    exit();
}

$property = $stmt->fetch(PDO::FETCH_ASSOC);

// Get property images
$query = "SELECT * FROM property_images WHERE property_id = :property_id ORDER BY is_primary DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(":property_id", $property_id);
$stmt->execute();
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Edit Property</h2>
        <a href="properties.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Properties
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="property_actions.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="property_id" value="<?php echo $property_id; ?>">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($property['title']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="type" required>
                            <option value="apartment" <?php echo $property['type'] === 'apartment' ? 'selected' : ''; ?>>Apartment</option>
                            <option value="house" <?php echo $property['type'] === 'house' ? 'selected' : ''; ?>>House</option>
                            <option value="condo" <?php echo $property['type'] === 'condo' ? 'selected' : ''; ?>>Condo</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="3" required><?php echo htmlspecialchars($property['description']); ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Price (per month)</label>
                        <input type="number" class="form-control" name="price" value="<?php echo $property['price']; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Area (sq ft)</label>
                        <input type="number" class="form-control" name="area" value="<?php echo $property['area']; ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Bedrooms</label>
                        <input type="number" class="form-control" name="bedrooms" value="<?php echo $property['bedrooms']; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Bathrooms</label>
                        <input type="number" class="form-control" name="bathrooms" value="<?php echo $property['bathrooms']; ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($property['address']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">City</label>
                    <input type="text" class="form-control" name="city" value="<?php echo htmlspecialchars($property['city']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status" required>
                        <option value="available" <?php echo $property['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="rented" <?php echo $property['status'] === 'rented' ? 'selected' : ''; ?>>Rented</option>
                        <option value="maintenance" <?php echo $property['status'] === 'maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                    </select>
                </div>

                <!-- Current Images -->
                <?php if (!empty($images)): ?>
                <div class="mb-3">
                    <label class="form-label">Current Images</label>
                    <div class="row">
                        <?php foreach ($images as $image): ?>
                            <div class="col-md-3 mb-3">
                                <div class="card">
                                    <img src="../uploads/<?php echo $image['image_path']; ?>" class="card-img-top" alt="Property Image" style="height: 200px; object-fit: cover;">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="primary_image" value="<?php echo $image['id']; ?>" <?php echo $image['is_primary'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Set as Primary</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="delete_images[]" value="<?php echo $image['id']; ?>">
                                            <label class="form-check-label">Delete</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Add New Images</label>
                    <input type="file" class="form-control" name="images[]" multiple accept="image/*">
                    <small class="text-muted">Leave empty to keep current images</small>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Update Property</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
