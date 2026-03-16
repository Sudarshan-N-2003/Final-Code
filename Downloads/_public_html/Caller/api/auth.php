<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login': login(); break;
    case 'logout': logout(); break;
    case 'change_password': changePassword(); break;
    case 'forgot_password': forgotPassword(); break;
    case 'verify_reset': verifyReset(); break;
    case 'reset_password': resetPassword(); break;
    default: jsonResponse(['error' => 'Invalid action'], 400);
}

function login() {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = sanitize($data['email'] ?? '');
    $password = $data['password'] ?? '';
    
    if (!$email || !$password) jsonResponse(['error' => 'Email and password required'], 400);
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password'])) {
        jsonResponse(['error' => 'Invalid credentials'], 401);
    }
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['is_first_login'] = $user['is_first_login'];
    
    session_regenerate_id(true);
    
    jsonResponse(['success' => true, 'role' => $user['role'], 'is_first_login' => $user['is_first_login']]);
}

function logout() {
    session_unset();
    session_destroy();
    jsonResponse(['success' => true]);
}

function changePassword() {
    if (empty($_SESSION['user_id'])) jsonResponse(['error' => 'Unauthorized'], 401);
    
    $data = json_decode(file_get_contents('php://input'), true);
    $current = $data['current_password'] ?? '';
    $new = $data['new_password'] ?? '';
    
    if (!$current || !$new) jsonResponse(['error' => 'All fields required'], 400);
    if (strlen($new) < 8) jsonResponse(['error' => 'Password must be 8+ characters'], 400);
    
    $db = getDB();
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!password_verify($current, $user['password'])) {
        jsonResponse(['error' => 'Current password incorrect'], 401);
    }
    
    $hashed = hashPassword($new);
    $stmt = $db->prepare("UPDATE users SET password = ?, is_first_login = 0 WHERE id = ?");
    $stmt->execute([$hashed, $_SESSION['user_id']]);
    
    $_SESSION['is_first_login'] = false;
    jsonResponse(['success' => true]);
}

function forgotPassword() {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = sanitize($data['email'] ?? '');
    
    if (!$email) jsonResponse(['error' => 'Email required'], 400);
    
    $db = getDB();
    $stmt = $db->prepare("SELECT id, email FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Don't reveal if email exists or not (security)
        jsonResponse(['success' => true, 'message' => 'If this email exists, proceed to verification']);
    }
    
    jsonResponse(['success' => true, 'email' => $email, 'message' => 'Proceed to verification']);
}

function verifyReset() {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = sanitize($data['email'] ?? '');
        $dob_input = $data['dob'] ?? '';
        
        if (!$email || !$dob_input) {
            jsonResponse(['error' => 'Email and Date of Birth required'], 400);
            return;
        }
        
        // Get database connection
        $db = getDB();
        if (!$db) {
            error_log("Database connection failed in verifyReset");
            jsonResponse(['error' => 'Database connection error'], 500);
            return;
        }
        
        // Try to find user by email first (to debug)
        $stmt = $db->prepare("SELECT id, name, dob FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // User not found with this email
            jsonResponse(['error' => 'Email not found'], 401);
            return;
        }
        
        // Log for debugging (remove in production)
        error_log("Database DOB: '" . $user['dob'] . "'");
        error_log("Input DOB: '" . $dob_input . "'");
        
        // Format the input date from YYYY-MM-DD to DD-MM-YYYY
        $date_parts = explode('-', $dob_input);
        if (count($date_parts) == 3) {
            $dob_dmy = $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0];
            
            // Compare both formats
            if ($user['dob'] == $dob_input || $user['dob'] == $dob_dmy) {
                // Success - DOB matches
                session_start();
                $_SESSION['reset_user_id'] = $user['id'];
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_expires'] = time() + 600;
                
                jsonResponse(['success' => true, 'verified' => true]);
                return;
            }
        }
        
        // If we get here, DOB doesn't match
        jsonResponse(['error' => 'Email and Date of Birth do not match'], 401);
        
    } catch (Exception $e) {
        error_log("Exception in verifyReset: " . $e->getMessage());
        jsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
    } catch (Error $e) {
        error_log("Error in verifyReset: " . $e->getMessage());
        jsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
    }
}
function resetPassword() {
    session_start();
    
    // Check if verification was done
    if (empty($_SESSION['reset_user_id']) || empty($_SESSION['reset_expires'])) {
        jsonResponse(['error' => 'Verification required'], 401);
    }
    
    // Check if token expired
    if (time() > $_SESSION['reset_expires']) {
        unset($_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['reset_expires']);
        jsonResponse(['error' => 'Reset link expired. Please try again.'], 401);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $newPassword = $data['new_password'] ?? '';
    $confirmPassword = $data['confirm_password'] ?? '';
    
    if (!$newPassword || !$confirmPassword) {
        jsonResponse(['error' => 'All fields required'], 400);
    }
    
    if (strlen($newPassword) < 8) {
        jsonResponse(['error' => 'Password must be at least 8 characters'], 400);
    }
    
    if ($newPassword !== $confirmPassword) {
        jsonResponse(['error' => 'Passwords do not match'], 400);
    }
    
    $db = getDB();
    $hashed = hashPassword($newPassword);
    
    $stmt = $db->prepare("UPDATE users SET password = ?, is_first_login = 0 WHERE id = ?");
    $stmt->execute([$hashed, $_SESSION['reset_user_id']]);
    
    // Clear reset session
    unset($_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['reset_expires']);
    
    jsonResponse(['success' => true, 'message' => 'Password reset successfully']);
}
