<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
// Adjust paths if db.php or functions.php are in a different directory
include 'db.php';
include 'functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: adminpage.php"); // Redirect admin to admin dashboard
    } else {
        header("Location: ../index.html"); // Redirect regular user to main website index
    }
    exit();
}

$error = '';
$success_message = ''; // To display registration success message

// Check if registration was successful (from register.php)
if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $success_message = "Registration successful! Please log in.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password']; // Do NOT sanitize password before verification

    // Use $user_conn for user authentication
    $stmt = $user_conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Verify the password against the hashed password in the database
        if (password_verify($password, $user['password'])) {
            // Set session variables upon successful login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true; // A general flag for being logged in

            // Redirect based on user role
            if (isAdmin()) {
                header("Location: adminpage.php"); // Admin to admin dashboard
            } else {
                header("Location: ../index.html"); // Regular user to main website index
            }
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "Username not found!";
    }
    $stmt->close(); // Close the prepared statement
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh; /* Use min-height for flexible content */
            margin: 0;
            padding: 20px; /* Add padding for small screens */
            box-sizing: border-box; /* Include padding in element's total width and height */
        }

        .container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            box-sizing: border-box;
        }

        h2 {
            text-align: center;
            color: #1a73e8;
            margin-bottom: 1.5rem;
        }

        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
        }

        .error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        .success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        input[type="text"],
        input[type="email"], /* Added for consistency, though not used in login form */
        input[type="password"] {
            width: 100%;
            padding: 12px; /* Increased padding */
            margin: 10px 0; /* Adjusted margin */
            border: 1px solid #ddd;
            border-radius: 6px; /* Slightly more rounded */
            box-sizing: border-box;
            font-size: 16px; /* Consistent font size */
        }

        button {
            background-color: #1a73e8;
            color: white;
            padding: 14px 20px; /* Increased padding */
            margin: 15px 0 10px; /* Adjusted margin for better spacing */
            border: none;
            border-radius: 6px; /* Slightly more rounded */
            cursor: pointer;
            width: 100%;
            font-size: 18px; /* Larger font size for button */
            font-weight: bold; /* Make button text bold */
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        button:hover {
            background-color: #1557b0; /* Darker blue on hover */
            transform: translateY(-2px); /* Slight lift effect */
        }

        p {
            text-align: center;
            margin-top: 15px;
            font-size: 15px;
        }

        p a {
            color: #2196f3;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        p a:hover {
            text-decoration: underline;
            color: #1976d2;
        }

        .admin-login {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            text-align: center;
        }

        .admin-login p {
            margin: 0;
            font-size: 14px;
            color: #555;
        }

        .admin-login a {
            color: #e67e22; /* Orange color for admin link */
            font-weight: bold;
        }

        .admin-login a:hover {
            color: #d35400; /* Darker orange */
        }

        /* Responsive adjustments */
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            .container {
                padding: 1.5rem;
            }
            h2 {
                font-size: 1.8rem;
            }
            input, button {
                font-size: 15px;
                padding: 10px;
            }
            button {
                padding: 12px 15px;
            }
            .admin-login p {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <?php if (isset($error) && $error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (isset($success_message) && $success_message): ?>
            <div class="alert success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="register.php">Register here</a></p>

        <div class="admin-login">
            <p>Are you an Admin? <a href="admin.php">Click here for admin login</a></p>
        </div>
    </div>
</body>
</html>