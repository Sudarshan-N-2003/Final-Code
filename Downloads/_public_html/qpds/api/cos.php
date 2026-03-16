<?php
// api/cos.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
header('Content-Type: application/json');
Auth::requireRole('admin','principal','hod','staff');
$db = Database::getInstance();
$subjectId = (int)($_GET['subject'] ?? 0);
echo json_encode($db->fetchAll("SELECT * FROM course_outcomes WHERE subject_id=? ORDER BY co_number", [$subjectId]));
