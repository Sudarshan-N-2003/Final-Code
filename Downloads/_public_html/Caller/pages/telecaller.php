<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if ($_SESSION['role'] !== 'telecaller') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$userName = htmlspecialchars($_SESSION['name']);
$userEmail = htmlspecialchars($_SESSION['email']);
$userId = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Telecaller Dashboard — <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}:root{--primary:#667eea;--sidebar:#1a202c;--bg:#f7fafc;--text:#2d3748;--border:#e2e8f0;--success:#48bb78;--danger:#f56565}body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text)}.container{display:flex;min-height:100vh}.sidebar{width:260px;background:var(--sidebar);color:white;padding:0;position:fixed;height:100vh;overflow-y:auto}.sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,.1)}.sidebar-header h2{font-size:20px;margin-bottom:4px}.sidebar-header p{font-size:13px;opacity:.7}.nav{padding:16px 0}.nav-item{display:block;padding:12px 24px;color:rgba(255,255,255,.8);text-decoration:none;transition:.2s;cursor:pointer}.nav-item:hover,.nav-item.active{background:rgba(255,255,255,.1);color:white}.main{margin-left:260px;flex:1;padding:32px}.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:32px}.header h1{font-size:28px;font-weight:700}.user-info{display:flex;align-items:center;gap:16px}.user-name{font-weight:600;font-size:14px}.btn-logout{padding:8px 16px;background:var(--danger);color:white;border:none;border-radius:6px;cursor:pointer;font-size:14px}.content-area{background:white;padding:28px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.1)}.table-container{overflow-x:auto;margin-top:20px}.table{width:100%;border-collapse:collapse}.table th{background:#f7fafc;padding:12px;text-align:left;font-weight:600;font-size:13px;color:#4a5568;border-bottom:2px solid var(--border)}.table td{padding:12px;border-bottom:1px solid var(--border);font-size:14px}.table tr:hover{background:#f7fafc}.badge{padding:4px 12px;border-radius:12px;font-size:12px;font-weight:600}.badge-pending{background:#fef5e7;color:#d4a017}.badge-accepted{background:#d4edda;color:#155724}.badge-rejected{background:#f8d7da;color:#721c24}.badge-callback{background:#d1ecf1;color:#0c5460}.badge-in_progress{background:#fff3cd;color:#856404}.btn{padding:8px 16px;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:13px;transition:.2s;margin-right:4px}.btn-primary{background:var(--primary);color:white}.btn-success{background:var(--success);color:white}.btn-danger{background:var(--danger);color:white}.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center}.modal.active{display:flex}.modal-content{background:white;padding:32px;border-radius:12px;max-width:500px;width:90%;max-height:90vh;overflow-y:auto}.modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.modal-header h3{font-size:20px;font-weight:700}.modal-close{background:none;border:none;font-size:28px;cursor:pointer;color:#999}.form-group{margin-bottom:20px}.form-group label{display:block;margin-bottom:8px;font-weight:600;font-size:14px}.form-group select,.form-group textarea{width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:6px;font-size:14px;font-family:inherit}.form-group textarea{resize:vertical;min-height:100px}.alert{padding:12px 16px;border-radius:6px;margin-bottom:16px;display:none}.alert.show{display:block}.alert-success{background:#d4edda;color:#155724}.alert-error{background:#f8d7da;color:#721c24}
</style>
</head>
<body>

<div class="container">
  <aside class="sidebar">
    <div class="sidebar-header">
      <h2><?= APP_NAME ?></h2>
      <p>Telecaller Dashboard</p>
    </div>
    
    <nav class="nav">
      <a class="nav-item active">👥 My Students</a>
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
    
    <div class="content-area">
      <h2 style="margin-bottom:24px">My Assigned Students</h2>
      
      <div class="table-container">
        <table class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Mobile</th>
              <th>College Type</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="students-table">
            <tr>
              <td colspan="6" style="text-align:center;padding:40px;color:#999">Loading...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<!-- Feedback Modal -->
<div class="modal" id="feedback-modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Update Status & Add Feedback</h3>
      <button class="modal-close" onclick="closeFeedbackModal()">&times;</button>
    </div>
    
    <div class="alert" id="feedback-alert"></div>
    
    <form id="feedback-form" onsubmit="submitFeedback(event)">
      <input type="hidden" id="feedback-student-id">
      <input type="hidden" id="feedback-status">
      
      <div style="background:#f7fafc;padding:16px;border-radius:8px;margin-bottom:20px">
        <strong>Student:</strong> <span id="feedback-student-name"></span><br>
        <strong>New Status:</strong> <span id="feedback-status-display" style="font-weight:600"></span>
      </div>
      
      <div class="form-group">
        <label>Call Status *</label>
        <select id="feedback-call-status" required>
          <option value="">-- Select Call Status --</option>
          <option value="answered">Answered</option>
          <option value="no_answer">No Answer</option>
          <option value="busy">Busy</option>
          <option value="invalid">Invalid Number</option>
          <option value="callback">Callback Required</option>
        </select>
      </div>
      
      <div class="form-group">
        <label>Notes/Comments *</label>
        <textarea id="feedback-notes" required placeholder="Enter your conversation notes, student's response, concerns, etc."></textarea>
      </div>
      
      <div class="form-group">
        <label>Next Action</label>
        <textarea id="feedback-next-action" placeholder="What should be done next? (Optional)"></textarea>
      </div>
      
      <div style="display:flex;gap:12px">
        <button type="submit" class="btn btn-primary" style="flex:1">Submit Feedback</button>
        <button type="button" class="btn btn-danger" onclick="closeFeedbackModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
const BASE = '<?= rtrim(BASE_URL, '/') ?>';
const MY_USER_ID = <?= $userId ?>;
let currentStudentId = null;
let currentStudentName = '';

async function loadMyStudents() {
  try {
    const res = await fetch(BASE + '/api/students.php?action=list', {credentials: 'include'});
    if (!res.ok) throw new Error('Failed to fetch');
    
    const data = await res.json();
    if (!Array.isArray(data)) throw new Error('Invalid response');
    
    const myStudents = data.filter(s => parseInt(s.assigned_to) === parseInt(MY_USER_ID));
    const tbody = document.getElementById('students-table');
    
    if (myStudents.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;color:#999">No students assigned yet</td></tr>';
      return;
    }
    
    tbody.innerHTML = myStudents.map((s, i) => `
      <tr>
        <td>${i + 1}</td>
        <td>${s.name || 'N/A'}</td>
        <td><a href="tel:${s.mobile}" style="color:var(--primary)">${s.mobile}</a></td>
        <td>${s.college_type || 'N/A'}</td>
        <td><span class="badge badge-${s.status}">${s.status}</span></td>
        <td>
          <button class="btn btn-primary" onclick="openFeedbackModal(${s.id}, '${s.name}', 'in_progress')">In Progress</button>
          <button class="btn btn-success" onclick="openFeedbackModal(${s.id}, '${s.name}', 'accepted')">Accept</button>
          <button class="btn btn-danger" onclick="openFeedbackModal(${s.id}, '${s.name}', 'rejected')">Reject</button>
        </td>
      </tr>
    `).join('');
  } catch (err) {
    console.error(err);
    document.getElementById('students-table').innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;color:#f56565">Error loading students</td></tr>';
  }
}

function openFeedbackModal(studentId, studentName, status) {
  currentStudentId = studentId;
  currentStudentName = studentName;
  
  document.getElementById('feedback-student-id').value = studentId;
  document.getElementById('feedback-status').value = status;
  document.getElementById('feedback-student-name').textContent = studentName;
  document.getElementById('feedback-status-display').textContent = status.toUpperCase().replace('_', ' ');
  
  // Change display color based on status
  const display = document.getElementById('feedback-status-display');
  display.style.color = status === 'accepted' ? 'var(--success)' : status === 'rejected' ? 'var(--danger)' : 'var(--primary)';
  
  // Reset form
  document.getElementById('feedback-form').reset();
  document.getElementById('feedback-student-id').value = studentId;
  document.getElementById('feedback-status').value = status;
  document.getElementById('feedback-alert').classList.remove('show');
  
  // Show modal
  document.getElementById('feedback-modal').classList.add('active');
}

function closeFeedbackModal() {
  document.getElementById('feedback-modal').classList.remove('active');
}

async function submitFeedback(e) {
  e.preventDefault();
  
  const studentId = document.getElementById('feedback-student-id').value;
  const status = document.getElementById('feedback-status').value;
  const callStatus = document.getElementById('feedback-call-status').value;
  const notes = document.getElementById('feedback-notes').value;
  const nextAction = document.getElementById('feedback-next-action').value;
  
  if (!callStatus || !notes) {
    showAlert('Please fill all required fields', 'error');
    return;
  }
  
  try {
    // Update student status
    const statusRes = await fetch(BASE + '/api/students.php?action=update', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      credentials: 'include',
      body: JSON.stringify({id: studentId, status})
    });
    
    const statusData = await statusRes.json();
    if (!statusData.success) throw new Error(statusData.error || 'Update failed');
    
    // Add feedback (you'll need to create this API endpoint)
    const feedbackRes = await fetch(BASE + '/api/feedback.php?action=add', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      credentials: 'include',
      body: JSON.stringify({
        student_id: studentId,
        call_status: callStatus,
        notes: notes,
        next_action: nextAction
      })
    });
    
    // Even if feedback API doesn't exist yet, we updated the status
    showAlert('Status updated successfully!', 'success');
    
    setTimeout(() => {
      closeFeedbackModal();
      loadMyStudents();
    }, 1500);
    
  } catch (err) {
    console.error(err);
    showAlert('Error: ' + err.message, 'error');
  }
}

function showAlert(msg, type) {
  const alert = document.getElementById('feedback-alert');
  alert.className = `alert alert-${type} show`;
  alert.textContent = msg;
}

async function logout() {
  try {
    await fetch(BASE + '/api/auth.php?action=logout', {credentials: 'include'});
  } catch (err) {}
  window.location.href = BASE + '/index.php';
}

loadMyStudents();
</script>
</body>
</html>