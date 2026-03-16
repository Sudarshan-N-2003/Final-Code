<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/database.php';

Auth::requireRole('hod');

$db   = Database::getInstance();
$user = Auth::currentUser();
$deptId = $user['department_id'];
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $err = 'Security error.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'add_staff') {
            $username = trim($_POST['username'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $fullname = trim($_POST['full_name'] ?? '');
            $password = $_POST['password'] ?? '';

            if (!$username || !$email || !$fullname || !$password) {
                $err = 'All fields are required.';
            } elseif (strlen($password) < 6) {
                $err = 'Password must be at least 6 characters.';
            } else {
                $exists = $db->fetchOne("SELECT id FROM users WHERE username=? OR email=?", [$username, $email]);
                if ($exists) {
                    $err = 'Username or email already exists.';
                } else {
                    $db->insert('users', [
                        'username'      => $username,
                        'email'         => $email,
                        'password'      => password_hash($password, PASSWORD_BCRYPT),
                        'full_name'     => $fullname,
                        'role'          => 'staff',
                        'department_id' => $deptId,  // HOD can only add to OWN dept
                        'is_active'     => 1,
                        'created_by'    => $user['id'],
                    ]);
                    Auth::logActivity($user['id'], 'ADD_STAFF', 'users', "HOD added staff: $username to dept $deptId");
                    $msg = "Staff '$fullname' added to " . $user['dept_name'] . " department!";
                }
            }
        }

        if ($action === 'toggle') {
            $uid = (int)$_POST['user_id'];
            // Verify this staff belongs to HOD's department
            $target = $db->fetchOne("SELECT * FROM users WHERE id=? AND role='staff' AND department_id=?", [$uid, $deptId]);
            if ($target) {
                $db->update('users', ['is_active' => $target['is_active'] ? 0 : 1], 'id=?', [$uid]);
                $msg = 'Staff status updated.';
            } else {
                $err = 'Unauthorized action.';
            }
        }
    }
}

// Load only THIS dept's staff
$staffList = $db->fetchAll(
    "SELECT u.*, COUNT(q.id) as q_count, SUM(CASE WHEN q.is_approved=1 THEN 1 ELSE 0 END) as approved_q
     FROM users u
     LEFT JOIN questions q ON q.added_by=u.id AND q.is_active=1
     WHERE u.role='staff' AND u.department_id=?
     GROUP BY u.id ORDER BY u.is_active DESC, u.full_name",
    [$deptId]
);

$pageTitle = 'My Staff – ' . $user['dept_name'];
require_once __DIR__ . '/../../includes/layout.php';
?>

<?php if ($msg): ?><div class="flash-msg flash-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="flash-msg flash-error"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- Info banner: dept-restricted -->
<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:#1e40af;display:flex;align-items:center;gap:10px;">
  <i class="fas fa-shield-halved"></i>
  <span>As <strong><?= htmlspecialchars($user['dept_name']) ?> HOD</strong>, you can only add and manage staff for your department.</span>
</div>

<div style="display:flex;justify-content:flex-end;margin-bottom:18px;">
  <button class="btn btn-primary" onclick="document.getElementById('addModal').style.display='flex'">
    <i class="fas fa-user-plus"></i> Add Staff to <?= htmlspecialchars($user['dept_code']) ?>
  </button>
</div>

<div class="card">
  <div class="card-title"><i class="fas fa-users"></i> <?= htmlspecialchars($user['dept_name']) ?> Staff (<?= count($staffList) ?>)</div>
  <?php if (empty($staffList)): ?>
  <div style="text-align:center;padding:40px;color:#94a3b8;">
    <i class="fas fa-users" style="font-size:40px;display:block;margin-bottom:12px;color:#cbd5e1;"></i>
    <p>No staff members added yet.</p>
  </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Name</th><th>Username</th><th>Email</th><th>Questions Added</th><th>Approved</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($staffList as $i => $s): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="width:32px;height:32px;border-radius:50%;background:#10b981;color:white;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;">
                <?= strtoupper(substr($s['full_name'],0,1)) ?>
              </div>
              <strong><?= htmlspecialchars($s['full_name']) ?></strong>
            </div>
          </td>
          <td><code style="font-size:12px;"><?= htmlspecialchars($s['username']) ?></code></td>
          <td style="font-size:13px;"><?= htmlspecialchars($s['email']) ?></td>
          <td><strong><?= $s['q_count'] ?></strong></td>
          <td>
            <span style="color:#10b981;font-weight:600;"><?= $s['approved_q'] ?></span>
            / <?= $s['q_count'] ?>
          </td>
          <td><span class="badge <?= $s['is_active']?'badge-success':'badge-danger' ?>"><?= $s['is_active']?'Active':'Inactive' ?></span></td>
          <td>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Toggle staff status?')">
              <input type="hidden" name="action" value="toggle">
              <input type="hidden" name="user_id" value="<?= $s['id'] ?>">
              <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
              <button type="submit" class="btn btn-outline btn-sm">
                <i class="fas <?= $s['is_active']?'fa-ban':'fa-check' ?>"></i>
                <?= $s['is_active']?'Deactivate':'Activate' ?>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- ADD STAFF MODAL -->
<div id="addModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
  <div style="background:white;border-radius:16px;padding:28px;width:480px;max-width:95vw;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
      <h3 style="font-family:var(--font-head);">Add Staff – <?= htmlspecialchars($user['dept_name']) ?></h3>
      <button onclick="document.getElementById('addModal').style.display='none'" style="background:none;border:none;font-size:20px;cursor:pointer;">×</button>
    </div>
    <div style="background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:13px;color:#92400e;">
      <i class="fas fa-info-circle"></i> This staff will be assigned to <strong><?= htmlspecialchars($user['dept_name']) ?></strong> automatically.
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add_staff">
      <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="full_name" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Username *</label>
          <input type="text" name="username" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email *</label>
          <input type="email" name="email" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Password *</label>
          <input type="password" name="password" class="form-control" required minlength="6">
        </div>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px;">
        <button type="button" onclick="document.getElementById('addModal').style.display='none'" class="btn btn-outline">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add Staff</button>
      </div>
    </form>
  </div>
</div>

</div></div></body></html>
