<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/QPGenerator.php';

Auth::requireRole('admin', 'principal');

$paperId = (int)($_GET['id'] ?? 0);
if (!$paperId) { header('Location: generate.php'); exit; }

$db = Database::getInstance();
// Update print count
$db->update('question_papers', ['print_count' => $db->fetchOne("SELECT print_count FROM question_papers WHERE id=?", [$paperId])['print_count'] + 1, 'status' => 'printed'], 'id=?', [$paperId]);
Auth::logActivity($_SESSION['user_id'], 'PRINT_QP', 'qp_generator', "Printed paper ID: $paperId");

$gen = new QPGenerator();
echo $gen->renderPaperHTML($paperId);
?>
<script>
window.onload = function() {
  window.print();
};
</script>
