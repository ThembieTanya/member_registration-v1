<?php
require_once 'config.php';

// ONLY ADMIN CAN ACCESS
if (!isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

// Log CSV export
logActivity('Export CSV', 'User exported member data as CSV');

// Get all members
$members = $pdo->query("SELECT * FROM members ORDER BY member_id DESC")->fetchAll();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="members_export_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Headers
fputcsv($output, [
    'Member ID', 'Area Name', 'Full Name', 'ID Number', 'Gender', 'Date of Birth',
    'Marital Status', 'Phone 1', 'Phone 2', 'Zone Department', 'Residential Address',
    'Company Name', 'Occupation', 'Email', 'Spouse Name', 'Spouse ID', 'Spouse DOB',
    'Spouse Profession', 'Spouse Email', 'Spouse Phone 1', 'Spouse Phone 2',
    'Spouse Zone', 'Next of Kin', 'Next of Kin Relationship', 'Next of Kin Phone',
    'Received By', 'Date Received', 'Registration Date'
]);

// Data rows
foreach ($members as $member) {
    fputcsv($output, [
        $member['member_id'],
        $member['area_name'],
        $member['full_name'],
        $member['id_number'],
        $member['gender'],
        $member['date_of_birth'],
        $member['marital_status'],
        $member['phone1'],
        $member['phone2'],
        $member['zone_department'],
        $member['residential_address'],
        $member['company_name'],
        $member['occupation'],
        $member['email'],
        $member['spouse_full_name'],
        $member['spouse_id_number'],
        $member['spouse_dob'],
        $member['spouse_profession'],
        $member['spouse_email'],
        $member['spouse_phone1'],
        $member['spouse_phone2'],
        $member['spouse_zone_department'],
        $member['next_of_kin_name'],
        $member['next_of_kin_relationship'],
        $member['next_of_kin_phone'],
        $member['received_by'],
        $member['date_received'],
        $member['created_at']
    ]);
}

fclose($output);
exit;
?>