<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Log dashboard view
logActivity('View Dashboard', 'User viewed the dashboard');

$user_role = $_SESSION['role'] ?? 'viewer';
$is_admin = ($user_role == 'admin');
$is_editor = ($user_role == 'editor' || $user_role == 'admin');

// Get statistics
$totalMembers = $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
$recentMembers = $pdo->query("SELECT * FROM members ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Get children for viewer
$children = $pdo->query("SELECT * FROM children")->fetchAll();
$children_by_member = [];
foreach ($children as $child) {
    $children_by_member[$child['member_id']][] = $child;
}

// Get user area
$user_area = getUserArea();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Member Registration</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        
        .header { 
            background: #2c3e50; 
            color: white; 
            padding: 20px 30px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap;
            gap: 15px;
        }
        .header h1 { 
            font-size: 24px; 
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .nav { 
            display: flex; 
            align-items: center; 
            flex-wrap: wrap;
            gap: 8px;
        }
        .nav .welcome-text {
            font-size: 14px;
            opacity: 0.9;
            margin-right: 5px;
        }
        .nav a { 
            color: white; 
            text-decoration: none; 
            padding: 6px 14px; 
            border-radius: 4px; 
            font-size: 13px;
            transition: background 0.2s;
            white-space: nowrap;
        }
        .nav a:hover { 
            background: rgba(255,255,255,0.15); 
        }
        .nav .badge { 
            display: inline-block; 
            padding: 4px 12px; 
            border-radius: 20px; 
            font-size: 12px; 
            font-weight: bold; 
            margin: 0 5px;
        }
        .nav .badge-admin { background: #e74c3c; color: white; }
        .nav .badge-editor { background: #f39c12; color: white; }
        .nav .badge-viewer { background: #3498db; color: white; }
        .nav .area-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            background: rgba(255,255,255,0.2);
            color: white;
            margin: 0 5px;
        }
        
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stat-card h3 { color: #666; font-size: 14px; margin-bottom: 10px; }
        .stat-card .number { font-size: 32px; font-weight: bold; color: #2c3e50; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        
        /* IMPROVED TABLE STYLES */
        .table-wrapper {
            overflow-x: auto;
            margin-top: 10px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 14px;
        }
        th { 
            background: #f8f9fa; 
            text-align: left; 
            padding: 12px 15px; 
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #2c3e50;
            white-space: nowrap;
        }
        td { 
            padding: 12px 15px; 
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }
        tr:hover { 
            background: #f8f9fa; 
        }
        
        /* ACTION BUTTONS - IMPROVED */
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            align-items: center;
        }
        .action-buttons .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
            gap: 4px;
            min-width: 55px;
        }
        .action-buttons .btn-view { 
            background: #3498db; 
            color: white; 
        }
        .action-buttons .btn-view:hover { 
            background: #2980b9; 
            transform: translateY(-1px);
        }
        .action-buttons .btn-edit { 
            background: #27ae60; 
            color: white; 
        }
        .action-buttons .btn-edit:hover { 
            background: #229954; 
            transform: translateY(-1px);
        }
        .action-buttons .btn-delete { 
            background: #e74c3c; 
            color: white; 
        }
        .action-buttons .btn-delete:hover { 
            background: #c0392b; 
            transform: translateY(-1px);
        }
        .action-buttons .btn-disabled {
            background: #95a5a6;
            color: white;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .btn { display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px; }
        .btn:hover { background: #2980b9; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #229954; }
        
        .admin-tools { 
            display: grid; 
            grid-template-columns: repeat(4, 1fr); 
            gap: 15px; 
            margin-top: 15px; 
        }
        .admin-tools .btn { 
            text-align: center; 
            padding: 15px; 
            margin: 0; 
            width: 100%; 
            display: block;
            box-sizing: border-box;
            font-size: 14px;
        }
        .admin-tools div { width: 100%; }
        .admin-tools small { display: block; color: rgba(255,255,255,0.6); font-size: 11px; margin-top: 4px; text-align: center; }
        
        .btn-purple { background: #8e44ad; }
        .btn-purple:hover { background: #7d3c98; }
        .btn-warning { background: #f39c12; }
        .btn-warning:hover { background: #e67e22; }
        .btn-green { background: #27ae60; }
        .btn-green:hover { background: #229954; }
        .btn-red { background: #e74c3c; }
        .btn-red:hover { background: #c0392b; }
        
        .restricted-note {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .area-info {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .child-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            background: #27ae60;
            color: white;
        }
        .expand-btn {
            cursor: pointer;
            color: #3498db;
            text-decoration: underline;
            font-size: 12px;
        }
        .expand-btn:hover {
            color: #2980b9;
        }
        .children-list {
            display: none;
            margin-top: 5px;
        }
        .children-list.show {
            display: block;
        }
        .child-item {
            display: inline-block;
            background: #f0f0f0;
            padding: 2px 10px;
            border-radius: 12px;
            margin: 2px 4px 2px 0;
            font-size: 12px;
        }
        .child-item .dept {
            color: #6c757d;
            font-size: 10px;
        }
        
        /* Registration Date Column */
        .reg-date {
            font-size: 12px;
            color: #6c757d;
            white-space: nowrap;
        }
        
        @media (max-width: 768px) {
            .header { flex-direction: column; align-items: stretch; text-align: center; }
            .nav { justify-content: center; }
            .admin-tools { grid-template-columns: repeat(2, 1fr); }
            .action-buttons .btn { font-size: 11px; padding: 4px 10px; min-width: 45px; }
            th, td { padding: 8px 10px; font-size: 12px; }
        }
        @media (max-width: 480px) {
            .header { padding: 15px; }
            .nav a { padding: 5px 10px; font-size: 12px; }
            .admin-tools { grid-template-columns: 1fr; }
            .action-buttons { flex-direction: column; gap: 3px; }
            .action-buttons .btn { width: 100%; }
        }
    </style>
    <script>
        function toggleChildren(id) {
            var element = document.getElementById('children_' + id);
            if (element.classList.contains('show')) {
                element.classList.remove('show');
            } else {
                element.classList.add('show');
            }
        }
    </script>
</head>
<body>
    <div class="header">
        <h1>📋 Member Registration System</h1>
        <div class="nav">
            <span class="welcome-text">Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></span>
            <span class="badge badge-<?= $_SESSION['role'] ?>"><?= ucfirst($_SESSION['role']) ?></span>
            <?php if ($user_area): ?>
                <span class="area-badge">📍 <?= htmlspecialchars($user_area) ?></span>
            <?php endif; ?>
            <?php if (canEdit()): ?>
                <a href="register.php">New Registration</a>
            <?php endif; ?>
            <a href="members.php">View Members</a>
            <?php if (isAdmin()): ?>
                <a href="form_download.php">Registration Forms</a>
            <?php endif; ?>
            <?php if ($is_admin): ?>
                <a href="users.php">Manage Users</a>
                <a href="activity_log.php">Activity Log</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <?php if ($user_role == 'viewer'): ?>
            <div class="restricted-note">
                <strong>🔒 Viewer Access:</strong> You can view <i>Name, Area, Phone Number, Department</i>, 
                and <i>Children (18+) with Departments</i>. Contact an Administrator for full access.
            </div>
        <?php endif; ?>
        
        <?php if ($is_editor && !$is_admin && $user_area): ?>
            <div class="area-info">
                <strong>📍 Area Restricted:</strong> You can only manage members from <strong><?= htmlspecialchars($user_area) ?></strong> area.
            </div>
        <?php endif; ?>
        
        <div class="stats">
            <div class="stat-card">
                <h3>Total Members</h3>
                <div class="number"><?= $totalMembers ?></div>
            </div>
            <div class="stat-card">
                <h3>Recent Registrations</h3>
                <div class="number"><?= count($recentMembers) ?></div>
            </div>
            <div class="stat-card">
                <h3>Your Role</h3>
                <div class="number" style="font-size: 24px;"><?= ucfirst($_SESSION['role']) ?></div>
            </div>
            <?php if ($is_editor && !$is_admin && $user_area): ?>
                <div class="stat-card">
                    <h3>Your Area</h3>
                    <div class="number" style="font-size: 24px;">📍 <?= htmlspecialchars($user_area) ?></div>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($is_admin): ?>
            <div class="card" style="margin-top: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h2 style="color: white; border-bottom-color: rgba(255,255,255,0.3);">📊 Admin Tools</h2>
                <p style="color: rgba(255,255,255,0.8); margin-bottom: 15px;">Manage members, export data, and control system access</p>
                <div class="admin-tools">
                    <div>
                        <a href="export_advanced.php" class="btn btn-purple" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3);">
                            🔍 Advanced Export
                        </a>
                        <small>Filter, sort & export</small>
                    </div>
                    <div>
                        <a href="form_download.php" class="btn btn-green" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3);">
                            📥 Download Forms
                        </a>
                        <small>Access registration forms</small>
                    </div>
                    <div>
                        <a href="users.php" class="btn btn-red" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3);">
                            👤 Manage Users
                        </a>
                        <small>Control user access</small>
                    </div>
                    <div>
                        <a href="activity_log.php" class="btn" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3);">
                            📋 Activity Log
                        </a>
                        <small>View user activity</small>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Recent Members</h2>
            <?php if ($recentMembers): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Full Name</th>
                                <th>Area</th>
                                <th>Phone</th>
                                <th>Department</th>
                                <?php if ($user_role != 'viewer'): ?>
                                    <th>ID Number</th>
                                    <th>Reg Date</th>
                                    <?php if ($is_editor): ?>
                                        <th>Actions</th>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <th>Children (18+)</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $count = 1; ?>
                            <?php foreach ($recentMembers as $member): ?>
                                <?php 
                                $child_list = $children_by_member[$member['member_id']] ?? [];
                                $adult_children = [];
                                foreach ($child_list as $child) {
                                    if (!empty($child['profession_zone']) && !empty($child['date_of_birth'])) {
                                        $dob = new DateTime($child['date_of_birth']);
                                        $today = new DateTime('today');
                                        $age = $dob->diff($today)->y;
                                        if ($age >= 18) {
                                            $adult_children[] = $child;
                                        }
                                    }
                                }
                                $can_edit = canEditMember($member['area_name'] ?? '');
                                ?>
                                <tr>
                                    <td><?= $count++ ?></td>
                                    <td><strong><?= htmlspecialchars($member['full_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($member['area_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($member['phone1'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($member['zone_department'] ?? 'N/A') ?></td>
                                    
                                    <?php if ($user_role != 'viewer'): ?>
                                        <td><?= htmlspecialchars($member['id_number'] ?? 'N/A') ?></td>
                                        <td class="reg-date"><?= date('Y-m-d', strtotime($member['created_at'])) ?></td>
                                        <?php if ($is_editor): ?>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="view_member.php?id=<?= $member['member_id'] ?>" class="btn btn-view" title="View Member">
                                                        👁️ View
                                                    </a>
                                                    <?php if ($can_edit): ?>
                                                        <a href="edit_member.php?id=<?= $member['member_id'] ?>" class="btn btn-edit" title="Edit Member">
                                                            ✏️ Edit
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($is_admin): ?>
                                                        <a href="delete_member.php?id=<?= $member['member_id'] ?>" class="btn btn-delete" title="Delete Member" onclick="return confirm('Are you sure you want to delete this member?')">
                                                            🗑️ Delete
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <td>
                                            <?php if (count($adult_children) > 0): ?>
                                                <span class="expand-btn" onclick="toggleChildren(<?= $member['member_id'] ?>)">
                                                    <?= count($adult_children) ?> child<?= count($adult_children) > 1 ? 'ren' : '' ?> (18+)
                                                </span>
                                                <div class="children-list" id="children_<?= $member['member_id'] ?>">
                                                    <?php foreach ($adult_children as $child): ?>
                                                        <span class="child-item">
                                                            <?= htmlspecialchars($child['full_name']) ?>
                                                            <span class="dept">(<?= htmlspecialchars($child['profession_zone']) ?>)</span>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: #999; font-size: 12px;">None</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; padding: 30px; color: #6c757d;">No members registered yet.</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Quick Links</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; margin-top: 15px;">
                <a href="members.php" class="btn" style="text-align: center;">👥 View All Members</a>
                <?php if (canEdit()): ?>
                    <a href="register.php" class="btn btn-success" style="text-align: center;">➕ Add New Member</a>
                <?php endif; ?>
                <?php if (isAdmin()): ?>
                    <a href="form_download.php" class="btn btn-purple" style="text-align: center;">📋 Export Member Data</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>