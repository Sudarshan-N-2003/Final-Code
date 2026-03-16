<?php
// api/subjects.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

header('Content-Type: application/json');
Auth::requireRole('admin','principal','hod','staff');

$db = Database::getInstance();
$deptId = (int)($_GET['dept'] ?? 0);
$sem    = (int)($_GET['sem'] ?? 0);

if (!$deptId || !$sem) {
    echo json_encode([]);
    exit;
}

$subjects = $db->fetchAll(
    "SELECT id, subject_code, subject_name, credits, total_units, subject_type FROM subjects WHERE department_id=? AND semester=? AND is_active=1 ORDER BY subject_name",
    [$deptId, $sem]
);

echo json_encode($subjects);
