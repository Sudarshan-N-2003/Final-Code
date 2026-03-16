<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'add': addStudent(); break;
    case 'bulk_add': bulkAdd(); break;
    case 'list': listStudents(); break;
    case 'detail': getDetail(); break;
    case 'update': updateStudent(); break;
    case 'delete': deleteStudent(); break;
    case 'auto_assign': autoAssign(); break;
    case 'export': exportCSV(); break;
    case 'stats': getStats(); break;
    case 'recent_activity': getRecentActivity(); break;  // Add this line
    default: jsonResponse(['error' => 'Invalid action'], 400);
}

function addStudent() {
    requireRole(['admin', 'office']);
    $data = json_decode(file_get_contents('php://input'), true);
    
    $name = sanitize($data['name'] ?? '');
    $mobile = sanitize($data['mobile'] ?? '');
    $college = sanitize($data['present_college'] ?? '');
    $type = $data['college_type'] ?? 'Other';
    $address = sanitize($data['address'] ?? '');
    
    if (!$name || !$mobile) jsonResponse(['error' => 'Name and mobile required'], 400);
    
    $db = getDB();
    
    // Check duplicate
    $stmt = $db->prepare("SELECT id FROM students WHERE mobile = ?");
    $stmt->execute([$mobile]);
    if ($stmt->fetch()) jsonResponse(['error' => 'Mobile already exists'], 400);
    
    // Auto-assign to least-loaded telecaller
    $stmt = $db->query("SELECT u.id FROM users u LEFT JOIN students s ON s.assigned_to = u.id WHERE u.role = 'telecaller' GROUP BY u.id ORDER BY COUNT(s.id) ASC LIMIT 1");
    $assigned = $stmt->fetchColumn() ?: null;
    
    $stmt = $db->prepare("INSERT INTO students (name, mobile, present_college, college_type, address, assigned_to, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $mobile, $college, $type, $address, $assigned, $_SESSION['user_id']]);
    
    jsonResponse(['success' => true, 'id' => $db->lastInsertId(), 'assigned_to' => $assigned]);
}

function bulkAdd() {
    requireRole(['admin', 'office']);
    $data = json_decode(file_get_contents('php://input'), true);
    $students = $data['students'] ?? [];
    
    if (empty($students)) jsonResponse(['error' => 'No students provided'], 400);
    
    $db = getDB();
    
    // Get telecallers
    $stmt = $db->query("SELECT u.id FROM users u LEFT JOIN students s ON s.assigned_to = u.id WHERE u.role = 'telecaller' GROUP BY u.id ORDER BY COUNT(s.id) ASC");
    $telecallers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($telecallers)) $telecallers = [null];
    
    $db->beginTransaction();
    $insert = $db->prepare("INSERT INTO students (name, mobile, present_college, college_type, address, assigned_to, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $added = 0;
    $idx = 0;
    
    foreach ($students as $s) {
        $name = sanitize($s['name'] ?? '');
        $mobile = sanitize($s['mobile'] ?? '');
        if (empty($name) || empty($mobile)) continue;
        
        $assigned = $telecallers[$idx % count($telecallers)];
        try {
            $insert->execute([
                $name,
                $mobile,
                sanitize($s['present_college'] ?? ''),
                $s['college_type'] ?? 'Other',
                sanitize($s['address'] ?? ''),
                $assigned,
                $_SESSION['user_id']
            ]);
            $added++;
            $idx++;
        } catch (PDOException $e) {
            // Skip duplicates
        }
    }
    
    $db->commit();
    jsonResponse(['success' => true, 'added' => $added]);
}

function listStudents() {
    requireRole(['admin', 'office', 'telecaller']);  // Add 'telecaller' here
    
    $db = getDB();
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    $search = sanitize($_GET['search'] ?? '');
    $status = $_GET['status'] ?? '';
    
    // Base query
    $sql = "SELECT s.*, u.name as assigned_name FROM students s LEFT JOIN users u ON u.id = s.assigned_to";
    $params = [];
    
    // Different filters based on role
    if ($role === 'telecaller') {
        // Telecaller sees only their assigned students
        $sql .= " WHERE s.assigned_to = ?";
        $params[] = $user_id;
    } else {
        // Admin/office sees all students
        $sql .= " WHERE 1=1";
    }
    
    // Add search if provided
    if ($search) {
        $sql .= " AND (s.name LIKE ? OR s.mobile LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    // Add status filter if provided
    if ($status) {
        $sql .= " AND s.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY s.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
    
    jsonResponse($students);
}

function getDetail() {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'ID required'], 400);
    
    $db = getDB();
    $stmt = $db->prepare("SELECT s.*, u.name as assigned_name FROM students s LEFT JOIN users u ON u.id = s.assigned_to WHERE s.id = ?");
    $stmt->execute([$id]);
    $student = $stmt->fetch();
    
    if (!$student) jsonResponse(['error' => 'Not found'], 404);
    jsonResponse($student);
}

function updateStudent() {
    requireRole(['admin', 'office', 'telecaller']);
    $data = json_decode(file_get_contents('php://input'), true);
    $id = intval($data['id'] ?? 0);
    
    if (!$id) jsonResponse(['error' => 'ID required'], 400);
    
    $db = getDB();
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    // For telecallers, verify they can only update their assigned students
    if ($role === 'telecaller') {
        $stmt = $db->prepare("SELECT id FROM students WHERE id = ? AND assigned_to = ?");
        $stmt->execute([$id, $user_id]);
        if (!$stmt->fetch()) {
            jsonResponse(['error' => 'Not authorized to update this student'], 403);
        }
    }
    
    $fields = [];
    $params = [];
    
    // Telecallers can only update status
    if ($role === 'telecaller') {
        if (!isset($data['status'])) {
            jsonResponse(['error' => 'Telecallers can only update status'], 400);
        }
        $fields[] = "status = ?";
        $params[] = $data['status'];
    } else {
        // Admin/office can update all fields
        if (isset($data['status'])) {
            $fields[] = "status = ?";
            $params[] = $data['status'];
        }
        if (isset($data['name'])) {
            $fields[] = "name = ?";
            $params[] = sanitize($data['name']);
        }
        // Add other fields as needed
    }
    
    if (empty($fields)) jsonResponse(['error' => 'No fields to update'], 400);
    
    $params[] = $id;
    $stmt = $db->prepare("UPDATE students SET " . implode(', ', $fields) . " WHERE id = ?");
    $stmt->execute($params);
    
    jsonResponse(['success' => true]);
}

function deleteStudent() {
    requireRole('admin');
    $id = intval($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'ID required'], 400);
    
    $db = getDB();
    $db->prepare("DELETE FROM students WHERE id = ?")->execute([$id]);
    jsonResponse(['success' => true]);
}

function autoAssign() {
    requireRole('admin');
    $db = getDB();
    
    // Get unassigned students
    $stmt = $db->query("SELECT id FROM students WHERE assigned_to IS NULL");
    $unassigned = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($unassigned)) jsonResponse(['success' => true, 'assigned' => 0]);
    
    // Get telecallers
    $stmt = $db->query("SELECT u.id FROM users u LEFT JOIN students s ON s.assigned_to = u.id WHERE u.role = 'telecaller' GROUP BY u.id ORDER BY COUNT(s.id) ASC");
    $telecallers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($telecallers)) jsonResponse(['error' => 'No telecallers'], 400);
    
    $update = $db->prepare("UPDATE students SET assigned_to = ? WHERE id = ?");
    $i = 0;
    foreach ($unassigned as $sid) {
        $update->execute([$telecallers[$i % count($telecallers)], $sid]);
        $i++;
    }
    
    jsonResponse(['success' => true, 'assigned' => $i]);
}

function exportCSV() {
    requireRole(['admin', 'office']);
    $db = getDB();
    
    $stmt = $db->query("SELECT s.name, s.mobile, s.present_college, s.college_type, s.address, s.status, u.name as assigned_to, s.created_at FROM students s LEFT JOIN users u ON u.id = s.assigned_to ORDER BY s.created_at DESC");
    $rows = $stmt->fetchAll();
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="students_' . date('Ymd') . '.csv"');
    
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, ['Name', 'Mobile', 'College', 'Type', 'Address', 'Status', 'Assigned To', 'Created']);
    foreach ($rows as $row) fputcsv($out, $row);
    fclose($out);
    exit;
}

function getStats() {
    requireRole(['admin', 'office']);
    $db = getDB();
    
    $stmt = $db->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted, SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN assigned_to IS NULL THEN 1 ELSE 0 END) as unassigned FROM students");
    jsonResponse($stmt->fetch());
}

function getRecentActivity() {
    requireRole(['admin', 'office', 'telecaller']);
    
    $db = getDB();
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    // For now, we'll create a simple recent activity from students table
    // If you have an activity log table, you'd query that instead
    
    $sql = "SELECT 
                s.id,
                s.name as student_name,
                s.status,
                s.updated_at,
                u.name as telecaller_name,
                'Status Updated' as action,
                CASE 
                    WHEN s.updated_at > NOW() - INTERVAL 1 HOUR THEN CONCAT(FLOOR(HOUR(TIMEDIFF(NOW(), s.updated_at)) * 60 + MINUTE(TIMEDIFF(NOW(), s.updated_at))), ' minutes ago')
                    WHEN s.updated_at > NOW() - INTERVAL 24 HOUR THEN CONCAT(HOUR(TIMEDIFF(NOW(), s.updated_at)), ' hours ago')
                    ELSE CONCAT(DAY(s.updated_at), ' days ago')
                END as time_ago
            FROM students s 
            LEFT JOIN users u ON s.assigned_to = u.id";
    
    if ($role === 'telecaller') {
        $sql .= " WHERE s.assigned_to = ?";
        $params = [$user_id];
    } else {
        $params = [];
    }
    
    $sql .= " ORDER BY s.updated_at DESC LIMIT 10";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $activities = $stmt->fetchAll();
    
    // If no activities found, return empty array
    if (empty($activities)) {
        jsonResponse([]);
    } else {
        jsonResponse($activities);
    }
}
