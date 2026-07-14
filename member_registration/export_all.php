<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Create ZIP file
$zip = new ZipArchive();
$zip_name = 'member_registration_files_' . date('Y-m-d') . '.zip';

if ($zip->open($zip_name, ZipArchive::CREATE) !== TRUE) {
    die('Could not create ZIP file');
}

// Add all PHP files
$files = glob('*.php');
foreach ($files as $file) {
    if (file_exists($file)) {
        $zip->addFile($file);
    }
}

// Add SQL files if any
$sql_files = glob('*.sql');
foreach ($sql_files as $file) {
    if (file_exists($file)) {
        $zip->addFile($file);
    }
}

$zip->close();

// Download the zip file
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zip_name . '"');
header('Content-Length: ' . filesize($zip_name));

readfile($zip_name);

// Delete the zip file after download
unlink($zip_name);
exit;
?>