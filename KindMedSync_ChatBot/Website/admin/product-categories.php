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
    $category_id = $_POST['category_id'] ?? null;
    
    // Validation
    if (empty($name)) {
        $error = 'Tên danh mục không được để trống';
    } else {
        // Kiểm tra trùng tên (trừ chính nó khi edit)
        $check_sql = "SELECT category_id FROM product_categories WHERE name = ?";
        if ($category_id) {
            $check_sql .= " AND category_id != ?";
        }
        
        $check_stmt = $conn->prepare($check_sql);
        if ($category_id) {
            $check_stmt->bind_param("si", $name, $category_id);
        } else {
            $check_stmt->bind_param("s", $name);
        }
        
        $check_stmt->execute();
        $existing = $check_stmt->get_result();
        
        if ($existing->num_rows > 0) {
            $error = 'Tên danh mục đã tồn tại';
        }
    }
    
    if (!$error) {
        try {
            if ($category_id) {
                // Cập nhật danh mục
                $stmt = $conn->prepare("UPDATE product_categories SET name = ?, description = ?, updated_at = NOW() WHERE category_id = ?");
                $stmt->bind_param("ssi", $name, $description, $category_id);
                $message = 'Cập nhật danh mục thành công!';
            } else {
                // Thêm danh mục mới
                $stmt = $conn->prepare("INSERT INTO product_categories (name, description, created_at) VALUES (?, ?, NOW())");
                $stmt->bind_param("ss", $name, $description);
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
    
    try {
        // Kiểm tra xem danh mục có sản phẩm không
        $check_products = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
        $check_products->bind_param("i", $category_id);
        $check_products->execute();
        $product_count = $check_products->get_result()->fetch_assoc()['count'];
        
        if ($product_count > 0) {
            $redirect_url = "?page=" . $redirect_page . 
                           "&search=" . urlencode($redirect_search) . 
                           "&error=" . urlencode("Không thể xóa danh mục có {$product_count} sản phẩm. Vui lòng chuyển sản phẩm sang danh mục khác trước.");
        } else {
            $stmt = $conn->prepare("DELETE FROM product_categories WHERE category_id = ?");
            $stmt->bind_param("i", $category_id);
            if ($stmt->execute()) {
                $redirect_url = "?page=" . $redirect_page . 
                               "&search=" . urlencode($redirect_search) . 
                               "&message=" . urlencode('Xóa danh mục thành công!');
            } else {
                $redirect_url = "?page=" . $redirect_page . 
                               "&search=" . urlencode($redirect_search) . 
                               "&error=" . urlencode('Không thể xóa danh mục');
            }
        }
        
        header('Location: ' . $redirect_url);
        exit;
    } catch (Exception $e) {
        $error = 'Lỗi: ' . $e->getMessage();
    }
    $action = 'list';
}

// Lấy thông tin danh mục để sửa
$category = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $category_id = (int)$_GET['id'];
    $result = $conn->query("SELECT * FROM product_categories WHERE category_id = $category_id");
    $category = $result->fetch_assoc();
    if (!$category) {
        $action = 'list';
        $error = 'Không tìm thấy danh mục';
    }
}

// Lấy danh sách danh mục với pagination
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10; // Số danh mục mỗi trang
$offset = ($page - 1) * $per_page;

$where_conditions = [];
$params = [];
$types = '';

if ($search) {
    $where_conditions[] = "(pc.name LIKE ? OR pc.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

$where_clause = empty($where_conditions) ? '1=1' : implode(' AND ', $where_conditions);

// Đếm tổng số danh mục để tính pagination
$count_sql = "SELECT COUNT(*) as total 
              FROM product_categories pc 
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

$total_categories = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_categories / $per_page);

// Lấy danh mục cho trang hiện tại với số lượng sản phẩm
$sql = "SELECT pc.*, 
               COUNT(p.product_id) as product_count
        FROM product_categories pc 
        LEFT JOIN products p ON pc.category_id = p.category_id AND p.is_active = 1
        WHERE $where_clause 
        GROUP BY pc.category_id
        ORDER BY pc.created_at DESC
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

// Lấy error từ URL nếu có
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý danh mục sản phẩm - MediSync Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
    <link href="assets/css/sidebar.css" rel="stylesheet">
    <link href="assets/css/header.css" rel="stylesheet">
    <style>
        .category-card {
            transition: all 0.3s ease;
            border: 1px solid #e3e6f0;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .category-stats {
            background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
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
        
        .pagination .page-item.disabled .page-link {
            color: #6c757d;
            background-color: #fff;
            border-color: #dee2e6;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(45deg, #5a6fd8 0%, #6a4190 100%);
            transform: translateY(-1px);
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
                        <i class="fas fa-tags me-2"></i>Quản lý danh mục sản phẩm
                    </h1>
                    <p class="mb-0 text-muted">Quản lý các danh mục sản phẩm trong hệ thống</p>
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
                <!-- Search -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Tìm kiếm danh mục</label>
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Nhập tên hoặc mô tả danh mục..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-4">
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
                                        <th class="border-0">ID</th>
                                        <th class="border-0">Tên danh mục</th>
                                        <th class="border-0">Mô tả</th>
                                        <th class="border-0">Số sản phẩm</th>
                                        <th class="border-0">Ngày tạo</th>
                                        <th class="border-0">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($categories && $categories->num_rows > 0): ?>
                                        <?php while ($cat = $categories->fetch_assoc()): ?>
                                            <tr>
                                                <td class="fw-bold text-primary">#<?= $cat['category_id'] ?></td>
                                                <td>
                                                    <div>
                                                        <h6 class="mb-0"><?= htmlspecialchars($cat['name']) ?></h6>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="text-muted">
                                                        <?= htmlspecialchars(substr($cat['description'] ?? '', 0, 60)) ?>
                                                        <?= strlen($cat['description'] ?? '') > 60 ? '...' : '' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= $cat['product_count'] > 0 ? 'success' : 'secondary' ?> fs-6">
                                                        <?= $cat['product_count'] ?> sản phẩm
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?= date('d/m/Y H:i', strtotime($cat['created_at'])) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?action=edit&id=<?= $cat['category_id'] ?>" 
                                                           class="btn btn-outline-primary" title="Sửa">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button class="btn btn-outline-danger" 
                                                                onclick="deleteCategory(<?= $cat['category_id'] ?>, <?= $page ?>, '<?= htmlspecialchars($cat['name']) ?>', <?= $cat['product_count'] ?>)" 
                                                                title="Xóa">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
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
                                    <!-- Previous Button -->
                                    <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
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
                                            <a class="page-link" href="?page=1&search=<?= urlencode($search) ?>">1</a>
                                        </li>
                                        <?php if ($start_page > 2): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>">
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
                                            <a class="page-link" href="?page=<?= $total_pages ?>&search=<?= urlencode($search) ?>"><?= $total_pages ?></a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <!-- Next Button -->
                                    <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
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
                                <input type="hidden" name="category_id" value="<?= $category['category_id'] ?>">
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Tên danh mục <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" required
                                               placeholder="Nhập tên danh mục"
                                               value="<?= htmlspecialchars($category['name'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <?php if ($category): ?>
                                    <div class="mb-3">
                                        <label class="form-label">Ngày tạo</label>
                                        <input type="text" class="form-control" readonly
                                               value="<?= date('d/m/Y H:i', strtotime($category['created_at'])) ?>">
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Mô tả danh mục</label>
                                <textarea name="description" class="form-control" rows="4" 
                                          placeholder="Nhập mô tả cho danh mục (tùy chọn)"><?= htmlspecialchars($category['description'] ?? '') ?></textarea>
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
        function deleteCategory(categoryId, currentPage, categoryName, productCount) {
            if (productCount > 0) {
                if (confirm(`Danh mục "${categoryName}" có ${productCount} sản phẩm.\n\nBạn có chắc chắn muốn xóa? Việc này có thể ảnh hưởng đến các sản phẩm liên quan.`)) {
                    const urlParams = new URLSearchParams(window.location.search);
                    const search = urlParams.get('search') || '';
                    
                    window.location.href = '?action=delete&id=' + categoryId + 
                                         '&page=' + currentPage +
                                         '&search=' + encodeURIComponent(search);
                }
            } else {
                if (confirm(`Bạn có chắc chắn muốn xóa danh mục "${categoryName}"?`)) {
                    const urlParams = new URLSearchParams(window.location.search);
                    const search = urlParams.get('search') || '';
                    
                    window.location.href = '?action=delete&id=' + categoryId + 
                                         '&page=' + currentPage +
                                         '&search=' + encodeURIComponent(search);
                }
            }
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const name = document.querySelector('input[name="name"]').value.trim();
            
            if (!name) {
                alert('Vui lòng nhập tên danh mục!');
                e.preventDefault();
                return;
            }
            
            if (name.length > 255) {
                alert('Tên danh mục không được vượt quá 255 ký tự!');
                e.preventDefault();
                return;
            }
        });

        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html> 