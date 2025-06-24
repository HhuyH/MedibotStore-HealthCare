<?php
session_start();
include '../includes/db.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../login.php');
    exit;
}

// Xử lý các actions
$action = $_GET['action'] ?? 'list';
$category_filter = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Xử lý delete service
if ($action == 'delete' && isset($_GET['id'])) {
    $service_id = (int)$_GET['id'];
    
    // Kiểm tra xem dịch vụ có đang được sử dụng không
    $check_sql = "SELECT COUNT(*) as count FROM appointments WHERE service_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $service_id);
    $check_stmt->execute();
    $appointments_count = $check_stmt->get_result()->fetch_assoc()['count'];
    
    if ($appointments_count > 0) {
        header('Location: services.php?error=service_in_use');
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM services WHERE service_id = ?");
    $stmt->bind_param("i", $service_id);
    
    if ($stmt->execute()) {
        header('Location: services.php?success=deleted');
    } else {
        header('Location: services.php?error=delete_failed');
    }
    exit;
}

// Xử lý toggle status
if ($action == 'toggle_status' && isset($_GET['id'])) {
    $service_id = (int)$_GET['id'];
    
    $stmt = $conn->prepare("UPDATE services SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END WHERE service_id = ?");
    $stmt->bind_param("i", $service_id);
    
    if ($stmt->execute()) {
        header('Location: services.php?success=status_updated');
    } else {
        header('Location: services.php?error=update_failed');
    }
    exit;
}

// Xây dựng query
$where_conditions = [];
$params = [];

if ($category_filter != 'all') {
    $where_conditions[] = "s.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(s.name LIKE ? OR s.description LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param]);
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

// Count total
$count_sql = "SELECT COUNT(*) as total FROM services s $where_clause";

if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param(str_repeat('s', count($params)), ...$params);
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_records = $conn->query($count_sql)->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $limit);

// Get services
$sql = "SELECT s.*, c.name as category_name
        FROM services s
        LEFT JOIN categories c ON s.category_id = c.category_id
        $where_clause
        ORDER BY s.created_at DESC
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param(str_repeat('s', count($params) - 2) . 'ii', ...$params);
}
$stmt->execute();
$services = $stmt->get_result();

// Lấy danh mục
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

// Thống kê
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
    AVG(price) as avg_price
FROM services";
$stats = $conn->query($stats_sql)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý dịch vụ - QickMed Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Quản lý dịch vụ</h1>
                    <p class="mb-0 text-muted">Quản lý tất cả dịch vụ y tế</p>
                </div>
                <div>
                    <a href="categories.php" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-tags me-2"></i>Quản lý danh mục
                    </a>
                    <a href="service-add.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Thêm dịch vụ
                    </a>
                </div>
            </div>

            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php
                    switch($success) {
                        case 'added': echo 'Thêm dịch vụ thành công!'; break;
                        case 'updated': echo 'Cập nhật dịch vụ thành công!'; break;
                        case 'deleted': echo 'Xóa dịch vụ thành công!'; break;
                        case 'status_updated': echo 'Cập nhật trạng thái thành công!'; break;
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
                        case 'service_in_use': echo 'Không thể xóa dịch vụ đang được sử dụng!'; break;
                        case 'delete_failed': echo 'Xóa dịch vụ thất bại!'; break;
                        case 'update_failed': echo 'Cập nhật thất bại!'; break;
                        default: echo 'Có lỗi xảy ra!';
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-primary mb-2">
                                <i class="fas fa-medical-kit fa-2x"></i>
                            </div>
                            <h5 class="mb-0"><?= number_format($stats['total']) ?></h5>
                            <small class="text-muted">Tổng dịch vụ</small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-success mb-2">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                            <h5 class="mb-0"><?= number_format($stats['active']) ?></h5>
                            <small class="text-muted">Đang hoạt động</small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-warning mb-2">
                                <i class="fas fa-pause-circle fa-2x"></i>
                            </div>
                            <h5 class="mb-0"><?= number_format($stats['inactive']) ?></h5>
                            <small class="text-muted">Tạm dừng</small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-info mb-2">
                                <i class="fas fa-dollar-sign fa-2x"></i>
                            </div>
                            <h5 class="mb-0"><?= number_format($stats['avg_price']) ?>₫</h5>
                            <small class="text-muted">Giá trung bình</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Tìm kiếm</label>
                            <input type="text" class="form-control" name="search" 
                                   value="<?= htmlspecialchars($search) ?>" 
                                   placeholder="Tên dịch vụ, mô tả...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Danh mục</label>
                            <select class="form-select" name="category">
                                <option value="all" <?= $category_filter == 'all' ? 'selected' : '' ?>>Tất cả</option>
                                <?php if ($categories && $categories->num_rows > 0): ?>
                                    <?php while ($category = $categories->fetch_assoc()): ?>
                                        <option value="<?= $category['category_id'] ?>" 
                                                <?= $category_filter == $category['category_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
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
                                <a href="services.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-undo me-2"></i>Đặt lại
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Services Grid -->
            <div class="row">
                <?php if ($services && $services->num_rows > 0): ?>
                    <?php while ($service = $services->fetch_assoc()): ?>
                        <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                            <div class="card border-0 shadow-sm h-100">
                                <?php if ($service['image']): ?>
                                    <img src="../uploads/services/<?= htmlspecialchars($service['image']) ?>" 
                                         class="card-img-top" style="height: 200px; object-fit: cover;" 
                                         alt="<?= htmlspecialchars($service['name']) ?>">
                                <?php else: ?>
                                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                                         style="height: 200px;">
                                        <i class="fas fa-medical-kit fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body d-flex flex-column">
                                    <div class="mb-2">
                                        <?php if ($service['category_name']): ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($service['category_name']) ?></span>
                                        <?php endif; ?>
                                        <span class="badge bg-<?= $service['status'] == 'active' ? 'success' : 'warning' ?>">
                                            <?= $service['status'] == 'active' ? 'Hoạt động' : 'Tạm dừng' ?>
                                        </span>
                                    </div>
                                    
                                    <h5 class="card-title"><?= htmlspecialchars($service['name']) ?></h5>
                                    
                                    <p class="card-text text-muted small flex-grow-1">
                                        <?= htmlspecialchars(substr($service['description'], 0, 100)) ?>
                                        <?= strlen($service['description']) > 100 ? '...' : '' ?>
                                    </p>
                                    
                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <?php if ($service['price']): ?>
                                                    <h5 class="text-primary mb-0"><?= number_format($service['price']) ?>₫</h5>
                                                <?php else: ?>
                                                    <span class="text-muted">Liên hệ</span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted">
                                                <?= date('d/m/Y', strtotime($service['created_at'])) ?>
                                            </small>
                                        </div>
                                        
                                        <div class="btn-group w-100">
                                            <a href="service-edit.php?id=<?= $service['service_id'] ?>" 
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-edit me-1"></i>Sửa
                                            </a>
                                            <a href="service-view.php?id=<?= $service['service_id'] ?>" 
                                               class="btn btn-outline-info btn-sm">
                                                <i class="fas fa-eye me-1"></i>Xem
                                            </a>
                                            <a href="?action=toggle_status&id=<?= $service['service_id'] ?>" 
                                               class="btn btn-outline-warning btn-sm"
                                               data-bs-toggle="tooltip" 
                                               title="<?= $service['status'] == 'active' ? 'Tạm dừng' : 'Kích hoạt' ?>">
                                                <i class="fas fa-<?= $service['status'] == 'active' ? 'pause' : 'play' ?>"></i>
                                            </a>
                                            <a href="?action=delete&id=<?= $service['service_id'] ?>" 
                                               class="btn btn-outline-danger btn-sm btn-delete"
                                               data-message="Bạn có chắc chắn muốn xóa dịch vụ này?">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="fas fa-medical-kit fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">Không có dịch vụ nào</h4>
                            <p class="text-muted">Hãy thêm dịch vụ đầu tiên để bắt đầu</p>
                            <a href="service-add.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Thêm dịch vụ
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <small class="text-muted">
                                            Hiển thị <?= min($offset + 1, $total_records) ?> - <?= min($offset + $limit, $total_records) ?> 
                                            trong tổng số <?= number_format($total_records) ?> dịch vụ
                                        </small>
                                    </div>
                                    <div class="col-auto">
                                        <nav>
                                            <ul class="pagination pagination-sm mb-0">
                                                <?php if ($page > 1): ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="?page=<?= $page - 1 ?>&category=<?= $category_filter ?>&search=<?= urlencode($search) ?>">
                                                            <i class="fas fa-chevron-left"></i>
                                                        </a>
                                                    </li>
                                                <?php endif; ?>

                                                <?php
                                                $start_page = max(1, $page - 2);
                                                $end_page = min($total_pages, $page + 2);
                                                ?>

                                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                        <a class="page-link" href="?page=<?= $i ?>&category=<?= $category_filter ?>&search=<?= urlencode($search) ?>">
                                                            <?= $i ?>
                                                        </a>
                                                    </li>
                                                <?php endfor; ?>

                                                <?php if ($page < $total_pages): ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="?page=<?= $page + 1 ?>&category=<?= $category_filter ?>&search=<?= urlencode($search) ?>">
                                                            <i class="fas fa-chevron-right"></i>
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
</body>
</html> 