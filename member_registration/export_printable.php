<?php
require_once 'config.php';

if (!canView()) {
    header('Location: login.php');
    exit;
}

$members = $pdo->query("SELECT * FROM members ORDER BY full_name")->fetchAll();
$print_mode = isset($_GET['print']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Member Directory - Printable</title>
    <style>
        @media print {
            body { margin: 0.3in; font-size: 10px; }
            .no-print { display: none; }
            .page-break { page-break-after: always; }
        }
        @page { size: A4; margin: 0.5in; }
        
        body { font-family: Arial, sans-serif; padding: 20px; background: white; }
        .no-print { text-align: center; margin-bottom: 20px; }
        .btn { display: inline-block; padding: 8px 16px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; margin: 3px; }
        .btn:hover { background: #2980b9; }
        .btn-purple { background: #8e44ad; }
        .btn-dark { background: #2c3e50; }
        
        .header { text-align: center; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { font-size: 20px; text-transform: uppercase; }
        .header p { color: #6c757d; font-size: 12px; }
        
        .member-list { column-count: 2; column-gap: 30px; }
        .member-item { break-inside: avoid; margin-bottom: 15px; border: 1px solid #dee2e6; border-radius: 4px; padding: 10px; }
        .member-item h3 { font-size: 13px; color: #2c3e50; margin-bottom: 3px; }
        .member-item p { font-size: 11px; color: #555; margin: 2px 0; }
        .member-item .details { display: grid; grid-template-columns: 1fr 1fr; gap: 2px 10px; }
        .member-item .details span { font-size: 10px; color: #6c757d; }
        
        .footer { text-align: center; margin-top: 20px; padding-top: 10px; border-top: 1px solid #dee2e6; font-size: 10px; color: #6c757d; }
    </style>
</head>
<body>
    <?php if (!$print_mode): ?>
    <div class="no-print">
        <a href="export_data.php" class="btn">⬅️ Back</a>
        <button onclick="window.print()" class="btn btn-purple">🖨️ Print</button>
        <button onclick="window.location.href='export_printable.php?print=1'" class="btn btn-dark">📄 Print Preview</button>
    </div>
    <?php endif; ?>
    
    <div class="header">
        <h1>Member Directory</h1>
        <p>Total Members: <?= count($members) ?> | Generated: <?= date('F d, Y') ?></p>
    </div>
    
    <div class="member-list">
        <?php foreach ($members as $member): ?>
            <div class="member-item">
                <h3><?= htmlspecialchars($member['full_name']) ?></h3>
                <div class="details">
                    <span>ID: <?= htmlspecialchars($member['id_number'] ?? 'N/A') ?></span>
                    <span>Gender: <?= $member['gender'] ?></span>
                    <span>Phone: <?= htmlspecialchars($member['phone1'] ?? 'N/A') ?></span>
                    <span>Zone: <?= htmlspecialchars($member['zone_department'] ?? 'N/A') ?></span>
                    <span>Email: <?= htmlspecialchars($member['email'] ?? 'N/A') ?></span>
                    <span>Registered: <?= date('Y-m-d', strtotime($member['created_at'])) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="footer">
        <p>Member Registration System | Page 1 of 1</p>
    </div>
</body>
</html>