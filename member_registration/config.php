<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$dbname = 'member_registration';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function isAdmin() {
    return hasRole('admin');
}

function canEdit() {
    return isAdmin() || hasRole('editor');
}

function canView() {
    return isLoggedIn();
}

// Get user's assigned area
function getUserArea() {
    return $_SESSION['assigned_area'] ?? null;
}

// Check if user can edit a specific member
function canEditMember($member_area) {
    if (isAdmin()) {
        return true;
    }
    if (hasRole('editor')) {
        $user_area = getUserArea();
        return $user_area && strcasecmp(trim($user_area), trim($member_area)) === 0;
    }
    return false;
}

// Get allowed areas for user (for filtering)
function getAllowedAreas() {
    if (isAdmin()) {
        return null;
    }
    $area = getUserArea();
    return $area ? [$area] : [];
}

// LOG ACTIVITY FUNCTION
function logActivity($action, $details = null) {
    global $pdo;
    
    if (!isLoggedIn()) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_log 
            (user_id, username, user_role, action, details, ip_address, user_agent, page_accessed) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $page_accessed = $_SERVER['REQUEST_URI'] ?? 'Unknown';
        
        $stmt->execute([
            $_SESSION['user_id'],
            $_SESSION['username'],
            $_SESSION['role'],
            $action,
            $details,
            $ip_address,
            $user_agent,
            $page_accessed
        ]);
        
        return true;
    } catch (Exception $e) {
        // Silently fail - don't break the application if logging fails
        return false;
    }
}
?>