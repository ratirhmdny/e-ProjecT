<?php
/**
 * User CRUD Functions
 * Fungsi-fungsi untuk manajemen user/pengguna
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

/**
 * Get all users with pagination
 * @param int $page
 * @param int $per_page
 * @param string $search
 * @return array
 */
function getUsers($page = 1, $per_page = 10, $search = '') {
    $db = getDbConnection();
    
    try {
        $offset = ($page - 1) * $per_page;
        
        $query = "SELECT u.*, p.program_name 
                  FROM users u 
                  LEFT JOIN programs p ON u.program_id = p.id 
                  WHERE u.role != 'admin'";
        
        $params = [];
        
        if ($search) {
            $query .= " AND (u.full_name LIKE :search OR u.username LIKE :search2 OR u.nim LIKE :search3)";
            $search_param = '%' . $search . '%';
            $params[':search'] = $search_param;
            $params[':search2'] = $search_param;
            $params[':search3'] = $search_param;
        }
        
        $query .= " ORDER BY u.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get user by ID
 * @param int $id
 * @return array|null
 */
function getUserById($id) {
    $db = getDbConnection();
    
    try {
        $query = "SELECT u.*, p.program_name 
                  FROM users u 
                  LEFT JOIN programs p ON u.program_id = p.id 
                  WHERE u.id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Get user by username or email
 * @param string $username
 * @return array|null
 */
function getUserByUsername($username) {
    $db = getDbConnection();
    
    try {
        $query = "SELECT * FROM users WHERE username = :username OR email = :email LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $username);
        $stmt->execute();
        
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Create new user
 * @param array $data
 * @return bool|int
 */
function createUser($data) {
    $db = getDbConnection();
    
    try {
        $query = "INSERT INTO users (username, email, password_hash, role, full_name, nim, program_id, phone, address, is_active) 
                  VALUES (:username, :email, :password_hash, :role, :full_name, :nim, :program_id, :phone, :address, :is_active)";
        
        $stmt = $db->prepare($query);
        
        // Hash password
        $password_hash = password_hash($data['password'], PASSWORD_BCRYPT);
        
        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':role', $data['role']);
        $stmt->bindParam(':full_name', $data['full_name']);
        $stmt->bindParam(':nim', $data['nim']);
        $stmt->bindParam(':program_id', $data['program_id']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':is_active', $data['is_active']);
        
        if ($stmt->execute()) {
            $user_id = $db->lastInsertId();
            logActivity($_SESSION['user_id'] ?? 0, 'create_user', 'Created user: ' . $data['username']);
            return $user_id;
        }
        
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Update user
 * @param int $id
 * @param array $data
 * @return bool
 */
function updateUser($id, $data) {
    $db = getDbConnection();
    
    try {
        $query = "UPDATE users SET 
                  username = :username, 
                  email = :email, 
                  role = :role, 
                  full_name = :full_name, 
                  nim = :nim, 
                  program_id = :program_id, 
                  phone = :phone, 
                  address = :address, 
                  is_active = :is_active";
        
        // Include password if provided
        if (!empty($data['password'])) {
            $query .= ", password_hash = :password_hash";
        }
        
        $query .= " WHERE id = :id";
        
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':role', $data['role']);
        $stmt->bindParam(':full_name', $data['full_name']);
        $stmt->bindParam(':nim', $data['nim']);
        $stmt->bindParam(':program_id', $data['program_id']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':is_active', $data['is_active']);
        
        if (!empty($data['password'])) {
            $password_hash = password_hash($data['password'], PASSWORD_BCRYPT);
            $stmt->bindParam(':password_hash', $password_hash);
        }
        
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'] ?? 0, 'update_user', 'Updated user ID: ' . $id);
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Delete user
 * @param int $id
 * @return bool
 */
function deleteUser($id) {
    $db = getDbConnection();
    
    try {
        // Check if user exists
        $user = getUserById($id);
        if (!$user) {
            return false;
        }
        
        // Don't delete admin
        if ($user['role'] === 'admin') {
            return false;
        }
        
        $query = "DELETE FROM users WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'] ?? 0, 'delete_user', 'Deleted user ID: ' . $id);
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Count total users
 * @param string $search
 * @return int
 */
function countUsers($search = '') {
    $db = getDbConnection();
    
    try {
        $query = "SELECT COUNT(*) as total FROM users WHERE role != 'admin'";
        
        if ($search) {
            $query .= " AND (full_name LIKE :search OR username LIKE :search2 OR nim LIKE :search3)";
        }
        
        $stmt = $db->prepare($query);
        
        if ($search) {
            $search_param = '%' . $search . '%';
            $stmt->bindValue(':search', $search_param);
            $stmt->bindValue(':search2', $search_param);
            $stmt->bindValue(':search3', $search_param);
        }
        
        $stmt->execute();
        
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Check if username exists
 * @param string $username
 * @param int $exclude_id
 * @return bool
 */
function usernameExists($username, $exclude_id = 0) {
    $db = getDbConnection();
    
    try {
        $query = "SELECT COUNT(*) FROM users WHERE username = :username AND id != :exclude_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':exclude_id', $exclude_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Check if email exists
 * @param string $email
 * @param int $exclude_id
 * @return bool
 */
function emailExists($email, $exclude_id = 0) {
    $db = getDbConnection();
    
    try {
        $query = "SELECT COUNT(*) FROM users WHERE email = :email AND id != :exclude_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':exclude_id', $exclude_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Check if NIM exists
 * @param string $nim
 * @param int $exclude_id
 * @return bool
 */
function nimExists($nim, $exclude_id = 0) {
    $db = getDbConnection();
    
    try {
        $query = "SELECT COUNT(*) FROM users WHERE nim = :nim AND id != :exclude_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nim', $nim);
        $stmt->bindParam(':exclude_id', $exclude_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}