<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/database.php';

Auth::requireRole('staff', 'hod');

$db   = Database::getInstance();
$user = Auth::currentUser();
$deptId = $user['department_id'];
$msg = ''; $err = '';

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $err = 'Security error.';
    } else {
        $subjectId = (int)$_POST['subject_id'];
        $unitId    = (int)$_POST['unit_id'];
        $coId      = (int)$_POST['co_id'];
        $qText     = trim($_POST['question_text'] ?? '');
        $qType     = $_POST['question_type'] ?? '';
        $marks     = (int)$_POST['marks'];
        $difficulty = $_POST['difficulty'] ?? 'medium';
        $bloom     = $_POST['bloom_level'] ?? 'L2';
        $diagram   = isset($_POST['diagram_required']) ? 1 : 0;
        $tags      = trim($_POST['tags'] ?? '');

        if (!$subjectId || !$unitId || !$coId || !$qText || !$qType || !$marks) {
            $err = 'Please fill all required fields.';
        } else {
            // Verify subject belongs to staff's department
            $subject = $db->fetchOne("SELECT * FROM subjects WHERE id=? AND department_id=?", [$subjectId, $deptId]);
            if (!$subject) {
                $err = 'You can only add questions for your department subjects.';
            } else {
                $db->insert('questions', [
                    'subject_id'    => $subjectId,
                    'unit_id'       => $unitId,
                    'co_id'         => $coId,
                    'added_by'      => $user['id'],
                    'question_text' => $qText,
                    'question_type' => $qType,
                    'marks'         => $marks,
                    'difficulty'    => $difficulty,
                    'bloom_level'   => $bloom,
                    'is_approved'   => ($user['role'] === 'hod') ? 1 : 0, // HOD auto-approves
                    'diagram_required' => $diagram,
                    'tags'          => $tags,
                ]);
                Auth::logActivity($user['id'], 'ADD_QUESTION', 'questions', "Added $marks-mark question for subject $subjectId");
                $msg = 'Question added successfully!' . ($user['role'] === 'staff' ? ' Pending HOD approval.' : '');
            }
        }
    }
}

// Load subjects for this dept
$subjects = $db->fetchAll("SELECT * FROM subjects WHERE department_id=? AND is_active=1 ORDER BY semester, subject_name", [$deptId]);

// My recent questions
$myQuestions = $db->fetchAll(
    "SELECT q.*, s.subject_name, s.subject_code, su.unit_title, co.co_code FROM questions q JOIN subjects s ON q.subject_id=s.id JOIN subject_units su ON q.unit_id=su.id JOIN course_outcomes co ON q.co_id=co.id WHERE q.added_by=? ORDER BY q.created_at DESC LIMIT 10",
    [$user['id']]
);

$pageTitle = 'Add Question';
require_once __DIR__ . '/../../includes/layout.php';
?>

<?php if ($msg): ?><div class="flash-msg flash-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="flash-msg flash-error"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start;">

  <div class="card">
    <div class="card-title"><i class="fas fa-plus-circle"></i> Add New Question</div>

    <form method="POST" id="addQForm">
      <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div class="form-group">
          <label class="form-label">Subject *</label>
          <select name="subject_id" id="subjectSel" class="form-control" required onchange="loadUnitsAndCOs(this.value)">
            <option value="">Select Subject</option>
            <?php foreach ($subjects as $s): ?>
            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['subject_code'] . ' – ' . $s['subject_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Unit *</label>
          <select name="unit_id" id="unitSel" class="form-control" required>
            <option value="">— Select Subject first —</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Course Outcome (CO) *</label>
          <select name="co_id" id="coSel" class="form-control" required>
            <option value="">— Select Subject first —</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Question Type *</label>
          <select name="question_type" id="qTypeSel" class="form-control" required onchange="setMarksFromType(this.value)">
            <option value="">Select Type</option>
            <option value="2mark">2-Mark (Short Answer)</option>
            <option value="5mark">5-Mark (Medium Answer)</option>
            <option value="10mark">10-Mark (Long Answer)</option>
            <option value="part_a">Part A</option>
            <option value="part_b">Part B</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Marks *</label>
          <input type="number" name="marks" id="marksSel" class="form-control" min="1" max="25" required value="2">
        </div>

        <div class="form-group">
          <label class="form-label">Bloom's Taxonomy Level *</label>
          <select name="bloom_level" class="form-control" required>
            <option value="L1">L1 – Remember</option>
            <option value="L2" selected>L2 – Understand</option>
            <option value="L3">L3 – Apply</option>
            <option value="L4">L4 – Analyze</option>
            <option value="L5">L5 – Evaluate</option>
            <option value="L6">L6 – Create</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Difficulty</label>
          <select name="difficulty" class="form-control">
            <option value="easy">Easy</option>
            <option value="medium" selected>Medium</option>
            <option value="hard">Hard</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Question Text *</label>
        <textarea name="question_text" class="form-control" style="min-height:120px;" required
                  placeholder="Enter the complete question text. For multi-part questions, use (a), (b), (c) format."></textarea>
      </div>

      <div style="display:grid;grid-template-columns:1fr auto;gap:16px;align-items:center;">
        <div class="form-group" style="margin:0;">
          <label class="form-label">Tags (comma separated)</label>
          <input type="text" name="tags" class="form-control" placeholder="e.g. important, expected, unit1">
        </div>
        <div style="padding-top:24px;">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;">
            <input type="checkbox" name="diagram_required" value="1">
            Diagram Required
          </label>
        </div>
      </div>

      <div style="margin-top:18px;padding:12px;background:#fffbf0;border:1px solid #fde68a;border-radius:8px;font-size:13px;color:#92400e;">
        <i class="fas fa-info-circle"></i>
        <?php if ($user['role'] === 'hod'): ?>
        As HOD, your questions are <strong>auto-approved</strong>.
        <?php else: ?>
        Your questions will be submitted for <strong>HOD approval</strong> before appearing in question papers.
        <?php endif; ?>
      </div>

      <div style="margin-top:18px;display:flex;gap:12px;">
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Question</button>
        <button type="reset" class="btn btn-outline"><i class="fas fa-rotate-left"></i> Reset</button>
      </div>
    </form>
  </div>

  <!-- RIGHT: My Recent Questions -->
  <div class="card">
    <div class="card-title">
      <i class="fas fa-clock-rotate-left"></i> My Recent Questions
      <a href="questions.php" style="margin-left:auto;font-size:12px;color:#1a4fd6;text-decoration:none;">View All →</a>
    </div>
    <?php if (empty($myQuestions)): ?>
    <p style="color:#64748b;font-size:14px;">No questions added yet.</p>
    <?php else: ?>
    <?php foreach ($myQuestions as $q): ?>
    <div style="padding:10px 0;border-bottom:1px solid #f1f5f9;">
      <div style="display:flex;gap:6px;margin-bottom:4px;flex-wrap:wrap;">
        <span class="badge badge-info" style="font-size:10px;"><?= $q['subject_code'] ?></span>
        <span class="badge badge-warning" style="font-size:10px;"><?= $q['unit_title'] ?></span>
        <span class="badge badge-purple" style="font-size:10px;"><?= $q['co_code'] ?></span>
        <span class="badge <?= $q['is_approved'] ? 'badge-success' : 'badge-danger' ?>" style="font-size:10px;">
          <?= $q['is_approved'] ? 'Approved' : 'Pending' ?>
        </span>
      </div>
      <div style="font-size:13px;color:#334155;line-height:1.5;">
        <?= htmlspecialchars(substr($q['question_text'], 0, 100)) ?><?= strlen($q['question_text']) > 100 ? '...' : '' ?>
      </div>
      <div style="font-size:11px;color:#94a3b8;margin-top:3px;">
        <?= $q['marks'] ?> marks · <?= $q['bloom_level'] ?> · <?= $q['difficulty'] ?>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>

<script>
async function loadUnitsAndCOs(subjectId) {
  if (!subjectId) return;
  
  // Load units
  const unitSel = document.getElementById('unitSel');
  unitSel.innerHTML = '<option>Loading...</option>';
  const uRes = await fetch(`../../api/units.php?subject=${subjectId}`);
  const units = await uRes.json();
  unitSel.innerHTML = '<option value="">Select Unit</option>';
  units.forEach(u => unitSel.add(new Option(`Unit ${u.unit_number}: ${u.unit_title}`, u.id)));

  // Load COs
  const coSel = document.getElementById('coSel');
  coSel.innerHTML = '<option>Loading...</option>';
  const cRes = await fetch(`../../api/cos.php?subject=${subjectId}`);
  const cos = await cRes.json();
  coSel.innerHTML = '<option value="">Select CO</option>';
  cos.forEach(c => coSel.add(new Option(`${c.co_code}: ${c.co_description.substring(0,60)}`, c.id)));
}

function setMarksFromType(type) {
  const map = { '2mark': 2, '5mark': 5, '10mark': 10, 'part_a': 2, 'part_b': 5 };
  if (map[type]) document.getElementById('marksSel').value = map[type];
}
</script>

</div></div></body></html>
