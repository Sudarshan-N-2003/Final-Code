<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/database.php';

Auth::requireRole('hod');

$db   = Database::getInstance();
$user = Auth::currentUser();
$deptId = $user['department_id'];

$stats = [
    'staff'    => $db->fetchOne("SELECT COUNT(*) as c FROM users WHERE role='staff' AND department_id=? AND is_active=1", [$deptId])['c'],
    'questions'=> $db->fetchOne("SELECT COUNT(*) as c FROM questions q JOIN subjects s ON q.subject_id=s.id WHERE s.department_id=? AND q.is_active=1", [$deptId])['c'],
    'pending_q'=> $db->fetchOne("SELECT COUNT(*) as c FROM questions q JOIN subjects s ON q.subject_id=s.id WHERE s.department_id=? AND q.is_active=1 AND q.is_approved=0", [$deptId])['c'],
    'subjects' => $db->fetchOne("SELECT COUNT(*) as c FROM subjects WHERE department_id=? AND is_active=1", [$deptId])['c'],
    'papers'   => $db->fetchOne("SELECT COUNT(*) as c FROM question_papers qp JOIN subjects s ON qp.subject_id=s.id WHERE s.department_id=?", [$deptId])['c'],
];

$pendingQs = $db->fetchAll(
    "SELECT q.*, s.subject_name, s.subject_code, su.unit_title, co.co_code, u.full_name as added_by_name
     FROM questions q
     JOIN subjects s ON q.subject_id=s.id
     JOIN subject_units su ON q.unit_id=su.id
     JOIN course_outcomes co ON q.co_id=co.id
     JOIN users u ON q.added_by=u.id
     WHERE s.department_id=? AND q.is_approved=0 AND q.is_active=1
     ORDER BY q.created_at DESC LIMIT 10",
    [$deptId]
);

$myStaff = $db->fetchAll(
    "SELECT u.*, COUNT(q.id) as q_count
     FROM users u
     LEFT JOIN questions q ON q.added_by=u.id AND q.is_active=1
     WHERE u.role='staff' AND u.department_id=? AND u.is_active=1
     GROUP BY u.id
     ORDER BY u.full_name",
    [$deptId]
);

$pageTitle = 'HOD Dashboard – ' . $user['dept_name'];
require_once __DIR__ . '/../../includes/layout.php';
?>

<!-- Approve question action -->
<?php
if (isset($_GET['approve_q']) && is_numeric($_GET['approve_q'])) {
    $db->update('questions', ['is_approved'=>1, 'approved_by'=>$user['id']], 'id=?', [(int)$_GET['approve_q']]);
    Auth::logActivity($user['id'], 'APPROVE_QUESTION', 'questions', 'Approved Q#'.(int)$_GET['approve_q']);
    echo '<div class="flash-msg flash-success"><i class="fas fa-check-circle"></i> Question approved!</div>';
}
if (isset($_GET['reject_q']) && is_numeric($_GET['reject_q'])) {
    $db->update('questions', ['is_active'=>0], 'id=?', [(int)$_GET['reject_q']]);
    echo '<div class="flash-msg flash-error"><i class="fas fa-xmark"></i> Question rejected.</div>';
}
?>

<!-- STATS -->
<div class="stats-grid">
  <?php
  $items=[
    ['label'=>'My Staff',         'val'=>$stats['staff'],     'color'=>'#10b981','bg'=>'#dcfce7','icon'=>'fa-users'],
    ['label'=>'Dept Subjects',    'val'=>$stats['subjects'],  'color'=>'#1e40af','bg'=>'#dbeafe','icon'=>'fa-book'],
    ['label'=>'Total Questions',  'val'=>$stats['questions'], 'color'=>'#7c3aed','bg'=>'#ede9fe','icon'=>'fa-circle-question'],
    ['label'=>'Pending Approval', 'val'=>$stats['pending_q'], 'color'=>'#dc2626','bg'=>'#fee2e2','icon'=>'fa-clock'],
    ['label'=>'Papers Generated', 'val'=>$stats['papers'],    'color'=>'#d97706','bg'=>'#fef3c7','icon'=>'fa-file-alt'],
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
    <a href="staff.php?action=add" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add Staff</a>
    <a href="questions.php" class="btn btn-primary" style="background:#7c3aed;"><i class="fas fa-list-check"></i> Approve Questions (<?= $stats['pending_q'] ?>)</a>
    <a href="../staff/add_question.php" class="btn btn-primary" style="background:#10b981;"><i class="fas fa-plus"></i> Add Question</a>
    <a href="copo.php" class="btn btn-outline"><i class="fas fa-diagram-project"></i> CO-PO Mapping</a>
    <a href="papers.php" class="btn btn-outline"><i class="fas fa-files"></i> Dept Papers</a>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 300px;gap:24px;">

  <!-- PENDING QUESTIONS -->
  <div class="card">
    <div class="card-title" style="color:#dc2626;"><i class="fas fa-clock"></i> Questions Pending Approval (<?= count($pendingQs) ?>)</div>
    <?php if (empty($pendingQs)): ?>
    <div style="text-align:center;padding:30px;color:#94a3b8;">
      <i class="fas fa-check-circle" style="font-size:32px;color:#10b981;display:block;margin-bottom:8px;"></i>
      All questions approved!
    </div>
    <?php else: ?>
    <?php foreach ($pendingQs as $q): ?>
    <div style="padding:14px;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:10px;">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
          <span class="badge badge-info" style="font-size:10px;"><?= htmlspecialchars($q['subject_code']) ?></span>
          <span class="badge badge-warning" style="font-size:10px;">Unit <?= htmlspecialchars($q['unit_title']) ?></span>
          <span class="badge badge-purple" style="font-size:10px;"><?= htmlspecialchars($q['co_code']) ?></span>
          <span class="badge badge-info" style="font-size:10px;"><?= $q['marks'] ?> Marks · <?= $q['bloom_level'] ?></span>
        </div>
        <div style="display:flex;gap:6px;flex-shrink:0;">
          <a href="?approve_q=<?= $q['id'] ?>" class="btn btn-primary btn-sm" style="background:#10b981;" onclick="return confirm('Approve this question?')">
            <i class="fas fa-check"></i> Approve
          </a>
          <a href="?reject_q=<?= $q['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Reject this question?')">
            <i class="fas fa-xmark"></i>
          </a>
        </div>
      </div>
      <div style="font-size:14px;color:#334155;line-height:1.6;background:#f8fafc;padding:10px;border-radius:6px;">
        <?= nl2br(htmlspecialchars($q['question_text'])) ?>
      </div>
      <div style="font-size:12px;color:#94a3b8;margin-top:6px;">
        Added by: <strong><?= htmlspecialchars($q['added_by_name']) ?></strong> · <?= date('d M Y, h:i A', strtotime($q['created_at'])) ?>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- MY STAFF -->
  <div class="card">
    <div class="card-title"><i class="fas fa-users"></i> My Staff Members</div>
    <a href="staff.php?action=add" class="btn btn-primary btn-sm" style="margin-bottom:14px;width:100%;justify-content:center;"><i class="fas fa-user-plus"></i> Add New Staff</a>
    <?php if (empty($myStaff)): ?>
    <p style="color:#94a3b8;font-size:14px;text-align:center;padding:20px 0;">No staff added yet.</p>
    <?php else: ?>
    <?php foreach ($myStaff as $s): ?>
    <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f1f5f9;">
      <div style="width:34px;height:34px;border-radius:50%;background:#10b981;color:white;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;">
        <?= strtoupper(substr($s['full_name'],0,1)) ?>
      </div>
      <div style="flex:1;min-width:0;">
        <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($s['full_name']) ?></div>
        <div style="font-size:11px;color:#94a3b8;"><?= $s['q_count'] ?> questions added</div>
      </div>
      <span class="badge <?= $s['is_active'] ? 'badge-success' : 'badge-danger' ?>" style="font-size:10px;"><?= $s['is_active'] ? 'Active' : 'Inactive' ?></span>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>

</div></div></body></html>
