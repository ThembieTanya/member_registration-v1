<?php
require_once 'config.php';

if (!isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

// Log edit user page view
logActivity('View Edit User Form', 'User viewed the edit user form');

$all_areas = ['A', 'B', 'C', 'D', 'E'];

$userId = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: users.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $role = $_POST['role'] ?? 'viewer';
    $assigned_area = $_POST['assigned_area'] ?? null;
    $new_password = $_POST['new_password'] ?? '';
    
    // If role is admin, clear assigned area
    if ($role == 'admin') {
        $assigned_area = null;
    }
    
    try {
        if (!empty($new_password)) {
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, role = ?, assigned_area = ?, password = ? WHERE user_id = ?");
            $stmt->execute([$full_name, $role, $assigned_area, $hashedPassword, $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, role = ?, assigned_area = ? WHERE user_id = ?");
            $stmt->execute([$full_name, $role, $assigned_area, $userId]);
        }
        
        // Log user edit
        logActivity('Edit User', 'Updated user: ' . $user['username'] . ' (New Role: ' . $role . ', New Area: ' . ($assigned_area ?? 'All') . ')');
        
        $_SESSION['message'] = 'User updated successfully!';
        header('Location: users.php');
        exit;
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        .header { background: #2c3e50; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .header a { color: white; text-decoration: none; margin-left: 20px; }
        .header a:hover { text-decoration: underline; }
        .container { max-width: 500px; margin: 50px auto; padding: 0 20px; }
        .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h2 { margin-bottom: 20px; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236c757d' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 35px;
        }
        .btn { padding: 10px 20px; background: #27ae60; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #229954; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        .alert { padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .help-text {
            display: block;
            color: #6c757d;
            font-size: 12px;
            margin-top: 3px;
        }
        .readonly-field {
            background: #f0f0f0;
            cursor: not-allowed;
        }
        .area-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            background: #e8f4fd;
            color: #2c3e50;
        }
    </style>
    <script>
        function toggleAreaField() {
            var role = document.getElementById('roleSelect').value;
            var areaField = document.getElementById('areaField');
            if (role === 'admin') {
                areaField.style.display = 'none';
                document.getElementById('assigned_area').value = '';
            } else {
                areaField.style.display = 'block';
            }
        }
    </script>
</head>
<body>
    <div class="header">
        <h1>Edit User</h1>
        <div>
            <a href="dashboard.php">Dashboard</a>
            <a href="users.php">Manage Users</a>
            <a href="activity_log.php">Activity Log</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <h2>Edit User: <?= htmlspecialchars($user['username']) ?></h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Username (Cannot be changed)</label>
                    <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled class="readonly-field">
                </div>
                
                <div class="form-group">
                    <label>Full Name <span style="color: red;">*</span></label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Role <span style="color: red;">*</span></label>
                    <select name="role" id="roleSelect" required onchange="toggleAreaField()">
                        <option value="viewer" <?= $user['role'] == 'viewer' ? 'selected' : '' ?>>Viewer (View assigned area only)</option>
                        <option value="editor" <?= $user['role'] == 'editor' ? 'selected' : '' ?>>Editor (Manage assigned area)</option>
                        <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin (Full Access - All Areas)</option>
                    </select>
                </div>
                
                <div class="form-group" id="areaField" <?= ($user['role'] == 'admin') ? 'style="display: none;"' : '' ?>>
                    <label>Assigned Area <?php if ($user['role'] != 'admin'): ?><span style="color: red;">*</span><?php endif; ?></label>
                    <select name="assigned_area" id="assigned_area">
                        <option value="">-- Select Area --</option>
                        <?php foreach ($all_areas as $area): ?>
                            <option value="<?= $area ?>" <?= ($user['assigned_area'] == $area) ? 'selected' : '' ?>>
                                <?= $area ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="help-text">Select the area this user will manage or view</span>
                    <?php if ($user['assigned_area'] && $user['role'] != 'admin'): ?>
                        <span class="help-text" style="color: #28a745;">Current: <span class="area-badge">📍 <?= htmlspecialchars($user['assigned_area']) ?></span></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label>New Password (Leave blank to keep current)</label>
                    <input type="password" name="new_password" placeholder="Enter new password">
                    <span class="help-text">Only fill this field if you want to change the password</span>
                </div>
                
                <button type="submit" class="btn">Update User</button>
                <a href="users.php" class="btn btn-secondary" style="text-decoration: none; display: inline-block; margin-left: 10px;">Cancel</a>
            </form>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            toggleAreaField();
        });
    </script>
</body>
</html>