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

// Xử lý thêm/sửa danh mục
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $icon = trim($_POST['icon']);
    $display_order = (int)$_POST['display_order'];
    $is_active = $_POST['is_active'] == 'active' ? 1 : 0;
    $category_id = $_POST['category_id'] ?? null;
    
    // Tạo slug từ tên
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    $slug = trim($slug, '-');
    
    if (!$error) {
        try {
            if ($category_id) {
                // Cập nhật danh mục
                $stmt = $conn->prepare("UPDATE service_categories SET name = ?, slug = ?, description = ?, icon = ?, display_order = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("ssssiii", $name, $slug, $description, $icon, $display_order, $is_active, $category_id);
                $message = 'Cập nhật danh mục thành công!';
            } else {
                // Thêm danh mục mới
                $stmt = $conn->prepare("INSERT INTO service_categories (name, slug, description, icon, display_order, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("ssssii", $name, $slug, $description, $icon, $display_order, $is_active);
                $message = 'Thêm danh mục thành công!';
            }
            
            if ($stmt->execute()) {
                $action = 'list';
            } else {
                $error = 'Có lỗi xảy ra khi lưu danh mục';
            }
        } catch (Exception $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}

// Xử lý xóa danh mục
if ($action === 'delete' && isset($_GET['id'])) {
    $category_id = (int)$_GET['id'];
    $redirect_page = $_GET['page'] ?? 1;
    $redirect_search = $_GET['search'] ?? '';
    $redirect_status = $_GET['status'] ?? '';
    
    try {
        // Kiểm tra xem có dịch vụ nào thuộc danh mục này không
        $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM services WHERE category_id = ? AND is_active = 1");
        $check_stmt->bind_param("i", $category_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $service_count = $result->fetch_assoc()['count'];
        
        if ($service_count > 0) {
            $error = 'Không thể xóa danh mục này vì còn có ' . $service_count . ' dịch vụ đang sử dụng';
            $action = 'list';
        } else {
            $stmt = $conn->prepare("UPDATE service_categories SET is_active = 0, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $category_id);
            if ($stmt->execute()) {
                $redirect_url = "?page=" . $redirect_page . 
                               "&search=" . urlencode($redirect_search) . 
                               "&status=" . urlencode($redirect_status) . 
                               "&message=" . urlencode('Xóa danh mục thành công!');
                header('Location: ' . $redirect_url);
                exit;
            } else {
                $error = 'Không thể xóa danh mục';
            }
        }
    } catch (Exception $e) {
        $error = 'Lỗi: ' . $e->getMessage();
    }
    $action = 'list';
}

// Lấy thông tin danh mục để sửa
$category = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $category_id = (int)$_GET['id'];
    $result = $conn->query("SELECT * FROM service_categories WHERE id = $category_id");
    $category = $result->fetch_assoc();
    if (!$category) {
        $action = 'list';
        $error = 'Không tìm thấy danh mục';
    }
}

// Lấy danh sách danh mục với pagination
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where_conditions = [];
$params = [];
$types = '';

if ($search) {
    $where_conditions[] = "(name LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if ($status_filter) {
    if ($status_filter === 'active') {
        $where_conditions[] = "is_active = ?";
        $params[] = 1;
    } else {
        $where_conditions[] = "is_active = ?";
        $params[] = 0;
    }
    $types .= 'i';
} else {
    // Mặc định hiển thị tất cả
    $where_conditions[] = "1=1";
}

$where_clause = empty($where_conditions) ? '1=1' : implode(' AND ', $where_conditions);

// Đếm tổng số danh mục
$count_sql = "SELECT COUNT(*) as total FROM service_categories WHERE $where_clause";
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

$total_categories = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_categories / $per_page);

// Lấy danh mục cho trang hiện tại
$sql = "SELECT sc.*, 
               (SELECT COUNT(*) FROM services s WHERE s.category_id = sc.id AND s.is_active = 1) as service_count 
        FROM service_categories sc 
        WHERE $where_clause 
        ORDER BY sc.display_order, sc.created_at DESC
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

$categories = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý danh mục dịch vụ - MediSync Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
    <link href="assets/css/sidebar.css" rel="stylesheet">
    <link href="assets/css/header.css" rel="stylesheet">
    <style>
        .icon-preview {
            font-size: 2rem;
            color: #007bff;
            margin: 10px;
        }
        
        .icon-selector {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            padding: 10px;
            border-radius: 5px;
        }
        
        .icon-option {
            padding: 8px;
            cursor: pointer;
            border: 2px solid transparent;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .icon-option:hover {
            background-color: #f8f9fa;
            border-color: #007bff;
        }
        
        .icon-option.selected {
            background-color: #007bff;
            color: white;
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
                        <i class="fas fa-tags me-2"></i>Quản lý danh mục dịch vụ
                    </h1>
                    <p class="mb-0 text-muted">Quản lý danh mục phân loại dịch vụ y tế</p>
                </div>
                <?php if ($action === 'list'): ?>
                <a href="?action=add" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Thêm danh mục
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
                            <div class="col-md-6">
                                <label class="form-label">Tìm kiếm</label>
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Tên danh mục..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-4">
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

                <!-- Categories List -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-list me-2"></i>Danh sách danh mục
                        </h6>
                        <small class="text-muted">
                            Hiển thị <?= ($offset + 1) ?>-<?= min($offset + $per_page, $total_categories) ?> 
                            trong tổng số <?= $total_categories ?> danh mục
                        </small>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0">Icon</th>
                                        <th class="border-0">Tên danh mục</th>
                                        <th class="border-0">Mô tả</th>
                                        <th class="border-0">Số dịch vụ</th>
                                        <th class="border-0">Thứ tự</th>
                                        <th class="border-0">Trạng thái</th>
                                        <th class="border-0">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($categories && $categories->num_rows > 0): ?>
                                        <?php while ($cat = $categories->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <i class="<?= htmlspecialchars($cat['icon']) ?> fa-2x text-primary"></i>
                                                </td>
                                                <td>
                                                    <div>
                                                        <h6 class="mb-0"><?= htmlspecialchars($cat['name']) ?></h6>
                                                        <small class="text-muted"><?= htmlspecialchars($cat['slug']) ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="text-muted"><?= htmlspecialchars(substr($cat['description'] ?? '', 0, 80)) ?><?= strlen($cat['description'] ?? '') > 80 ? '...' : '' ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?= $cat['service_count'] ?> dịch vụ</span>
                                                </td>
                                                <td><?= $cat['display_order'] ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $cat['is_active'] == 1 ? 'success' : 'secondary' ?>">
                                                        <?= $cat['is_active'] == 1 ? 'Hoạt động' : 'Không hoạt động' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?action=edit&id=<?= $cat['id'] ?>" 
                                                           class="btn btn-outline-primary" title="Sửa">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button class="btn btn-outline-danger" 
                                                                onclick="deleteCategory(<?= $cat['id'] ?>, <?= $page ?>)" title="Xóa">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="fas fa-tags fa-2x mb-2 d-block"></i>
                                                Không có danh mục nào
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
                                    <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>">
                                            <i class="fas fa-chevron-left"></i> Trước
                                        </a>
                                    </li>
                                    <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link"><i class="fas fa-chevron-left"></i> Trước</span>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>">
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
                            <?= $action === 'add' ? 'Thêm danh mục mới' : 'Sửa danh mục' ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php if ($category): ?>
                                <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label">Tên danh mục <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" required
                                               value="<?= htmlspecialchars($category['name'] ?? '') ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Mô tả</label>
                                        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($category['description'] ?? '') ?></textarea>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Thứ tự hiển thị</label>
                                                <input type="number" name="display_order" class="form-control" min="0"
                                                       value="<?= $category['display_order'] ?? 0 ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Trạng thái</label>
                                                <select name="is_active" class="form-select">
                                                    <option value="active" <?= ($category['is_active'] ?? 1) == 1 ? 'selected' : '' ?>>Hoạt động</option>
                                                    <option value="inactive" <?= ($category['is_active'] ?? 1) == 0 ? 'selected' : '' ?>>Không hoạt động</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Icon <span class="text-danger">*</span></label>
                                        <input type="text" name="icon" id="icon_input" class="form-control" required
                                               value="<?= htmlspecialchars($category['icon'] ?? 'fas fa-stethoscope') ?>"
                                               placeholder="fas fa-stethoscope">
                                        
                                        <div class="text-center mt-2">
                                            <i id="icon_preview" class="<?= htmlspecialchars($category['icon'] ?? 'fas fa-stethoscope') ?> icon-preview"></i>
                                        </div>
                                        
                                        <small class="text-muted">Chọn icon từ danh sách bên dưới hoặc nhập class FontAwesome:</small>
                                        
                                        <div class="icon-selector">
                                            <?php 
                                            $icons = [
                                                'fas fa-stethoscope', 'fas fa-heartbeat', 'fas fa-brain', 
                                                'fas fa-bone', 'fas fa-eye', 'fas fa-tooth', 
                                                'fas fa-ambulance', 'fas fa-prescription-bottle-alt', 'fas fa-syringe',
                                                'fas fa-microscope', 'fas fa-x-ray', 'fas fa-dna',
                                                'fas fa-lungs', 'fas fa-kidney', 'fas fa-baby'
                                            ];
                                            foreach ($icons as $icon): ?>
                                                <div class="icon-option" data-icon="<?= $icon ?>">
                                                    <i class="<?= $icon ?>"></i>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    <?= $action === 'add' ? 'Thêm danh mục' : 'Cập nhật' ?>
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
        function deleteCategory(categoryId, currentPage) {
            if (confirm('Bạn có chắc chắn muốn xóa danh mục này?')) {
                const urlParams = new URLSearchParams(window.location.search);
                const search = urlParams.get('search') || '';
                const status = urlParams.get('status') || '';
                
                window.location.href = '?action=delete&id=' + categoryId + 
                                     '&page=' + currentPage +
                                     '&search=' + encodeURIComponent(search) +
                                     '&status=' + encodeURIComponent(status);
            }
        }

        // Icon preview functionality
        document.addEventListener('DOMContentLoaded', function() {
            const iconInput = document.getElementById('icon_input');
            const iconPreview = document.getElementById('icon_preview');
            const iconOptions = document.querySelectorAll('.icon-option');
            
            // Update preview when input changes
            iconInput.addEventListener('input', function() {
                const iconClass = this.value.trim();
                iconPreview.className = iconClass + ' icon-preview';
                
                // Update selected icon option
                iconOptions.forEach(option => {
                    if (option.dataset.icon === iconClass) {
                        option.classList.add('selected');
                    } else {
                        option.classList.remove('selected');
                    }
                });
            });
            
            // Icon selection
            iconOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const iconClass = this.dataset.icon;
                    iconInput.value = iconClass;
                    iconPreview.className = iconClass + ' icon-preview';
                    
                    // Update selected state
                    iconOptions.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                });
                
                // Set initial selected state
                if (option.dataset.icon === iconInput.value) {
                    option.classList.add('selected');
                }
            });
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const name = document.querySelector('input[name="name"]').value.trim();
            const icon = document.querySelector('input[name="icon"]').value.trim();
            
            if (!name) {
                alert('Vui lòng nhập tên danh mục!');
                e.preventDefault();
                return;
            }
            
            if (!icon) {
                alert('Vui lòng chọn icon!');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html> 