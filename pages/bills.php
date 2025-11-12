<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../functions/bill_functions.php';
require_once __DIR__ . '/../functions/user_functions.php';
require_once __DIR__ . '/../functions/program_functions.php';

requireAuth();
if (!hasRole(['admin', 'staff'])) {
    redirect('unauthorized.php');
}

$currentUser = getCurrentUser();

$pdo = getDbConnection();

// Get data dropdown
$students = getUsers();
$programs = getPrograms();

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_bill'])) {
    $data = [
        'bill_number' => uniqid('BILL-'),
        'student_id' => $_POST['student_id'],
        'program_id' => $_POST['program_id'],
        'amount' => $_POST['amount'],
        'description' => $_POST['description'],
        'due_date' => $_POST['due_date'],
        'status' => $_POST['status'],
        'created_by' => getCurrentUser()['id']
    ];
    createBill($data);
    header("Location: bills.php");
    exit;
}

// Handle Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_bill'])) {
    $id = $_POST['id'];
    $data = [
        'amount' => $_POST['amount'],
        'description' => $_POST['description'],
        'due_date' => $_POST['due_date'],
        'status' => $_POST['status']
    ];
    
    if (updateBill($id, $data)) {
        $_SESSION['success_message'] = "Tagihan berhasil diperbarui!";
    } else {
        $_SESSION['error_message'] = "Gagal memperbarui tagihan!";
    }
    
    header("Location: bills.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    deleteBill($_GET['delete']);
    header("Location: bills.php");
    exit;
}

$bills = getBills();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Tagihan - <?php echo APP_NAME; ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

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

<div class="container py-4">
    <div class="d-flex justify-content-between mb-3">
        <h3>Daftar Tagihan</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus me-1"></i>Tambah Tagihan
        </button>
    </div>

    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>No Tagihan</th>
                <th>Mahasiswa</th>
                <th>Program</th>
                <th>Jumlah</th>
                <th>Jatuh Tempo</th>
                <th>Status</th>
                <th width="140">Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($bills as $b): ?>
            <tr>
                <td><?= $b['bill_number'] ?></td>
                <td><?= $b['student_name'] ?></td>
                <td><?= $b['program_name'] ?></td>
                <td>Rp <?= number_format($b['amount'], 0, ',', '.') ?></td>
                <td><?= $b['due_date'] ?></td>
                <td>
                    <?php
                    $badge = [
                        'pending' => 'warning',
                        'paid' => 'success',
                        'overdue' => 'danger',
                        'cancelled' => 'secondary'
                    ];
                    $status = $b['status'] ?? 'pending';
                    ?>
                    <span class="badge bg-<?= $badge[$status] ?>">
                        <i class="fas fa-<?= 
                            $status == 'pending' ? 'clock' : 
                            ($status == 'paid' ? 'check' : 
                            ($status == 'overdue' ? 'exclamation-triangle' : 'ban'))
                        ?> me-1"></i>
                        <?= ucfirst($status) ?>
                    </span>
                </td>
                <td>
                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $b['id'] ?>">Edit</button>
                    <a href="?delete=<?= $b['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus?')">Hapus</a>
                </td>
            </tr>

            <!-- Edit Modal -->
            <!-- Edit Modal -->
            <div class="modal fade" id="editModal<?= $b['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-edit me-2"></i>Edit Tagihan
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <input type="hidden" name="edit_bill" value="1">
                            
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="edit_amount_<?= $b['id'] ?>" class="form-label">Jumlah Tagihan *</label>
                                            <input type="number" 
                                                class="form-control" 
                                                id="edit_amount_<?= $b['id'] ?>" 
                                                name="amount" 
                                                value="<?= htmlspecialchars($b['amount']) ?>" 
                                                required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="edit_due_date_<?= $b['id'] ?>" class="form-label">Jatuh Tempo *</label>
                                            <input type="date" 
                                                class="form-control" 
                                                id="edit_due_date_<?= $b['id'] ?>" 
                                                name="due_date" 
                                                value="<?= htmlspecialchars($b['due_date']) ?>" 
                                                required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_description_<?= $b['id'] ?>" class="form-label">Deskripsi</label>
                                    <textarea class="form-control" 
                                            id="edit_description_<?= $b['id'] ?>" 
                                            name="description" 
                                            rows="3"><?= htmlspecialchars($b['description']) ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_status_<?= $b['id'] ?>" class="form-label">Status *</label>
                                    <select class="form-control" id="edit_status_<?= $b['id'] ?>" name="status" required>
                                        <option value="pending" <?= $b['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="paid" <?= $b['status'] == 'paid' ? 'selected' : '' ?>>Paid</option>
                                        <option value="overdue" <?= $b['status'] == 'overdue' ? 'selected' : '' ?>>Overdue</option>
                                        <option value="cancelled" <?= $b['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>Batal
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>Tambah Tagihan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="add_bill" value="1">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_student_id" class="form-label">Mahasiswa *</label>
                        <select class="form-control" id="add_student_id" name="student_id" required>
                            <option value="">Pilih Mahasiswa...</option>
                            <?php foreach ($students as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?> (<?= htmlspecialchars($s['nim'] ?? '') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add_program_id" class="form-label">Program Studi *</label>
                        <select class="form-control" id="add_program_id" name="program_id" required>
                            <?php foreach ($programs as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['program_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="add_amount" class="form-label">Jumlah Tagihan *</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="add_amount" 
                                       name="amount" 
                                       required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="add_due_date" class="form-label">Jatuh Tempo *</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="add_due_date" 
                                       name="due_date" 
                                       required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add_description" class="form-label">Deskripsi</label>
                        <textarea class="form-control" 
                                  id="add_description" 
                                  name="description" 
                                  rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add_status" class="form-label">Status</label>
                        <select class="form-control" id="add_status" name="status">
                            <option value="pending" selected>Pending</option>
                            <option value="paid">Paid</option>
                            <option value="overdue">Overdue</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
