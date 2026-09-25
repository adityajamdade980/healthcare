<?php
session_start();
session_unset(); // Unset all session variables
session_destroy(); // Destroy the session
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // A date in the past

// Redirect to the login page. Use 'replace' in history for cleaner navigation.
// Also, add a small delay and JS redirect as a fallback for older/stubborn browsers
echo '<!DOCTYPE html><html><head><title>Logging Out...</title></head><body>';
echo '<script type="text/javascript">';
echo 'history.replaceState(null, null, "login");'; // Replace current history entry with login
echo 'window.location.replace("login");'; // Force redirect, preventing back button to logged-in state
echo '</script>';
echo '<noscript>';
echo '<meta http-equiv="refresh" content="0;url=login" />'; // Fallback for no JS
echo '</noscript>';
echo '</body></html>';
exit(); 
header("Location: login.php"); // Redirect to login page after logout
exit();
?>