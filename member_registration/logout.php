<?php
session_start();

// Log the logout before destroying session
if (isset($_SESSION['user_id'])) {
    require_once 'config.php';
    logActivity('Logout', 'User logged out');
}

session_destroy();
header('Location: login.php');
exit;
?>