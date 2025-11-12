<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../functions/user_functions.php';
require_once __DIR__ . '/../functions/program_functions.php';
require_once __DIR__ . '/../includes/security.php';

// Require admin or staff role
requireRole(['admin', 'staff']);

// Get current user
$currentUser = getCurrentUser();

// Pagination and search parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$search = sanitizeInput($_GET['search'] ?? '');
$per_page = ITEMS_PER_PAGE;

// Get users
$users = getUsers($page, $per_page, $search);
$total_users = countUsers($search);
$pagination = getPaginationData($total_users, $page, $per_page);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('error', 'Invalid request. Please try again.');
        header('Location: users.php');
        exit();
    }
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            handleCreateUser();
            break;
            
        case 'update':
            handleUpdateUser();
            break;
            
        case 'delete':
            handleDeleteUser();
            break;
    }
}

/**
 * Handle create user
 */
function handleCreateUser() {
    $data = [
        'username' => sanitizeInput($_POST['username'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'role' => sanitizeInput($_POST['role'] ?? 'mahasiswa'),
        'full_name' => sanitizeInput($_POST['full_name'] ?? ''),
        'nim' => sanitizeInput($_POST['nim'] ?? ''),
        'program_id' => intval($_POST['program_id'] ?? 0),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'address' => sanitizeInput($_POST['address'] ?? ''),
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    // Validate input
    $errors = [];
    
    if (empty($data['username'])) {
        $errors[] = 'Username is required';
    } elseif (usernameExists($data['username'])) {
        $errors[] = 'Username already exists';
    }
    
    if (empty($data['email'])) {
        $errors[] = 'Email is required';
    } elseif (!isValidEmail($data['email'])) {
        $errors[] = 'Invalid email format';
    } elseif (emailExists($data['email'])) {
        $errors[] = 'Email already exists';
    }
    
    if (empty($data['password'])) {
        $errors[] = 'Password is required';
    } elseif (strlen($data['password']) < PASSWORD_MIN_LENGTH) {
        $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
    }
    
    if (empty($data['full_name'])) {
        $errors[] = 'Full name is required';
    }
    
    if ($data['role'] === 'mahasiswa') {
        if (empty($data['nim'])) {
            $errors[] = 'NIM is required for students';
        } elseif (!isValidNIM($data['nim'])) {
            $errors[] = 'Invalid NIM format';
        } elseif (nimExists($data['nim'])) {
            $errors[] = 'NIM already exists';
        }
        
        if (empty($data['program_id'])) {
            $errors[] = 'Program is required for students';
        }
    }
    
    if (empty($errors)) {
        $user_id = createUser($data);
        
        if ($user_id) {
            setFlashMessage('success', 'User created successfully');
            header('Location: users.php');
            exit();
        } else {
            setFlashMessage('error', 'Failed to create user');
        }
    } else {
        setFlashMessage('error', implode('<br>', $errors));
    }
    
    header('Location: users.php');
    exit();
}

/**
 * Handle update user
 */
function handleUpdateUser() {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        setFlashMessage('error', 'Invalid user ID');
        header('Location: users.php');
        exit();
    }
    
    $data = [
        'username' => sanitizeInput($_POST['username'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'role' => sanitizeInput($_POST['role'] ?? 'mahasiswa'),
        'full_name' => sanitizeInput($_POST['full_name'] ?? ''),
        'nim' => sanitizeInput($_POST['nim'] ?? ''),
        'program_id' => intval($_POST['program_id'] ?? 0),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'address' => sanitizeInput($_POST['address'] ?? ''),
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    // Update password if provided
    if (!empty($_POST['password'])) {
        $data['password'] = $_POST['password'];
    }
    
    // Validate input
    $errors = [];
    
    if (empty($data['username'])) {
        $errors[] = 'Username is required';
    } elseif (usernameExists($data['username'], $id)) {
        $errors[] = 'Username already exists';
    }
    
    if (empty($data['email'])) {
        $errors[] = 'Email is required';
    } elseif (!isValidEmail($data['email'])) {
        $errors[] = 'Invalid email format';
    } elseif (emailExists($data['email'], $id)) {
        $errors[] = 'Email already exists';
    }
    
    if (empty($data['full_name'])) {
        $errors[] = 'Full name is required';
    }
    
    if ($data['role'] === 'mahasiswa') {
        if (empty($data['nim'])) {
            $errors[] = 'NIM is required for students';
        } elseif (!isValidNIM($data['nim'])) {
            $errors[] = 'Invalid NIM format';
        } elseif (nimExists($data['nim'], $id)) {
            $errors[] = 'NIM already exists';
        }
        
        if (empty($data['program_id'])) {
            $errors[] = 'Program is required for students';
        }
    }
    
    if (empty($errors)) {
        if (updateUser($id, $data)) {
            setFlashMessage('success', 'User updated successfully');
        } else {
            setFlashMessage('error', 'Failed to update user');
        }
    } else {
        setFlashMessage('error', implode('<br>', $errors));
    }
    
    header('Location: users.php');
    exit();
}

/**
 * Handle delete user
 */
function handleDeleteUser() {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        setFlashMessage('error', 'Invalid user ID');
        header('Location: users.php');
        exit();
    }
    
    // Prevent self-deletion
    if ($id == $_SESSION['user_id']) {
        setFlashMessage('error', 'Cannot delete your own account');
        header('Location: users.php');
        exit();
    }
    
    if (deleteUser($id)) {
        setFlashMessage('success', 'User deleted successfully');
    } else {
        setFlashMessage('error', 'Failed to delete user');
    }
    
    header('Location: users.php');
    exit();
}

// Get programs for dropdown
$programs = getPrograms(true);

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Mahasiswa - <?php echo APP_NAME; ?></title>
    
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-users me-1"></i>Manajemen
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item active" href="users.php">Mahasiswa</a></li>
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
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1>
                    <i class="fas fa-users me-2"></i>Manajemen Mahasiswa
                </h1>
                <p class="text-muted">Kelola data mahasiswa dan pengguna sistem</p>
            </div>
            
            <div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-plus me-2"></i>Tambah Mahasiswa
                </button>
            </div>
        </div>
        
        <!-- Search and Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" 
                                   class="form-control" 
                                   name="search" 
                                   placeholder="Cari berdasarkan nama, username, atau NIM..."
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-outline-primary">
                                Cari
                            </button>
                        </div>
                    </div>
                    
                    <div class="col-md-6 text-end">
                        <?php if ($search): ?>
                            <a href="users.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Reset
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Daftar Pengguna</h5>
                    <span class="badge bg-secondary"><?php echo $total_users; ?> pengguna</span>
                </div>
            </div>
            
            <div class="card-body">
                <?php if (empty($users)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Tidak ada pengguna ditemukan</h5>
                        <?php if ($search): ?>
                            <p class="text-muted">Coba kata kunci pencarian yang berbeda</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Lengkap</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>NIM</th>
                                    <th>Program</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $index => $user): ?>
                                    <tr>
                                        <td><?php echo $pagination['offset'] + $index + 1; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['nim'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($user['program_name'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge <?php echo getRoleBadgeClass($user['role']); ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $user['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo $user['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" 
                                                        class="btn btn-sm btn-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editUserModal<?php echo $user['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger btn-delete" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteUserModal<?php echo $user['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php echo generatePagination($pagination, 'users.php'); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Tambah Pengguna
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="add_username" class="form-label">Username *</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="add_username" 
                                           name="username" 
                                           required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="add_email" class="form-label">Email *</label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="add_email" 
                                           name="email" 
                                           required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="add_password" class="form-label">Password *</label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="add_password" 
                                           name="password" 
                                           required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="add_role" class="form-label">Role *</label>
                                    <select class="form-control" id="add_role" name="role" required>
                                        <option value="mahasiswa">Mahasiswa</option>
                                        <option value="staff">Staff</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="add_full_name" class="form-label">Nama Lengkap *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="add_full_name" 
                                   name="full_name" 
                                   required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="add_nim" class="form-label">NIM</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="add_nim" 
                                           name="nim">
                                    <small class="text-muted">Wajib diisi untuk mahasiswa</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="add_program_id" class="form-label">Program Studi</label>
                                    <select class="form-control" id="add_program_id" name="program_id">
                                        <option value="">Pilih Program</option>
                                        <?php foreach ($programs as $program): ?>
                                            <option value="<?php echo $program['id']; ?>">
                                                <?php echo htmlspecialchars($program['program_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="add_phone" class="form-label">No. Telepon</label>
                            <input type="tel" 
                                   class="form-control" 
                                   id="add_phone" 
                                   name="phone">
                        </div>
                        
                        <div class="mb-3">
                            <label for="add_address" class="form-label">Alamat</label>
                            <textarea class="form-control" 
                                      id="add_address" 
                                      name="address" 
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
    
    <!-- Edit and Delete Modals for each user -->
    <?php foreach ($users as $user): ?>
        <!-- Edit User Modal -->
        <div class="modal fade" id="editUserModal<?php echo $user['id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-edit me-2"></i>Edit Pengguna
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_username_<?php echo $user['id']; ?>" class="form-label">Username *</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="edit_username_<?php echo $user['id']; ?>" 
                                               name="username" 
                                               value="<?php echo htmlspecialchars($user['username']); ?>"
                                               required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_email_<?php echo $user['id']; ?>" class="form-label">Email *</label>
                                        <input type="email" 
                                               class="form-control" 
                                               id="edit_email_<?php echo $user['id']; ?>" 
                                               name="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>"
                                               required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_password_<?php echo $user['id']; ?>" class="form-label">Password Baru</label>
                                        <input type="password" 
                                               class="form-control" 
                                               id="edit_password_<?php echo $user['id']; ?>" 
                                               name="password">
                                        <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_role_<?php echo $user['id']; ?>" class="form-label">Role *</label>
                                        <select class="form-control" id="edit_role_<?php echo $user['id']; ?>" name="role" required>
                                            <option value="mahasiswa" <?php echo $user['role'] === 'mahasiswa' ? 'selected' : ''; ?>>Mahasiswa</option>
                                            <option value="staff" <?php echo $user['role'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_full_name_<?php echo $user['id']; ?>" class="form-label">Nama Lengkap *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="edit_full_name_<?php echo $user['id']; ?>" 
                                       name="full_name" 
                                       value="<?php echo htmlspecialchars($user['full_name']); ?>"
                                       required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_nim_<?php echo $user['id']; ?>" class="form-label">NIM</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="edit_nim_<?php echo $user['id']; ?>" 
                                               name="nim" 
                                               value="<?php echo htmlspecialchars($user['nim'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_program_id_<?php echo $user['id']; ?>" class="form-label">Program Studi</label>
                                        <select class="form-control" id="edit_program_id_<?php echo $user['id']; ?>" name="program_id">
                                            <option value="">Pilih Program</option>
                                            <?php foreach ($programs as $program): ?>
                                                <option value="<?php echo $program['id']; ?>" 
                                                        <?php echo ($user['program_id'] == $program['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($program['program_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_phone_<?php echo $user['id']; ?>" class="form-label">No. Telepon</label>
                                <input type="tel" 
                                       class="form-control" 
                                       id="edit_phone_<?php echo $user['id']; ?>" 
                                       name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_address_<?php echo $user['id']; ?>" class="form-label">Alamat</label>
                                <textarea class="form-control" 
                                          id="edit_address_<?php echo $user['id']; ?>" 
                                          name="address" 
                                          rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="edit_is_active_<?php echo $user['id']; ?>" 
                                           name="is_active" 
                                           value="1" 
                                           <?php echo $user['is_active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="edit_is_active_<?php echo $user['id']; ?>">
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
                                <i class="fas fa-save me-2"></i>Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Delete User Modal -->
        <div class="modal fade" id="deleteUserModal<?php echo $user['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-trash me-2"></i>Konfirmasi Hapus
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        
                        <div class="modal-body">
                            <p>Apakah Anda yakin ingin menghapus pengguna berikut?</p>
                            <ul>
                                <li><strong>Nama:</strong> <?php echo htmlspecialchars($user['full_name']); ?></li>
                                <li><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></li>
                                <li><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></li>
                            </ul>
                            <p class="text-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Tindakan ini tidak dapat dibatalkan!
                            </p>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Batal
                            </button>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash me-2"></i>Hapus
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
    
    <script>
        // Role change handler for add form
        document.getElementById('add_role').addEventListener('change', function() {
            const nimField = document.getElementById('add_nim').parentNode;
            const programField = document.getElementById('add_program_id').parentNode;
            
            if (this.value === 'mahasiswa') {
                nimField.style.display = 'block';
                programField.style.display = 'block';
                document.getElementById('add_nim').required = true;
                document.getElementById('add_program_id').required = true;
            } else {
                nimField.style.display = 'none';
                programField.style.display = 'none';
                document.getElementById('add_nim').required = false;
                document.getElementById('add_program_id').required = false;
            }
        });
        
        // Role change handler for edit forms
        document.querySelectorAll('[id^="edit_role_"]').forEach(select => {
            select.addEventListener('change', function() {
                const userId = this.id.replace('edit_role_', '');
                const nimField = document.getElementById('edit_nim_' + userId).parentNode;
                const programField = document.getElementById('edit_program_id_' + userId).parentNode;
                
                if (this.value === 'mahasiswa') {
                    nimField.style.display = 'block';
                    programField.style.display = 'block';
                    document.getElementById('edit_nim_' + userId).required = true;
                    document.getElementById('edit_program_id_' + userId).required = true;
                } else {
                    nimField.style.display = 'none';
                    programField.style.display = 'none';
                    document.getElementById('edit_nim_' + userId).required = false;
                    document.getElementById('edit_program_id_' + userId).required = false;
                }
            });
        });
    </script>
</body>
</html>