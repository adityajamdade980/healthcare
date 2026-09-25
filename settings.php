<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verify admin privileges
if (!isLoggedIn() || !isAdmin()) {
    header("Location: login"); // Redirect to clean URL
    exit();
}

$error = '';
$success = '';
$settings = []; // Array to hold all fetched settings

// Fetch existing settings from the database
try {
    $result = $user_conn->query("SELECT setting_key, setting_value, description FROM settings");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = [
                'value' => htmlspecialchars($row['setting_value']),
                'description' => htmlspecialchars($row['description'])
            ];
        }
    } else {
        throw new Exception("Error fetching settings: " . $user_conn->error);
    }
} catch (Exception $e) {
    $error = "Error loading settings: " . $e->getMessage();
}

// Handle form submission (updating settings)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    try {
        // Prepare statement for updating settings
        $stmt_update = $user_conn->prepare("UPDATE settings SET setting_value = ?, last_updated = CURRENT_TIMESTAMP WHERE setting_key = ?");
        
        // Loop through submitted form data to update settings
        foreach ($_POST as $key => $value) {
            // Only process keys that are actual settings (and not the submit button)
            if (array_key_exists($key, $settings)) {
                $sanitized_value = htmlspecialchars(trim($value));
                $stmt_update->bind_param("ss", $sanitized_value, $key);
                if (!$stmt_update->execute()) {
                    throw new Exception("Failed to update setting '{$key}': " . $stmt_update->error);
                }
            }
        }
        $stmt_update->close();

        $_SESSION['success_message'] = "Settings updated successfully!";
        header("Location: settings"); // Redirect to clean URL
        exit();

    } catch (Exception $e) {
        $error = "Error saving settings: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Panel</title>
    <style>
        /* Define color variables */
        :root {
            --primary: #007bff;
            --secondary: #0056b3;
            --white: #ffffff;
            --bg-light: #f8f9fa;
            --text-dark: #333;
            --text-medium: #666;
            --admin-bg: #333; /* Dark background for admin panel */
            --admin-card-bg: #444; /* Darker card background */
            --admin-text-light: #e0e0e0;
            --admin-border: #666;
        }

        /* General Resets and Base Styling */
        body {
            background: var(--admin-bg);
            color: var(--admin-text-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* --- Main Header (Consistent Across Site) --- */
        .main-header {
            background-color: var(--primary);
            padding: 1rem 2rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--white);
            flex-wrap: wrap;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--white);
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
            color: var(--white);
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

        /* --- Admin Dashboard Specific Layout --- */
        .admin-dashboard-container {
            display: flex;
            flex-grow: 1; /* Allows it to take available height */
        }

        .admin-sidebar {
            width: 250px;
            background-color: #2a2a2a;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.2);
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .admin-sidebar h2 {
            color: var(--primary);
            font-size: 1.5rem;
            margin-bottom: 20px;
        }

        .admin-sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
            width: 100%;
        }

        .admin-sidebar ul li {
            margin-bottom: 10px;
        }

        .admin-sidebar ul li a {
            display: block;
            padding: 10px 15px;
            color: var(--admin-text-light);
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s, color 0.3s;
        }

        .admin-sidebar ul li a:hover,
        .admin-sidebar ul li a.active {
            background-color: #007bff;
            color: var(--white);
        }

        .admin-sidebar .logout-btn-sidebar {
            margin-top: auto; /* Push to bottom */
            width: 100%;
            text-align: center;
            background-color: #dc3545;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .admin-sidebar .logout-btn-sidebar:hover {
            background-color: #c82333;
        }


        .admin-content {
            flex-grow: 1;
            padding: 30px;
            background: var(--admin-bg);
        }

        .admin-content h1 {
            color: var(--white);
            border-bottom: 2px solid var(--admin-border);
            padding-bottom: 15px;
            margin-bottom: 30px;
        }

        /* --- Settings Form Specific Styles --- */
        .settings-form-container {
            background: var(--admin-card-bg);
            padding: 30px;
            margin: 2rem auto; /* Center like other admin forms */
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
            max-width: 800px; /* Max width for content */
        }

        .settings-form-container h2 {
            color: var(--white);
            border-bottom: 1px solid var(--admin-border);
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.8rem;
            color: #ccc;
            font-weight: 500;
        }

        input[type="text"],
        input[type="email"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 12px;
            background: #555;
            border: 1px solid #666;
            border-radius: 4px;
            color: #fff;
            font-size: 16px;
            transition: all 0.3s ease;
            box-sizing: border-box; /* Include padding in width */
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #777;
            background: #5a5a5a;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        /* Specific style for boolean (checkbox/select) */
        .form-group.checkbox-group label {
            display: inline-block;
            margin-left: 10px;
            vertical-align: middle;
        }
        .form-group.checkbox-group input[type="checkbox"] {
            width: auto;
            vertical-align: middle;
        }


        button[type="submit"] {
            background: #28a745;
            color: white;
            padding: 14px 28px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background 0.3s ease, transform 0.2s ease;
            margin-top: 2rem;
            float: right; /* Align to right on desktop */
        }

        button[type="submit"]:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        /* --- Message Styles --- */
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
        }

        .success {
            background: #155724; /* Darker green */
            color: #d4edda;
            border: 1px solid #28a745;
        }

        .error {
            background: #721c24; /* Darker red */
            color: #f8d7da;
            border: 1px solid #f5c6cb;
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .admin-sidebar {
                width: 200px;
            }
            .admin-content {
                padding: 20px;
            }
            .settings-form-container {
                padding: 25px;
                margin: 1.5rem auto;
            }
        }

        @media (max-width: 768px) {
            /* Navbar specific adjustments for mobile */
            .main-header {
                padding: 1rem 1.5rem;
            }
            .nav-links {
                display: none;
                flex-direction: column;
                width: 100%;
                position: absolute;
                top: 70px;
                left: 0;
                background-color: var(--primary);
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

            /* Admin dashboard layout change for mobile */
            .admin-dashboard-container {
                flex-direction: column;
            }
            .admin-sidebar {
                width: 100%;
                height: auto;
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
                padding: 15px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            .admin-sidebar h2 {
                display: none;
            }
            .admin-sidebar ul {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
                margin-bottom: 10px;
            }
            .admin-sidebar ul li {
                margin-bottom: 0;
            }
            .admin-sidebar ul li a {
                padding: 8px 12px;
                font-size: 0.9em;
            }
            .admin-sidebar .logout-btn-sidebar {
                margin-top: 10px;
                width: auto;
                min-width: 120px;
            }

            .admin-content {
                padding: 15px;
            }
            .admin-content h1 {
                font-size: 2em;
                margin-bottom: 20px;
            }
            .settings-form-container {
                padding: 20px;
                margin: 1rem auto;
            }
            .settings-form-container h2 {
                font-size: 1.5rem;
            }
            .form-group input, .form-group select, .form-group textarea {
                font-size: 15px;
                padding: 10px;
            }
            button[type="submit"] {
                width: 100%;
                float: none;
            }
        }

        @media (max-width: 480px) {
            .main-header {
                padding: 0.8rem 1rem;
            }
            .logo {
                font-size: 1.5rem;
            }
            .admin-sidebar ul {
                flex-direction: column;
                align-items: stretch;
                gap: 5px;
            }
            .admin-sidebar ul li a {
                padding: 10px;
            }
            .admin-sidebar .logout-btn-sidebar {
                width: 100%;
            }

            .admin-content {
                padding: 10px;
            }
            .admin-content h1 {
                font-size: 1.8em;
            }
            .settings-form-container {
                padding: 15px;
                margin: 0.5rem auto;
            }
            .settings-form-container h2 {
                font-size: 1.3rem;
            }
            label {
                font-size: 0.9em;
            }
            input, select, textarea {
                padding: 10px;
                font-size: 15px;
            }
            button[type="submit"] {
                padding: 12px 20px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <header class="main-header">
        <nav class="navbar">
            <div class="logo">Admin Panel</div>
            <ul class="nav-links">
                <li><a href="admin_dashboard">Dashboard</a></li>
                <li><a href="create_article">Create Article</a></li>
                <li><a href="manage-articles">Manage Articles</a></li>
                <li><a href="settings" class="active">Settings</a></li> <li><a href="logout">Logout</a></li>
            </ul>
            <div class="hamburger">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </nav>
    </header>

    <div class="admin-dashboard-container">
        <aside class="admin-sidebar">
            <h2>Admin Menu</h2>
            <ul>
                <li><a href="admin_dashboard">Dashboard</a></li>
                <li><a href="create_article">Create Article</a></li>
                <li><a href="manage-articles">Manage Articles</a></li>
                <li><a href="#">Manage Users</a></li> <li><a href="settings" class="active">Settings</a></li>
            </ul>
            <a href="logout" class="logout-btn-sidebar">Logout</a>
        </aside>

        <main class="admin-content">
            <div class="settings-form-container">
                <h1>Website Settings</h1>

                <?php if ($error): ?>
                    <div class="message error"><?= $error ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="message success"><?= $success ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="update_settings" value="1">

                    <div class="form-group">
                        <label for="site_title">Site Title:</label>
                        <input type="text" id="site_title" name="site_title" value="<?= $settings['site_title']['value'] ?? '' ?>">
                        <p style="font-size:0.8em; color:#bbb; margin-top:5px;"><?= $settings['site_title']['description'] ?? '' ?></p>
                    </div>

                    <div class="form-group">
                        <label for="site_tagline">Site Tagline:</label>
                        <textarea id="site_tagline" name="site_tagline" rows="2"><?= $settings['site_tagline']['value'] ?? '' ?></textarea>
                        <p style="font-size:0.8em; color:#bbb; margin-top:5px;"><?= $settings['site_tagline']['description'] ?? '' ?></p>
                    </div>

                    <div class="form-group">
                        <label for="contact_email">Contact Email:</label>
                        <input type="email" id="contact_email" name="contact_email" value="<?= $settings['contact_email']['value'] ?? '' ?>">
                        <p style="font-size:0.8em; color:#bbb; margin-top:5px;"><?= $settings['contact_email']['description'] ?? '' ?></p>
                    </div>

                    <div class="form-group">
                        <label for="articles_per_page">Articles Per Page (Listings):</label>
                        <input type="number" id="articles_per_page" name="articles_per_page" value="<?= $settings['articles_per_page']['value'] ?? '10' ?>" min="1" step="1">
                        <p style="font-size:0.8em; color:#bbb; margin-top:5px;"><?= $settings['articles_per_page']['description'] ?? '' ?></p>
                    </div>

                    <div class="form-group">
                        <label>Enable User Registration:</label>
                        <select name="enable_user_registration">
                            <option value="1" <?= (($settings['enable_user_registration']['value'] ?? '1') == '1') ? 'selected' : '' ?>>Yes</option>
                            <option value="0" <?= (($settings['enable_user_registration']['value'] ?? '1') == '0') ? 'selected' : '' ?>>No</option>
                        </select>
                        <p style="font-size:0.8em; color:#bbb; margin-top:5px;"><?= $settings['enable_user_registration']['description'] ?? '' ?></p>
                    </div>

                    <button type="submit">Save Settings</button>
                </form>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Hamburger menu functionality (consistent)
            const hamburger = document.querySelector(".hamburger");
            const navLinks = document.querySelector(".nav-links");

            if (hamburger && navLinks) {
                hamburger.addEventListener("click", () => {
                    navLinks.classList.toggle("nav-active");
                    hamburger.classList.toggle("toggle");
                });
                document.querySelectorAll(".nav-links li a").forEach(link => {
                    link.addEventListener("click", () => {
                        navLinks.classList.remove("nav-active");
                        hamburger.classList.remove("toggle");
                    });
                });
            }

            // Prevent form submission on Enter key press in input fields
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('keydown', function(event) {
                    if (event.keyCode === 13) {
                        const targetTagName = event.target.tagName.toLowerCase();
                        const targetType = event.target.type ? event.target.type.toLowerCase() : '';

                        if (targetTagName === 'input' && targetType !== 'submit' && targetType !== 'file') {
                            event.preventDefault();
                            const formElements = Array.from(form.querySelectorAll('input:not([type="hidden"]), select, textarea, button[type="submit"]'));
                            const currentIndex = formElements.indexOf(event.target);
                            if (currentIndex > -1 && currentIndex < formElements.length - 1) {
                                formElements[currentIndex + 1].focus();
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>