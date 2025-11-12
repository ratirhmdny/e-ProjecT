<?php
/**
 * E-SPP System - Main Entry Point
 * Redirects to appropriate page based on authentication status
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// Check if user is logged in
if (isLoggedIn()) {
    // Redirect to dashboard
    header('Location: pages/dashboard.php');
} else {
    // Redirect to login page
    header('Location: pages/login.php');
}

exit();
?>