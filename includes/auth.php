<?php
/**
 * Authentication & Authorization Functions
 * Fungsi-fungsi untuk autentikasi dan otorisasi pengguna
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

/**
 * Login user
 * @param string $username
 * @param string $password
 * @return array|bool
 */
function loginUser($username, $password) {
    $db = getDbConnection();
    
    try {
        $query = "SELECT id, username, email, password_hash, role, full_name, nim, program_id, is_active 
                  FROM users 
                  WHERE (username = :username OR email = :email) AND is_active = 1";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $username);
        $stmt->execute();
        
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Set session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['nim'] = $user['nim'];
            $_SESSION['program_id'] = $user['program_id'];
            $_SESSION['is_logged_in'] = true;
            $_SESSION['login_time'] = time();
            
            // Log activity
            logActivity($user['id'], 'login', 'User logged in successfully');
            
            return $user;
        }
        
        return false;
    } catch (PDOException $e) {
        logActivity(0, 'login_failed', 'Login failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Logout user
 */
function logoutUser() {
    if (isset($_SESSION['user_id'])) {
        logActivity($_SESSION['user_id'], 'logout', 'User logged out');
    }
    
    // Clear all session data
    $_SESSION = array();
    
    // Destroy session
    session_destroy();
    
    // Clear session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
}

/**
 * Check user role
 * @param string|array $roles
 * @return bool
 */
function hasRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    if (is_array($roles)) {
        return in_array($_SESSION['role'], $roles);
    }
    
    return $_SESSION['role'] === $roles;
}

/**
 * Require authentication
 * Redirect to login if not authenticated
 */
function requireAuth() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ../pages/login.php');
        exit();
    }
}

/**
 * Require specific role
 * @param string|array $roles
 */
function requireRole($roles) {
    requireAuth();
    
    if (!hasRole($roles)) {
        header('Location: ../pages/unauthorized.php');
        exit();
    }
}

/**
 * Get current user info
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role'],
        'full_name' => $_SESSION['full_name'],
        'nim' => $_SESSION['nim'],
        'program_id' => $_SESSION['program_id']
    ];
}

/**
 * Check if session is valid (not expired)
 * @return bool
 */
function isSessionValid() {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Session timeout: 24 hours
    $session_timeout = 24 * 60 * 60;
    
    if (time() - $_SESSION['login_time'] > $session_timeout) {
        logoutUser();
        return false;
    }
    
    return true;
}