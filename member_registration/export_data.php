<?php
require_once 'config.php';

if (!canView()) {
    header('Location: login.php');
    exit;
}

// Get all members with their children
$members = $pdo->query("SELECT * FROM members ORDER BY member_id DESC")->fetchAll();

// Get all children
$children = $pdo->query("SELECT * FROM children ORDER BY member_id, serial_number")->fetchAll();

// Group children by member_id
$children_by_member = [];
foreach ($children as $child) {
    $children_by_member[$child['member_id']][] = $child;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Export Member Data</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        .header { background: #2c3e50; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .header a { color: white; text-decoration: none; margin-left: 20px; }
        .header a:hover { text-decoration: underline; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { margin-bottom: 20px; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .export-options { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin: 20px 0; }
        .option-card { background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6; text-align: center; transition: all 0.3s; }
        .option-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .option-card h3 { color: #2c3e50; margin-bottom: 10px; }
        .option-card p { color: #6c757d; font-size: 14px; margin-bottom: 15px; }
        .btn { display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; font-size: 14px; margin: 3px; }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #229954; }
        .btn-warning { background: #f39c12; }
        .btn-warning:hover { background: #e67e22; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .btn-purple { background: #8e44ad; }
        .btn-purple:hover { background: #7d3c98; }
        .btn-dark { background: #2c3e50; }
        .btn-dark:hover { background: #1a252f; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-box { background: #e8f4fd; padding: 15px; border-radius: 8px; text-align: center; }
        .stat-box .number { font-size: 28px; font-weight: bold; color: #2c3e50; }
        .stat-box .label { color: #6c757d; font-size: 14px; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .badge-csv { background: #27ae60; color: white; }
        .badge-pdf { background: #e74c3c; color: white; }
        .badge-excel { background: #217346; color: white; }
        .badge-json { background: #f39c12; color: white; }
        .badge-print { background: #8e44ad; color: white; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Export Member Data</h1>
        <div>
            <a href="dashboard.php">Dashboard</a>
            <a href="members.php">View Members</a>
            <a href="register.php">New Registration</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <h2>📥 Export Member Records</h2>
            <p style="color: #6c757d; margin-bottom: 20px;">Export all member data from the database in various formats</p>
            
            <div class="stats">
                <div class="stat-box">
                    <div class="number"><?= count($members) ?></div>
                    <div class="label">Total Members</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?= count($children) ?></div>
                    <div class="label">Total Children</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?= date('Y-m-d') ?></div>
                    <div class="label">Export Date</div>
                </div>
            </div>
            
            <div class="export-options">
                <!-- PDF -->
                <div class="option-card">
                    <h3>📄 PDF Report</h3>
                    <p>Professional PDF report with all member details</p>
                    <div>
                        <a href="export_pdf.php" class="btn btn-danger" target="_blank">Generate PDF</a>
                        <a href="export_pdf.php?print=1" class="btn btn-purple" target="_blank">Print PDF</a>
                    </div>
                    <span class="badge badge-pdf">PDF</span>
                </div>
                
                <!-- Excel/CSV -->
                <div class="option-card">
                    <h3>📊 CSV/Excel</h3>
                    <p>Download as CSV for Excel or Google Sheets</p>
                    <div>
                        <a href="export_csv.php" class="btn btn-success">Download CSV</a>
                        <a href="export_csv.php?format=excel" class="btn btn-success">Excel Format</a>
                    </div>
                    <span class="badge badge-csv">CSV</span>
                    <span class="badge badge-excel">Excel</span>
                </div>
                
                <!-- JSON -->
                <div class="option-card">
                    <h3>📦 JSON Data</h3>
                    <p>Structured JSON format for developers</p>
                    <div>
                        <a href="export_json.php" class="btn btn-warning">Download JSON</a>
                        <a href="export_json.php?view=1" class="btn" target="_blank">View JSON</a>
                    </div>
                    <span class="badge badge-json">JSON</span>
                </div>
                
                <!-- Printable List -->
                <div class="option-card">
                    <h3>🖨️ Printable List</h3>
                    <p>Print-friendly member directory</p>
                    <div>
                        <a href="export_printable.php" class="btn btn-purple" target="_blank">View Printable</a>
                        <a href="export_printable.php?print=1" class="btn btn-dark" target="_blank">Print All</a>
                    </div>
                    <span class="badge badge-print">PRINT</span>
                </div>
            </div>
            
            <div style="margin-top: 30px; padding: 20px; background: #e8f4fd; border-radius: 8px; border-left: 4px solid #3498db;">
                <h4>💡 Available Export Formats</h4>
                <ul style="margin-left: 20px; line-height: 2;">
                    <li><strong>PDF Report:</strong> Professional formatted report with all member details</li>
                    <li><strong>CSV/Excel:</strong> Open in Microsoft Excel, Google Sheets, or any spreadsheet software</li>
                    <li><strong>JSON:</strong> Structured data format for developers and APIs</li>
                    <li><strong>Printable List:</strong> Optimized for printing member directories</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>