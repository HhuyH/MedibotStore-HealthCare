<?php
require_once 'includes/db.php';
require_once 'includes/functions/product_functions.php';

// Lấy sản phẩm nổi bật
$featuredProducts = getFeaturedProducts(2);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Shop Buttons</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/shop.css">
    <!-- AOS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <h2>🔧 Debug Shop Buttons</h2>
        
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <strong>Testing Environment:</strong> Same as shop.php with all CSS and JS loaded
                </div>
            </div>
        </div>

        <!-- Test 1: Simple Button (like debug_button.php) -->
        <div class="row mb-4">
            <div class="col-12">
                <h3>Test 1: Simple Button (Working)</h3>
                <button class="btn btn-primary add-to-cart" data-id="1">
                    <i class="fas fa-cart-plus"></i> Simple Add to Cart (Product ID: 1)
                </button>
            </div>
        </div>

        <!-- Test 2: Exact Shop.php Structure -->
        <div class="row mb-4">
            <div class="col-12">
                <h3>Test 2: Exact Shop.php Structure</h3>
                <div class="featured-products-grid">
                    <?php foreach ($featuredProducts as $index => $product): ?>
                    <div class="product-item" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?php echo htmlspecialchars($product['display_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     class="img-fluid">
                                
                                <div class="product-actions">
                                    <button class="action-btn add-to-cart" 
                                            data-id="<?php echo $product['product_id']; ?>"
                                            <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>>
                                        <i class="fas fa-cart-plus"></i>
                                        <span class="tooltip">Thêm vào giỏ</span>
                                    </button>
                                </div>
                            </div>

                            <div class="product-content">
                                <h3 class="product-title">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </h3>
                                <div class="product-price">
                                    <?php if ($product['discount_price']): ?>
                                    <span class="current-price"><?php echo number_format($product['discount_price'], 0, ',', '.'); ?>đ</span>
                                    <span class="original-price"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</span>
                                    <?php else: ?>
                                    <span class="current-price"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Test 3: Different Button Styles -->
        <div class="row mb-4">
            <div class="col-12">
                <h3>Test 3: Different Button Styles</h3>
                <button class="btn btn-success add-to-cart" data-id="2">Regular Class</button>
                <button class="action-btn add-to-cart" data-id="3">Action Btn Class</button>
                <button class="btn action-btn add-to-cart" data-id="4">Combined Classes</button>
            </div>
        </div>

        <!-- Status Display -->
        <div class="row">
            <div class="col-md-6">
                <h3>Cart Status</h3>
                <div id="cart-status" class="alert alert-secondary">Loading...</div>
            </div>
            <div class="col-md-6">
                <h3>Debug Logs</h3>
                <div id="debug-logs" style="max-height: 300px; overflow-y: auto;"></div>
            </div>
        </div>
    </div>

    <!-- Scripts - EXACT SAME ORDER AS SHOP.PHP -->
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
        // Khởi tạo thư viện AOS 
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });

        // Debug logging
        function logDebug(message) {
            const debugLogs = document.getElementById('debug-logs');
            const logElement = document.createElement('div');
            logElement.className = 'alert alert-info py-1 mb-1';
            logElement.innerHTML = `<small>${new Date().toLocaleTimeString()}</small> ${message}`;
            debugLogs.appendChild(logElement);
            debugLogs.scrollTop = debugLogs.scrollHeight;
        }

        // Monitor click events
        document.addEventListener('click', function(e) {
            if (e.target.closest('.add-to-cart')) {
                const button = e.target.closest('.add-to-cart');
                const productId = button.dataset.id;
                logDebug(`🖱️ CLICKED: Add to cart button for product ${productId}`);
                logDebug(`📍 BUTTON CLASSES: ${button.className}`);
                logDebug(`📍 BUTTON DATA-ID: ${productId}`);
                
                // Check if CartManager exists
                if (window.CartManager) {
                    logDebug(`✅ CartManager exists`);
                } else {
                    logDebug(`❌ CartManager not found`);
                }
            }
        }, true); // Use capture phase

        // Check cart status periodically
        async function checkCartStatus() {
            try {
                const response = await fetch('/api/cart.php');
                const data = await response.json();
                
                document.getElementById('cart-status').innerHTML = `
                    <strong>Cart Items:</strong> ${data.items ? data.items.length : 0}<br>
                    <strong>Total:</strong> ${data.total || 0}đ<br>
                    <strong>Success:</strong> ${data.success}
                `;
            } catch (error) {
                document.getElementById('cart-status').innerHTML = `<span class="text-danger">Error: ${error.message}</span>`;
            }
        }

        // Initial cart check
        checkCartStatus();
        
        // Re-check cart every 3 seconds after click
        let recheckTimer;
        document.addEventListener('click', function(e) {
            if (e.target.closest('.add-to-cart')) {
                clearTimeout(recheckTimer);
                recheckTimer = setTimeout(() => {
                    logDebug('🔄 Rechecking cart...');
                    checkCartStatus();
                }, 3000);
            }
        });

        logDebug('🚀 Debug page loaded with shop.php environment');
    </script>
</body>
</html> 