<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../functions/user_functions.php';
require_once __DIR__ . '/../functions/bill_functions.php';
require_once __DIR__ . '/../functions/payment_functions.php';

// Require authentication
requireAuth();

// Get current user
$currentUser = getCurrentUser();

// Get dashboard statistics
$stats = getDashboardStats();

// Get payment statistics for charts
$paymentStats = getPaymentStats();

// Get overdue bills (for admin/staff)
$overdueBills = [];
if (hasRole(['admin', 'staff'])) {
    $overdueBills = getOverdueBills();
    
    // Update overdue bills status
    if (!empty($overdueBills)) {
        updateOverdueBills();
    }
}

// Get recent activities
$recentActivities = [];
if (hasRole(['admin', 'staff'])) {
    $db = getDbConnection();
    try {
        $query = "SELECT al.*, u.full_name, u.username 
                  FROM activity_logs al 
                  JOIN users u ON al.user_id = u.id 
                  ORDER BY al.created_at DESC 
                  LIMIT 10";
        $stmt = $db->query($query);
        $recentActivities = $stmt->fetchAll();
    } catch (PDOException $e) {
        $recentActivities = [];
    }
}

// Get user's bills and payments (for students)
$userBills = [];
$userPayments = [];
if (hasRole('mahasiswa')) {
    $userBills = getBillsByStudentId($currentUser['id']);
    $userPayments = getPaymentsByStudentId($currentUser['id']);
}

// Prepare chart data
$chartData = [
    'payment' => [
        'labels' => [],
        'data' => []
    ],
    'program' => [
        'labels' => [],
        'data' => []
    ]
];

// Payment chart data
if (!empty($paymentStats['monthly_stats'])) {
    foreach ($paymentStats['monthly_stats'] as $stat) {
        $chartData['payment']['labels'][] = date('M Y', strtotime($stat['month'] . '-01'));
        $chartData['payment']['data'][] = (float)$stat['total'];
    }
}

// Program chart data
$programPayments = getPaymentsByProgram();
if (!empty($programPayments)) {
    foreach ($programPayments as $program) {
        $chartData['program']['labels'][] = $program['program_name'];
        $chartData['program']['data'][] = (float)$program['total_amount'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i><?php echo APP_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    
                    <?php if (hasRole(['admin', 'staff'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-users me-1"></i>Manajemen
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="users.php">Mahasiswa</a></li>
                                <li><a class="dropdown-item" href="programs.php">Program Studi</a></li>
                                <li><a class="dropdown-item" href="bills.php">Tagihan</a></li>
                                <li><a class="dropdown-item" href="payments.php">Pembayaran</a></li>
                            </ul>
                        </li>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-chart-bar me-1"></i>Laporan
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="reports/payments.php">Laporan Pembayaran</a></li>
                                <li><a class="dropdown-item" href="reports/students.php">Laporan Mahasiswa</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    
                    <?php if (hasRole('mahasiswa')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="my_bills.php">
                                <i class="fas fa-file-invoice me-1"></i>Tagihan Saya
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my_payments.php">
                                <i class="fas fa-receipt me-1"></i>Pembayaran Saya
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($currentUser['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user me-2"></i>Profil
                            </a></li>
                            <li><a class="dropdown-item" href="change_password.php">
                                <i class="fas fa-key me-2"></i>Ganti Password
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container-fluid py-4">
        <!-- Flash Messages -->
        <?php showFlashMessage(); ?>
        
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1>
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </h1>
                    <p class="text-muted">
                        Selamat datang, <?php echo htmlspecialchars($currentUser['full_name']); ?>!
                        <span class="badge bg-primary ms-2"><?php echo ucfirst($currentUser['role']); ?></span>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="text-muted">
                        <i class="fas fa-calendar me-1"></i>
                        <?php echo formatDate(date('Y-m-d')); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <?php if (hasRole(['admin', 'staff'])): ?>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card primary">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="mb-0"><?php echo number_format($stats['total_students']); ?></h3>
                                <p class="text-muted mb-0">Total Mahasiswa</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="fas fa-users fa-2x text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card success">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="mb-0"><?php echo number_format($stats['total_programs']); ?></h3>
                                <p class="text-muted mb-0">Program Studi</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="fas fa-graduation-cap fa-2x text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card info">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="mb-0"><?php echo formatCurrency($stats['total_revenue']); ?></h3>
                                <p class="text-muted mb-0">Total Pendapatan</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="fas fa-money-bill-wave fa-2x text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card warning">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="mb-0"><?php echo number_format($stats['pending_bills']); ?></h3>
                                <p class="text-muted mb-0">Tagihan Pending</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="fas fa-clock fa-2x text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="stats-card primary">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="mb-0"><?php echo count($userBills); ?></h3>
                                <p class="text-muted mb-0">Total Tagihan</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="fas fa-file-invoice fa-2x text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="stats-card success">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="mb-0"><?php echo count(array_filter($userBills, fn($b) => $b['status'] === 'paid')); ?></h3>
                                <p class="text-muted mb-0">Tagihan Lunas</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle fa-2x text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="stats-card info">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="mb-0"><?php echo count($userPayments); ?></h3>
                                <p class="text-muted mb-0">Total Pembayaran</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="fas fa-receipt fa-2x text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Charts Section (Admin/Staff Only) -->
        <?php if (hasRole(['admin', 'staff'])): ?>
            <div class="row mb-4">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-bar me-2"></i>Grafik Pembayaran Per Bulan
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="paymentChart" data-chart='<?php echo json_encode($chartData['payment']); ?>'></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-pie me-2"></i>Pembayaran per Program
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="programChart" data-chart='<?php echo json_encode($chartData['program']); ?>'></canvas>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Recent Activities (Admin/Staff) -->
        <?php if (hasRole(['admin', 'staff']) && !empty($recentActivities)): ?>
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-history me-2"></i>Aktivitas Terbaru
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Aksi</th>
                                            <th>Deskripsi</th>
                                            <th>Waktu</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentActivities as $activity): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($activity['full_name']); ?></strong><br>
                                                    <small class="text-muted">@<?php echo htmlspecialchars($activity['username']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge badge-primary"><?php echo htmlspecialchars($activity['action']); ?></span>
                                                </td>
                                                <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo formatDate($activity['created_at'], 'd M Y H:i'); ?>
                                                    </small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Overdue Bills -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>Tagihan Jatuh Tempo
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($overdueBills)): ?>
                                <div class="text-center text-muted">
                                    <i class="fas fa-check-circle fa-3x mb-3"></i>
                                    <p>Tidak ada tagihan jatuh tempo</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach (array_slice($overdueBills, 0, 5) as $bill): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($bill['student_name']); ?></h6>
                                                    <p class="mb-1 small"><?php echo htmlspecialchars($bill['bill_number']); ?></p>
                                                    <small class="text-muted">
                                                        <?php echo formatCurrency($bill['amount']); ?>
                                                    </small>
                                                </div>
                                                <small class="text-danger">
                                                    <?php echo daysUntilDue($bill['due_date']); ?> hari
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <?php if (count($overdueBills) > 5): ?>
                                    <div class="text-center mt-3">
                                        <a href="bills.php?status=overdue" class="btn btn-sm btn-outline-primary">
                                            Lihat Semua
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Student Dashboard -->
        <?php if (hasRole('mahasiswa')): ?>
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-file-invoice me-2"></i>Tagihan Terbaru
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($userBills)): ?>
                                <div class="text-center text-muted">
                                    <i class="fas fa-file-invoice fa-3x mb-3"></i>
                                    <p>Tidak ada tagihan</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>No. Tagihan</th>
                                                <th>Deskripsi</th>
                                                <th>Jumlah</th>
                                                <th>Jatuh Tempo</th>
                                                <th>Status</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($userBills, 0, 5) as $bill): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($bill['bill_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($bill['description']); ?></td>
                                                    <td><?php echo formatCurrency($bill['amount']); ?></td>
                                                    <td>
                                                        <?php echo formatDate($bill['due_date']); ?>
                                                        <?php if (daysUntilDue($bill['due_date']) < 0): ?>
                                                            <span class="badge bg-danger ms-1">Overdue</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo getStatusBadgeClass($bill['status']); ?>">
                                                            <?php echo ucfirst($bill['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($bill['status'] === 'pending'): ?>
                                                            <a href="pay_bill.php?id=<?php echo $bill['id']; ?>" class="btn btn-sm btn-primary">
                                                                Bayar
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <a href="my_bills.php" class="btn btn-outline-primary">
                                        Lihat Semua Tagihan
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-receipt me-2"></i>Pembayaran Terbaru
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($userPayments)): ?>
                                <div class="text-center text-muted">
                                    <i class="fas fa-receipt fa-3x mb-3"></i>
                                    <p>Belum ada pembayaran</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach (array_slice($userPayments, 0, 5) as $payment): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($payment['payment_number']); ?></h6>
                                                    <p class="mb-1 small"><?php echo formatCurrency($payment['amount']); ?></p>
                                                    <small class="text-muted">
                                                        <?php echo formatDate($payment['payment_date']); ?>
                                                    </small>
                                                </div>
                                                <span class="badge <?php echo getStatusBadgeClass($payment['status']); ?>">
                                                    <?php echo ucfirst($payment['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <a href="my_payments.php" class="btn btn-sm btn-outline-primary">
                                        Lihat Semua
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
</body>
</html>