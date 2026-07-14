<?php
require_once 'config.php';

// ONLY ADMIN CAN ACCESS
if (!isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

// ... rest of your code

require_once 'config.php';

if (!canView()) {
    header('Location: login.php');
    exit;
}

// Get all members with their children
$members = $pdo->query("SELECT * FROM members ORDER BY member_id DESC")->fetchAll();
$children = $pdo->query("SELECT * FROM children ORDER BY member_id, serial_number")->fetchAll();

// Group children by member_id
$children_by_member = [];
foreach ($children as $child) {
    $children_by_member[$child['member_id']][] = $child;
}

// Build data structure
$data = [];
foreach ($members as $member) {
    $member_data = $member;
    $member_data['children'] = $children_by_member[$member['member_id']] ?? [];
    $data[] = $member_data;
}

$json_data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

if (isset($_GET['view'])) {
    header('Content-Type: application/json');
    echo $json_data;
    exit;
}

// Download as file
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="members_export_' . date('Y-m-d') . '.json"');
echo $json_data;
exit;
?>