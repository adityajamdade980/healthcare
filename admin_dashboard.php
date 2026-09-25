<?php
session_start();
// IMPORTANT: Adjust these paths based on the actual location of your files.
// Assuming admin_dashboard.php is in public_html/, and db.php/functions.php are also in public_html/
require_once 'functions.php'; // Contains isLoggedIn() and isAdmin()
require_once 'db.php';       // Contains $health_db and $medicine_db

// Enable error reporting for development (remove/disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);


header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Verify admin privileges
if (!isLoggedIn() || !isAdmin()) {
    header("Location: login"); // Redirect to clean URL for login page
    exit();
}

$error = '';
$success = '';

// Check for success/error messages from previous redirects (e.g., after publish from create_article)
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}


// --- Fetch Dashboard Stats ---
$total_health_articles = 0;
$total_medicine_articles = 0;
$recent_articles = [];

try {
    // Count total health articles
    $result_health_count = $health_db->query("SELECT COUNT(*) AS total FROM health_articles");
    if ($result_health_count) {
        $total_health_articles = $result_health_count->fetch_assoc()['total'];
    }

    // Count total medicine articles
    $result_medicine_count = $medicine_db->query("SELECT COUNT(*) AS total FROM medicine_articles");
    if ($result_medicine_count) {
        $total_medicine_articles = $result_medicine_count->fetch_assoc()['total'];
    }

    // Fetch recent articles (e.g., last 5, combined)
    // Select health articles
    $health_recent_query = "SELECT id, title, category, 'health' as type, created_at FROM health_articles ORDER BY created_at DESC LIMIT 5";
    $health_recent_result = $health_db->query($health_recent_query);
    if ($health_recent_result) {
        while ($row = $health_recent_result->fetch_assoc()) {
            $recent_articles[] = $row;
        }
    }

    // Select medicine articles
    $medicine_recent_query = "SELECT id, title, category, 'medicine' as type, created_at FROM medicine_articles ORDER BY created_at DESC LIMIT 5";
    $medicine_recent_result = $medicine_db->query($medicine_recent_query);
    if ($medicine_recent_result) {
        while ($row = $medicine_recent_result->fetch_assoc()) {
            $recent_articles[] = $row;
        }
    }

    // Sort combined recent articles by created_at (most recent first)
    usort($recent_articles, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    $recent_articles = array_slice($recent_articles, 0, 5);


} catch (Exception $e) {
    $error = "Error fetching dashboard data: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">
    <html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Overview</title>
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

        /* New Logout Button Style */
        .logout-btn-sidebar,
        .nav-links .logout-button { /* Apply to both sidebar and top nav */
            margin-top: auto; /* Push sidebar button to bottom */
            width: 100%; /* Full width in sidebar */
            text-align: center;
            background-color: #dc3545; /* Red */
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s, transform 0.2s;
            display: block; /* Ensure it behaves like a block for padding/width */
            box-sizing: border-box; /* Include padding/border in width calculation */
            border: none; /* Remove default button border */
            cursor: pointer;
            font-weight: 500;
        }

        .logout-btn-sidebar:hover,
        .nav-links .logout-button:hover {
            background-color: #c82333; /* Darker red on hover */
            transform: translateY(-2px);
        }

        /* Adjust top nav logout button specifically */
        .nav-links .logout-button {
            width: auto; /* Allow auto width for flex item in top nav */
            margin-left: 20px; /* Space from other nav items */
            padding: 10px 15px; /* Consistent with other nav links */
            background-color: #dc3545; /* Red background */
            color: white; /* White text */
            text-decoration: none; /* No underline by default */
            border-radius: 5px;
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

        /* --- Dashboard Overview Specific Styles --- */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .dashboard-card {
            background: var(--admin-card-bg);
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
            text-align: center;
        }

        .dashboard-card h3 {
            color: var(--primary);
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .dashboard-card p {
            font-size: 1.2rem;
            color: var(--admin-text-light);
            margin-top: 5px;
        }
        .dashboard-card p.label {
            font-size: 0.9em;
            color: #aaa;
        }


        .recent-activity {
            background: var(--admin-card-bg);
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
            margin-bottom: 40px; /* Added margin for spacing */
        }

        .recent-activity h2 {
            color: var(--white);
            font-size: 1.8rem;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--admin-border);
            padding-bottom: 10px;
        }

        .recent-activity ul {
            list-style: none;
            padding: 0;
        }

        .recent-activity li {
            padding: 10px 0;
            border-bottom: 1px dashed #555;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
        }

        .recent-activity li:last-child {
            border-bottom: none;
        }

        .recent-activity li span {
            color: var(--admin-text-light);
            font-size: 1em;
        }

        .recent-activity li .article-title-recent {
            color: var(--primary);
            font-weight: bold;
            flex-basis: 100%; /* Take full width on small screens */
            margin-bottom: 5px;
        }
        .recent-activity li .article-info-recent {
            color: #bbb;
            font-size: 0.9em;
            flex-grow: 1; /* Allows it to take available width */
        }
        .recent-activity li .article-date-recent {
            color: #aaa;
            font-size: 0.85em;
            margin-left: 10px;
        }

        /* --- Message Styles (from adminpage) --- */
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
            .dashboard-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
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
            .dashboard-card h3 {
                font-size: 1.5rem;
            }
            .dashboard-card p {
                font-size: 1rem;
            }
            .recent-activity h2 {
                font-size: 1.5rem;
            }
            .quick-links-section h2 {
                font-size: 1.5rem;
            }
            .recent-activity li span {
                font-size: 0.9em;
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
            .dashboard-card {
                padding: 20px;
            }
            .dashboard-card h3 {
                font-size: 1.3rem;
            }
            .dashboard-card p {
                font-size: 0.9em;
            }
            .recent-activity h2 {
                font-size: 1.3rem;
            }
            .quick-links-section h2 {
                display: none; /* Hide Quick Actions title on smallest screens */
            }
            .quick-links-grid {
                grid-template-columns: 1fr; /* Stack links vertically */
                max-width: 250px; /* Constrain width */
            }
            .quick-links-grid a {
                font-size: 0.9em;
            }
            .recent-activity li span {
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>
    <header class="main-header">
        <nav class="navbar">
            <div class="logo">Admin Panel</div>
            <ul class="nav-links">
                <li><a href="admin_dashboard" class="active">Dashboard</a></li>
                <li><a href="create_article">Create Article</a></li>
                <li><a href="manage-articles">Manage Articles</a></li>
                <li><a href="manage_users">Manage Users</a></li> <li><a href="logout" class="logout-button">Logout</a></li> </ul>
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
                <li><a href="admin_dashboard" class="active">Dashboard</a></li>
                <li><a href="create_article">Create Article</a></li>
                <li><a href="manage-articles">Manage Articles</a></li>
                <li><a href="manage_users">Manage Users</a></li>
            </ul>
            <a href="logout" class="logout-btn-sidebar">Logout</a>
        </aside>

        <main class="admin-content">
            <h1>Admin Dashboard Overview</h1>

            <?php if ($error): ?>
                <div class="message error"><?= $error ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="message success"><?= $success ?></div>
            <?php endif; ?>

            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h3>Total Health Articles</h3>
                    <p><?= $total_health_articles ?></p>
                </div>
                <div class="dashboard-card">
                    <h3>Total Medicine Articles</h3>
                    <p><?= $total_medicine_articles ?></p>
                </div>
                <div class="dashboard-card">
                    <h3>Welcome Back!</h3>
                    <p class="label">Logged in as:</p>
                    <p><strong><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong></p>
                </div>
            </div>

            <section class="recent-activity">
                <h2>Recent Articles</h2>
                <?php if (empty($recent_articles)): ?>
                    <p style="text-align: center; color: #aaa;">No recent articles found. Start publishing!</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($recent_articles as $article): ?>
                            <li>
                                <span class="article-title-recent"><?= htmlspecialchars($article['title']) ?></span>
                                <span class="article-info-recent">
                                    Type: <?= ucfirst(htmlspecialchars($article['type'])) ?> | Category: <?= ucfirst(str_replace('_', ' ', htmlspecialchars($article['category']))) ?>
                                </span>
                                <span class="article-date-recent"><?= date('M j, Y H:i', strtotime($article['created_at'])) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

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
                document.querySelectorAll(".nav-links li a").forEach((link) => {
                    link.addEventListener("click", () => {
                        navLinks.classList.remove("nav-active");
                        hamburger.classList.remove("toggle");
                    });
                });
            }
        });
    </script>
</body>
</html>