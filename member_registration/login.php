<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['assigned_area'] = $user['assigned_area'] ?? null;
            
            // Log the login
            logActivity('Login', 'User logged in successfully');
            
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Member Registration System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh;
        }
        
        .login-container { 
            background: white; 
            padding: 40px; 
            border-radius: 12px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.3); 
            width: 100%; 
            max-width: 420px;
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-container img {
            max-width: 180px;
            height: auto;
            display: inline-block;
        }
        
        .logo-container .logo-text {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            margin-top: 10px;
        }
        
        .logo-container .logo-sub {
            font-size: 13px;
            color: #6c757d;
            margin-top: 3px;
        }
        
        h2 { 
            text-align: center; 
            margin-bottom: 25px; 
            color: #333;
            font-size: 20px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .form-group { 
            margin-bottom: 20px; 
        }
        
        label { 
            display: block; 
            margin-bottom: 5px; 
            color: #555;
            font-weight: 600;
            font-size: 14px;
        }
        
        input { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ddd; 
            border-radius: 6px; 
            font-size: 15px;
            transition: border-color 0.3s;
        }
        
        input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        button { 
            width: 100%; 
            padding: 12px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; 
            border: none; 
            border-radius: 6px; 
            font-size: 16px; 
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        button:hover { 
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            padding: 12px; 
            border-radius: 6px; 
            margin-bottom: 20px; 
            text-align: center;
            border: 1px solid #f5c6cb;
        }
        
        .info { 
            text-align: center; 
            margin-top: 25px; 
            color: #666; 
            font-size: 13px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .info p {
            margin: 3px 0;
        }
        
        .info strong {
            color: #2c3e50;
        }
        
        .credential-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
        }
        
        .credential-row .role {
            color: #6c757d;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 15px;
            font-size: 12px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-container">
            <img src="Put image source Here" alt="Place Church Logo Here">
            <div class="logo-text">PLACE CHURCH NAME HERE</div>
            <div class="logo-sub">Management System</div>
        </div>
        
        <h2>🔐 Login to System</h2>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>👤 Username</label>
                <input type="text" name="username" placeholder="Enter your username" required>
            </div>
            <div class="form-group">
                <label>🔑 Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit">Login</button>
        </form>
        
        <div class="footer-text">
            &copy; <?= date('Y') ?> Place Church Name Here - Management System
        </div>
    </div>
</body>
</html>