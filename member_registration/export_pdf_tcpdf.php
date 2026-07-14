<?php
require_once 'config.php';

// ONLY ADMIN CAN ACCESS
if (!isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

// Include TCPDF library
require_once('tcpdf/tcpdf.php');

// Get all members
$members = $pdo->query("SELECT * FROM members ORDER BY member_id DESC")->fetchAll();
$children = $pdo->query("SELECT * FROM children ORDER BY member_id, serial_number")->fetchAll();

// Group children by member_id
$children_by_member = [];
foreach ($children as $child) {
    $children_by_member[$child['member_id']][] = $child;
}

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Member Registration System');
$pdf->SetAuthor('System Admin');
$pdf->SetTitle('Member Registration Report');
$pdf->SetSubject('Member Registration Report');
$pdf->SetKeywords('members, registration, report');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Title
$pdf->SetFont('helvetica', 'B', 20);
$pdf->Cell(0, 15, 'MEMBER REGISTRATION REPORT', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 10, 'Generated: ' . date('F d, Y H:i:s'), 0, 1, 'C');
$pdf->Cell(0, 10, 'Total Members: ' . count($members), 0, 1, 'C');
$pdf->Ln(10);

// Loop through members
$counter = 1;
foreach ($members as $member) {
    // Member header
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, '# ' . $counter . ' - ' . $member['full_name'], 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 11);
    
    // Member details
    $pdf->Cell(50, 7, 'Area Name:', 0, 0, 'L');
    $pdf->Cell(0, 7, $member['area_name'] ?? 'N/A', 0, 1, 'L');
    
    $pdf->Cell(50, 7, 'ID Number:', 0, 0, 'L');
    $pdf->Cell(0, 7, $member['id_number'] ?? 'N/A', 0, 1, 'L');
    
    $pdf->Cell(50, 7, 'Gender:', 0, 0, 'L');
    $pdf->Cell(0, 7, $member['gender'] ?? 'N/A', 0, 1, 'L');
    
    $pdf->Cell(50, 7, 'Date of Birth:', 0, 0, 'L');
    $pdf->Cell(0, 7, $member['date_of_birth'] ? date('F d, Y', strtotime($member['date_of_birth'])) : 'N/A', 0, 1, 'L');
    
    $pdf->Cell(50, 7, 'Marital Status:', 0, 0, 'L');
    $pdf->Cell(0, 7, $member['marital_status'] ?? 'N/A', 0, 1, 'L');
    
    $pdf->Cell(50, 7, 'Phone:', 0, 0, 'L');
    $pdf->Cell(0, 7, $member['phone1'] ?? 'N/A', 0, 1, 'L');
    
    $pdf->Cell(50, 7, 'Department:', 0, 0, 'L');
    $pdf->Cell(0, 7, $member['zone_department'] ?? 'N/A', 0, 1, 'L');
    
    $pdf->Cell(50, 7, 'Profession:', 0, 0, 'L');
    $pdf->Cell(0, 7, $member['occupation'] ?? 'N/A', 0, 1, 'L');
    
    $pdf->Cell(50, 7, 'Email:', 0, 0, 'L');
    $pdf->Cell(0, 7, $member['email'] ?? 'N/A', 0, 1, 'L');
    
    // Spouse details
    if (!empty($member['spouse_full_name'])) {
        $pdf->Ln(3);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 7, 'Spouse Details:', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(50, 7, 'Name:', 0, 0, 'L');
        $pdf->Cell(0, 7, $member['spouse_full_name'], 0, 1, 'L');
        $pdf->Cell(50, 7, 'Phone:', 0, 0, 'L');
        $pdf->Cell(0, 7, $member['spouse_phone1'] ?? 'N/A', 0, 1, 'L');
        $pdf->Cell(50, 7, 'Profession:', 0, 0, 'L');
        $pdf->Cell(0, 7, $member['spouse_profession'] ?? 'N/A', 0, 1, 'L');
    }
    
    // Children
    if (isset($children_by_member[$member['member_id']]) && count($children_by_member[$member['member_id']]) > 0) {
        $pdf->Ln(3);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 7, 'Children:', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 11);
        
        // Table header
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(20, 7, '#', 1, 0, 'C', true);
        $pdf->Cell(60, 7, 'Full Name', 1, 0, 'C', true);
        $pdf->Cell(40, 7, 'Date of Birth', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Gender', 1, 0, 'C', true);
        $pdf->Cell(0, 7, 'Profession/Zone', 1, 1, 'C', true);
        
        foreach ($children_by_member[$member['member_id']] as $child) {
            $pdf->Cell(20, 7, $child['serial_number'], 1, 0, 'C');
            $pdf->Cell(60, 7, $child['full_name'], 1, 0, 'L');
            $pdf->Cell(40, 7, $child['date_of_birth'] ? date('Y-m-d', strtotime($child['date_of_birth'])) : 'N/A', 1, 0, 'C');
            $pdf->Cell(30, 7, $child['gender'] ?? 'N/A', 1, 0, 'C');
            $pdf->Cell(0, 7, $child['profession_zone'] ?? 'N/A', 1, 1, 'L');
        }
    }
    
    $pdf->Ln(5);
    $pdf->Cell(0, 7, 'Registered: ' . date('Y-m-d H:i', strtotime($member['created_at'])), 0, 1, 'L');
    if ($member['received_by']) {
        $pdf->Cell(0, 7, 'Received by: ' . $member['received_by'], 0, 1, 'L');
    }
    
    $pdf->Ln(10);
    $counter++;
}

// Output PDF
$pdf->Output('member_registration_report_' . date('Y-m-d') . '.pdf', 'D');
exit;
?>