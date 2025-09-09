<?php
session_start();
require_once '../includes/db.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../login.php');
    exit();
}

// Xử lý các actions
$action = $_GET['action'] ?? 'list';
$role_filter = $_GET['role'] ?? 'all';
$search = $_GET['search'] ?? '';
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Xử lý delete user
if ($action == 'delete' && isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    
    // Không cho phép xóa admin hiện tại
    if ($user_id == $_SESSION['user_id']) {
        header('Location: users.php?error=cannot_delete_self');
        exit();
    }
    
    $conn->begin_transaction();
    try {
        // Xóa thông tin user
        $conn->query("DELETE FROM users_info WHERE user_id = $user_id");
        // Xóa user
        $conn->query("DELETE FROM users WHERE user_id = $user_id");
        
        $conn->commit();
        header('Location: users.php?success=deleted');
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        header('Location: users.php?error=delete_failed');
        exit();
    }
}

// Lấy danh sách users
$where_conditions = [];
$params = [];

if ($role_filter != 'all') {
    $role_id = 0;
    switch($role_filter) {
        case 'admin': $role_id = 1; break;  // Admin
        case 'doctor': $role_id = 2; break; // Bác sĩ 
        case 'patient': $role_id = 3; break; // Bệnh nhân
    }
    if ($role_id > 0) {
        $where_conditions[] = "u.role_id = ?";
        $params[] = $role_id;
    }
}

if (!empty($search)) {
    $where_conditions[] = "(u.username LIKE ? OR u.email LIKE ? OR ui.full_name LIKE ? OR ui.phone LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Count total records
$count_sql = "SELECT COUNT(*) as total FROM users u 
              LEFT JOIN users_info ui ON u.user_id = ui.user_id 
              $where_clause";

try {
    if (!empty($params)) {
        $count_stmt = $conn->prepare($count_sql);
        if ($count_stmt) {
            $count_stmt->bind_param(str_repeat('s', count($params)), ...$params);
            $count_stmt->execute();
            $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
        } else {
            $total_records = 0;
        }
    } else {
        $result = $conn->query($count_sql);
        $total_records = $result ? $result->fetch_assoc()['total'] : 0;
    }
} catch (Exception $e) {
    $total_records = 0;
}

$total_pages = ceil($total_records / $limit);

// Get users data - Sử dụng CASE để xử lý role_name
$sql = "SELECT u.*, ui.full_name, ui.phone, ui.date_of_birth,
               r.role_name
        FROM users u
        LEFT JOIN users_info ui ON u.user_id = ui.user_id
        LEFT JOIN roles r ON u.role_id = r.role_id
        $where_clause
        ORDER BY u.created_at DESC
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;

try {
    $stmt = $conn->prepare($sql);
    if ($stmt && !empty($params)) {
        $stmt->bind_param(str_repeat('s', count($params) - 2) . 'ii', ...$params);
        $stmt->execute();
        $users = $stmt->get_result();
    } elseif ($stmt) {
        $stmt->execute();
        $users = $stmt->get_result();
    } else {
        $users = false;
    }
} catch (Exception $e) {
    $users = false;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý người dùng - MediSync Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
<link href="assets/css/sidebar.css" rel="stylesheet">
<link href="assets/css/header.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/headeradmin.php'; ?>
    <?php include 'includes/sidebaradmin.php'; ?>

    <main class="main-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Quản lý người dùng</h1>
                    <p class="mb-0 text-muted">Quản lý tất cả người dùng trong hệ thống</p>
                </div>
                <div>
                    <a href="user-add.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Thêm người dùng
                    </a>
                </div>
            </div>

            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php
                    switch($success) {
                        case 'added': echo 'Thêm người dùng thành công!'; break;
                        case 'updated': echo 'Cập nhật người dùng thành công!'; break;
                        case 'deleted': echo 'Xóa người dùng thành công!'; break;
                        default: echo 'Thao tác thành công!';
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php
                    switch($error) {
                        case 'cannot_delete_self': echo 'Không thể xóa tài khoản của chính mình!'; break;
                        case 'delete_failed': echo 'Xóa người dùng thất bại!'; break;
                        default: echo 'Có lỗi xảy ra!';
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filters & Search -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Tìm kiếm</label>
                            <input type="text" class="form-control" name="search" 
                                   value="<?= htmlspecialchars($search) ?>" 
                                   placeholder="Tìm theo tên, email, số điện thoại...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Vai trò</label>
                            <select class="form-select" name="role">
                                <option value="all" <?= $role_filter == 'all' ? 'selected' : '' ?>>Tất cả</option>
                                <option value="admin" <?= $role_filter == 'admin' ? 'selected' : '' ?>>Quản trị viên</option>
                                <option value="doctor" <?= $role_filter == 'doctor' ? 'selected' : '' ?>>Bác sĩ</option>
                                <option value="patient" <?= $role_filter == 'patient' ? 'selected' : '' ?>>Bệnh nhân</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Tìm kiếm
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <a href="users.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-undo me-2"></i>Đặt lại
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="m-0 font-weight-bold text-primary">
                                Danh sách người dùng 
                                <span class="badge bg-primary ms-2"><?= number_format($total_records) ?></span>
                            </h6>
                        </div>
                        <div class="col-auto">
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" 
                                        data-bs-toggle="dropdown">
                                    <i class="fas fa-download me-2"></i>Xuất dữ liệu
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#"><i class="fas fa-file-excel me-2"></i>Excel</a></li>
                                    <li><a class="dropdown-item" href="#"><i class="fas fa-file-pdf me-2"></i>PDF</a></li>
                                    <li><a class="dropdown-item" href="#"><i class="fas fa-file-csv me-2"></i>CSV</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="15%">Thông tin</th>
                                    <th width="20%">Liên hệ</th>
                                    <th width="15%">Vai trò</th>
                                    <th width="15%">Trạng thái</th>
                                    <th width="15%">Ngày tạo</th>
                                    <th width="15%">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($users && $users->num_rows > 0): ?>
                                    <?php $i = $offset + 1; ?>
                                    <?php while ($user = $users->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $i++ ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm me-3">
                                                        <div class="avatar-title bg-soft-primary text-primary rounded-circle">
                                                            <?= strtoupper(substr($user['full_name'] ?? $user['username'], 0, 1)) ?>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0"><?= htmlspecialchars($user['full_name'] ?? $user['username']) ?></h6>
                                                        <small class="text-muted">@<?= htmlspecialchars($user['username']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <i class="fas fa-envelope me-2 text-muted"></i>
                                                    <?= htmlspecialchars($user['email']) ?>
                                                </div>
                                                <?php if ($user['phone']): ?>
                                                    <div class="mt-1">
                                                        <i class="fas fa-phone me-2 text-muted"></i>
                                                        <?= htmlspecialchars($user['phone']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $role_name = ucfirst($user['role_name']);
                                                $role_color = $role_colors[$role_name] ?? 'secondary';
                                                $display_name = $role_display_names[$role_name] ?? $role_name;
                                                ?>
                                                <span class="badge bg-<?= $role_color ?>"><?= $display_name ?></span>
                                            </td>
                                            <td>
                                                <?php if ($user['status'] == 'active'): ?>
                                                    <span class="badge bg-success">Hoạt động</span>
                                                <?php elseif ($user['status'] == 'inactive'): ?>
                                                    <span class="badge bg-warning">Không hoạt động</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Bị khóa</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="user-edit.php?id=<?= $user['user_id'] ?>" 
                                                       class="btn btn-outline-primary" title="Chỉnh sửa">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="user-view.php?id=<?= $user['user_id'] ?>" 
                                                       class="btn btn-outline-info" title="Xem chi tiết">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                                        <a href="users.php?action=delete&id=<?= $user['user_id'] ?>" 
                                                           class="btn btn-outline-danger" title="Xóa"
                                                           onclick="return confirm('Bạn có chắc chắn muốn xóa người dùng này?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fas fa-users fa-2x mb-2 d-block"></i>
                                            Không tìm thấy người dùng nào
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="card-footer bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">
                                    Hiển thị <?= $offset + 1 ?> - <?= min($offset + $limit, $total_records) ?> 
                                    trong tổng số <?= $total_records ?> người dùng
                                </small>
                            </div>
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page - 1 ?>&role=<?= $role_filter ?>&search=<?= urlencode($search) ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&role=<?= $role_filter ?>&search=<?= urlencode($search) ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page + 1 ?>&role=<?= $role_filter ?>&search=<?= urlencode($search) ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/notifications.js"></script>
    <script src="assets/js/admin.js"></script>
</body>
</html> 