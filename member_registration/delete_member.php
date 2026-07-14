<?php
require_once 'config.php';

if (!isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$memberId = $_GET['id'] ?? 0;

if ($memberId) {
    // Get member info before deleting
    $stmt = $pdo->prepare("SELECT full_name, area_name FROM members WHERE member_id = ?");
    $stmt->execute([$memberId]);
    $member = $stmt->fetch();
    
    try {
        // Log before deleting
        if ($member) {
            logActivity('Delete Member', 'Deleted member: ' . $member['full_name'] . ' (ID: ' . $memberId . ', Area: ' . $member['area_name'] . ')');
        } else {
            logActivity('Delete Member', 'Attempted to delete non-existent member ID: ' . $memberId);
        }
        
        $stmt = $pdo->prepare("DELETE FROM members WHERE member_id = ?");
        $stmt->execute([$memberId]);
        $_SESSION['message'] = 'Member deleted successfully!';
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error deleting member: ' . $e->getMessage();
    }
}

header('Location: members.php');
exit;
?>