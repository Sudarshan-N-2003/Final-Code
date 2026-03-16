<?php
require_once __DIR__ . '/../includes/auth.php';
Auth::logout();
header('Location: ../index.php?msg=logged_out');
exit;
