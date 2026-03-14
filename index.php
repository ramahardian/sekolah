<?php
/**
 * INDEX.PHP - Entry Point
 */
ob_start();
require_once 'includes/auth_check.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Ambil parameter halaman dari URL (default: dashboard jika login, landing jika belum)
$page = isset($_GET['page']) ? $_GET['page'] : (isset($_SESSION['user_id']) ? 'dashboard' : 'landing');

require_once __DIR__ . '/routes/web.php';
?>
