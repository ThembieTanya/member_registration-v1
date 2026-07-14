<?php
require_once 'config.php';

// ONLY ADMIN CAN ACCESS THIS PAGE
if (!isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

// Rest of the code...

require_once 'config.php';

if (!canView()) {
    header('Location: login.php');
    exit;
}

$preset = $_GET['preset'] ?? '';
$format = $_GET['format'] ?? 'csv';

// Build query based on preset
$where = "1=1";
$order = "ORDER BY full_name ASC";

switch ($preset) {
    case 'by_area':
        $order = "ORDER BY area_name, full_name";
        break;
    case 'by_gender':
        $order = "ORDER BY gender, full_name";
        break;
    case 'by_age':
        $order = "ORDER BY date_of_birth ASC";
        break;
    case 'by_marital':
        $order = "ORDER BY marital_status, full_name";
        break;
    case 'by_profession':
        $order = "ORDER BY occupation, full_name";
        break;
    case 'by_department':
        $order = "ORDER BY zone_department, full_name";
        break;
    case 'married':
        $where = "marital_status IN ('Customary Married', 'Civil Married')";
        $order = "ORDER BY full_name";
        break;
    case 'single':
        $where = "marital_status = 'Single'";
        $order = "ORDER BY full_name";
        break;
    case 'male':
        $where = "gender = 'M'";
        $order = "ORDER BY full_name";
        break;
    case 'female':
        $where = "gender = 'F'";
        $order = "ORDER BY full_name";
        break;
    default:
        $order = "ORDER BY full_name";
}

$query = "SELECT * FROM members WHERE $where $order";
$members = $pdo->query($query)->fetchAll();

if ($format == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="members_' . $preset . '_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, ['#', 'Full Name', 'Area', 'Gender', 'Age', 'DOB', 'Marital Status', 'Phone', 'Department', 'Profession', 'Email']);
    
    $count = 1;
    foreach ($members as $m) {
        $age = !empty($m['date_of_birth']) ? (new DateTime($m['date_of_birth']))->diff(new DateTime('today'))->y : 'N/A';
        fputcsv($output, [
            $count++,
            $m['full_name'],
            $m['area_name'] ?? '',
            $m['gender'] ?? '',
            $age,
            $m['date_of_birth'] ?? '',
            $m['marital_status'] ?? '',
            $m['phone1'] ?? '',
            $m['zone_department'] ?? '',
            $m['occupation'] ?? '',
            $m['email'] ?? ''
        ]);
    }
    fclose($output);
    exit;
}

// Display results
?>
<!DOCTYPE html>
<html>
<head>
    <title>Export Presets</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        h2 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; margin: 5px; }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #229954; }
        .btn-warning { background: #f39c12; }
        .btn-warning:hover { background: #e67e22; }
        .btn-purple { background: #8e44ad; }
        .btn-purple:hover { background: #7d3c98; }
        .btn-dark { background: #2c3e50; }
        .btn-dark:hover { background: #1a252f; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #2c3e50; color: white; padding: 8px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #dee2e6; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: bold; }
        .badge-male { background: #3498db; color: white; }
        .badge-female { background: #e74c3c; color: white; }
        .badge-married { background: #27ae60; color: white; }
        .badge-single { background: #f39c12; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2>📊 Quick Export Presets</h2>
            <p style="color: #6c757d;">Download member data sorted by different criteria</p>
            
            <div class="grid">
                <a href="export_presets.php?preset=by_area&format=csv" class="btn btn-success">📥 By Area</a>
                <a href="export_presets.php?preset=by_gender&format=csv" class="btn btn-success">📥 By Gender</a>
                <a href="export_presets.php?preset=by_age&format=csv" class="btn btn-success">📥 By Age</a>
                <a href="export_presets.php?preset=by_marital&format=csv" class="btn btn-success">📥 By Marital Status</a>
                <a href="export_presets.php?preset=by_profession&format=csv" class="btn btn-success">📥 By Profession</a>
                <a href="export_presets.php?preset=by_department&format=csv" class="btn btn-success">📥 By Department</a>
                <a href="export_presets.php?preset=married&format=csv" class="btn btn-warning">📥 Married Only</a>
                <a href="export_presets.php?preset=single&format=csv" class="btn btn-warning">📥 Single Only</a>
                <a href="export_presets.php?preset=male&format=csv" class="btn btn-purple">📥 Males Only</a>
                <a href="export_presets.php?preset=female&format=csv" class="btn btn-purple">📥 Females Only</a>
            </div>
            
            <p style="margin-top: 15px;">
                <a href="export_advanced.php" class="btn btn-dark">🔍 Advanced Export with Filters</a>
                <a href="form_download.php" class="btn">⬅️ Back</a>
            </p>
        </div>
        
        <?php if ($members): ?>
        <div class="card">
            <h3>Preview: <?= ucfirst(str_replace('_', ' ', $preset)) ?></h3>
            <p style="color: #6c757d;">Showing <?= count($members) ?> members</p>
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
                    </tr>
                </thead>
                <tbody>
                    <?php $count = 1; foreach ($members as $m): ?>
                    <tr>
                        <td><?= $count++ ?></td>
                        <td><strong><?= htmlspecialchars($m['full_name']) ?></strong></td>
                        <td><?= htmlspecialchars($m['area_name'] ?? '-') ?></td>
                        <td><span class="badge badge-<?= strtolower($m['gender']) ?>"><?= $m['gender'] ?? '-' ?></span></td>
                        <td><?= !empty($m['date_of_birth']) ? (new DateTime($m['date_of_birth']))->diff(new DateTime('today'))->y : 'N/A' ?></td>
                        <td><?= htmlspecialchars($m['marital_status'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($m['occupation'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($m['zone_department'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>