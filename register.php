<?php
session_start();
// Adjust paths if db.php or functions.php are in a different directory
include 'db.php';
include 'functions.php';

// Redirect logged-in users away from the registration page
if (isLoggedIn()) {
    // Determine where to redirect based on role, if needed.
    // For now, just redirect to dashboard.php or index.html
    header("Location: ../index.html"); // Assuming index.html is the main page for users
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate inputs
    $username = sanitize($_POST['username']);
    $email = filter_var(sanitize($_POST['email']), FILTER_VALIDATE_EMAIL); // Validate email format
    $password = $_POST['password']; // Don't sanitize password before hashing
    $confirm_password = $_POST['confirm_password'];

    if (!$email) {
        $error = "Invalid email format!";
    } elseif (strlen($password) < 6) { // Minimum password length
        $error = "Password must be at least 6 characters long!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // Use $user_conn for user management
        $stmt = $user_conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Username or Email already exists!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            // Assuming 'password' is the column name in your users table (was password_hash in previous SQL)
            $insert_stmt = $user_conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("sss", $username, $email, $hashed_password);

            if ($insert_stmt->execute()) {
                $success = "Registration successful! You can now log in.";
                // Optionally redirect to login page after successful registration
                header("Location: login.php?registered=1");
                exit();
            } else {
                $error = "Registration failed! Please try again later. " . $user_conn->error; // Add database error for debugging
            }
        }
        $stmt->close(); // Close statement
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
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
        input[type="email"],
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
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Register</h2>
        <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            <button type="submit">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html>