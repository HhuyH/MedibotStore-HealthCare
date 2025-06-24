<?php
require_once '../includes/db.php';
require_once '../includes/functions/product_functions.php';
require_once '../includes/functions/format_helpers.php';

// Xử lý các tham số
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : PHP_FLOAT_MAX;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';

// Lấy khoảng giá cho slider
$price_range = getProductPriceRange();
if (!isset($_GET['max_price'])) {
    $max_price = $price_range['max'];
}

// Lấy danh sách sản phẩm với filter
$result = getFilteredProducts($min_price, $max_price, $sort, $category_id, $page);
$products = $result['products'];
$total_pages = $result['total_pages'];

// Lấy danh mục và sản phẩm phổ biến
$categories = getCategories();
$popular_products = getPopularProducts(3);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sản phẩm - QickMed</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/shop.css">
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- noUiSlider -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nouislider@14.6.3/distribute/nouislider.min.css">
    
    <!-- AOS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="py-5">
        <div class="container">
            <div class="row g-4">
                <!-- Sidebar Filters -->
                <div class="col-lg-3">
                    <!-- Categories -->
                    <div class="sidebar-widget mb-4" data-aos="fade-right">
                        <h4 class="widget-title">Danh mục sản phẩm</h4>
                        <div class="widget-content">
                            <ul class="category-list">
                                <li class="<?php echo !$category_id ? 'active' : ''; ?>">
                                    <a href="?">
                                        Tất cả sản phẩm
                                        <span class="count">(<?php echo $result['total']; ?>)</span>
                                    </a>
                                </li>
                                <?php foreach ($categories as $category): ?>
                                <li class="<?php echo $category_id == $category['category_id'] ? 'active' : ''; ?>">
                                    <a href="?category=<?php echo $category['category_id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                        <span class="count">(<?php echo getCategoryProductCount($category['category_id']); ?>)</span>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Price Filter -->
                    <div class="sidebar-widget mb-4" data-aos="fade-right" data-aos-delay="100">
                        <h4 class="widget-title">Lọc theo giá</h4>
                        <div class="widget-content">
                            <div id="price-slider"></div>
                            <div class="price-inputs mt-3">
                                <div class="input-group">
                                    <span class="input-group-text">₫</span>
                                    <input type="number" id="min-price" value="<?php echo $min_price; ?>" class="form-control">
                                </div>
                                <span class="separator">-</span>
                                <div class="input-group">
                                    <span class="input-group-text">₫</span>
                                    <input type="number" id="max-price" value="<?php echo $max_price; ?>" class="form-control">
                                </div>
                            </div>
                            <button id="price-filter" class="btn btn-primary w-100 mt-3">
                                <i class="fas fa-filter"></i> Lọc giá
                            </button>
                        </div>
                    </div>

                    <!-- Popular Products -->
                    <div class="sidebar-widget" data-aos="fade-right" data-aos-delay="200">
                        <h4 class="widget-title">Sản phẩm nổi bật</h4>
                        <div class="widget-content">
                            <?php foreach ($popular_products as $product): ?>
                            <div class="popular-product">
                                <img src="<?php echo htmlspecialchars($product['display_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     class="popular-product-img">
                                <div class="popular-product-info">
                                    <h5>
                                        <a href="/shop/details.php?id=<?php echo $product['product_id']; ?>">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </a>
                                    </h5>
                                    <div class="rating">
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
                                    <div class="price">
                                        <?php if (isset($product['discount_price'])): ?>
                                        <span class="original-price"><?php echo format_currency($product['price']); ?></span>
                                        <span class="current-price"><?php echo format_currency($product['discount_price']); ?></span>
                                        <?php else: ?>
                                        <span class="current-price"><?php echo format_currency($product['price']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Product Grid -->
                <div class="col-lg-9">
                    <!-- Toolbar -->
                    <div class="products-toolbar mb-4" data-aos="fade-up">
                        <div class="showing-results">
                            Hiển thị <?php echo count($products); ?> / <?php echo $result['total']; ?> sản phẩm
                        </div>
                        <div class="sorting">
                            <select class="form-select" id="sort-products">
                                <option value="default" <?php echo $sort == 'default' ? 'selected' : ''; ?>>Mới nhất</option>
                                <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Giá tăng dần</option>
                                <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Giá giảm dần</option>
                                <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Tên A-Z</option>
                                <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Tên Z-A</option>
                                <option value="rating" <?php echo $sort == 'rating' ? 'selected' : ''; ?>>Đánh giá cao</option>
                            </select>
                        </div>
                    </div>

                    <!-- Products Grid -->
                    <div class="row g-4">
                        <?php foreach ($products as $index => $product): ?>
                        <div class="col-md-4" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
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
                                        <span class="current-price"><?php echo format_currency($product['discount_price']); ?></span>
                                        <span class="original-price"><?php echo format_currency($product['price']); ?></span>
                                        <?php else: ?>
                                        <span class="current-price"><?php echo format_currency($product['price']); ?></span>
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

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <nav class="mt-4" data-aos="fade-up">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page-1; ?>&category=<?php echo $category_id; ?>&sort=<?php echo $sort; ?>&min_price=<?php echo $min_price; ?>&max_price=<?php echo $max_price; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&category=<?php echo $category_id; ?>&sort=<?php echo $sort; ?>&min_price=<?php echo $min_price; ?>&max_price=<?php echo $max_price; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>&category=<?php echo $category_id; ?>&sort=<?php echo $sort; ?>&min_price=<?php echo $min_price; ?>&max_price=<?php echo $max_price; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <!-- Quick View Modal -->
    <div class="modal fade" id="quickViewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-body">
                    <!-- Quick view content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/14.6.3/nouislider.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <!-- <script src="/assets/js/shop.js"></script> REMOVED TO AVOID CONFLICT WITH cart-new.js --></script>
    <script src="/assets/js/cart-new.js"></script>
    <script>
        // Khởi tạo AOS
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });

        // Khởi tạo price slider
        const priceSlider = document.getElementById('price-slider');
        if (priceSlider) {
            noUiSlider.create(priceSlider, {
                start: [<?php echo $min_price; ?>, <?php echo $max_price; ?>],
                connect: true,
                range: {
                    'min': <?php echo $price_range['min']; ?>,
                    'max': <?php echo $price_range['max']; ?>
                },
                format: {
                    to: value => Math.round(value),
                    from: value => Math.round(value)
                }
            });

            // Cập nhật input khi slider thay đổi
            priceSlider.noUiSlider.on('update', function(values, handle) {
                document.getElementById(handle ? 'max-price' : 'min-price').value = values[handle];
            });
        }

        // Xử lý sắp xếp
        document.getElementById('sort-products').addEventListener('change', function() {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', this.value);
            window.location.href = url.toString();
        });

        // Xử lý lọc giá
        document.getElementById('price-filter').addEventListener('click', function() {
            const url = new URL(window.location.href);
            url.searchParams.set('min_price', document.getElementById('min-price').value);
            url.searchParams.set('max_price', document.getElementById('max-price').value);
            window.location.href = url.toString();
        });

        // Xử lý quick view
        document.querySelectorAll('.quick-view').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.dataset.id;
                const modal = new bootstrap.Modal(document.getElementById('quickViewModal'));
                
                // Gọi API lấy thông tin sản phẩm
                fetch(`/api/product/${productId}`)
                    .then(response => response.json())
                    .then(data => {
                        // Hiển thị modal với thông tin sản phẩm
                        document.querySelector('#quickViewModal .modal-body').innerHTML = `
                            <div class="quick-view-content">
                                <!-- Content will be inserted here -->
                            </div>
                        `;
                        modal.show();
                    });
            });
        });
    </script>
</body>
</html> 