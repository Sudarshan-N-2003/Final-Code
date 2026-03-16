<?php
require_once __DIR__ . '/includes/config.php';

$email = 'admin@college.com';
$password = 'Admin@123';

?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Setup Admin</title>
<style>body{font-family:sans-serif;max-width:600px;margin:50px auto;padding:20px}.success{background:#d4edda;padding:20px;border-radius:8px;margin:20px 0}.error{background:#f8d7da;padding:20px;border-radius:8px}.btn{display:inline-block;padding:12px 24px;background:#667eea;color:white;text-decoration:none;border-radius:6px;margin-top:20px}</style>
</head><body>
<h1>🚀 Admin Setup</h1>
<?php
try {
    $db = getDB();
    $hashed = hashPassword($password);
    
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        $db->prepare("UPDATE users SET password = ? WHERE email = ?")->execute([$hashed, $email]);
        echo '<div class="success">✅ Admin password updated!</div>';
    } else {
        $db->prepare("INSERT INTO users (name, email, password, phone, role, is_first_login) VALUES (?, ?, ?, ?, ?, ?)")->execute(['Admin', $email, $hashed, '9999999999', 'admin', 0]);
        echo '<div class="success">✅ Admin user created!</div>';
    }
    
    echo "<p><strong>Email:</strong> $email</p>";
    echo "<p><strong>Password:</strong> $password</p>";
    echo '<p style="color:red"><strong>⚠️ DELETE THIS FILE IMMEDIATELY!</strong></p>';
    echo '<a href="'.BASE_URL.'/" class="btn">Go to Login</a>';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . $e->getMessage() . '</div>';
    echo '<p>Check database credentials in includes/config.php</p>';
}
?>
</body></html>
