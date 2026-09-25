<?php
session_start();
// Assuming functions.php is in the same directory as dashboard.php
include 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}
// Optional: If you want to prevent admins from accessing this specific dashboard (e.g., if admin has their own adminpage.php)
// if (isAdmin()) {
//     header("Location: adminpage.php");
//     exit();
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Health Care Guide</title>
    <style>
        /* Define color variables */
        :root {
            --primary: #007bff;
            --secondary: #0056b3;
            --white: #ffffff;
            --bg-light: #f8f9fa;
            --text-dark: #333;
            --text-medium: #666;
        }

        /* General Resets and Base Styling */
        body {
            font-family: Arial, sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            line-height: 1.6;
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

        /* --- Dashboard Specific Styles --- */
        .dashboard-container {
            flex-grow: 1; /* Allows it to take available height */
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .content-card {
            background-color: var(--white);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 600px;
            width: 100%;
            margin: 20px auto;
        }

        .content-card h1 {
            color: var(--primary);
            margin-bottom: 25px;
            font-size: 2.5em;
        }

        .content-card p {
            font-size: 1.1em;
            color: var(--text-medium);
            margin-bottom: 20px;
        }

        .dashboard-links a {
            display: inline-block;
            background-color: var(--primary);
            color: var(--white);
            padding: 12px 25px;
            border-radius: 25px;
            text-decoration: none;
            margin: 10px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            font-weight: bold;
        }

        .dashboard-links a:hover {
            background-color: var(--secondary);
            transform: translateY(-3px);
        }

        .dashboard-links a.admin-link {
            background-color: #e67e22; /* Admin button color */
        }

        .dashboard-links a.admin-link:hover {
            background-color: #d35400;
        }

        .dashboard-links a.logout-link {
            background-color: #dc3545; /* Logout button color */
        }

        .dashboard-links a.logout-link:hover {
            background-color: #c82333;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
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

            .content-card {
                padding: 30px 20px;
                margin: 15px auto;
            }
            .content-card h1 {
                font-size: 2em;
            }
            .content-card p {
                font-size: 1em;
            }
            .dashboard-links a {
                padding: 10px 20px;
                font-size: 0.95em;
                margin: 8px;
            }
        }

        @media (max-width: 480px) {
            .main-header {
                padding: 0.8rem 1rem;
            }
            .logo {
                font-size: 1.5rem;
            }
            .content-card {
                padding: 25px 15px;
                border-radius: 8px;
            }
            .content-card h1 {
                font-size: 1.8em;
            }
            .content-card p {
                font-size: 0.9em;
            }
            .dashboard-links a {
                display: block; /* Stack buttons vertically */
                width: 90%;
                margin: 10px auto;
                padding: 12px 15px;
                font-size: 1em;
            }
        }
    </style>
</head>
<body>
    <header class="main-header">
        <nav class="navbar">
            <div class="logo">Health Care Guide</div>
            <ul class="nav-links">
                <li><a href="../index.html">Home</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
                <?php if (isAdmin()): ?>
                    <li><a href="adminpage.php">Admin Panel</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Logout</a></li>
            </ul>
            <div class="hamburger">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </nav>
    </header>

    <div class="dashboard-container">
        <div class="content-card">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
            <p>Your Health ID: <?php echo htmlspecialchars($_SESSION['user_id']); ?></p>
            <p>Your Role: <?php echo htmlspecialchars($_SESSION['role']); ?></p>

            <div class="dashboard-links">
                <a href="../index.html">Go to Homepage</a>
                <?php if (isAdmin()): ?>
                    <a href="adminpage.php" class="admin-link">Go to Admin Panel</a>
                <?php endif; ?>
                <a href="logout.php" class="logout-link">Logout</a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
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