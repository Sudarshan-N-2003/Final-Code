<?php
/**
 * ================================================================
 * AdmissionConnect - Configuration File
 * Complete Rewrite for Hostinger MySQL
 * ================================================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error.log');

// ================================================================
// DATABASE CREDENTIALS - UPDATE THESE
// ================================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'u558908426_call');
define('DB_USER', 'u558908426_call');
define('DB_PASS', 'Vvit@567');

// ================================================================
// APPLICATION URL - UPDATE THIS
// ================================================================
define('BASE_URL', 'https://vvit.cc/Caller');

// ================================================================
// APPLICATION SETTINGS
// ================================================================
define('APP_NAME', 'AdmissionConnect');
define('PASSWORD_COST', 12);

// ================================================================
// SESSION CONFIGURATION
// ================================================================
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');

// ================================================================
// TIMEZONE
// ================================================================
date_default_timezone_set('Asia/Kolkata');

// ================================================================
// DATABASE CONNECTION
// ================================================================
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            http_response_code(503);
            die(json_encode(['error' => 'Database connection failed']));
        }
    }
    
    return $pdo;
}

// ================================================================
// UTILITY FUNCTIONS
// ================================================================
function sanitize($str) {
    return $str === null ? null : htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function requireLogin() {
    session_status() === PHP_SESSION_NONE && session_start();
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/');
        exit;
    }
}

function requireRole($roles) {
    session_status() === PHP_SESSION_NONE && session_start();
    if (empty($_SESSION['user_id'])) jsonResponse(['error' => 'Unauthorized'], 401);
    is_array($roles) || $roles = [$roles];
    if (!in_array($_SESSION['role'], $roles)) jsonResponse(['error' => 'Forbidden'], 403);
}

function generatePassword($len = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    $pwd = '';
    for ($i = 0; $i < $len; $i++) $pwd .= $chars[random_int(0, strlen($chars) - 1)];
    return $pwd;
}

function hashPassword($pwd) {
    return password_hash($pwd, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
}
