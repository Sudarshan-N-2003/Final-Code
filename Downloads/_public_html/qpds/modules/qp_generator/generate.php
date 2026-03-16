<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/QPGenerator.php';

Auth::requireRole('admin', 'principal');

$db = Database::getInstance();
$user = Auth::currentUser();

$msg = ''; $err = ''; $generatedPaper = null;

// Handle generation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $err = 'Security error. Please refresh.';
    } else {
        $action = $_POST['action'] ?? 'generate';
        $subjectId  = (int)$_POST['subject_id'];
        $templateId = (int)$_POST['template_id'];
        $numSets    = (int)($_POST['num_sets'] ?? 1);
        $shuffle    = !empty($_POST['shuffle']);
        $shuffleType = $_POST['shuffle_type'] ?? 'within_section';
        $avoidDays  = (int)($_POST['avoid_recent_days'] ?? 30);
        $instructions = trim($_POST['instructions'] ?? '');
        $watermark  = trim($_POST['watermark'] ?? 'CONFIDENTIAL');
        $academicYear = trim($_POST['academic_year'] ?? '');

        $gen = new QPGenerator();

        $options = [
            'shuffle'           => $shuffle,
            'shuffle_type'      => $shuffleType,
            'avoid_recent_days' => $avoidDays,
            'instructions'      => $instructions,
            'watermark'         => $watermark,
            'academic_year'     => $academicYear,
        ];

        if ($numSets > 1) {
            $result = $gen->generateSets($subjectId, $templateId, min($numSets, MAX_PAPER_SETS), $options);
            if ($result['success']) {
                $msg = "Generated {$numSets} sets successfully!";
                $generatedPaper = $result;
            } else {
                $err = $result['message'];
            }
        } else {
            $result = $gen->generate($subjectId, $templateId, $options);
            if ($result['success']) {
                $msg = "Paper '{$result['paper_code']}' generated successfully!";
                $generatedPaper = $result;
            } else {
                $err = $result['message'];
            }
        }
    }
}

// Load data for form
$departments = $db->fetchAll("SELECT * FROM departments ORDER BY name");
$templates   = $db->fetchAll("SELECT * FROM qp_templates ORDER BY exam_type");

$pageTitle = 'Generate Question Paper';
require_once __DIR__ . '/../../includes/layout.php';
?>

<?php if ($msg): ?><div class="flash-msg flash-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?>
  <?php if ($generatedPaper && !empty($generatedPaper['paper_id'])): ?>
    &nbsp;<a href="view.php?id=<?= $generatedPaper['paper_id'] ?>" class="btn btn-primary btn-sm">View Paper</a>
    <a href="print.php?id=<?= $generatedPaper['paper_id'] ?>" class="btn btn-outline btn-sm" target="_blank"><i class="fas fa-print"></i> Print</a>
  <?php endif; ?>
</div><?php endif; ?>
<?php if ($err): ?><div class="flash-msg flash-error"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;">

  <!-- GENERATOR FORM -->
  <div class="card">
    <div class="card-title"><i class="fas fa-file-circle-plus"></i> Paper Generation Settings</div>

    <form method="POST" id="genForm">
      <input type="hidden" name="action" value="generate">
      <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">

      <!-- Subject Selection -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div class="form-group">
          <label class="form-label">Department *</label>
          <select id="deptSelect" class="form-control" onchange="loadSubjects(this.value)" required>
            <option value="">Select Department</option>
            <?php foreach ($departments as $d): ?>
            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?> (<?= $d['code'] ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Semester *</label>
          <select id="semSelect" class="form-control" onchange="loadSubjects()" required>
            <option value="">Select Semester</option>
            <?php for ($i=1;$i<=8;$i++): ?>
            <option value="<?= $i ?>"><?= $i ?><?= ['st','nd','rd','th','th','th','th','th'][$i-1] ?> Semester</option>
            <?php endfor; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Subject *</label>
        <select name="subject_id" id="subjectSelect" class="form-control" required onchange="showSubjectInfo(this)">
          <option value="">— Select Department & Semester first —</option>
        </select>
        <div id="subjectInfo" style="margin-top:8px;padding:10px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;font-size:13px;color:#64748b;display:none;"></div>
      </div>

      <div class="form-group">
        <label class="form-label">Exam Template (VTU) *</label>
        <select name="template_id" id="templateSelect" class="form-control" required onchange="showTemplateInfo(this)">
          <option value="">Select Template</option>
          <?php foreach ($templates as $t): ?>
          <option value="<?= $t['id'] ?>" data-rules='<?= htmlspecialchars($t['vtu_rules']) ?>'>
            <?= htmlspecialchars($t['template_name'] . ' — ' . $t['total_marks'] . ' Marks, ' . ($t['duration_minutes']/60) . 'H') ?>
          </option>
          <?php endforeach; ?>
        </select>
        <div id="templateInfo" style="margin-top:8px;padding:10px;background:#eff6ff;border-radius:8px;border:1px solid #bfdbfe;font-size:13px;color:#1e40af;display:none;"></div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div class="form-group">
          <label class="form-label">Academic Year *</label>
          <input type="text" name="academic_year" class="form-control" placeholder="e.g. 2024-25"
                 value="<?= date('Y') . '-' . (date('Y')+1) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Number of Sets</label>
          <select name="num_sets" class="form-control">
            <option value="1">1 Set</option>
            <option value="2">2 Sets (A & B)</option>
            <option value="3">3 Sets</option>
            <option value="4">4 Sets</option>
          </select>
        </div>
      </div>

      <!-- SHUFFLE SETTINGS -->
      <div class="card" style="background:#f8fafc;border:1px dashed #cbd5e1;padding:16px;margin-bottom:16px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
          <i class="fas fa-shuffle" style="color:#1a4fd6;"></i>
          <strong style="font-size:14px;">Shuffling Options</strong>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
          <div>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;">
              <input type="checkbox" name="shuffle" value="1" checked
                     onchange="document.getElementById('shuffleTypeWrap').style.display=this.checked?'block':'none'">
              Enable Question Shuffling
            </label>
          </div>
          <div id="shuffleTypeWrap">
            <select name="shuffle_type" class="form-control" style="font-size:13px;">
              <option value="within_section">Within Section (VTU Standard)</option>
              <option value="full_shuffle">Full Shuffle</option>
              <option value="question_swap">OR-Pair Swap (SEE)</option>
            </select>
          </div>
        </div>
        <div style="margin-top:12px;">
          <label class="form-label" style="font-size:12px;">Avoid Questions Used in Last (days)</label>
          <input type="number" name="avoid_recent_days" class="form-control" value="30" min="0" max="365" style="max-width:120px;">
        </div>
      </div>

      <!-- PAPER SETTINGS -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div class="form-group">
          <label class="form-label">Watermark Text</label>
          <input type="text" name="watermark" class="form-control" value="CONFIDENTIAL" placeholder="CONFIDENTIAL">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Special Instructions (printed on paper)</label>
        <textarea name="instructions" class="form-control" placeholder="e.g. All questions carry equal marks. Draw neat diagrams wherever necessary.">All questions carry equal marks. Draw neat diagrams wherever necessary.</textarea>
      </div>

      <button type="submit" class="btn btn-primary" style="font-size:15px;padding:12px 28px;" id="genBtn">
        <i class="fas fa-wand-magic-sparkles"></i> Generate Question Paper
      </button>
    </form>
  </div>

  <!-- RIGHT: QUESTION BANK STATS + VTU RULES -->
  <div style="display:flex;flex-direction:column;gap:20px;">
    <div class="card">
      <div class="card-title"><i class="fas fa-database"></i> Question Bank Status</div>
      <div id="qbStats" style="color:#64748b;font-size:14px;">Select a subject to view stats</div>
    </div>

    <div class="card" style="background:#fffbf0;border-color:#fde68a;">
      <div class="card-title" style="color:#92400e;"><i class="fas fa-graduation-cap"></i> VTU Rules Info</div>
      <div style="font-size:13px;color:#78350f;line-height:1.8;">
        <div><strong>CIE (30 Marks):</strong></div>
        <div>• Part A: 5×2 = 10 marks (all compulsory)</div>
        <div>• Part B: 3 out of 5 × 5 = 15 marks</div>
        <div>• Units 1-5 covered across sections</div>
        <br>
        <div><strong>SEE (100 Marks):</strong></div>
        <div>• 5 modules × 20 marks each</div>
        <div>• Each module: Q(n) OR Q(n+1)</div>
        <div>• Duration: 3 hours</div>
        <br>
        <div><strong>CO-PO Mapping:</strong></div>
        <div>• Auto-computed per paper</div>
        <div>• Bloom's levels tagged per question</div>
        <div>• 1=Low, 2=Medium, 3=High attainment</div>
      </div>
    </div>

    <!-- Recently generated -->
    <?php
    $recent = $db->fetchAll("SELECT qp.id, qp.paper_code, qp.exam_type, s.subject_code, qp.status FROM question_papers qp JOIN subjects s ON qp.subject_id=s.id WHERE qp.generated_by=? ORDER BY qp.created_at DESC LIMIT 5", [$user['id']]);
    ?>
    <?php if ($recent): ?>
    <div class="card">
      <div class="card-title"><i class="fas fa-clock-rotate-left"></i> My Recent Papers</div>
      <?php foreach ($recent as $rp): ?>
      <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:13px;">
        <div>
          <div style="font-weight:500;"><?= htmlspecialchars($rp['subject_code']) ?></div>
          <div style="color:#94a3b8;font-size:11px;"><?= htmlspecialchars($rp['paper_code']) ?></div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
          <span class="badge badge-info" style="font-size:10px;"><?= $rp['exam_type'] ?></span>
          <a href="view.php?id=<?= $rp['id'] ?>" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i></a>
          <a href="print.php?id=<?= $rp['id'] ?>" class="btn btn-outline btn-sm" target="_blank"><i class="fas fa-print"></i></a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
const subjectsData = {};

async function loadSubjects() {
  const deptId = document.getElementById('deptSelect').value;
  const sem    = document.getElementById('semSelect').value;
  const sel    = document.getElementById('subjectSelect');

  sel.innerHTML = '<option value="">Loading...</option>';
  if (!deptId || !sem) {
    sel.innerHTML = '<option value="">— Select Department & Semester first —</option>';
    return;
  }

  try {
    const res = await fetch(`../../api/subjects.php?dept=${deptId}&sem=${sem}`);
    const data = await res.json();
    sel.innerHTML = '<option value="">Select Subject</option>';
    data.forEach(s => {
      const opt = new Option(`${s.subject_code} – ${s.subject_name}`, s.id);
      opt.dataset.info = JSON.stringify(s);
      sel.add(opt);
    });
  } catch (e) {
    sel.innerHTML = '<option value="">Error loading subjects</option>';
  }
}

function showSubjectInfo(sel) {
  const opt = sel.options[sel.selectedIndex];
  if (!opt.dataset.info) return;
  const s = JSON.parse(opt.dataset.info);
  const infoDiv = document.getElementById('subjectInfo');
  infoDiv.innerHTML = `Credits: <b>${s.credits}</b> &nbsp;|&nbsp; Units: <b>${s.total_units}</b> &nbsp;|&nbsp; Type: <b>${s.subject_type}</b>`;
  infoDiv.style.display = 'block';

  // Load QB stats
  loadQBStats(s.id);
}

async function loadQBStats(subjectId) {
  try {
    const res = await fetch(`../../api/qb_stats.php?subject=${subjectId}`);
    const data = await res.json();
    const div = document.getElementById('qbStats');
    if (data.total === 0) {
      div.innerHTML = `<div style="color:#ef4444;"><i class="fas fa-exclamation-triangle"></i> No questions found in bank for this subject!</div>`;
    } else {
      div.innerHTML = `
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
          <div style="background:#f0fdf4;padding:10px;border-radius:8px;text-align:center;">
            <div style="font-size:22px;font-weight:800;color:#166534;">${data.total}</div>
            <div style="font-size:11px;color:#64748b;">Total Questions</div>
          </div>
          <div style="background:#eff6ff;padding:10px;border-radius:8px;text-align:center;">
            <div style="font-size:22px;font-weight:800;color:#1e40af;">${data.approved}</div>
            <div style="font-size:11px;color:#64748b;">Approved</div>
          </div>
          <div style="background:#fef3c7;padding:10px;border-radius:8px;text-align:center;">
            <div style="font-size:22px;font-weight:800;color:#92400e;">${data['2mark']}</div>
            <div style="font-size:11px;color:#64748b;">2-Mark Qs</div>
          </div>
          <div style="background:#ede9fe;padding:10px;border-radius:8px;text-align:center;">
            <div style="font-size:22px;font-weight:800;color:#6d28d9;">${data['10mark']}</div>
            <div style="font-size:11px;color:#64748b;">10-Mark Qs</div>
          </div>
        </div>
      `;
    }
  } catch(e) {}
}

function showTemplateInfo(sel) {
  const opt = sel.options[sel.selectedIndex];
  if (!opt.dataset.rules) return;
  const rules = JSON.parse(opt.dataset.rules);
  const div = document.getElementById('templateInfo');
  let html = `<strong>Structure:</strong> `;
  rules.sections.forEach((s, i) => {
    html += `Part ${s.section}: ${s.questions_to_answer} of ${s.questions_to_attempt} × ${s.marks_per_question}M`;
    if (i < rules.sections.length - 1) html += ' &nbsp;|&nbsp; ';
  });
  html += `<br><em>${rules.note || ''}</em>`;
  div.innerHTML = html;
  div.style.display = 'block';
}
</script>

</div></div></body></html>
