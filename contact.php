<?php
session_start();
// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Adjust these paths based on where PHPMailer is located relative to your contact.php
// Assuming PHPMailer-6.9.3 is directly in your 'backend' folder
require __DIR__ . '/PHPMailer-6.9.3/src/Exception.php';
require __DIR__ . '/PHPMailer-6.9.3/src/PHPMailer.php'; // Corrected filename
require __DIR__ . '/PHPMailer-6.9.3/src/SMTP.php';

$message_status = ''; // Variable to hold success/error messages

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $message = htmlspecialchars(trim($_POST['message']));

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        // IMPORTANT: Replace with your actual Gmail address and App Password
        // If 2FA is enabled on your Gmail, you MUST use an App Password here.
        // See: https://support.google.com/accounts/answer/185833
        $mail->Username = 'your-email@gmail.com'; // YOUR GMAIL ADDRESS
        $mail->Password = 'your-app-password';   // YOUR GMAIL APP PASSWORD OR REGULAR PASSWORD
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use ENCRYPTION_SMTPS for port 465
        $mail->Port = 587; // Use 465 for ENCRYPTION_SMTPS

        // Recipients
        $mail->setFrom($email, $name);
        $mail->addAddress('magicgames374@gmail.com'); // Your website's contact email

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'New Contact Form Submission from Health Care Guide';
        $mail->Body    = "
            <h3>New Contact Message</h3>
            <p><strong>Name:</strong> {$name}</p>
            <p><strong>Email:</strong> {$email}</p>
            <p><strong>Message:</strong></p>
            <p>{$message}</p>
        ";
        $mail->AltBody = "Name: {$name}\nEmail: {$email}\nMessage:\n{$message}"; // Plain text for non-HTML clients

        $mail->send();
        $message_status = '<div class="status-message success">Message sent successfully! We will get back to you soon.</div>';
    } catch (Exception $e) {
        $message_status = '<div class="status-message error">Failed to send message. Please try again later. Mailer Error: ' . $mail->ErrorInfo . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Contact Us - Health Care & Medicine Guide</title>
    <style>
        /* General Resets and Base Styles */
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa; /* Lighter background */
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Header Section (consistent with other pages) */
        .main-header {
            background-color: #007bff;
            padding: 1rem 2rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #ffffff;
            flex-wrap: wrap;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: #ffffff;
        }

        .nav-links {
            list-style: none;
            display: flex;
            gap: 2rem;
            margin: 0;
            padding: 0;
            transition: all 0.3s ease-in-out;
        }

        .nav-links a {
            color: #ffffff;
            text-decoration: none;
            transition: color 0.3s;
            font-weight: 500;
            padding: 0.5rem 0;
        }

        .nav-links a:hover {
            color: #cceeff;
        }

        /* Hamburger Menu Styles */
        .hamburger {
            display: none;
            cursor: pointer;
            flex-direction: column;
            justify-content: space-around;
            width: 30px;
            height: 25px;
            z-index: 1000;
        }

        .hamburger .bar {
            width: 100%;
            height: 3px;
            background-color: white;
            transition: all 0.3s ease-in-out;
        }

        .hamburger.toggle .bar:nth-child(1) {
            transform: translateY(11px) rotate(45deg);
        }
        .hamburger.toggle .bar:nth-child(2) {
            opacity: 0;
        }
        .hamburger.toggle .bar:nth-child(3) {
            transform: translateY(-11px) rotate(-45deg);
        }

        /* Main Container */
        .container {
            max-width: 900px; /* Wider container */
            margin: 30px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            flex: 1;
        }

        /* Headings */
        h1 {
            text-align: center;
            font-size: 2.8em;
            margin-bottom: 25px;
            color: #0056b3;
        }

        h2 {
            font-size: 2.2em;
            margin-top: 30px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
            color: #007bff;
        }

        /* Contact Info Section */
        .contact-section {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            margin-top: 30px;
            justify-content: center;
        }

        .contact-info-block {
            flex: 1 1 300px; /* Allows blocks to grow/shrink and wrap */
            background-color: #eaf6ff;
            padding: 25px;
            border-radius: 10px;
            border: 1px solid #cceeff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .contact-info-block h3 {
            color: #007bff;
            font-size: 1.5em;
            margin-bottom: 15px;
        }

        .contact-item {
            margin: 10px 0;
            font-size: 1.1em;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .contact-item strong {
            color: #0056b3;
            margin-right: 8px;
        }

        .contact-item a {
            color: #007bff;
            text-decoration: none;
            transition: color 0.3s;
        }

        .contact-item a:hover {
            text-decoration: underline;
            color: #0056b3;
        }

        .contact-icon {
            margin-right: 10px;
            font-size: 1.3em;
            color: #007bff;
        }

        /* Contact Form */
        .contact-form-section {
            margin-top: 40px;
        }

        .contact-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            padding: 25px;
            background-color: #f0f8ff;
            border-radius: 10px;
            border: 1px solid #a7d9ff;
        }

        .form-group {
            margin-bottom: 10px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #444;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1em;
            transition: border-color 0.3s;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="email"]:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .submit-btn {
            background-color: #28a745; /* Green submit button */
            color: white;
            padding: 14px 25px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: bold;
            transition: background-color 0.3s, transform 0.2s;
            align-self: flex-start; /* Align button to left */
            margin-top: 10px;
        }

        .submit-btn:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        /* Status Messages */
        .status-message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
            text-align: center;
        }

        .status-message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 20px 10px;
            background-color: #007bff;
            color: white;
            margin-top: auto;
            font-size: 0.95em;
        }

        footer p {
            margin: 5px 0;
            color: white;
        }

        footer a {
            color: white;
            text-decoration: underline;
            transition: color 0.3s;
        }

        footer a:hover {
            color: #cceeff;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                padding: 1rem 1.5rem;
            }

            .nav-links {
                display: none;
                flex-direction: column;
                width: 100%;
                position: absolute;
                top: 70px;
                left: 0;
                background-color: #007bff;
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
                z-index: 999;
                padding: 10px 0;
                opacity: 0;
                transform: translateY(-20px);
            }

            .nav-links.nav-active {
                display: flex;
                opacity: 1;
                transform: translateY(0);
            }

            .nav-links li {
                width: 100%;
                text-align: center;
            }

            .nav-links a {
                padding: 15px;
                display: block;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }

            .hamburger {
                display: flex;
            }

            .container {
                margin: 15px;
                padding: 20px;
                border-radius: 8px;
            }

            h1 {
                font-size: 2.2em;
                margin-bottom: 20px;
            }
            h2 {
                font-size: 1.8em;
                margin-top: 25px;
            }

            .contact-section {
                flex-direction: column;
                gap: 20px;
            }

            .contact-info-block {
                flex: none; /* Reset flex-grow/shrink */
                width: 100%;
                padding: 20px;
            }

            .contact-item {
                font-size: 1em;
                flex-direction: column; /* Stack label and value */
                align-items: flex-start; /* Align left */
            }
            .contact-item strong {
                margin-bottom: 5px;
            }

            .contact-form {
                padding: 20px;
            }

            .submit-btn {
                width: 100%; /* Full width button */
                align-self: center; /* Center button */
            }
        }

        @media (max-width: 480px) {
            .main-header {
                padding: 0.8rem 1rem;
            }

            .logo {
                font-size: 1.5rem;
            }

            .container {
                margin: 10px;
                padding: 15px;
                border-radius: 5px;
            }

            h1 {
                font-size: 1.8em;
                margin-bottom: 15px;
            }
            h2 {
                font-size: 1.5em;
                margin-top: 20px;
                padding-bottom: 8px;
            }

            .contact-info-block h3 {
                font-size: 1.3em;
            }
            .contact-item {
                font-size: 0.95em;
            }
            .contact-icon {
                font-size: 1.1em;
            }

            .contact-form {
                padding: 15px;
            }

            .form-group label {
                font-size: 0.95em;
            }

            .form-group input,
            .form-group textarea {
                padding: 10px;
                font-size: 0.9em;
            }

            .submit-btn {
                padding: 12px 20px;
                font-size: 1em;
            }

            .status-message {
                font-size: 0.9em;
                padding: 10px;
            }

            footer {
                padding: 15px 10px;
                font-size: 0.85em;
            }
        }
    </style>
</head>
<body>
    <header class="main-header">
        <nav class="navbar">
            <div class="logo">Health Care Guide</div>
            <ul class="nav-links">
                <li><a href="index.html">Home</a></li>
                <li><a href="features1.html">Features</a></li>
                <li><a href="contact.php">Contact</a></li> <li><a href="login.php">Log In</a></li>
            </ul>
            <div class="hamburger">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </nav>
    </header>

    <div class="container">
        <h1>Contact Us</h1>
        <p style="text-align: center; font-size: 1.1em; color: #555;">
            We'd love to hear from you! Please feel free to reach out with any questions, feedback, or inquiries.
        </p>

        <?php echo $message_status; // Display message status here ?>

        <div class="contact-section">
            <div class="contact-info-block">
                <h3>Our Details</h3>
                <div class="contact-item">
                    <span class="contact-icon">👤</span> <strong>Names:</strong> Aditya Jamdade
                </div>
                <div class="contact-item">
                    <span class="contact-icon">📧</span> <strong>Email:</strong> <a href="mailto:jamdadeaditya980@gmail.com">jamdadeaditya980@gmail.com</a>
                </div>
                <div class="contact-item">
                    <span class="contact-icon">📞</span> <strong>Phones:</strong>
                    <a href="tel:+9325008902">+91 9325008902</a> /
                    <a href="tel:"></a>
                </div>
            </div>

            <div class="contact-info-block">
                <h3>Working Hours</h3>
                <div class="contact-item">
                    <span class="contact-icon">⏰</span> <strong>Monday - Friday:</strong> 9:00 AM - 6:00 PM (IST)
                </div>
                <div class="contact-item">
                    <span class="contact-icon">📅</span> <strong>Weekends:</strong> Closed
                </div>
            </div>
        </div>

        <section class="contact-form-section">
            <h2>Send Us a Message</h2>
            <form action="contact.php" method="POST" class="contact-form">
                <div class="form-group">
                    <label for="name">Your Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Your Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="message">Your Message:</label>
                    <textarea id="message" name="message" required></textarea>
                </div>
                <button type="submit" class="submit-btn">Send Message</button>
            </form>
        </section>
    </div>

    <footer>
        <p>&copy; 2025 Health Care Guide. All rights reserved.</p>
        <p>Contact Us: <a href="mailto:magicgames374@gmail.com">magicgames374@gmail.com</a></p>
    </footer

    <script>
        // Hamburger Menu Toggle
        const hamburger = document.querySelector(".hamburger");
        const navLinks = document.querySelector(".nav-links");

        hamburger.addEventListener("click", () => {
            navLinks.classList.toggle("nav-active");
            hamburger.classList.toggle("toggle");
        });

        // Close nav when a link is clicked (optional)
        document.querySelectorAll(".nav-links li a").forEach((link) => {
            link.addEventListener("click", () => {
                navLinks.classList.remove("nav-active");
                hamburger.classList.remove("toggle");
            });
        });
    </script>
</body>
</html>