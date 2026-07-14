<?php
require_once 'config.php';

if (!isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'viewer';
    $assigned_area = $_POST['assigned_area'] ?? null;
    
    if (empty($username) || empty($full_name) || empty($password)) {
        $_SESSION['error'] = 'All fields are required!';
        header('Location: users.php');
        exit;
    }
    
    // If role is admin, clear assigned area
    if ($role == 'admin') {
        $assigned_area = null;
    }
    
    try {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['error'] = 'Username already exists!';
            header('Location: users.php');
            exit;
        }
        
        // Hash password and insert user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, assigned_area) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $hashedPassword, $full_name, $role, $assigned_area]);
        
        // Log user creation
        logActivity('Add User', 'Created new user: ' . $username . ' (Role: ' . $role . ', Area: ' . ($assigned_area ?? 'All') . ')');
        
        $_SESSION['message'] = 'User created successfully!';
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error: ' . $e->getMessage();
    }
}

header('Location: users.php');
exit;
?>