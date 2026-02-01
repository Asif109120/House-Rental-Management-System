<?php
require_once '../includes/header.php';
require_once '../config/db.php';

$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$city = isset($_GET['city']) ? $_GET['city'] : '';
$min_price = isset($_GET['min_price']) ? $_GET['min_price'] : '';
$max_price = isset($_GET['max_price']) ? $_GET['max_price'] : '';
$bedrooms = isset($_GET['bedrooms']) ? $_GET['bedrooms'] : '';
$property_type = isset($_GET['property_type']) ? $_GET['property_type'] : '';

// Build query
$query = "SELECT p.*, u.full_name as landlord_name, u.phone as landlord_phone 
          FROM properties p 
          JOIN users u ON p.landlord_id = u.id 
          WHERE p.status = 'available'";

if ($city) {
    $query .= " AND p.city LIKE :city";
}
if ($min_price) {
    $query .= " AND p.price >= :min_price";
}
if ($max_price) {
    $query .= " AND p.price <= :max_price";
}
if ($bedrooms) {
    $query .= " AND p.bedrooms = :bedrooms";
}
if ($property_type) {
    $query .= " AND p.type = :property_type";
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $db->prepare($query);

// Bind parameters
if ($city) {
    $city_param = "%$city%";
    $stmt->bindParam(":city", $city_param);
}
if ($min_price) {
    $stmt->bindParam(":min_price", $min_price);
}
if ($max_price) {
    $stmt->bindParam(":max_price", $max_price);
}
if ($bedrooms) {
    $stmt->bindParam(":bedrooms", $bedrooms);
}
if ($property_type) {
    $stmt->bindParam(":property_type", $property_type);
}

$stmt->execute();
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h2 class="mb-4">Find Your Perfect Home</h2>
    
    <!-- Search Filters 
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">City</label>
                    <input type="text" class="form-control" name="city" value="<?php echo htmlspecialchars($city); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Min Price (₹)</label>
                    <input type="number" class="form-control" name="min_price" value="<?php echo htmlspecialchars($min_price); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Max Price (₹)</label>
                    <input type="number" class="form-control" name="max_price" value="<?php echo htmlspecialchars($max_price); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Bedrooms</label>
                    <select class="form-select" name="bedrooms">
                        <option value="">Any</option>
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $bedrooms == $i ? 'selected' : ''; ?>>
                                <?php echo $i; ?>+
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Property Type</label>
                    <select class="form-select" name="property_type">
                        <option value="">Any</option>
                        <option value="apartment" <?php echo $property_type == 'apartment' ? 'selected' : ''; ?>>Apartment</option>
                        <option value="house" <?php echo $property_type == 'house' ? 'selected' : ''; ?>>House</option>
                        <option value="condo" <?php echo $property_type == 'condo' ? 'selected' : ''; ?>>Condo</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>
        </div>
    </div>-->

    <!-- Property Listings -->
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
                            <li><i class="fas fa-user"></i> <?php echo htmlspecialchars($property['landlord_name']); ?></li>
                            <li><i class="fas fa-phone"></i> <?php echo htmlspecialchars($property['landlord_phone']); ?></li>
                        </ul>
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" onclick="requestBooking(<?php echo $property['id']; ?>)">
                                Request Booking
                            </button>
                            <button class="btn btn-outline-primary" onclick="contactLandlord(<?php echo $property['landlord_id']; ?>)">
                                Contact Landlord
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
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
            <form id="bookingForm" action="booking_actions.php" method="POST">
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

<script>
function requestBooking(propertyId) {
    document.getElementById('property_id').value = propertyId;
    new bootstrap.Modal(document.getElementById('bookingModal')).show();
}

function contactLandlord(landlordId) {
    window.location.href = '../messages.php?user=' + landlordId;
}
</script>

<?php require_once '../includes/footer.php'; ?>
