<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - House Rental Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
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

        .hero {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)),
                        url('https://images.unsplash.com/photo-1423666639041-f56000c27a9a?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            margin-bottom: 50px;
        }

        .contact-info-card {
            transition: transform 0.3s ease;
            margin-bottom: 20px;
        }
        .contact-info-card:hover {
            transform: translateY(-5px);
        }
        .contact-icon {
            font-size: 2rem;
            color: #3498db;
            margin-bottom: 15px;
        }

        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .map-container {
            height: 400px;
            width: 100%;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .social-links a {
            display: inline-block;
            width: 40px;
            height: 40px;
            line-height: 40px;
            text-align: center;
            border-radius: 50%;
            background-color: #3498db;
            color: white;
            margin: 0 10px;
            transition: transform 0.3s ease;
        }
        .social-links a:hover {
            transform: translateY(-3px);
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero text-center">
        <div class="container">
            <h1 class="display-4 mb-4" data-aos="fade-up">Contact Us</h1>
            <p class="lead" data-aos="fade-up" data-aos-delay="100">
                Have questions? We'd love to hear from you. Send us a message and we'll respond as soon as possible.
            </p>
        </div>
    </section>

    <!-- Contact Info Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-md-4" data-aos="fade-up">
                    <div class="card contact-info-card">
                        <div class="card-body text-center">
                            <i class="fas fa-map-marker-alt contact-icon"></i>
                            <h5 class="card-title">Address</h5>
                            <p class="card-text">123 Main Street<br>Mumbai, Maharashtra<br>India</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="card contact-info-card">
                        <div class="card-body text-center">
                            <i class="fas fa-phone contact-icon"></i>
                            <h5 class="card-title">Phone</h5>
                            <p class="card-text">+91 123 456 7890<br>+91 987 654 3210</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="card contact-info-card">
                        <div class="card-body text-center">
                            <i class="fas fa-envelope contact-icon"></i>
                            <h5 class="card-title">Email</h5>
                            <p class="card-text">info@hrms.com<br>support@hrms.com</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Contact Form -->
            <div class="col-lg-6 mb-4" data-aos="fade-right">
                <h2 class="mb-4">Send us a Message</h2>
                <form id="contactForm" action="process_contact.php" method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">Your Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Send Message
                    </button>
                </form>
            </div>

            <!-- Map -->
            <div class="col-lg-6" data-aos="fade-left">
                <h2 class="mb-4">Find Us</h2>
                <div class="map-container">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d241317.11609823277!2d72.74109995646221!3d19.08219783958221!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3be7c6306644edc1%3A0x5da4ed8f8d648c69!2sMumbai%2C%20Maharashtra!5e0!3m2!1sen!2sin!4v1680241195884!5m2!1sen!2sin"
                        width="100%" 
                        height="100%" 
                        style="border:0;" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>
        </div>

        <!-- Social Media Links -->
        <div class="text-center mt-5" data-aos="fade-up">
            <h3 class="mb-4">Connect With Us</h3>
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-linkedin-in"></i></a>
            </div>
        </div>
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


    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="successModalLabel">
                        <i class="fas fa-check-circle me-2"></i>Success
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="fas fa-envelope-open-text text-success mb-3" style="font-size: 3rem;"></i>
                    <h4 class="mb-3">Thank You!</h4>
                    <p class="mb-0">Your message has been sent successfully. We will get back to you soon!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });

        // Show success modal if success message exists
        <?php if (isset($_SESSION['success'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var successModal = new bootstrap.Modal(document.getElementById('successModal'));
                successModal.show();
                <?php unset($_SESSION['success']); ?>
            });
        <?php endif; ?>
    </script>
</body>
</html>
