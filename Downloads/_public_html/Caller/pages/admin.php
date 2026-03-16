<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'office') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$userName = htmlspecialchars($_SESSION['name']);
$userEmail = htmlspecialchars($_SESSION['email']);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}:root{--primary:#667eea;--sidebar:#1a202c;--bg:#f7fafc;--text:#2d3748;--border:#e2e8f0;--success:#48bb78;--danger:#f56565;--warning:#f59e0b}body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text)}.container{display:flex;min-height:100vh}.sidebar{width:260px;background:var(--sidebar);color:white;padding:0;position:fixed;height:100vh;overflow-y:auto}.sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,.1)}.sidebar-header h2{font-size:20px;margin-bottom:4px}.sidebar-header p{font-size:13px;opacity:.7}.nav{padding:16px 0}.nav-section{margin-bottom:24px}.nav-title{padding:8px 24px;font-size:11px;text-transform:uppercase;letter-spacing:1px;opacity:.5;font-weight:600}.nav-item{display:block;padding:12px 24px;color:rgba(255,255,255,.8);text-decoration:none;transition:.2s;cursor:pointer}.nav-item:hover,.nav-item.active{background:rgba(255,255,255,.1);color:white}.main{margin-left:260px;flex:1;padding:32px}.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:32px}.header h1{font-size:28px;font-weight:700}.user-info{display:flex;align-items:center;gap:16px}.user-name{font-weight:600;font-size:14px}.btn-logout{padding:8px 16px;background:var(--danger);color:white;border:none;border-radius:6px;cursor:pointer;font-size:14px}.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;margin-bottom:32px}.stat-card{background:white;padding:24px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.1)}.stat-card h3{font-size:13px;color:#718096;font-weight:600;margin-bottom:8px}.stat-card .value{font-size:32px;font-weight:700;color:var(--primary)}.content-area{background:white;padding:28px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.1);display:none}.content-area.active{display:block}.form-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin-bottom:20px}.form-group{margin-bottom:16px}.form-group label{display:block;margin-bottom:6px;font-weight:500;font-size:14px}.form-group input,.form-group select,.form-group textarea{width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:6px;font-size:14px}.form-group textarea{resize:vertical;min-height:80px}.btn{padding:10px 20px;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:14px;transition:.2s}.btn-primary{background:var(--primary);color:white}.btn-primary:hover{opacity:.9}.btn-success{background:var(--success);color:white}.btn-danger{background:var(--danger);color:white}.table-container{overflow-x:auto;margin-top:20px}.table{width:100%;border-collapse:collapse}.table th{background:#f7fafc;padding:12px;text-align:left;font-weight:600;font-size:13px;color:#4a5568;border-bottom:2px solid var(--border)}.table td{padding:12px;border-bottom:1px solid var(--border);font-size:14px}.table tr:hover{background:#f7fafc}.badge{padding:4px 12px;border-radius:12px;font-size:12px;font-weight:600}.badge-pending{background:#fef5e7;color:#d4a017}.badge-accepted{background:#d4edda;color:#155724}.badge-rejected{background:#f8d7da;color:#721c24}.badge-telecaller{background:#d1ecf1;color:#0c5460}.badge-admin{background:#f8d7da;color:#721c24}.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center}.modal.active{display:flex}.modal-content{background:white;padding:28px;border-radius:12px;max-width:500px;width:90%;max-height:90vh;overflow-y:auto}.modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}.modal-header h3{font-size:20px}.modal-close{background:none;border:none;font-size:24px;cursor:pointer;color:#999}.alert{padding:12px 16px;border-radius:6px;margin-bottom:16px;display:none}.alert.show{display:block}.alert-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb}.alert-error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}.search-bar{margin-bottom:20px}.search-bar input{width:100%;max-width:400px;padding:10px 16px;border:1px solid var(--border);border-radius:6px;font-size:14px}.file-upload{border:2px dashed var(--border);padding:32px;text-align:center;border-radius:8px;cursor:pointer;transition:.2s}.file-upload:hover{border-color:var(--primary);background:#f7fafc}.file-upload input{display:none}.hidden{display:none}.user-stats-table{margin-top:32px}.user-stats-table h3{margin-bottom:16px;font-size:18px}.progress-bar{background:#e2e8f0;height:8px;border-radius:4px;overflow:hidden;margin-top:4px}.progress-fill{height:100%;background:var(--success);transition:.3s}.mini-stat{display:inline-block;margin-right:12px;font-size:12px;color:#718096}
</style>
</head>
<body>

<div class="container">
  <aside class="sidebar">
    <div class="sidebar-header">
      <h2><?= APP_NAME ?></h2>
      <p>Admin Dashboard</p>
    </div>
    
    <nav class="nav">
      <div class="nav-section">
        <div class="nav-title">Main</div>
        <a class="nav-item active" onclick="showSection('dashboard')">📊 Dashboard</a>
        <a class="nav-item" onclick="showSection('performance')">📈 Performance</a>
      </div>
      
      <div class="nav-section">
        <div class="nav-title">Students</div>
        <a class="nav-item" onclick="showSection('add-student')">➕ Add Student</a>
        <a class="nav-item" onclick="showSection('students')">👥 All Students</a>
      </div>
      
      <div class="nav-section">
        <div class="nav-title">Team</div>
        <a class="nav-item" onclick="showSection('add-user')">➕ Add User</a>
        <a class="nav-item" onclick="showSection('users')">👤 View Users</a>
      </div>
      
      <div class="nav-section">
        <div class="nav-title">Reports</div>
        <a class="nav-item" onclick="exportCSV()">📥 Export Excel</a>
      </div>
    </nav>
  </aside>
  
  <main class="main">
    <div class="header">
      <h1>Welcome, <?= $userName ?></h1>
      <div class="user-info">
        <span class="user-name"><?= $userEmail ?></span>
        <button class="btn-logout" onclick="logout()">Logout</button>
      </div>
    </div>
    
    <!-- Dashboard -->
    <div id="dashboard" class="content-area active">
      <h2 style="margin-bottom:24px">Dashboard Overview</h2>
      
      <div class="stats-grid">
        <div class="stat-card">
          <h3>Total Students</h3>
          <div class="value" id="stat-total">0</div>
        </div>
        <div class="stat-card">
          <h3>Accepted</h3>
          <div class="value" id="stat-accepted" style="color:var(--success)">0</div>
        </div>
        <div class="stat-card">
          <h3>Rejected</h3>
          <div class="value" id="stat-rejected" style="color:var(--danger)">0</div>
        </div>
        <div class="stat-card">
          <h3>Pending</h3>
          <div class="value" id="stat-pending" style="color:var(--warning)">0</div>
        </div>
        <div class="stat-card">
          <h3>Unassigned</h3>
          <div class="value" id="stat-unassigned">0</div>
        </div>
      </div>
      
      <button class="btn btn-success" onclick="autoAssignAll()" style="margin-bottom:20px">🔄 Auto-Assign All Unassigned</button>
    </div>
    
    <!-- Performance Section -->
    <div id="performance" class="content-area">
      <h2 style="margin-bottom:24px">Telecaller Performance</h2>
      
      <div class="table-container">
        <table class="table">
          <thead>
            <tr>
              <th>Telecaller</th>
              <th>Total Assigned</th>
              <th>Pending</th>
              <th>In Progress</th>
              <th>Accepted</th>
              <th>Rejected</th>
              <th>Callback</th>
              <th>Success Rate</th>
            </tr>
          </thead>
          <tbody id="performance-table">
            <tr>
              <td colspan="8" style="text-align:center;padding:40px">Loading...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Add Student -->
    <div id="add-student" class="content-area">
      <h2 style="margin-bottom:24px">Add Student</h2>
      
      <div style="margin-bottom:32px">
        <h3 style="margin-bottom:16px">Single Student</h3>
        <div class="alert" id="add-alert"></div>
        <form id="add-student-form" onsubmit="addSingleStudent(event)">
          <div class="form-grid">
            <div class="form-group">
              <label>Name *</label>
              <input type="text" id="s-name" required>
            </div>
            <div class="form-group">
              <label>Mobile *</label>
              <input type="tel" id="s-mobile" required>
            </div>
            <div class="form-group">
              <label>Present College</label>
              <input type="text" id="s-college">
            </div>
            <div class="form-group">
              <label>College Type</label>
              <select id="s-type">
                <option>PU</option>
                <option>Diploma</option>
                <option>Other</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Address</label>
            <textarea id="s-address"></textarea>
          </div>
          <button type="submit" class="btn btn-primary">Add Student</button>
        </form>
      </div>
      
      <div>
        <h3 style="margin-bottom:16px">Bulk Upload (CSV)</h3>
<a href="sample.csv" download="students.csv"
style="background:#0d6efd;color:white;padding:10px 18px;text-decoration:none;border-radius:5px;display:inline-block;">
Sample CSV
</a>
        <div class="alert" id="bulk-alert"></div>
        <div class="file-upload" onclick="document.getElementById('csv-file').click()">
          <input type="file" id="csv-file" accept=".csv" onchange="handleCSV(event)">
          <p style="font-size:16px;font-weight:600;margin-bottom:8px">📁 Click to upload CSV</p>
          <p style="font-size:13px;color:#666">Format: Name, Mobile, College Type, Present College, Address</p>
        </div>
      </div>
    </div>
    
    <!-- All Students -->
    <div id="students" class="content-area">
      <h2 style="margin-bottom:24px">All Students</h2>
      <div class="search-bar">
        <input type="text" id="search-students" placeholder="Search students..." onkeyup="loadStudents()">
      </div>
      <div class="alert" id="students-alert"></div>
      <div class="table-container">
        <table class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Mobile</th>
              <th>College Type</th>
              <th>Status</th>
              <th>Assigned To</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="students-table"></tbody>
        </table>
      </div>
    </div>
    
    <!-- Add User -->
    <div id="add-user" class="content-area">
      <h2 style="margin-bottom:24px">Add User</h2>
      <div class="alert" id="user-add-alert"></div>
      <form id="add-user-form" onsubmit="addUser(event)">
        <div class="form-grid">
          <div class="form-group">
            <label>Name *</label>
            <input type="text" id="u-name" required>
          </div>
          <div class="form-group">
            <label>Email *</label>
            <input type="email" id="u-email" required>
          </div>
          <div class="form-group">
            <label>Phone *</label>
            <input type="tel" id="u-phone" required>
          </div>
          <div class="form-group">
            <label>Role *</label>
            <select id="u-role">
              <option value="telecaller">Telecaller</option>
              <option value="office">Office</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="form-group">
            <label>Gender *</label>
            <select id="u-gender">
              <option>Male</option>
              <option>Female</option>
              <option>Other</option>
            </select>
          </div>
          <div class="form-group">
            <label>Date of Birth *</label>
            <input type="date" id="u-dob" required>
          </div>
        </div>
        <button type="submit" class="btn btn-primary">Create User</button>
      </form>
    </div>
    
    <!-- View Users -->
    <div id="users" class="content-area">
      <h2 style="margin-bottom:24px">All Users</h2>
      <div class="alert" id="users-alert"></div>
      <div class="table-container">
        <table class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Role</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="users-table"></tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<!-- Student Detail Modal -->
<div class="modal" id="student-modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Student Details</h3>
      <button class="modal-close" onclick="closeModal('student-modal')">&times;</button>
    </div>
    <div id="student-detail"></div>
  </div>
</div>

<script>
const BASE = '<?= rtrim(BASE_URL, '/') ?>';

function showSection(section) {
  document.querySelectorAll('.content-area').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
  document.getElementById(section).classList.add('active');
  event.target.classList.add('active');
  
  if (section === 'dashboard') loadDashboard();
  if (section === 'performance') loadPerformance();
  if (section === 'students') loadStudents();
  if (section === 'users') loadUsers();
}

function showAlert(id, msg, type='success') {
  const alert = document.getElementById(id);
  alert.className = `alert alert-${type} show`;
  alert.textContent = msg;
  setTimeout(() => alert.classList.remove('show'), 5000);
}

async function loadDashboard() {
  try {
    const res = await fetch(BASE + '/api/students.php?action=stats', {credentials: 'include'});
    const data = await res.json();
    document.getElementById('stat-total').textContent = data.total || 0;
    document.getElementById('stat-accepted').textContent = data.accepted || 0;
    document.getElementById('stat-rejected').textContent = data.rejected || 0;
    document.getElementById('stat-pending').textContent = data.pending || 0;
    document.getElementById('stat-unassigned').textContent = data.unassigned || 0;
  } catch (err) {
    console.error(err);
  }
}

async function loadPerformance() {
  try {
    const res = await fetch(BASE + '/api/users.php?action=stats', {credentials: 'include'});
    const data = await res.json();
    
    const tbody = document.getElementById('performance-table');
    
    if (!Array.isArray(data) || data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px">No telecallers found</td></tr>';
      return;
    }
    
    tbody.innerHTML = data.map(u => {
      const total = parseInt(u.total || 0);
      const accepted = parseInt(u.accepted || 0);
      const rejected = parseInt(u.rejected || 0);
      const pending = parseInt(u.pending || 0);
      const inProgress = parseInt(u.in_progress || 0);
      const callback = parseInt(u.callback || 0);
      
      const successRate = total > 0 ? Math.round((accepted / total) * 100) : 0;
      
      return `
        <tr>
          <td><strong>${u.name}</strong><br><small style="color:#999">${u.email}</small></td>
          <td><strong>${total}</strong></td>
          <td><span class="badge badge-pending">${pending}</span></td>
          <td><span style="color:var(--primary);font-weight:600">${inProgress}</span></td>
          <td><span class="badge badge-accepted">${accepted}</span></td>
          <td><span class="badge badge-rejected">${rejected}</span></td>
          <td><span style="color:var(--warning);font-weight:600">${callback}</span></td>
          <td>
            <strong style="color:${successRate >= 50 ? 'var(--success)' : 'var(--danger)'}">${successRate}%</strong>
            <div class="progress-bar">
              <div class="progress-fill" style="width:${successRate}%;background:${successRate >= 50 ? 'var(--success)' : 'var(--danger)'}"></div>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  } catch (err) {
    console.error(err);
    document.getElementById('performance-table').innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--danger)">Error loading performance data</td></tr>';
  }
}

async function addSingleStudent(e) {
  e.preventDefault();
  
  const data = {
    name: document.getElementById('s-name').value,
    mobile: document.getElementById('s-mobile').value,
    present_college: document.getElementById('s-college').value,
    college_type: document.getElementById('s-type').value,
    address: document.getElementById('s-address').value
  };
  
  try {
    const res = await fetch(BASE + '/api/students.php?action=add', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      credentials: 'include',
      body: JSON.stringify(data)
    });
    
    const result = await res.json();
    
    if (result.success) {
      showAlert('add-alert', 'Student added successfully!');
      document.getElementById('add-student-form').reset();
      loadDashboard();
    } else {
      showAlert('add-alert', result.error, 'error');
    }
  } catch (err) {
    showAlert('add-alert', 'Error adding student', 'error');
  }
}

async function handleCSV(e) {
  const file = e.target.files[0];
  if (!file) return;
  
  const text = await file.text();
  const lines = text.split('\n').slice(1);
  const students = [];
  
  for (const line of lines) {
    const [name, mobile, type, college, address] = line.split(',').map(s => s.trim());
    if (name && mobile) {
      students.push({name, mobile, college_type: type || 'Other', present_college: college || '', address: address || ''});
    }
  }
  
  if (students.length === 0) {
    showAlert('bulk-alert', 'No valid students found in CSV', 'error');
    return;
  }
  
  try {
    const res = await fetch(BASE + '/api/students.php?action=bulk_add', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      credentials: 'include',
      body: JSON.stringify({students})
    });
    
    const data = await res.json();
    
    if (data.success) {
      showAlert('bulk-alert', `Successfully added ${data.added} students!`);
      document.getElementById('csv-file').value = '';
      loadDashboard();
    } else {
      showAlert('bulk-alert', data.error, 'error');
    }
  } catch (err) {
    showAlert('bulk-alert', 'Error uploading CSV', 'error');
  }
}

async function loadStudents() {
  const search = document.getElementById('search-students').value;
  
  try {
    const res = await fetch(BASE + `/api/students.php?action=list&search=${search}`, {credentials: 'include'});
    const students = await res.json();
    
    const tbody = document.getElementById('students-table');
    tbody.innerHTML = students.map((s, i) => `
      <tr>
        <td>${i + 1}</td>
        <td>${s.name}</td>
        <td>${s.mobile}</td>
        <td>${s.college_type}</td>
        <td><span class="badge badge-${s.status}">${s.status}</span></td>
        <td>${s.assigned_name || '<span class="badge badge-pending">Unassigned</span>'}</td>
        <td>
          <button class="btn btn-primary" style="padding:6px 12px;font-size:12px" onclick="viewStudent(${s.id})">View</button>
          <button class="btn btn-danger" style="padding:6px 12px;font-size:12px" onclick="deleteStudent(${s.id})">Delete</button>
        </td>
      </tr>
    `).join('');
  } catch (err) {
    console.error(err);
  }
}

async function viewStudent(id) {
  try {
    const res = await fetch(BASE + `/api/students.php?action=detail&id=${id}`, {credentials: 'include'});
    const s = await res.json();
    
    document.getElementById('student-detail').innerHTML = `
      <p><strong>Name:</strong> ${s.name}</p>
      <p><strong>Mobile:</strong> ${s.mobile}</p>
      <p><strong>College:</strong> ${s.present_college || '—'}</p>
      <p><strong>Type:</strong> ${s.college_type}</p>
      <p><strong>Address:</strong> ${s.address || '—'}</p>
      <p><strong>Status:</strong> <span class="badge badge-${s.status}">${s.status}</span></p>
      <p><strong>Assigned To:</strong> ${s.assigned_name || 'Unassigned'}</p>
      <p><strong>Created:</strong> ${s.created_at}</p>
    `;
    
    document.getElementById('student-modal').classList.add('active');
  } catch (err) {
    console.error(err);
  }
}

async function deleteStudent(id) {
  if (!confirm('Delete this student?')) return;
  
  try {
    const res = await fetch(BASE + `/api/students.php?action=delete&id=${id}`, {
      method: 'DELETE',
      credentials: 'include'
    });
    
    const data = await res.json();
    
    if (data.success) {
      showAlert('students-alert', 'Student deleted');
      loadStudents();
      loadDashboard();
    } else {
      showAlert('students-alert', data.error, 'error');
    }
  } catch (err) {
    showAlert('students-alert', 'Error deleting student', 'error');
  }
}

async function addUser(e) {
  e.preventDefault();
  
  const data = {
    name: document.getElementById('u-name').value,
    email: document.getElementById('u-email').value,
    phone: document.getElementById('u-phone').value,
    role: document.getElementById('u-role').value,
    gender: document.getElementById('u-gender').value,
    dob: document.getElementById('u-dob').value
  };
  
  try {
    const res = await fetch(BASE + '/api/users.php?action=add', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      credentials: 'include',
      body: JSON.stringify(data)
    });
    
    const result = await res.json();
    
    if (result.success) {
      showAlert('user-add-alert', `User created! Password: ${result.system_password}`);
      document.getElementById('add-user-form').reset();
    } else {
      showAlert('user-add-alert', result.error, 'error');
    }
  } catch (err) {
    showAlert('user-add-alert', 'Error creating user', 'error');
  }
}

async function loadUsers() {
  try {
    const res = await fetch(BASE + '/api/users.php?action=list', {credentials: 'include'});
    const users = await res.json();
    
    const tbody = document.getElementById('users-table');
    tbody.innerHTML = users.map((u, i) => `
      <tr>
        <td>${i + 1}</td>
        <td>${u.name}</td>
        <td>${u.email}</td>
        <td>${u.phone}</td>
        <td><span class="badge badge-${u.role}">${u.role}</span></td>
        <td>
          <button class="btn btn-danger" style="padding:6px 12px;font-size:12px" onclick="deleteUser(${u.id})">Delete</button>
        </td>
      </tr>
    `).join('');
  } catch (err) {
    console.error(err);
  }
}

async function deleteUser(id) {
  if (!confirm('Delete this user?')) return;
  
  try {
    const res = await fetch(BASE + `/api/users.php?action=delete&id=${id}`, {
      method: 'DELETE',
      credentials: 'include'
    });
    
    const data = await res.json();
    
    if (data.success) {
      showAlert('users-alert', 'User deleted');
      loadUsers();
    } else {
      showAlert('users-alert', data.error, 'error');
    }
  } catch (err) {
    showAlert('users-alert', 'Error deleting user', 'error');
  }
}

async function autoAssignAll() {
  if (!confirm('Auto-assign all unassigned students?')) return;
  
  try {
    const res = await fetch(BASE + '/api/students.php?action=auto_assign', {
      method: 'POST',
      credentials: 'include'
    });
    
    const data = await res.json();
    
    if (data.success) {
      alert(`✅ Assigned ${data.assigned} students!`);
      loadDashboard();
      loadStudents();
    } else {
      alert('❌ ' + data.error);
    }
  } catch (err) {
    alert('Error auto-assigning');
  }
}

async function exportCSV() {
  window.location.href = BASE + '/api/students.php?action=export';
}

async function logout() {
  try {
    await fetch(BASE + '/api/auth.php?action=logout', {credentials: 'include'});
    window.location.href = BASE + '/index.php';
  } catch (err) {
    window.location.href = BASE + '/index.php';
  }
}

function closeModal(id) {
  document.getElementById(id).classList.remove('active');
}

loadDashboard();
</script>
</body>
</html>