<?php
require_once '../../includes/db.php';
require_once '../../includes/blog_functions.php';
require_once '../../includes/auth_check.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: /login.php');
    exit();
}

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $excerpt = trim($_POST['excerpt']);
    $category_id = (int)$_POST['category_id'];
    $status = $_POST['status'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    // Tạo slug từ tiêu đề
    $slug = create_slug($title);
    
    // Xử lý upload ảnh
    $featured_image = '';
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == 0) {
        $upload_dir = '../../uploads/blog/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $upload_path)) {
            $featured_image = '/uploads/blog/' . $new_filename;
        }
    }
    
    // Chuẩn bị dữ liệu
    $data = [
        'author_id' => $_SESSION['user_id'],
        'category_id' => $category_id,
        'title' => $title,
        'slug' => $slug,
        'content' => $content,
        'excerpt' => $excerpt,
        'featured_image' => $featured_image,
        'status' => $status,
        'is_featured' => $is_featured,
        'published_at' => $status == 'published' ? date('Y-m-d H:i:s') : null
    ];
    
    // Tạo bài viết mới
    if (create_blog_post($data)) {
        $_SESSION['success'] = "Đã tạo bài viết mới thành công!";
        header('Location: manage_posts.php');
        exit();
    } else {
        $_SESSION['error'] = "Có lỗi xảy ra khi tạo bài viết!";
    }
}

// Lấy danh sách danh mục
$categories = get_blog_categories();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo bài viết mới - Admin Panel</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- TinyMCE -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/admin_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Tạo bài viết mới</h1>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Tiêu đề -->
                            <div class="mb-3">
                                <label for="title" class="form-label">Tiêu đề bài viết</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                                <div class="invalid-feedback">
                                    Vui lòng nhập tiêu đề bài viết
                                </div>
                            </div>

                            <!-- Nội dung -->
                            <div class="mb-3">
                                <label for="content" class="form-label">Nội dung</label>
                                <textarea class="form-control" id="content" name="content" rows="10" required></textarea>
                                <div class="invalid-feedback">
                                    Vui lòng nhập nội dung bài viết
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <!-- Danh mục -->
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Danh mục</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Chọn danh mục</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['category_id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Vui lòng chọn danh mục
                                </div>
                            </div>

                            <!-- Trạng thái -->
                            <div class="mb-3">
                                <label for="status" class="form-label">Trạng thái</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="draft">Bản nháp</option>
                                    <option value="published">Đăng ngay</option>
                                </select>
                            </div>

                            <!-- Bài viết nổi bật -->
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured">
                                    <label class="form-check-label" for="is_featured">
                                        Đánh dấu là bài viết nổi bật
                                    </label>
                                </div>
                            </div>

                            <!-- Ảnh đại diện -->
                            <div class="mb-3">
                                <label for="featured_image" class="form-label">Ảnh đại diện</label>
                                <input type="file" class="form-control" id="featured_image" name="featured_image" accept="image/*">
                                <div class="form-text">
                                    Kích thước đề xuất: 1200x630 pixels
                                </div>
                            </div>

                            <!-- Tóm tắt -->
                            <div class="mb-3">
                                <label for="excerpt" class="form-label">Tóm tắt</label>
                                <textarea class="form-control" id="excerpt" name="excerpt" rows="4"></textarea>
                                <div class="form-text">
                                    Tóm tắt ngắn gọn nội dung bài viết (tối đa 200 ký tự)
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Lưu bài viết
                        </button>
                        <a href="manage_posts.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Hủy
                        </a>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="/assets/js/admin.js"></script>
    
    <!-- TinyMCE -->
    <script>
        tinymce.init({
            selector: '#content',
            plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
            height: 500,
            images_upload_url: '/admin/upload.php',
            automatic_uploads: true
        });
    </script>

    <!-- Form Validation -->
    <script>
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html> 