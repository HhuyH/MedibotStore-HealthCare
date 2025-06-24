<?php
require_once '../../includes/db.php';
require_once '../../includes/blog_functions.php';
require_once '../../includes/auth_check.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: /login.php');
    exit();
}

// Xử lý xóa bài viết
if (isset($_POST['delete_post'])) {
    $post_id = (int)$_POST['post_id'];
    if (delete_blog_post($post_id)) {
        $_SESSION['success'] = "Đã xóa bài viết thành công!";
    } else {
        $_SESSION['error'] = "Có lỗi xảy ra khi xóa bài viết!";
    }
    header('Location: manage_posts.php');
    exit();
}

// Lấy danh sách bài viết
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$posts = get_blog_posts($limit, $offset);
$categories = get_blog_categories();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý bài viết - Admin Panel</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    <h1 class="h2">Quản lý bài viết</h1>
                    <a href="create_post.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tạo bài viết mới
                    </a>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tiêu đề</th>
                                <th>Danh mục</th>
                                <th>Tác giả</th>
                                <th>Trạng thái</th>
                                <th>Ngày đăng</th>
                                <th>Lượt xem</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($posts as $post): ?>
                            <tr>
                                <td><?php echo $post['post_id']; ?></td>
                                <td>
                                    <a href="/blog/post.php?slug=<?php echo $post['slug']; ?>" target="_blank">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($post['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($post['author_name']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $post['status'] == 'published' ? 'success' : 
                                            ($post['status'] == 'draft' ? 'warning' : 'secondary'); 
                                    ?>">
                                        <?php 
                                        echo $post['status'] == 'published' ? 'Đã đăng' : 
                                            ($post['status'] == 'draft' ? 'Bản nháp' : 'Đã lưu trữ'); 
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($post['published_at'])); ?></td>
                                <td><?php echo $post['view_count']; ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="edit_post.php?id=<?php echo $post['post_id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteModal<?php echo $post['post_id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>

                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="deleteModal<?php echo $post['post_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Xác nhận xóa</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Bạn có chắc chắn muốn xóa bài viết "<?php echo htmlspecialchars($post['title']); ?>"?
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                                        <button type="submit" name="delete_post" class="btn btn-danger">Xóa</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page-1; ?>">Trước</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= ceil(count($posts) / $limit); $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < ceil(count($posts) / $limit)): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>">Sau</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="/assets/js/admin.js"></script>
</body>
</html> 