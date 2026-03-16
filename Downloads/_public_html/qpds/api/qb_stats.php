<?php
// api/qb_stats.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
header('Content-Type: application/json');
Auth::requireRole('admin','principal','hod','staff');

$db = Database::getInstance();
$subjectId = (int)($_GET['subject'] ?? 0);

if (!$subjectId) { echo json_encode(['total'=>0]); exit; }

$total    = $db->fetchOne("SELECT COUNT(*) as c FROM questions WHERE subject_id=? AND is_active=1", [$subjectId])['c'];
$approved = $db->fetchOne("SELECT COUNT(*) as c FROM questions WHERE subject_id=? AND is_active=1 AND is_approved=1", [$subjectId])['c'];
$twoMark  = $db->fetchOne("SELECT COUNT(*) as c FROM questions WHERE subject_id=? AND is_active=1 AND marks=2", [$subjectId])['c'];
$fiveMark = $db->fetchOne("SELECT COUNT(*) as c FROM questions WHERE subject_id=? AND is_active=1 AND marks=5", [$subjectId])['c'];
$tenMark  = $db->fetchOne("SELECT COUNT(*) as c FROM questions WHERE subject_id=? AND is_active=1 AND marks=10", [$subjectId])['c'];

echo json_encode([
    'total'   => (int)$total,
    'approved'=> (int)$approved,
    '2mark'   => (int)$twoMark,
    '5mark'   => (int)$fiveMark,
    '10mark'  => (int)$tenMark,
]);
