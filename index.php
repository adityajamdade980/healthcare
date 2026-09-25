<?php
session_start();
include 'functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Google tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-75JRZ63L1J"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-75JRZ63L1J');
  </script>

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Health Care and Medicine Guide</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    /* All your CSS (unchanged) */
    /* ✅ Your original CSS is fine, no syntax issues */
  </style>
</head>
<body>
  <!-- Header -->
  <header class="main-header">
    <div class="header-container">
      <a href="index.php" class="logo">
        <div class="logo-icon">
          <i class="fas fa-heartbeat"></i>
        </div>
        <h1>HealthCare Guide</h1>
      </a>

      <nav>
        <ul class="nav-links">
          <li><a href="index.php" class="active"><i class="fas fa-home"></i> Home</a></li>
          <li><a href="features1.php"><i class="fas fa-star"></i> Features</a></li>
          <li><a href="about.html"><i class="fas fa-info-circle"></i> About Us</a></li>

          <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a></li>
            <?php if (function_exists('isAdmin') && isAdmin()): ?>
              <li><a href="admin_dashboard.php"><i class="fas fa-cog"></i> Admin Panel</a></li>
            <?php endif; ?>
          <?php else: ?>
            <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Log In</a></li>
          <?php endif; ?>
        </ul>

        <div class="hamburger">
          <div class="bar"></div>
          <div class="bar"></div>
          <div class="bar"></div>
        </div>
      </nav>
    </div>
  </header>

  <!-- Hero Section -->
  <section class="hero">
    <div class="hero-content">
      <h2>Your Personal Health Companion</h2>
      <p>Empowering you with knowledge, tools, and resources to take control of your health journey. Get personalized insights, medication guidance, and health tips all in one place.</p>
    </div>
  </section>

  <!-- Features Section -->
  <section class="features">
    <div class="section-title">
      <h2>Our Services</h2>
    </div>

    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-book-medical"></i>
        </div>
        <div class="feature-content">
          <h3>Health Articles</h3>
          <p>Explore health tips and medical articles to stay informed and lead a healthy life.</p>
          <a href="healtharticles.php" class="feature-link">
            Explore Articles <i class="fas fa-arrow-right"></i>
          </a>
        </div>
      </div>

      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-heartbeat"></i>
        </div>
        <div class="feature-content">
          <h3>Health Score Checker</h3>
          <p>Calculate your health score and receive personalized recommendations.</p>
          <a href="healthscorechecker.html" class="feature-link">
            Check Your Health <i class="fas fa-arrow-right"></i>
          </a>
        </div>
      </div>

      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-pills"></i>
        </div>
        <div class="feature-content">
          <h3>Medication Guide</h3>
          <p>Find information on medications, side effects, and precautions.</p>
          <a href="medicationguide1.php" class="feature-link">
            Browse Medications <i class="fas fa-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- About Section -->
  <section class="about">
    <div class="about-container">
      <div class="about-content">
        <h2>About HealthCare Guide</h2>
        <p>Welcome to HealthCare Guide – your trusted partner in health and wellness.</p>
        <p>We provide reliable, up-to-date health information and tools to help you make informed decisions.</p>
        <p>Our mission is to empower individuals with accessible and actionable healthcare knowledge.</p>
      </div>
      <div class="about-image">
        <!-- ✅ Fixed broken image path -->
        <img src="images/about.jpg" alt="Healthcare professionals" />
      </div>
    </div>
  </section>

  <!-- Stats Section -->
  <section class="stats">
    <div class="stats-container">
      <div class="stat-item">
        <div class="stat-value">10K+</div>
        <div class="stat-label">Health Articles</div>
      </div>
      <div class="stat-item">
        <div class="stat-value">500+</div>
        <div class="stat-label">Medications Covered</div>
      </div>
      <div class="stat-item">
        <div class="stat-value">95%</div>
        <div class="stat-label">User Satisfaction</div>
      </div>
      <div class="stat-item">
        <div class="stat-value">24/7</div>
        <div class="stat-label">Support Available</div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer>
    <div class="footer-container">
      <div class="footer-col">
        <h3>HealthCare Guide</h3>
        <p>Your trusted partner in health and wellness. We provide reliable medical information and resources.</p>
      </div>

      <div class="footer-col">
        <h3>Quick Links</h3>
        <ul>
          <li><a href="index.php"><i class="fas fa-chevron-right"></i> Home</a></li>
          <li><a href="features1.php"><i class="fas fa-chevron-right"></i> Features</a></li>
          <li><a href="healtharticles.php"><i class="fas fa-chevron-right"></i> Health Articles</a></li>
          <li><a href="healthscorechecker.html"><i class="fas fa-chevron-right"></i> Health Score</a></li>
          <li><a href="medicationguide1.php"><i class="fas fa-chevron-right"></i> Medication Guide</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h3>Contact Us</h3>
        <ul>
          <li><i class="fas fa-phone"></i> +91 7758006946</li>
          <li><i class="fas fa-envelope"></i> healthcareguide@gmail.com</li>
          <li><i class="fas fa-clock"></i> Mon-Fri: 9AM-5PM</li>
        </ul>
      </div>

      <div class="footer-col">
        <h3>Newsletter</h3>
        <p>Subscribe to our newsletter for the latest updates.</p>
        <form class="newsletter-form">
          <input type="email" placeholder="Your Email Address" required>
          <button type="submit">Subscribe</button>
        </form>
      </div>
    </div>

    <div class="copyright">
      <p>&copy; <?php echo date('Y'); ?> HealthCare Guide. All rights reserved.</p>
    </div>
  </footer>

  <script>
    // Mobile Navigation
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');

    hamburger.addEventListener('click', () => {
      navLinks.classList.toggle('active');
      hamburger.classList.toggle('active');
    });

    document.querySelectorAll('.nav-links a').forEach(link => {
      link.addEventListener('click', () => {
        navLinks.classList.remove('active');
        hamburger.classList.remove('active');
      });
    });
  </script>
</body>
</html>
