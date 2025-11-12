<?php
/**
 * Application Initialization
 * File ini harus di-include di setiap halaman untuk inisialisasi aplikasi
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('FUNCTIONS_PATH', BASE_PATH . '/functions');

// Include necessary files
require_once CONFIG_PATH . '/config.php';
require_once CONFIG_PATH . '/database.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/security.php';
require_once INCLUDES_PATH . '/helpers.php';

// Check session validity
if (isLoggedIn() && !isSessionValid()) {
    logoutUser();
    header('Location: pages/login.php');
    exit();
}

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Initialize CSRF token for forms
if (!isset($_SESSION['csrf_token'])) {
    generateCSRFToken();
}

// Prevent caching for sensitive pages
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Content Security Policy
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self'");

// XSS Protection
header('X-XSS-Protection: 1; mode=block');

// Frame Options
header('X-Frame-Options: DENY');

// Content Type Options
header('X-Content-Type-Options: nosniff');

// Strict Transport Security (if using HTTPS)
// header('Strict-Transport-Security: max-age=31536000; includeSubDomains');