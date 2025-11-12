<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../functions/payment_functions.php';
require_once __DIR__ . '/../../functions/user_functions.php';
require_once __DIR__ . '/../../functions/program_functions.php';
require_once __DIR__ . '/../../functions/bill_functions.php';

requireAuth();
if (!hasRole(['admin', 'staff'])) {
    redirect('../unauthorized.php');
}

$currentUser = getCurrentUser();

// Data dropdown
$programs = getPrograms();

// Ambil Filter
$program_id = $_GET['program_id'] ?? '';
$status = $_GET['status'] ?? '';
$date_start = $_GET['date_start'] ?? '';
$date_end = $_GET['date_end'] ?? '';

// Query dasar - PERBAIKAN: gunakan student_id bukan user_id
$query = "
SELECT p.id, p.payment_number, u.full_name, u.nim, pr.program_name, 
       b.bill_number, b.amount AS bill_amount, p.amount as payment_amount,
       p.status, p.payment_date, p.payment_method
FROM payments p
JOIN users u ON p.student_id = u.id  -- PERBAIKAN: student_id bukan user_id
JOIN bills b ON p.bill_id = b.id
JOIN programs pr ON b.program_id = pr.id  -- PERBAIKAN: ambil program_id dari bills
WHERE 1=1
";

// Tambah filter
$params = [];

if ($program_id !== '') {
    $query .= " AND pr.id = :program_id";
    $params[':program_id'] = $program_id;
}
if ($status !== '') {
    $query .= " AND p.status = :status";
    $params[':status'] = $status;
}
if ($date_start !== '' && $date_end !== '') {
    $query .= " AND DATE(p.payment_date) BETWEEN :date_start AND :date_end";
    $params[':date_start'] = $date_start;
    $params[':date_end'] = $date_end;
}

$query .= " ORDER BY p.payment_date DESC";

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error in payments report: " . $e->getMessage());
    $payments = [];
    $_SESSION['error_message'] = "Terjadi kesalahan saat mengambil data laporan.";
}

// Hitung total
$total = 0;
foreach ($payments as $p) {
    $total += $p['payment_amount'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pembayaran - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            .container { max-width: 100% !important; }
            .card { border: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark no-print">
    <div class="container-fluid">
        <a class="navbar-brand" href="../dashboard.php">
            <i class="fas fa-graduation-cap me-2"></i><?php echo APP_NAME; ?>
        </a>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="../dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                </li>
                
                <?php if (hasRole(['admin', 'staff'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-users me-1"></i>Manajemen
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../../pages/users.php">Mahasiswa</a></li>
                            <li><a class="dropdown-item" href="../../pages/programs.php">Program Studi</a></li>
                            <li><a class="dropdown-item" href="../../pages/bills.php">Tagihan</a></li>
                            <li><a class="dropdown-item" href="../../pages/payments.php">Pembayaran</a></li>
                        </ul>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-chart-bar me-1"></i>Laporan
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item active" href="payments.php">Laporan Pembayaran</a></li>
                            <li><a class="dropdown-item" href="students.php">Laporan Mahasiswa</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($currentUser['full_name']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="../profile.php">
                            <i class="fas fa-user me-2"></i>Profil
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
    <!-- Notifikasi -->
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $_SESSION['error_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-chart-bar me-2 text-primary"></i>Laporan Pembayaran</h3>
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-success">
                <i class="fas fa-print me-1"></i>Cetak
            </button>
            <a href="../payments.php" class="btn btn-primary">
                <i class="fas fa-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>

    <!-- Filter -->
    <div class="card card-body mb-4 no-print">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Program Studi</label>
                <select name="program_id" class="form-control">
                    <option value="">Semua Program</option>
                    <?php foreach ($programs as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $p['id']==$program_id?'selected':'' ?>>
                            <?= htmlspecialchars($p['program_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Status Pembayaran</label>
                <select name="status" class="form-control">
                    <option value="">Semua Status</option>
                    <option value="confirmed" <?= $status=='confirmed'?'selected':'' ?>>Terkonfirmasi</option>
                    <option value="pending" <?= $status=='pending'?'selected':'' ?>>Menunggu</option>
                    <option value="rejected" <?= $status=='rejected'?'selected':'' ?>>Ditolak</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="date_start" class="form-control" value="<?= $date_start ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="date_end" class="form-control" value="<?= $date_end ?>">
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-1"></i>Terapkan Filter
                </button>
                <a href="payments.php" class="btn btn-secondary">Reset Filter</a>
            </div>
        </form>
    </div>

    <!-- Info Filter -->
    <?php if ($program_id || $status || $date_start || $date_end): ?>
    <div class="alert alert-info mb-3">
        <strong>Filter Aktif:</strong>
        <?php
        $filters = [];
        if ($program_id) {
            $program_name = '';
            foreach ($programs as $p) {
                if ($p['id'] == $program_id) {
                    $program_name = $p['program_name'];
                    break;
                }
            }
            $filters[] = "Program: " . $program_name;
        }
        if ($status) {
            $status_text = [
                'confirmed' => 'Terkonfirmasi',
                'pending' => 'Menunggu', 
                'rejected' => 'Ditolak'
            ];
            $filters[] = "Status: " . ($status_text[$status] ?? $status);
        }
        if ($date_start && $date_end) {
            $filters[] = "Periode: " . date('d/m/Y', strtotime($date_start)) . " - " . date('d/m/Y', strtotime($date_end));
        }
        echo implode(" | ", $filters);
        ?>
    </div>
    <?php endif; ?>

    <!-- Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($payments)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Tidak ada data pembayaran</h5>
                    <p class="text-muted">Tidak ditemukan data pembayaran dengan filter yang dipilih.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>No Pembayaran</th>
                                <th>NIM</th>
                                <th>Mahasiswa</th>
                                <th>Program Studi</th>
                                <th>No Tagihan</th>
                                <th>Jumlah Tagihan</th>
                                <th>Jumlah Dibayar</th>
                                <th>Metode</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['payment_number']) ?></td>
                                <td><?= htmlspecialchars($p['nim']) ?></td>
                                <td><?= htmlspecialchars($p['full_name']) ?></td>
                                <td><?= htmlspecialchars($p['program_name']) ?></td>
                                <td><?= htmlspecialchars($p['bill_number']) ?></td>
                                <td>Rp <?= number_format($p['bill_amount'], 0, ',', '.') ?></td>
                                <td>Rp <?= number_format($p['payment_amount'], 0, ',', '.') ?></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?= strtoupper(htmlspecialchars($p['payment_method'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $badge = [
                                        'confirmed' => 'success',
                                        'pending' => 'warning',
                                        'rejected' => 'danger'
                                    ];
                                    $status = $p['status'] ?? 'pending';
                                    ?>
                                    <span class="badge bg-<?= $badge[$status] ?>">
                                        <i class="fas fa-<?= 
                                            $status == 'pending' ? 'clock' : 
                                            ($status == 'confirmed' ? 'check' : 'times')
                                        ?> me-1"></i>
                                        <?= ucfirst($status) ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y', strtotime($p['payment_date'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-info mt-3">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Total Data:</strong> <?= count($payments) ?> pembayaran
                        </div>
                        <div class="col-md-6 text-end">
                            <strong>Total Pembayaran: </strong>
                            Rp <?= number_format($total, 0, ',', '.') ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Summary Cards -->
    <?php if (!empty($payments)): ?>
    <div class="row mt-4 no-print">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= count($payments) ?></h4>
                            <small>Total Pembayaran</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-receipt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">
                                <?= array_reduce($payments, function($carry, $item) {
                                    return $item['status'] === 'confirmed' ? $carry + 1 : $carry;
                                }, 0) ?>
                            </h4>
                            <small>Terkonfirmasi</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">
                                <?= array_reduce($payments, function($carry, $item) {
                                    return $item['status'] === 'pending' ? $carry + 1 : $carry;
                                }, 0) ?>
                            </h4>
                            <small>Menunggu</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set default dates if not set
    const dateStart = document.querySelector('input[name="date_start"]');
    const dateEnd = document.querySelector('input[name="date_end"]');
    
    if (dateStart && !dateStart.value) {
        // Set default to first day of current month
        const now = new Date();
        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
        dateStart.value = firstDay.toISOString().split('T')[0];
    }
    
    if (dateEnd && !dateEnd.value) {
        // Set default to today
        dateEnd.value = new Date().toISOString().split('T')[0];
    }
});
</script>
</body>
</html>