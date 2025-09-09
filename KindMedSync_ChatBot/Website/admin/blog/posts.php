<?php
session_start();
include '../../includes/db.php';
include '../../includes/blog_functions.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../../login.php');
    exit;
}

// Xử lý các action
$action = $_GET['action'] ?? 'list';
$message = $_GET['message'] ?? '';
$error = '';

// Xử lý thêm/sửa bài viết
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $excerpt = trim($_POST['excerpt']);
    $category_id = (int)$_POST['category_id'];
    $author_id = (int)$_POST['author_id'];
    $status = $_POST['status'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $post_id = $_POST['post_id'] ?? null;
    
    // Tạo slug từ title
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    
    // Xử lý ảnh
    $featured_image = $_POST['current_image'] ?? 'assets/images/default-product.jpg';
    $image_type = $_POST['image_type'] ?? 'keep';
    
    if ($image_type === 'url' && !empty($_POST['image_url'])) {
        $featured_image = trim($_POST['image_url']);
        if (!filter_var($featured_image, FILTER_VALIDATE_URL)) {
            $error = 'URL ảnh không hợp lệ';
        }
    } elseif ($image_type === 'upload' && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../assets/images/blog/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['image']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $error = 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WebP)';
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $error = 'File ảnh quá lớn (tối đa 5MB)';
        } else {
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $file_name = 'blog_' . time() . '_' . uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $featured_image = 'assets/images/blog/' . $file_name;
            } else {
                $error = 'Không thể upload ảnh';
            }
        }
    }
    
    if (!$error) {
        if (empty($title) || empty($content)) {
            $error = 'Tiêu đề và nội dung không được để trống';
        } else {
            try {
                if ($post_id) {
                    // Cập nhật bài viết
                    $update_published = '';
                    if ($status == 'published') {
                        $update_published = ', published_at = COALESCE(published_at, NOW())';
                    }
                    
                    $stmt = $conn->prepare("UPDATE blog_posts SET title = ?, slug = ?, content = ?, excerpt = ?, category_id = ?, author_id = ?, status = ?, is_featured = ?, featured_image = ?, updated_at = NOW() $update_published WHERE post_id = ?");
                    $stmt->bind_param("ssssiisisi", $title, $slug, $content, $excerpt, $category_id, $author_id, $status, $is_featured, $featured_image, $post_id);
                    $message = 'Cập nhật bài viết thành công!';
                } else {
                    // Thêm bài viết mới
                    $published_at = ($status == 'published') ? 'NOW()' : 'NULL';
                    $stmt = $conn->prepare("INSERT INTO blog_posts (title, slug, content, excerpt, category_id, author_id, status, is_featured, featured_image, published_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, " . $published_at . ", NOW())");
                    $stmt->bind_param("ssssiiiss", $title, $slug, $content, $excerpt, $category_id, $author_id, $status, $is_featured, $featured_image);
                    $message = 'Thêm bài viết thành công!';
                }
                
                if ($stmt->execute()) {
                    $action = 'list';
                } else {
                    $error = 'Có lỗi xảy ra khi lưu bài viết';
                }
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $error = 'Tiêu đề bài viết đã tồn tại';
                } else {
                    $error = 'Lỗi: ' . $e->getMessage();
                }
            }
        }
    }
}

// Xử lý xóa bài viết
if ($action === 'delete' && isset($_GET['id'])) {
    $post_id = (int)$_GET['id'];
    
    try {
        $stmt = $conn->prepare("UPDATE blog_posts SET status = 'archived', updated_at = NOW() WHERE post_id = ?");
        $stmt->bind_param("i", $post_id);
        if ($stmt->execute()) {
            $message = 'Xóa bài viết thành công!';
        } else {
            $error = 'Không thể xóa bài viết';
        }
    } catch (Exception $e) {
        $error = 'Lỗi: ' . $e->getMessage();
    }
    $action = 'list';
}

// Lấy danh sách danh mục
$categories = [];

$categories_result = $conn->query("SELECT * FROM blog_categories ORDER BY name");
if ($categories_result) {
    while ($cat = $categories_result->fetch_assoc()) {
        $categories[] = $cat;
    }
}

// Lấy thông tin bài viết để sửa
$post = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $post_id = (int)$_GET['id'];
    $result = $conn->query("SELECT * FROM blog_posts WHERE post_id = $post_id");
    $post = $result->fetch_assoc();
    if (!$post) {
        $action = 'list';
        $error = 'Không tìm thấy bài viết';
    }
}

// Lấy danh sách bài viết với pagination
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$author_filter = $_GET['author'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where_conditions = [];
$params = [];
$types = '';

if ($search) {
    $where_conditions[] = "(bp.title LIKE ? OR bp.content LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if ($category_filter) {
    $where_conditions[] = "bp.category_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

if ($author_filter) {
    $where_conditions[] = "bp.author_id = ?";
    $params[] = $author_filter;
    $types .= 'i';
}

if ($status_filter) {
    $where_conditions[] = "bp.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$where_clause = empty($where_conditions) ? '1=1' : implode(' AND ', $where_conditions);

// Đếm tổng số bài viết
$count_sql = "SELECT COUNT(*) as total FROM blog_posts bp WHERE $where_clause";
$count_stmt = $conn->prepare($count_sql);
if ($params) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_posts = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_posts / $per_page);

// Lấy danh sách bài viết
$sql = "SELECT bp.*, bc.name as category_name, ba.name as author_name
        FROM blog_posts bp
        LEFT JOIN blog_categories bc ON bp.category_id = bc.category_id
        LEFT JOIN blog_authors ba ON bp.author_id = ba.author_id
        WHERE $where_clause
        ORDER BY bp.created_at DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$posts_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý bài viết - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="../assets/css/sidebar.css" rel="stylesheet">
    <link href="../assets/css/header.css" rel="stylesheet">
    <!-- TinyMCE -->
    <script src="https://cdn.tiny.cloud/1/uudz6iuzw3jfiry9wjz755ebc3n25uo482x1psy00mznsu4t/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
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
                                    <i class="fas fa-blog"></i> 
                                    <?php echo ($action === 'add') ? 'Thêm bài viết mới' : (($action === 'edit') ? 'Chỉnh sửa bài viết' : 'Quản lý bài viết blog'); ?>
                                </h4>
                                <?php if ($action === 'list'): ?>
                                    <div>
                                        <a href="?action=add" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Thêm bài viết
                                        </a>
                                        <a href="categories.php" class="btn btn-secondary">
                                            <i class="fas fa-tags"></i> Quản lý danh mục
                                        </a>
                                    </div>
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
                                    <!-- Bộ lọc -->
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <form method="GET" class="d-flex">
                                                <input type="text" class="form-control me-2" name="search" 
                                                       placeholder="Tìm kiếm bài viết..." value="<?php echo htmlspecialchars($search); ?>">
                                                <button class="btn btn-outline-primary" type="submit">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                            </form>
                                        </div>
                                        <div class="col-md-8">
                                            <form method="GET" class="d-flex gap-2">
                                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                                <select name="category" class="form-select">
                                                    <option value="">Tất cả danh mục</option>
                                                    <?php foreach ($categories as $cat): ?>
                                                        <option value="<?php echo $cat['category_id']; ?>" 
                                                                <?php echo ($category_filter == $cat['category_id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($cat['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <select name="author" class="form-select">
                                                    <option value="">Tất cả tác giả</option>
                                                    <option value="1" <?php echo ($author_filter == '1') ? 'selected' : ''; ?>>Admin</option>
                                                    <option value="2" <?php echo ($author_filter == '2') ? 'selected' : ''; ?>>Doctor</option>
                                                </select>
                                                <select name="status" class="form-select">
                                                    <option value="">Tất cả trạng thái</option>
                                                    <option value="draft" <?php echo ($status_filter == 'draft') ? 'selected' : ''; ?>>Bản nháp</option>
                                                    <option value="published" <?php echo ($status_filter == 'published') ? 'selected' : ''; ?>>Đã xuất bản</option>
                                                    <option value="archived" <?php echo ($status_filter == 'archived') ? 'selected' : ''; ?>>Đã xóa</option>
                                                </select>
                                                <button class="btn btn-outline-secondary" type="submit">Lọc</button>
                                                <a href="?" class="btn btn-outline-secondary">Reset</a>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    <!-- Bảng danh sách -->
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Tiêu đề</th>
                                                    <th>Danh mục</th>
                                                    <th>Tác giả</th>
                                                    <th>Trạng thái</th>
                                                    <th>Nổi bật</th>
                                                    <th>Lượt xem</th>
                                                    <th>Ngày tạo</th>
                                                    <th>Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if ($posts_result->num_rows > 0): ?>
                                                    <?php while ($row = $posts_result->fetch_assoc()): ?>
                                                        <tr>
                                                            <td><?php echo $row['post_id']; ?></td>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <img src="../../<?php echo $row['featured_image']; ?>" 
                                                                         alt="<?php echo htmlspecialchars($row['title']); ?>" 
                                                                         class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                                                    <div>
                                                                        <strong><?php echo htmlspecialchars(substr($row['title'], 0, 50)); ?></strong>
                                                                        <?php if (strlen($row['title']) > 50): ?>...<?php endif; ?>
                                                                        <br>
                                                                        <small class="text-muted"><?php echo htmlspecialchars($row['slug']); ?></small>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($row['category_name']); ?></span>
                                                            </td>
                                                            <td>
                                                                <?php 
                                                                if ($row['author_id'] == 1) {
                                                                    echo 'Admin';
                                                                } elseif ($row['author_id'] == 2) {
                                                                    echo 'Doctor';  
                                                                } else {
                                                                    echo htmlspecialchars($row['author_name'] ?? 'Unknown');
                                                                }
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <?php
                                                                $status_class = '';
                                                                switch ($row['status']) {
                                                                    case 'published':
                                                                        $status_class = 'bg-success';
                                                                        break;
                                                                    case 'draft':
                                                                        $status_class = 'bg-warning';
                                                                        break;
                                                                    case 'archived':
                                                                        $status_class = 'bg-danger';
                                                                        break;
                                                                }
                                                                ?>
                                                                <span class="badge <?php echo $status_class; ?>">
                                                                    <?php echo ucfirst($row['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <?php if ($row['is_featured']): ?>
                                                                    <i class="fas fa-star text-warning"></i>
                                                                <?php else: ?>
                                                                    <i class="far fa-star text-muted"></i>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-info"><?php echo number_format($row['view_count']); ?></span>
                                                            </td>
                                                            <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                                            <td>
                                                                <div class="btn-group btn-group-sm">
                                                                    <a href="../../blog-post.php?slug=<?php echo $row['slug']; ?>" 
                                                                       class="btn btn-info" title="Xem" target="_blank">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
                                                                    <a href="?action=edit&id=<?php echo $row['post_id']; ?>" 
                                                                       class="btn btn-warning" title="Sửa">
                                                                        <i class="fas fa-edit"></i>
                                                                    </a>
                                                                    <a href="?action=delete&id=<?php echo $row['post_id']; ?>" 
                                                                       class="btn btn-danger" title="Xóa"
                                                                       onclick="return confirm('Bạn có chắc muốn xóa bài viết này?')">
                                                                        <i class="fas fa-trash"></i>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="9" class="text-center">Không có bài viết nào</td>
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
                                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&author=<?php echo $author_filter; ?>&status=<?php echo $status_filter; ?>">
                                                            <i class="fas fa-chevron-left"></i>
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                                
                                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&author=<?php echo $author_filter; ?>&status=<?php echo $status_filter; ?>">
                                                            <?php echo $i; ?>
                                                        </a>
                                                    </li>
                                                <?php endfor; ?>
                                                
                                                <?php if ($page < $total_pages): ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&author=<?php echo $author_filter; ?>&status=<?php echo $status_filter; ?>">
                                                            <i class="fas fa-chevron-right"></i>
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </nav>
                                    <?php endif; ?>
                                    
                                <?php else: ?>
                                    <!-- Form thêm/sửa -->
                                    <form method="POST" enctype="multipart/form-data" class="row g-3">
                                        <?php if ($action === 'edit'): ?>
                                            <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                            <input type="hidden" name="current_image" value="<?php echo $post['featured_image']; ?>">
                                        <?php endif; ?>
                                        
                                        <div class="col-md-8">
                                            <label for="title" class="form-label">Tiêu đề bài viết *</label>
                                            <input type="text" class="form-control" id="title" name="title" 
                                                   value="<?php echo htmlspecialchars($post['title'] ?? ''); ?>" required>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <label for="status" class="form-label">Trạng thái *</label>
                                            <select class="form-select" id="status" name="status" required>
                                                <option value="draft" <?php echo (($post['status'] ?? 'draft') == 'draft') ? 'selected' : ''; ?>>Bản nháp</option>
                                                <option value="published" <?php echo (($post['status'] ?? '') == 'published') ? 'selected' : ''; ?>>Đã xuất bản</option>
                                                <option value="archived" <?php echo (($post['status'] ?? '') == 'archived') ? 'selected' : ''; ?>>Đã xóa</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="category_id" class="form-label">Danh mục *</label>
                                            <select class="form-select" id="category_id" name="category_id" required>
                                                <option value="">Chọn danh mục</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo $cat['category_id']; ?>" 
                                                            <?php echo (($post['category_id'] ?? '') == $cat['category_id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($cat['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="author_id" class="form-label">Tác giả *</label>
                                            <select class="form-select" id="author_id" name="author_id" required>
                                                <option value="">Chọn tác giả</option>
                                                <option value="1" <?php echo (($post['author_id'] ?? '') == '1') ? 'selected' : ''; ?>>Admin</option>
                                                <option value="2" <?php echo (($post['author_id'] ?? '') == '2') ? 'selected' : ''; ?>>Doctor</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-12">            
                                            <label for="excerpt" class="form-label">Mô tả ngắn</label>
                                            <textarea class="form-control" id="excerpt" name="excerpt" rows="3"><?php echo htmlspecialchars($post['excerpt'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="col-12">
                                            <label for="content" class="form-label">Nội dung bài viết *</label>
                                            <textarea class="form-control" id="content" name="content" rows="10"><?php echo htmlspecialchars($post['content'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <!-- Ảnh đại diện -->
                                        <div class="col-12">
                                            <label class="form-label">Ảnh đại diện</label>
                                            <div class="card">
                                                <div class="card-body">
                                                    <div class="row g-3">
                                                        <!-- Tùy chọn 1: Giữ ảnh hiện tại -->
                                                        <div class="col-md-4">
                                                            <div class="border rounded p-3">
                                                                <div class="d-flex align-items-center mb-3">
                                                                    <input type="radio" name="image_type" id="image_keep" value="keep" checked 
                                                                           class="me-2" style="width: 20px; height: 20px;">
                                                                    <label for="image_keep" class="mb-0 fw-bold">Giữ ảnh hiện tại</label>
                                                                </div>
                                                                <?php if ($action === 'edit' && $post['featured_image']): ?>
                                                                    <img src="../../<?php echo $post['featured_image']; ?>" 
                                                                         alt="Current image" class="img-thumbnail" 
                                                                         style="width: 100px; height: 100px; object-fit: cover;">
                                                                <?php else: ?>
                                                                    <div class="text-muted small">Sử dụng ảnh mặc định</div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- Tùy chọn 2: Upload ảnh mới -->
                                                        <div class="col-md-4">
                                                            <div class="border rounded p-3">
                                                                <div class="d-flex align-items-center mb-3">
                                                                    <input type="radio" name="image_type" id="image_upload" value="upload" 
                                                                           class="me-2" style="width: 20px; height: 20px;">
                                                                    <label for="image_upload" class="mb-0 fw-bold">Upload ảnh mới</label>
                                                                </div>
                                                                <input type="file" class="form-control mb-2" name="image" accept="image/*" disabled>
                                                                <small class="text-muted">JPG, PNG, GIF, WebP (tối đa 5MB)</small>
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- Tùy chọn 3: URL ảnh -->
                                                        <div class="col-md-4">
                                                            <div class="border rounded p-3">
                                                                <div class="d-flex align-items-center mb-3">
                                                                    <input type="radio" name="image_type" id="image_url" value="url" 
                                                                           class="me-2" style="width: 20px; height: 20px;">
                                                                    <label for="image_url" class="mb-0 fw-bold">Sử dụng URL ảnh</label>
                                                                </div>
                                                                <input type="url" class="form-control mb-2" name="image_url" 
                                                                       placeholder="https://example.com/image.jpg" disabled>
                                                                <small class="text-muted">Nhập link ảnh từ internet</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Khu vực hiển thị preview -->
                                                    <div id="image-preview" class="mt-3" style="display: none;">
                                                        <div class="alert alert-info">
                                                            <strong>Preview:</strong>
                                                            <div class="mt-2">
                                                                <img id="preview-image" src="" alt="Preview" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" 
                                                       <?php echo (($post['is_featured'] ?? 0) == 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="is_featured">
                                                    Bài viết nổi bật
                                                </label>
                                            </div>
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
    <style>
        /* CSS cho radio buttons ảnh */
        input[type="radio"] {
            cursor: pointer !important;
            pointer-events: auto !important;
            position: relative !important;
            z-index: 1 !important;
        }
        
        label {
            cursor: pointer !important;
            pointer-events: auto !important;
        }
        
        /* Đảm bảo radio buttons có thể click */
        .d-flex.align-items-center {
            cursor: pointer;
        }
        
        .d-flex.align-items-center:hover {
            background-color: rgba(13, 110, 253, 0.1);
            border-radius: 4px;
        }
        
        .border.rounded {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .border.rounded:hover {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-color: #0d6efd !important;
        }
        
        input[type="radio"]:checked + label {
            color: #0d6efd;
            font-weight: bold;
        }
        
        /* Hiệu ứng khi chọn */
        .image-option-selected {
            border-color: #0d6efd !important;
            background-color: #f8f9fa;
        }
        
        .form-control:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .form-control:not(:disabled) {
            cursor: pointer;
        }
    </style>
    <script>
        // TinyMCE Editor
        tinymce.init({
            selector: '#content',
            height: 400,
            plugins: 'advlist autolink lists link image charmap print preview anchor searchreplace visualblocks code fullscreen insertdatetime media table paste code help wordcount',
            toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; -webkit-font-smoothing: antialiased; }'
        });
        
        // Image type radio buttons - Phiên bản mới đơn giản
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded - Setting up image controls');
            
            const imageRadios = document.querySelectorAll('input[name="image_type"]');
            const fileInput = document.querySelector('input[name="image"]');
            const urlInput = document.querySelector('input[name="image_url"]');
            const previewDiv = document.getElementById('image-preview');
            const previewImg = document.getElementById('preview-image');
            
            console.log('Found elements:', {
                radios: imageRadios.length,
                fileInput: !!fileInput,
                urlInput: !!urlInput,
                previewDiv: !!previewDiv
            });
            
            // Hàm cập nhật trạng thái
            function updateImageInputs() {
                const selectedRadio = document.querySelector('input[name="image_type"]:checked');
                if (!selectedRadio) return;
                
                const value = selectedRadio.value;
                console.log('Selected image type:', value);
                
                // Reset tất cả
                fileInput.disabled = true;
                urlInput.disabled = true;
                previewDiv.style.display = 'none';
                
                // Xóa class selected cũ
                document.querySelectorAll('.image-option-selected').forEach(el => {
                    el.classList.remove('image-option-selected');
                });
                
                // Thêm class selected mới
                const selectedContainer = selectedRadio.closest('.border.rounded');
                if (selectedContainer) {
                    selectedContainer.classList.add('image-option-selected');
                }
                
                // Kích hoạt input phù hợp
                if (value === 'upload') {
                    fileInput.disabled = false;
                    fileInput.required = true;
                    urlInput.required = false;
                } else if (value === 'url') {
                    urlInput.disabled = false;
                    urlInput.required = true;
                    fileInput.required = false;
                } else {
                    fileInput.required = false;
                    urlInput.required = false;
                }
            }
            
            // Gán event cho radio buttons
            imageRadios.forEach(radio => {
                radio.addEventListener('change', updateImageInputs);
                radio.addEventListener('click', updateImageInputs);
            });
            
            // Cho phép click vào container để chọn radio
            document.querySelectorAll('.border.rounded').forEach(container => {
                container.addEventListener('click', function(e) {
                    const radio = this.querySelector('input[type="radio"]');
                    if (radio && e.target !== radio) {
                        radio.checked = true;
                        updateImageInputs();
                    }
                });
            });
            
            // Preview file
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        previewDiv.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            // Preview URL
            urlInput.addEventListener('blur', function() {
                const url = this.value.trim();
                if (url) {
                    previewImg.src = url;
                    previewDiv.style.display = 'block';
                    
                    // Kiểm tra nếu ảnh load lỗi
                    previewImg.onerror = function() {
                        previewDiv.style.display = 'none';
                        alert('Không thể tải ảnh từ URL này!');
                    };
                }
            });
            
            // Thiết lập trạng thái ban đầu
            updateImageInputs();
        });
    </script>
</body>
</html> 