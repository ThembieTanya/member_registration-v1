<?php
require_once 'config.php';

// Allow both Admin and Editor to access
if (!canEdit()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Define all areas
$all_areas = ['A', 'B', 'C', 'D', 'E'];

// Define variables for use in HTML
$user_area = getUserArea();
$is_admin = isAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Insert member
        $stmt = $pdo->prepare("INSERT INTO members (
            area_name, full_name, id_number, gender, date_of_birth, marital_status,
            phone1, phone2, zone_department, residential_address, company_name,
            occupation, email, spouse_full_name, spouse_id_number, spouse_dob,
            spouse_profession, spouse_email, spouse_phone1, spouse_phone2,
            spouse_zone_department, next_of_kin_name, next_of_kin_relationship,
            next_of_kin_phone, received_by, date_received
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $_POST['area_name'],
            $_POST['full_name'],
            $_POST['id_number'],
            $_POST['gender'],
            $_POST['date_of_birth'],
            $_POST['marital_status'],
            $_POST['phone1'],
            $_POST['phone2'],
            $_POST['zone_department'],
            $_POST['residential_address'],
            $_POST['company_name'],
            $_POST['occupation'],
            $_POST['email'],
            $_POST['spouse_full_name'],
            $_POST['spouse_id_number'],
            $_POST['spouse_dob'],
            $_POST['spouse_profession'],
            $_POST['spouse_email'],
            $_POST['spouse_phone1'],
            $_POST['spouse_phone2'],
            $_POST['spouse_zone_department'],
            $_POST['next_of_kin_name'],
            $_POST['next_of_kin_relationship'],
            $_POST['next_of_kin_phone'],
            $_POST['received_by'],
            $_POST['date_received']
        ]);
        
        $memberId = $pdo->lastInsertId();
        
        // Insert children
        if (isset($_POST['children']) && is_array($_POST['children'])) {
            $childStmt = $pdo->prepare("INSERT INTO children (member_id, serial_number, full_name, date_of_birth, gender, profession_zone) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($_POST['children'] as $child) {
                if (!empty($child['full_name'])) {
                    $childStmt->execute([
                        $memberId,
                        $child['serial_number'],
                        $child['full_name'],
                        $child['date_of_birth'],
                        $child['gender'],
                        $child['profession_zone']
                    ]);
                }
            }
        }
        
        $pdo->commit();
        
        // Log the new member registration
        logActivity('Add Member', 'Added new member: ' . $_POST['full_name'] . ' (Area: ' . $_POST['area_name'] . ', ID: ' . $memberId . ')');
        
        $success = 'Member registered successfully!';
        
        // Clear form data
        $_POST = [];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register Member</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        .header { background: #2c3e50; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .header a { color: white; text-decoration: none; margin-left: 20px; }
        .header a:hover { text-decoration: underline; }
        .container { max-width: 800px; margin: 30px auto; padding: 0 20px; }
        .form-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h2 { margin-bottom: 20px; color: #333; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        textarea { resize: vertical; min-height: 60px; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .section-title { background: #f8f9fa; padding: 10px; margin: 20px 0 15px; border-left: 4px solid #3498db; font-weight: bold; }
        .btn { padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #2980b9; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        .alert { padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .child-entry { background: #f8f9fa; padding: 15px; margin-bottom: 15px; border-radius: 4px; border: 1px solid #dee2e6; }
        .add-btn { background: #3498db; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; margin-top: 10px; }
        .add-btn:hover { background: #2980b9; }
        .role-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; margin-left: 10px; background: rgba(255,255,255,0.2); color: white; }
        .area-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            background: rgba(255,255,255,0.2);
            color: white;
            margin-left: 10px;
        }
        .help-text {
            display: block;
            color: #6c757d;
            font-size: 12px;
            margin-top: 3px;
            font-weight: normal;
        }
        .readonly-field {
            background: #f0f0f0;
            cursor: not-allowed;
        }
        .dropdown-icon select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236c757d' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 35px;
        }
        .editor-info {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Member Registration</h1>
        <div>
            <span>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></span>
            <span class="role-badge"><?= ucfirst($_SESSION['role']) ?></span>
            <?php if ($user_area): ?>
                <span class="area-badge">📍 <?= htmlspecialchars($user_area) ?></span>
            <?php endif; ?>
            <a href="dashboard.php">Dashboard</a>
            <a href="members.php">View Members</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="form-container">
            <h2>Register New Member</h2>
            
            <?php if (!$is_admin && $user_area): ?>
                <div class="editor-info">
                    <strong>📍 Editor Access:</strong> You can only register members in <strong><?= htmlspecialchars($user_area) ?></strong> area.
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST" id="registrationForm">
                <div class="form-group">
                    <label>Area Name <span style="color: red;">*</span></label>
                    <?php if ($is_admin): ?>
                        <div class="dropdown-icon">
                            <select name="area_name" required>
                                <option value="">-- Select Area --</option>
                                <?php foreach ($all_areas as $area): ?>
                                    <option value="<?= $area ?>" <?= (isset($_POST['area_name']) && $_POST['area_name'] == $area) ? 'selected' : '' ?>>
                                        <?= $area ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <input type="text" name="area_name" value="<?= htmlspecialchars($user_area) ?>" readonly class="readonly-field">
                        <span class="help-text">You are restricted to <?= htmlspecialchars($user_area) ?> area</span>
                    <?php endif; ?>
                </div>
                
                <div class="section-title">Member Information</div>
                
                <div class="form-group">
                    <label>Full Name <span style="color: red;">*</span></label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                </div>
                
                <div class="row">
                    <div class="form-group">
                        <label>ID Number</label>
                        <input type="text" name="id_number" value="<?= htmlspecialchars($_POST['id_number'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Gender <span style="color: red;">*</span></label>
                        <select name="gender" required>
                            <option value="">Select</option>
                            <option value="M" <?= (isset($_POST['gender']) && $_POST['gender'] == 'M') ? 'selected' : '' ?>>Male</option>
                            <option value="F" <?= (isset($_POST['gender']) && $_POST['gender'] == 'F') ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth" value="<?= htmlspecialchars($_POST['date_of_birth'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Marital Status</label>
                        <select name="marital_status">
                            <option value="">Select</option>
                            <option value="Single" <?= (isset($_POST['marital_status']) && $_POST['marital_status'] == 'Single') ? 'selected' : '' ?>>Single</option>
                            <option value="Customary Married" <?= (isset($_POST['marital_status']) && $_POST['marital_status'] == 'Customary Married') ? 'selected' : '' ?>>Customary Married</option>
                            <option value="Civil Married" <?= (isset($_POST['marital_status']) && $_POST['marital_status'] == 'Civil Married') ? 'selected' : '' ?>>Civil Married</option>
                            <option value="Widowed" <?= (isset($_POST['marital_status']) && $_POST['marital_status'] == 'Widowed') ? 'selected' : '' ?>>Widowed</option>
                            <option value="Divorced" <?= (isset($_POST['marital_status']) && $_POST['marital_status'] == 'Divorced') ? 'selected' : '' ?>>Divorced</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="form-group">
                        <label>Phone Number 1</label>
                        <input type="text" name="phone1" value="<?= htmlspecialchars($_POST['phone1'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Phone Number 2</label>
                        <input type="text" name="phone2" value="<?= htmlspecialchars($_POST['phone2'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="form-group">
                        <label>Zone Department</label>
                        <input type="text" name="zone_department" value="<?= htmlspecialchars($_POST['zone_department'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Residential Address</label>
                    <textarea name="residential_address"><?= htmlspecialchars($_POST['residential_address'] ?? '') ?></textarea>
                </div>
                
                <div class="row">
                    <div class="form-group">
                        <label>Company Name</label>
                        <input type="text" name="company_name" value="<?= htmlspecialchars($_POST['company_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Occupation/Profession</label>
                        <input type="text" name="occupation" value="<?= htmlspecialchars($_POST['occupation'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="section-title">Spouse's Details (Leave blank if not applicable)</div>
                
                <div class="form-group">
                    <label>Spouse Full Name</label>
                    <input type="text" name="spouse_full_name" value="<?= htmlspecialchars($_POST['spouse_full_name'] ?? '') ?>">
                </div>
                
                <div class="row">
                    <div class="form-group">
                        <label>Spouse ID Number</label>
                        <input type="text" name="spouse_id_number" value="<?= htmlspecialchars($_POST['spouse_id_number'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Spouse Date of Birth</label>
                        <input type="date" name="spouse_dob" value="<?= htmlspecialchars($_POST['spouse_dob'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="form-group">
                        <label>Spouse Profession</label>
                        <input type="text" name="spouse_profession" value="<?= htmlspecialchars($_POST['spouse_profession'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Spouse Email</label>
                        <input type="email" name="spouse_email" value="<?= htmlspecialchars($_POST['spouse_email'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="form-group">
                        <label>Spouse Phone 1</label>
                        <input type="text" name="spouse_phone1" value="<?= htmlspecialchars($_POST['spouse_phone1'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Spouse Phone 2</label>
                        <input type="text" name="spouse_phone2" value="<?= htmlspecialchars($_POST['spouse_phone2'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Spouse Zone Department</label>
                    <input type="text" name="spouse_zone_department" value="<?= htmlspecialchars($_POST['spouse_zone_department'] ?? '') ?>">
                </div>
                
                <div class="section-title">Next of Kin / Emergency Contact</div>
                
                <div class="row">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="next_of_kin_name" value="<?= htmlspecialchars($_POST['next_of_kin_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Relationship</label>
                        <input type="text" name="next_of_kin_relationship" value="<?= htmlspecialchars($_POST['next_of_kin_relationship'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="next_of_kin_phone" value="<?= htmlspecialchars($_POST['next_of_kin_phone'] ?? '') ?>">
                </div>
                
                <div class="section-title">Children's Details</div>
                
                <div id="childrenContainer">
                    <div class="child-entry">
                        <div class="row">
                            <div class="form-group">
                                <label>Serial Number</label>
                                <input type="number" name="children[0][serial_number]" value="1">
                            </div>
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" name="children[0][full_name]">
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group">
                                <label>Date of Birth</label>
                                <input type="date" name="children[0][date_of_birth]">
                            </div>
                            <div class="form-group">
                                <label>Gender</label>
                                <select name="children[0][gender]">
                                    <option value="">Select</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Profession/Zone Department</label>
                            <input type="text" name="children[0][profession_zone]">
                        </div>
                    </div>
                </div>
                
                <button type="button" class="add-btn" onclick="addChild()">+ Add Another Child</button>
                
                <div class="section-title">Office Use Only</div>
                
                <div class="row">
                    <div class="form-group">
                        <label>Received By</label>
                        <input type="text" name="received_by" value="<?= htmlspecialchars($_POST['received_by'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Date Received</label>
                        <input type="date" name="date_received" value="<?= htmlspecialchars($_POST['date_received'] ?? '') ?>">
                    </div>
                </div>
                
                <button type="submit" class="btn" style="margin-top: 20px;">Register Member</button>
                <a href="members.php" class="btn btn-secondary" style="margin-top: 20px; text-decoration: none; display: inline-block;">Cancel</a>
            </form>
        </div>
    </div>
    
    <script>
        let childCount = 1;
        
        function addChild() {
            childCount++;
            const container = document.getElementById('childrenContainer');
            const div = document.createElement('div');
            div.className = 'child-entry';
            div.innerHTML = `
                <div class="row">
                    <div class="form-group">
                        <label>Serial Number</label>
                        <input type="number" name="children[${childCount}][serial_number]" value="${childCount}">
                    </div>
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="children[${childCount}][full_name]">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="children[${childCount}][date_of_birth]">
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="children[${childCount}][gender]">
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Profession/Zone Department</label>
                    <input type="text" name="children[${childCount}][profession_zone]">
                </div>
                <button type="button" class="btn btn-danger" style="margin-top: 10px;" onclick="this.parentElement.remove()">Remove</button>
            `;
            container.appendChild(div);
        }
    </script>
</body>
</html>