<?php
/**
 * Payment CRUD Functions
 * Fungsi-fungsi untuk manajemen pembayaran
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

/**
 * Get all payments with pagination
 * @param int $page
 * @param int $per_page
 * @param string $search
 * @param string $status
 * @return array
 */
function getPayments($page = 1, $per_page = 10, $search = '', $status = '') {
    $db = getDbConnection();
    
    try {
        $offset = ($page - 1) * $per_page;
        
        $query = "SELECT p.*, u.full_name as student_name, u.nim, b.bill_number, b.description as bill_description 
                  FROM payments p 
                  JOIN users u ON p.student_id = u.id 
                  JOIN bills b ON p.bill_id = b.id 
                  WHERE 1=1";
        
        $params = [];
        
        if ($search) {
            $query .= " AND (u.full_name LIKE :search OR u.nim LIKE :search2 OR p.payment_number LIKE :search3)";
            $search_param = '%' . $search . '%';
            $params[':search'] = $search_param;
            $params[':search2'] = $search_param;
            $params[':search3'] = $search_param;
        }
        
        if ($status) {
            $query .= " AND p.status = :status";
            $params[':status'] = $status;
        }
        
        $query .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        
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
 * Get payment by ID
 * @param int $id
 * @return array|null
 */
function getPaymentById($id) {
    $db = getDbConnection();
    
    try {
        $query = "SELECT p.*, u.full_name as student_name, u.nim, b.bill_number, b.description as bill_description 
                  FROM payments p 
                  JOIN users u ON p.student_id = u.id 
                  JOIN bills b ON p.bill_id = b.id 
                  WHERE p.id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Get payments by student ID
 * @param int $student_id
 * @param string $status
 * @return array
 */
function getPaymentsByStudentId($student_id, $status = '') {
    $db = getDbConnection();
    
    try {
        $query = "SELECT p.*, b.bill_number, b.description as bill_description 
                  FROM payments p 
                  JOIN bills b ON p.bill_id = b.id 
                  WHERE p.student_id = :student_id";
        
        if ($status) {
            $query .= " AND p.status = :status";
        }
        
        $query .= " ORDER BY p.payment_date DESC";
        
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
 * Create new payment
 * @param array $data
 * @return bool|int
 */
function createPayment($data) {
    $db = getDbConnection();
    
    try {
        $query = "INSERT INTO payments (payment_number, bill_id, student_id, amount, payment_date, payment_method, status, notes, proof_file) 
                  VALUES (:payment_number, :bill_id, :student_id, :amount, :payment_date, :payment_method, :status, :notes, :proof_file)";
        
        $stmt = $db->prepare($query);
        
        // Generate payment number
        $payment_number = 'PAY-' . generateUniqueNumber();
        
        $stmt->bindParam(':payment_number', $payment_number);
        $stmt->bindParam(':bill_id', $data['bill_id'], PDO::PARAM_INT);
        $stmt->bindParam(':student_id', $data['student_id'], PDO::PARAM_INT);
        $stmt->bindParam(':amount', $data['amount']);
        $stmt->bindParam(':payment_date', $data['payment_date']);
        $stmt->bindParam(':payment_method', $data['payment_method']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':notes', $data['notes']);
        $stmt->bindParam(':proof_file', $data['proof_file']);
        
        if ($stmt->execute()) {
            $payment_id = $db->lastInsertId();
            logActivity($_SESSION['user_id'] ?? 0, 'create_payment', 'Created payment: ' . $payment_number);
            return $payment_id;
        }
        
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Update payment
 * @param int $id
 * @param array $data
 * @return bool
 */
function updatePayment($id, $data) {
    $db = getDbConnection();
    
    try {
        $query = "UPDATE payments SET 
                  bill_id = :bill_id, 
                  student_id = :student_id, 
                  amount = :amount, 
                  payment_date = :payment_date, 
                  payment_method = :payment_method, 
                  status = :status, 
                  notes = :notes, 
                  proof_file = :proof_file 
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':bill_id', $data['bill_id'], PDO::PARAM_INT);
        $stmt->bindParam(':student_id', $data['student_id'], PDO::PARAM_INT);
        $stmt->bindParam(':amount', $data['amount']);
        $stmt->bindParam(':payment_date', $data['payment_date']);
        $stmt->bindParam(':payment_method', $data['payment_method']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':notes', $data['notes']);
        $stmt->bindParam(':proof_file', $data['proof_file']);
        
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'] ?? 0, 'update_payment', 'Updated payment ID: ' . $id);
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Confirm payment
 * @param int $id
 * @return bool
 */
function confirmPayment($id) {
    $db = getDbConnection();
    
    try {
        $query = "UPDATE payments SET 
                  status = 'confirmed', 
                  confirmed_by = :confirmed_by, 
                  confirmed_at = NOW() 
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':confirmed_by', $_SESSION['user_id'], PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            // Update bill status to paid
            $payment = getPaymentById($id);
            if ($payment) {
                updateBillStatus($payment['bill_id'], 'paid');
            }
            
            logActivity($_SESSION['user_id'] ?? 0, 'confirm_payment', 'Confirmed payment ID: ' . $id);
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Reject payment
 * @param int $id
 * @param string $reason
 * @return bool
 */
function rejectPayment($id, $reason = '') {
    $db = getDbConnection();
    
    try {
        $query = "UPDATE payments SET 
                  status = 'rejected', 
                  notes = :notes 
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':notes', $reason);
        
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'] ?? 0, 'reject_payment', 'Rejected payment ID: ' . $id . ' - ' . $reason);
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Delete payment
 * @param int $id
 * @return bool
 */
function deletePayment($id) {
    $db = getDbConnection();
    
    try {
        $query = "DELETE FROM payments WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'] ?? 0, 'delete_payment', 'Deleted payment ID: ' . $id);
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Count total payments
 * @param string $search
 * @param string $status
 * @return int
 */
function countPayments($search = '', $status = '') {
    $db = getDbConnection();
    
    try {
        $query = "SELECT COUNT(*) as total 
                  FROM payments p 
                  JOIN users u ON p.student_id = u.id 
                  WHERE 1=1";
        
        $params = [];
        
        if ($search) {
            $query .= " AND (u.full_name LIKE :search OR u.nim LIKE :search2 OR p.payment_number LIKE :search3)";
            $search_param = '%' . $search . '%';
            $params[':search'] = $search_param;
            $params[':search2'] = $search_param;
            $params[':search3'] = $search_param;
        }
        
        if ($status) {
            $query .= " AND p.status = :status";
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
 * Get payment statistics
 * @return array
 */
function getPaymentStats() {
    $db = getDbConnection();
    
    try {
        // Payments by status
        $query = "SELECT status, COUNT(*) as count, SUM(amount) as total 
                  FROM payments 
                  GROUP BY status";
        
        $stmt = $db->query($query);
        $status_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Payments by month
        $query = "SELECT DATE_FORMAT(payment_date, '%Y-%m') as month, 
                         COUNT(*) as count, 
                         SUM(amount) as total 
                  FROM payments 
                  WHERE status = 'confirmed' 
                  GROUP BY DATE_FORMAT(payment_date, '%Y-%m') 
                  ORDER BY month DESC 
                  LIMIT 12";
        
        $stmt = $db->query($query);
        $monthly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'status_stats' => $status_stats,
            'monthly_stats' => $monthly_stats
        ];
    } catch (PDOException $e) {
        return [
            'status_stats' => [],
            'monthly_stats' => []
        ];
    }
}

/**
 * Check if payment number exists
 * @param string $payment_number
 * @param int $exclude_id
 * @return bool
 */
function paymentNumberExists($payment_number, $exclude_id = 0) {
    $db = getDbConnection();
    
    try {
        $query = "SELECT COUNT(*) FROM payments WHERE payment_number = :payment_number AND id != :exclude_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':payment_number', $payment_number);
        $stmt->bindParam(':exclude_id', $exclude_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get payments by program
 * @return array
 */
function getPaymentsByProgram() {
    $db = getDbConnection();
    
    try {
        $query = "SELECT p.program_name, 
                         COUNT(pay.id) as payment_count, 
                         SUM(pay.amount) as total_amount 
                  FROM programs p 
                  JOIN bills b ON p.id = b.program_id 
                  JOIN payments pay ON b.id = pay.bill_id 
                  WHERE pay.status = 'confirmed' 
                  GROUP BY p.id, p.program_name";
        
        $stmt = $db->query($query);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}