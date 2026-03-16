<?php
session_start();

// Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? 'telecaller';
    if ($role === 'admin' || $role === 'office') {
        header('Location: pages/admin.php');
    } else {
        header('Location: pages/telecaller.php');
    }
    exit;
}

require_once __DIR__ . '/includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}.login-box{background:white;padding:40px;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.3);width:100%;max-width:420px}.logo{text-align:center;margin-bottom:30px}.logo h1{font-size:28px;color:#333;margin-bottom:5px}.logo p{color:#666;font-size:14px}.form-group{margin-bottom:20px}.form-label{display:block;margin-bottom:8px;font-weight:500;color:#333;font-size:14px}.form-input{width:100%;padding:12px 15px;border:1px solid #ddd;border-radius:8px;font-size:15px;transition:.2s}.form-input:focus{border-color:#667eea;outline:none}.btn{width:100%;padding:14px;background:#667eea;color:white;border:none;border-radius:8px;font-size:16px;font-weight:600;cursor:pointer;transition:.2s}.btn:hover{background:#5568d3}.btn:disabled{opacity:.6;cursor:not-allowed}.btn-secondary{background:#6c757d;margin-top:10px}.btn-secondary:hover{background:#5a6268}.alert{padding:12px;border-radius:8px;margin-bottom:20px;display:none;font-size:14px}.alert.show{display:block}.alert-error{background:#fee;border:1px solid #fcc;color:#c33}.alert-success{background:#d4edda;border:1px solid #c3e6cb;color:#155724}.spinner{border:2px solid rgba(255,255,255,.3);border-top-color:white;border-radius:50%;width:16px;height:16px;animation:spin .6s linear infinite;display:inline-block;margin-right:8px}@keyframes spin{to{transform:rotate(360deg)}}.link{color:#667eea;text-decoration:none;font-size:14px;display:inline-block;margin-top:15px;cursor:pointer}.link:hover{text-decoration:underline}.hidden{display:none}.back-link{color:#666;font-size:14px;margin-top:15px;cursor:pointer;display:inline-block}.back-link:hover{color:#333}
</style>
</head>
<body>
<div class="login-box">
  <div class="logo">
    <h1>📞 <?= APP_NAME ?></h1>
    <p>Telecaller Management System</p>
  </div>
  
  <div class="alert" id="alert"></div>
  
  <!-- Login Form -->
  <div id="login-form-container">
    <form id="form" onsubmit="login(event)">
      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" class="form-input" id="email" required autofocus>
      </div>
      
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" class="form-input" id="password" required>
      </div>
      
      <button type="submit" class="btn" id="btn">Sign In</button>
    </form>
    
    <a class="link" onclick="showForgotPassword()">Forgot Password?</a>
  </div>
  
  <!-- Forgot Password - Email Entry -->
  <div id="forgot-password-container" class="hidden">
    <h2 style="font-size:20px;margin-bottom:20px;color:#333">Forgot Password</h2>
    <form id="forgot-form" onsubmit="submitEmail(event)">
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" class="form-input" id="forgot-email" required>
      </div>
      
      <button type="submit" class="btn" id="forgot-btn">Continue</button>
      <button type="button" class="btn btn-secondary" onclick="showLogin()">Back to Login</button>
    </form>
  </div>
  
  <!-- Verification - Email & DOB -->
  <div id="verify-container" class="hidden">
    <h2 style="font-size:20px;margin-bottom:20px;color:#333">Verify Identity</h2>
    <form id="verify-form" onsubmit="verifyIdentity(event)">
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" class="form-input" id="verify-email" readonly style="background:#f5f5f5">
      </div>
      
      <div class="form-group">
        <label class="form-label">Date of Birth</label>
        <input type="date" class="form-input" id="verify-dob" required>
      </div>
      
      <button type="submit" class="btn" id="verify-btn">Verify</button>
      <button type="button" class="btn btn-secondary" onclick="showLogin()">Cancel</button>
    </form>
  </div>
  
  <!-- Reset Password -->
  <div id="reset-container" class="hidden">
    <h2 style="font-size:20px;margin-bottom:20px;color:#333">Reset Password</h2>
    <form id="reset-form" onsubmit="resetPassword(event)">
      <div class="form-group">
        <label class="form-label">New Password</label>
        <input type="password" class="form-input" id="new-password" required minlength="8">
        <small style="color:#666;font-size:12px">Minimum 8 characters</small>
      </div>
      
      <div class="form-group">
        <label class="form-label">Confirm Password</label>
        <input type="password" class="form-input" id="confirm-password" required>
      </div>
      
      <button type="submit" class="btn" id="reset-btn">Reset Password</button>
    </form>
  </div>
</div>

<script>
const BASE = '<?= rtrim(BASE_URL, '/') ?>';
let currentEmail = '';

function showAlert(msg, type='error') {
  const alert = document.getElementById('alert');
  alert.className = `alert alert-${type} show`;
  alert.textContent = msg;
  setTimeout(() => alert.classList.remove('show'), 5000);
}

function showLogin() {
  document.getElementById('login-form-container').classList.remove('hidden');
  document.getElementById('forgot-password-container').classList.add('hidden');
  document.getElementById('verify-container').classList.add('hidden');
  document.getElementById('reset-container').classList.add('hidden');
  document.getElementById('alert').classList.remove('show');
}

function showForgotPassword() {
  document.getElementById('login-form-container').classList.add('hidden');
  document.getElementById('forgot-password-container').classList.remove('hidden');
  document.getElementById('verify-container').classList.add('hidden');
  document.getElementById('reset-container').classList.add('hidden');
  document.getElementById('alert').classList.remove('show');
  document.getElementById('forgot-email').focus();
}

function showVerify(email) {
  document.getElementById('login-form-container').classList.add('hidden');
  document.getElementById('forgot-password-container').classList.add('hidden');
  document.getElementById('verify-container').classList.remove('hidden');
  document.getElementById('reset-container').classList.add('hidden');
  document.getElementById('verify-email').value = email;
  document.getElementById('alert').classList.remove('show');
}

function showReset() {
  document.getElementById('login-form-container').classList.add('hidden');
  document.getElementById('forgot-password-container').classList.add('hidden');
  document.getElementById('verify-container').classList.add('hidden');
  document.getElementById('reset-container').classList.remove('hidden');
  document.getElementById('alert').classList.remove('show');
}

async function login(e) {
  e.preventDefault();
  const email = document.getElementById('email').value;
  const password = document.getElementById('password').value;
  const btn = document.getElementById('btn');
  
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span>Signing in...';
  
  try {
    const res = await fetch(BASE + '/api/auth.php?action=login', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      credentials: 'include',
      body: JSON.stringify({email, password})
    });
    
    const data = await res.json();
    
    if (data.success) {
      // Redirect based on role
      if (data.role === 'admin' || data.role === 'office') {
        window.location.href = BASE + '/pages/admin.php';
      } else {
        window.location.href = BASE + '/pages/telecaller.php';
      }
    } else {
      showAlert(data.error || 'Login failed');
      btn.disabled = false;
      btn.innerHTML = 'Sign In';
    }
  } catch (err) {
    showAlert('Connection error. Please check your internet.');
    btn.disabled = false;
    btn.innerHTML = 'Sign In';
  }
}

async function submitEmail(e) {
  e.preventDefault();
  const email = document.getElementById('forgot-email').value;
  const btn = document.getElementById('forgot-btn');
  
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span>Processing...';
  
  try {
    const res = await fetch(BASE + '/api/auth.php?action=forgot_password', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({email})
    });
    
    const data = await res.json();
    
    if (data.success) {
      currentEmail = email;
      showVerify(email);
    } else {
      showAlert(data.error);
    }
    btn.disabled = false;
    btn.innerHTML = 'Continue';
  } catch (err) {
    showAlert('Connection error');
    btn.disabled = false;
    btn.innerHTML = 'Continue';
  }
}

async function verifyIdentity(e) {
  e.preventDefault();
  const email = document.getElementById('verify-email').value;
  const dob = document.getElementById('verify-dob').value;
  const btn = document.getElementById('verify-btn');
  
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span>Verifying...';
  
  try {
    const res = await fetch(BASE + '/api/auth.php?action=verify_reset', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      credentials: 'include',
      body: JSON.stringify({email, dob})
    });
    
    const data = await res.json();
    
    if (data.success && data.verified) {
      showReset();
    } else {
      showAlert(data.error || 'Verification failed');
    }
    btn.disabled = false;
    btn.innerHTML = 'Verify';
  } catch (err) {
    showAlert('Connection error');
    btn.disabled = false;
    btn.innerHTML = 'Verify';
  }
}

async function resetPassword(e) {
  e.preventDefault();
  const newPassword = document.getElementById('new-password').value;
  const confirmPassword = document.getElementById('confirm-password').value;
  const btn = document.getElementById('reset-btn');
  
  if (newPassword !== confirmPassword) {
    showAlert('Passwords do not match');
    return;
  }
  
  if (newPassword.length < 8) {
    showAlert('Password must be at least 8 characters');
    return;
  }
  
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span>Resetting...';
  
  try {
    const res = await fetch(BASE + '/api/auth.php?action=reset_password', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      credentials: 'include',
      body: JSON.stringify({new_password: newPassword, confirm_password: confirmPassword})
    });
    
    const data = await res.json();
    
    if (data.success) {
      showAlert('Password reset successfully! Please login.', 'success');
      setTimeout(() => {
        showLogin();
        document.getElementById('email').value = currentEmail;
      }, 2000);
    } else {
      showAlert(data.error || 'Reset failed');
    }
    btn.disabled = false;
    btn.innerHTML = 'Reset Password';
  } catch (err) {
    showAlert('Connection error');
    btn.disabled = false;
    btn.innerHTML = 'Reset Password';
  }
}
</script>
</body>
</html>
