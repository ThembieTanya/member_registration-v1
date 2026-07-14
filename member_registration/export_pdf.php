<?php
require_once 'config.php';

// ONLY ADMIN CAN ACCESS
if (!isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

// Log PDF export
logActivity('Export PDF', 'User exported member data as PDF');

// Include FPDF
require_once('fpdf.php');

// Get all members
$members = $pdo->query("SELECT * FROM members ORDER BY member_id DESC")->fetchAll();

// Get children
$children = $pdo->query("SELECT * FROM children ORDER BY member_id, serial_number")->fetchAll();
$children_by_member = [];
foreach ($children as $child) {
    $children_by_member[$child['member_id']][] = $child;
}

// Create PDF class
class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'MEMBER REGISTRATION REPORT', 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, 'Generated: ' . date('F d, Y H:i:s'), 0, 1, 'C');
        $this->Cell(0, 6, 'Total Members: ' . $GLOBALS['total_members'], 0, 1, 'C');
        $this->Ln(5);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' | Member Registration System', 0, 0, 'C');
    }
}

$GLOBALS['total_members'] = count($members);

$pdf = new PDF();
$pdf->SetAutoPageBreak(true, 25);
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

$counter = 1;
foreach ($members as $member) {
    if ($pdf->GetY() > 250) {
        $pdf->AddPage();
    }
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, '# ' . $counter . ' - ' . $member['full_name'], 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    
    $pdf->SetX(10);
    $pdf->Cell(45, 6, 'Area Name:', 0, 0);
    $pdf->Cell(0, 6, $member['area_name'] ?? 'N/A', 0, 1);
    
    $pdf->SetX(10);
    $pdf->Cell(45, 6, 'ID Number:', 0, 0);
    $pdf->Cell(0, 6, $member['id_number'] ?? 'N/A', 0, 1);
    
    $pdf->SetX(10);
    $pdf->Cell(45, 6, 'Gender:', 0, 0);
    $pdf->Cell(0, 6, $member['gender'] ?? 'N/A', 0, 1);
    
    $pdf->SetX(10);
    $pdf->Cell(45, 6, 'Date of Birth:', 0, 0);
    $pdf->Cell(0, 6, $member['date_of_birth'] ? date('F d, Y', strtotime($member['date_of_birth'])) : 'N/A', 0, 1);
    
    $pdf->SetX(10);
    $pdf->Cell(45, 6, 'Marital Status:', 0, 0);
    $pdf->Cell(0, 6, $member['marital_status'] ?? 'N/A', 0, 1);
    
    $pdf->SetX(10);
    $pdf->Cell(45, 6, 'Phone:', 0, 0);
    $pdf->Cell(0, 6, $member['phone1'] ?? 'N/A', 0, 1);
    
    $pdf->SetX(10);
    $pdf->Cell(45, 6, 'Department:', 0, 0);
    $pdf->Cell(0, 6, $member['zone_department'] ?? 'N/A', 0, 1);
    
    $pdf->SetX(10);
    $pdf->Cell(45, 6, 'Profession:', 0, 0);
    $pdf->Cell(0, 6, $member['occupation'] ?? 'N/A', 0, 1);
    
    $pdf->SetX(10);
    $pdf->Cell(45, 6, 'Email:', 0, 0);
    $pdf->Cell(0, 6, $member['email'] ?? 'N/A', 0, 1);
    
    if (!empty($member['spouse_full_name'])) {
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetX(10);
        $pdf->Cell(0, 6, 'Spouse Details:', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetX(15);
        $pdf->Cell(40, 6, 'Name:', 0, 0);
        $pdf->Cell(0, 6, $member['spouse_full_name'], 0, 1);
        $pdf->SetX(15);
        $pdf->Cell(40, 6, 'Phone:', 0, 0);
        $pdf->Cell(0, 6, $member['spouse_phone1'] ?? 'N/A', 0, 1);
        $pdf->SetX(15);
        $pdf->Cell(40, 6, 'Profession:', 0, 0);
        $pdf->Cell(0, 6, $member['spouse_profession'] ?? 'N/A', 0, 1);
    }
    
    if (isset($children_by_member[$member['member_id']]) && count($children_by_member[$member['member_id']]) > 0) {
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetX(10);
        $pdf->Cell(0, 6, 'Children:', 0, 1);
        $pdf->SetFont('Arial', '', 9);
        
        $pdf->SetX(15);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(15, 6, '#', 1, 0, 'C', true);
        $pdf->Cell(55, 6, 'Full Name', 1, 0, 'L', true);
        $pdf->Cell(35, 6, 'Date of Birth', 1, 0, 'C', true);
        $pdf->Cell(25, 6, 'Gender', 1, 0, 'C', true);
        $pdf->Cell(0, 6, 'Profession/Zone', 1, 1, 'L', true);
        
        foreach ($children_by_member[$member['member_id']] as $child) {
            $pdf->SetX(15);
            $pdf->Cell(15, 6, $child['serial_number'], 1, 0, 'C');
            $pdf->Cell(55, 6, $child['full_name'], 1, 0, 'L');
            $pdf->Cell(35, 6, $child['date_of_birth'] ? date('Y-m-d', strtotime($child['date_of_birth'])) : 'N/A', 1, 0, 'C');
            $pdf->Cell(25, 6, $child['gender'] ?? 'N/A', 1, 0, 'C');
            $pdf->Cell(0, 6, $child['profession_zone'] ?? 'N/A', 1, 1, 'L');
        }
    }
    
    $pdf->Ln(3);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetX(10);
    $pdf->Cell(0, 5, 'Registered: ' . date('Y-m-d H:i', strtotime($member['created_at'])), 0, 1);
    if ($member['received_by']) {
        $pdf->SetX(10);
        $pdf->Cell(0, 5, 'Received by: ' . $member['received_by'], 0, 1);
    }
    
    $pdf->Ln(5);
    $counter++;
}

$pdf->Output('D', 'member_registration_report_' . date('Y-m-d') . '.pdf');
exit;
?>