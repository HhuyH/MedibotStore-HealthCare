// Cart Page JavaScript - Database Version

document.addEventListener('DOMContentLoaded', function() {
    initializeCart();
});

// Initialize cart functionality
function initializeCart() {
    initializeQuantityControls();
    initializeRemoveButtons();
    initializeCouponCode();
    initializePaymentMethods();
    initializeCheckout();
    loadCartCount();
}

// Load cart count for header
function loadCartCount() {
    fetch('/api/cart/get.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartCount(data.cart_count || 0);
            }
        })
        .catch(error => {
            console.error('Error loading cart count:', error);
        });
}

// Initialize quantity controls
function initializeQuantityControls() {
    // Plus button
    document.addEventListener('click', (e) => {
        if (e.target.closest('.quantity-btn.plus')) {
            const btn = e.target.closest('.quantity-btn.plus');
            const productId = btn.getAttribute('data-id');
            const input = document.querySelector(`.quantity-input[data-id="${productId}"]`);
            const currentQuantity = parseInt(input.value);
            const maxQuantity = parseInt(input.getAttribute('max'));
            
            if (currentQuantity < maxQuantity) {
                updateQuantityInDB(productId, currentQuantity + 1);
            } else {
                showNotification('Số lượng vượt quá tồn kho', 'warning');
            }
        }
    });
    
    // Minus button
    document.addEventListener('click', (e) => {
        if (e.target.closest('.quantity-btn.minus')) {
            const btn = e.target.closest('.quantity-btn.minus');
            const productId = btn.getAttribute('data-id');
            const input = document.querySelector(`.quantity-input[data-id="${productId}"]`);
            const currentQuantity = parseInt(input.value);
            
            if (currentQuantity > 1) {
                updateQuantityInDB(productId, currentQuantity - 1);
            }
        }
    });
    
    // Input change
    document.addEventListener('change', (e) => {
        if (e.target.classList.contains('quantity-input')) {
            const input = e.target;
            const productId = input.getAttribute('data-id');
            const newQuantity = parseInt(input.value) || 1;
            const maxQuantity = parseInt(input.getAttribute('max'));
            
            if (newQuantity > maxQuantity) {
                input.value = maxQuantity;
                showNotification('Số lượng vượt quá tồn kho', 'warning');
                return;
            }
            
            if (newQuantity < 1) {
                input.value = 1;
                return;
            }
            
            updateQuantityInDB(productId, newQuantity);
        }
    });
}

// Update quantity in database
function updateQuantityInDB(productId, quantity) {
    fetch('/api/cart/update.php', {
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
            // Update UI
            const input = document.querySelector(`.quantity-input[data-id="${productId}"]`);
            if (input) {
                input.value = quantity;
            }
            
            // Update item total
            const cartItem = document.querySelector(`.cart-item[data-id="${productId}"]`);
            if (cartItem) {
                const priceElement = cartItem.querySelector('.current-price');
                const totalElement = cartItem.querySelector('.total-price');
                if (priceElement && totalElement) {
                    const unitPrice = parseFloat(priceElement.textContent.replace(/[^\d]/g, ''));
                    const newTotal = unitPrice * quantity;
                    totalElement.textContent = formatPrice(newTotal);
                }
            }
            
            // Update cart summary
            updateCartSummary(data.total, data.cart_count);
            updateCartCount(data.cart_count);
            
            showNotification('Đã cập nhật giỏ hàng', 'success');
        } else {
            showNotification(data.message || 'Có lỗi xảy ra', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Có lỗi xảy ra', 'error');
    });
}

// Initialize remove buttons
function initializeRemoveButtons() {
    document.addEventListener('click', (e) => {
        if (e.target.closest('.remove-item')) {
            const btn = e.target.closest('.remove-item');
            const productId = btn.getAttribute('data-id');
            
            if (confirm('Bạn có chắc muốn xóa sản phẩm này khỏi giỏ hàng?')) {
                removeItemFromDB(productId);
            }
        }
    });
}

// Remove item from database
function removeItemFromDB(productId) {
    fetch('/api/cart/update.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: 0
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove item from UI
            const cartItem = document.querySelector(`.cart-item[data-id="${productId}"]`);
            if (cartItem) {
                cartItem.remove();
            }
            
            // Check if cart is empty
            const remainingItems = document.querySelectorAll('.cart-item');
            if (remainingItems.length === 0) {
                showEmptyCart();
            }
            
            // Update cart summary
            updateCartSummary(data.total, data.cart_count);
            updateCartCount(data.cart_count);
            
            showNotification('Đã xóa sản phẩm khỏi giỏ hàng', 'success');
        } else {
            showNotification(data.message || 'Có lỗi xảy ra', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Có lỗi xảy ra', 'error');
    });
}

// Show empty cart
function showEmptyCart() {
    const cartContainer = document.getElementById('cartItems');
    if (cartContainer) {
        cartContainer.innerHTML = `
            <div class="empty-cart" id="emptyCart">
                <div class="text-center py-5">
                    <i class="fas fa-shopping-cart empty-icon"></i>
                    <h3>Giỏ hàng trống</h3>
                    <p>Hãy thêm sản phẩm vào giỏ hàng để tiếp tục mua sắm</p>
                    <a href="/shop.php" class="btn btn-primary">Tiếp tục mua sắm</a>
                </div>
            </div>
        `;
    }
    
    // Hide cart summary
    const cartSummary = document.querySelector('.cart-summary');
    if (cartSummary) {
        cartSummary.style.display = 'none';
    }
}

// Update cart summary
function updateCartSummary(total, cartCount) {
    const subtotalElement = document.getElementById('subtotal');
    const totalElement = document.getElementById('total');
    
    if (subtotalElement) {
        subtotalElement.textContent = formatPrice(total || 0);
    }
    
    if (totalElement) {
        totalElement.textContent = formatPrice(total || 0);
    }
}

// Initialize coupon code
function initializeCouponCode() {
    const applyBtn = document.querySelector('.coupon-section button');
    if (applyBtn) {
        applyBtn.addEventListener('click', applyCoupon);
    }
}

// Apply coupon
function applyCoupon() {
    const couponInput = document.getElementById('couponCode');
    const code = couponInput.value.trim();
    
    if (!code) {
        showNotification('Vui lòng nhập mã giảm giá', 'warning');
        return;
    }
    
    // TODO: Implement coupon API
    showNotification('Tính năng mã giảm giá sẽ được cập nhật sớm', 'info');
}

// Initialize payment methods
function initializePaymentMethods() {
    const paymentInputs = document.querySelectorAll('input[name="payment"]');
    paymentInputs.forEach(input => {
        input.addEventListener('change', function() {
            // Update UI based on selected payment method
            console.log('Selected payment method:', this.value);
        });
    });
}

// Initialize checkout
function initializeCheckout() {
    const checkoutBtn = document.querySelector('button[onclick="proceedToCheckout()"]');
    if (checkoutBtn) {
        checkoutBtn.removeAttribute('onclick');
        checkoutBtn.addEventListener('click', proceedToCheckout);
    }
}

// Proceed to checkout
function proceedToCheckout() {
    // Get cart data from server
    fetch('/api/cart/get.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.items.length > 0) {
                // Create form to send cart data to checkout page
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'checkout.php';
                
                const cartInput = document.createElement('input');
                cartInput.type = 'hidden';
                cartInput.name = 'cart_data';
                cartInput.value = JSON.stringify(data.items);
                
                form.appendChild(cartInput);
                document.body.appendChild(form);
                form.submit();
            } else {
                showNotification('Giỏ hàng trống! Vui lòng thêm sản phẩm trước khi thanh toán.', 'warning');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Có lỗi xảy ra', 'error');
        });
}

// Format price
function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN').format(price) + 'đ';
}

// Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    const icons = {
        success: 'check-circle',
        error: 'exclamation-circle',
        info: 'info-circle',
        warning: 'exclamation-triangle'
    };
    
    const colors = {
        success: '#28a745',
        error: '#dc3545',
        info: '#17a2b8',
        warning: '#ffc107'
    };
    
    notification.innerHTML = `
        <i class="fas fa-${icons[type]} me-2"></i>
        ${message}
    `;
    
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: ${colors[type]};
        color: white;
        padding: 12px 20px;
        border-radius: 25px;
        z-index: 1000;
        animation: slideInRight 0.3s ease;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        max-width: 400px;
        word-wrap: break-word;
    `;
    
    // Add animation styles
    if (!document.getElementById('notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(notification);
    
    // Remove after 5 seconds
    setTimeout(() => {
        notification.style.animation = 'slideInRight 0.3s ease reverse';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

// Update cart count in header
function updateCartCount(count) {
    const cartCountElement = document.querySelector('.cart-count');
    if (cartCountElement) {
        cartCountElement.textContent = count;
        
        if (count > 0) {
            cartCountElement.style.display = 'flex';
        } else {
            cartCountElement.style.display = 'none';
        }
    }
}

// Add to cart function for product pages
function addToCart(productId, quantity = 1) {
    fetch('/api/cart/add.php', {
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
            showNotification('Đã thêm sản phẩm vào giỏ hàng', 'success');
            updateCartCount(data.cart_count);
        } else {
            showNotification(data.message || 'Có lỗi xảy ra', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Có lỗi xảy ra', 'error');
    });
}

// Make addToCart available globally
window.addToCart = addToCart; 