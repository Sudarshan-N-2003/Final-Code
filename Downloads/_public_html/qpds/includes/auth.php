<?php
require_once __DIR__ . '/database.php';

class Auth {
    private static Database $db;

    public static function init(): void {
        self::$db = Database::getInstance();
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params(['httponly' => true, 'secure' => isset($_SERVER['HTTPS']), 'samesite' => 'Strict']);
            session_start();
        }
    }

    public static function login(string $username, string $password): array {
        self::init();

        // Check lockout
        $attempts = $_SESSION['login_attempts'][$username] ?? 0;
        $lockTime  = $_SESSION['lockout_time'][$username] ?? 0;
        if ($attempts >= MAX_LOGIN_ATTEMPTS && (time() - $lockTime) < LOCKOUT_TIME) {
            $remaining = LOCKOUT_TIME - (time() - $lockTime);
            return ['success' => false, 'message' => "Account locked. Try again in " . ceil($remaining/60) . " minutes."];
        }

        $user = self::$db->fetchOne(
            "SELECT * FROM users WHERE (username=? OR email=?) AND is_active=1 LIMIT 1",
            [$username, $username]
        );

        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['login_attempts'][$username] = ($attempts + 1);
            if ($_SESSION['login_attempts'][$username] >= MAX_LOGIN_ATTEMPTS) {
                $_SESSION['lockout_time'][$username] = time();
            }
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }

        // Clear attempts
        unset($_SESSION['login_attempts'][$username], $_SESSION['lockout_time'][$username]);

        // Set session
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['dept_id']   = $user['department_id'];
        $_SESSION['login_time'] = time();
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));

        // Update last login
        self::$db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id=?', [$user['id']]);

        // Log
        self::logActivity($user['id'], 'LOGIN', 'auth', 'User logged in');

        return ['success' => true, 'role' => $user['role'], 'name' => $user['full_name']];
    }

    public static function logout(): void {
        self::init();
        if (isset($_SESSION['user_id'])) {
            self::logActivity($_SESSION['user_id'], 'LOGOUT', 'auth', 'User logged out');
        }
        session_unset();
        session_destroy();
    }

    public static function check(string ...$roles): bool {
        self::init();
        if (!isset($_SESSION['user_id'])) return false;
        if ((time() - ($_SESSION['login_time'] ?? 0)) > SESSION_TIMEOUT) {
            self::logout();
            return false;
        }
        if (empty($roles)) return true;
        return in_array($_SESSION['user_role'], $roles);
    }

    public static function requireRole(string ...$roles): void {
        if (!self::check(...$roles)) {
            if (!isset($_SESSION['user_id'])) {
                header('Location: ' . SITE_URL . '/index.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            } else {
                header('Location: ' . SITE_URL . '/dashboard.php?error=unauthorized');
            }
            exit;
        }
    }

    public static function currentUser(): ?array {
        self::init();
        if (!isset($_SESSION['user_id'])) return null;
        return self::$db->fetchOne("SELECT u.*, d.name as dept_name, d.code as dept_code FROM users u LEFT JOIN departments d ON u.department_id=d.id WHERE u.id=?", [$_SESSION['user_id']]);
    }

    public static function canManageDept(int $deptId): bool {
        self::init();
        $role = $_SESSION['user_role'] ?? '';
        if (in_array($role, ['admin', 'principal'])) return true;
        if ($role === 'hod') return ($_SESSION['dept_id'] == $deptId);
        return false;
    }

    public static function csrfToken(): string {
        self::init();
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    public static function verifyCsrf(string $token): bool {
        return hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $token);
    }

    public static function logActivity(int $userId, string $action, string $module, string $details = ''): void {
        try {
            self::$db->insert('activity_logs', [
                'user_id'    => $userId,
                'action'     => $action,
                'module'     => $module,
                'details'    => $details,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        } catch (Exception $e) { /* silent */ }
    }
}
