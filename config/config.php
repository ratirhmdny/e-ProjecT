<?php
/**
 * Application Configuration
 * Konfigurasi umum aplikasi
 */

// Environment
define('ENVIRONMENT', 'development'); // development, production

// Base URL
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    define('BASE_URL', 'https://' . $_SERVER['HTTP_HOST'] . '/');
} else {
    define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/');
}

// Application Settings
define('APP_NAME', 'E-SPP System');
define('APP_VERSION', '1.0.0');
define('APP_DESCRIPTION', 'Electronic Sistem Pembayaran Pendidikan');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Upload Settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf']);

// Pagination
define('ITEMS_PER_PAGE', 10);

// CSRF Token Expiry (in seconds)
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hour

// Password Settings
define('PASSWORD_MIN_LENGTH', 6);

// Error Reporting
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}