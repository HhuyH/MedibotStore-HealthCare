<?php
session_start();
include '../includes/db.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../login.php');
    exit;
}

// Xử lý các action
$action = $_GET['action'] ?? 'list';
$message = $_GET['message'] ?? '';
$error = '';

// Xử lý thêm/sửa dịch vụ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $short_description = trim($_POST['short_description']);
    $full_description = trim($_POST['full_description']);
    $category_id = (int)$_POST['category_id'];
    $price_from = !empty($_POST['price_from']) ? (float)$_POST['price_from'] : null;
    $price_to = !empty($_POST['price_to']) ? (float)$_POST['price_to'] : null;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_emergency = isset($_POST['is_emergency']) ? 1 : 0;
    $is_active = $_POST['is_active'] == 'active' ? 1 : 0;
    $display_order = (int)$_POST['display_order'];
    $service_id = $_POST['service_id'] ?? null;
    
    // Xử lý features
    $features = [];
    if (isset($_POST['features']) && is_array($_POST['features'])) {
        foreach ($_POST['features'] as $feature) {
            if (!empty(trim($feature))) {
                $features[] = trim($feature);
            }
        }
    }
    
    // Tạo slug từ tên
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    
    if (!$error) {
        try {
            $conn->begin_transaction();
            
            if ($service_id) {
                // Cập nhật dịch vụ
                $stmt = $conn->prepare("UPDATE services SET name = ?, slug = ?, short_description = ?, full_description = ?, category_id = ?, price_from = ?, price_to = ?, is_featured = ?, is_emergency = ?, is_active = ?, display_order = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("ssssiddiiiii", $name, $slug, $short_description, $full_description, $category_id, $price_from, $price_to, $is_featured, $is_emergency, $is_active, $display_order, $service_id);
                
                if ($stmt->execute()) {
                    // Xóa features cũ
                    $conn->query("DELETE FROM service_features WHERE service_id = $service_id");
                    $message = 'Cập nhật dịch vụ thành công!';
                } else {
                    throw new Exception('Không thể cập nhật dịch vụ');
                }
            } else {
                // Thêm dịch vụ mới
                $stmt = $conn->prepare("INSERT INTO services (name, slug, short_description, full_description, category_id, price_from, price_to, is_featured, is_emergency, is_active, display_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("sssssddiiiii", $name, $slug, $short_description, $full_description, $category_id, $price_from, $price_to, $is_featured, $is_emergency, $is_active, $display_order);
                
                if ($stmt->execute()) {
                    $service_id = $conn->insert_id;
                    $message = 'Thêm dịch vụ thành công!';
                } else {
                    throw new Exception('Không thể thêm dịch vụ');
                }
            }
            
            // Thêm features mới
            if (!empty($features)) {
                $stmt_feature = $conn->prepare("INSERT INTO service_features (service_id, feature_name, display_order) VALUES (?, ?, ?)");
                foreach ($features as $index => $feature) {
                    $stmt_feature->bind_param("isi", $service_id, $feature, $index);
                    $stmt_feature->execute();
                }
            }
            
            $conn->commit();
            $action = 'list';
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}

// Xử lý xóa dịch vụ
if ($action === 'delete' && isset($_GET['id'])) {
    $service_id = (int)$_GET['id'];
    $redirect_page = $_GET['page'] ?? 1;
    $redirect_search = $_GET['search'] ?? '';
    $redirect_category = $_GET['category'] ?? '';
    $redirect_status = $_GET['status'] ?? '';
    
    try {
        $stmt = $conn->prepare("UPDATE services SET is_active = 0, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $service_id);
        if ($stmt->execute()) {
            $redirect_url = "?page=" . $redirect_page . 
                           "&search=" . urlencode($redirect_search) . 
                           "&category=" . urlencode($redirect_category) . 
                           "&status=" . urlencode($redirect_status) . 
                           "&message=" . urlencode('Xóa dịch vụ thành công!');
            header('Location: ' . $redirect_url);
            exit;
        } else {
            $error = 'Không thể xóa dịch vụ';
        }
    } catch (Exception $e) {
        $error = 'Lỗi: ' . $e->getMessage();
    }
    $action = 'list';
}

// Lấy danh sách danh mục
$categories_result = $conn->query("SELECT * FROM service_categories WHERE is_active = 1 ORDER BY display_order, name");
$categories = [];
if ($categories_result) {
    while ($cat = $categories_result->fetch_assoc()) {
        $categories[] = $cat;
    }
}

// Lấy thông tin dịch vụ để sửa
$service = null;
$service_features = [];
if ($action === 'edit' && isset($_GET['id'])) {
    $service_id = (int)$_GET['id'];
    $result = $conn->query("SELECT * FROM services WHERE id = $service_id");
    $service = $result->fetch_assoc();
    if (!$service) {
        $action = 'list';
        $error = 'Không tìm thấy dịch vụ';
    } else {
        // Lấy features của dịch vụ
        $features_result = $conn->query("SELECT feature_name FROM service_features WHERE service_id = $service_id ORDER BY display_order");
        while ($feature = $features_result->fetch_assoc()) {
            $service_features[] = $feature['feature_name'];
        }
    }
}

// Lấy danh sách dịch vụ với pagination
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where_conditions = [];
$params = [];
$types = '';

if ($search) {
    $where_conditions[] = "(s.name LIKE ? OR s.short_description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if ($category_filter) {
    $where_conditions[] = "s.category_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

if ($status_filter) {
    if ($status_filter === 'active') {
        $where_conditions[] = "s.is_active = ?";
        $params[] = 1;
    } else {
        $where_conditions[] = "s.is_active = ?";
        $params[] = 0;
    }
    $types .= 'i';
} else {
    // Mặc định hiển thị tất cả
    $where_conditions[] = "1=1";
}

$where_clause = empty($where_conditions) ? '1=1' : implode(' AND ', $where_conditions);

// Đếm tổng số dịch vụ
$count_sql = "SELECT COUNT(*) as total 
              FROM services s 
              LEFT JOIN service_categories sc ON s.category_id = sc.id 
              WHERE $where_clause";

$count_stmt = $conn->prepare($count_sql);
if (!$count_stmt) {
    die('Lỗi SQL prepare (count): ' . $conn->error);
}

if ($params) {
    $count_stmt->bind_param($types, ...$params);
}

if (!$count_stmt->execute()) {
    die('Lỗi SQL execute (count): ' . $count_stmt->error);
}

$total_services = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_services / $per_page);

// Lấy dịch vụ cho trang hiện tại
$sql = "SELECT s.*, sc.name as category_name 
        FROM services s 
        LEFT JOIN service_categories sc ON s.category_id = sc.id 
        WHERE $where_clause 
        ORDER BY s.display_order, s.created_at DESC
        LIMIT $per_page OFFSET $offset";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Lỗi SQL prepare: ' . $conn->error . '<br>SQL: ' . $sql);
}

if ($params) {
    $stmt->bind_param($types, ...$params);
}

if (!$stmt->execute()) {
    die('Lỗi SQL execute: ' . $stmt->error);
}

$services = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý dịch vụ - MediSync Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
    <link href="assets/css/sidebar.css" rel="stylesheet">
    <link href="assets/css/header.css" rel="stylesheet">
    <style>
        .feature-input {
            margin-bottom: 10px;
        }
        
        .feature-group {
            position: relative;
        }
        
        .remove-feature {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #dc3545;
        }
        
        .price-range-inputs {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .price-separator {
            font-weight: bold;
            color: #6c757d;
        }
        
        .service-badges {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .pagination .page-link {
            color: #007bff;
            border: 1px solid #dee2e6;
            margin: 0 2px;
            border-radius: 6px;
        }
        
        .pagination .page-item.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
        }
        
        .pagination .page-link:hover {
            color: #0056b3;
            background-color: #e9ecef;
            border-color: #dee2e6;
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
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-stethoscope me-2"></i>Quản lý dịch vụ y tế
                    </h1>
                    <p class="mb-0 text-muted">Quản lý danh sách dịch vụ y tế và chăm sóc sức khỏe</p>
                </div>
                <?php if ($action === 'list'): ?>
                <a href="?action=add" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Thêm dịch vụ
                </a>
                <?php else: ?>
                <a href="?" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Quay lại
                </a>
                <?php endif; ?>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($action === 'list'): ?>
                <!-- Filters -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Tìm kiếm</label>
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Tên dịch vụ..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Danh mục</label>
                                <select name="category" class="form-select">
                                    <option value="">Tất cả danh mục</option>
                                    <?php if (!empty($categories)): ?>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" 
                                                    <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Trạng thái</label>
                                <select name="status" class="form-select">
                                    <option value="">Tất cả trạng thái</option>
                                    <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Hoạt động</option>
                                    <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Không hoạt động</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-2"></i>Tìm kiếm
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Services List -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-list me-2"></i>Danh sách dịch vụ
                        </h6>
                        <small class="text-muted">
                            Hiển thị <?= ($offset + 1) ?>-<?= min($offset + $per_page, $total_services) ?> 
                            trong tổng số <?= $total_services ?> dịch vụ
                        </small>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0">Tên dịch vụ</th>
                                        <th class="border-0">Danh mục</th>
                                        <th class="border-0">Giá</th>
                                        <th class="border-0">Trạng thái</th>
                                        <th class="border-0">Thứ tự</th>
                                        <th class="border-0">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($services && $services->num_rows > 0): ?>
                                        <?php while ($svc = $services->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <h6 class="mb-1"><?= htmlspecialchars($svc['name']) ?></h6>
                                                        <small class="text-muted"><?= htmlspecialchars(substr($svc['short_description'], 0, 80)) ?>...</small>
                                                        <div class="service-badges mt-1">
                                                            <?php if ($svc['is_featured']): ?>
                                                                <span class="badge bg-warning text-dark">Nổi bật</span>
                                                            <?php endif; ?>
                                                            <?php if ($svc['is_emergency']): ?>
                                                                <span class="badge bg-danger">Cấp cứu</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($svc['category_name'] ?? 'N/A') ?></td>
                                                <td>
                                                    <?php if ($svc['price_from'] !== null): ?>
                                                        <span class="fw-bold text-primary">
                                                            <?= number_format($svc['price_from']) ?>đ
                                                            <?php if ($svc['price_to'] !== null): ?>
                                                                - <?= number_format($svc['price_to']) ?>đ
                                                            <?php endif; ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">Liên hệ</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= $svc['is_active'] == 1 ? 'success' : 'secondary' ?>">
                                                        <?= $svc['is_active'] == 1 ? 'Hoạt động' : 'Không hoạt động' ?>
                                                    </span>
                                                </td>
                                                <td><?= $svc['display_order'] ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?action=edit&id=<?= $svc['id'] ?>" 
                                                           class="btn btn-outline-primary" title="Sửa">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button class="btn btn-outline-danger" 
                                                                onclick="deleteService(<?= $svc['id'] ?>, <?= $page ?>)" title="Xóa">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="fas fa-stethoscope fa-2x mb-2 d-block"></i>
                                                Không có dịch vụ nào
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <div class="card-footer bg-white">
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center mb-0">
                                    <!-- Previous Button -->
                                    <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category_filter) ?>&status=<?= urlencode($status_filter) ?>">
                                            <i class="fas fa-chevron-left"></i> Trước
                                        </a>
                                    </li>
                                    <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link"><i class="fas fa-chevron-left"></i> Trước</span>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <!-- Page Numbers -->
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    if ($start_page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=1&search=<?= urlencode($search) ?>&category=<?= urlencode($category_filter) ?>&status=<?= urlencode($status_filter) ?>">1</a>
                                        </li>
                                        <?php if ($start_page > 2): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category_filter) ?>&status=<?= urlencode($status_filter) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($end_page < $total_pages): ?>
                                        <?php if ($end_page < $total_pages - 1): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $total_pages ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category_filter) ?>&status=<?= urlencode($status_filter) ?>"><?= $total_pages ?></a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <!-- Next Button -->
                                    <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category_filter) ?>&status=<?= urlencode($status_filter) ?>">
                                            Tiếp <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                    <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">Tiếp <i class="fas fa-chevron-right"></i></span>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php else: ?>
                <!-- Add/Edit Form -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-<?= $action === 'add' ? 'plus' : 'edit' ?> me-2"></i>
                            <?= $action === 'add' ? 'Thêm dịch vụ mới' : 'Sửa dịch vụ' ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php if ($service): ?>
                                <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label">Tên dịch vụ <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" required
                                               value="<?= htmlspecialchars($service['name'] ?? '') ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Mô tả ngắn</label>
                                        <textarea name="short_description" class="form-control" rows="2"><?= htmlspecialchars($service['short_description'] ?? '') ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Mô tả chi tiết</label>
                                        <textarea name="full_description" class="form-control" rows="4"><?= htmlspecialchars($service['full_description'] ?? '') ?></textarea>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Danh mục</label>
                                                <select name="category_id" class="form-select">
                                                    <option value="">Chọn danh mục</option>
                                                    <?php foreach ($categories as $cat): ?>
                                                        <option value="<?= $cat['id'] ?>" 
                                                                <?= ($service['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($cat['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Thứ tự hiển thị</label>
                                                <input type="number" name="display_order" class="form-control" min="0"
                                                       value="<?= $service['display_order'] ?? 0 ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Khoảng giá</label>
                                        <div class="price-range-inputs">
                                            <input type="number" name="price_from" class="form-control" 
                                                   placeholder="Giá từ" min="0" step="1000"
                                                   value="<?= $service['price_from'] ?? '' ?>">
                                            <span class="price-separator">-</span>
                                            <input type="number" name="price_to" class="form-control" 
                                                   placeholder="Giá đến" min="0" step="1000"
                                                   value="<?= $service['price_to'] ?? '' ?>">
                                        </div>
                                        <small class="text-muted">Để trống nếu giá liên hệ</small>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Trạng thái</label>
                                        <select name="is_active" class="form-select">
                                            <option value="active" <?= ($service['is_active'] ?? 1) == 1 ? 'selected' : '' ?>>Hoạt động</option>
                                            <option value="inactive" <?= ($service['is_active'] ?? 1) == 0 ? 'selected' : '' ?>>Không hoạt động</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured"
                                                   <?= ($service['is_featured'] ?? 0) == 1 ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="is_featured">
                                                Dịch vụ nổi bật
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_emergency" id="is_emergency"
                                                   <?= ($service['is_emergency'] ?? 0) == 1 ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="is_emergency">
                                                Dịch vụ cấp cứu
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Tính năng dịch vụ</label>
                                        <div id="features-container">
                                            <?php if (!empty($service_features)): ?>
                                                <?php foreach ($service_features as $feature): ?>
                                                    <div class="feature-group">
                                                        <input type="text" name="features[]" class="form-control feature-input" 
                                                               value="<?= htmlspecialchars($feature) ?>" placeholder="Nhập tính năng">
                                                        <i class="fas fa-times remove-feature" onclick="removeFeature(this)"></i>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="feature-group">
                                                    <input type="text" name="features[]" class="form-control feature-input" placeholder="Nhập tính năng">
                                                    <i class="fas fa-times remove-feature" onclick="removeFeature(this)"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addFeature()">
                                            <i class="fas fa-plus me-1"></i>Thêm tính năng
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    <?= $action === 'add' ? 'Thêm dịch vụ' : 'Cập nhật' ?>
                                </button>
                                <a href="?" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Hủy
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
    <script>
        function deleteService(serviceId, currentPage) {
            if (confirm('Bạn có chắc chắn muốn xóa dịch vụ này?')) {
                const urlParams = new URLSearchParams(window.location.search);
                const search = urlParams.get('search') || '';
                const category = urlParams.get('category') || '';
                const status = urlParams.get('status') || '';
                
                window.location.href = '?action=delete&id=' + serviceId + 
                                     '&page=' + currentPage +
                                     '&search=' + encodeURIComponent(search) +
                                     '&category=' + encodeURIComponent(category) +
                                     '&status=' + encodeURIComponent(status);
            }
        }

        function addFeature() {
            const container = document.getElementById('features-container');
            const featureGroup = document.createElement('div');
            featureGroup.className = 'feature-group';
            featureGroup.innerHTML = `
                <input type="text" name="features[]" class="form-control feature-input" placeholder="Nhập tính năng">
                <i class="fas fa-times remove-feature" onclick="removeFeature(this)"></i>
            `;
            container.appendChild(featureGroup);
        }

        function removeFeature(element) {
            const container = document.getElementById('features-container');
            if (container.children.length > 1) {
                element.parentElement.remove();
            }
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const name = document.querySelector('input[name="name"]').value.trim();
            
            if (!name) {
                alert('Vui lòng nhập tên dịch vụ!');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html> 