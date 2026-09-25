<?php
session_start();
// IMPORTANT: Adjust these paths based on the actual location of your files relative to manage_articles.php
// Assuming manage_articles.php is in public_html/, and db.php/functions.php are also in public_html/
require_once 'db.php';
require_once 'functions.php';

// Enable error reporting for development (remove/disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verify admin privileges
// This page requires the user to be logged in AND have an 'admin' role
if (!isLoggedIn() || !isAdmin()) {
    header("Location: login"); // Redirect to clean URL for login page
    exit();
}

// Initialize messages
$error = '';
$success = '';
$editArticle = null; // Will store data of article being edited

// Check for success/error messages from previous redirects (e.g., after publish from create_article)
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}


// --- Handle Delete Action ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id']) && isset($_POST['delete_type'])) {
    $article_id = (int)$_POST['delete_id'];
    $article_type = $_POST['delete_type'];

    try {
        $db_conn_delete = null;
        $table_to_delete = '';

        if ($article_type === 'health') {
            $db_conn_delete = $health_db;
            $table_to_delete = 'health_articles';
        } elseif ($article_type === 'medicine') {
            $db_conn_delete = $medicine_db;
            $table_to_delete = 'medicine_articles';
        } else {
            throw new Exception("Invalid article type for deletion.");
        }

        if (!$db_conn_delete) {
            throw new Exception("Database connection not found for deletion.");
        }

        // --- OPTIONAL: Delete associated image file for health articles ---
        if ($article_type === 'health') {
            $stmt_fetch_image = $db_conn_delete->prepare("SELECT image_path FROM {$table_to_delete} WHERE id = ?");
            $stmt_fetch_image->bind_param("i", $article_id);
            $stmt_fetch_image->execute();
            $image_result = $stmt_fetch_image->get_result()->fetch_assoc();
            $stmt_fetch_image->close();

            if ($image_result && !empty($image_result['image_path'])) {
                // Ensure image path is relative to DOCUMENT_ROOT for deletion
                $image_file_to_delete = $_SERVER['DOCUMENT_ROOT'] . '/' . $image_result['image_path'];
                if (file_exists($image_file_to_delete)) {
                    unlink($image_file_to_delete); // Delete the actual file
                }
            }
        }
        // --- END OPTIONAL IMAGE DELETION ---


        $stmt = $db_conn_delete->prepare("DELETE FROM {$table_to_delete} WHERE id = ?");
        $stmt->bind_param("i", $article_id);

        if (!$stmt->execute()) {
            throw new Exception("Failed to delete article: " . $stmt->error);
        }

        $_SESSION['success_message'] = "Article deleted successfully!";

    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error deleting article: " . $e->getMessage();
    }
    header("Location: manage_articles"); // Redirect to clean URL
    exit();
}

// --- Handle Edit Mode Initialization ---
if (isset($_GET['edit_id']) && isset($_GET['edit_type'])) {
    $article_id = (int)$_GET['edit_id'];
    $article_type = $_GET['edit_type'];

    $db_conn_fetch = null;
    $table_to_fetch = '';

    if ($article_type === 'health') {
        $db_conn_fetch = $health_db;
        $table_to_fetch = 'health_articles';
    } elseif ($article_type === 'medicine') {
        $db_conn_fetch = $medicine_db;
        $table_to_fetch = 'medicine_articles';
    }

    if ($db_conn_fetch) {
        $stmt = $db_conn_fetch->prepare("SELECT * FROM {$table_to_fetch} WHERE id = ?");
        $stmt->bind_param("i", $article_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $editArticle = $result->fetch_assoc();
            $editArticle['article_type'] = $article_type; // Add type for form
        } else {
            $error = "Article not found for editing.";
        }
        $stmt->close();
    } else {
        $error = "Invalid article type for fetching.";
    }
}


// --- Handle Form Submission (Update Article) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_article'])) {
    $article_id = (int)$_POST['article_id'];
    $article_type = $_POST['article_type'];
    $category = htmlspecialchars(trim($_POST['category'] ?? ''));
    $title = htmlspecialchars(trim($_POST['title'] ?? ''));
    $content = htmlspecialchars(trim($_POST['content'] ?? ''));
    $image_path = null; // Will be set if health article and new image uploaded

    try {
        $db_conn_update = null;
        $table_to_update = '';

        if ($article_type === 'health') {
            $db_conn_update = $health_db;
            $table_to_update = 'health_articles';

            // Handle image upload if a new one is provided
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $target_dir = "uploads/articles/"; // Path relative to public_html/
                if (!is_dir($target_dir)) { mkdir($target_dir, 0755, true); }
                $file_name = uniqid() . "_" . basename($_FILES["image"]["name"]);
                $target_file = $target_dir . $file_name;
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime_type = $finfo->file($_FILES['image']['tmp_name']);

                if (!in_array($mime_type, $allowed_types)) {
                    throw new Exception("Only JPG, PNG, and GIF files are allowed for image upload!");
                }
                if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    throw new Exception("Failed to upload new image file.");
                }
                $image_path = "uploads/articles/" . $file_name; // Path relative to frontend root

                // Delete old image if a new one was uploaded and old one existed
                $old_image_path_query = $health_db->prepare("SELECT image_path FROM health_articles WHERE id = ?");
                $old_image_path_query->bind_param("i", $article_id);
                $old_image_path_query->execute();
                $old_image_result = $old_image_path_query->get_result()->fetch_assoc();
                $old_image_path_query->close();

                if ($old_image_result && !empty($old_image_result['image_path'])) {
                    $old_file_to_delete = $_SERVER['DOCUMENT_ROOT'] . '/' . $old_image_result['image_path'];
                    if (file_exists($old_file_to_delete)) {
                        unlink($old_file_to_delete);
                    }
                }

            } else {
                // If no new image, retain old image path if available
                $old_image_path_query = $health_db->prepare("SELECT image_path FROM health_articles WHERE id = ?");
                $old_image_path_query->bind_param("i", $article_id);
                $old_image_path_query->execute();
                $old_image_result = $old_image_path_query->get_result()->fetch_assoc();
                $image_path = $old_image_result['image_path'] ?? null;
                $old_image_path_query->close();
            }

            $stmt = $db_conn_update->prepare("UPDATE {$table_to_update} SET
                category = ?, title = ?, content = ?, image_path = ?
                WHERE id = ?");
            $stmt->bind_param("ssssi", $category, $title, $content, $image_path, $article_id);

        } elseif ($article_type === 'medicine') {
            $db_conn_update = $medicine_db;
            $table_to_update = 'medicine_articles';

            // Medicine-specific fields
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

            $stmt = $db_conn_update->prepare("UPDATE {$table_to_update} SET
                category = ?, title = ?, content = ?, generic_name = ?, brand_names = ?, prescription_required = ?,
                available_forms = ?, active_ingredients = ?, adult_dosage = ?, elderly_dosage = ?,
                children_dosage = ?, pregnancy_safety = ?, breastfeeding_safety = ?, dosage_chart = ?,
                how_to_take = ?, missed_dose = ?, overdose_symptoms = ?, side_effects = ?
                WHERE id = ?"); // Ensure 'id' is used here

            $stmt->bind_param("sssssisssssssssssi",
                $category, $title, $content,
                $medicine_fields['generic_name'], $medicine_fields['brand_names'], $medicine_fields['prescription_required'],
                $medicine_fields['available_forms'], $medicine_fields['active_ingredients'],
                $medicine_fields['adult_dosage'], $medicine_fields['elderly_dosage'],
                $medicine_fields['children_dosage'], $medicine_fields['pregnancy_safety'],
                $medicine_fields['breastfeeding_safety'], $medicine_fields['dosage_chart'],
                $medicine_fields['how_to_take'], $medicine_fields['missed_dose'],
                $medicine_fields['overdose_symptoms'], $medicine_fields['side_effects'],
                $article_id
            );
        } else {
            throw new Exception("Invalid article type for update.");
        }

        if (!$stmt->execute()) {
            throw new Exception("Database update failed: " . $stmt->error);
        }

        $_SESSION['success_message'] = "Article updated successfully!";
        header("Location: manage_articles"); // Redirect to clean URL
        exit();

    } catch (Exception $e) {
        $error = "Error updating article: " . $e->getMessage();
    }
}


// --- Fetch All Articles (from both tables) for display ---
$articles = [];
try {
    // Fetch health articles
    $health_articles_result = $health_db->query("SELECT id, category, title, content, image_path, 'health' as article_type, created_at, updated_at FROM health_articles ORDER BY created_at DESC");
    if ($health_articles_result) {
        while ($row = $health_articles_result->fetch_assoc()) {
            $articles[] = $row;
        }
    } else {
        // Log error, but don't stop page render for empty table
        error_log("Error fetching health articles from health_articles: " . $health_db->error);
    }

    // Fetch medicine articles
    // Select all relevant medicine columns, provide NULL as image_path since medicine articles don't have images
    // Note: 'updated_at' must be selected for sorting and display
    $medicine_articles_result = $medicine_db->query("
        SELECT id, category, title, content, NULL as image_path, 'medicine' as article_type,
               generic_name, brand_names, prescription_required, available_forms, active_ingredients,
               adult_dosage, elderly_dosage, children_dosage, pregnancy_safety, breastfeeding_safety,
               dosage_chart, how_to_take, missed_dose, overdose_symptoms, side_effects,
               created_at, updated_at
        FROM medicine_articles ORDER BY created_at DESC
    ");
    if ($medicine_articles_result) {
        while ($row = $medicine_articles_result->fetch_assoc()) {
            $articles[] = $row;
        }
    } else {
        error_log("Error fetching medicine articles from medicine_articles: " . $medicine_db->error);
    }

    // Sort all articles by created_at in descending order after combining
    usort($articles, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

} catch (Exception $e) {
    // This catches errors from the above queries if they fail
    $error = "Error fetching articles: " . $e->getMessage(); // Display general fetch error
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Articles - Admin Panel</title>
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

        /* --- Table Styling --- */
        .table-container {
            overflow-x: auto; /* Enable horizontal scroll on small screens */
            background: var(--admin-card-bg);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px; /* Ensure table is readable on small screens when scrolled */
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #555; /* Lighter border for dark background */
            color: var(--admin-text-light);
        }

        th {
            background-color: #555; /* Darker header background */
            font-weight: 600;
            color: var(--white);
            white-space: nowrap; /* Prevent wrapping in headers */
        }

        td {
            white-space: nowrap; /* Prevent wrapping in table cells */
            max-width: 150px; /* Limit cell width */
            overflow: hidden;
            text-overflow: ellipsis; /* Add ellipsis for overflow */
        }

        td:nth-child(1), td:nth-child(2) { /* Title, Type */
            max-width: 200px;
        }


        /* --- Buttons --- */
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9em;
            transition: background-color 0.3s ease, transform 0.2s ease;
            display: inline-flex; /* For vertical alignment */
            align-items: center;
            justify-content: center;
            margin: 2px; /* Small margin between buttons */
            white-space: nowrap; /* Prevent button text wrapping */
        }

        .btn-edit {
            background-color: #007bff; /* Primary blue for edit */
            color: white;
        }
        .btn-edit:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }

        .btn-delete {
            background-color: #dc3545; /* Red for delete */
            color: white;
        }
        .btn-delete:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }

        /* --- Message Styles --- */
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px; /* More rounded */
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

        /* --- Edit Form Styles --- */
        .edit-form {
            background: var(--admin-card-bg);
            padding: 30px;
            margin-top: 30px; /* Space from table */
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }

        .edit-form h2 {
            color: var(--white);
            border-bottom: 1px solid var(--admin-border);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .edit-form .form-group {
            margin-bottom: 15px;
        }

        .edit-form label {
            display: block;
            margin-bottom: 8px;
            color: #ccc;
            font-weight: 500;
        }

        .edit-form input[type="text"],
        .edit-form select,
        .edit-form textarea,
        .edit-form input[type="file"] {
            width: 100%;
            padding: 10px;
            background: #555;
            border: 1px solid #666;
            border-radius: 4px;
            color: #fff;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .edit-form input:focus,
        .edit-form select:focus,
        .edit-form textarea:focus {
            outline: none;
            border-color: #777;
            background: #5a5a5a;
        }
        .edit-form textarea {
            resize: vertical;
            min-height: 80px;
        }
        .edit-form input[type="checkbox"] {
            width: auto; /* Override 100% width */
            margin-right: 8px;
        }

        /* Highlight invalid fields for edit form */
        .edit-form input:required:invalid:not(:focus),
        .edit-form select:required:invalid:not(:focus),
        .edit-form textarea:required:invalid:not(:focus) {
            border-color: #dc3545; /* Red border */
            box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.2);
        }

        /* Fieldset for grouping in edit form */
        .edit-form fieldset {
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .edit-form legend {
            color: var(--primary);
            font-size: 1.2rem;
            font-weight: bold;
            padding: 0 10px;
            background: var(--admin-card-bg); /* Match card background */
            border-radius: 5px;
        }


        .edit-form .form-actions {
            margin-top: 30px;
            text-align: right;
        }

        .edit-form .form-actions .btn {
            padding: 10px 20px;
            font-size: 1em;
        }
        .edit-form .btn-cancel {
            background-color: #6c757d; /* Grey for cancel */
            margin-left: 10px;
        }
        .edit-form .btn-cancel:hover {
            background-color: #5a6268;
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .admin-sidebar {
                width: 200px; /* Slightly smaller sidebar */
            }
            .admin-content {
                padding: 20px;
            }
            .table-container {
                padding: 15px;
            }
            th, td {
                padding: 10px;
                font-size: 0.9em;
            }
            .btn {
                padding: 6px 12px;
                font-size: 0.85em;
            }
            .edit-form {
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
                <li><a href="admin_dashboard">Dashboard</a></li>
                <li><a href="create_article" class="active">Create Article</a></li>
                <li><a href="manage_articles">Manage Articles</a></li>
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
                <li><a href="admin_dashboard">Dashboard</a></li>
                <li><a href="create_article" class="active">Create Article</a></li>
                <li><a href="manage_articles">Manage Articles</a></li>
                <li><a href="#">Manage Users</a></li> <li><a href="#">Settings</a></li>     </ul>
            <a href="logout" class="logout-btn-sidebar">Logout</a>
        </aside>

        <main class="admin-content">
            <h1>Manage Articles</h1>

            <?php if ($error): ?>
                <div class="message error"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="message success"><?= $success ?></div>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($articles)): ?>
                            <tr><td colspan="6" style="text-align: center;">No articles found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($articles as $article): ?>
                            <tr>
                                <td><?= htmlspecialchars($article['id']) ?></td>
                                <td><?= htmlspecialchars($article['title']) ?></td>
                                <td><?= ucfirst(htmlspecialchars($article['article_type'])) ?></td>
                                <td><?= ucfirst(str_replace('_', ' ', htmlspecialchars($article['category']))) ?></td>
                                <td><?= date('M j, Y H:i', strtotime($article['updated_at'])) ?></td>
                                <td>
                                    <a href="manage_articles?edit_id=<?= $article['id'] ?>&edit_type=<?= htmlspecialchars($article['article_type']) ?>" class="btn btn-edit">Edit</a>
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="delete_id" value="<?= $article['id'] ?>">
                                        <input type="hidden" name="delete_type" value="<?= htmlspecialchars($article['article_type']) ?>">
                                        <button type="submit" class="btn btn-delete"
                                            onclick="return confirm('Are you sure you want to delete this article?')">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($editArticle): ?>
            <div class="edit-form">
                <h2>Edit Article (ID: <?= htmlspecialchars($editArticle['id']) ?> - Type: <?= ucfirst(htmlspecialchars($editArticle['article_type'])) ?>)</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="article_id" value="<?= htmlspecialchars($editArticle['id']) ?>">
                    <input type="hidden" name="update_article" value="1">
                    <input type="hidden" name="article_type" value="<?= htmlspecialchars($editArticle['article_type']) ?>">

                    <div class="form-group">
                        <label for="edit_title">Title:</label>
                        <input type="text" id="edit_title" name="title" value="<?= htmlspecialchars($editArticle['title']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_category">Category:</label>
                        <select name="category" id="edit_category" required>
                            </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_content">Content:</label>
                        <textarea id="edit_content" name="content" rows="6" required><?= htmlspecialchars($editArticle['content']) ?></textarea>
                    </div>

                    <?php if ($editArticle['article_type'] === 'health'): ?>
                    <div class="conditional-field active" id="editImageField">
                        <div class="form-group">
                            <label for="edit_image">Featured Image:</label>
                            <input type="file" id="edit_image" name="image" accept="image/*">
                            <?php if (!empty($editArticle['image_path'])): ?>
                                <p style="margin-top: 10px; color: #ccc;">Current Image:
                                    <img src="<?= htmlspecialchars($editArticle['image_path']) ?>" alt="Current Image" style="max-width: 100px; max-height: 100px; vertical-align: middle; margin-left: 10px; border-radius: 5px;">
                                    (Upload new to replace)
                                </p>
                            <?php else: ?>
                                <p style="margin-top: 10px; color: #ccc;">No image currently set.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($editArticle['article_type'] === 'medicine'): ?>
                    <div class="conditional-field active" id="editMedicineFields">
                        <h3>Medicine Details</h3>
                        <fieldset> <legend>Basic Information</legend>
                            <div class="form-group">
                                <label>Generic Name:</label>
                                <input type="text" name="generic_name" value="<?= htmlspecialchars($editArticle['generic_name'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Brand Names:</label>
                                <input type="text" name="brand_names" value="<?= htmlspecialchars($editArticle['brand_names'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Prescription Required:</label>
                                <select name="prescription_required" data-optional="true">
                                    <option value="">Select</option>
                                    <option value="Yes" <?= (($editArticle['prescription_required'] ?? 0) == 1) ? 'selected' : '' ?>>Yes</option>
                                    <option value="No" <?= (($editArticle['prescription_required'] ?? 0) == 0 && ($editArticle['prescription_required'] ?? '') !== '') ? 'selected' : '' ?>>No</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Available Forms:</label>
                                <input type="text" name="available_forms" value="<?= htmlspecialchars($editArticle['available_forms'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Active Ingredients:</label>
                                <textarea name="active_ingredients" rows="3"><?= htmlspecialchars($editArticle['active_ingredients'] ?? '') ?></textarea>
                            </div>
                        </fieldset>

                        <fieldset> <legend>Dosage for Age Groups</legend>
                            <div class="form-group">
                                <label>Adult Dosage:</label>
                                <textarea name="adult_dosage" rows="2"><?= htmlspecialchars($editArticle['adult_dosage'] ?? '') ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Elderly Dosage:</label>
                                <textarea name="elderly_dosage" rows="2"><?= htmlspecialchars($editArticle['elderly_dosage'] ?? '') ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Children Dosage:</label>
                                <textarea name="children_dosage" rows="2"><?= htmlspecialchars($editArticle['children_dosage'] ?? '') ?></textarea>
                            </div>
                        </fieldset>

                        <fieldset> <legend>Safety Information</legend>
                            <div class="form-group">
                                <label>Pregnancy Safety:</label>
                                <textarea name="pregnancy_safety" rows="2"><?= htmlspecialchars($editArticle['pregnancy_safety'] ?? '') ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Breastfeeding Safety:</label>
                                <textarea name="breastfeeding_safety" rows="2"><?= htmlspecialchars($editArticle['breastfeeding_safety'] ?? '') ?></textarea>
                            </div>
                        </fieldset>

                        <fieldset> <legend>Administration & Overdose</legend>
                            <div class="form-group">
                                <label>Dosage Chart:</label>
                                <textarea name="dosage_chart" rows="4"><?= htmlspecialchars($editArticle['dosage_chart'] ?? '') ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>How to Take:</label>
                                <textarea name="how_to_take" rows="3"><?= htmlspecialchars($editArticle['how_to_take'] ?? '') ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Missed Dose:</label>
                                <textarea name="missed_dose" rows="2"><?= htmlspecialchars($editArticle['missed_dose'] ?? '') ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Overdose Symptoms:</label>
                                <textarea name="overdose_symptoms" rows="2"><?= htmlspecialchars($editArticle['overdose_symptoms'] ?? '') ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Side Effects:</label>
                                <textarea name="side_effects" rows="2"><?= htmlspecialchars($editArticle['side_effects'] ?? '') ?></textarea>
                            </div>
                        </fieldset>
                    </div>
                    <?php endif; ?>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-edit">Update Article</button>
                        <a href="manage_articles" class="btn btn-cancel">Cancel</a>
                    </div>
                </form>
            </div>
            <?php endif; ?>
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

            // Categories for Edit Form dropdown
            const allCategories = {
                health: ['Nutrition', 'Fitness', 'Mental_Health', 'Diseases'],
                medicine: ['Tablets', 'Syrups', 'Injections', 'Capsules']
            };

            const editForm = document.querySelector('.edit-form');
            if (editForm) {
                // Correctly get the articleType from the hidden input
                const articleTypeInput = editForm.querySelector('input[name="article_type"]');
                const categorySelect = editForm.querySelector('select[name="category"]');
                const editImageField = document.getElementById('editImageField');
                const editMedicineFields = document.getElementById('editMedicineFields');

                // Get the initial type and category values from PHP
                // Use a PHP variable for the initial category value directly in JS
                const initialArticleType = articleTypeInput ? articleTypeInput.value : '';
                const initialCategoryValue = <?= json_encode($editArticle['category'] ?? '') ?>;


                function updateEditFormCategories(currentType, currentCategoryValue) {
                    categorySelect.innerHTML = '<option value="">Select Category</option>'; // Clear existing options

                    if (allCategories[currentType]) {
                        allCategories[currentType].forEach(cat => {
                            const option = document.createElement('option');
                            option.value = cat.toLowerCase().replace(/ /g, '_');
                            option.textContent = cat.replace(/_/g, ' ');
                            
                            // Select the option if its value matches the current category from PHP
                            if (option.value === currentCategoryValue.toLowerCase().replace(/ /g, '_')) {
                                option.selected = true;
                            }
                            categorySelect.appendChild(option);
                        });
                    }
                    
                    // Toggle visibility of image/medicine fields in edit form based on currentType
                    if (editImageField) editImageField.classList.toggle('active', currentType === 'health');
                    if (editMedicineFields) editMedicineFields.classList.toggle('active', currentType === 'medicine');

                    // Set required attributes for dynamic fields in edit form (medicine fields are optional by default)
                    const medicineInputs = editMedicineFields ? editMedicineFields.querySelectorAll('input:not([type="hidden"]), textarea, select') : [];
                    medicineInputs.forEach(input => {
                        input.required = false; // Reset first
                        if (currentType === 'medicine' && !input.hasAttribute('data-optional')) {
                            input.required = true;
                        }
                    });

                    const imageInput = editImageField ? editImageField.querySelector('input[type="file"]') : null;
                    if (imageInput) {
                        // Make image required if health article AND no existing image path (for new uploads)
                        // If there's an existing image, it's optional to upload a new one
                        imageInput.required = currentType === 'health' && !<?= json_encode(!empty($editArticle['image_path'])) ?>;
                        imageInput.closest('.form-group').querySelector('label').textContent = 'Featured Image' + (imageInput.required ? ' (Required)' : ' (Optional)');
                    }
                }

                // Initial call to set up the form state based on PHP data
                updateEditFormCategories(initialArticleType, initialCategoryValue);

                // --- NEW: Add an event listener to the articleTypeSelect for future changes (though it's hidden) ---
                // If you were to make articleType a visible select in edit form, this would be crucial
                // For a hidden input, its value is fixed, so the initial call is usually enough.
                // However, if the edit form structure changes and articleType becomes a user-selectable field,
                // this listener would be needed. For now, it won't fire as it's hidden.
                // If you want to change article type during edit, you'd need to convert the hidden input to a select.
                // articleTypeSelect.addEventListener('change', () => updateEditFormCategories(articleTypeSelect.value, categorySelect.dataset.currentCategory));
            }
        });
    </script>
</body>
</html>