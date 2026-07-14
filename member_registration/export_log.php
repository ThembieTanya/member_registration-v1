<?php
require_once 'config.php';

// ONLY ADMIN CAN ACCESS
if (!isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

// Get logs
$logs = $pdo->query("SELECT * FROM activity_log ORDER BY created_at DESC")->fetchAll();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="activity_log_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Headers
fputcsv($output, ['Log ID', 'User', 'Role', 'Action', 'Details', 'IP Address', 'Page', 'Date/Time']);

// Data rows
foreach ($logs as $log) {
    fputcsv($output, [
        $log['log_id'],
        $log['username'],
        $log['user_role'],
        $log['action'],
        $log['details'] ?? '',
        $log['ip_address'] ?? '',
        $log['page_accessed'] ?? '',
        $log['created_at']
    ]);
}

fclose($output);
exit;
?>