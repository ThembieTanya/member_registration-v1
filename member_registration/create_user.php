<?php
// Database configuration
$host = 'localhost';
$dbname = 'member_registration';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create admin user
    $username = 'admin';
    $full_name = 'Administrator';
    $password = 'admin123';
    $role = 'admin';
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $hashedPassword, $full_name, $role]);
        echo "Admin user created successfully!<br>";
    } else {
        // Update existing user
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->execute([$hashedPassword, $username]);
        echo "Admin password updated successfully!<br>";
    }
    
    // Create viewer user
    $username = 'viewer';
    $full_name = 'Viewer User';
    $password = 'viewer123';
    $role = 'viewer';
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $hashedPassword, $full_name, $role]);
        echo "Viewer user created successfully!<br>";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->execute([$hashedPassword, $username]);
        echo "Viewer password updated successfully!<br>";
    }
    
    // Create editor user
    $username = 'editor';
    $full_name = 'Editor User';
    $password = 'editor123';
    $role = 'editor';
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $hashedPassword, $full_name, $role]);
        echo "Editor user created successfully!<br>";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->execute([$hashedPassword, $username]);
        echo "Editor password updated successfully!<br>";
    }
    
    echo "<br>Login credentials:<br>";
    echo "Admin: admin / admin123<br>";
    echo "Viewer: viewer / viewer123<br>";
    echo "Editor: editor / editor123<br>";
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>