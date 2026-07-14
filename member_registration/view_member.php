<?php
require_once 'config.php';

if (!canView()) {
    header('Location: login.php');
    exit;
}

$memberId = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
$stmt->execute([$memberId]);
$member = $stmt->fetch();

// Log member view
if ($member) {
    logActivity('View Member', 'Viewed member: ' . $member['full_name'] . ' (ID: ' . $memberId . ')');
} else {
    logActivity('View Member', 'Attempted to view non-existent member ID: ' . $memberId);
}

if (!$member) {
    die("Member not found");
}

$user_role = $_SESSION['role'] ?? 'viewer';
$is_admin = ($user_role == 'admin');
$is_editor = ($user_role == 'editor' || $user_role == 'admin');

// Get children
$childrenStmt = $pdo->prepare("SELECT * FROM children WHERE member_id = ? ORDER BY serial_number");
$childrenStmt->execute([$memberId]);
$all_children = $childrenStmt->fetchAll();

// Filter children for viewer (over 18 with department)
$viewer_children = [];
foreach ($all_children as $child) {
    if (!empty($child['profession_zone']) && !empty($child['date_of_birth'])) {
        $dob = new DateTime($child['date_of_birth']);
        $today = new DateTime('today');
        $age = $dob->diff($today)->y;
        if ($age >= 18) {
            $viewer_children[] = $child;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Member</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        .header { background: #2c3e50; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .header a { color: white; text-decoration: none; margin-left: 20px; }
        .header a:hover { text-decoration: underline; }
        .container { max-width: 800px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .detail-row { display: flex; padding: 12px 0; border-bottom: 1px solid #eee; }
        .detail-label { font-weight: bold; width: 200px; color: #555; flex-shrink: 0; }
        .detail-value { flex: 1; color: #333; }
        h2 { margin-bottom: 20px; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .section-title { background: #f8f9fa; padding: 10px 15px; margin: 25px 0 15px; border-left: 4px solid #3498db; font-weight: bold; color: #2c3e50; }
        .btn { display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px; }
        .btn:hover { background: #2980b9; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-viewer { background: #3498db; color: white; }
        .role-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; margin-left: 10px; }
        .role-badge-viewer { background: #3498db; color: white; }
        .restricted-note {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .restricted-field {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 4px;
            margin: 10px 0;
            border-left: 3px solid #6c757d;
            color: #6c757d;
            font-style: italic;
        }
        .restricted-field .lock-icon {
            margin-right: 8px;
        }
        .btn-purple { background: #8e44ad; }
        .btn-purple:hover { background: #7d3c98; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #229954; }
        
        .child-item {
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 5px;
            border-left: 3px solid #27ae60;
        }
        .child-item .child-name {
            font-weight: bold;
        }
        .child-item .child-dept {
            color: #6c757d;
            font-size: 13px;
            margin-left: 10px;
        }
        .child-item .child-age {
            color: #f39c12;
            font-size: 12px;
            margin-left: 10px;
        }
        .child-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            background: #27ae60;
            color: white;
            margin-left: 5px;
        }
        .no-children {
            color: #999;
            font-style: italic;
        }
        .area-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            background: #e8f4fd;
            color: #2c3e50;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Member Details</h1>
        <div>
            <span>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></span>
            <span class="role-badge badge-<?= $user_role ?>"><?= ucfirst($user_role) ?></span>
            <?php if ($user_area = getUserArea()): ?>
                <span class="area-badge">📍 <?= htmlspecialchars($user_area) ?></span>
            <?php endif; ?>
            <a href="dashboard.php">Dashboard</a>
            <a href="members.php">View All Members</a>
            <?php if ($is_editor): ?>
                <a href="register.php">New Registration</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <h2><?= htmlspecialchars($member['full_name']) ?></h2>
            
            <?php if ($user_role == 'viewer'): ?>
                <div class="restricted-note">
                    <strong>🔒 Restricted View:</strong> As a Viewer, you can see <i>Name, Area, Phone Number, Department</i>, 
                    and <i>Children (18+) with Departments</i>.
                </div>
            <?php endif; ?>
            
            <!-- Always visible to all users -->
            <div class="section-title">Member Information</div>
            
            <div class="detail-row">
                <div class="detail-label">Full Name:</div>
                <div class="detail-value"><strong><?= htmlspecialchars($member['full_name']) ?></strong></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Area Name:</div>
                <div class="detail-value"><?= htmlspecialchars($member['area_name'] ?? 'N/A') ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Phone Number:</div>
                <div class="detail-value"><?= htmlspecialchars($member['phone1'] ?? 'N/A') ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Department:</div>
                <div class="detail-value"><?= htmlspecialchars($member['zone_department'] ?? 'N/A') ?></div>
            </div>
            
            <?php if ($user_role != 'viewer'): ?>
                <!-- Full details for Admin and Editor -->
                <div class="detail-row">
                    <div class="detail-label">ID Number:</div>
                    <div class="detail-value"><?= htmlspecialchars($member['id_number'] ?? 'N/A') ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Gender:</div>
                    <div class="detail-value"><?= $member['gender'] ?? 'N/A' ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Date of Birth:</div>
                    <div class="detail-value"><?= $member['date_of_birth'] ? date('F d, Y', strtotime($member['date_of_birth'])) : 'N/A' ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Marital Status:</div>
                    <div class="detail-value"><?= htmlspecialchars($member['marital_status'] ?? 'N/A') ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Phone Number 2:</div>
                    <div class="detail-value"><?= htmlspecialchars($member['phone2'] ?? 'N/A') ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Email Address:</div>
                    <div class="detail-value"><?= htmlspecialchars($member['email'] ?? 'N/A') ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Residential Address:</div>
                    <div class="detail-value"><?= nl2br(htmlspecialchars($member['residential_address'] ?? 'N/A')) ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Company Name:</div>
                    <div class="detail-value"><?= htmlspecialchars($member['company_name'] ?? 'N/A') ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Occupation/Profession:</div>
                    <div class="detail-value"><?= htmlspecialchars($member['occupation'] ?? 'N/A') ?></div>
                </div>
                
                <?php if (!empty($member['spouse_full_name'])): ?>
                    <div class="section-title">Spouse's Details</div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Full Name:</div>
                        <div class="detail-value"><?= htmlspecialchars($member['spouse_full_name']) ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">ID Number:</div>
                        <div class="detail-value"><?= htmlspecialchars($member['spouse_id_number'] ?? 'N/A') ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Date of Birth:</div>
                        <div class="detail-value"><?= $member['spouse_dob'] ? date('F d, Y', strtotime($member['spouse_dob'])) : 'N/A' ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Profession:</div>
                        <div class="detail-value"><?= htmlspecialchars($member['spouse_profession'] ?? 'N/A') ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Email Address:</div>
                        <div class="detail-value"><?= htmlspecialchars($member['spouse_email'] ?? 'N/A') ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Phone Number 1:</div>
                        <div class="detail-value"><?= htmlspecialchars($member['spouse_phone1'] ?? 'N/A') ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Phone Number 2:</div>
                        <div class="detail-value"><?= htmlspecialchars($member['spouse_phone2'] ?? 'N/A') ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Zone Department:</div>
                        <div class="detail-value"><?= htmlspecialchars($member['spouse_zone_department'] ?? 'N/A') ?></div>
                    </div>
                <?php endif; ?>
                
                <!-- All Children for Admin/Editor -->
                <?php if (count($all_children) > 0): ?>
                    <div class="section-title">Children</div>
                    <?php foreach ($all_children as $child): 
                        $dob = !empty($child['date_of_birth']) ? new DateTime($child['date_of_birth']) : null;
                        $age = $dob ? $dob->diff(new DateTime('today'))->y : null;
                    ?>
                        <div class="child-item">
                            <span class="child-name"><?= htmlspecialchars($child['full_name']) ?></span>
                            <span class="child-dept"><?= htmlspecialchars($child['profession_zone'] ?? 'No Department') ?></span>
                            <?php if ($age !== null): ?>
                                <span class="child-age">(Age: <?= $age ?>)</span>
                            <?php endif; ?>
                            <?php if ($age !== null && $age >= 18): ?>
                                <span class="child-badge">18+</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="detail-row">
                        <div class="detail-label">Children:</div>
                        <div class="detail-value" class="no-children">No children recorded</div>
                    </div>
                <?php endif; ?>
                
                <div class="section-title">Next of Kin / Emergency Contact</div>
                
                <div class="detail-row">
                    <div class="detail-label">Full Name:</div>
                    <div class="detail-value"><?= htmlspecialchars($member['next_of_kin_name'] ?? 'N/A') ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Relationship:</div>
                    <div class="detail-value"><?= htmlspecialchars($member['next_of_kin_relationship'] ?? 'N/A') ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Phone Number:</div>
                    <div class="detail-value"><?= htmlspecialchars($member['next_of_kin_phone'] ?? 'N/A') ?></div>
                </div>
                
                <div class="section-title">Office Use Only</div>
                
                <div class="detail-row">
                    <div class="detail-label">Received By:</div>
                    <div class="detail-value"><?= htmlspecialchars($member['received_by'] ?? 'N/A') ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Date Received:</div>
                    <div class="detail-value"><?= $member['date_received'] ? date('F d, Y', strtotime($member['date_received'])) : 'N/A' ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Registration Date:</div>
                    <div class="detail-value"><?= date('F d, Y H:i:s', strtotime($member['created_at'])) ?></div>
                </div>
            <?php else: ?>
                <!-- Restricted view for Viewer -->
                <div class="restricted-field">
                    <span class="lock-icon">🔒</span> 
                    <strong>Additional details are restricted for Viewers.</strong><br>
                    <span style="font-size: 13px; color: #6c757d;">Contact an Administrator for full access.</span>
                </div>
                
                <!-- Children over 18 with departments for Viewer -->
                <?php if (count($viewer_children) > 0): ?>
                    <div class="section-title">Children (18+ with Department)</div>
                    <?php foreach ($viewer_children as $child): 
                        $dob = !empty($child['date_of_birth']) ? new DateTime($child['date_of_birth']) : null;
                        $age = $dob ? $dob->diff(new DateTime('today'))->y : null;
                    ?>
                        <div class="child-item">
                            <span class="child-name"><?= htmlspecialchars($child['full_name']) ?></span>
                            <span class="child-dept"><?= htmlspecialchars($child['profession_zone']) ?></span>
                            <?php if ($age !== null): ?>
                                <span class="child-age">(Age: <?= $age ?>)</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="detail-row">
                        <div class="detail-label">Children (18+):</div>
                        <div class="detail-value" class="no-children">No children over 18 with departments</div>
                    </div>
                <?php endif; ?>
                
                <div class="detail-row">
                    <div class="detail-label">Registered:</div>
                    <div class="detail-value"><?= date('F d, Y', strtotime($member['created_at'])) ?></div>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 20px;">
                <a href="members.php" class="btn">⬅️ Back to Members</a>
                <?php if ($is_editor): ?>
                    <a href="edit_member.php?id=<?= $member['member_id'] ?>" class="btn btn-success" style="margin-left: 10px;">✏️ Edit Member</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>