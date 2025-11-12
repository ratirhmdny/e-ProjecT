<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../functions/user_functions.php';
require_once __DIR__ . '/../../functions/program_functions.php';
require_once __DIR__ . '/../../functions/bill_functions.php';
require_once __DIR__ . '/../../functions/payment_functions.php';

requireAuth();
if (!hasRole(['admin', 'staff'])) {
    redirect('../unauthorized.php');
}

$currentUser = getCurrentUser();

// Dropdown program
$programs = getPrograms();

// Filter
$program_id = $_GET['program_id'] ?? '';
$status = $_GET['status'] ?? '';

// Query mahasiswa - PERBAIKAN: sesuaikan dengan struktur database
$query = "
SELECT u.id, u.full_name, u.nim, u.email, u.phone, u.is_active, p.program_name,
       COUNT(b.id) as total_bills,
       SUM(CASE WHEN b.status = 'paid' THEN 1 ELSE 0 END) as paid_bills,
       SUM(CASE WHEN b.status = 'pending' THEN 1 ELSE 0 END) as pending_bills
FROM users u
LEFT JOIN programs p ON u.program_id = p.id
LEFT JOIN bills b ON u.id = b.student_id
WHERE u.role = 'mahasiswa'
";

$params = [];

if ($program_id !== '') {
    $query .= " AND p.id = :program_id";
    $params[':program_id'] = $program_id;
}

if ($status !== '') {
    if ($status === 'active') {
        $query .= " AND u.is_active = 1";
    } elseif ($status === 'inactive') {
        $query .= " AND u.is_active = 0";
    }
}

$query .= " GROUP BY u.id, u.full_name, u.nim, u.email, u.phone, u.is_active, p.program_name";
$query .= " ORDER BY u.full_name ASC";

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error in students report: " . $e->getMessage());
    $students = [];
    $_SESSION['error_message'] = "Terjadi kesalahan saat mengambil data mahasiswa.";
}

// Hitung statistik
$total_students = count($students);
$active_students = array_reduce($students, function($carry, $student) {
    return $carry + ($student['is_active'] ? 1 : 0);
}, 0);
$inactive_students = $total_students - $active_students;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Mahasiswa - <?= APP_NAME ?></title>
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
                            <li><a class="dropdown-item" href="payments.php">Laporan Pembayaran</a></li>
                            <li><a class="dropdown-item active" href="students.php">Laporan Mahasiswa</a></li>
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
        <h3><i class="fas fa-user-graduate me-2 text-primary"></i>Laporan Mahasiswa</h3>
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-success">
                <i class="fas fa-print me-1"></i>Cetak
            </button>
            <a href="../users.php" class="btn btn-primary">
                <i class="fas fa-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4 no-print">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= $total_students ?></h4>
                            <small>Total Mahasiswa</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= $active_students ?></h4>
                            <small>Aktif</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-user-check fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= $inactive_students ?></h4>
                            <small>Nonaktif</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-user-times fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">
                                <?= $total_students > 0 ? round(($active_students / $total_students) * 100, 1) : 0 ?>%
                            </h4>
                            <small>Persentase Aktif</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-chart-pie fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card card-body mb-4 no-print">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
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
            
            <div class="col-md-4">
                <label class="form-label">Status Mahasiswa</label>
                <select name="status" class="form-control">
                    <option value="">Semua Status</option>
                    <option value="active" <?= $status=='active'?'selected':'' ?>>Aktif</option>
                    <option value="inactive" <?= $status=='inactive'?'selected':'' ?>>Nonaktif</option>
                </select>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-search me-1"></i>Terapkan Filter
                </button>
                <a href="students.php" class="btn btn-secondary">Reset Filter</a>
            </div>
        </form>
    </div>

    <!-- Info Filter -->
    <?php if ($program_id || $status): ?>
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
            $status_text = $status === 'active' ? 'Aktif' : 'Nonaktif';
            $filters[] = "Status: " . $status_text;
        }
        echo implode(" | ", $filters);
        ?>
    </div>
    <?php endif; ?>

    <!-- Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($students)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Tidak ada data mahasiswa</h5>
                    <p class="text-muted">Tidak ditemukan data mahasiswa dengan filter yang dipilih.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>NIM</th>
                                <th>Nama Mahasiswa</th>
                                <th>Program Studi</th>
                                <th>Email</th>
                                <th>No HP</th>
                                <th>Total Tagihan</th>
                                <th>Lunas</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td><?= htmlspecialchars($s['nim'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($s['full_name']) ?></td>
                                <td><?= htmlspecialchars($s['program_name'] ?? 'Tidak ada program') ?></td>
                                <td><?= htmlspecialchars($s['email']) ?></td>
                                <td><?= htmlspecialchars($s['phone'] ?: '-') ?></td>
                                <td>
                                    <span class="badge bg-<?= $s['total_bills'] > 0 ? 'primary' : 'secondary' ?>">
                                        <?= $s['total_bills'] ?> tagihan
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $s['paid_bills'] > 0 ? 'success' : 'secondary' ?>">
                                        <?= $s['paid_bills'] ?> lunas
                                    </span>
                                </td>
                                <td>
                                    <?php if ($s['is_active']): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>Aktif
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-times-circle me-1"></i>Nonaktif
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-info mt-3">
                    <strong>Total Data:</strong> <?= count($students) ?> mahasiswa
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Program Distribution -->
    <?php if (!empty($students)): ?>
    <div class="row mt-4 no-print">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Distribusi Mahasiswa per Program
                    </h6>
                </div>
                <div class="card-body">
                    <?php
                    $program_stats = [];
                    foreach ($students as $student) {
                        $program_name = $student['program_name'] ?? 'Tidak ada program';
                        if (!isset($program_stats[$program_name])) {
                            $program_stats[$program_name] = 0;
                        }
                        $program_stats[$program_name]++;
                    }
                    ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($program_stats as $program_name => $count): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <?= htmlspecialchars($program_name) ?>
                                <span class="badge bg-primary rounded-pill"><?= $count ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Status Mahasiswa
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="text-success">
                                <h4><?= $active_students ?></h4>
                                <small>Aktif</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-secondary">
                                <h4><?= $inactive_students ?></h4>
                                <small>Nonaktif</small>
                            </div>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 20px;">
                        <div class="progress-bar bg-success" style="width: <?= $total_students > 0 ? ($active_students / $total_students) * 100 : 0 ?>%">
                            <?= $total_students > 0 ? round(($active_students / $total_students) * 100, 1) : 0 ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>