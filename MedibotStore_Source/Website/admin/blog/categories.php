<?php
session_start();
include '../../includes/db.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../../login.php');
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
    
    // Tạo slug từ name
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    
    if (empty($name)) {
        $error = 'Tên danh mục không được để trống';
    } else {
        try {
            if ($category_id) {
                // Cập nhật danh mục
                $stmt = $conn->prepare("UPDATE blog_categories SET name = ?, slug = ?, description = ?, updated_at = NOW() WHERE category_id = ?");
                $stmt->bind_param("sssi", $name, $slug, $description, $category_id);
                $message = 'Cập nhật danh mục thành công!';
            } else {
                // Thêm danh mục mới
                $stmt = $conn->prepare("INSERT INTO blog_categories (name, slug, description, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("sss", $name, $slug, $description);
                $message = 'Thêm danh mục thành công!';
            }
            
            if ($stmt->execute()) {
                $action = 'list';
            } else {
                $error = 'Có lỗi xảy ra khi lưu danh mục';
            }
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $error = 'Tên danh mục đã tồn tại';
            } else {
                $error = 'Lỗi: ' . $e->getMessage();
            }
        }
    }
}

// Xử lý xóa danh mục
if ($action === 'delete' && isset($_GET['id'])) {
    $category_id = (int)$_GET['id'];
    
    try {
        // Kiểm tra xem danh mục có bài viết nào không
        $check_stmt = $conn->prepare("SELECT COUNT(*) as post_count FROM blog_posts WHERE category_id = ?");
        $check_stmt->bind_param("i", $category_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $post_count = $result->fetch_assoc()['post_count'];
        
        if ($post_count > 0) {
            $error = 'Không thể xóa danh mục vì còn ' . $post_count . ' bài viết thuộc danh mục này';
        } else {
            $stmt = $conn->prepare("DELETE FROM blog_categories WHERE category_id = ?");
            $stmt->bind_param("i", $category_id);
            if ($stmt->execute()) {
                $message = 'Xóa danh mục thành công!';
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
    $result = $conn->query("SELECT * FROM blog_categories WHERE category_id = $category_id");
    $category = $result->fetch_assoc();
    if (!$category) {
        $action = 'list';
        $error = 'Không tìm thấy danh mục';
    }
}

// Lấy danh sách danh mục với pagination
$search = $_GET['search'] ?? '';
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

$where_clause = empty($where_conditions) ? '1=1' : implode(' AND ', $where_conditions);

// Đếm tổng số danh mục
$count_sql = "SELECT COUNT(*) as total FROM blog_categories WHERE $where_clause";
$count_stmt = $conn->prepare($count_sql);
if ($params) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_categories = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_categories / $per_page);

// Lấy danh sách danh mục
$sql = "SELECT bc.*, 
               (SELECT COUNT(*) FROM blog_posts bp WHERE bp.category_id = bc.category_id) as post_count
        FROM blog_categories bc 
        WHERE $where_clause 
        ORDER BY bc.created_at DESC 
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$categories_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý danh mục blog - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="../assets/css/sidebar.css" rel="stylesheet">
    <link href="../assets/css/header.css" rel="stylesheet">
  </head>
  <body>

  <?php include '../includes/headeradmin.php'; ?>
  <?php include '../includes/sidebaradmin.php'; ?>

    <main class="main-content">
            
            <div class="container-fluid p-4">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="mb-0">
                                    <i class="fas fa-tags"></i> 
                                    <?php echo ($action === 'add') ? 'Thêm danh mục mới' : (($action === 'edit') ? 'Chỉnh sửa danh mục' : 'Quản lý danh mục blog'); ?>
                                </h4>
                                <?php if ($action === 'list'): ?>
                                    <a href="?action=add" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Thêm danh mục
                                    </a>
                                <?php else: ?>
                                    <a href="?" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Quay lại
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-body">
                                <?php if ($message): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <?php echo htmlspecialchars($message); ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($error): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <?php echo htmlspecialchars($error); ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($action === 'list'): ?>
                                    <!-- Tìm kiếm -->
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <form method="GET" class="d-flex">
                                                <input type="text" class="form-control me-2" name="search" 
                                                       placeholder="Tìm kiếm danh mục..." value="<?php echo htmlspecialchars($search); ?>">
                                                <button class="btn btn-outline-primary" type="submit">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                                <?php if ($search): ?>
                                                    <a href="?" class="btn btn-outline-secondary ms-2">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </form>
                                        </div>
                                        <div class="col-md-6 text-end">
                                            <small class="text-muted">Tổng cộng: <?php echo $total_categories; ?> danh mục</small>
                                        </div>
                                    </div>
                                    
                                    <!-- Bảng danh sách -->
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Tên danh mục</th>
                                                    <th>Slug</th>
                                                    <th>Mô tả</th>
                                                    <th>Số bài viết</th>
                                                    <th>Ngày tạo</th>
                                                    <th>Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if ($categories_result->num_rows > 0): ?>
                                                    <?php while ($row = $categories_result->fetch_assoc()): ?>
                                                        <tr>
                                                            <td><?php echo $row['category_id']; ?></td>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                                                            </td>
                                                            <td>
                                                                <code><?php echo htmlspecialchars($row['slug']); ?></code>
                                                            </td>
                                                            <td>
                                                                <?php echo htmlspecialchars(substr($row['description'], 0, 100)); ?>
                                                                <?php if (strlen($row['description']) > 100): ?>...<?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-info"><?php echo $row['post_count']; ?></span>
                                                            </td>
                                                            <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                                            <td>
                                                                <div class="btn-group btn-group-sm">
                                                                    <a href="?action=edit&id=<?php echo $row['category_id']; ?>" 
                                                                       class="btn btn-warning" title="Sửa">
                                                                        <i class="fas fa-edit"></i>
                                                                    </a>
                                                                    <a href="?action=delete&id=<?php echo $row['category_id']; ?>" 
                                                                       class="btn btn-danger" title="Xóa"
                                                                       onclick="return confirm('Bạn có chắc muốn xóa danh mục này?')">
                                                                        <i class="fas fa-trash"></i>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center">Không có danh mục nào</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Pagination -->
                                    <?php if ($total_pages > 1): ?>
                                        <nav aria-label="Page navigation">
                                            <ul class="pagination justify-content-center">
                                                <?php if ($page > 1): ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">
                                                            <i class="fas fa-chevron-left"></i>
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                                
                                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                                            <?php echo $i; ?>
                                                        </a>
                                                    </li>
                                                <?php endfor; ?>
                                                
                                                <?php if ($page < $total_pages): ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">
                                                            <i class="fas fa-chevron-right"></i>
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </nav>
                                    <?php endif; ?>
                                    
                                <?php else: ?>
                                    <!-- Form thêm/sửa -->
                                    <form method="POST" class="row g-3">
                                        <?php if ($action === 'edit'): ?>
                                            <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                                        <?php endif; ?>
                                        
                                        <div class="col-md-6">
                                            <label for="name" class="form-label">Tên danh mục *</label>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   value="<?php echo htmlspecialchars($category['name'] ?? ''); ?>" required>
                                        </div>
                                        
                                        <div class="col-12">
                                            <label for="description" class="form-label">Mô tả</label>
                                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> 
                                                <?php echo ($action === 'edit') ? 'Cập nhật' : 'Thêm mới'; ?>
                                            </button>
                                            <a href="?" class="btn btn-secondary">
                                                <i class="fas fa-times"></i> Hủy
                                            </a>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 