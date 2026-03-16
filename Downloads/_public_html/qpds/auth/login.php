<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$csrf  = $_POST['csrf_token'] ?? '';
if (!Auth::verifyCsrf($csrf)) {
    echo json_encode(['success' => false, 'message' => 'Security token expired. Please refresh.']);
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    echo json_encode(['success' => false, 'message' => 'Please enter username and password.']);
    exit;
}

$result = Auth::login($username, $password);

if ($result['success']) {
    $redirectMap = [
        'admin'     => '../modules/admin/dashboard.php',
        'principal' => '../modules/principal/dashboard.php',
        'hod'       => '../modules/hod/dashboard.php',
        'staff'     => '../modules/staff/dashboard.php',
    ];
    $result['redirect'] = $redirectMap[$result['role']] ?? '../dashboard.php';
}

echo json_encode($result);
