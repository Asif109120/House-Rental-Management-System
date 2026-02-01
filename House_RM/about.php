<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - House Rental Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        /* Navbar Styles */
        .navbar {
            background-color: rgba(255, 255, 255, 0.95);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .nav-link {
            position: relative;
        }
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 50%;
            background-color: #3498db;
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }
        .nav-link:hover::after {
            width: 100%;
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)),
                        url('https://images.unsplash.com/photo-1582407947304-fd86f028f716?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            margin-bottom: 50px;
        }

        /* Team Section */
        .team-member {
            text-align: center;
            margin-bottom: 30px;
            transition: transform 0.3s ease;
        }
        .team-member:hover {
            transform: translateY(-10px);
        }
        .team-member img {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            margin-bottom: 20px;
            object-fit: cover;
        }
        
        /* Timeline */
        .timeline {
            position: relative;
            padding: 50px 0;
        }
        .timeline::before {
            content: '';
            position: absolute;
            width: 2px;
            background: #3498db;
            top: 0;
            bottom: 0;
            left: 50%;
            margin-left: -1px;
        }
        .timeline-item {
            margin-bottom: 50px;
            position: relative;
        }
        .timeline-content {
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            position: relative;
            width: 45%;
            margin-left: auto;
        }
        .timeline-item:nth-child(even) .timeline-content {
            margin-left: 0;
        }
        .timeline-content::before {
            content: '';
            position: absolute;
            top: 20px;
            width: 20px;
            height: 20px;
            background: #3498db;
            border-radius: 50%;
        }
        .timeline-item:nth-child(odd) .timeline-content::before {
            left: -60px;
        }
        .timeline-item:nth-child(even) .timeline-content::before {
            right: -60px;
        }

        /* Stats */
        .stats {
            background: #f8f9fa;
            padding: 50px 0;
        }
        .stat-item {
            text-align: center;
            padding: 20px;
        }
        .stat-item i {
            font-size: 40px;
            color: #3498db;
            margin-bottom: 15px;
        }
        .stat-item h3 {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero text-center">
        <div class="container">
            <h1 class="display-4 mb-4" data-aos="fade-up">About Us</h1>
            <p class="lead" data-aos="fade-up" data-aos-delay="100">
                We're revolutionizing the way people rent properties by creating a seamless connection between landlords and tenants.
            </p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container">
        <!-- Our Story -->
        <section class="mb-5">
            <h2 class="text-center mb-4" data-aos="fade-up">Our Story</h2>
            <div class="row">
                <div class="col-md-6" data-aos="fade-right">
                    <p>House Rental Management System (HRMS) was founded with a simple mission: to make property rental easy, secure, and accessible for everyone. We understand the challenges both landlords and tenants face in the rental market.</p>
                    <p>Our platform brings together cutting-edge technology and user-friendly design to create a seamless rental experience. Whether you're a landlord looking to manage your properties efficiently or a tenant searching for your perfect home, HRMS has you covered.</p>
                </div>
                <div class="col-md-6" data-aos="fade-left">
                    <img src="https://images.unsplash.com/photo-1497366216548-37526070297c?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" 
                         class="img-fluid rounded" alt="Our Story">
                </div>
            </div>
        </section>

        <!-- Stats -->
        <section class="stats rounded mb-5">
            <div class="row">
                <div class="col-md-3" data-aos="fade-up">
                    <div class="stat-item">
                        <i class="fas fa-home"></i>
                        <h3>1000+</h3>
                        <p>Properties Listed</p>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-item">
                        <i class="fas fa-users"></i>
                        <h3>5000+</h3>
                        <p>Happy Users</p>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-item">
                        <i class="fas fa-city"></i>
                        <h3>50+</h3>
                        <p>Cities Covered</p>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-item">
                        <i class="fas fa-handshake"></i>
                        <h3>2000+</h3>
                        <p>Successful Rentals</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Our Values -->
        <section class="mb-5">
            <h2 class="text-center mb-4" data-aos="fade-up">Our Values</h2>
            <div class="row">
                <div class="col-md-4" data-aos="fade-up">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                            <h4>Trust & Security</h4>
                            <p>We prioritize the safety and security of our users, implementing robust verification processes and secure transactions.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-hand-holding-heart fa-3x text-primary mb-3"></i>
                            <h4>User-Centric</h4>
                            <p>Our platform is designed with our users in mind, ensuring a seamless and enjoyable experience for both landlords and tenants.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-sync fa-3x text-primary mb-3"></i>
                            <h4>Innovation</h4>
                            <p>We continuously evolve our platform, incorporating the latest technologies and features to improve the rental experience.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
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
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> House Rental Management System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
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
    </script>
</body>
</html>
