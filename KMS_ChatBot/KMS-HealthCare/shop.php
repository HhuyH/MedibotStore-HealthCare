<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions/product_functions.php';

// Lấy danh sách danh mục
$categories = getCategories();
// Lấy sản phẩm nổi bật
$featuredProducts = getFeaturedProducts(4);

// Lấy từ khóa tìm kiếm phổ biến
$popularSearches = [
    'Vitamin' => 'search.php?q=vitamin',
    'Thuốc bổ' => 'search.php?q=thuoc-bo',
    'Máy đo huyết áp' => 'search.php?q=may-do-huyet-ap',
    'Omega 3' => 'search.php?q=omega-3'
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cửa hàng - Qickmed Medical & Health Care</title>
    <meta name="description" content="Mua sắm các sản phẩm y tế chất lượng cao tại Qickmed - thuốc, thực phẩm chức năng, thiết bị y tế và dược phẩm.">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/shop.css">
    
    <!-- Notification Override CSS -->
    <style>
        .cart-notification {
            position: fixed !important;
            z-index: 999999 !important;
            top: 100px !important;
            right: 20px !important;
            min-width: 300px !important;
            max-width: 400px !important;
            box-shadow: 0 8px 24px rgba(0,0,0,0.25) !important;
            border-radius: 12px !important;
            backdrop-filter: blur(10px) !important;
            border: 1px solid rgba(255,255,255,0.2) !important;
            font-weight: 500 !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        .cart-notification.alert {
            position: fixed !important;
            z-index: 999999 !important;
            margin: 0 !important;
        }
        
        @media (max-width: 768px) {
            .cart-notification {
                top: 80px !important;
                left: 10px !important;
                right: 10px !important;
                min-width: auto !important;
                max-width: none !important;
            }
        }
        
        /* Force notification to be on top of everything */
        .cart-notification,
        .cart-notification.position-fixed,
        .cart-notification.alert,
        .cart-notification.alert.position-fixed {
            position: fixed !important;
            z-index: 999999 !important;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
     <!-- Appointment Modal -->
     <?php include 'includes/appointment-modal.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-bg-pattern"></div>
            <div class="container position-relative">
                <div class="row align-items-center min-vh-50">
                    <div class="col-lg-6 hero-content" data-aos="fade-right">
                        <div class="hero-badge">
                            <i class="fas fa-star-of-life"></i>
                            Qickmed Healthcare
                        </div>
                        <h1 class="hero-title">
                            Chăm Sóc Sức Khỏe 
                            <span class="text-primary">Tận Tâm</span>
                        </h1>
                            <p class="hero-subtitle">
                            Khám phá các sản phẩm y tế chất lượng cao, được chứng nhận và 
                            tin dùng bởi các chuyên gia hàng đầu
                        </p>
                        <div class="search-container">
                            <form action="search.php" method="GET" class="search-box" id="searchForm">
                                <input type="text" 
                                       name="q" 
                                       id="searchInput"
                                       class="search-input" 
                                       placeholder="Tìm kiếm sản phẩm..." 
                                       autocomplete="off"
                                       required>
                                <button type="submit" class="search-button">
                                        <i class="fas fa-search"></i>
                                    Tìm Kiếm
                                    </button>
                            </form>
                            <div class="search-suggestions" id="searchSuggestions"></div>
                            <div class="popular-searches">
                                <div class="popular-label">Tìm kiếm phổ biến:</div>
                                <div class="popular-tags">
                                    <?php foreach ($popularSearches as $text => $url): ?>
                                    <a href="<?php echo htmlspecialchars($url); ?>" class="popular-tag"><?php echo htmlspecialchars($text); ?></a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 hero-image" data-aos="fade-left">
                        <div class="image-wrapper">
                            <img src="/assets/images/thuoc_icon.jpg" alt="Medical Products" class="main-image">
                            <div class="floating-card card-1">
                                <i class="fas fa-pills"></i>
                                <span>100% Chính Hãng</span>
                            </div>
                            <div class="floating-card card-2">
                                <i class="fas fa-truck-fast"></i>
                                <span>Giao Hàng 24/7</span>
                            </div>
                            <div class="floating-card card-3">
                                <i class="fas fa-certificate"></i>
                                <span>Chứng Nhận Bộ Y Tế</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Categories Section -->
        <section class="categories-section">
            <div class="container">
                <!-- Section Header -->
                <div class="row">
                    <div class="col-lg-8 mx-auto text-center">
                        <div class="section-header">
                            <span class="section-badge">Danh Mục</span>
                            <h2 class="section-title">Danh Mục Sản Phẩm</h2>
                        <p class="section-description">
                                Khám phá các danh mục sản phẩm y tế chất lượng cao, được chọn lọc kỹ lưỡng để đáp ứng nhu cầu chăm sóc sức khỏe của bạn
                        </p>
                        </div>
                    </div>
                </div>

                <!-- Categories Grid -->
                <div class="row g-4 categories-grid">
                    <?php 
                    // Lặp qua mảng categories được truyền từ controller
                    foreach ($categories as $category): 
                        // Lấy số lượng sản phẩm trong danh mục
                        $productCount = getCategoryProductCount($category['category_id']);
                        
                        // Xác định icon mặc định
                        $iconClass = 'fa-pills';
                        $bgClass = 'bg-medicine';
                        
                        // Gán icon và background class dựa trên tên danh mục
                        switch(true) {
                            case stripos($category['name'], 'thiết bị') !== false:
                            $iconClass = 'fa-stethoscope';
                                $bgClass = 'bg-equipment';
                                break;
                            case stripos($category['name'], 'thuốc') !== false:
                            $iconClass = 'fa-prescription-bottle-alt';
                                $bgClass = 'bg-medicine';
                                break;
                            case stripos($category['name'], 'dược phẩm') !== false:
                            $iconClass = 'fa-flask';
                                $bgClass = 'bg-pharma';
                                break;
                            case stripos($category['name'], 'thực phẩm') !== false:
                                $iconClass = 'fa-apple-alt';
                                $bgClass = 'bg-supplement';
                                break;
                        }
                    ?>
                    <div class="col-xl-3 col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo $loop * 100; ?>">
                        <div class="category-card <?php echo $bgClass; ?>">
                            <div class="category-card-inner">
                            <div class="category-icon">
                                <i class="fas <?php echo $iconClass; ?>"></i>
                                </div>
                                <div class="category-content">
                                    <h4 class="category-title">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </h4>
                                    <p class="category-description">
                                        <?php echo htmlspecialchars($category['description']); ?>
                                    </p>
                                    <div class="category-meta">
                                        <span class="category-count">
                                            <i class="fas fa-box-open"></i>
                                            <?php echo $productCount; ?>+ sản phẩm
                                        </span>
                                    </div>
                                    <a href="/shop/category.php?id=<?php echo $category['category_id']; ?>" 
                                       class="btn btn-category">
                                        Xem chi tiết
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Featured Products -->
        <section class="featured-products">
            <div class="container">
                <!-- Section Header -->
                <div class="row">
                    <div class="col-lg-8 mx-auto text-center">
                        <div class="section-header">
                            <span class="section-badge">Sản Phẩm Nổi Bật</span>
                            <h2 class="section-title">Được Tin Dùng Nhiều Nhất</h2>
                        <p class="section-description">
                                Khám phá những sản phẩm chất lượng cao được đánh giá và tin dùng bởi hàng nghìn khách hàng
                        </p>
                        </div>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="featured-products-grid">
                    <?php foreach ($featuredProducts as $index => $product): ?>
                    <div class="product-item" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?php echo htmlspecialchars($product['display_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     class="img-fluid">
                                
                                <?php if ($product['discount_percent'] > 0): ?>
                                <div class="product-badge discount">
                                    -<?php echo $product['discount_percent']; ?>%
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($product['stock'] <= 0): ?>
                                <div class="product-badge out-of-stock">
                                    Hết hàng
                                </div>
                                <?php endif; ?>

                                <div class="product-actions">
                                    <button class="action-btn add-to-cart" 
                                            data-id="<?php echo $product['product_id']; ?>"
                                            <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>>
                                        <i class="fas fa-cart-plus"></i>
                                        <span class="tooltip">Thêm vào giỏ</span>
                                    </button>
                                    <button class="action-btn add-to-wishlist"
                                            data-id="<?php echo $product['product_id']; ?>">
                                        <i class="fas fa-heart"></i>
                                        <span class="tooltip">Yêu thích</span>
                                    </button>
                                    <button class="action-btn quick-view"
                                            data-id="<?php echo $product['product_id']; ?>">
                                        <i class="fas fa-eye"></i>
                                        <span class="tooltip">Xem nhanh</span>
                                    </button>
                                </div>
                            </div>

                            <div class="product-content">
                                <div class="product-category">
                                    <i class="fas fa-tag"></i>
                                    <?php echo htmlspecialchars($product['category_name']); ?>
                                </div>
                                
                                <h3 class="product-title">
                                    <a href="/shop/details.php?id=<?php echo $product['product_id']; ?>">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </a>
                                </h3>

                                <div class="product-rating">
                                    <div class="rating-stars">
                                    <?php 
                                    $rating = round($product['avg_rating']);
                                    for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $rating) {
                                                echo '<i class="fas fa-star"></i>';
                                            } else {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <span class="rating-count">(<?php echo $product['review_count']; ?> đánh giá)</span>
                                </div>

                                <div class="product-price">
                                    <?php if ($product['discount_price']): ?>
                                    <span class="current-price"><?php echo number_format($product['discount_price'], 0, ',', '.'); ?>đ</span>
                                    <span class="original-price"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</span>
                                    <?php else: ?>
                                    <span class="current-price"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($product['stock'] > 0): ?>
                                <div class="product-stock">
                                    <div class="stock-bar" style="--stock-percent: <?php echo min(($product['stock'] / 100) * 100, 100); ?>%">
                                        <span class="stock-text">Còn <?php echo $product['stock']; ?> sản phẩm</span>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- View All Button -->
                <div class="text-center mt-5">
                    <a href="/shop/products.php" class="btn btn-view-all">
                        Xem tất cả sản phẩm
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </section>

        <!-- Why Choose Us -->
        <section class="features-section py-5">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8 mx-auto text-center mb-5">
                        <h2 class="section-title">Tại sao mua sắm tại Qickmed?</h2>
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-lg-3 col-md-6">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h5>Chính hãng 100%</h5>
                            <p>Cam kết sản phẩm chính hãng từ các nhà sản xuất uy tín</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-shipping-fast"></i>
                            </div>
                            <h5>Giao hàng nhanh</h5>
                            <p>Giao hàng trong 24h tại TP.HCM và 2-3 ngày toàn quốc</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-headset"></i>
                            </div>
                            <h5>Tư vấn 24/7</h5>
                            <p>Dược sĩ chuyên nghiệp tư vấn sử dụng thuốc mọi lúc</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-undo-alt"></i>
                            </div>
                            <h5>Đổi trả dễ dàng</h5>
                            <p>Chính sách đổi trả linh hoạt trong vòng 30 ngày</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="/assets/js/shop.js"></script>
    <script src="/assets/js/search.js"></script>
    <script src="/assets/js/cart-new.js"></script>
    <!-- AOS Animation Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <!-- Global Enhancements -->
    <script src="assets/js/global-enhancements.js"></script>
    <script>
        // Khởi tạo thư viện AOS để tạo hiệu ứng animation khi scroll
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });
    </script>
</body>
</html> 