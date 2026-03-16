<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'add': addFeedback(); break;
    case 'list': listFeedback(); break;
    default: jsonResponse(['error' => 'Invalid action'], 400);
}

function addFeedback() {
    requireRole('telecaller');
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $studentId = intval($data['student_id'] ?? 0);
    $callStatus = $data['call_status'] ?? '';
    $notes = sanitize($data['notes'] ?? '');
    $nextAction = sanitize($data['next_action'] ?? '');
    
    if (!$studentId || !$callStatus || !$notes) {
        jsonResponse(['error' => 'Student ID, call status, and notes are required'], 400);
    }
    
    if (!in_array($callStatus, ['answered', 'no_answer', 'busy', 'invalid', 'callback'])) {
        jsonResponse(['error' => 'Invalid call status'], 400);
    }
    
    try {
        $db = getDB();
        
        $stmt = $db->prepare(
            "INSERT INTO feedback (student_id, telecaller_id, call_status, notes, next_action) 
             VALUES (?, ?, ?, ?, ?)"
        );
        
        $stmt->execute([
            $studentId,
            $_SESSION['user_id'],
            $callStatus,
            $notes,
            $nextAction
        ]);
        
        jsonResponse([
            'success' => true,
            'id' => $db->lastInsertId(),
            'message' => 'Feedback added successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log("Add feedback error: " . $e->getMessage());
        jsonResponse(['error' => 'Database error occurred'], 500);
    }
}

function listFeedback() {
    requireRole(['admin', 'office', 'telecaller']);
    
    $studentId = intval($_GET['student_id'] ?? 0);
    
    try {
        $db = getDB();
        
        if ($studentId) {
            // Get feedback for specific student
            $stmt = $db->prepare(
                "SELECT f.*, u.name as telecaller_name 
                 FROM feedback f
                 LEFT JOIN users u ON u.id = f.telecaller_id
                 WHERE f.student_id = ?
                 ORDER BY f.created_at DESC"
            );
            $stmt->execute([$studentId]);
        } else {
            // Get all feedback (admin only)
            requireRole(['admin', 'office']);
            
            $stmt = $db->query(
                "SELECT f.*, u.name as telecaller_name, s.name as student_name, s.mobile
                 FROM feedback f
                 LEFT JOIN users u ON u.id = f.telecaller_id
                 LEFT JOIN students s ON s.id = f.student_id
                 ORDER BY f.created_at DESC
                 LIMIT 100"
            );
        }
        
        jsonResponse($stmt->fetchAll());
        
    } catch (PDOException $e) {
        error_log("List feedback error: " . $e->getMessage());
        jsonResponse(['error' => 'Database error occurred'], 500);
    }
}
