<?php
function requireAdminAuth() {
    session_start();
    
    if (!isset($_SESSION['user']) || !$_SESSION['is_admin']) {
        header("Location: login.php");
        exit();
    }
    
    // Additional security checks
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1");
        exit();
    }
    
    $_SESSION['last_activity'] = time();
}