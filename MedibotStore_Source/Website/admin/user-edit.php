<?php
session_start();
require_once '../includes/db.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../login.php');
    exit();
}

$user_id = (int)($_GET['id'] ?? 0);
if (!$user_id) {
    header('Location: users.php?error=invalid_id');
    exit();
}

// Khởi tạo biến
$user = null;
$user_info = null;
$roles = [];
$success_message = '';
$error_message = '';

// Lấy danh sách roles
try {
    $roles_result = $conn->query("SELECT * FROM roles ORDER BY role_name");
    while ($role = $roles_result->fetch_assoc()) {
        $roles[] = $role;
    }
} catch (Exception $e) {
    $error_message = "Lỗi khi lấy danh sách vai trò: " . $e->getMessage();
}

// Lấy thông tin user hiện tại
try {
    $user_result = $conn->query("SELECT * FROM users WHERE user_id = $user_id");
    if ($user_result && $user_result->num_rows > 0) {
        $user = $user_result->fetch_assoc();
    } else {
        header('Location: users.php?error=not_found');
        exit();
    }
} catch (Exception $e) {
    header('Location: users.php?error=database_error');
    exit();
}

// Lấy thông tin user_info
try {
    $user_info_result = $conn->query("SELECT * FROM users_info WHERE user_id = $user_id");
    if ($user_info_result && $user_info_result->num_rows > 0) {
        $user_info = $user_info_result->fetch_assoc();
    }
} catch (Exception $e) {
    // Ignore error, user_info is optional
}

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role_id = (int)($_POST['role_id'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    $full_name = trim($_POST['full_name'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Tên đăng nhập không được để trống";
    } elseif (strlen($username) < 3) {
        $errors[] = "Tên đăng nhập phải có ít nhất 3 ký tự";
    } else {
        // Check username uniqueness
        $check_username = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
        $check_username->bind_param("si", $username, $user_id);
        $check_username->execute();
        if ($check_username->get_result()->num_rows > 0) {
            $errors[] = "Tên đăng nhập đã tồn tại";
        }
    }
    
    if (empty($email)) {
        $errors[] = "Email không được để trống";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email không hợp lệ";
    } else {
        // Check email uniqueness
        $check_email = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $check_email->bind_param("si", $email, $user_id);
        $check_email->execute();
        if ($check_email->get_result()->num_rows > 0) {
            $errors[] = "Email đã tồn tại";
        }
    }
    
    if (!empty($phone)) {
        if (!preg_match('/^[0-9]{10,11}$/', $phone)) {
            $errors[] = "Số điện thoại phải có 10-11 chữ số";
        } else {
            // Check phone uniqueness
            $check_phone = $conn->prepare("SELECT user_id FROM users WHERE phone = ? AND user_id != ?");
            $check_phone->bind_param("si", $phone, $user_id);
            $check_phone->execute();
            if ($check_phone->get_result()->num_rows > 0) {
                $errors[] = "Số điện thoại đã tồn tại";
            }
        }
    }
    
    if ($role_id <= 0) {
        $errors[] = "Vui lòng chọn vai trò";
    }
    
    if (!in_array($status, ['active', 'inactive', 'suspended'])) {
        $errors[] = "Trạng thái không hợp lệ";
    }
    
    if (!empty($date_of_birth) && !DateTime::createFromFormat('Y-m-d', $date_of_birth)) {
        $errors[] = "Ngày sinh không hợp lệ";
    }
    
    // Password validation
    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            $errors[] = "Mật khẩu mới phải có ít nhất 6 ký tự";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "Mật khẩu xác nhận không khớp";
        }
    }
    
    // If no errors, update database
    if (empty($errors)) {
        $conn->begin_transaction();
        
        try {
            // Update users table
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_user_sql = "UPDATE users SET username = ?, email = ?, phone = ?, role_id = ?, status = ?, password = ?, updated_at = NOW() WHERE user_id = ?";
                $stmt = $conn->prepare($update_user_sql);
                if (!$stmt) {
                    throw new Exception("Lỗi chuẩn bị câu lệnh SQL: " . $conn->error);
                }
                if (!$stmt->bind_param("sssissi", $username, $email, $phone, $role_id, $status, $hashed_password, $user_id)) {
                    throw new Exception("Lỗi bind tham số: " . $stmt->error);
                }
            } else {
                $update_user_sql = "UPDATE users SET username = ?, email = ?, phone = ?, role_id = ?, status = ?, updated_at = NOW() WHERE user_id = ?";
                $stmt = $conn->prepare($update_user_sql);
                if (!$stmt) {
                    throw new Exception("Lỗi chuẩn bị câu lệnh SQL: " . $conn->error);
                }
                if (!$stmt->bind_param("sssisi", $username, $email, $phone, $role_id, $status, $user_id)) {
                    throw new Exception("Lỗi bind tham số: " . $stmt->error);
                }
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Lỗi khi cập nhật thông tin user: " . $stmt->error);
            }
            
            // Update or insert users_info
            if (!empty($full_name) || !empty($date_of_birth) || !empty($address)) {
                if ($user_info) {
                    // Update existing record
                    $update_info_sql = "UPDATE users_info SET full_name = ?, date_of_birth = ?, address = ?, updated_at = NOW() WHERE user_id = ?";
                    $stmt_info = $conn->prepare($update_info_sql);
                    if (!$stmt_info) {
                        throw new Exception("Lỗi chuẩn bị câu lệnh SQL users_info: " . $conn->error);
                    }
                    $date_value = !empty($date_of_birth) ? $date_of_birth : null;
                    if (!$stmt_info->bind_param("sssi", $full_name, $date_value, $address, $user_id)) {
                        throw new Exception("Lỗi bind tham số users_info: " . $stmt_info->error);
                    }
                } else {
                    // Insert new record
                    $insert_info_sql = "INSERT INTO users_info (user_id, full_name, date_of_birth, address, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())";
                    $stmt_info = $conn->prepare($insert_info_sql);
                    if (!$stmt_info) {
                        throw new Exception("Lỗi chuẩn bị câu lệnh SQL users_info: " . $conn->error);
                    }
                    $date_value = !empty($date_of_birth) ? $date_of_birth : null;
                    if (!$stmt_info->bind_param("isss", $user_id, $full_name, $date_value, $address)) {
                        throw new Exception("Lỗi bind tham số users_info: " . $stmt_info->error);
                    }
                }
                
                if (!$stmt_info->execute()) {
                    throw new Exception("Lỗi khi cập nhật thông tin cá nhân: " . $stmt_info->error);
                }
            }
            
            $conn->commit();
            $success_message = "Cập nhật thông tin người dùng thành công!";
            
            // Refresh user data
            $user_result = $conn->query("SELECT * FROM users WHERE user_id = $user_id");
            $user = $user_result->fetch_assoc();
            
            $user_info_result = $conn->query("SELECT * FROM users_info WHERE user_id = $user_id");
            if ($user_info_result && $user_info_result->num_rows > 0) {
                $user_info = $user_info_result->fetch_assoc();
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = $e->getMessage();
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Get current role name
$current_role = null;
foreach ($roles as $role) {
    if ($role['role_id'] == $user['role_id']) {
        $current_role = $role;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa người dùng - <?= htmlspecialchars($user['username']) ?> - MediBot Store Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
    <link href="assets/css/sidebar.css" rel="stylesheet">
    <link href="assets/css/header.css" rel="stylesheet">
    <style>
        .form-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .form-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .form-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            border-radius: 10px 10px 0 0;
            margin: -1rem -1rem 1.5rem -1rem;
        }
        .form-section h5 {
            margin: 0;
            font-weight: 600;
        }
        .form-floating > .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .form-floating > .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .form-floating > label {
            color: #6c757d;
            font-weight: 500;
        }
        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-gradient:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            z-index: 5;
        }
        .form-floating {
            position: relative;
        }
        .alert {
            border-radius: 10px;
            border: none;
            padding: 1rem 1.5rem;
        }
        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f1b0b7 100%);
            color: #721c24;
        }
        .card-body {
            padding: 2rem;
        }
        .row.g-3 > * {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/headeradmin.php'; ?>        
    <?php include 'includes/sidebaradmin.php'; ?>

    <main class="main-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="users.php">Người dùng</a></li>
                            <li class="breadcrumb-item"><a href="user-view.php?id=<?= $user_id ?>">Chi tiết</a></li>
                            <li class="breadcrumb-item active">Chỉnh sửa</li>
                        </ol>
                    </nav>
                    <h1 class="h3 mb-0 text-gray-800">Chỉnh sửa người dùng</h1>
                    <p class="text-muted mb-0">Cập nhật thông tin cho tài khoản: <strong><?= htmlspecialchars($user['username']) ?></strong></p>
                </div>
                <div>
                    <a href="user-view.php?id=<?= $user_id ?>" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i>Quay lại
                    </a>
                    <a href="users.php" class="btn btn-outline-primary">
                        <i class="fas fa-users me-2"></i>Danh sách
                    </a>
                </div>
            </div>

            <!-- Alerts -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= $success_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $error_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" id="userEditForm" novalidate>
                <div class="row">
                    <!-- Thông tin tài khoản -->
                    <div class="col-lg-8">
                        <div class="card form-card mb-4">
                            <div class="card-body">
                                <div class="form-section">
                                    <h5><i class="fas fa-user me-2"></i>Thông tin tài khoản</h5>
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="username" name="username" 
                                                   value="<?= htmlspecialchars($user['username']) ?>" required>
                                            <label for="username">Tên đăng nhập *</label>
                                            <div class="invalid-feedback">Vui lòng nhập tên đăng nhập</div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?= htmlspecialchars($user['email']) ?>" required>
                                            <label for="email">Email *</label>
                                            <div class="invalid-feedback">Vui lòng nhập email hợp lệ</div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>" pattern="[0-9]{10,11}">
                                            <label for="phone">Số điện thoại</label>
                                            <div class="invalid-feedback">Số điện thoại phải có 10-11 chữ số</div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <select class="form-select" id="role_id" name="role_id" required>
                                                <option value="">Chọn vai trò</option>
                                                <?php foreach ($roles as $role): ?>
                                                    <option value="<?= $role['role_id'] ?>" 
                                                            <?= $role['role_id'] == $user['role_id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($role['role_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <label for="role_id">Vai trò *</label>
                                            <div class="invalid-feedback">Vui lòng chọn vai trò</div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <select class="form-select" id="status" name="status" required>
                                                <option value="active" <?= $user['status'] == 'active' ? 'selected' : '' ?>>
                                                    Hoạt động
                                                </option>
                                                <option value="inactive" <?= $user['status'] == 'inactive' ? 'selected' : '' ?>>
                                                    Không hoạt động
                                                </option>
                                                <option value="suspended" <?= $user['status'] == 'suspended' ? 'selected' : '' ?>>
                                                    Bị khóa
                                                </option>
                                            </select>
                                            <label for="status">Trạng thái *</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Thông tin cá nhân -->
                        <div class="card form-card mb-4">
                            <div class="card-body">
                                <div class="form-section">
                                    <h5><i class="fas fa-id-card me-2"></i>Thông tin cá nhân</h5>
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                                   value="<?= htmlspecialchars($user_info['full_name'] ?? '') ?>">
                                            <label for="full_name">Họ và tên</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                                   value="<?= htmlspecialchars($user_info['date_of_birth'] ?? '') ?>">
                                            <label for="date_of_birth">Ngày sinh</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <textarea class="form-control" id="address" name="address" 
                                                      style="height: 100px"><?= htmlspecialchars($user_info['address'] ?? '') ?></textarea>
                                            <label for="address">Địa chỉ</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Đổi mật khẩu -->
                        <div class="card form-card mb-4">
                            <div class="card-body">
                                <div class="form-section">
                                    <h5><i class="fas fa-lock me-2"></i>Đổi mật khẩu</h5>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Để trống nếu không muốn đổi mật khẩu
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                                   minlength="6">
                                            <label for="new_password">Mật khẩu mới</label>
                                            <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <div class="invalid-feedback">Mật khẩu phải có ít nhất 6 ký tự</div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                                   minlength="6">
                                            <label for="confirm_password">Xác nhận mật khẩu</label>
                                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <div class="invalid-feedback">Mật khẩu xác nhận không khớp</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="col-lg-4">
                        <!-- Thông tin hiện tại -->
                        <div class="card form-card mb-4">
                            <div class="card-body">
                                <div class="form-section">
                                    <h5><i class="fas fa-info-circle me-2"></i>Thông tin hiện tại</h5>
                                </div>
                                
                                <div class="text-center mb-3">
                                    <div class="user-avatar bg-primary text-white rounded-circle mx-auto mb-2" 
                                         style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; font-size: 2rem;">
                                        <?= strtoupper(substr(($user_info['full_name'] ?? null) ?: $user['username'], 0, 1)) ?>
                                    </div>
                                    <h6 class="mb-1"><?= htmlspecialchars(($user_info['full_name'] ?? null) ?: $user['username']) ?></h6>
                                    <small class="text-muted">@<?= htmlspecialchars($user['username']) ?></small>
                                </div>
                                
                                <div class="row text-center mb-3">
                                    <div class="col-6">
                                        <div class="border-end">
                                            <div class="h6 mb-0">ID</div>
                                            <small class="text-muted"><?= $user['user_id'] ?></small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="h6 mb-0">Vai trò</div>
                                        <small class="text-muted"><?= htmlspecialchars($current_role['role_name'] ?? 'N/A') ?></small>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">Ngày tạo:</small><br>
                                    <span><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></span>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">Cập nhật cuối:</small><br>
                                    <span><?= date('d/m/Y H:i', strtotime($user['updated_at'])) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Hành động -->
                        <div class="card form-card">
                            <div class="card-body">
                                <div class="form-section">
                                    <h5><i class="fas fa-cogs me-2"></i>Hành động</h5>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-gradient text-white">
                                        <i class="fas fa-save me-2"></i>Lưu thay đổi
                                    </button>
                                    
                                    <a href="user-view.php?id=<?= $user_id ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-eye me-2"></i>Xem chi tiết
                                    </a>
                                    
                                    <button type="button" class="btn btn-outline-warning" onclick="resetForm()">
                                        <i class="fas fa-undo me-2"></i>Khôi phục
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
    <script>
        // Form validation
        document.getElementById('userEditForm').addEventListener('submit', function(e) {
            const form = this;
            
            // Bootstrap validation
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            // Password confirmation check
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword || confirmPassword) {
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    e.stopPropagation();
                    document.getElementById('confirm_password').setCustomValidity('Mật khẩu xác nhận không khớp');
                    document.getElementById('confirm_password').classList.add('is-invalid');
                } else {
                    document.getElementById('confirm_password').setCustomValidity('');
                    document.getElementById('confirm_password').classList.remove('is-invalid');
                }
            }
            
            form.classList.add('was-validated');
        });

        // Password toggle
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const button = field.nextElementSibling.nextElementSibling;
            const icon = button.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // Reset form
        function resetForm() {
            if (confirm('Bạn có chắc chắn muốn khôi phục lại thông tin ban đầu?')) {
                document.getElementById('userEditForm').reset();
                document.getElementById('userEditForm').classList.remove('was-validated');
            }
        }

        // Real-time validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword && confirmPassword) {
                if (newPassword === confirmPassword) {
                    this.setCustomValidity('');
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.setCustomValidity('Mật khẩu xác nhận không khớp');
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                }
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid', 'is-valid');
            }
        });

        // Username validation
        document.getElementById('username').addEventListener('input', function() {
            if (this.value.length < 3) {
                this.setCustomValidity('Tên đăng nhập phải có ít nhất 3 ký tự');
            } else {
                this.setCustomValidity('');
            }
        });

        // Email validation
        document.getElementById('email').addEventListener('input', function() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(this.value)) {
                this.setCustomValidity('Email không hợp lệ');
            } else {
                this.setCustomValidity('');
            }
        });

        // Phone validation
        document.getElementById('phone').addEventListener('input', function() {
            const phoneRegex = /^[0-9]{10,11}$/;
            if (this.value && !phoneRegex.test(this.value)) {
                this.setCustomValidity('Số điện thoại phải có 10-11 chữ số');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html> 