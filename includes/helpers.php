<?php
/**
 * Helper Functions
 * Fungsi-fungsi pembantu untuk aplikasi
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Format currency (Rupiah)
 * @param float $amount
 * @return string
 */
function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Format date (Indonesian)
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'd F Y') {
    $timestamp = strtotime($date);
    
    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $days = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    
    $formatted = date($format, $timestamp);
    
    // Replace month names
    foreach ($months as $num => $name) {
        $formatted = str_replace(date('F', mktime(0, 0, 0, $num, 10)), $name, $formatted);
    }
    
    // Replace day names
    foreach ($days as $en => $id) {
        $formatted = str_replace($en, $id, $formatted);
    }
    
    return $formatted;
}

/**
 * Get status badge class
 * @param string $status
 * @return string
 */
function getStatusBadgeClass($status) {
    $classes = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'paid' => 'bg-green-100 text-green-800',
        'overdue' => 'bg-red-100 text-red-800',
        'cancelled' => 'bg-gray-100 text-gray-800',
        'confirmed' => 'bg-blue-100 text-blue-800',
        'rejected' => 'bg-red-100 text-red-800'
    ];
    
    return $classes[strtolower($status)] ?? 'bg-gray-100 text-gray-800';
}

/**
 * Get role badge class
 * @param string $role
 * @return string
 */
function getRoleBadgeClass($role) {
    $classes = [
        'admin' => 'bg-purple-100 text-purple-800',
        'staff' => 'bg-blue-100 text-blue-800',
        'mahasiswa' => 'bg-green-100 text-green-800'
    ];
    
    return $classes[strtolower($role)] ?? 'bg-gray-100 text-gray-800';
}

/**
 * Generate unique number
 * @param string $prefix
 * @return string
 */
function generateUniqueNumber($prefix = '') {
    return $prefix . date('YmdHis') . rand(1000, 9999);
}

/**
 * Log activity
 * @param int $user_id
 * @param string $action
 * @param string $description
 */
function logActivity($user_id, $action, $description = '') {
    $db = getDbConnection();
    
    try {
        $query = "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) 
                  VALUES (:user_id, :action, :description, :ip_address, :user_agent)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR']);
        $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT']);
        $stmt->execute();
    } catch (PDOException $e) {
        // Silently fail
    }
}

/**
 * Set flash message
 * @param string $type
 * @param string $message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get flash message
 * @return array|null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    
    return null;
}

/**
 * Show flash message
 */
function showFlashMessage() {
    $flash = getFlashMessage();
    
    if ($flash) {
        $alertClasses = [
            'success' => 'bg-green-100 border border-green-400 text-green-700',
            'error' => 'bg-red-100 border border-red-400 text-red-700',
            'warning' => 'bg-yellow-100 border border-yellow-400 text-yellow-700',
            'info' => 'bg-blue-100 border border-blue-400 text-blue-700'
        ];
        
        $class = $alertClasses[$flash['type']] ?? $alertClasses['info'];
        
        echo '<div class="' . $class . ' px-4 py-3 rounded mb-4" role="alert">';
        echo '<span class="block sm:inline">' . htmlspecialchars($flash['message']) . '</span>';
        echo '</div>';
    }
}

/**
 * Pagination
 * @param int $total_items
 * @param int $current_page
 * @param int $items_per_page
 * @return array
 */
function getPaginationData($total_items, $current_page, $items_per_page = ITEMS_PER_PAGE) {
    $total_pages = ceil($total_items / $items_per_page);
    $current_page = max(1, min($current_page, $total_pages));
    $offset = ($current_page - 1) * $items_per_page;
    
    return [
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'offset' => $offset,
        'items_per_page' => $items_per_page
    ];
}

/**
 * Generate pagination HTML
 * @param array $pagination
 * @param string $base_url
 * @return string
 */
function generatePagination($pagination, $base_url) {
    if ($pagination['total_pages'] <= 1) {
        return '';
    }
    
    $html = '<nav class="flex items-center justify-between mt-4">';
    $html .= '<div class="text-sm text-gray-700">';
    $html .= 'Page ' . $pagination['current_page'] . ' of ' . $pagination['total_pages'];
    $html .= '</div>';
    $html .= '<div class="flex space-x-2">';
    
    // Previous button
    if ($pagination['current_page'] > 1) {
        $html .= '<a href="' . $base_url . '?page=' . ($pagination['current_page'] - 1) . '" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 hover:text-gray-700">';
        $html .= 'Previous';
        $html .= '</a>';
    }
    
    // Page numbers
    $start_page = max(1, $pagination['current_page'] - 2);
    $end_page = min($pagination['total_pages'], $pagination['current_page'] + 2);
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        $class = $i == $pagination['current_page'] 
            ? 'px-3 py-2 text-sm font-medium text-blue-600 bg-blue-100 border border-gray-300 rounded-lg'
            : 'px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 hover:text-gray-700';
        
        $html .= '<a href="' . $base_url . '?page=' . $i . '" class="' . $class . '">';
        $html .= $i;
        $html .= '</a>';
    }
    
    // Next button
    if ($pagination['current_page'] < $pagination['total_pages']) {
        $html .= '<a href="' . $base_url . '?page=' . ($pagination['current_page'] + 1) . '" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 hover:text-gray-700">';
        $html .= 'Next';
        $html .= '</a>';
    }
    
    $html .= '</div></nav>';
    
    return $html;
}

/**
 * Export data to CSV
 * @param array $data
 * @param array $headers
 * @param string $filename
 */
function exportToCSV($data, $headers, $filename = 'export.csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    $output = fopen('php://output', 'w');
    
    // Write BOM for UTF-8
    fwrite($output, "\xEF\xBB\xBF");
    
    // Write headers
    fputcsv($output, $headers);
    
    // Write data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();
}

/**
 * Get dashboard statistics
 * @return array
 */
function getDashboardStats() {
    $db = getDbConnection();
    
    try {
        // Total students
        $query = "SELECT COUNT(*) as total FROM users WHERE role = 'mahasiswa' AND is_active = 1";
        $stmt = $db->query($query);
        $total_students = $stmt->fetchColumn();
        
        // Total programs
        $query = "SELECT COUNT(*) as total FROM programs WHERE is_active = 1";
        $stmt = $db->query($query);
        $total_programs = $stmt->fetchColumn();
        
        // Total bills
        $query = "SELECT COUNT(*) as total FROM bills";
        $stmt = $db->query($query);
        $total_bills = $stmt->fetchColumn();
        
        // Total payments
        $query = "SELECT COUNT(*) as total FROM payments WHERE status = 'confirmed'";
        $stmt = $db->query($query);
        $total_payments = $stmt->fetchColumn();
        
        // Total revenue
        $query = "SELECT SUM(amount) as total FROM payments WHERE status = 'confirmed'";
        $stmt = $db->query($query);
        $total_revenue = $stmt->fetchColumn() ?? 0;
        
        // Pending bills
        $query = "SELECT COUNT(*) as total FROM bills WHERE status = 'pending'";
        $stmt = $db->query($query);
        $pending_bills = $stmt->fetchColumn();
        
        // Overdue bills
        $query = "SELECT COUNT(*) as total FROM bills WHERE status = 'overdue'";
        $stmt = $db->query($query);
        $overdue_bills = $stmt->fetchColumn();
        
        return [
            'total_students' => $total_students,
            'total_programs' => $total_programs,
            'total_bills' => $total_bills,
            'total_payments' => $total_payments,
            'total_revenue' => $total_revenue,
            'pending_bills' => $pending_bills,
            'overdue_bills' => $overdue_bills
        ];
    } catch (PDOException $e) {
        return [
            'total_students' => 0,
            'total_programs' => 0,
            'total_bills' => 0,
            'total_payments' => 0,
            'total_revenue' => 0,
            'pending_bills' => 0,
            'overdue_bills' => 0
        ];
    }
}