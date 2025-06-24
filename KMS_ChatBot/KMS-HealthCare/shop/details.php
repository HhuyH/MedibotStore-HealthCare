<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions/product_functions.php';

// Lấy ID sản phẩm từ URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Lấy thông tin sản phẩm
$product = getProductDetails($product_id);

// Nếu không tìm thấy sản phẩm, chuyển hướng về trang shop
if (!$product) {
    header('Location: /shop.php');
    exit;
}

// Lấy sản phẩm liên quan
$relatedProducts = getRelatedProducts($product['category_id'], $product_id, 4);

// Lấy đánh giá sản phẩm
$reviews = getProductReviews($product_id);

// Tính số sao trung bình
$avgRating = 0;
$totalReviews = count($reviews);
if ($totalReviews > 0) {
    $totalStars = array_sum(array_column($reviews, 'rating'));
    $avgRating = round($totalStars / $totalReviews, 1);
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Qickmed</title>
    <meta name="description" content="<?php echo htmlspecialchars($product['description']); ?>">
    
    <!-- CSS -->
    <link rel="stylesheet" href="/assets/css/layout.css">
    <link rel="stylesheet" href="/assets/css/shop.css">
    <link rel="stylesheet" href="/assets/css/product-details.css">
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Swiper Slider -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="py-5">
        <div class="container">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/shop.php">Cửa hàng</a></li>
                    <li class="breadcrumb-item">
                        <a href="/shop/products.php?category=<?php echo $product['category_id']; ?>">
                            <?php echo htmlspecialchars($product['category_name']); ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?php echo htmlspecialchars($product['name']); ?>
                    </li>
                </ol>
            </nav>

            <!-- Product Details -->
            <div class="product-details">
                <div class="row">
                    <!-- Product Images -->
                    <div class="col-lg-6 mb-4 mb-lg-0">
                        <div class="product-gallery">
                            <div class="swiper product-gallery-main">
                                <div class="swiper-wrapper">
                                    <div class="swiper-slide">
                                        <img src="<?php echo htmlspecialchars($product['display_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             class="img-fluid">
                                    </div>
                                    <?php if (!empty($product['gallery_images'])): 
                                        $gallery = json_decode($product['gallery_images'], true);
                                        foreach ($gallery as $image): ?>
                                    <div class="swiper-slide">
                                        <img src="<?php echo htmlspecialchars($image); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             class="img-fluid">
                                    </div>
                                    <?php endforeach; endif; ?>
                                </div>
                                <div class="swiper-button-next"></div>
                                <div class="swiper-button-prev"></div>
                            </div>
                            <div class="swiper product-gallery-thumbs mt-3">
                                <div class="swiper-wrapper">
                                    <div class="swiper-slide">
                                        <img src="<?php echo htmlspecialchars($product['display_image']); ?>" 
                                             alt="Thumbnail"
                                             class="img-fluid">
                                    </div>
                                    <?php if (!empty($product['gallery_images'])): 
                                        foreach ($gallery as $image): ?>
                                    <div class="swiper-slide">
                                        <img src="<?php echo htmlspecialchars($image); ?>" 
                                             alt="Thumbnail"
                                             class="img-fluid">
                                    </div>
                                    <?php endforeach; endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Product Info -->
                    <div class="col-lg-6">
                        <div class="product-info">
                            <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                            
                            <div class="product-meta">
                                <div class="product-rating">
                                    <div class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $avgRating): ?>
                                                <i class="fas fa-star"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="rating-count">(<?php echo $totalReviews; ?> đánh giá)</span>
                                </div>
                                
                                <div class="product-stock <?php echo $product['stock'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                                    <?php if ($product['stock'] > 0): ?>
                                        <i class="fas fa-check-circle"></i> Còn hàng
                                    <?php else: ?>
                                        <i class="fas fa-times-circle"></i> Hết hàng
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="product-price">
                                <?php if ($product['discount_price']): ?>
                                    <span class="current-price">
                                        <?php echo number_format($product['discount_price'], 0, ',', '.'); ?>đ
                                    </span>
                                    <span class="original-price">
                                        <?php echo number_format($product['price'], 0, ',', '.'); ?>đ
                                    </span>
                                    <span class="discount-badge">
                                        -<?php echo $product['discount_percent']; ?>%
                                    </span>
                                <?php else: ?>
                                    <span class="current-price">
                                        <?php echo number_format($product['price'], 0, ',', '.'); ?>đ
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="product-description">
                                <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                            </div>

                            <?php if ($product['stock'] > 0): ?>
                            <div class="product-actions">
                                <div class="quantity-selector">
                                    <button class="btn-quantity minus">-</button>
                                    <input type="number" value="1" min="1" max="<?php echo $product['stock']; ?>" 
                                           class="quantity-input">
                                    <button class="btn-quantity plus">+</button>
                                </div>
                                
                                <button class="btn btn-primary add-to-cart" 
                                        data-id="<?php echo $product['product_id']; ?>">
                                    <i class="fas fa-cart-plus"></i>
                                    Thêm vào giỏ hàng
                                </button>
                                
                                <button class="btn btn-outline-primary add-to-wishlist"
                                        data-id="<?php echo $product['product_id']; ?>">
                                    <i class="far fa-heart"></i>
                                </button>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($product['active_ingredient'])): ?>
                            <div class="product-specs">
                                <h3>Thông tin sản phẩm</h3>
                                <table class="specs-table">
                                    <tr>
                                        <td>Hoạt chất:</td>
                                        <td><?php echo htmlspecialchars($product['active_ingredient']); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Dạng bào chế:</td>
                                        <td><?php echo htmlspecialchars($product['dosage_form']); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Đơn vị:</td>
                                        <td><?php echo htmlspecialchars($product['unit']); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Hướng dẫn sử dụng:</td>
                                        <td><?php echo nl2br(htmlspecialchars($product['usage_instructions'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Reviews -->
            <div class="product-reviews mt-5">
                <h3>Đánh giá sản phẩm</h3>
                
                <?php if ($totalReviews > 0): ?>
                <div class="reviews-summary">
                    <div class="overall-rating">
                        <div class="rating-number"><?php echo $avgRating; ?></div>
                        <div class="rating-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= $avgRating): ?>
                                    <i class="fas fa-star"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <div class="total-reviews"><?php echo $totalReviews; ?> đánh giá</div>
                    </div>
                </div>

                <div class="reviews-list">
                    <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <div class="reviewer-info">
                                <img src="<?php echo !empty($review['avatar']) ? 
                                    htmlspecialchars($review['avatar']) : 
                                    '/assets/images/default-avatar.png'; ?>" 
                                     alt="Avatar" class="reviewer-avatar">
                                <div class="reviewer-name">
                                    <?php echo htmlspecialchars($review['reviewer_name']); ?>
                                </div>
                            </div>
                            <div class="review-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= $review['rating']): ?>
                                        <i class="fas fa-star"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="review-content">
                            <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                        </div>
                        <?php if (!empty($review['images'])): 
                            $reviewImages = json_decode($review['images'], true); ?>
                        <div class="review-images">
                            <?php foreach ($reviewImages as $image): ?>
                            <img src="<?php echo htmlspecialchars($image); ?>" 
                                 alt="Review image" 
                                 class="review-image">
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <div class="review-footer">
                            <div class="review-date">
                                <?php echo date('d/m/Y', strtotime($review['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="no-reviews">
                    Chưa có đánh giá nào cho sản phẩm này
                </div>
                <?php endif; ?>
            </div>

            <!-- Related Products -->
            <?php if (!empty($relatedProducts)): ?>
            <div class="related-products mt-5">
                <h3 class="section-title">Sản phẩm liên quan</h3>
                <div class="row g-4">
                    <?php foreach ($relatedProducts as $relatedProduct): ?>
                    <div class="col-lg-3 col-md-4 col-6">
                        <a href="/shop/details.php?id=<?php echo $relatedProduct['product_id']; ?>" 
                           class="product-card">
                            <div class="product-image">
                                <img src="<?php echo htmlspecialchars($relatedProduct['display_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($relatedProduct['name']); ?>"
                                     class="img-fluid">
                                
                                <?php if ($relatedProduct['discount_percent'] > 0): ?>
                                <div class="discount-badge">
                                    -<?php echo $relatedProduct['discount_percent']; ?>%
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="product-info">
                                <h4 class="product-name">
                                    <?php echo htmlspecialchars($relatedProduct['name']); ?>
                                </h4>
                                
                                <div class="product-price">
                                    <?php if ($relatedProduct['discount_price']): ?>
                                    <div class="price-group">
                                        <span class="current-price">
                                            <?php echo number_format($relatedProduct['discount_price'], 0, ',', '.'); ?>đ
                                        </span>
                                        <span class="original-price">
                                            <?php echo number_format($relatedProduct['price'], 0, ',', '.'); ?>đ
                                        </span>
                                    </div>
                                    <?php else: ?>
                                    <div class="price-group">
                                        <span class="current-price">
                                            <?php echo number_format($relatedProduct['price'], 0, ',', '.'); ?>đ
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="product-status">
                                    <?php if ($relatedProduct['stock'] > 0): ?>
                                    <span class="status in-stock">Còn hàng</span>
                                    <?php else: ?>
                                    <span class="status out-of-stock">Hết hàng</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script src="/assets/js/cart-new.js"></script>
    <script>
        // Initialize Swiper
        const galleryThumbs = new Swiper('.product-gallery-thumbs', {
            spaceBetween: 10,
            slidesPerView: 4,
            freeMode: true,
            watchSlidesVisibility: true,
            watchSlidesProgress: true,
        });

        const galleryMain = new Swiper('.product-gallery-main', {
            spaceBetween: 10,
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            thumbs: {
                swiper: galleryThumbs
            }
        });

        // Quantity Selector
        document.querySelectorAll('.btn-quantity').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('.quantity-input');
                const currentValue = parseInt(input.value);
                const maxValue = parseInt(input.getAttribute('max'));
                
                if (this.classList.contains('plus') && currentValue < maxValue) {
                    input.value = currentValue + 1;
                } else if (this.classList.contains('minus') && currentValue > 1) {
                    input.value = currentValue - 1;
                }
            });
        });

        // Add to Cart - Sử dụng CartManager từ cart-new.js
        // Code này sẽ được xử lý bởi CartManager tự động

        // Add to Wishlist - Sử dụng CartManager từ cart-new.js
        // Code này sẽ được xử lý bởi CartManager tự động

        // Xử lý các nút trong product actions
        document.querySelectorAll('.btn-quickview').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.dataset.id;
                // Thêm code xử lý xem nhanh sản phẩm ở đây
                window.location.href = '/shop/details.php?id=' + productId;
            });
        });

        // Related products - Cart buttons sẽ được xử lý bởi CartManager từ cart-new.js
        // Không cần code riêng vì sử dụng class 'add-to-cart' và 'add-to-wishlist'
    </script>

    <style>
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 2rem;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .product-card {
            display: block;
            text-decoration: none;
            color: inherit;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-4px);
        }

        .product-image {
            position: relative;
            padding-top: 100%;
            background: #f8f9fa;
            overflow: hidden;
        }

        .product-image img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .discount-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ff4757;
            color: #fff;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .product-info {
            padding: 1rem;
        }

        .product-name {
            font-size: 0.95rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #2d3436;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 2.8em;
        }

        .product-price {
            margin-bottom: 0.5rem;
        }

        .price-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .current-price {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2d3436;
        }

        .original-price {
            font-size: 0.9rem;
            color: #b2bec3;
            text-decoration: line-through;
        }

        .product-status {
            font-size: 0.85rem;
        }

        .status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 500;
        }

        .status.in-stock {
            background: #e3fcef;
            color: #00b894;
        }

        .status.out-of-stock {
            background: #ffeaea;
            color: #ff4757;
        }

        @media (max-width: 768px) {
            .product-name {
                font-size: 0.9rem;
            }
            
            .current-price {
                font-size: 1rem;
            }
            
            .original-price {
                font-size: 0.85rem;
            }
        }
    </style>
</body>
</html> 