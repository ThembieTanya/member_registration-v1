<?php
require_once 'config.php';

// ONLY ADMIN CAN ACCESS
if (!isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

// Get filter parameters
$action_filter = $_GET['action'] ?? '';
$user_filter = $_GET['user'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$query = "SELECT * FROM activity_log WHERE 1=1";
$params = [];

if ($action_filter) {
    $query .= " AND action = ?";
    $params[] = $action_filter;
}

if ($user_filter) {
    $query .= " AND username LIKE ?";
    $params[] = "%$user_filter%";
}

if ($date_from) {
    $query .= " AND DATE(created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $query .= " AND DATE(created_at) <= ?";
    $params[] = $date_to;
}

$query .= " ORDER BY created_at DESC LIMIT 1000";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get unique actions for filter
$actions = $pdo->query("SELECT DISTINCT action FROM activity_log ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Activity Log</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        .header { background: #2c3e50; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .header a { color: white; text-decoration: none; margin-left: 20px; }
        .header a:hover { text-decoration: underline; }
        .container { max-width: 1400px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h2 { margin-bottom: 20px; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .filter-form { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; align-items: flex-end; }
        .filter-form .form-group { display: flex; flex-direction: column; }
        .filter-form label { font-weight: bold; font-size: 12px; margin-bottom: 3px; color: #555; }
        .filter-form input, .filter-form select { padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; min-width: 150px; }
        .filter-form .btn { padding: 8px 20px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .filter-form .btn:hover { background: #2980b9; }
        .filter-form .btn-clear { background: #6c757d; }
        .filter-form .btn-clear:hover { background: #5a6268; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { background: #f8f9fa; text-align: left; padding: 10px; border-bottom: 2px solid #dee2e6; color: #2c3e50; white-space: nowrap; }
        td { padding: 10px; border-bottom: 1px solid #dee2e6; }
        tr:hover { background: #f8f9fa; }
        .badge-action { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; }
        .badge-login { background: #27ae60; color: white; }
        .badge-logout { background: #e74c3c; color: white; }
        .badge-view { background: #3498db; color: white; }
        .badge-add { background: #f39c12; color: white; }
        .badge-edit { background: #8e44ad; color: white; }
        .badge-delete { background: #e74c3c; color: white; }
        .badge-export { background: #1abc9c; color: white; }
        .badge-other { background: #95a5a6; color: white; }
        .badge-role { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .badge-admin { background: #e74c3c; color: white; }
        .badge-editor { background: #f39c12; color: white; }
        .badge-viewer { background: #3498db; color: white; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-admin { background: #e74c3c; color: white; }
        .badge-editor { background: #f39c12; color: white; }
        .badge-viewer { background: #3498db; color: white; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; margin-bottom: 20px; }
        .stat-box { background: #f8f9fa; padding: 10px; border-radius: 6px; text-align: center; }
        .stat-box .number { font-size: 20px; font-weight: bold; color: #2c3e50; }
        .stat-box .label { font-size: 11px; color: #6c757d; }
        .actions-cell { white-space: nowrap; }
        .btn { display: inline-block; padding: 4px 10px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; font-size: 11px; }
        .btn:hover { background: #2980b9; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .no-logs { text-align: center; padding: 40px; color: #6c757d; }
        .table-wrapper { overflow-x: auto; }
        .refresh-btn { background: #27ae60; padding: 8px 20px; border: none; border-radius: 4px; color: white; cursor: pointer; }
        .refresh-btn:hover { background: #229954; }
        .total-count { text-align: right; margin-top: 10px; font-size: 13px; color: #6c757d; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 Activity Log</h1>
        <div>
            <span>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></span>
            <span class="badge badge-<?= $_SESSION['role'] ?>"><?= ucfirst($_SESSION['role']) ?></span>
            <a href="dashboard.php">Dashboard</a>
            <a href="members.php">Members</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 20px;">
                <h2>User Activity Log</h2>
                <div>
                    <button onclick="window.location.href='activity_log.php'" class="refresh-btn">🔄 Refresh</button>
                    <a href="export_log.php" class="btn" style="padding: 8px 20px; font-size: 13px; background: #27ae60;">📥 Export CSV</a>
                    <a href="clear_log.php" class="btn" style="padding: 8px 20px; font-size: 13px; background: #e74c3c;" onclick="return confirm('Are you sure you want to clear all logs?')">🗑️ Clear Log</a>
                </div>
            </div>
            
            <!-- Filter Form -->
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label>Action</label>
                    <select name="action">
                        <option value="">All Actions</option>
                        <?php foreach ($actions as $action): ?>
                            <option value="<?= htmlspecialchars($action) ?>" <?= $action_filter == $action ? 'selected' : '' ?>>
                                <?= htmlspecialchars($action) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="user" placeholder="Search user..." value="<?= htmlspecialchars($user_filter) ?>">
                </div>
                <div class="form-group">
                    <label>Date From</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                </div>
                <div class="form-group">
                    <label>Date To</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn">🔍 Filter</button>
                    <a href="activity_log.php" class="btn btn-clear" style="text-align: center; padding: 8px 20px; margin-top: 3px;">Clear</a>
                </div>
            </form>
            
            <!-- Stats -->
            <?php 
            $total_logs = count($logs);
            $login_count = count(array_filter($logs, fn($l) => strpos($l['action'], 'Login') !== false));
            $view_count = count(array_filter($logs, fn($l) => strpos($l['action'], 'View') !== false));
            $add_count = count(array_filter($logs, fn($l) => strpos($l['action'], 'Add') !== false || strpos($l['action'], 'Register') !== false));
            ?>
            <div class="stats">
                <div class="stat-box">
                    <div class="number"><?= $total_logs ?></div>
                    <div class="label">Total Activities</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?= $login_count ?></div>
                    <div class="label">Logins</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?= $view_count ?></div>
                    <div class="label">Views</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?= $add_count ?></div>
                    <div class="label">Adds/Registrations</div>
                </div>
            </div>
            
            <!-- Log Table -->
            <?php if ($logs): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>IP Address</th>
                                <th>Page</th>
                                <th>Date/Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $count = 1; ?>
                            <?php foreach ($logs as $log): ?>
                                <?php 
                                $action_class = 'badge-other';
                                if (strpos($log['action'], 'Login') !== false) $action_class = 'badge-login';
                                elseif (strpos($log['action'], 'Logout') !== false) $action_class = 'badge-logout';
                                elseif (strpos($log['action'], 'View') !== false) $action_class = 'badge-view';
                                elseif (strpos($log['action'], 'Add') !== false || strpos($log['action'], 'Register') !== false) $action_class = 'badge-add';
                                elseif (strpos($log['action'], 'Edit') !== false || strpos($log['action'], 'Update') !== false) $action_class = 'badge-edit';
                                elseif (strpos($log['action'], 'Delete') !== false) $action_class = 'badge-delete';
                                elseif (strpos($log['action'], 'Export') !== false) $action_class = 'badge-export';
                                ?>
                                <tr>
                                    <td><?= $count++ ?></td>
                                    <td><strong><?= htmlspecialchars($log['username']) ?></strong></td>
                                    <td><span class="badge-role badge-<?= $log['user_role'] ?>"><?= ucfirst($log['user_role']) ?></span></td>
                                    <td><span class="badge-action <?= $action_class ?>"><?= htmlspecialchars($log['action']) ?></span></td>
                                    <td><?= htmlspecialchars($log['details'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($log['ip_address'] ?? '-') ?></td>
                                    <td style="font-size: 11px; max-width: 150px; word-break: break-all;"><?= htmlspecialchars(basename($log['page_accessed'] ?? '-')) ?></td>
                                    <td style="white-space: nowrap;"><?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="total-count">Total: <strong><?= $total_logs ?></strong> records</div>
            <?php else: ?>
                <div class="no-logs">
                    <h3>📭 No activity logs found</h3>
                    <p style="color: #6c757d; margin-top: 5px;">Activity will be logged when users interact with the system.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>