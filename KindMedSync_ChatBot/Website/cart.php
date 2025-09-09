<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions/logger.php';
require_once 'includes/functions/format_helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

    $user_id = $_SESSION['user_id'];
    
// Lấy thông tin giỏ hàng - query đơn giản và chính xác
$sql = "
    SELECT 
        oi.item_id, 
        oi.product_id, 
        oi.quantity, 
        oi.unit_price,
        p.name, 
        p.image_url, 
        p.stock, 
        p.price,
        o.order_id,
        COALESCE(
            (SELECT AVG(rating) FROM product_reviews WHERE product_id = oi.product_id), 
            0
        ) as avg_rating
    FROM order_items oi
    INNER JOIN orders o ON oi.order_id = o.order_id
    INNER JOIN products p ON oi.product_id = p.product_id
    WHERE o.user_id = ? AND o.status = 'cart'
    ORDER BY oi.item_id DESC
";

$stmt = $conn->prepare($sql);

// Kiểm tra lỗi nếu prepare thất bại
if (!$stmt) {
    die("SQL Prepare Error: " . $conn->error);
}

$stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
$cart_items = $result->fetch_all(MYSQLI_ASSOC);

// Debug logging
if (function_exists('writeLog')) {
    writeLog("CART_DEBUG: Found " . count($cart_items) . " cart items for user {$user_id}");
    foreach ($cart_items as $idx => $item) {
        writeLog("CART_DEBUG: Item {$idx} - ItemID: {$item['item_id']}, ProductID: {$item['product_id']}, Name: '{$item['name']}', Price: {$item['price']}, UnitPrice: {$item['unit_price']}, Qty: {$item['quantity']}");
    }
}

// Thêm debug trực tiếp trên trang
echo "<!-- DEBUG INFO -->";
echo "<!-- Cart Items Count: " . count($cart_items) . " -->";
foreach ($cart_items as $idx => $item) {
    echo "<!-- Item {$idx}: ItemID={$item['item_id']}, ProductID={$item['product_id']}, Name='{$item['name']}', Price={$item['price']} -->";
}

// Thêm thông tin giảm giá cho từng item
require_once __DIR__ . '/includes/functions/format_helpers.php';
foreach ($cart_items as $index => $item) {
    // Sử dụng hàm calculateDiscountPrice để check config
    $discount_info = calculateDiscountPrice($item['price'], $item['avg_rating']);
    $cart_items[$index]['discount_percent'] = $discount_info['discount_percent'];
    $cart_items[$index]['discount_price'] = $discount_info['discount_price'];
    
    // Xử lý ảnh mặc định
    if (empty($item['image_url'])) {
        $cart_items[$index]['image_url'] = '/assets/images/product-placeholder.jpg';
    }
}

// Unset any lingering references
unset($item);

// Tính tổng giỏ hàng
$cart_total = 0;
foreach ($cart_items as $item) {
    $cart_total += $item['quantity'] * $item['unit_price'];
}

// Debug log tổng tiền
if (function_exists('writeLog')) {
    writeLog("CART_DEBUG: Total calculated: {$cart_total}");
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng - MediBot Store Medical & Health Care</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Inter', sans-serif;
        }
        
        .cart-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .cart-header {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .cart-item {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .cart-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .product-info h5 {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .price {
            font-size: 1.1rem;
            font-weight: 700;
            color: #e74c3c;
        }
        
        .original-price {
            color: #95a5a6;
            text-decoration: line-through;
            font-size: 0.9rem;
            margin-left: 0.5rem;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .quantity-btn {
            width: 32px;
            height: 32px;
            border: none;
            background: #3498db;
            color: white;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .quantity-btn:hover {
            background: #2980b9;
            transform: scale(1.05);
        }
        
        .quantity-input {
            width: 60px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 0.25rem;
        }
        
        .remove-btn {
            color: #e74c3c;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .remove-btn:hover {
            color: #c0392b;
            transform: scale(1.1);
        }
        
        .cart-summary {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            position: sticky;
            top: 2rem;
        }
        
        .checkout-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background: linear-gradient(135deg, #1976d2, #1ec0f7) !important;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .checkout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .empty-cart i {
            font-size: 4rem;
            color: #bdc3c7;
            margin-bottom: 1rem;
        }
        
        .continue-shopping {
            background:rgb(22, 142, 222);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }
        .continue-shopping.fa-arrow-left{
            font-size: 10px !important;
        }
        
        .continue-shopping:hover {
            background: #2980b9;
            color: white;
            transform: translateY(-1px);
        }

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
            justify-content: center !important;
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
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="cart-container">
        <div class="cart-header">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="mb-0">
                    <i class="fas fa-shopping-cart me-2"></i>
                    Giỏ hàng của bạn
                </h1>
                <span class="badge bg-primary fs-6"><?php echo count($cart_items); ?> sản phẩm</span>
                    </div>
                </div>

                            <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                                        <h3>Giỏ hàng trống</h3>
                <p class="text-muted">Bạn chưa có sản phẩm nào trong giỏ hàng</p>
                <a href="/shop.php" class="continue-shopping">
                    <i class="fas"></i>
                    Tiếp tục mua sắm
                </a>
                                </div>
                            <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                                <?php foreach ($cart_items as $item): ?>
                    <!-- DEBUG: Item ID=<?php echo $item['item_id']; ?>, Product ID=<?php echo $item['product_id']; ?>, Name=<?php echo htmlspecialchars($item['name']); ?> -->
                    <div class="cart-item" data-product-id="<?php echo $item['product_id']; ?>">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     class="product-image">
                                    </div>
                            <div class="col-md-4">
                                <div class="product-info">
                                    <h5><?php echo htmlspecialchars($item['name']); ?> 
                                        <!-- <small style="color: #999;">(ID: <?php echo $item['product_id']; ?>)</small> -->
                                    </h5>
                                    <div class="price">
                                        <?php echo number_format($item['unit_price'], 0, ',', '.'); ?>đ
                                        <?php if ($item['discount_price'] && $item['price'] != $item['unit_price']): ?>
                                            <span class="original-price"><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">Còn <?php echo $item['stock']; ?> sản phẩm</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="quantity-controls">
                                    <button class="quantity-btn decrease-qty" data-product-id="<?php echo $item['product_id']; ?>">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" class="quantity-input" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="1" max="<?php echo $item['stock']; ?>"
                                           data-product-id="<?php echo $item['product_id']; ?>">
                                    <button class="quantity-btn increase-qty" data-product-id="<?php echo $item['product_id']; ?>">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-end">
                                    <div class="fw-bold fs-5 text-primary">
                                        <?php echo number_format($item['quantity'] * $item['unit_price'], 0, ',', '.'); ?>đ
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <i class="fas fa-trash remove-btn" 
                                   data-product-id="<?php echo $item['product_id']; ?>" 
                                   title="Xóa sản phẩm"></i>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="col-lg-4">
                    <div class="cart-summary">
                        <h4 class="mb-3">Tóm tắt đơn hàng</h4>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tạm tính:</span>
                            <span id="subtotal"><?php echo number_format($cart_total, 0, ',', '.'); ?>đ</span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Phí vận chuyển:</span>
                            <span id="shipping-fee"></span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Tổng cộng:</strong>
                            <strong class="text-primary fs-4" id="total"><?php echo number_format($cart_total, 0, ',', '.'); ?>đ</strong>
                        </div>
                        
                        <button class="checkout-btn" onclick="proceedToCheckout()">
                            <i class="fas fa-credit-card me-2"></i>
                            Thanh toán
                                </button>
                        
                        <a href="/shop.php" class="btn btn-outline-secondary w-100 mt-2">
                            <i class="fas fa-arrow-left me-2"></i>
                            Tiếp tục mua sắm
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Cập nhật số lượng
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                updateQuantity(this.dataset.productId, parseInt(this.value));
            });
        });
        
        // Tăng số lượng
        document.querySelectorAll('.increase-qty').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.dataset.productId;
                const input = document.querySelector(`input[data-product-id="${productId}"]`);
                const newQty = parseInt(input.value) + 1;
                const maxQty = parseInt(input.getAttribute('max'));
                
                if (newQty <= maxQty) {
                    input.value = newQty;
                    updateQuantity(productId, newQty);
                }
            });
        });
        
        // Giảm số lượng
        document.querySelectorAll('.decrease-qty').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.dataset.productId;
                const input = document.querySelector(`input[data-product-id="${productId}"]`);
                const newQty = parseInt(input.value) - 1;
                
                if (newQty >= 1) {
                    input.value = newQty;
                    updateQuantity(productId, newQty);
                }
            });
        });
        
        // Hàm hiển thị thông báo
        function showNotification(type, message) {
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

        // Xóa sản phẩm
        document.querySelectorAll('.remove-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.dataset.productId;
                const productName = this.closest('.cart-item').querySelector('.product-info h5').textContent.trim();
                removeFromCart(productId, productName);
            });
        });
        
        function updateQuantity(productId, quantity) {
            fetch('/api/cart.php?action=update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: quantity
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Reload để cập nhật tổng tiền
                } else {
                    alert(data.message);
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra');
            });
        }
        
        function removeFromCart(productId, productName) {
            fetch('/api/cart.php?action=remove', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('success', 'Đã xóa sản phẩm khỏi giỏ hàng');
                    const cartItem = document.querySelector(`.cart-item[data-product-id="${productId}"]`);
                    if (cartItem) {
                        cartItem.style.opacity = '0';
                        cartItem.style.transform = 'translateX(-20px)';
                        setTimeout(() => {
                            cartItem.remove();
                            // Kiểm tra nếu không còn sản phẩm nào thì reload
                            if (document.querySelectorAll('.cart-item').length === 0) {
                                setTimeout(() => location.reload(), 500);
                            } else {
                                // Cập nhật tổng tiền nếu còn sản phẩm
                                updateCartTotal();
                            }
                        }, 300);
                    }
                } else {
                    showNotification('error', data.message || 'Không thể xóa sản phẩm');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', 'Có lỗi xảy ra khi xóa sản phẩm');
            });
        }
        
        function proceedToCheckout() {
            window.location.href = '/checkout.php';
        }
    </script>
</body>
</html> 