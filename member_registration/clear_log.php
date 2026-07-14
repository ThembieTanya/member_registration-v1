<?php
require_once 'config.php';

// ONLY ADMIN CAN ACCESS
if (!isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

// Clear all logs
try {
    $pdo->exec("TRUNCATE TABLE activity_log");
    $_SESSION['message'] = 'Activity log cleared successfully!';
} catch (Exception $e) {
    $_SESSION['error'] = 'Error clearing log: ' . $e->getMessage();
}

header('Location: activity_log.php');
exit;
?>