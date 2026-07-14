<?php
require_once 'config.php';

// ONLY ADMIN CAN ACCESS
if (!isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

// Get all members
$members = $pdo->query("SELECT * FROM members ORDER BY member_id DESC")->fetchAll();
$children = $pdo->query("SELECT * FROM children ORDER BY member_id, serial_number")->fetchAll();
$children_by_member = [];
foreach ($children as $child) {
    $children_by_member[$child['member_id']][] = $child;
}

// Calculate age
function calculateAge($dob) {
    if (empty($dob)) return 'N/A';
    $birthDate = new DateTime($dob);
    $today = new DateTime('today');
    $age = $birthDate->diff($today)->y;
    return $age;
}

// Get unique values for filters
$areas = $pdo->query("SELECT DISTINCT area_name FROM members WHERE area_name IS NOT NULL AND area_name != '' ORDER BY area_name")->fetchAll(PDO::FETCH_COLUMN);
$genders = ['M', 'F'];
$marital_statuses = ['Single', 'Customary Married', 'Civil Married', 'Widowed', 'Divorced'];
$professions = $pdo->query("SELECT DISTINCT occupation FROM members WHERE occupation IS NOT NULL AND occupation != '' ORDER BY occupation")->fetchAll(PDO::FETCH_COLUMN);
$departments = $pdo->query("SELECT DISTINCT zone_department FROM members WHERE zone_department IS NOT NULL AND zone_department != '' ORDER BY zone_department")->fetchAll(PDO::FETCH_COLUMN);

// Apply filters
$filtered_members = $members;
$selected_area = $_GET['area'] ?? '';
$selected_gender = $_GET['gender'] ?? '';
$selected_marital = $_GET['marital'] ?? '';
$selected_profession = $_GET['profession'] ?? '';
$selected_department = $_GET['department'] ?? '';
$sort_by = $_GET['sort'] ?? 'full_name';
$sort_order = $_GET['order'] ?? 'ASC';

if ($selected_area) {
    $filtered_members = array_filter($filtered_members, function($m) use ($selected_area) {
        return ($m['area_name'] ?? '') == $selected_area;
    });
}
if ($selected_gender) {
    $filtered_members = array_filter($filtered_members, function($m) use ($selected_gender) {
        return ($m['gender'] ?? '') == $selected_gender;
    });
}
if ($selected_marital) {
    $filtered_members = array_filter($filtered_members, function($m) use ($selected_marital) {
        return ($m['marital_status'] ?? '') == $selected_marital;
    });
}
if ($selected_profession) {
    $filtered_members = array_filter($filtered_members, function($m) use ($selected_profession) {
        return ($m['occupation'] ?? '') == $selected_profession;
    });
}
if ($selected_department) {
    $filtered_members = array_filter($filtered_members, function($m) use ($selected_department) {
        return ($m['zone_department'] ?? '') == $selected_department;
    });
}

// Sort members
usort($filtered_members, function($a, $b) use ($sort_by, $sort_order) {
    $val_a = $a[$sort_by] ?? '';
    $val_b = $b[$sort_by] ?? '';
    
    if ($sort_by == 'age') {
        $age_a = calculateAge($a['date_of_birth']);
        $age_b = calculateAge($b['date_of_birth']);
        return $age_a == 'N/A' ? 1 : ($age_b == 'N/A' ? -1 : ($age_a - $age_b));
    }
    
    if ($sort_by == 'date_of_birth') {
        return strtotime($val_a) - strtotime($val_b);
    }
    
    $result = strcasecmp($val_a, $val_b);
    return $sort_order == 'ASC' ? $result : -$result;
});

$export_format = $_GET['export'] ?? '';
$print_mode = isset($_GET['print']);

// Handle exports
if ($export_format == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="members_sorted_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, ['#', 'Full Name', 'Area', 'Gender', 'Age', 'DOB', 'Marital Status', 'Phone', 'Department', 'Profession', 'Email', 'Spouse', 'Children', 'Registration Date']);
    
    $count = 1;
    foreach ($filtered_members as $m) {
        $children_count = count($children_by_member[$m['member_id']] ?? []);
        fputcsv($output, [
            $count++,
            $m['full_name'],
            $m['area_name'] ?? '',
            $m['gender'] ?? '',
            calculateAge($m['date_of_birth']),
            $m['date_of_birth'] ?? '',
            $m['marital_status'] ?? '',
            $m['phone1'] ?? '',
            $m['zone_department'] ?? '',
            $m['occupation'] ?? '',
            $m['email'] ?? '',
            $m['spouse_full_name'] ?? '',
            $children_count,
            date('Y-m-d', strtotime($m['created_at']))
        ]);
    }
    fclose($output);
    exit;
}

// PDF Export using FPDF
if ($export_format == 'pdf') {
    // Include FPDF
    require_once('fpdf.php');
    
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
    
    $GLOBALS['total_members'] = count($filtered_members);
    
    $pdf = new PDF();
    $pdf->SetAutoPageBreak(true, 25);
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 10);
    
    $counter = 1;
    foreach ($filtered_members as $member) {
        if ($pdf->GetY() > 250) {
            $pdf->AddPage();
        }
        
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, '# ' . $counter . ' - ' . $member['full_name'], 0, 1, 'L');
        $pdf->SetFont('Arial', '', 10);
        
        $pdf->SetX(10);
        $pdf->Cell(45, 6, 'Area:', 0, 0);
        $pdf->Cell(0, 6, $member['area_name'] ?? 'N/A', 0, 1);
        
        $pdf->SetX(10);
        $pdf->Cell(45, 6, 'Gender:', 0, 0);
        $pdf->Cell(0, 6, $member['gender'] ?? 'N/A', 0, 1);
        
        $pdf->SetX(10);
        $pdf->Cell(45, 6, 'Age:', 0, 0);
        $pdf->Cell(0, 6, calculateAge($member['date_of_birth']), 0, 1);
        
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
            $pdf->Cell(0, 6, 'Spouse:', 0, 1);
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
        
        $children_count = count($children_by_member[$member['member_id']] ?? []);
        if ($children_count > 0) {
            $pdf->Ln(2);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetX(10);
            $pdf->Cell(0, 6, 'Children: ' . $children_count, 0, 1);
        }
        
        $pdf->Ln(3);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->SetX(10);
        $pdf->Cell(0, 5, 'Registered: ' . date('Y-m-d H:i', strtotime($member['created_at'])), 0, 1);
        
        $pdf->Ln(5);
        $counter++;
    }
    
    $pdf->Output('D', 'member_report_' . date('Y-m-d') . '.pdf');
    exit;
}

// If print mode, show print view
if ($print_mode) {
    // Print view code here
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Advanced Member Export</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        .header { background: #2c3e50; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .header a { color: white; text-decoration: none; margin-left: 20px; }
        .header a:hover { text-decoration: underline; }
        .container { max-width: 1300px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        h2 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-bottom: 20px; }
        
        .filter-section { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; margin: 15px 0; }
        .filter-group label { display: block; font-weight: bold; font-size: 13px; color: #555; margin-bottom: 3px; }
        .filter-group select, .filter-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        
        .btn { display: inline-block; padding: 8px 16px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; font-size: 13px; margin: 2px; }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #229954; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .btn-purple { background: #8e44ad; }
        .btn-purple:hover { background: #7d3c98; }
        .btn-dark { background: #2c3e50; }
        .btn-dark:hover { background: #1a252f; }
        
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { background: #2c3e50; color: white; padding: 10px; text-align: left; white-space: nowrap; }
        td { padding: 8px 10px; border-bottom: 1px solid #dee2e6; }
        tr:hover { background: #f8f9fa; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: bold; }
        .badge-male { background: #3498db; color: white; }
        .badge-female { background: #e74c3c; color: white; }
        
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; margin: 15px 0; }
        .stat-box { background: #e8f4fd; padding: 10px; border-radius: 6px; text-align: center; }
        .stat-box .number { font-size: 22px; font-weight: bold; color: #2c3e50; }
        .stat-box .label { font-size: 11px; color: #6c757d; }
        
        .actions { display: flex; gap: 10px; flex-wrap: wrap; margin: 15px 0; }
        
        .export-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        .export-buttons .btn {
            padding: 10px 20px;
            font-size: 14px;
            min-width: 120px;
            text-align: center;
        }
        
        @media print {
            .no-print { display: none; }
            th { background: #333 !important; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Advanced Member Export</h1>
        <div class="no-print">
            <a href="dashboard.php">Dashboard</a>
            <a href="members.php">Members</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="card no-print">
            <h2>🔍 Filter & Sort Members</h2>
            
            <form method="GET" action="">
                <div class="filter-section">
                    <div class="filter-group">
                        <label>Area</label>
                        <select name="area">
                            <option value="">All Areas</option>
                            <?php foreach ($areas as $area): ?>
                                <option value="<?= htmlspecialchars($area) ?>" <?= $selected_area == $area ? 'selected' : '' ?>><?= htmlspecialchars($area) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Gender</label>
                        <select name="gender">
                            <option value="">All Genders</option>
                            <option value="M" <?= $selected_gender == 'M' ? 'selected' : '' ?>>Male</option>
                            <option value="F" <?= $selected_gender == 'F' ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Marital Status</label>
                        <select name="marital">
                            <option value="">All Status</option>
                            <?php foreach ($marital_statuses as $status): ?>
                                <option value="<?= $status ?>" <?= $selected_marital == $status ? 'selected' : '' ?>><?= $status ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Profession</label>
                        <select name="profession">
                            <option value="">All Professions</option>
                            <?php foreach ($professions as $prof): ?>
                                <option value="<?= htmlspecialchars($prof) ?>" <?= $selected_profession == $prof ? 'selected' : '' ?>><?= htmlspecialchars($prof) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Department</label>
                        <select name="department">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= htmlspecialchars($dept) ?>" <?= $selected_department == $dept ? 'selected' : '' ?>><?= htmlspecialchars($dept) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Sort By</label>
                        <select name="sort">
                            <option value="full_name" <?= $sort_by == 'full_name' ? 'selected' : '' ?>>Name</option>
                            <option value="area_name" <?= $sort_by == 'area_name' ? 'selected' : '' ?>>Area</option>
                            <option value="gender" <?= $sort_by == 'gender' ? 'selected' : '' ?>>Gender</option>
                            <option value="age" <?= $sort_by == 'age' ? 'selected' : '' ?>>Age</option>
                            <option value="marital_status" <?= $sort_by == 'marital_status' ? 'selected' : '' ?>>Marital Status</option>
                            <option value="occupation" <?= $sort_by == 'occupation' ? 'selected' : '' ?>>Profession</option>
                            <option value="zone_department" <?= $sort_by == 'zone_department' ? 'selected' : '' ?>>Department</option>
                            <option value="created_at" <?= $sort_by == 'created_at' ? 'selected' : '' ?>>Registration Date</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Order</label>
                        <select name="order">
                            <option value="ASC" <?= $sort_order == 'ASC' ? 'selected' : '' ?>>Ascending</option>
                            <option value="DESC" <?= $sort_order == 'DESC' ? 'selected' : '' ?>>Descending</option>
                        </select>
                    </div>
                </div>
                
                <div class="actions">
                    <button type="submit" class="btn">🔍 Apply Filters</button>
                    <a href="export_advanced.php" class="btn btn-dark">🔄 Reset</a>
                </div>
                
                <div class="export-buttons" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #dee2e6;">
                    <span style="font-weight: bold; color: #555; margin-right: 10px;">Export As:</span>
                    <button type="submit" name="export" value="csv" class="btn btn-success">📊 CSV</button>
                    <button type="submit" name="export" value="pdf" class="btn btn-danger">📄 PDF</button>
                    <button onclick="window.print()" class="btn btn-purple">🖨️ Print</button>
                </div>
            </form>
        </div>
        
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <h2>📋 Member List</h2>
                <span style="color: #6c757d; font-size: 14px;">Total: <?= count($filtered_members) ?> members</span>
            </div>
            
            <div class="stats">
                <div class="stat-box">
                    <div class="number"><?= count($filtered_members) ?></div>
                    <div class="label">Total Members</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?= count(array_filter($filtered_members, fn($m) => ($m['gender'] ?? '') == 'M')) ?></div>
                    <div class="label">Males</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?= count(array_filter($filtered_members, fn($m) => ($m['gender'] ?? '') == 'F')) ?></div>
                    <div class="label">Females</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?= count(array_filter($filtered_members, fn($m) => !empty($m['spouse_full_name']))) ?></div>
                    <div class="label">Married</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?= count(array_filter($filtered_members, fn($m) => !empty($m['occupation']))) ?></div>
                    <div class="label">Employed</div>
                </div>
            </div>
            
            <?php if (empty($filtered_members)): ?>
                <p style="text-align: center; padding: 30px; color: #6c757d;">No members match the selected filters.</p>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Area</th>
                                <th>Gender</th>
                                <th>Age</th>
                                <th>Marital Status</th>
                                <th>Profession</th>
                                <th>Department</th>
                                <th>Phone</th>
                                <th>Children</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $count = 1; ?>
                            <?php foreach ($filtered_members as $member): ?>
                                <?php 
                                $child_count = count($children_by_member[$member['member_id']] ?? []);
                                $gender_class = ($member['gender'] == 'M') ? 'badge-male' : (($member['gender'] == 'F') ? 'badge-female' : '');
                                ?>
                                <tr>
                                    <td><?= $count++ ?></td>
                                    <td><strong><?= htmlspecialchars($member['full_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($member['area_name'] ?? '-') ?></td>
                                    <td>
                                        <?php if ($member['gender']): ?>
                                            <span class="badge <?= $gender_class ?>"><?= $member['gender'] ?></span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?= calculateAge($member['date_of_birth']) ?></td>
                                    <td><?= htmlspecialchars($member['marital_status'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($member['occupation'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($member['zone_department'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($member['phone1'] ?? '-') ?></td>
                                    <td><?= $child_count ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 15px; font-size: 12px; color: #6c757d; text-align: center;">
                Generated: <?= date('Y-m-d H:i:s') ?> | <?= count($filtered_members) ?> records
            </div>
        </div>
    </div>
</body>
</html>