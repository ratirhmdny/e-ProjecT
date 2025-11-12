<?php
/**
 * Security Functions
 * Fungsi-fungsi keamanan untuk CSRF, XSS, dan validasi
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    $token = bin2hex(random_bytes(32));
    
    // Store token in session
    $_SESSION['csrf_token'] = $token;
    $_SESSION['csrf_token_time'] = time();
    
    return $token;
}

/**
 * Validate CSRF token
 * @param string $token
 * @return bool
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    // Check token expiry
    if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_EXPIRY) {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF token input field
 * @return string
 */
function getCSRFTokenField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Sanitize input data
 * @param mixed $data
 * @return mixed
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    return $data;
}

/**
 * Validate email
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Indonesian format)
 * @param string $phone
 * @return bool
 */
function isValidPhone($phone) {
    return preg_match('/^(\+62|62|0)8[1-9][0-9]{6,9}$/', $phone);
}

/**
 * Validate NIM format
 * @param string $nim
 * @return bool
 */
function isValidNIM($nim) {
    return preg_match('/^[0-9]{8,12}$/', $nim);
}

/**
 * Validate file upload
 * @param array $file
 * @return array
 */
function validateFileUpload($file) {
    $errors = [];
    
    if (!isset($file['error']) || is_array($file['error'])) {
        $errors[] = 'Invalid file upload';
        return $errors;
    }
    
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            $errors[] = 'No file uploaded';
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $errors[] = 'File size exceeds limit';
            break;
        default:
            $errors[] = 'Unknown upload error';
            break;
    }
    
    if (empty($errors)) {
        // Check file size
        if ($file['size'] > MAX_FILE_SIZE) {
            $errors[] = 'File size too large (max 5MB)';
        }
        
        // Check file type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        
        if (!in_array($mime, ALLOWED_FILE_TYPES)) {
            $errors[] = 'Invalid file type. Allowed: JPG, PNG, PDF';
        }
    }
    
    return $errors;
}

/**
 * Generate secure random string
 * @param int $length
 * @return string
 */
function generateRandomString($length = 16) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Hash password
 * @param string $password
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Secure redirect
 * @param string $url
 */
function secureRedirect($url) {
    // Prevent header injection
    $url = str_replace(array("\r", "\n"), '', $url);
    header('Location: ' . $url);
    exit();
}

/**
 * Get client IP address
 * @return string
 */
function getClientIP() {
    $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
}

/**
 * Rate limiting check
 * @param string $identifier
 * @param int $max_attempts
 * @param int $time_window
 * @return bool
 */
function checkRateLimit($identifier, $max_attempts = 5, $time_window = 300) {
    $db = getDbConnection();
    
    try {
        // Clean old attempts
        $query = "DELETE FROM rate_limiting WHERE created_at < DATE_SUB(NOW(), INTERVAL :time_window SECOND)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':time_window', $time_window, PDO::PARAM_INT);
        $stmt->execute();
        
        // Count current attempts
        $query = "SELECT COUNT(*) as attempts FROM rate_limiting 
                  WHERE identifier = :identifier 
                  AND created_at > DATE_SUB(NOW(), INTERVAL :time_window SECOND)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':identifier', $identifier);
        $stmt->bindParam(':time_window', $time_window, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch();
        
        return $result['attempts'] < $max_attempts;
    } catch (PDOException $e) {
        return true; // Allow if error
    }
}

/**
 * Record rate limit attempt
 * @param string $identifier
 */
function recordRateLimit($identifier) {
    $db = getDbConnection();
    
    try {
        $query = "INSERT INTO rate_limiting (identifier, created_at) VALUES (:identifier, NOW())";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':identifier', $identifier);
        $stmt->execute();
    } catch (PDOException $e) {
        // Silently fail
    }
}