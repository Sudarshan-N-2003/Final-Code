<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list': listUsers(); break;
    case 'add': addUser(); break;
    case 'delete': deleteUser(); break;
    case 'telecallers': getTelecallers(); break;
    case 'stats': getUserStats(); break;
    default: jsonResponse(['error' => 'Invalid action'], 400);
}

function listUsers() {
    requireRole(['admin', 'office']);
    
    $search = sanitize($_GET['search'] ?? '');
    
    try {
        $db = getDB();
        
        $sql = "SELECT id, name, email, phone, role, gender, created_at 
                FROM users 
                WHERE 1=1";
        
        if ($search) {
            $sql .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $stmt = $db->prepare($sql . " ORDER BY created_at DESC");
            $searchTerm = "%$search%";
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        } else {
            $stmt = $db->query($sql . " ORDER BY created_at DESC");
        }
        
        jsonResponse($stmt->fetchAll());
    } catch (PDOException $e) {
        error_log("List users error: " . $e->getMessage());
        jsonResponse(['error' => 'Database error occurred'], 500);
    }
}

function addUser() {
    requireRole(['admin', 'office']);
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $name = sanitize($data['name'] ?? '');
    $email = sanitize($data['email'] ?? '');
    $phone = sanitize($data['phone'] ?? '');
    $role = $data['role'] ?? 'telecaller';
    $gender = $data['gender'] ?? '';
    $dob = $data['dob'] ?? '';
    
    if (!$name || !$email || !$phone) {
        jsonResponse(['error' => 'Name, email, and phone are required'], 400);
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['error' => 'Invalid email format'], 400);
    }
    
    if (!in_array($role, ['admin', 'office', 'telecaller'])) {
        jsonResponse(['error' => 'Invalid role'], 400);
    }
    
    // Generate random password
    $systemPassword = bin2hex(random_bytes(6)); // 12 character password
    $hashedPassword = hashPassword($systemPassword);
    
    try {
        $db = getDB();
        
        // Check if email exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            jsonResponse(['error' => 'Email already exists'], 400);
        }
        
        $stmt = $db->prepare(
            "INSERT INTO users (name, email, phone, password, role, gender, dob) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        
        $stmt->execute([$name, $email, $phone, $hashedPassword, $role, $gender, $dob]);
        
        jsonResponse([
            'success' => true,
            'id' => $db->lastInsertId(),
            'system_password' => $systemPassword
        ]);
        
    } catch (PDOException $e) {
        error_log("Add user error: " . $e->getMessage());
        jsonResponse(['error' => 'Database error occurred'], 500);
    }
}

function deleteUser() {
    requireRole(['admin']);
    
    $id = intval($_GET['id'] ?? 0);
    
    if (!$id) {
        jsonResponse(['error' => 'User ID required'], 400);
    }
    
    if ($id === $_SESSION['user_id']) {
        jsonResponse(['error' => 'Cannot delete yourself'], 400);
    }
    
    try {
        $db = getDB();
        
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        
        jsonResponse(['success' => true]);
        
    } catch (PDOException $e) {
        error_log("Delete user error: " . $e->getMessage());
        jsonResponse(['error' => 'Database error occurred'], 500);
    }
}

function getTelecallers() {
    requireLogin();
    
    try {
        $db = getDB();
        
        $stmt = $db->query(
            "SELECT id, name, email 
             FROM users 
             WHERE role = 'telecaller' 
             ORDER BY name ASC"
        );
        
        jsonResponse($stmt->fetchAll());
        
    } catch (PDOException $e) {
        error_log("Get telecallers error: " . $e->getMessage());
        jsonResponse(['error' => 'Database error occurred'], 500);
    }
}

function getUserStats() {
    requireRole(['admin', 'office']);
    
    try {
        $db = getDB();
        
        $stmt = $db->query(
            "SELECT 
                u.id,
                u.name,
                u.email,
                COUNT(s.id) as total,
                SUM(CASE WHEN s.status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN s.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN s.status = 'accepted' THEN 1 ELSE 0 END) as accepted,
                SUM(CASE WHEN s.status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN s.status = 'callback' THEN 1 ELSE 0 END) as callback
             FROM users u
             LEFT JOIN students s ON s.assigned_to = u.id
             WHERE u.role = 'telecaller'
             GROUP BY u.id, u.name, u.email
             ORDER BY u.name ASC"
        );
        
        jsonResponse($stmt->fetchAll());
        
    } catch (PDOException $e) {
        error_log("Get user stats error: " . $e->getMessage());
        jsonResponse(['error' => 'Database error occurred'], 500);
    }
}