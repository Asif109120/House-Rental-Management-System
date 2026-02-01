<?php
require_once '../includes/header.php';
require_once '../config/db.php';

$database = new Database();
$db = $database->getConnection();

// Get landlord's properties
$query = "SELECT * FROM properties WHERE landlord_id = :landlord_id ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(":landlord_id", $_SESSION['user_id']);
$stmt->execute();
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Properties</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPropertyModal">
            <i class="fas fa-plus"></i> Add Property
        </button>
    </div>

    <div class="row">
        <?php foreach ($properties as $property): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <?php
                    // Get primary image
                    $query = "SELECT image_path FROM property_images WHERE property_id = :property_id AND is_primary = true LIMIT 1";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(":property_id", $property['id']);
                    $stmt->execute();
                    $image = $stmt->fetch(PDO::FETCH_ASSOC);
                    ?>
                    <img src="<?php echo $image ? '../uploads/' . $image['image_path'] : '../assets/img/placeholder.jpg'; ?>" 
                         class="card-img-top" alt="Property Image" style="height: 200px; object-fit: cover;">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($property['title']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($property['description']); ?></p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($property['city']); ?></li>
                            <li><i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?> Bedrooms</li>
                            <li><i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?> Bathrooms</li>
                            <li><i class="fas fa-rupee-sign"></i> ₹<?php echo number_format($property['price'], 2); ?>/month</li>
                        </ul>
                        <div class="d-flex justify-content-between mt-3">
                            <button class="btn btn-primary" onclick="editProperty(<?php echo $property['id']; ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-danger" onclick="deleteProperty(<?php echo $property['id']; ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                    <div class="card-footer">
                        <small class="text-muted">Status: <?php echo ucfirst($property['status']); ?></small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Add Property Modal -->
<div class="modal fade" id="addPropertyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Property</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addPropertyForm" action="property_actions.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" required minlength="3" maxlength="100">
                            <small class="text-muted">Between 3 and 100 characters</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="type" required>
                                <option value="apartment">Apartment</option>
                                <option value="house">House</option>
                                <option value="condo">Condo</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" required minlength="10" maxlength="1000"></textarea>
                        <small class="text-muted">Minimum 10 characters, maximum 1000 characters</small>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price (per month)</label>
                            <input type="number" class="form-control" name="price" required min="1" max="1000000" step="0.01" oninput="validateNumber(this, 1, 1000000)">
                            <small class="text-muted">Enter a value between ₹1 and ₹10,00,000</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Area (sq ft)</label>
                            <input type="number" class="form-control" name="area" required min="100" max="100000" step="1" oninput="validateNumber(this, 100, 100000)">
                            <small class="text-muted">Enter a value between 100 and 100,000 sq ft</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control" name="address" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">City</label>
                        <input type="text" class="form-control" name="city" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Images</label>
                        <input type="file" class="form-control" name="images[]" multiple accept="image/*" required>
                        <small class="text-muted">First image will be set as primary</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Property</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editProperty(id) {
    window.location.href = 'edit_property.php?id=' + id;
}

function deleteProperty(id) {
    if (confirm('Are you sure you want to delete this property? This will also delete all bookings and images associated with it.')) {
        fetch('property_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=delete&id=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message and reload
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success alert-dismissible fade show';
                alertDiv.innerHTML = `
                    Property deleted successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.d-flex'));
                
                // Reload after 1 second
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                // Show error message
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                alertDiv.innerHTML = `
                    ${data.message || 'Error deleting property. Please try again.'}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.d-flex'));
            }
        })
        .catch(error => {
            // Show network error
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger alert-dismissible fade show';
            alertDiv.innerHTML = `
                Network error occurred. Please try again.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.d-flex'));
        });
    }
}

function validateNumber(input, min, max) {
    if (input.value < min || input.value > max) {
        input.setCustomValidity(`Please enter a value between ${min} and ${max}`);
    } else {
        input.setCustomValidity('');
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
