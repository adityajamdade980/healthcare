<?php
session_start();
// Adjust paths if db.php or functions.php are in a different directory
require_once 'db.php';
require_once 'functions.php';

// Redirect if already logged in as admin
if (isAdmin()) { // Using isAdmin() directly from functions.php
    header("Location: admin_dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password']; // Do NOT sanitize password before verification

    // Use $user_conn for admin authentication
    $stmt = $user_conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? AND role = 'admin'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            // Set session variables for admin
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true; // General login flag
            // A specific admin_logged_in flag could be used if preferred, but 'role' is usually enough
            // $_SESSION['admin_logged_in'] = true; // Optional: specific admin flag

            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "Admin user not found or incorrect username!"; // More specific message
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh; /* Use min-height */
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }

        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            box-sizing: border-box;
        }

        h2 {
            color: #1a73e8;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px; /* Consistent border-radius */
            box-sizing: border-box;
            font-size: 16px;
        }

        button {
            width: 100%;
            padding: 14px 20px; /* Consistent padding with other forms */
            background: #1a73e8;
            color: white;
            border: none;
            border-radius: 6px; /* Consistent border-radius */
            font-size: 18px; /* Consistent font-size */
            font-weight: bold; /* Consistent font-weight */
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease;
            margin-top: 10px; /* Space above button */
        }

        button:hover {
            background: #1557b0;
            transform: translateY(-2px); /* Consistent lift effect */
        }

        .error {
            background-color: #ffebee; /* Consistent error styling */
            color: #c62828;
            border: 1px solid #ffcdd2;
            padding: 10px;
            margin-bottom: 1rem;
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
        }
        .back-to-user-login {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            text-align: center;     
        }
        .back-to-user-login p {
            margin: 0;
            font-size: 14px;
            color: #555;
        }
        .back-to-user-login a {
            color: #2196f3;
            text-decoration: none;
            font-weight: bold;
        }
        .back-to-user-login a:hover {
            text-decoration: underline;
            color: #1976d2;
        }

        /* Responsive adjustments */
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            .login-container {
                padding: 1.5rem;
            }
            h2 {
                font-size: 1.8rem;
            }
            input {
                font-size: 15px;
                padding: 10px;
            }
            button {
                padding: 12px 15px;
                font-size: 15px;
            }
            .back-to-user-login p {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Admin Login</h2>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <input type="text" name="username" placeholder="Admin Username" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Admin Password" required>
            </div>
            <button type="submit">Login as Admin</button>
        </form>
        <div class="back-to-user-login">
            <p>Not an Admin? <a href="login.php">Return to User Login</a></p>
        </div>
    </div>
</body>
</html>
