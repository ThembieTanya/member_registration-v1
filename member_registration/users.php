<?php
require_once 'config.php';

if (!isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

// Log users page view
logActivity('View Users', 'User viewed the user management page');

// Define all areas
$all_areas = ['A', 'B', 'C', 'D', 'E'];

// Get all users
$users = $pdo->query("SELECT * FROM users ORDER BY user_id")->fetchAll();

// Handle user deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $userId = $_GET['delete'];
    if ($userId != $_SESSION['user_id']) {
        // Get user info before deleting
        $stmt = $pdo->prepare("SELECT username, role, assigned_area FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user) {
            logActivity('Delete User', 'Deleted user: ' . $user['username'] . ' (Role: ' . $user['role'] . ', Area: ' . ($user['assigned_area'] ?? 'All') . ')');
        }
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $_SESSION['message'] = 'User deleted successfully!';
        header('Location: users.php');
        exit;
    } else {
        $_SESSION['error'] = 'You cannot delete your own account!';
        header('Location: users.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        .header { background: #2c3e50; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .header a { color: white; text-decoration: none; margin-left: 20px; }
        .header a:hover { text-decoration: underline; }
        .container { max-width: 1100px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #f8f9fa; text-align: left; padding: 12px; border-bottom: 2px solid #dee2e6; }
        td { padding: 12px; border-bottom: 1px solid #dee2e6; }
        .btn { display: inline-block; padding: 8px 15px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; font-size: 12px; }
        .btn:hover { background: #2980b9; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #229954; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-admin { background: #e74c3c; color: white; }
        .badge-editor { background: #f39c12; color: white; }
        .badge-viewer { background: #3498db; color: white; }
        .area-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            background: #e8f4fd;
            color: #2c3e50;
        }
        .area-badge-all {
            background: #d4edda;
            color: #155724;
        }
        .alert { padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .modal { display: none; position: fixed; z-index: 999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 5% auto; padding: 30px; border-radius: 8px; width: 500px; max-width: 90%; max-height: 90%; overflow-y: auto; }
        .modal-content h2 { margin-bottom: 20px; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .modal-content .form-group { margin-bottom: 15px; }
        .modal-content label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        .modal-content input, .modal-content select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .modal-content .btn { margin-top: 10px; }
        .modal-content .btn-secondary { background: #6c757d; }
        .modal-content .btn-secondary:hover { background: #5a6268; }
        .area-select-wrapper {
            position: relative;
        }
        .area-select-wrapper select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236c757d' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 35px;
        }
        .help-text {
            display: block;
            color: #6c757d;
            font-size: 12px;
            margin-top: 3px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Manage Users</h1>
        <div>
            <a href="dashboard.php">Dashboard</a>
            <a href="members.php">Members</a>
            <a href="activity_log.php">Activity Log</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 20px;">
                <h2>User Management</h2>
                <button class="btn btn-success" onclick="openAddUserModal()" style="font-size: 14px; padding: 10px 20px;">+ Add New User</button>
            </div>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Role</th>
                        <th>Assigned Area</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['user_id'] ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['full_name']) ?></td>
                            <td><span class="badge badge-<?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span></td>
                            <td>
                                <?php if ($user['role'] == 'admin'): ?>
                                    <span class="area-badge area-badge-all">🌍 All Areas</span>
                                <?php elseif ($user['assigned_area']): ?>
                                    <span class="area-badge">📍 <?= htmlspecialchars($user['assigned_area']) ?></span>
                                <?php else: ?>
                                    <span style="color: #999; font-size: 12px;">Not assigned</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('Y-m-d', strtotime($user['created_at'])) ?></td>
                            <td>
                                <a href="edit_user.php?id=<?= $user['user_id'] ?>" class="btn">Edit</a>
                                <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                    <a href="users.php?delete=<?= $user['user_id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 4px; font-size: 13px; color: #6c757d;">
                <strong>📌 Area Assignment:</strong>
                <ul style="margin-left: 20px; margin-top: 5px; line-height: 1.8;">
                    <li><strong>Admin:</strong> Can access ALL areas (no area restriction)</li>
                    <li><strong>Editor:</strong> Can only manage members in their assigned area</li>
                    <li><strong>Viewer:</strong> Can only view members in their assigned area</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <h2>➕ Add New User</h2>
            <form method="POST" action="add_user.php">
                <div class="form-group">
                    <label>Username <span style="color: red;">*</span></label>
                    <input type="text" name="username" placeholder="Enter username" required>
                </div>
                <div class="form-group">
                    <label>Full Name <span style="color: red;">*</span></label>
                    <input type="text" name="full_name" placeholder="Enter full name" required>
                </div>
                <div class="form-group">
                    <label>Password <span style="color: red;">*</span></label>
                    <input type="password" name="password" placeholder="Enter password" required>
                </div>
                <div class="form-group">
                    <label>Role <span style="color: red;">*</span></label>
                    <select name="role" id="roleSelect" required onchange="toggleAreaField()">
                        <option value="">Select Role</option>
                        <option value="admin">Admin (Full Access - All Areas)</option>
                        <option value="editor">Editor (Manage assigned area)</option>
                        <option value="viewer">Viewer (View assigned area only)</option>
                    </select>
                </div>
                <div class="form-group" id="areaField" style="display: none;">
                    <label>Assigned Area <span style="color: red;">*</span></label>
                    <div class="area-select-wrapper">
                        <select name="assigned_area">
                            <option value="">-- Select Area --</option>
                            <?php foreach ($all_areas as $area): ?>
                                <option value="<?= $area ?>"><?= $area ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <span class="help-text">Select the area this user will manage or view</span>
                </div>
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-success">Create User</button>
                    <button type="button" class="btn btn-secondary" onclick="closeAddUserModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openAddUserModal() {
            document.getElementById('addUserModal').style.display = 'block';
            toggleAreaField();
        }
        
        function closeAddUserModal() {
            document.getElementById('addUserModal').style.display = 'none';
        }
        
        function toggleAreaField() {
            var role = document.getElementById('roleSelect').value;
            var areaField = document.getElementById('areaField');
            if (role === 'admin') {
                areaField.style.display = 'none';
            } else {
                areaField.style.display = 'block';
            }
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('addUserModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>