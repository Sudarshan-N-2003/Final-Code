<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QPDS – VTU Question Paper System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --navy: #0a1628;
    --navy2: #0f2044;
    --blue: #1a4fd6;
    --blue-light: #2563eb;
    --gold: #f59e0b;
    --gold2: #fbbf24;
    --white: #ffffff;
    --gray: #94a3b8;
    --gray-light: #e2e8f0;
    --danger: #ef4444;
    --success: #10b981;
    --font-head: 'Syne', sans-serif;
    --font-body: 'DM Sans', sans-serif;
  }

  html, body {
    height: 100%;
    font-family: var(--font-body);
    background: var(--navy);
    overflow: hidden;
  }

  /* Animated BG */
  .bg-canvas {
    position: fixed; inset: 0; z-index: 0;
    background: radial-gradient(ellipse at 20% 20%, #1a3a6e 0%, transparent 60%),
                radial-gradient(ellipse at 80% 80%, #0f2044 0%, transparent 60%),
                var(--navy);
  }

  .grid-overlay {
    position: fixed; inset: 0; z-index: 0;
    background-image: 
      linear-gradient(rgba(26,79,214,0.07) 1px, transparent 1px),
      linear-gradient(90deg, rgba(26,79,214,0.07) 1px, transparent 1px);
    background-size: 50px 50px;
    animation: gridMove 20s linear infinite;
  }

  @keyframes gridMove {
    0% { background-position: 0 0; }
    100% { background-position: 50px 50px; }
  }

  .orb {
    position: fixed; border-radius: 50%; filter: blur(80px); z-index: 0;
    animation: float 8s ease-in-out infinite;
  }
  .orb1 { width: 400px; height: 400px; background: rgba(26,79,214,0.15); top: -100px; right: -100px; animation-delay: 0s; }
  .orb2 { width: 300px; height: 300px; background: rgba(245,158,11,0.08); bottom: -50px; left: -50px; animation-delay: 4s; }

  @keyframes float {
    0%, 100% { transform: translate(0,0); }
    50% { transform: translate(20px, -20px); }
  }

  /* MAIN LAYOUT */
  .page {
    position: relative; z-index: 1;
    min-height: 100vh;
    display: grid;
    grid-template-columns: 1fr 480px;
  }

  /* LEFT PANEL */
  .left-panel {
    display: flex; flex-direction: column; justify-content: center;
    padding: 60px 80px;
    position: relative;
  }

  .brand-logo {
    display: flex; align-items: center; gap: 14px;
    margin-bottom: 60px;
  }

  .logo-icon {
    width: 52px; height: 52px;
    background: linear-gradient(135deg, var(--blue), var(--blue-light));
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; color: var(--white);
    box-shadow: 0 8px 25px rgba(26,79,214,0.4);
  }

  .brand-text .name {
    font-family: var(--font-head);
    font-size: 22px; font-weight: 800;
    color: var(--white);
    letter-spacing: 2px;
    text-transform: uppercase;
  }

  .brand-text .tagline {
    font-size: 12px; color: var(--gray);
    letter-spacing: 1px;
  }

  .hero-headline {
    font-family: var(--font-head);
    font-size: clamp(32px, 4vw, 52px);
    font-weight: 800;
    color: var(--white);
    line-height: 1.1;
    margin-bottom: 24px;
  }

  .hero-headline span {
    background: linear-gradient(90deg, var(--gold), var(--gold2));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  .hero-desc {
    font-size: 16px; color: var(--gray);
    line-height: 1.7; max-width: 480px;
    margin-bottom: 48px;
  }

  .feature-list {
    display: flex; flex-direction: column; gap: 16px;
  }

  .feature-item {
    display: flex; align-items: center; gap: 14px;
    padding: 14px 18px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
  }

  .feature-item:hover {
    background: rgba(26,79,214,0.12);
    border-color: rgba(26,79,214,0.3);
    transform: translateX(4px);
  }

  .feature-icon {
    width: 36px; height: 36px;
    background: linear-gradient(135deg, var(--blue), var(--blue-light));
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; color: var(--white); flex-shrink: 0;
  }

  .feature-text strong {
    display: block; color: var(--white);
    font-size: 14px; font-weight: 500;
  }
  .feature-text span { color: var(--gray); font-size: 12px; }

  /* RIGHT PANEL */
  .right-panel {
    background: rgba(255,255,255,0.03);
    border-left: 1px solid rgba(255,255,255,0.08);
    backdrop-filter: blur(20px);
    display: flex; flex-direction: column;
    justify-content: center; align-items: center;
    padding: 50px 48px;
  }

  .login-card {
    width: 100%; max-width: 380px;
  }

  .login-title {
    font-family: var(--font-head);
    font-size: 28px; font-weight: 700;
    color: var(--white);
    margin-bottom: 6px;
    text-align: center;
  }

  .login-subtitle {
    text-align: center; color: var(--gray);
    font-size: 14px; margin-bottom: 36px;
  }

  /* Role selector */
  .role-selector {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 10px; margin-bottom: 28px;
  }

  .role-btn {
    padding: 12px 8px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 10px;
    color: var(--gray);
    font-family: var(--font-body);
    font-size: 13px; font-weight: 500;
    cursor: pointer; transition: all 0.2s;
    display: flex; flex-direction: column;
    align-items: center; gap: 6px;
  }

  .role-btn i { font-size: 18px; }

  .role-btn:hover {
    background: rgba(26,79,214,0.15);
    border-color: rgba(26,79,214,0.4);
    color: var(--white);
  }

  .role-btn.active {
    background: var(--blue);
    border-color: var(--blue-light);
    color: var(--white);
    box-shadow: 0 4px 20px rgba(26,79,214,0.4);
  }

  .role-btn.active i { color: var(--gold); }

  /* Form fields */
  .form-group { margin-bottom: 18px; }

  .form-label {
    display: block;
    font-size: 13px; font-weight: 500;
    color: var(--gray); margin-bottom: 8px;
    letter-spacing: 0.5px;
  }

  .input-wrap { position: relative; }

  .input-wrap i {
    position: absolute; left: 14px; top: 50%;
    transform: translateY(-50%);
    color: var(--gray); font-size: 15px;
    pointer-events: none;
  }

  .form-input {
    width: 100%;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 10px;
    padding: 13px 14px 13px 42px;
    color: var(--white);
    font-family: var(--font-body);
    font-size: 14px;
    transition: all 0.2s;
    outline: none;
  }

  .form-input::placeholder { color: rgba(148,163,184,0.5); }

  .form-input:focus {
    border-color: var(--blue-light);
    background: rgba(26,79,214,0.1);
    box-shadow: 0 0 0 3px rgba(26,79,214,0.15);
  }

  .toggle-pw {
    position: absolute; right: 14px; top: 50%;
    transform: translateY(-50%);
    background: none; border: none; color: var(--gray);
    cursor: pointer; font-size: 14px;
    transition: color 0.2s;
  }

  .toggle-pw:hover { color: var(--white); }

  .remember-row {
    display: flex; justify-content: space-between;
    align-items: center; margin-bottom: 24px;
    font-size: 13px;
  }

  .checkbox-label {
    display: flex; align-items: center; gap: 8px;
    color: var(--gray); cursor: pointer;
  }

  .checkbox-label input[type="checkbox"] {
    width: 16px; height: 16px;
    accent-color: var(--blue-light);
  }

  .forgot-link {
    color: var(--blue-light); text-decoration: none;
    transition: color 0.2s;
  }

  .forgot-link:hover { color: var(--gold); }

  .btn-login {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, var(--blue), var(--blue-light));
    border: none; border-radius: 10px;
    color: var(--white); font-family: var(--font-head);
    font-size: 15px; font-weight: 700;
    letter-spacing: 1px; text-transform: uppercase;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 20px rgba(26,79,214,0.3);
    position: relative; overflow: hidden;
  }

  .btn-login::after {
    content: '';
    position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.15), transparent);
    opacity: 0; transition: opacity 0.3s;
  }

  .btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(26,79,214,0.5);
  }

  .btn-login:hover::after { opacity: 1; }
  .btn-login:active { transform: translateY(0); }

  .btn-login .spinner {
    display: none;
    width: 18px; height: 18px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto;
  }

  @keyframes spin { to { transform: rotate(360deg); } }

  .divider {
    display: flex; align-items: center; gap: 12px;
    margin: 20px 0; color: var(--gray); font-size: 12px;
  }
  .divider::before, .divider::after {
    content: ''; flex: 1; height: 1px;
    background: rgba(255,255,255,0.1);
  }

  .alert {
    padding: 12px 16px; border-radius: 10px;
    font-size: 13px; margin-bottom: 18px;
    display: flex; align-items: center; gap: 10px;
    animation: slideIn 0.3s ease;
  }

  @keyframes slideIn {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .alert-error {
    background: rgba(239,68,68,0.15);
    border: 1px solid rgba(239,68,68,0.3);
    color: #fca5a5;
  }

  .alert-success {
    background: rgba(16,185,129,0.15);
    border: 1px solid rgba(16,185,129,0.3);
    color: #6ee7b7;
  }

  .vtu-badge {
    display: flex; align-items: center; justify-content: center;
    gap: 8px; margin-top: 24px;
    padding: 10px;
    background: rgba(245,158,11,0.08);
    border: 1px solid rgba(245,158,11,0.2);
    border-radius: 8px; font-size: 12px;
    color: var(--gold);
  }

  @media (max-width: 900px) {
    .page { grid-template-columns: 1fr; }
    .left-panel { display: none; }
    .right-panel { border-left: none; min-height: 100vh; }
  }
</style>
</head>
<body>
<div class="bg-canvas"></div>
<div class="grid-overlay"></div>
<div class="orb orb1"></div>
<div class="orb orb2"></div>

<div class="page">
  <!-- LEFT PANEL -->
  <div class="left-panel">
    <div class="brand-logo">
      <div class="logo-icon"><i class="fas fa-file-alt"></i></div>
      <div class="brand-text">
        <div class="name">QPDS</div>
        <div class="tagline">Question Paper Delivery System</div>
      </div>
    </div>

    <h1 class="hero-headline">
      Smart Question<br>Papers for<br><span>VTU Colleges</span>
    </h1>

    <p class="hero-desc">
      Automated VTU-compliant question paper generation with CO-PO mapping, shuffling algorithms, and multi-role access control. From CIE to SEE — in seconds.
    </p>

    <div class="feature-list">
      <div class="feature-item">
        <div class="feature-icon"><i class="fas fa-shuffle"></i></div>
        <div class="feature-text">
          <strong>Smart Shuffling</strong>
          <span>Auto-generate multiple sets with question randomization</span>
        </div>
      </div>
      <div class="feature-item">
        <div class="feature-icon"><i class="fas fa-diagram-project"></i></div>
        <div class="feature-text">
          <strong>CO-PO Mapping</strong>
          <span>VTU Bloom's taxonomy attainment tracking per paper</span>
        </div>
      </div>
      <div class="feature-item">
        <div class="feature-icon"><i class="fas fa-users-cog"></i></div>
        <div class="feature-text">
          <strong>Role-Based Access</strong>
          <span>Admin, Principal, HOD, Staff — department-wise control</span>
        </div>
      </div>
      <div class="feature-item">
        <div class="feature-icon"><i class="fas fa-print"></i></div>
        <div class="feature-text">
          <strong>Print & Export</strong>
          <span>VTU-formatted print-ready papers with watermark</span>
        </div>
      </div>
    </div>
  </div>

  <!-- RIGHT PANEL (LOGIN) -->
  <div class="right-panel">
    <div class="login-card">
      <h2 class="login-title">Welcome Back</h2>
      <p class="login-subtitle">Select your role and sign in to continue</p>

      <!-- Role Selector -->
      <div class="role-selector">
        <button class="role-btn active" data-role="admin" onclick="selectRole(this,'admin')">
          <i class="fas fa-shield-halved"></i>Admin
        </button>
        <button class="role-btn" data-role="principal" onclick="selectRole(this,'principal')">
          <i class="fas fa-user-tie"></i>Principal
        </button>
        <button class="role-btn" data-role="hod" onclick="selectRole(this,'hod')">
          <i class="fas fa-chalkboard-user"></i>HOD
        </button>
        <button class="role-btn" data-role="staff" onclick="selectRole(this,'staff')">
          <i class="fas fa-person-chalkboard"></i>Staff
        </button>
      </div>

      <!-- Alert -->
      <div id="alert-box" style="display:none;"></div>

      <!-- Form -->
      <form id="loginForm" onsubmit="handleLogin(event)">
        <input type="hidden" name="role" id="selected_role" value="admin">
        <input type="hidden" name="csrf_token" value="<?php 
          require_once __DIR__ . '/includes/auth.php'; 
          Auth::init(); 
          echo Auth::csrfToken(); 
        ?>">

        <div class="form-group">
          <label class="form-label">USERNAME OR EMAIL</label>
          <div class="input-wrap">
            <i class="fas fa-user"></i>
            <input type="text" class="form-input" name="username" id="username"
                   placeholder="Enter your username" required autocomplete="username">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">PASSWORD</label>
          <div class="input-wrap">
            <i class="fas fa-lock"></i>
            <input type="password" class="form-input" name="password" id="password"
                   placeholder="Enter your password" required autocomplete="current-password">
            <button type="button" class="toggle-pw" onclick="togglePw()">
              <i class="fas fa-eye" id="eyeIcon"></i>
            </button>
          </div>
        </div>

        <div class="remember-row">
          <label class="checkbox-label">
            <input type="checkbox" name="remember"> Remember me
          </label>
          <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
        </div>

        <button type="submit" class="btn-login" id="loginBtn">
          <span id="btnText">Sign In</span>
          <div class="spinner" id="btnSpinner"></div>
        </button>
      </form>

      <div class="vtu-badge">
        <i class="fas fa-university"></i>
        Affiliated to VTU, Belagavi
      </div>
    </div>
  </div>
</div>

<script>
function selectRole(el, role) {
  document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('selected_role').value = role;
}

function togglePw() {
  const pw = document.getElementById('password');
  const icon = document.getElementById('eyeIcon');
  if (pw.type === 'password') {
    pw.type = 'text'; icon.className = 'fas fa-eye-slash';
  } else {
    pw.type = 'password'; icon.className = 'fas fa-eye';
  }
}

function showAlert(msg, type = 'error') {
  const box = document.getElementById('alert-box');
  box.innerHTML = `<div class="alert alert-${type}"><i class="fas fa-${type==='error'?'circle-exclamation':'circle-check'}"></i> ${msg}</div>`;
  box.style.display = 'block';
}

async function handleLogin(e) {
  e.preventDefault();
  const btn = document.getElementById('loginBtn');
  const btnText = document.getElementById('btnText');
  const spinner = document.getElementById('btnSpinner');

  btn.disabled = true;
  btnText.style.display = 'none';
  spinner.style.display = 'block';

  const form = document.getElementById('loginForm');
  const data = new FormData(form);

  try {
    const res = await fetch('auth/login.php', { method: 'POST', body: data });
    const json = await res.json();

    if (json.success) {
      showAlert('Login successful! Redirecting...', 'success');
      setTimeout(() => { window.location.href = json.redirect; }, 800);
    } else {
      showAlert(json.message || 'Login failed. Please try again.');
      btn.disabled = false;
      btnText.style.display = 'block';
      spinner.style.display = 'none';
    }
  } catch(err) {
    showAlert('Connection error. Please try again.');
    btn.disabled = false;
    btnText.style.display = 'block';
    spinner.style.display = 'none';
  }
}
</script>
</body>
</html>
