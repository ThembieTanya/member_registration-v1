<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Log form download view
logActivity('View Forms', 'User viewed the forms download page');

$user_role = $_SESSION['role'] ?? 'viewer';
$is_admin = ($user_role == 'admin');
$is_editor = ($user_role == 'editor' || $user_role == 'admin');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Download Forms & Export Data</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        .header { background: #2c3e50; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .header a { color: white; text-decoration: none; margin-left: 20px; }
        .header a:hover { text-decoration: underline; }
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        h2 { margin-bottom: 20px; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .section { margin: 30px 0; }
        .section-title { font-size: 18px; font-weight: bold; color: #2c3e50; margin-bottom: 15px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .card-item { background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6; transition: all 0.3s; }
        .card-item:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .card-item h3 { color: #2c3e50; margin-bottom: 8px; }
        .card-item p { color: #6c757d; font-size: 14px; margin-bottom: 15px; }
        .btn { display: inline-block; padding: 8px 16px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; font-size: 13px; margin: 2px; }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #229954; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .btn-purple { background: #8e44ad; }
        .btn-purple:hover { background: #7d3c98; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; }
        .badge-pdf { background: #e74c3c; color: white; }
        .badge-csv { background: #27ae60; color: white; }
        .badge-excel { background: #217346; color: white; }
        .badge-html { background: #e34c26; color: white; }
        .badge-print { background: #8e44ad; color: white; }
        .badge-text { background: #2b579a; color: white; }
        .role-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; margin-left: 10px; }
        .role-badge-admin { background: #e74c3c; color: white; }
        .role-badge-editor { background: #f39c12; color: white; }
        .role-badge-viewer { background: #3498db; color: white; }
        .access-denied { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 30px; border-radius: 8px; text-align: center; }
        .access-granted { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .btn-group { display: flex; gap: 5px; flex-wrap: wrap; margin-top: 10px; }
        .btn-group .btn { flex: 1; min-width: 80px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 Download Forms & Export Data</h1>
        <div>
            <span>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></span>
            <span class="role-badge role-badge-<?= $user_role ?>"><?= ucfirst($user_role) ?></span>
            <a href="dashboard.php">Dashboard</a>
            <a href="members.php">View Members</a>
            <?php if ($is_editor): ?>
                <a href="register.php">New Registration</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="card" style="background: #f8f9fa;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <div>
                    <strong>Your Access Level:</strong>
                    <span class="role-badge role-badge-<?= $user_role ?>"><?= ucfirst($user_role) ?></span>
                </div>
                <div>
                    <?php if ($is_admin): ?>
                        <span class="access-granted">✅ Full Access - You can export data</span>
                    <?php else: ?>
                        <span class="access-denied" style="padding: 10px 20px;">⚠️ View Only - Export features are for Administrators only</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2>📥 Download Forms & Export Data</h2>   
            <div class="section">
                <div class="section-title">
                    📊 Member Data Export 
                    <span style="font-size: 12px; color: #e74c3c; font-weight: normal;">
                        <?php if ($is_admin): ?>
                            (Admin Access Only)
                        <?php else: ?>
                            🔒 <strong>Admin Only</strong>
                        <?php endif; ?>
                    </span>
                </div>
                
                <?php if ($is_admin): ?>
                    <div class="grid">
                        <div class="card-item" style="border-color: #e74c3c;">
                            <h3>📄 PDF Report</h3>
                            <p>Professional PDF with all member details</p>
                            <div class="btn-group">
                                <a href="export_pdf.php" class="btn btn-danger">📥 Download PDF</a>
                            </div>
                            <span class="badge badge-pdf">PDF</span>
                        </div>
                        
                        <div class="card-item" style="border-color: #27ae60;">
                            <h3>📊 CSV/Excel</h3>
                            <p>Open in Excel or Google Sheets</p>
                            <div class="btn-group">
                                <a href="export_csv.php" class="btn btn-success">📥 Download CSV</a>
                            </div>
                            <span class="badge badge-csv">CSV</span>
                            <span class="badge badge-excel">Excel</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="access-denied">
                        <h3 style="color: #721c24;">🔒 Access Denied</h3>
                        <p style="margin-top: 10px;">Only Administrators can export member data.</p>
                        <p style="margin-top: 5px; font-size: 14px;">Your role: <strong><?= ucfirst($user_role) ?></strong></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>