<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/blog_functions.php';

// Get current page for pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6; // Number of posts per page
$offset = ($page - 1) * $limit;

// Get search query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get category filter
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;

// Get posts
$posts = get_blog_posts($limit, $offset, $category_id, $search);
$total_posts = get_blog_posts_count($category_id, $search);
$total_pages = ceil($total_posts / $limit);

// Get categories for sidebar
$categories = get_blog_categories();

// Get recent posts for sidebar
$recent_posts = get_recent_posts(5);

// Get featured post
$featured_post = get_featured_post();

// Get current category name for breadcrumb
$current_category = null;
if ($category_id) {
    foreach ($categories as $cat) {
        if ($cat['category_id'] == $category_id) {
            $current_category = $cat;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
 
    <title><?php echo $current_category ? $current_category['name'] . ' - ' : ''; ?>Góc sức khỏe - MediSync</title>
    <meta name="description" content="Đọc những bài viết y khoa mới nhất, mẹo chăm sóc sức khỏe và tin tức y tế từ các chuyên gia tại MediSync.">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/blog.css">  
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <!-- Appointment Modal -->
    <?php include 'includes/appointment-modal.php'; ?>

    <main class="blog-main">
        <!-- Breadcrumb -->
        <section class="breadcrumb-section">
            <div class="container">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/">Trang chủ</a></li>
                        <li class="breadcrumb-item <?php echo !$current_category ? 'active' : ''; ?>" <?php echo !$current_category ? 'aria-current="page"' : ''; ?>>
                            <?php if (!$current_category): ?>
                                Góc sức khỏe
                            <?php else: ?>
                                <a href="/blog.php">Góc sức khỏe</a>
                            <?php endif; ?>
                        </li>
                        <?php if ($current_category): ?>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($current_category['name']); ?></li>
                        <?php endif; ?>
                    </ol>
                </nav>
            </div>
        </section>

        <!-- Page Header -->
        <section class="page-header">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h1 class="page-title">
                            <?php echo $current_category ? htmlspecialchars($current_category['name']) : 'Góc sức khỏe'; ?>
                        </h1>
                        <p class="page-subtitle">
                            <?php if ($current_category): ?>
                                <?php echo htmlspecialchars($current_category['description']); ?>
                            <?php else: ?>
                                Khám phá kiến thức y khoa hữu ích, mẹo chăm sóc sức khỏe và những thông tin mới nhất từ giới y tế
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-lg-4">
                        <div class="search-widget">
                            <form action="" method="GET" class="search-form">
                                <?php if ($category_id): ?>
                                <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                                <?php endif; ?>
                                <div class="search-input-group">
                                    <input type="text" name="search" placeholder="Tìm kiếm bài viết..." 
                                           class="form-control" value="<?php echo htmlspecialchars($search); ?>">
                                    <button type="submit" class="search-btn">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Category Tags -->
        <section class="category-tags">
            <div class="container">
                <div class="category-filter">
                    <a href="/blog.php" class="category-tag <?php echo !$category_id ? 'active' : ''; ?>">
                        Tất cả
                    </a>
                    <?php foreach ($categories as $category): ?>
                    <a href="?category=<?php echo $category['category_id']; ?>" 
                       class="category-tag <?php echo $category_id == $category['category_id'] ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($category['name']); ?>
                        <span class="count"><?php echo $category['post_count']; ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Blog Content -->
        <section class="blog-content">
            <div class="container">
                <div class="row">
                    <!-- Main Content -->
                    <div class="col-lg-8">
                        <?php if ($featured_post && !$category_id && !$search): ?>
                        <!-- Featured Post -->
                        <div class="featured-post-wrapper mb-5">
                            <article class="featured-post">
                                <div class="featured-image">
                                    <img src="<?php echo htmlspecialchars($featured_post['featured_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($featured_post['title']); ?>" 
                                         class="img-fluid">
                                    <div class="featured-badge">
                                        <i class="fas fa-star"></i>
                                        Nổi bật
                                    </div>
                                </div>
                                <div class="featured-content">
                                    <div class="post-meta">
                                        <span class="category-badge"><?php echo htmlspecialchars($featured_post['category_name']); ?></span>
                                        <span class="post-date">
                                            <i class="far fa-calendar"></i>
                                            <?php echo date('d/m/Y', strtotime($featured_post['created_at'])); ?>
                                        </span>
                                        <span class="read-time">
                                            <i class="far fa-clock"></i>
                                            5 phút đọc
                                        </span>
                                    </div>
                                    <h2 class="featured-title">
                                        <a href="/blog-post.php?slug=<?php echo $featured_post['slug']; ?>">
                                            <?php echo htmlspecialchars($featured_post['title']); ?>
                                        </a>
                                    </h2>
                                    <p class="featured-excerpt"><?php echo htmlspecialchars($featured_post['excerpt']); ?></p>
                                    <div class="post-footer">
                                        <div class="author-info">
                                            <img src="<?php echo htmlspecialchars($featured_post['author_avatar']); ?>" 
                                                 alt="<?php echo htmlspecialchars($featured_post['author_name']); ?>" 
                                                 class="author-avatar">
                                            <div class="author-details">
                                                <span class="author-name"><?php echo htmlspecialchars($featured_post['author_name']); ?></span>
                                                <span class="author-title">Chuyên gia y tế</span>
                                            </div>
                                        </div>
                                        <a href="/blog-post.php?slug=<?php echo $featured_post['slug']; ?>" class="read-more-btn">
                                            Đọc tiếp
                                            <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        </div>
                        <?php endif; ?>

                        <!-- Posts Grid -->
                        <div class="posts-grid">
                            <div class="row g-4">
                                <?php if (empty($posts)): ?>
                                <div class="col-12">
                                    <div class="no-posts">
                                        <div class="no-posts-icon">
                                            <i class="fas fa-search"></i>
                                        </div>
                                        <h3>Không tìm thấy bài viết</h3>
                                        <p>Không có bài viết nào phù hợp với từ khóa tìm kiếm của bạn.</p>
                                        <a href="/blog.php" class="btn btn-primary">Xem tất cả bài viết</a>
                                    </div>
                                </div>
                                <?php else: ?>
                                <?php foreach ($posts as $post): ?>
                                <div class="col-md-6">
                                    <article class="post-card">
                                        <div class="post-image">
                                            <a href="/blog-post.php?slug=<?php echo $post['slug']; ?>">
                                                <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($post['title']); ?>" 
                                                     class="img-fluid">
                                            </a>
                                            <div class="post-category">
                                                <a href="?category=<?php echo $post['category_id']; ?>">
                                                    <?php echo htmlspecialchars($post['category_name']); ?>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="post-content">
                                            <div class="post-meta">
                                                <span class="post-date">
                                                    <i class="far fa-calendar"></i>
                                                    <?php echo date('d/m/Y', strtotime($post['created_at'])); ?>
                                                </span>
                                                <span class="read-time">
                                                    <i class="far fa-clock"></i>
                                                    3 phút đọc
                                                </span>
                                            </div>
                                            <h3 class="post-title">
                                                <a href="/blog-post.php?slug=<?php echo $post['slug']; ?>">
                                                    <?php echo htmlspecialchars($post['title']); ?>
                                                </a>
                                            </h3>
                                            <p class="post-excerpt"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                                            <div class="post-footer">
                                                <div class="author-info">
                                                    <img src="<?php echo htmlspecialchars($post['author_avatar']); ?>" 
                                                         alt="<?php echo htmlspecialchars($post['author_name']); ?>" 
                                                         class="author-avatar">
                                                    <span class="author-name"><?php echo htmlspecialchars($post['author_name']); ?></span>
                                                </div>
                                                <a href="/blog-post.php?slug=<?php echo $post['slug']; ?>" class="read-more">
                                                    Đọc tiếp
                                                </a>
                                            </div>
                                        </div>
                                    </article>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav class="pagination-wrapper mt-5">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo $category_id ? '&category='.$category_id : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                        Trước
                                    </a>
                                </li>
                                <?php 
                                $start = max(1, $page - 2);
                                $end = min($total_pages, $page + 2);
                                for ($i = $start; $i <= $end; $i++): 
                                ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $category_id ? '&category='.$category_id : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo $category_id ? '&category='.$category_id : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">
                                        Sau
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>

                    <!-- Sidebar -->
                    <div class="col-lg-4">
                        <div class="blog-sidebar">
                            <!-- Recent Posts -->
                            <div class="sidebar-widget">
                                <h4 class="widget-title">Bài viết mới nhất</h4>
                                <div class="recent-posts">
                                    <?php foreach ($recent_posts as $recent): ?>
                                    <div class="recent-post">
                                        <div class="recent-image">
                                            <a href="/blog-post.php?slug=<?php echo $recent['slug']; ?>">
                                                <img src="<?php echo htmlspecialchars($recent['featured_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($recent['title']); ?>">
                                            </a>
                                        </div>
                                        <div class="recent-content">
                                            <div class="recent-category">
                                                <?php echo htmlspecialchars($recent['category_name']); ?>
                                            </div>
                                            <h6 class="recent-title">
                                                <a href="/blog-post.php?slug=<?php echo $recent['slug']; ?>">
                                                    <?php echo htmlspecialchars($recent['title']); ?>
                                                </a>
                                            </h6>
                                            <div class="recent-date">
                                                <i class="far fa-calendar"></i>
                                                <?php echo date('d/m/Y', strtotime($recent['created_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Newsletter -->
                            <!-- <div class="sidebar-widget newsletter-widget">
                                <div class="newsletter-content">
                                    <div class="newsletter-icon">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <h4 class="widget-title">Đăng ký nhận tin</h4>
                                    <p>Nhận thông tin y khoa mới nhất và các mẹo chăm sóc sức khỏe qua email</p>
                                    <form class="newsletter-form" action="/subscribe.php" method="POST">
                                        <div class="form-group">
                                            <input type="email" name="email" placeholder="Nhập email của bạn" 
                                                   class="form-control" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-paper-plane"></i>
                                            Đăng ký ngay
                                        </button>
                                    </form>
                                </div>
                            </div> -->

                            <!-- Popular Categories -->
                            <div class="sidebar-widget">
                                <h4 class="widget-title">Danh mục phổ biến</h4>
                                <div class="popular-categories">
                                    <?php foreach (array_slice($categories, 0, 6) as $category): ?>
                                    <a href="?category=<?php echo $category['category_id']; ?>" 
                                       class="category-item <?php echo $category_id == $category['category_id'] ? 'active' : ''; ?>">
                                        <span class="category-name"><?php echo htmlspecialchars($category['name']); ?></span>
                                        <span class="category-count"><?php echo $category['post_count']; ?></span>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS Animation -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <!-- Global Enhancements -->
    <!-- <script src="assets/js/global-enhancements.js"></script> -->
    <!-- Custom JS -->
    <script src="assets/js/blog.js"></script>
</body>
</html> 