<?php
// ============================================================
// QPDS Configuration File
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'qpds_vtu');
define('DB_USER', 'your_db_user');       // Change on Hostinger
define('DB_PASS', 'your_db_password');   // Change on Hostinger
define('DB_CHARSET', 'utf8mb4');

define('SITE_URL', 'https://yourdomain.com/qpds'); // Change to your domain
define('SITE_NAME', 'QPDS - VTU Question Paper System');
define('SITE_VERSION', '1.0.0');
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

// Session config
define('SESSION_TIMEOUT', 3600); // 1 hour
define('SESSION_NAME', 'QPDS_SESSION');

// Security
define('CSRF_TOKEN_NAME', 'qpds_csrf_token');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes

// Paper settings
define('MAX_PAPER_SETS', 4);
define('WATERMARK_DEFAULT', 'CONFIDENTIAL');

// VTU Rules
define('VTU_AFFILIATION', 'Visvesvaraya Technological University, Belagavi');
define('VTU_MAX_SEMESTERS', 8);

// Timezone
date_default_timezone_set('Asia/Kolkata');
