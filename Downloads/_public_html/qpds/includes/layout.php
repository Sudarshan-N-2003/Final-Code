<?php
// includes/layout.php - Shared layout header
// Usage: include at top of each dashboard page after Auth::requireRole(...)

$user = Auth::currentUser();
$role = $user['role'] ?? 'staff';
$deptName = $user['dept_name'] ?? '';

// Role-specific nav items
$navItems = [
    'admin' => [
        ['icon' => 'fa-gauge', 'label' => 'Dashboard', 'href' => 'dashboard.php'],
        ['icon' => 'fa-users', 'label' => 'User Management', 'href' => 'users.php'],
        ['icon' => 'fa-building-columns', 'label' => 'Departments', 'href' => 'departments.php'],
        ['icon' => 'fa-book', 'label' => 'Subjects', 'href' => 'subjects.php'],
        ['icon' => 'fa-file-circle-plus', 'label' => 'Generate Paper', 'href' => '../qp_generator/generate.php'],
        ['icon' => 'fa-files', 'label' => 'All Papers', 'href' => 'papers.php'],
        ['icon' => 'fa-chart-bar', 'label' => 'Reports', 'href' => 'reports.php'],
        ['icon' => 'fa-scroll', 'label' => 'Activity Log', 'href' => 'logs.php'],
        ['icon' => 'fa-gear', 'label' => 'Settings', 'href' => 'settings.php'],
    ],
    'principal' => [
        ['icon' => 'fa-gauge', 'label' => 'Dashboard', 'href' => 'dashboard.php'],
        ['icon' => 'fa-user-plus', 'label' => 'Add HOD / Staff', 'href' => 'users.php'],
        ['icon' => 'fa-book', 'label' => 'Subjects', 'href' => 'subjects.php'],
        ['icon' => 'fa-file-circle-plus', 'label' => 'Generate Paper', 'href' => '../qp_generator/generate.php'],
        ['icon' => 'fa-files', 'label' => 'All Papers', 'href' => 'papers.php'],
        ['icon' => 'fa-check-circle', 'label' => 'Approve Papers', 'href' => 'approve.php'],
        ['icon' => 'fa-chart-bar', 'label' => 'Reports', 'href' => 'reports.php'],
    ],
    'hod' => [
        ['icon' => 'fa-gauge', 'label' => 'Dashboard', 'href' => 'dashboard.php'],
        ['icon' => 'fa-user-plus', 'label' => 'Add Staff', 'href' => 'staff.php'],
        ['icon' => 'fa-book', 'label' => 'Dept Subjects', 'href' => 'subjects.php'],
        ['icon' => 'fa-circle-question', 'label' => 'Question Bank', 'href' => 'questions.php'],
        ['icon' => 'fa-diagram-project', 'label' => 'CO-PO Mapping', 'href' => 'copo.php'],
        ['icon' => 'fa-files', 'label' => 'Dept Papers', 'href' => 'papers.php'],
        ['icon' => 'fa-chart-bar', 'label' => 'Reports', 'href' => 'reports.php'],
    ],
    'staff' => [
        ['icon' => 'fa-gauge', 'label' => 'Dashboard', 'href' => 'dashboard.php'],
        ['icon' => 'fa-circle-question', 'label' => 'My Questions', 'href' => 'questions.php'],
        ['icon' => 'fa-plus-circle', 'label' => 'Add Question', 'href' => 'add_question.php'],
        ['icon' => 'fa-files', 'label' => 'My Papers', 'href' => 'papers.php'],
        ['icon' => 'fa-chart-line', 'label' => 'My CO Report', 'href' => 'co_report.php'],
    ],
];

$currentNavItems = $navItems[$role] ?? $navItems['staff'];
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

$roleColors = [
    'admin'     => '#ef4444',
    'principal' => '#8b5cf6',
    'hod'       => '#f59e0b',
    'staff'     => '#10b981',
];
$roleColor = $roleColors[$role] ?? '#1a4fd6';

// Unread notifications
$db = Database::getInstance();
$unreadNotifs = $db->fetchOne("SELECT COUNT(*) as cnt FROM notifications WHERE user_id=? AND is_read=0", [$user['id']]);
$notifCount = $unreadNotifs['cnt'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?> – QPDS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --sidebar-bg: #0a1628;
    --sidebar-w: 260px;
    --accent: <?= $roleColor ?>;
    --content-bg: #f0f4f8;
    --white: #ffffff;
    --text: #1e293b;
    --text-muted: #64748b;
    --border: #e2e8f0;
    --card-bg: #ffffff;
    --font-head: 'Syne', sans-serif;
    --font-body: 'DM Sans', sans-serif;
    --shadow: 0 1px 3px rgba(0,0,0,0.08), 0 4px 16px rgba(0,0,0,0.06);
  }
  html, body { height: 100%; font-family: var(--font-body); background: var(--content-bg); color: var(--text); overflow-x: hidden; }

  /* SIDEBAR */
  .sidebar {
    position: fixed; top: 0; left: 0; bottom: 0;
    width: var(--sidebar-w);
    background: var(--sidebar-bg);
    border-right: 1px solid rgba(255,255,255,0.06);
    display: flex; flex-direction: column;
    z-index: 100;
    transition: transform 0.3s;
  }

  .sidebar-brand {
    padding: 24px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    display: flex; align-items: center; gap: 12px;
  }

  .brand-icon {
    width: 40px; height: 40px;
    background: var(--accent);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; color: white;
    flex-shrink: 0;
  }

  .brand-info .brand-name {
    font-family: var(--font-head);
    font-size: 16px; font-weight: 800;
    color: white; letter-spacing: 1px;
  }

  .brand-info .brand-role {
    font-size: 11px; color: var(--accent);
    text-transform: uppercase; letter-spacing: 1.5px;
    font-weight: 600;
  }

  .nav-section {
    flex: 1; overflow-y: auto; padding: 16px 12px;
  }

  .nav-label {
    font-size: 10px; color: rgba(255,255,255,0.3);
    text-transform: uppercase; letter-spacing: 2px;
    padding: 8px 10px 4px;
  }

  .nav-link {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 14px; margin: 2px 0;
    border-radius: 8px;
    color: rgba(255,255,255,0.6);
    text-decoration: none; font-size: 14px;
    transition: all 0.2s;
    position: relative;
  }

  .nav-link i { width: 18px; font-size: 14px; text-align: center; }

  .nav-link:hover {
    background: rgba(255,255,255,0.06);
    color: white;
  }

  .nav-link.active {
    background: var(--accent);
    color: white;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
  }

  .nav-link.active i { color: white; }

  .sidebar-footer {
    padding: 16px 12px;
    border-top: 1px solid rgba(255,255,255,0.08);
  }

  .user-card {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 12px; border-radius: 8px;
    background: rgba(255,255,255,0.05);
    margin-bottom: 10px;
  }

  .user-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: var(--accent);
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; color: white; font-weight: 700; flex-shrink: 0;
  }

  .user-info .user-name {
    font-size: 13px; font-weight: 600; color: white;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;
  }

  .user-info .user-role {
    font-size: 11px; color: rgba(255,255,255,0.4);
    text-transform: capitalize;
  }

  .btn-logout {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 14px; border-radius: 8px;
    background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2);
    color: #fca5a5; font-size: 13px; text-decoration: none;
    transition: all 0.2s; width: 100%; justify-content: center;
  }

  .btn-logout:hover {
    background: rgba(239,68,68,0.2);
    color: white;
  }

  /* MAIN CONTENT */
  .main {
    margin-left: var(--sidebar-w);
    min-height: 100vh;
    display: flex; flex-direction: column;
  }

  /* TOP BAR */
  .topbar {
    background: var(--white);
    border-bottom: 1px solid var(--border);
    padding: 0 28px;
    height: 64px;
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 50;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
  }

  .topbar-left { display: flex; align-items: center; gap: 16px; }

  .page-breadcrumb {
    font-family: var(--font-head);
    font-size: 18px; font-weight: 700; color: var(--text);
  }

  .topbar-right { display: flex; align-items: center; gap: 12px; }

  .icon-btn {
    width: 38px; height: 38px; border-radius: 10px;
    background: var(--content-bg); border: 1px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    color: var(--text-muted); font-size: 15px; cursor: pointer;
    text-decoration: none; position: relative; transition: all 0.2s;
  }

  .icon-btn:hover {
    background: var(--accent); color: white; border-color: var(--accent);
  }

  .notif-badge {
    position: absolute; top: -4px; right: -4px;
    background: #ef4444; color: white;
    width: 18px; height: 18px; border-radius: 50%;
    font-size: 10px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    border: 2px solid white;
  }

  .dept-chip {
    background: rgba(<?= implode(',', sscanf($roleColor, '#%02x%02x%02x') ?? [26,79,214]) ?>,0.12);
    color: var(--accent);
    padding: 4px 12px; border-radius: 20px;
    font-size: 12px; font-weight: 600;
    border: 1px solid rgba(<?= implode(',', sscanf($roleColor, '#%02x%02x%02x') ?? [26,79,214]) ?>,0.2);
  }

  /* CONTENT AREA */
  .content {
    flex: 1; padding: 28px;
  }

  /* CARDS */
  .card {
    background: var(--card-bg); border-radius: 14px;
    box-shadow: var(--shadow); padding: 24px;
    border: 1px solid var(--border);
  }

  .card-title {
    font-family: var(--font-head);
    font-size: 16px; font-weight: 700;
    margin-bottom: 16px; color: var(--text);
    display: flex; align-items: center; gap: 8px;
  }

  /* STAT CARDS */
  .stats-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px; margin-bottom: 28px;
  }

  .stat-card {
    background: var(--card-bg); border-radius: 14px;
    padding: 20px 24px; box-shadow: var(--shadow);
    border: 1px solid var(--border);
    display: flex; align-items: center; gap: 16px;
    transition: transform 0.2s;
  }

  .stat-card:hover { transform: translateY(-2px); }

  .stat-icon {
    width: 50px; height: 50px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; flex-shrink: 0;
  }

  .stat-value {
    font-family: var(--font-head);
    font-size: 28px; font-weight: 800; line-height: 1;
    margin-bottom: 2px;
  }

  .stat-label { font-size: 13px; color: var(--text-muted); }

  /* TABLE */
  .table-wrap { overflow-x: auto; }
  table { width: 100%; border-collapse: collapse; }
  th {
    background: #f8fafc; padding: 10px 14px;
    font-size: 12px; font-weight: 600; color: var(--text-muted);
    text-transform: uppercase; letter-spacing: 0.5px;
    text-align: left; border-bottom: 1px solid var(--border);
  }
  td { padding: 12px 14px; border-bottom: 1px solid var(--border); font-size: 14px; }
  tr:last-child td { border-bottom: none; }
  tr:hover td { background: #f8fafc; }

  /* BADGE */
  .badge {
    padding: 3px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 600;
  }
  .badge-success { background: #dcfce7; color: #166534; }
  .badge-warning { background: #fef3c7; color: #92400e; }
  .badge-danger  { background: #fee2e2; color: #991b1b; }
  .badge-info    { background: #dbeafe; color: #1e40af; }
  .badge-purple  { background: #ede9fe; color: #6d28d9; }

  /* BUTTONS */
  .btn {
    padding: 9px 18px; border-radius: 8px; font-family: var(--font-body);
    font-size: 13px; font-weight: 600; cursor: pointer;
    border: none; transition: all 0.2s; display: inline-flex;
    align-items: center; gap: 7px; text-decoration: none;
  }
  .btn-primary { background: var(--accent); color: white; }
  .btn-primary:hover { filter: brightness(1.1); transform: translateY(-1px); }
  .btn-outline { background: transparent; color: var(--text-muted); border: 1px solid var(--border); }
  .btn-outline:hover { background: var(--content-bg); color: var(--text); }
  .btn-danger { background: #ef4444; color: white; }
  .btn-sm { padding: 6px 12px; font-size: 12px; }

  /* FORMS */
  .form-group { margin-bottom: 18px; }
  .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
  .form-control { width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-family: var(--font-body); font-size: 14px; color: var(--text); background: var(--white); outline: none; transition: border-color 0.2s; }
  .form-control:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(26,79,214,0.1); }
  select.form-control { cursor: pointer; }
  textarea.form-control { resize: vertical; min-height: 90px; }

  /* ALERT */
  .flash-msg { padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 18px; display: flex; align-items: center; gap: 10px; }
  .flash-success { background: #dcfce7; border: 1px solid #bbf7d0; color: #166534; }
  .flash-error   { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }
  .flash-warning { background: #fef3c7; border: 1px solid #fde68a; color: #92400e; }

  /* RESPONSIVE */
  @media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.open { transform: translateX(0); }
    .main { margin-left: 0; }
    .topbar { padding: 0 16px; }
    .content { padding: 16px; }
  }
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><i class="fas fa-file-alt"></i></div>
    <div class="brand-info">
      <div class="brand-name">QPDS</div>
      <div class="brand-role"><?= ucfirst($role) ?> Panel</div>
    </div>
  </div>

  <nav class="nav-section">
    <div class="nav-label">Navigation</div>
    <?php foreach ($currentNavItems as $item): 
      $isActive = (strpos($currentPage, basename($item['href'], '.php')) !== false) ? 'active' : '';
    ?>
    <a href="<?= $item['href'] ?>" class="nav-link <?= $isActive ?>">
      <i class="fas <?= $item['icon'] ?>"></i>
      <?= $item['label'] ?>
    </a>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
        <div class="user-role"><?= ucfirst($role) ?></div>
      </div>
    </div>
    <a href="<?= str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/') - 2) ?>auth/logout.php" class="btn-logout">
      <i class="fas fa-right-from-bracket"></i> Logout
    </a>
  </div>
</aside>

<!-- MAIN -->
<div class="main">
  <header class="topbar">
    <div class="topbar-left">
      <button onclick="document.getElementById('sidebar').classList.toggle('open')" style="display:none;background:none;border:none;cursor:pointer;font-size:20px;" id="menuBtn">
        <i class="fas fa-bars"></i>
      </button>
      <span class="page-breadcrumb"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></span>
    </div>
    <div class="topbar-right">
      <?php if ($deptName): ?>
      <span class="dept-chip"><i class="fas fa-building"></i> <?= htmlspecialchars($deptName) ?></span>
      <?php endif; ?>
      <a href="notifications.php" class="icon-btn" title="Notifications">
        <i class="fas fa-bell"></i>
        <?php if ($notifCount > 0): ?>
        <span class="notif-badge"><?= $notifCount ?></span>
        <?php endif; ?>
      </a>
      <a href="profile.php" class="icon-btn" title="Profile">
        <i class="fas fa-user-circle"></i>
      </a>
    </div>
  </header>

  <div class="content">
<?php // Content goes here - layout.php is included and then page content follows ?>
