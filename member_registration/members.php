<?php
require_once 'config.php';

if (!canView()) {
    header('Location: login.php');
    exit;
}

// Log members list view
logActivity('View Members List', 'User viewed the members list');

$user_role = $_SESSION['role'] ?? 'viewer';
$is_admin = ($user_role == 'admin');
$is_editor = ($user_role == 'editor' || $user_role == 'admin');
$user_area = getUserArea();

// Build query with area filter for editors
$query = "SELECT * FROM members";
$params = [];

if ($is_editor && !$is_admin) {
    $query .= " WHERE area_name = ?";
    $params[] = $user_area;
}

$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$members = $stmt->fetchAll();

// Get children for all members
$children = $pdo->query("SELECT * FROM children ORDER BY member_id, serial_number")->fetchAll();
$children_by_member = [];
foreach ($children as $child) {
    $children_by_member[$child['member_id']][] = $child;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Members</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        .header { background: #2c3e50; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .header a { color: white; text-decoration: none; margin-left: 20px; }
        .header a:hover { text-decoration: underline; }
        .container { max-width: 1400px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); overflow-x: auto; }
        
        h2 { margin-bottom: 20px; color: #2c3e50; }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 13px;
            min-width: 1200px;
        }
        
        th { 
            background: #f8f9fa; 
            text-align: left; 
            padding: 10px 8px; 
            border-bottom: 2px solid #dee2e6;
            white-space: nowrap;
            font-weight: 600;
            color: #2c3e50;
        }
        
        td { 
            padding: 10px 8px; 
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
            word-break: break-word;
        }
        
        tr:hover { 
            background: #f8f9fa; 
        }
        
        .col-id { width: 4%; }
        .col-name { width: 12%; }
        .col-area { width: 8%; }
        .col-phone { width: 10%; }
        .col-dept { width: 10%; }
        .col-idnum { width: 10%; }
        .col-gender { width: 6%; }
        .col-prof { width: 10%; }
        .col-email { width: 12%; }
        .col-children { width: 6%; }
        .col-date { width: 10%; }
        .col-actions { width: 12%; }
        
        .btn { 
            display: inline-block; 
            padding: 4px 10px; 
            background: #3498db; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px; 
            font-size: 11px; 
            border: none;
            cursor: pointer;
            white-space: nowrap;
        }
        .btn:hover { background: #2980b9; }
        .btn-edit { background: #27ae60; }
        .btn-edit:hover { background: #229954; }
        .btn-delete { background: #e74c3c; }
        .btn-delete:hover { background: #c0392b; }
        .btn-view { background: #3498db; }
        .btn-view:hover { background: #2980b9; }
        
        .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .badge-admin { background: #e74c3c; color: white; }
        .badge-editor { background: #f39c12; color: white; }
        .badge-viewer { background: #3498db; color: white; }
        
        .role-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; margin-left: 10px; background: rgba(255,255,255,0.2); color: white; }
        .area-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            background: rgba(255,255,255,0.2);
            color: white;
        }
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
            font-size: 11px;
        }
        .child-item .dept {
            color: #6c757d;
            font-size: 10px;
        }
        
        .actions-cell {
            white-space: nowrap;
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
        }
        
        .gender-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .gender-male { background: #3498db; color: white; }
        .gender-female { background: #e74c3c; color: white; }
        
        .table-wrapper {
            overflow-x: auto;
        }
        
        @media (max-width: 1200px) {
            .container { padding: 0 10px; }
            table { font-size: 12px; }
            th, td { padding: 6px 5px; }
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
        <h1>Member List</h1>
        <div>
            <span>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></span>
            <span class="role-badge"><?= ucfirst($user_role) ?></span>
            <?php if ($user_area): ?>
                <span class="area-badge">📍 <?= htmlspecialchars($user_area) ?></span>
            <?php endif; ?>
            <a href="dashboard.php">Dashboard</a>
            <?php if (canEdit()): ?>
                <a href="register.php">New Registration</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <h2>All Members</h2>
            
            <?php if ($is_editor && !$is_admin): ?>
                <div class="area-info">
                    <strong>📍 Area Filter:</strong> You are viewing members from <strong><?= htmlspecialchars($user_area) ?></strong> area only.
                    <?php if (count($members) == 0): ?>
                        <span style="color: #856404;"> No members found in your area.</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($user_role == 'viewer'): ?>
                <div class="restricted-note">
                    <strong>🔒 Viewer Access:</strong> You can view <i>Name, Area, Phone Number, Department</i>, 
                    and <i>Children (18+) with Departments</i>. Contact an Administrator for full access.
                </div>
            <?php endif; ?>
            
            <?php if ($members): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th class="col-id">#</th>
                                <th class="col-name">Full Name</th>
                                <th class="col-area">Area</th>
                                <th class="col-phone">Phone</th>
                                <th class="col-dept">Department</th>
                                <?php if ($user_role != 'viewer'): ?>
                                    <th class="col-idnum">ID Number</th>
                                    <th class="col-gender">Gender</th>
                                    <th class="col-prof">Profession</th>
                                    <th class="col-email">Email</th>
                                    <th class="col-children">Children</th>
                                    <th class="col-date">Reg Date</th>
                                    <?php if (canEdit()): ?>
                                        <th class="col-actions">Actions</th>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <th class="col-children">Children (18+)</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $count = 1; ?>
                            <?php foreach ($members as $member): ?>
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
                                $gender_class = ($member['gender'] == 'M') ? 'gender-male' : (($member['gender'] == 'F') ? 'gender-female' : '');
                                ?>
                                <tr>
                                    <td class="col-id"><?= $count++ ?></td>
                                    <td class="col-name"><strong><?= htmlspecialchars($member['full_name']) ?></strong></td>
                                    <td class="col-area"><?= htmlspecialchars($member['area_name'] ?? 'N/A') ?></td>
                                    <td class="col-phone"><?= htmlspecialchars($member['phone1'] ?? 'N/A') ?></td>
                                    <td class="col-dept"><?= htmlspecialchars($member['zone_department'] ?? 'N/A') ?></td>
                                    
                                    <?php if ($user_role != 'viewer'): ?>
                                        <td class="col-idnum"><?= htmlspecialchars($member['id_number'] ?? 'N/A') ?></td>
                                        <td class="col-gender">
                                            <?php if ($gender_class): ?>
                                                <span class="gender-badge <?= $gender_class ?>"><?= $member['gender'] ?></span>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                        <td class="col-prof"><?= htmlspecialchars($member['occupation'] ?? 'N/A') ?></td>
                                        <td class="col-email"><?= htmlspecialchars($member['email'] ?? 'N/A') ?></td>
                                        <td class="col-children"><?= count($child_list) ?></td>
                                        <td class="col-date"><?= date('Y-m-d', strtotime($member['created_at'])) ?></td>
                                        <?php if (canEdit()): ?>
                                            <td class="col-actions">
                                                <div class="actions-cell">
                                                    <a href="view_member.php?id=<?= $member['member_id'] ?>" class="btn btn-view">👁️ View</a>
                                                    <?php if ($can_edit): ?>
                                                        <a href="edit_member.php?id=<?= $member['member_id'] ?>" class="btn btn-edit">✏️ Edit</a>
                                                    <?php endif; ?>
                                                    <?php if ($is_admin): ?>
                                                        <a href="delete_member.php?id=<?= $member['member_id'] ?>" class="btn btn-delete" onclick="return confirm('Are you sure?')">🗑️ Delete</a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <td class="col-children">
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
                
                <div style="margin-top: 15px; font-size: 13px; color: #6c757d; text-align: right;">
                    Total: <strong><?= count($members) ?></strong> members
                </div>
            <?php else: ?>
                <p style="text-align: center; padding: 30px; color: #6c757d;">
                    <?php if ($is_editor && !$is_admin): ?>
                        No members found in <strong><?= htmlspecialchars($user_area) ?></strong> area.
                    <?php else: ?>
                        No members registered yet.
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>