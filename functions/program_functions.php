<?php
/**
 * Program CRUD Functions
 * Fungsi-fungsi untuk manajemen program studi
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

/**
 * Get all programs
 * @param bool $active_only
 * @return array
 */
function getPrograms($active_only = false) {
    $db = getDbConnection();
    
    try {
        $query = "SELECT * FROM programs";
        
        if ($active_only) {
            $query .= " WHERE is_active = 1";
        }
        
        $query .= " ORDER BY program_name ASC";
        
        $stmt = $db->query($query);
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get program by ID
 * @param int $id
 * @return array|null
 */
function getProgramById($id) {
    $db = getDbConnection();
    
    try {
        $query = "SELECT * FROM programs WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Create new program
 * @param array $data
 * @return bool|int
 */
function createProgram($data) {
    $db = getDbConnection();
    
    try {
        $query = "INSERT INTO programs (program_code, program_name, description, tuition_fee, is_active) 
                  VALUES (:program_code, :program_name, :description, :tuition_fee, :is_active)";
        
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':program_code', $data['program_code']);
        $stmt->bindParam(':program_name', $data['program_name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':tuition_fee', $data['tuition_fee']);
        $stmt->bindParam(':is_active', $data['is_active']);
        
        if ($stmt->execute()) {
            $program_id = $db->lastInsertId();
            logActivity($_SESSION['user_id'] ?? 0, 'create_program', 'Created program: ' . $data['program_name']);
            return $program_id;
        }
        
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Update program
 * @param int $id
 * @param array $data
 * @return bool
 */
function updateProgram($id, $data) {
    $db = getDbConnection();
    
    try {
        $query = "UPDATE programs SET 
                  program_code = :program_code, 
                  program_name = :program_name, 
                  description = :description, 
                  tuition_fee = :tuition_fee, 
                  is_active = :is_active 
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':program_code', $data['program_code']);
        $stmt->bindParam(':program_name', $data['program_name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':tuition_fee', $data['tuition_fee']);
        $stmt->bindParam(':is_active', $data['is_active']);
        
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'] ?? 0, 'update_program', 'Updated program ID: ' . $id);
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Delete program
 * @param int $id
 * @return bool
 */
function deleteProgram($id) {
    $db = getDbConnection();
    
    try {
        // Check if program is being used
        $query = "SELECT COUNT(*) FROM users WHERE program_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            return false; // Cannot delete program with users
        }
        
        $query = "DELETE FROM programs WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'] ?? 0, 'delete_program', 'Deleted program ID: ' . $id);
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Check if program code exists
 * @param string $program_code
 * @param int $exclude_id
 * @return bool
 */
function programCodeExists($program_code, $exclude_id = 0) {
    $db = getDbConnection();
    
    try {
        $query = "SELECT COUNT(*) FROM programs WHERE program_code = :program_code AND id != :exclude_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':program_code', $program_code);
        $stmt->bindParam(':exclude_id', $exclude_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get program statistics
 * @return array
 */
function getProgramStats() {
    $db = getDbConnection();
    
    try {
        $query = "SELECT p.id, p.program_name, p.program_code, 
                         COUNT(u.id) as student_count, 
                         SUM(CASE WHEN b.status = 'paid' THEN 1 ELSE 0 END) as paid_bills,
                         SUM(CASE WHEN b.status = 'pending' THEN 1 ELSE 0 END) as pending_bills
                  FROM programs p 
                  LEFT JOIN users u ON p.id = u.program_id AND u.role = 'mahasiswa'
                  LEFT JOIN bills b ON u.id = b.student_id
                  GROUP BY p.id, p.program_name, p.program_code";
        
        $stmt = $db->query($query);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}