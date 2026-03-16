<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/database.php';

Auth::requireRole('principal');

$db = Database::getInstance();
$user = Auth::currentUser();
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $err = 'Security error.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'add_user') {
            $role = $_POST['role'] ?? '';
            // Principal can only add HOD or staff
            if (!in_array($role, ['hod', 'staff'])) {
                $err = 'You can only add HOD or Staff accounts.';
            } else {
                $username = trim($_POST['username'] ?? '');
                $email    = trim($_POST['email'] ?? '');
                $fullname = trim($_POST['full_name'] ?? '');
                $deptId   = (int)$_POST['department_id'];
                $password = $_POST['password'] ?? '';

                if (!$username || !$email || !$fullname || !$deptId || !$password) {
                    $err = 'All fields required.';
                } else {
                    $exists = $db->fetchOne("SELECT id FROM users WHERE username=? OR email=?", [$username, $email]);
                    if ($exists) { $err = 'Username/email already exists.'; }
                    else {
                        $db->insert('users', [
                            'username' => $username, 'email' => $email,
                            'password' => password_hash($password, PASSWORD_BCRYPT),
                            'full_name' => $fullname, 'role' => $role,
                            'department_id' => $deptId, 'is_active' => 1,
                            'created_by' => $user['id'],
                        ]);
                        Auth::logActivity($user['id'], 'ADD_USER', 'users', "Principal added $role: $username");
                        $msg = ucfirst($role) . " '$fullname' added successfully!";
                    }
                }
            }
        }
        if ($action === 'toggle') {
            $uid = (int)$_POST['user_id'];
            // Make sure it's hod/staff only
            $target = $db->fetchOne("SELECT * FROM users WHERE id=? AND role IN ('hod','staff')", [$uid]);
            if ($target) {
                $db->update('users', ['is_active' => $target['is_active'] ? 0 : 1], 'id=?', [$uid]);
                $msg = 'Status updated.';
            }
        }
    }
}

$roleFilter = $_GET['role'] ?? '';
$deptFilter = (int)($_GET['dept'] ?? 0);

$where = ["u.role IN ('hod','staff')"]; $params = [];
if ($roleFilter) { $where[] = 'u.role=?'; $params[] = $roleFilter; }
if ($deptFilter) { $where[] = 'u.department_id=?'; $params[] = $deptFilter; }

$users = $db->fetchAll("SELECT u.*, d.name as dept_name, d.code as dept_code FROM users u LEFT JOIN departments d ON u.department_id=d.id WHERE " . implode(' AND ', $where) . " AND u.is_active IN (0,1) ORDER BY u.role, u.full_name", $params);
$departments = $db->fetchAll("SELECT * FROM departments ORDER BY name");

$pageTitle = 'Manage HODs & Staff';
require_once __DIR__ . '/../../includes/layout.php';
?>

<?php if ($msg): ?><div class="flash-msg flash-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="flash-msg flash-error"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div style="display:flex;justify-content:flex-end;margin-bottom:18px;gap:10px;">
  <button class="btn btn-primary" style="background:#8b5cf6;" onclick="openModal('addModal','hod')">
    <i class="fas fa-user-plus"></i> Add HOD
  </button>
  <button class="btn btn-primary" style="background:#10b981;" onclick="openModal('addModal','staff')">
    <i class="fas fa-user-plus"></i> Add Staff
  </button>
</div>

<!-- Filter bar -->
<div class="card" style="margin-bottom:18px;">
  <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
    <div class="form-group" style="margin:0;min-width:140px;">
      <label class="form-label">Role</label>
      <select name="role" class="form-control">
        <option value="">HOD + Staff</option>
        <option value="hod" <?= $roleFilter==='hod'?'selected':'' ?>>HOD Only</option>
        <option value="staff" <?= $roleFilter==='staff'?'selected':'' ?>>Staff Only</option>
      </select>
    </div>
    <div class="form-group" style="margin:0;min-width:180px;">
      <label class="form-label">Department</label>
      <select name="dept" class="form-control">
        <option value="">All Departments</option>
        <?php foreach ($departments as $d): ?>
        <option value="<?= $d['id'] ?>" <?= $deptFilter==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
    <a href="users.php" class="btn btn-outline">Clear</a>
  </form>
</div>

<!-- Users table -->
<div class="card">
  <div class="card-title"><i class="fas fa-users"></i> HODs & Staff (<?= count($users) ?>)</div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Name</th><th>Username</th><th>Role</th><th>Department</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($users as $i => $u): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="width:32px;height:32px;border-radius:50%;background:<?= $u['role']==='hod'?'#8b5cf6':'#10b981' ?>;color:white;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;">
                <?= strtoupper(substr($u['full_name'],0,1)) ?>
              </div>
              <div>
                <div style="font-weight:600;"><?= htmlspecialchars($u['full_name']) ?></div>
                <div style="font-size:11px;color:#94a3b8;"><?= htmlspecialchars($u['email']) ?></div>
              </div>
            </div>
          </td>
          <td><code style="font-size:12px;"><?= htmlspecialchars($u['username']) ?></code></td>
          <td><span class="badge <?= $u['role']==='hod'?'badge-purple':'badge-success' ?>"><?= ucfirst($u['role']) ?></span></td>
          <td><?= $u['dept_code'] ? '<span class="badge badge-info">'.htmlspecialchars($u['dept_code']).'</span>' : '—' ?></td>
          <td><span class="badge <?= $u['is_active']?'badge-success':'badge-danger' ?>"><?= $u['is_active']?'Active':'Inactive' ?></span></td>
          <td>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Toggle status?')">
              <input type="hidden" name="action" value="toggle">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
              <button type="submit" class="btn btn-outline btn-sm"><?= $u['is_active']?'Deactivate':'Activate' ?></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ADD USER MODAL -->
<div id="addModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
  <div style="background:white;border-radius:16px;padding:28px;width:480px;max-width:95vw;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
      <h3 style="font-family:var(--font-head);" id="modalTitle">Add User</h3>
      <button onclick="document.getElementById('addModal').style.display='none'" style="background:none;border:none;font-size:20px;cursor:pointer;">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add_user">
      <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
      <input type="hidden" name="role" id="roleInput" value="hod">
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
        <div class="form-group" style="grid-column:1/-1;">
          <label class="form-label">Department *</label>
          <select name="department_id" class="form-control" required>
            <option value="">Select Department</option>
            <?php foreach ($departments as $d): ?>
            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?> (<?= $d['code'] ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px;">
        <button type="button" onclick="document.getElementById('addModal').style.display='none'" class="btn btn-outline">Cancel</button>
        <button type="submit" class="btn btn-primary" id="modalSubmitBtn">Add</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id, role) {
  document.getElementById('roleInput').value = role;
  document.getElementById('modalTitle').textContent = 'Add ' + role.toUpperCase();
  document.getElementById('modalSubmitBtn').textContent = 'Add ' + role.charAt(0).toUpperCase() + role.slice(1);
  document.getElementById(id).style.display = 'flex';
}
</script>

</div></div></body></html>
