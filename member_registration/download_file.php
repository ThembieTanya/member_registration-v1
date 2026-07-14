<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$file = $_GET['file'] ?? '';

// Security: Only allow PHP and SQL files
$allowed_extensions = ['php', 'sql', 'txt', 'html', 'css', 'js'];
$file_extension = pathinfo($file, PATHINFO_EXTENSION);

if (!in_array($file_extension, $allowed_extensions)) {
    die('Invalid file type');
}

// Prevent directory traversal
$file = basename($file);
$file_path = __DIR__ . '/' . $file;

if (!file_exists($file_path)) {
    die('File not found');
}

// Set headers for download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: must-revalidate');

readfile($file_path);
exit;
?>