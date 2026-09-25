<?php
session_start();
// IMPORTANT: Adjust these paths based on the actual location of your files relative to create_article.php
// Assuming create_article.php is in public_html/, and db.php/functions.php are also in public_html/
require_once 'functions.php'; // Contains isLoggedIn() and isAdmin()
require_once 'db.php';       // Contains $health_db and $medicine_db

// Enable error reporting for development (remove/disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verify admin privileges
if (!isLoggedIn() || !isAdmin()) {
    header("Location: login"); // Redirect to clean URL for login page
    exit();
}

// Initialize messages
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $article_type = $_POST['article_type'] ?? '';
    $category = $_POST['category'] ?? '';
    $title = htmlspecialchars(trim($_POST['title'] ?? ''));
    $content = htmlspecialchars(trim($_POST['content'] ?? ''));
    $image_path = null;

    // Default values for medicine fields (only used if article_type is 'medicine')
    $medicine_fields = [
        'generic_name' => htmlspecialchars(trim($_POST['generic_name'] ?? '')),
        'brand_names' => htmlspecialchars(trim($_POST['brand_names'] ?? '')),
        'prescription_required' => (($_POST['prescription_required'] ?? '') === 'Yes' ? 1 : 0),
        'available_forms' => htmlspecialchars(trim($_POST['available_forms'] ?? '')),
        'active_ingredients' => htmlspecialchars(trim($_POST['active_ingredients'] ?? '')),
        'adult_dosage' => htmlspecialchars(trim($_POST['adult_dosage'] ?? '')),
        'elderly_dosage' => htmlspecialchars(trim($_POST['elderly_dosage'] ?? '')),
        'children_dosage' => htmlspecialchars(trim($_POST['children_dosage'] ?? '')),
        'pregnancy_safety' => htmlspecialchars(trim($_POST['pregnancy_safety'] ?? '')),
        'breastfeeding_safety' => htmlspecialchars(trim($_POST['breastfeeding_safety'] ?? '')),
        'dosage_chart' => htmlspecialchars(trim($_POST['dosage_chart'] ?? '')),
        'how_to_take' => htmlspecialchars(trim($_POST['how_to_take'] ?? '')),
        'missed_dose' => htmlspecialchars(trim($_POST['missed_dose'] ?? '')),
        'overdose_symptoms' => htmlspecialchars(trim($_POST['overdose_symptoms'] ?? '')),
        'side_effects' => htmlspecialchars(trim($_POST['side_effects'] ?? ''))
    ];

    // Basic validation for common fields
    if (empty($article_type) || empty($category) || empty($title) || empty($content)) {
        $error = "Please fill in all required common fields.";
    } else {
        try {
            $db_conn = null; // Will hold the correct database connection ($health_db or $medicine_db)
            $target_table = ''; // Will hold the correct table name ('health_articles' or 'medicine_articles')

            if ($article_type === 'health') {
                $db_conn = $health_db;
                $target_table = 'health_articles';
                if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception("Image upload is required for health articles.");
                }
                $target_dir = "uploads/articles/"; // Path relative to public_html/
                if (!is_dir($target_dir)) { mkdir($target_dir, 0755, true); }
                $file_name = uniqid() . "_" . basename($_FILES["image"]["name"]);
                $target_file = $target_dir . $file_name;
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime_type = $finfo->file($_FILES['image']['tmp_name']);
                if (!in_array($mime_type, $allowed_types)) { throw new Exception("Only JPG, PNG, and GIF files are allowed!"); }
                if ($_FILES["image"]["size"] > $max_size) { throw new Exception("File size exceeds 5MB limit!"); }
                if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) { throw new Exception("Failed to upload file. Check directory permissions (0755 for uploads folder)."); }
                $image_path = "uploads/articles/" . $file_name;
            } elseif ($article_type === 'medicine') {
                $db_conn = $medicine_db;
                $target_table = 'medicine_articles';
            } else {
                throw new Exception("Invalid article type selected.");
            }

            if (!$db_conn) {
                throw new Exception("Database connection error for selected article type.");
            }

            if ($article_type === 'health') {
                $stmt = $db_conn->prepare("INSERT INTO {$target_table} (category, title, content, image_path) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $category, $title, $content, $image_path);
            } else { // medicine
                $stmt = $db_conn->prepare("INSERT INTO {$target_table}
                    (category, title, content, generic_name, brand_names, prescription_required,
                     available_forms, active_ingredients, adult_dosage, elderly_dosage,
                     children_dosage, pregnancy_safety, breastfeeding_safety, dosage_chart,
                     how_to_take, missed_dose, overdose_symptoms, side_effects)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssissssssssssss",
                    $category, $title, $content, $medicine_fields['generic_name'], $medicine_fields['brand_names'],
                    $medicine_fields['prescription_required'], $medicine_fields['available_forms'],
                    $medicine_fields['active_ingredients'], $medicine_fields['adult_dosage'],
                    $medicine_fields['elderly_dosage'], $medicine_fields['children_dosage'],
                    $medicine_fields['pregnancy_safety'], $medicine_fields['breastfeeding_safety'],
                    $medicine_fields['dosage_chart'], $medicine_fields['how_to_take'],
                    $medicine_fields['missed_dose'], $medicine_fields['overdose_symptoms'],
                    $medicine_fields['side_effects']
                );
            }

            if (!$stmt->execute()) {
                throw new Exception("Database error: " . $stmt->error);
            }

            $_SESSION['success_message'] = "Article published successfully!"; // Use session for redirect
            header("Location: admin_dashboard"); // Redirect to new dashboard
            exit();

        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Article - Admin Panel</title>
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

        .admin-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 30px;
            background: var(--admin-card-bg);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }

        .admin-content h1 {
            color: var(--white);
            border-bottom: 2px solid var(--admin-border);
            padding-bottom: 15px;
            margin-bottom: 30px;
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
        input[type="password"],
        input[type="file"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            background: #555;
            border: 1px solid #666;
            border-radius: 4px;
            color: #fff;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #777;
            background: #5a5a5a;
        }
        /* Highlight invalid fields */
        input:required:invalid:not(:focus),
        select:required:invalid:not(:focus),
        textarea:required:invalid:not(:focus) {
            border-color: #dc3545; /* Red border */
            box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.2);
        }

        /* Style for file input label to make it consistent */
        input[type="file"] {
            padding: 8px; /* Slightly less padding for file input */
        }

        .conditional-field {
            display: none; /* Hidden by default */
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid var(--admin-border);
        }

        .conditional-field.active {
            display: block;
        }

        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
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

        /* Fieldset for grouping */
        fieldset {
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        legend {
            color: var(--primary);
            font-size: 1.2rem;
            font-weight: bold;
            padding: 0 10px;
            background: var(--admin-card-bg); /* Match card background */
            border-radius: 5px;
        }


        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .admin-sidebar {
                width: 200px; /* Slightly smaller sidebar */
            }
            .admin-content {
                padding: 20px;
            }
            .admin-container {
                padding: 25px;
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
                top: 70px; /* Adjust based on navbar height */
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
                flex-direction: column; /* Stack sidebar and content */
            }
            .admin-sidebar {
                width: 100%; /* Full width sidebar */
                height: auto; /* Auto height */
                flex-direction: row; /* Layout links in a row if space allows */
                flex-wrap: wrap; /* Allow sidebar items to wrap */
                justify-content: center; /* Center items */
                padding: 15px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1); /* Shadow at bottom */
            }
            .admin-sidebar h2 {
                display: none; /* Hide title on small screens to save space */
            }
            .admin-sidebar ul {
                display: flex; /* Make list items horizontal */
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px; /* Space between nav items */
                margin-bottom: 10px; /* Space below nav items */
            }
            .admin-sidebar ul li {
                margin-bottom: 0;
            }
            .admin-sidebar ul li a {
                padding: 8px 12px; /* Smaller padding for mobile nav links */
                font-size: 0.9em;
            }
            .admin-sidebar .logout-btn-sidebar {
                margin-top: 10px; /* Space from other links */
                width: auto; /* Auto width */
                min-width: 120px; /* Ensure a minimum width */
            }

            .admin-content {
                padding: 15px;
            }
            .admin-container {
                padding: 20px;
                margin: 1rem auto;
            }
            .admin-content h1 {
                font-size: 2em;
                margin-bottom: 20px;
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
                flex-direction: column; /* Stack sidebar items vertically on very small screens */
                align-items: stretch; /* Stretch to full width */
                gap: 5px;
            }
            .admin-sidebar ul li a {
                padding: 10px;
            }
            .admin-sidebar .logout-btn-sidebar {
                width: 100%; /* Full width logout button */
            }

            .admin-content {
                padding: 10px;
            }
            .admin-container {
                padding: 15px;
                margin: 0.5rem auto;
            }
            .admin-content h1 {
                font-size: 1.8em;
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
                <li><a href="admin_dashboard">Dashboard</a></li> <li><a href="create_article" class="active">Create Article</a></li> <li><a href="manage_articles">Manage Articles</a></li>
                <li><a href="logout">Logout</a></li>
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
                <li><a href="admin_dashboard">Dashboard</a></li> <li><a href="create_article" class="active">Create Article</a></li> <li><a href="manage_articles">Manage Articles</a></li>
                <li><a href="#">Manage Users</a></li>
                <li><a href="#">Settings</a></li>
            </ul>
            <a href="logout" class="logout-btn-sidebar">Logout</a>
        </aside>

        <main class="admin-content">
            <div class="admin-container">
                <h1>Create New Article</h1> <?php if ($error): ?>
                    <div class="message error"><?= $error ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="message success"><?= $success ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Article Type:</label>
                        <select name="article_type" id="articleType" required>
                            <option value="">Select Type</option>
                            <option value="health">Health Article</option>
                            <option value="medicine">Medicine Article</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Category:</label>
                        <select name="category" id="categorySelect" required>
                            <option value="">Select Category</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Title:</label>
                        <input type="text" name="title" required>
                    </div>

                    <div class="form-group">
                        <label>Content:</label>
                        <textarea name="content" rows="6" required></textarea>
                    </div>

                    <div class="conditional-field" id="imageField">
                        <div class="form-group">
                            <label>Featured Image (Required for Health Articles):</label>
                            <input type="file" name="image" accept="image/*">
                        </div>
                    </div>

                    <div class="conditional-field" id="medicineFields">
                        <h3>Medicine Details</h3>
                        <fieldset> <legend>Basic Information</legend>
                            <div class="form-group">
                                <label>Generic Name:</label>
                                <input type="text" name="generic_name">
                            </div>
                            <div class="form-group">
                                <label>Brand Names:</label>
                                <input type="text" name="brand_names">
                            </div>
                            <div class="form-group">
                                <label>Prescription Required:</label>
                                <select name="prescription_required" data-optional="true"> <option value="">Select</option>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Available Forms:</label>
                                <input type="text" name="available_forms">
                            </div>
                            <div class="form-group">
                                <label>Active Ingredients:</label>
                                <textarea name="active_ingredients" rows="3"></textarea>
                            </div>
                        </fieldset>

                        <fieldset> <legend>Dosage for Age Groups</legend>
                            <div class="form-group">
                                <label>Adult Dosage:</label>
                                <textarea name="adult_dosage" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Elderly Dosage:</label>
                                <textarea name="elderly_dosage" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Children Dosage:</label>
                                <textarea name="children_dosage" rows="2"></textarea>
                            </div>
                        </fieldset>

                        <fieldset> <legend>Safety Information</legend>
                            <div class="form-group">
                                <label>Pregnancy Safety:</label>
                                <textarea name="pregnancy_safety" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Breastfeeding Safety:</label>
                                <textarea name="breastfeeding_safety" rows="2"></textarea>
                            </div>
                        </fieldset>

                        <fieldset> <legend>Administration & Overdose</legend>
                            <div class="form-group">
                                <label>Dosage Chart:</label>
                                <textarea name="dosage_chart" rows="4"></textarea>
                            </div>
                            <div class="form-group">
                                <label>How to Take:</label>
                                <textarea name="how_to_take" rows="3"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Missed Dose:</label>
                                <textarea name="missed_dose" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Overdose Symptoms:</label>
                                <textarea name="overdose_symptoms" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Side Effects:</label>
                                <textarea name="side_effects" rows="2"></textarea>
                            </div>
                        </fieldset>
                    </div>

                    <button type="submit">Publish Article</button>
                </form>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Hamburger menu functionality
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

            // --- NEW: Prevent form submission on Enter key press in single-line inputs ---
            const form = document.querySelector('form');
            if (form) { // Ensure form exists before adding listener
                form.addEventListener('keydown', function(event) {
                    // Check if the pressed key is Enter (key code 13)
                    if (event.keyCode === 13) {
                        const targetTagName = event.target.tagName.toLowerCase();
                        const targetType = event.target.type ? event.target.type.toLowerCase() : '';

                        // Prevent default (submission) only for input elements that are not type="submit" or "file"
                        // and not for textarea elements (where Enter should create a newline)
                        if (targetTagName === 'input' && targetType !== 'submit' && targetType !== 'file') {
                            event.preventDefault(); // Prevent default form submission
                            // Optional: Move focus to the next input field for better UX
                            const formElements = Array.from(form.querySelectorAll('input:not([type="hidden"]), select, textarea, button[type="submit"]'));
                            const currentIndex = formElements.indexOf(event.target);
                            if (currentIndex > -1 && currentIndex < formElements.length - 1) {
                                formElements[currentIndex + 1].focus();
                            }
                        }
                    }
                });
            }


            // Article type and category logic
            const categories = {
                health: ['Nutrition', 'Fitness', 'Mental_Health', 'Diseases'],
                medicine: ['Tablets', 'Syrups', 'Injections', 'Capsules']
            };

            const articleType = document.getElementById('articleType');
            const categorySelect = document.getElementById('categorySelect');
            const medicineFields = document.getElementById('medicineFields');
            const imageField = document.getElementById('imageField');

            function updateForm() {
                const type = articleType.value;

                // Update categories
                categorySelect.innerHTML = '<option value="">Select Category</option>';
                if (categories[type]) {
                    categories[type].forEach(cat => {
                        const option = document.createElement('option');
                        option.value = cat.toLowerCase().replace(/ /g, '_'); // Store as lowercase_with_underscores
                        option.textContent = cat.replace(/_/g, ' '); // Display nicely
                        categorySelect.appendChild(option);
                    });
                }

                // Toggle fields visibility
                medicineFields.classList.toggle('active', type === 'medicine');
                imageField.classList.toggle('active', type === 'health');

                // Update required attributes for conditional fields
                // Get all relevant input types within the medicineFields section
                const medicineInputs = medicineFields.querySelectorAll('input:not([type="hidden"]), textarea, select');
                medicineInputs.forEach(input => {
                    // Reset required first
                    input.required = false;
                    // Then set based on type and data-optional attribute
                    if (type === 'medicine' && !input.hasAttribute('data-optional')) {
                        input.required = true;
                    }
                });
                
                // Image field is required only for health articles
                const imageInput = imageField.querySelector('input[type="file"]');
                if (imageInput) {
                    imageInput.required = type === 'health';
                }
            }

            articleType.addEventListener('change', updateForm);
            updateForm(); // Initial call to set up the form state
        });
    </script>
</body>
</html>