<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/database.php';

Auth::requireRole('admin');
$db = Database::getInstance();
$msg = ''; $err = '';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $err = 'Security error. Please refresh.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add_user') {
            $username = trim($_POST['username'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $fullname = trim($_POST['full_name'] ?? '');
            $role     = $_POST['role'] ?? '';
            $deptId   = $_POST['department_id'] ?: null;
            $password = $_POST['password'] ?? '';

            if (!$username || !$email || !$fullname || !$role || !$password) {
                $err = 'All required fields must be filled.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $err = 'Invalid email address.';
            } elseif (strlen($password) < 6) {
                $err = 'Password must be at least 6 characters.';
            } else {
                // Check duplicate
                $exists = $db->fetchOne("SELECT id FROM users WHERE username=? OR email=?", [$username, $email]);
                if ($exists) {
                    $err = 'Username or email already exists.';
                } else {
                    $db->insert('users', [
                        'username'      => $username,
                        'email'         => $email,
                        'password'      => password_hash($password, PASSWORD_BCRYPT),
                        'full_name'     => $fullname,
                        'role'          => $role,
                        'department_id' => $deptId,
                        'is_active'     => 1,
                        'created_by'    => $_SESSION['user_id'],
                    ]);
                    Auth::logActivity($_SESSION['user_id'], 'ADD_USER', 'users', "Added user: $username ($role)");
                    $msg = "User '$fullname' added successfully!";
                }
            }
        }

        if ($action === 'toggle_status') {
            $uid = (int)$_POST['user_id'];
            $currentStatus = $db->fetchOne("SELECT is_active FROM users WHERE id=?", [$uid])['is_active'] ?? 0;
            $db->update('users', ['is_active' => $currentStatus ? 0 : 1], 'id=?', [$uid]);
            $msg = 'User status updated.';
        }

        if ($action === 'update_role') {
            $uid  = (int)$_POST['user_id'];
            $newRole = $_POST['new_role'] ?? '';
            $deptId  = $_POST['department_id'] ?: null;
            if (in_array($newRole, ['admin','principal','hod','staff'])) {
                $db->update('users', ['role' => $newRole, 'department_id' => $deptId], 'id=?', [$uid]);
                Auth::logActivity($_SESSION['user_id'], 'UPDATE_ROLE', 'users', "Changed user #$uid to $newRole");
                $msg = 'User role updated.';
            }
        }

        if ($action === 'reset_password') {
            $uid = (int)$_POST['user_id'];
            $newPw = $_POST['new_password'] ?? '';
            if (strlen($newPw) < 6) {
                $err = 'Password must be at least 6 characters.';
            } else {
                $db->update('users', ['password' => password_hash($newPw, PASSWORD_BCRYPT)], 'id=?', [$uid]);
                $msg = 'Password reset successfully.';
            }
        }

        if ($action === 'delete_user') {
            $uid = (int)$_POST['user_id'];
            if ($uid === (int)$_SESSION['user_id']) {
                $err = 'You cannot delete your own account.';
            } else {
                $db->update('users', ['is_active' => 0], 'id=?', [$uid]);
                $msg = 'User deactivated.';
            }
        }
    }
}

// Filters
$roleFilter = $_GET['role'] ?? '';
$deptFilter = $_GET['dept'] ?? '';
$search     = trim($_GET['search'] ?? '');

$where = ['1=1'];
$params = [];
if ($roleFilter) { $where[] = 'u.role=?'; $params[] = $roleFilter; }
if ($deptFilter) { $where[] = 'u.department_id=?'; $params[] = $deptFilter; }
if ($search) { $where[] = '(u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)'; $params = [...$params, "%$search%", "%$search%", "%$search%"]; }

$users = $db->fetchAll("SELECT u.*, d.name as dept_name, d.code as dept_code, uc.full_name as created_by_name FROM users u LEFT JOIN departments d ON u.department_id=d.id LEFT JOIN users uc ON u.created_by=uc.id WHERE " . implode(' AND ', $where) . " ORDER BY u.role, u.full_name", $params);
$departments = $db->fetchAll("SELECT * FROM departments ORDER BY name");

$pageTitle = 'User Management';
require_once __DIR__ . '/../../includes/layout.php';
?>

<?php if ($msg): ?><div class="flash-msg flash-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="flash-msg flash-error"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
  <div></div>
  <button class="btn btn-primary" onclick="openModal('addUserModal')">
    <i class="fas fa-user-plus"></i> Add New User
  </button>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:20px;">
  <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
    <div class="form-group" style="margin:0;flex:1;min-width:160px;">
      <label class="form-label">Search</label>
      <input type="text" name="search" class="form-control" placeholder="Name, username, email..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="form-group" style="margin:0;min-width:140px;">
      <label class="form-label">Role</label>
      <select name="role" class="form-control">
        <option value="">All Roles</option>
        <option value="admin" <?= $roleFilter==='admin'?'selected':'' ?>>Admin</option>
        <option value="principal" <?= $roleFilter==='principal'?'selected':'' ?>>Principal</option>
        <option value="hod" <?= $roleFilter==='hod'?'selected':'' ?>>HOD</option>
        <option value="staff" <?= $roleFilter==='staff'?'selected':'' ?>>Staff</option>
      </select>
    </div>
    <div class="form-group" style="margin:0;min-width:160px;">
      <label class="form-label">Department</label>
      <select name="dept" class="form-control">
        <option value="">All Departments</option>
        <?php foreach ($departments as $d): ?>
        <option value="<?= $d['id'] ?>" <?= $deptFilter==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['code']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
    <a href="users.php" class="btn btn-outline"><i class="fas fa-xmark"></i> Clear</a>
  </form>
</div>

<!-- Users Table -->
<div class="card">
  <div class="card-title"><i class="fas fa-users"></i> All Users (<?= count($users) ?>)</div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Username</th>
          <th>Email</th>
          <th>Role</th>
          <th>Department</th>
          <th>Status</th>
          <th>Last Login</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $i => $u):
          $roleColors = ['admin'=>'danger','principal'=>'purple','hod'=>'warning','staff'=>'success'];
          $rc = $roleColors[$u['role']] ?? 'info';
        ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="width:32px;height:32px;border-radius:50%;background:<?= ['admin'=>'#ef4444','principal'=>'#8b5cf6','hod'=>'#f59e0b','staff'=>'#10b981'][$u['role']] ?? '#64748b' ?>;color:white;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;">
                <?= strtoupper(substr($u['full_name'],0,1)) ?>
              </div>
              <div>
                <div style="font-weight:600;"><?= htmlspecialchars($u['full_name']) ?></div>
                <?php if ($u['created_by_name']): ?>
                <div style="font-size:11px;color:#94a3b8;">Added by <?= htmlspecialchars($u['created_by_name']) ?></div>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <td><code style="font-size:12px;"><?= htmlspecialchars($u['username']) ?></code></td>
          <td style="font-size:13px;"><?= htmlspecialchars($u['email']) ?></td>
          <td><span class="badge badge-<?= $rc ?>"><?= ucfirst($u['role']) ?></span></td>
          <td><?= $u['dept_name'] ? '<span class="badge badge-info">' . htmlspecialchars($u['dept_code']) . '</span>' : '<span style="color:#94a3b8;">—</span>' ?></td>
          <td>
            <span class="badge <?= $u['is_active'] ? 'badge-success' : 'badge-danger' ?>">
              <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
            </span>
          </td>
          <td style="font-size:12px;color:#64748b;"><?= $u['last_login'] ? date('d M y, h:i A', strtotime($u['last_login'])) : 'Never' ?></td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
              <button class="btn btn-outline btn-sm" onclick="openEditRole(<?= $u['id'] ?>, '<?= $u['role'] ?>', <?= $u['department_id'] ?? 'null' ?>)" title="Edit Role">
                <i class="fas fa-user-pen"></i>
              </button>
              <button class="btn btn-outline btn-sm" onclick="openResetPw(<?= $u['id'] ?>, '<?= htmlspecialchars($u['full_name']) ?>')" title="Reset Password">
                <i class="fas fa-key"></i>
              </button>
              <form method="POST" style="display:inline;" onsubmit="return confirm('Toggle user status?')">
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                <button type="submit" class="btn btn-outline btn-sm" title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>">
                  <i class="fas <?= $u['is_active'] ? 'fa-ban' : 'fa-check' ?>"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ADD USER MODAL -->
<div id="addUserModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;display:none;align-items:center;justify-content:center;">
  <div style="background:white;border-radius:16px;padding:28px;width:520px;max-width:95vw;max-height:90vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
      <h3 style="font-family:var(--font-head);font-size:18px;">Add New User</h3>
      <button onclick="closeModal('addUserModal')" style="background:none;border:none;font-size:20px;cursor:pointer;color:#64748b;">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add_user">
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
          <label class="form-label">Role *</label>
          <select name="role" class="form-control" required onchange="toggleDept(this.value)">
            <option value="">Select Role</option>
            <option value="admin">Admin</option>
            <option value="principal">Principal</option>
            <option value="hod">HOD</option>
            <option value="staff">Staff</option>
          </select>
        </div>
        <div class="form-group" id="deptField" style="display:none;">
          <label class="form-label">Department</label>
          <select name="department_id" class="form-control">
            <option value="">Select Department</option>
            <?php foreach ($departments as $d): ?>
            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?> (<?= $d['code'] ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Password *</label>
          <input type="password" name="password" class="form-control" required minlength="6">
        </div>
      </div>
      <div style="margin-top:8px;display:flex;gap:10px;justify-content:flex-end;">
        <button type="button" onclick="closeModal('addUserModal')" class="btn btn-outline">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add User</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT ROLE MODAL -->
<div id="editRoleModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
  <div style="background:white;border-radius:16px;padding:28px;width:400px;max-width:95vw;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
      <h3 style="font-family:var(--font-head);">Edit User Role</h3>
      <button onclick="closeModal('editRoleModal')" style="background:none;border:none;font-size:20px;cursor:pointer;">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="update_role">
      <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
      <input type="hidden" name="user_id" id="editUserId">
      <div class="form-group">
        <label class="form-label">New Role</label>
        <select name="new_role" id="editRole" class="form-control" onchange="toggleEditDept(this.value)">
          <option value="admin">Admin</option>
          <option value="principal">Principal</option>
          <option value="hod">HOD</option>
          <option value="staff">Staff</option>
        </select>
      </div>
      <div class="form-group" id="editDeptField">
        <label class="form-label">Department</label>
        <select name="department_id" id="editDept" class="form-control">
          <option value="">None</option>
          <?php foreach ($departments as $d): ?>
          <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?> (<?= $d['code'] ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end;">
        <button type="button" onclick="closeModal('editRoleModal')" class="btn btn-outline">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Role</button>
      </div>
    </form>
  </div>
</div>

<!-- RESET PASSWORD MODAL -->
<div id="resetPwModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
  <div style="background:white;border-radius:16px;padding:28px;width:380px;max-width:95vw;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
      <h3 style="font-family:var(--font-head);">Reset Password</h3>
      <button onclick="closeModal('resetPwModal')" style="background:none;border:none;font-size:20px;cursor:pointer;">×</button>
    </div>
    <p id="resetPwName" style="color:#64748b;margin-bottom:16px;font-size:14px;"></p>
    <form method="POST">
      <input type="hidden" name="action" value="reset_password">
      <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
      <input type="hidden" name="user_id" id="resetPwId">
      <div class="form-group">
        <label class="form-label">New Password (min 6 chars)</label>
        <input type="text" name="new_password" class="form-control" required minlength="6" placeholder="Enter new password">
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end;">
        <button type="button" onclick="closeModal('resetPwModal')" class="btn btn-outline">Cancel</button>
        <button type="submit" class="btn btn-danger"><i class="fas fa-key"></i> Reset Password</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id) {
  document.getElementById(id).style.display = 'flex';
}
function closeModal(id) {
  document.getElementById(id).style.display = 'none';
}
function toggleDept(role) {
  document.getElementById('deptField').style.display = ['hod','staff'].includes(role) ? 'block' : 'none';
}
function toggleEditDept(role) {
  document.getElementById('editDeptField').style.display = ['hod','staff'].includes(role) ? 'block' : 'none';
}
function openEditRole(uid, role, deptId) {
  document.getElementById('editUserId').value = uid;
  document.getElementById('editRole').value = role;
  if (deptId) document.getElementById('editDept').value = deptId;
  toggleEditDept(role);
  openModal('editRoleModal');
}
function openResetPw(uid, name) {
  document.getElementById('resetPwId').value = uid;
  document.getElementById('resetPwName').textContent = 'Resetting password for: ' + name;
  openModal('resetPwModal');
}
// Close on backdrop click
document.querySelectorAll('[id$="Modal"]').forEach(m => {
  m.addEventListener('click', function(e) {
    if (e.target === this) closeModal(this.id);
  });
});
<?php if ($msg || $err): ?>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[id$="Modal"]').forEach(m => m.style.display = 'none');
});
<?php endif; ?>
</script>

</div></div></body></html>
