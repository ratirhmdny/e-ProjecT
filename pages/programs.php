<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../functions/program_functions.php';
require_once __DIR__ . '/../includes/security.php';

requireAuth();
if (!hasRole(['admin', 'staff'])) {
    header("Location: unauthorized.php");
    exit;
}

$pdo = getDbConnection();

// Handle Add Program
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_program'])) {
    $data = [
        'program_code' => $_POST['program_code'],
        'program_name' => $_POST['program_name'],
        'description' => $_POST['description'],
        'tuition_fee' => $_POST['tuition_fee'],
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    createProgram($data);
    header("Location: programs.php");
    exit;
}

// Handle Update Program
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_program'])) {
    $id = $_POST['id'];
    $data = [
        'program_code' => $_POST['program_code'],
        'program_name' => $_POST['program_name'],
        'description' => $_POST['description'],
        'tuition_fee' => $_POST['tuition_fee'],
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    updateProgram($id, $data);
    header("Location: programs.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    deleteProgram($pdo, $_GET['delete']);
    header("Location: programs.php");
    exit;
}

$programs = getPrograms();
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Program Studi - <?php echo APP_NAME; ?></title>
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
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Daftar Program Studi</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus me-1"></i>Tambah Program
        </button>
    </div>

    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>Kode</th>
                <th>Nama Program</th>
                <th>Biaya SPP</th>
                <th>Status</th>
                <th width="140">Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($programs as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['program_code']) ?></td>
                <td><?= htmlspecialchars($p['program_name']) ?></td>
                <td>Rp <?= number_format($p['tuition_fee'], 0, ',', '.') ?></td>
                <td>
                    <?php if ($p['is_active']): ?>
                        <span class="badge bg-success">Aktif</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Nonaktif</span>
                    <?php endif; ?>
                </td>
                <td>
                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $p['id'] ?>">Edit</button>
                    <a href="?delete=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                </td>
            </tr>

            <!-- Edit Modal -->
            <!-- Edit Modal -->
            <div class="modal fade" id="editModal<?= $p['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-edit me-2"></i>Edit Program Studi
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="edit_program" value="1">
                            
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="edit_program_code_<?= $p['id'] ?>" class="form-label">Kode Program *</label>
                                            <input type="text" 
                                                class="form-control" 
                                                id="edit_program_code_<?= $p['id'] ?>" 
                                                name="program_code" 
                                                value="<?= htmlspecialchars($p['program_code']) ?>" 
                                                required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="edit_tuition_fee_<?= $p['id'] ?>" class="form-label">Biaya SPP *</label>
                                            <input type="number" 
                                                class="form-control" 
                                                id="edit_tuition_fee_<?= $p['id'] ?>" 
                                                name="tuition_fee" 
                                                value="<?= htmlspecialchars($p['tuition_fee']) ?>" 
                                                required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_program_name_<?= $p['id'] ?>" class="form-label">Nama Program *</label>
                                    <input type="text" 
                                        class="form-control" 
                                        id="edit_program_name_<?= $p['id'] ?>" 
                                        name="program_name" 
                                        value="<?= htmlspecialchars($p['program_name']) ?>" 
                                        required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_description_<?= $p['id'] ?>" class="form-label">Deskripsi</label>
                                    <textarea class="form-control" 
                                            id="edit_description_<?= $p['id'] ?>" 
                                            name="description" 
                                            rows="3"><?= htmlspecialchars($p['description']) ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                            type="checkbox" 
                                            id="edit_is_active_<?= $p['id'] ?>" 
                                            name="is_active" 
                                            value="1" 
                                            <?= $p['is_active'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="edit_is_active_<?= $p['id'] ?>">
                                            Aktif
                                        </label>
                                    </div>
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
<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>Tambah Program Studi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="add_program" value="1">
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="add_program_code" class="form-label">Kode Program *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="add_program_code" 
                                       name="program_code" 
                                       required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="add_tuition_fee" class="form-label">Biaya SPP *</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="add_tuition_fee" 
                                       name="tuition_fee" 
                                       required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add_program_name" class="form-label">Nama Program *</label>
                        <input type="text" 
                               class="form-control" 
                               id="add_program_name" 
                               name="program_name" 
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add_description" class="form-label">Deskripsi</label>
                        <textarea class="form-control" 
                                  id="add_description" 
                                  name="description" 
                                  rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="add_is_active" 
                                   name="is_active" 
                                   value="1" 
                                   checked>
                            <label class="form-check-label" for="add_is_active">
                                Aktif
                            </label>
                        </div>
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
