<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/blog_functions.php';

// Lấy slug từ URL
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    header('Location: /blog.php');
    exit;
}

// Lấy bài viết theo slug
$post = get_blog_post($slug);

if (!$post) {
    header('HTTP/1.0 404 Not Found');
    header('Location: /blog.php');
    exit;
}

// Tăng lượt xem
increment_post_views($post['post_id']);

// Lấy bài viết liên quan
$related_posts = get_related_posts($post['post_id'], $post['category_id'], 3);

// Lấy bài viết mới nhất cho sidebar
$recent_posts = get_recent_posts(5);

// Lấy categories để hiển thị
$categories = get_blog_categories();
?>  
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> - MediSync</title>
    <meta name="description" content="<?php echo htmlspecialchars($post['excerpt']); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($post['author_name']); ?>">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($post['title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($post['excerpt']); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($post['featured_image']); ?>">
    <meta property="og:type" content="article">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Blog CSS -->
    <link rel="stylesheet" href="assets/css/blog.css">
    <!-- Blog Post CSS -->
    <link rel="stylesheet" href="assets/css/blog-post.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <!-- Appointment Modal -->
    <?php include 'includes/appointment-modal.php'; ?>

    <main class="blog-post-main">
        <!-- Breadcrumb -->
        <section class="breadcrumb-section">
            <div class="container">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="/blog.php">Góc sức khỏe</a></li>
                        <li class="breadcrumb-item">
                            <a href="/blog.php?category=<?php echo $post['category_id']; ?>">
                                <?php echo htmlspecialchars($post['category_name']); ?>
                            </a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            <?php echo htmlspecialchars($post['title']); ?>
                        </li>
                    </ol>
                </nav>
            </div>
        </section>

        <!-- Blog Post Content -->
        <section class="blog-post-content">
            <div class="container">
                <div class="row">
                    <!-- Main Content -->
                    <div class="col-lg-8">
                        <article class="post-detail">
                            <!-- Post Header -->
                            <header class="post-header">
                                <div class="post-category">
                                    <a href="/blog.php?category=<?php echo $post['category_id']; ?>">
                                        <?php echo htmlspecialchars($post['category_name']); ?>
                                    </a>
                                </div>
                                <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
                                <div class="post-meta">
                                    <div class="author-info">
                                        
                                         <img src=" /assets/images/default-avatar.png" 
                                             alt="<?php echo htmlspecialchars($post['author_name']); ?>" 
                                             class="author-avatar">
                                        <div class="author-details">
                                            <span class="author-name"><?php echo htmlspecialchars($post['author_name']); ?></span>
                                            <span class="author-title">Chuyên gia y tế</span>
                                        </div>
                                    </div>
                                    <div class="post-info">
                                        <span class="post-date">
                                            <i class="far fa-calendar"></i>
                                            <?php echo date('d/m/Y', strtotime($post['created_at'])); ?>
                                        </span>
                                        <span class="read-time">
                                            <i class="far fa-clock"></i>
                                            <?php echo ceil(str_word_count(strip_tags($post['content'])) / 200); ?> phút đọc
                                        </span>
                                        <span class="view-count">
                                            <i class="far fa-eye"></i>
                                            <?php echo number_format($post['view_count']); ?> lượt xem
                                        </span>
                                    </div>
                                </div>
                            </header>

                            <!-- Featured Image -->
                            <div class="post-featured-image">
                                <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($post['title']); ?>" 
                                     class="img-fluid">
                            </div>

                            <!-- Post Content -->
                            <div class="post-content">
                                <?php echo $post['content']; ?>
                            </div>

                            <!-- Post Tags -->
                            <div class="post-tags">
                                <span class="tags-label">Thẻ:</span>
                                <a href="/blog.php?category=<?php echo $post['category_id']; ?>" class="tag">
                                    <?php echo htmlspecialchars($post['category_name']); ?>
                                </a>
                            </div>

                            <!-- Social Share -->
                            <div class="post-share">
                                <h5>Chia sẻ bài viết</h5>
                                <div class="share-buttons">
                                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                                       target="_blank" class="share-btn facebook">
                                        <i class="fab fa-facebook-f"></i>
                                        Facebook
                                    </a>
                                    <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode($post['title']); ?>&url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                                       target="_blank" class="share-btn twitter">
                                        <i class="fab fa-twitter"></i>
                                        Twitter
                                    </a>
                                    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                                       target="_blank" class="share-btn linkedin">
                                        <i class="fab fa-linkedin-in"></i>
                                        LinkedIn
                                    </a>
                                    <a href="mailto:?subject=<?php echo urlencode($post['title']); ?>&body=<?php echo urlencode('Xem bài viết này: ' . 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                                       class="share-btn email">
                                        <i class="fas fa-envelope"></i>
                                        Email
                                    </a>
                                </div>
                            </div>

                            <!-- Author Bio -->
                            <div class="author-bio">
                                <div class="author-avatar-large">
                                    <!-- <img src="<?php echo htmlspecialchars($post['author_avatar']); ?>"  -->
                                     <img src=" /assets/images/default-avatar.png" 
                                         alt="<?php echo htmlspecialchars($post['author_name']); ?>">
                                </div>
                                <div class="author-info">
                                    <h4 class="author-name"><?php echo htmlspecialchars($post['author_name']); ?></h4>
                                    <p class="author-title">Chuyên gia y tế</p>
                                    <p class="author-description">
                                        Với nhiều năm kinh nghiệm trong lĩnh vực y tế, tôi cam kết mang đến những thông tin chính xác và hữu ích nhất cho sức khỏe của bạn.
                                    </p>
                                </div>
                            </div>
                        </article>
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

                            <!-- Popular Categories -->
                            <div class="sidebar-widget">
                                <h4 class="widget-title">Danh mục phổ biến</h4>
                                <div class="popular-categories">
                                    <?php foreach (array_slice($categories, 0, 6) as $category): ?>
                                    <a href="/blog.php?category=<?php echo $category['category_id']; ?>" 
                                       class="category-item">
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

        <!-- Related Posts -->
        <?php if (!empty($related_posts)): ?>
        <section class="related-posts">
            <div class="container">
                <h3 class="section-title">Bài viết liên quan</h3>
                <div class="row g-4">
                    <?php foreach ($related_posts as $related): ?>
                    <div class="col-md-4">
                        <article class="post-card">
                            <div class="post-image">
                                <a href="/blog-post.php?slug=<?php echo $related['slug']; ?>">
                                    <img src="<?php echo htmlspecialchars($related['featured_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($related['title']); ?>" 
                                         class="img-fluid">
                                </a>
                                <div class="post-category">
                                    <a href="/blog.php?category=<?php echo $related['category_id']; ?>">
                                        <?php echo htmlspecialchars($related['category_name']); ?>
                                    </a>
                                </div>
                            </div>
                            <div class="post-content">
                                <div class="post-meta">
                                    <span class="post-date">
                                        <i class="far fa-calendar"></i>
                                        <?php echo date('d/m/Y', strtotime($related['created_at'])); ?>
                                    </span>
                                    <span class="read-time">
                                        <i class="far fa-clock"></i>
                                        <?php echo ceil(str_word_count(strip_tags($related['content'])) / 200); ?> phút đọc
                                    </span>
                                </div>
                                <h4 class="post-title">
                                    <a href="/blog-post.php?slug=<?php echo $related['slug']; ?>">
                                        <?php echo htmlspecialchars($related['title']); ?>
                                    </a>
                                </h4>
                                <div class="post-excerpt"><?php 
                                    $content = strip_tags($related['content']);
                                    $words = explode(' ', $content);
                                    if (count($words) > 20) {
                                        $content = implode(' ', array_slice($words, 0, 20)) . '...';
                                    }
                                    echo htmlspecialchars($content); 
                                ?></div>
                                <a href="/blog-post.php?slug=<?php echo $related['slug']; ?>" class="read-more">
                                    Đọc tiếp
                                </a>
                            </div>
                        </article>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
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
    <!-- Blog Post JS -->
    <script src="assets/js/blog-post.js"></script>
</body>
</html> 