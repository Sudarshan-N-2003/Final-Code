<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/database.php';

Auth::requireRole('staff');

$db   = Database::getInstance();
$user = Auth::currentUser();
$uid  = $user['id'];
$deptId = $user['department_id'];

$stats = [
    'total_q'   => $db->fetchOne("SELECT COUNT(*) as c FROM questions WHERE added_by=? AND is_active=1", [$uid])['c'],
    'approved_q'=> $db->fetchOne("SELECT COUNT(*) as c FROM questions WHERE added_by=? AND is_active=1 AND is_approved=1", [$uid])['c'],
    'pending_q' => $db->fetchOne("SELECT COUNT(*) as c FROM questions WHERE added_by=? AND is_active=1 AND is_approved=0", [$uid])['c'],
    'papers_used'=> $db->fetchOne("SELECT COUNT(DISTINCT pq.paper_id) as c FROM paper_questions pq JOIN questions q ON pq.question_id=q.id WHERE q.added_by=?", [$uid])['c'],
];

$myQuestions = $db->fetchAll(
    "SELECT q.*, s.subject_name, s.subject_code, su.unit_title, co.co_code FROM questions q JOIN subjects s ON q.subject_id=s.id JOIN subject_units su ON q.unit_id=su.id JOIN course_outcomes co ON q.co_id=co.id WHERE q.added_by=? AND q.is_active=1 ORDER BY q.created_at DESC LIMIT 10",
    [$uid]
);

$subjects = $db->fetchAll("SELECT * FROM subjects WHERE department_id=? AND is_active=1 ORDER BY semester, subject_name", [$deptId]);

$pageTitle = 'Staff Dashboard';
require_once __DIR__ . '/../../includes/layout.php';
?>

<!-- STATS -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);">
  <?php
  $items=[
    ['label'=>'My Questions',    'val'=>$stats['total_q'],    'color'=>'#7c3aed','bg'=>'#ede9fe','icon'=>'fa-circle-question'],
    ['label'=>'Approved',        'val'=>$stats['approved_q'], 'color'=>'#10b981','bg'=>'#dcfce7','icon'=>'fa-check-circle'],
    ['label'=>'Pending HOD OK',  'val'=>$stats['pending_q'],  'color'=>'#dc2626','bg'=>'#fee2e2','icon'=>'fa-clock'],
    ['label'=>'Used in Papers',  'val'=>$stats['papers_used'],'color'=>'#1e40af','bg'=>'#dbeafe','icon'=>'fa-file-alt'],
  ];
  foreach ($items as $s): ?>
  <div class="stat-card">
    <div class="stat-icon" style="background:<?= $s['bg'] ?>;color:<?= $s['color'] ?>;"><i class="fas <?= $s['icon'] ?>"></i></div>
    <div>
      <div class="stat-value" style="color:<?= $s['color'] ?>;"><?= $s['val'] ?></div>
      <div class="stat-label"><?= $s['label'] ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Quick Actions -->
<div class="card" style="margin-bottom:24px;">
  <div class="card-title"><i class="fas fa-bolt"></i> Quick Actions</div>
  <div style="display:flex;flex-wrap:wrap;gap:12px;">
    <a href="add_question.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Question</a>
    <a href="questions.php" class="btn btn-outline"><i class="fas fa-list"></i> My Questions</a>
    <a href="co_report.php" class="btn btn-outline"><i class="fas fa-chart-line"></i> CO Report</a>
    <a href="papers.php" class="btn btn-outline"><i class="fas fa-file-alt"></i> Papers</a>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 300px;gap:24px;">

  <!-- MY RECENT QUESTIONS -->
  <div class="card">
    <div class="card-title">
      <i class="fas fa-clock-rotate-left"></i> My Recent Questions
      <a href="questions.php" style="margin-left:auto;font-size:12px;color:#1a4fd6;text-decoration:none;">View All →</a>
    </div>
    <?php if (empty($myQuestions)): ?>
    <div style="text-align:center;padding:40px;color:#94a3b8;">
      <i class="fas fa-circle-question" style="font-size:40px;display:block;margin-bottom:12px;color:#cbd5e1;"></i>
      <p>No questions yet.</p>
      <a href="add_question.php" class="btn btn-primary" style="margin-top:12px;"><i class="fas fa-plus"></i> Add Your First Question</a>
    </div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Subject</th><th>Unit</th><th>CO</th><th>Marks</th><th>Bloom</th><th>Status</th><th>Used</th></tr></thead>
        <tbody>
          <?php foreach ($myQuestions as $q): ?>
          <tr>
            <td><span class="badge badge-info" style="font-size:10px;"><?= htmlspecialchars($q['subject_code']) ?></span></td>
            <td style="font-size:12px;"><?= htmlspecialchars($q['unit_title']) ?></td>
            <td><span class="badge badge-purple" style="font-size:10px;"><?= htmlspecialchars($q['co_code']) ?></span></td>
            <td><strong><?= $q['marks'] ?>M</strong></td>
            <td style="font-size:12px;"><?= $q['bloom_level'] ?></td>
            <td><span class="badge <?= $q['is_approved'] ? 'badge-success' : 'badge-warning' ?>" style="font-size:10px;"><?= $q['is_approved'] ? 'Approved' : 'Pending' ?></span></td>
            <td style="font-size:12px;color:#64748b;"><?= $q['times_used'] ?>&times;</td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- MY SUBJECTS -->
  <div class="card">
    <div class="card-title"><i class="fas fa-book"></i> Dept Subjects</div>
    <?php foreach ($subjects as $s): ?>
    <?php $qCount = $db->fetchOne("SELECT COUNT(*) as c FROM questions WHERE subject_id=? AND added_by=? AND is_active=1", [$s['id'], $uid])['c']; ?>
    <div style="padding:10px 0;border-bottom:1px solid #f1f5f9;">
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <div>
          <div style="font-size:13px;font-weight:600;"><?= htmlspecialchars($s['subject_code']) ?></div>
          <div style="font-size:11px;color:#94a3b8;">Sem <?= $s['semester'] ?> · <?= $s['credits'] ?> Credits</div>
        </div>
        <div style="text-align:right;">
          <div style="font-size:16px;font-weight:800;color:<?= $qCount > 0 ? '#10b981' : '#dc2626' ?>;"><?= $qCount ?></div>
          <div style="font-size:10px;color:#94a3b8;">my Qs</div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

</div>

</div></div></body></html>
