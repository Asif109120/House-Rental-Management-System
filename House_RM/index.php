<?php
session_start();
require_once 'config/db.php';

// If user is admin, redirect to admin dashboard
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    header("Location: admin/dashboard.php");
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Fetch featured properties
    $query = "SELECT p.*, u.username as landlord_name 
              FROM properties p 
              LEFT JOIN users u ON p.landlord_id = u.id 
              WHERE p.status = 'available' 
              ORDER BY p.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $featured_properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $featured_properties = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>House Rental Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
        }

        /* Navbar Styles */
        .navbar {
            background-color: rgba(255, 255, 255, 0.95);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .navbar-brand {
            font-weight: bold;
            color: var(--primary-color) !important;
            transition: color 0.3s ease;
        }

        .nav-link {
            color: var(--primary-color) !important;
            font-weight: 500;
            position: relative;
            transition: color 0.3s ease;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 50%;
            background-color: var(--secondary-color);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .nav-link:hover::after {
            width: 100%;
        }

        /* Hero Section */
        .hero {
            height: 70vh;
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)),
                        url('https://images.unsplash.com/photo-1560518883-ce09059eeffa?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1973&q=80');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            color: white;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: bold;
            margin-bottom: 1.5rem;
        }

        /* Property Cards */
        .property-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }

        .property-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .property-card img {
            height: 200px;
            object-fit: cover;
        }

        .property-card .card-body {
            padding: 1.5rem;
        }

        .property-card .price {
            color: var(--accent-color);
            font-size: 1.25rem;
            font-weight: bold;
        }

        /* Search Section */
        .search-section {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-top: -70px;
            position: relative;
            z-index: 10;
        }

        /* Custom Buttons */
        .btn-custom {
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-custom-primary {
            background-color: var(--secondary-color);
            color: white;
            border: none;
        }

        .btn-custom-primary:hover {
            background-color: var(--primary-color);
            transform: translateY(-2px);
        }

        /* Features Section */
        .feature-icon {
            font-size: 2.5rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        /* Animations */
        .fade-up {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s ease;
        }

        .fade-up.active {
            opacity: 1;
            transform: translateY(0);
        }

        /* Footer */
        footer {
            background-color: var(--primary-color);
            color: white;
            padding: 3rem 0;
        }

        .social-links a {
            color: white;
            font-size: 1.5rem;
            margin: 0 10px;
            transition: color 0.3s ease;
        }

        .social-links a:hover {
            color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">HRMS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $_SESSION['user_type']; ?>/dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="auth/logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="row">
                <div class="col-lg-8" data-aos="fade-right">
                    <h1>Find Your Perfect Home</h1>
                    <p class="lead mb-4">Discover the best rental properties in your area with our comprehensive rental management system.</p>
                    <!--<a href="properties.php" class="btn btn-custom btn-custom-primary me-3">Browse Properties</a>-->
                    <a href="about.php" class="btn btn-custom btn-outline-light">Learn More</a>
                </div>
            </div>
        </div>
    </section>

    

    <!-- Featured Properties -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5" data-aos="fade-up">Available Properties</h2>
            <div class="row g-4">
                <?php foreach ($featured_properties as $property): ?>
                    <div class="col-md-4" data-aos="fade-up">
                        <div class="property-card card h-100">
                            <?php
                            // Get primary image
                            $query = "SELECT image_path FROM property_images WHERE property_id = :property_id LIMIT 1";
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(":property_id", $property['id']);
                            $stmt->execute();
                            $image = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            $imagePath = $image ? htmlspecialchars('uploads/' . $image['image_path']) : 'assets/img/default-property.jpg';
                            ?>
                            <img src="<?php echo $imagePath; ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($property['title']); ?>"
                                 onerror="this.src='assets/img/default-property.jpg'">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($property['title']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars(substr($property['description'], 0, 100)) . '...'; ?></p>
                                <ul class="property-features">
                                    <li><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($property['city']); ?></li>
                                    <li><i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?> Bedrooms</li>
                                    <li><i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?> Bathrooms</li>
                                    <li><i class="fas fa-rupee-sign"></i> ₹<?php echo number_format($property['price'], 2); ?>/month</li>
                                </ul>
                                <div class="mt-3">
                                    <?php if (!isset($_SESSION['user_id'])): ?>
                                        <a href="register.php" class="btn btn-custom btn-custom-primary w-100"> Book Property</a>
                                    <?php elseif ($_SESSION['user_type'] !== 'tenant'): ?>
                                        <div class="alert alert-warning p-2 text-center mb-0">Please register as a tenant to book properties</div>
                                    <?php else: ?>
                                        <button onclick="showBookingModal(<?php echo $property['id']; ?>)" class="btn btn-custom btn-custom-primary w-100">Book</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5" data-aos="fade-up">Why Choose Us</h2>
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="text-center">
                        <i class="fas fa-home feature-icon"></i>
                        <h4>Wide Range of Properties</h4>
                        <p>Browse through our extensive collection of rental properties to find your perfect match.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="text-center">
                        <i class="fas fa-shield-alt feature-icon"></i>
                        <h4>Secure Transactions</h4>
                        <p>Our platform ensures safe and secure transactions for both tenants and landlords.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="text-center">
                        <i class="fas fa-headset feature-icon"></i>
                        <h4>24/7 Support</h4>
                        <p>Our dedicated support team is always ready to assist you with any queries.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>About HRMS</h5>
                    <p>Your trusted partner in finding the perfect rental property. We connect tenants with quality properties and reliable landlords.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="about.php" class="text-white">About Us</a></li>
                        <!--<li><a href="properties.php" class="text-white">Properties</a></li>-->
                        <li><a href="contact.php" class="text-white">Contact Us</a></li>
                       <!--<li><a href="privacy.php" class="text-white">Privacy Policy</a></li>-->
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Connect With Us</h5>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <hr class="mt-4">
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> House Rental Management System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Book Property</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="tenant/booking_actions.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="property_id" id="modal_property_id">
                        <div class="mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message to Landlord (Optional)</label>
                            <textarea class="form-control" name="message" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Submit Booking Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            once: true
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.backgroundColor = 'rgba(255, 255, 255, 0.98)';
                navbar.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
            } else {
                navbar.style.backgroundColor = 'rgba(255, 255, 255, 0.95)';
                navbar.style.boxShadow = 'none';
            }
        });

        // Property card hover effect
        document.querySelectorAll('.property-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        function showBookingModal(propertyId) {
            document.getElementById('modal_property_id').value = propertyId;
            new bootstrap.Modal(document.getElementById('bookingModal')).show();
        }
    </script>
</body>
</html>
