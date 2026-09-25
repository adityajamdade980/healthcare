<?php
session_start();
// IMPORTANT: Adjust these paths based on the actual location of your files.
// Assuming manage_users.php is in public_html/, and db.php/functions.php are also in public_html/
require_once 'db.php';
require_once 'functions.php';

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
$editUser = null; // Will store data of user being edited

// Check for success/error messages from previous redirects
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// --- Handle Delete User Action ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $user_id = (int)$_POST['delete_user_id'];

    // Prevent admin from deleting their own account (optional but recommended)
    if ($user_id === (int)$_SESSION['user_id']) {
        $_SESSION['error_message'] = "You cannot delete your own admin account!";
        header("Location: manage_users");
        exit();
    }

    try {
        $stmt = $user_conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);

        if (!$stmt->execute()) {
            throw new Exception("Failed to delete user: " . $stmt->error);
        }

        $_SESSION['success_message'] = "User deleted successfully!";

    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error deleting user: " . $e->getMessage();
    }
    header("Location: manage_users"); // Redirect to clean URL
    exit();
}

// --- Handle Edit User Initialization ---
if (isset($_GET['edit_user_id'])) {
    $user_id = (int)$_GET['edit_user_id'];

    $stmt = $user_conn->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $editUser = $result->fetch_assoc();
    } else {
        $error = "User not found for editing.";
    }
    $stmt->close();
}

// --- Handle Form Submission (Update User) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $user_id = (int)$_POST['user_id'];
    $username = htmlspecialchars(trim($_POST['username'] ?? ''));
    $email = filter_var(htmlspecialchars(trim($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    $role = htmlspecialchars(trim($_POST['role'] ?? 'user'));
    $new_password = $_POST['new_password'] ?? ''; // Optional new password (not sanitized here, will be hashed)

    if (!$email) {
        $error = "Invalid email format!";
    } elseif (empty($username)) {
        $error = "Username cannot be empty!";
    } else {
        try {
            // Check for duplicate username/email (excluding current user)
            $stmt_check_duplicate = $user_conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt_check_duplicate->bind_param("ssi", $username, $email, $user_id);
            $stmt_check_duplicate->execute();
            $stmt_check_duplicate->store_result();
            if ($stmt_check_duplicate->num_rows > 0) {
                throw new Exception("Username or Email already exists for another user!");
            }
            $stmt_check_duplicate->close();

            // Prepare update query parts dynamically
            $update_query_parts = ["username = ?", "email = ?", "role = ?"];
            $bind_types = "sss";
            $bind_params = [&$username, &$email, &$role];

            if (!empty($new_password)) {
                if (strlen($new_password) < 6) {
                    throw new Exception("New password must be at least 6 characters long!");
                }
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_query_parts[] = "password = ?";
                $bind_types .= "s";
                $bind_params[] = &$hashed_password;
            }

            $update_query = "UPDATE users SET " . implode(", ", $update_query_parts) . " WHERE id = ?";
            $bind_types .= "i";
            $bind_params[] = &$user_id;

            $stmt = $user_conn->prepare($update_query);
            if ($stmt === false) {
                throw new Exception("Failed to prepare update statement: " . $user_conn->error);
            }

            // Bind parameters dynamically using call_user_func_array
            // Create an array for bind_param arguments: first element is types string, rest are references to parameters
            $bind_args = array_merge([$bind_types], $bind_params);
            call_user_func_array([$stmt, 'bind_param'], $bind_args);


            if (!$stmt->execute()) {
                throw new Exception("User update failed: " . $stmt->error);
            }

            // If the current admin changed their own role, log them out to re-authenticate with new permissions
            if ($user_id === (int)$_SESSION['user_id'] && $_SESSION['role'] !== $role) {
                session_unset();
                session_destroy();
                header("Location: login?message=role_changed"); // Redirect with message
                exit();
            }

            $_SESSION['success_message'] = "User updated successfully!";
            header("Location: manage_users");
            exit();

        } catch (Exception $e) {
            $error = "Error updating user: " . $e->getMessage();
        }
    }
}


// --- Fetch All Users for Display ---
$users = [];
try {
    // Assuming the 'users' table has 'created_at' and 'updated_at' columns.
    // If not, remove 'updated_at' from the SELECT query here.
    $result = $user_conn->query("SELECT id, username, email, role, created_at, updated_at FROM users ORDER BY created_at DESC");
    if ($result) {
        $users = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        error_log("Error fetching users: " . $user_conn->error);
        $error = "Error fetching users from database.";
    }
} catch (Exception $e) {
    $error = "Error fetching users: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
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
            margin-bottom: 30px; /* Space before edit form */
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
        td:nth-child(1) { /* ID column */
            width: 50px;
            max-width: 50px;
        }
        td:nth-child(2) { /* Username column */
            max-width: 180px;
        }
        td:nth-child(3) { /* Email column */
            max-width: 250px;
        }
        td:nth-child(4) { /* Role column */
            width: 80px;
            max-width: 80px;
        }
        td:nth-child(5) { /* Last Updated column */
            max-width: 150px;
            white-space: normal; /* Allow date to wrap */
        }
        td:last-child { /* Actions column */
            width: 120px;
            max-width: 120px;
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
        .edit-form input[type="email"],
        .edit-form input[type="password"],
        .edit-form select {
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
        .edit-form select:focus {
            outline: none;
            border-color: #777;
            background: #5a5a5a;
        }
        
        /* Highlight invalid fields for edit form */
        .edit-form input:required:invalid:not(:focus),
        .edit-form select:required:invalid:not(:focus) {
            border-color: #dc3545; /* Red border */
            box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.2);
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
                width: 200px;
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
            th, td {
                padding: 6px 8px;
                font-size: 0.75em;
            }
            .edit-form {
                padding: 15px;
            }
            .edit-form label,
            .edit-form input,
            .edit-form select {
                font-size: 0.9em;
            }
            .edit-form .form-actions .btn {
                font-size: 0.9em;
                padding: 10px;
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
                <li><a href="manage_articles" class="active">Manage Articles</a></li>
                <li><a href="manage_users">Manage Users</a></li> <li><a href="logout">Logout</a></li>
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
                <li><a href="manage_articles" class="active">Manage Articles</a></li>
                <li><a href="manage_users">Manage Users</a></li> <li><a href="#">Settings</a></li>     </ul>
            <a href="logout" class="logout-btn-sidebar">Logout</a>
        </aside>

        <main class="admin-content">
            <h1>Manage Users</h1>

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
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="6" style="text-align: center;">No users found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['id']) ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= ucfirst(htmlspecialchars($user['role'])) ?></td>
                                <td><?= date('M j, Y H:i', strtotime($user['updated_at'])) ?></td>
                                <td>
                                    <a href="manage_users?edit_user_id=<?= $user['id'] ?>" class="btn btn-edit">Edit</a>
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="delete_user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn-delete"
                                            onclick="return confirm('Are you sure you want to delete this user? This cannot be undone.')">
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

            <?php if ($editUser): ?>
            <div class="edit-form">
                <h2>Edit User (ID: <?= htmlspecialchars($editUser['id']) ?>)</h2>
                <form method="POST">
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($editUser['id']) ?>">
                    <input type="hidden" name="update_user" value="1">

                    <div class="form-group">
                        <label for="edit_username">Username:</label>
                        <input type="text" id="edit_username" name="username" value="<?= htmlspecialchars($editUser['username']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_email">Email:</label>
                        <input type="email" id="edit_email" name="email" value="<?= htmlspecialchars($editUser['email']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_role">Role:</label>
                        <select name="role" id="edit_role" required>
                            <option value="user" <?= ($editUser['role'] === 'user') ? 'selected' : '' ?>>User</option>
                            <option value="admin" <?= ($editUser['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password (leave blank to keep current):</label>
                        <input type="password" id="new_password" name="new_password" placeholder="Min 6 characters">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-edit">Update User</button>
                        <a href="manage_users" class="btn btn-cancel">Cancel</a>
                    </div>
                </form>
            </div>
            <?php endif; ?>
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