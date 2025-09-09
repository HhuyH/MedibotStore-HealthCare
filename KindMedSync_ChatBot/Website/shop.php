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
    <title>Cửa hàng - MediBot Store Medical & Health Care</title>
    <meta name="description" content="Mua sắm các sản phẩm y tế chất lượng cao tại MediBot Store - thuốc, thực phẩm chức năng, thiết bị y tế và dược phẩm.">
    
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
        /* Cart notification styles */
        .cart-notification {
            position: fixed !important;
            z-index: 99999999 !important;
            top: 20px !important;
            right: 20px !important;
            min-width: 300px !important;
            max-width: 400px !important;
            background: white !important;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12) !important;
            border-radius: 12px !important;
            backdrop-filter: blur(10px) !important;
            border: 1px solid rgba(255,255,255,0.2) !important;
            font-weight: 500 !important;
            margin: 0 !important;
            padding: 1rem !important;
            display: flex !important;
            align-items: center !important;
            gap: 1rem !important;
            transform: translateY(-100%) !important;
            opacity: 0 !important;
            transition: all 0.3s ease !important;
        }

        .cart-notification.show {
            transform: translateY(0) !important;
            opacity: 1 !important;
        }

        .cart-notification .icon {
            width: 40px !important;
            height: 40px !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;s
            font-size: 1.2rem !important;
            flex-shrink: 0 !important;
        }

        .cart-notification.success .icon {
            background: #d4edda !important;
            color: #155724 !important;
        }

        .cart-notification.error .icon {
            background: #f8d7da !important;
            color: #721c24 !important;
        }

        .cart-notification .content {
            flex: 1 !important;
        }

        .cart-notification .title {
            font-weight: 600 !important;
            margin-bottom: 0.2rem !important;
            color: #2d3748 !important;
        }

        .cart-notification .message {
            color: #718096 !important;
            font-size: 0.9rem !important;
        }

        .cart-notification .close {
            width: 24px !important;
            height: 24px !important;
            border-radius: 50% !important;
            background: #edf2f7 !important;
            color: #718096 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
            font-size: 0.8rem !important;
            transition: all 0.2s ease !important;
        }

        .cart-notification .close:hover {
            background: #e2e8f0 !important;
            color: #2d3748 !important;
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
        
        /* 确保购物车徽章显示正确 */
        .cart-count {
            position: absolute !important;
            top: -10px !important;
            right: -10px !important;
            background: #f44336 !important;
            color: white !important;
            border-radius: 50% !important;
            min-width: 24px !important;
            height: 24px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 0.85rem !important;
            font-weight: 600 !important;
            border: 2px solid white !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
            line-height: 1 !important;
            padding: 2px 4px !important;
            z-index: 999 !important;
            transform: scale(1) !important;
            transition: transform 0.2s ease !important;
        }

        .cart-count:hover {
            transform: scale(1.1) !important;
        }

        /* Thêm hiệu ứng khi có thay đổi số lượng */
        @keyframes cartBounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }

        .cart-count.updating {
            animation: cartBounce 0.5s ease !important;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        /* Removed test badge */
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
                            MediBot Store Healthcare
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
                <div class="categories-grid">
                    <?php 
                    // Giới hạn chỉ hiển thị 4 danh mục đầu tiên
                    $displayCategories = array_slice($categories, 0, 4);
                    
                    foreach ($displayCategories as $category): 
                        $iconClass = 'fa-pills';
                        
                        switch(true) {
                            case stripos($category['name'], 'thiết bị') !== false:
                                $iconClass = 'fa-stethoscope';
                                break;
                            case stripos($category['name'], 'thuốc') !== false:
                                $iconClass = 'fa-prescription-bottle-alt';
                                break;
                            case stripos($category['name'], 'dược phẩm') !== false:
                                $iconClass = 'fa-flask';
                                break;
                            case stripos($category['name'], 'thực phẩm') !== false:
                                $iconClass = 'fa-apple-alt';
                                break;
                        }
                    ?>
                    <a href="/shop/products.php?category=<?php echo $category['category_id']; ?>" class="category-card">
                        <div class="category-card-inner">
                            <div class="category-icon">
                                <i class="fas <?php echo $iconClass; ?>"></i>
                            </div>
                            <div class="category-title">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </div>
                        </div>
                    </a>
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
                <div class="row g-2">
                    <?php foreach ($featuredProducts as $index => $product): ?>
                    <div class="col-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
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
                                    <div class="price-info">
                                    <span class="current-price"><?php echo number_format($product['discount_price'], 0, ',', '.'); ?>đ</span>
                                    <span class="original-price"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</span>
                                    </div>
                                    <?php /* Tạm ẩn phần hiển thị tiết kiệm
                                    <div class="discount-info">
                                        <span class="saved-amount">Tiết kiệm: <?php echo number_format($product['saved_amount'], 0, ',', '.'); ?>đ</span>
                                        <span class="saved-percent">-<?php echo $product['saved_percent']; ?>%</span>
                                    </div>
                                    */ ?>
                                    <?php else: ?>
                                    <span class="current-price"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($product['stock'] > 0): ?>
                                <!-- <div class="product-stock">
                                    <div class="stock-bar" style="--stock-percent: <?php echo min(($product['stock'] / 100) * 100, 100); ?>%">
                                        <span class="stock-text">Còn <?php echo $product['stock']; ?> sản phẩm</span>
                                    </div>
                                </div> -->
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
                        <h2 class="section-title">Tại sao mua sắm tại MediBot Store?</h2>
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
    <?php include 'includes/floating_chat.php'; ?>
    <?php include 'includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="/assets/js/shop.js"></script>
    <script src="/assets/js/search.js"></script>
    <script src="/assets/js/cart-new.js"></script>
    <!-- AOS Animation Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Hàm hiển thị thông báo giỏ hàng
        function showCartNotification(type, message) {
            // Xóa thông báo cũ nếu có
            $('.cart-notification').remove();
            
            // Tạo thông báo mới
            const notification = $(`
                <div class="cart-notification ${type}">
                    <div class="icon">
                        <i class="fas ${type === 'success' ? 'fa-check' : 'fa-exclamation-triangle'}"></i>
                    </div>
                    <div class="content">
                        <div class="title">${type === 'success' ? 'Thành công!' : 'Lỗi!'}</div>
                        <div class="message">${message}</div>
                    </div>
                    <div class="close">
                        <i class="fas fa-times"></i>
                    </div>
                </div>
            `);
            
            // Thêm vào body
            $('body').append(notification);
            
            // Hiển thị notification với animation
            setTimeout(() => notification.addClass('show'), 100);
            
            // Xử lý nút đóng
            notification.find('.close').on('click', () => {
                notification.removeClass('show');
                setTimeout(() => notification.remove(), 300);
            });
            
            // Tự động ẩn sau 3 giây
            setTimeout(() => {
                notification.removeClass('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        $(document).ready(function() {
            // Khởi tạo thư viện AOS
            AOS.init({
                duration: 800,
                once: true,
                offset: 100
            });

            // Gắn sự kiện click cho nút thêm vào giỏ
            $(document).on('click', '.add-to-cart', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $btn = $(this);
                const productId = $btn.data('id');
                const quantity = 1;

                // Thêm class loading
                $btn.addClass('loading').prop('disabled', true);
                
                // Gọi API thêm vào giỏ
                $.ajax({
                    url: '/api/cart/add.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        product_id: productId,
                        quantity: quantity
                    }),
                    success: function(response) {
                        if (response.success) {
                            // Hiển thị thông báo thành công
                            showCartNotification('success', 'Đã thêm vào giỏ hàng');
                            
                            // Cập nhật số lượng giỏ hàng
                            updateCartCount();
                        } else {
                            // Hiển thị lỗi
                            showCartNotification('error', response.message || 'Có lỗi xảy ra khi thêm vào giỏ hàng');
                        }
                    },
                    error: function() {
                        showCartNotification('error', 'Không thể kết nối đến server');
                    },
                    complete: function() {
                        // Xóa class loading
                        $btn.removeClass('loading').prop('disabled', false);
                    }
                });
            });
        });
    </script>
</body>
</html> 