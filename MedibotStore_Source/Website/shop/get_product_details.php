<?php
require_once '../includes/db.php';
require_once '../includes/functions/product_functions.php';

// Lấy product ID từ request
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    die('ID sản phẩm không hợp lệ');
}

// Truy vấn thông tin sản phẩm
$sql = "SELECT p.*, 
               pc.name as category_name,
               COALESCE(AVG(pr.rating), 0) as avg_rating,
               COUNT(pr.review_id) as review_count
        FROM products p 
        LEFT JOIN product_categories pc ON p.category_id = pc.category_id 
        LEFT JOIN product_reviews pr ON p.product_id = pr.product_id
        WHERE p.product_id = ?
        GROUP BY p.product_id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    die('Không tìm thấy sản phẩm');
}

// Tính giá khuyến mãi
// Sử dụng hàm calculateDiscountPrice để check config
require_once __DIR__ . '/../includes/functions/format_helpers.php';
$discount_info = calculateDiscountPrice($product['price'], $product['avg_rating']);
$product['discount_percent'] = $discount_info['discount_percent'];
$product['discount_price'] = $discount_info['discount_price'];

// Format lại rating
$product['avg_rating'] = number_format($product['avg_rating'], 1);

// Xử lý ảnh sản phẩm
$product['display_image'] = $product['image_url'] ?: '/assets/images/product-placeholder.jpg';
?>

<div class="quick-view-content row">
    <div class="col-md-6">
        <div class="product-image">
            <img src="<?php echo htmlspecialchars($product['display_image']); ?>" 
                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                 class="img-fluid">
            <?php if ($product['discount_percent'] > 0): ?>
                <div class="product-badge discount">
                    -<?php echo $product['discount_percent']; ?>%
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="product-details">
            <h2 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h2>
            
            <div class="product-category mb-2">
                <i class="fas fa-tag"></i>
                <?php echo htmlspecialchars($product['category_name'] ?? 'Chưa phân loại'); ?>
            </div>
            
            <div class="product-rating mb-3">
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
            
            <div class="product-price mb-3">
                <?php if ($product['discount_price']): ?>
                    <span class="current-price"><?php echo number_format($product['discount_price'], 0, ',', '.'); ?> VNĐ</span>
                    <span class="original-price"><?php echo number_format($product['price'], 0, ',', '.'); ?> VNĐ</span>
                <?php else: ?>
                    <span class="current-price"><?php echo number_format($product['price'], 0, ',', '.'); ?> VNĐ</span>
                <?php endif; ?>
            </div>
            
            <div class="product-description mb-4">
                <?php echo nl2br(htmlspecialchars($product['description'])); ?>
            </div>
            
            <?php if ($product['stock'] > 0): ?>
                <div class="product-stock mb-4">
                    <div class="stock-bar" style="--stock-percent: <?php echo min(($product['stock'] / 100) * 100, 100); ?>%">
                    </div>
                    <span class="stock-text">Còn <?php echo $product['stock']; ?> sản phẩm</span>
                </div>
                
                <div class="product-actions">
                    <button class="btn btn-primary add-to-cart" data-id="<?php echo $product['product_id']; ?>">
                        <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                    </button>
                    <button class="btn btn-outline-primary add-to-wishlist" data-id="<?php echo $product['product_id']; ?>">
                        <i class="fas fa-heart"></i>
                    </button>
                    <a href="details.php?id=<?php echo $product['product_id']; ?>" class="btn btn-outline-secondary">
                        Xem chi tiết
                    </a>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    Sản phẩm hiện đã hết hàng
                </div>
            <?php endif; ?>
        </div>
    </div>
</div> 