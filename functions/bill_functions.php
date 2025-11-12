<?php
/**
 * Bill CRUD Functions
 * Fungsi-fungsi untuk manajemen tagihan/bills
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

/**
 * Get all bills with pagination
 * @param int $page
 * @param int $per_page
 * @param string $search
 * @param string $status
 * @return array
 */
function getBills($page = 1, $per_page = 10, $search = '', $status = '') {
    $db = getDbConnection();
    
    try {
        $offset = ($page - 1) * $per_page;
        
        $query = "SELECT b.*, u.full_name as student_name, u.nim, p.program_name 
                  FROM bills b 
                  JOIN users u ON b.student_id = u.id 
                  JOIN programs p ON b.program_id = p.id 
                  WHERE 1=1";
        
        $params = [];
        
        if ($search) {
            $query .= " AND (u.full_name LIKE :search OR u.nim LIKE :search2 OR b.bill_number LIKE :search3)";
            $search_param = '%' . $search . '%';
            $params[':search'] = $search_param;
            $params[':search2'] = $search_param;
            $params[':search3'] = $search_param;
        }
        
        if ($status) {
            $query .= " AND b.status = :status";
            $params[':status'] = $status;
        }
        
        $query .= " ORDER BY b.created_at DESC LIMIT :limit OFFSET :offset";
        
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
 * Get bill by ID
 * @param int $id
 * @return array|null
 */
function getBillById($id) {
    $db = getDbConnection();
    
    try {
        $query = "SELECT b.*, u.full_name as student_name, u.nim, p.program_name 
                  FROM bills b 
                  JOIN users u ON b.student_id = u.id 
                  JOIN programs p ON b.program_id = p.id 
                  WHERE b.id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Get bills by student ID
 * @param int $student_id
 * @param string $status
 * @return array
 */
function getBillsByStudentId($student_id, $status = '') {
    $db = getDbConnection();
    
    try {
        $query = "SELECT b.*, p.program_name 
                  FROM bills b 
                  JOIN programs p ON b.program_id = p.id 
                  WHERE b.student_id = :student_id";
        
        if ($status) {
            $query .= " AND b.status = :status";
        }
        
        $query .= " ORDER BY b.due_date ASC";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        
        if ($status) {
            $stmt->bindParam(':status', $status);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Create new bill
 * @param array $data
 * @return bool|int
 */
function createBill($data) {
    $db = getDbConnection();
    
    try {
        $query = "INSERT INTO bills (bill_number, student_id, program_id, amount, description, due_date, status, created_by) 
                  VALUES (:bill_number, :student_id, :program_id, :amount, :description, :due_date, :status, :created_by)";
        
        $stmt = $db->prepare($query);
        
        // Generate bill number
        $bill_number = 'BILL-' . generateUniqueNumber();
        
        $stmt->bindParam(':bill_number', $bill_number);
        $stmt->bindParam(':student_id', $data['student_id'], PDO::PARAM_INT);
        $stmt->bindParam(':program_id', $data['program_id'], PDO::PARAM_INT);
        $stmt->bindParam(':amount', $data['amount']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':due_date', $data['due_date']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':created_by', $_SESSION['user_id'], PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $bill_id = $db->lastInsertId();
            logActivity($_SESSION['user_id'] ?? 0, 'create_bill', 'Created bill: ' . $bill_number);
            return $bill_id;
        }
        
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Update bill
 * @param int $id
 * @param array $data
 * @return bool
 */
function updateBill($id, $data) {
    $db = getDbConnection();
    
    try {
        $query = "UPDATE bills SET 
                  student_id = :student_id, 
                  program_id = :program_id, 
                  amount = :amount, 
                  description = :description, 
                  due_date = :due_date, 
                  status = :status 
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':student_id', $data['student_id'], PDO::PARAM_INT);
        $stmt->bindParam(':program_id', $data['program_id'], PDO::PARAM_INT);
        $stmt->bindParam(':amount', $data['amount']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':due_date', $data['due_date']);
        $stmt->bindParam(':status', $data['status']);
        
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'] ?? 0, 'update_bill', 'Updated bill ID: ' . $id);
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Delete bill
 * @param int $id
 * @return bool
 */
function deleteBill($id) {
    $db = getDbConnection();
    
    try {
        $query = "DELETE FROM bills WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'] ?? 0, 'delete_bill', 'Deleted bill ID: ' . $id);
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Count total bills
 * @param string $search
 * @param string $status
 * @return int
 */
function countBills($search = '', $status = '') {
    $db = getDbConnection();
    
    try {
        $query = "SELECT COUNT(*) as total 
                  FROM bills b 
                  JOIN users u ON b.student_id = u.id 
                  WHERE 1=1";
        
        $params = [];
        
        if ($search) {
            $query .= " AND (u.full_name LIKE :search OR u.nim LIKE :search2 OR b.bill_number LIKE :search3)";
            $search_param = '%' . $search . '%';
            $params[':search'] = $search_param;
            $params[':search2'] = $search_param;
            $params[':search3'] = $search_param;
        }
        
        if ($status) {
            $query .= " AND b.status = :status";
            $params[':status'] = $status;
        }
        
        $stmt = $db->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Update bill status
 * @param int $id
 * @param string $status
 * @return bool
 */
function updateBillStatus($id, $status) {
    $db = getDbConnection();
    
    try {
        $query = "UPDATE bills SET status = :status WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status);
        
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'] ?? 0, 'update_bill_status', 'Updated bill status ID: ' . $id . ' to ' . $status);
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Check if bill number exists
 * @param string $bill_number
 * @param int $exclude_id
 * @return bool
 */
function billNumberExists($bill_number, $exclude_id = 0) {
    $db = getDbConnection();
    
    try {
        $query = "SELECT COUNT(*) FROM bills WHERE bill_number = :bill_number AND id != :exclude_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':bill_number', $bill_number);
        $stmt->bindParam(':exclude_id', $exclude_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get overdue bills
 * @return array
 */
function getOverdueBills() {
    $db = getDbConnection();
    
    try {
        $query = "SELECT b.*, u.full_name as student_name, u.nim, p.program_name 
                  FROM bills b 
                  JOIN users u ON b.student_id = u.id 
                  JOIN programs p ON b.program_id = p.id 
                  WHERE b.due_date < CURDATE() 
                  AND b.status = 'pending' 
                  ORDER BY b.due_date ASC";
        
        $stmt = $db->query($query);
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Update bill status to overdue
 * @return bool
 */
function updateOverdueBills() {
    $db = getDbConnection();
    
    try {
        $query = "UPDATE bills SET status = 'overdue' 
                  WHERE due_date < CURDATE() 
                  AND status = 'pending'";
        
        $stmt = $db->prepare($query);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        return false;
    }
}