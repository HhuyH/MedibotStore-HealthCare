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
$message = '';
$error = '';

// Xử lý thêm/sửa sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $category_id = (int)$_POST['category_id'];
    $stock_quantity = (int)$_POST['stock_quantity'];
    $status = $_POST['status'];
    $product_id = $_POST['product_id'] ?? null;
    
    // Xử lý upload ảnh
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/images/products/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_extension;
        $image_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
            $image_path = 'assets/images/products/' . $file_name;
        } else {
            $error = 'Không thể upload ảnh';
        }
    }
    
    if (!$error) {
        try {
            if ($product_id) {
                // Cập nhật sản phẩm
                if ($image_path) {
                    $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, category_id = ?, stock_quantity = ?, status = ?, image = ?, updated_at = NOW() WHERE product_id = ?");
                    $stmt->bind_param("ssdiissi", $name, $description, $price, $category_id, $stock_quantity, $status, $image_path, $product_id);
                } else {
                    $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, category_id = ?, stock_quantity = ?, status = ?, updated_at = NOW() WHERE product_id = ?");
                    $stmt->bind_param("ssdiisi", $name, $description, $price, $category_id, $stock_quantity, $status, $product_id);
                }
                $message = 'Cập nhật sản phẩm thành công!';
            } else {
                // Thêm sản phẩm mới
                $stmt = $conn->prepare("INSERT INTO products (name, description, price, category_id, stock_quantity, status, image, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("ssdiiss", $name, $description, $price, $category_id, $stock_quantity, $status, $image_path);
                $message = 'Thêm sản phẩm thành công!';
            }
            
            if ($stmt->execute()) {
                $action = 'list';
            } else {
                $error = 'Có lỗi xảy ra khi lưu sản phẩm';
            }
        } catch (Exception $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}

// Xử lý xóa sản phẩm
if ($action === 'delete' && isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    try {
        $stmt = $conn->prepare("UPDATE products SET status = 'deleted', updated_at = NOW() WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        if ($stmt->execute()) {
            $message = 'Xóa sản phẩm thành công!';
        } else {
            $error = 'Không thể xóa sản phẩm';
        }
    } catch (Exception $e) {
        $error = 'Lỗi: ' . $e->getMessage();
    }
    $action = 'list';
}

// Lấy danh sách danh mục
$categories = $conn->query("SELECT * FROM product_categories ORDER BY name");

// Lấy thông tin sản phẩm để sửa
$product = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    $result = $conn->query("SELECT * FROM products WHERE product_id = $product_id");
    $product = $result->fetch_assoc();
    if (!$product) {
        $action = 'list';
        $error = 'Không tìm thấy sản phẩm';
    }
}

// Lấy danh sách sản phẩm
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? '';

$where_conditions = ["status != 'deleted'"];
$params = [];
$types = '';

if ($search) {
    $where_conditions[] = "(name LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if ($category_filter) {
    $where_conditions[] = "category_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$where_clause = implode(' AND ', $where_conditions);

$sql = "SELECT p.*, pc.name as category_name 
        FROM products p 
        LEFT JOIN product_categories pc ON p.category_id = pc.category_id 
        WHERE $where_clause 
        ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sản phẩm - MediSync Admin</title>
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
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-pills me-2"></i>Quản lý sản phẩm
                    </h1>
                    <p class="mb-0 text-muted">Quản lý danh sách sản phẩm y tế</p>
                </div>
                <?php if ($action === 'list'): ?>
                <a href="?action=add" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Thêm sản phẩm
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
                                       placeholder="Tên sản phẩm..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Danh mục</label>
                                <select name="category" class="form-select">
                                    <option value="">Tất cả danh mục</option>
                                    <?php if ($categories): ?>
                                        <?php while ($cat = $categories->fetch_assoc()): ?>
                                            <option value="<?= $cat['category_id'] ?>" 
                                                    <?= $category_filter == $cat['category_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['name']) ?>
                                            </option>
                                        <?php endwhile; ?>
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

                <!-- Products List -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-list me-2"></i>Danh sách sản phẩm
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0">Ảnh</th>
                                        <th class="border-0">Tên sản phẩm</th>
                                        <th class="border-0">Danh mục</th>
                                        <th class="border-0">Giá</th>
                                        <th class="border-0">Tồn kho</th>
                                        <th class="border-0">Trạng thái</th>
                                        <th class="border-0">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($products && $products->num_rows > 0): ?>
                                        <?php while ($prod = $products->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <img src="<?= htmlspecialchars($prod['image'] ?: 'assets/images/default-product.jpg') ?>" 
                                                         alt="<?= htmlspecialchars($prod['name']) ?>" 
                                                         class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                                </td>
                                                <td>
                                                    <div>
                                                        <h6 class="mb-0"><?= htmlspecialchars($prod['name']) ?></h6>
                                                        <small class="text-muted"><?= htmlspecialchars(substr($prod['description'], 0, 50)) ?>...</small>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($prod['category_name'] ?? 'N/A') ?></td>
                                                <td class="fw-bold text-primary"><?= number_format($prod['price']) ?>đ</td>
                                                <td>
                                                    <span class="badge bg-<?= $prod['stock_quantity'] > 10 ? 'success' : ($prod['stock_quantity'] > 0 ? 'warning' : 'danger') ?>">
                                                        <?= $prod['stock_quantity'] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= $prod['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                        <?= $prod['status'] === 'active' ? 'Hoạt động' : 'Không hoạt động' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?action=edit&id=<?= $prod['product_id'] ?>" 
                                                           class="btn btn-outline-primary" title="Sửa">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button class="btn btn-outline-danger" 
                                                                onclick="deleteProduct(<?= $prod['product_id'] ?>)" title="Xóa">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="fas fa-box-open fa-2x mb-2 d-block"></i>
                                                Không có sản phẩm nào
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- Add/Edit Form -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-<?= $action === 'add' ? 'plus' : 'edit' ?> me-2"></i>
                            <?= $action === 'add' ? 'Thêm sản phẩm mới' : 'Sửa sản phẩm' ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <?php if ($product): ?>
                                <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label">Tên sản phẩm <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" required
                                               value="<?= htmlspecialchars($product['name'] ?? '') ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Mô tả</label>
                                        <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Giá <span class="text-danger">*</span></label>
                                                <input type="number" name="price" class="form-control" required min="0" step="0.01"
                                                       value="<?= $product['price'] ?? '' ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Số lượng tồn kho</label>
                                                <input type="number" name="stock_quantity" class="form-control" min="0"
                                                       value="<?= $product['stock_quantity'] ?? 0 ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Danh mục</label>
                                                <select name="category_id" class="form-select">
                                                    <option value="">Chọn danh mục</option>
                                                    <?php 
                                                    $categories->data_seek(0); // Reset pointer
                                                    while ($cat = $categories->fetch_assoc()): 
                                                    ?>
                                                        <option value="<?= $cat['category_id'] ?>" 
                                                                <?= ($product['category_id'] ?? '') == $cat['category_id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($cat['name']) ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Trạng thái</label>
                                                <select name="status" class="form-select">
                                                    <option value="active" <?= ($product['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Hoạt động</option>
                                                    <option value="inactive" <?= ($product['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Không hoạt động</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Ảnh sản phẩm</label>
                                        <input type="file" name="image" class="form-control" accept="image/*">
                                        <?php if ($product && $product['image']): ?>
                                            <div class="mt-2">
                                                <img src="<?= htmlspecialchars($product['image']) ?>" 
                                                     alt="Current image" class="img-thumbnail" style="max-width: 200px;">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    <?= $action === 'add' ? 'Thêm sản phẩm' : 'Cập nhật' ?>
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
        function deleteProduct(productId) {
            if (confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')) {
                window.location.href = '?action=delete&id=' + productId;
            }
        }
    </script>
</body>
</html> 