// Cart Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize cart functionality
    initializeCart();
    
    // Initialize quantity controls
    initializeQuantityControls();
    
    // Initialize coupon functionality
    initializeCouponCode();
    
    // Initialize payment methods
    initializePaymentMethods();
    
    // Initialize checkout process
    initializeCheckout();
    
    // Initialize animations
    initializeAnimations();
});

// Cart data management
let cartData = {
    items: [],
    subtotal: 0,
    discount: 0,
    shipping: 30000,
    total: 0
};

// Initialize cart
function initializeCart() {
    // Load cart from localStorage
    loadCartData();
    
    // Render cart items
    renderCartItems();
    
    // Update cart summary
    updateCartSummary();
    
    // Initialize remove buttons
    initializeRemoveButtons();
}

// Load cart data from localStorage
function loadCartData() {
    const savedCart = localStorage.getItem('qickmed_cart');
    if (savedCart) {
        try {
            const parsedCart = JSON.parse(savedCart);
            cartData.items = parsedCart || [];
        } catch (e) {
            console.error('Error parsing cart data:', e);
            cartData.items = getDefaultCartItems();
        }
    } else {
        cartData.items = getDefaultCartItems();
    }
}

// Get default cart items for demo
function getDefaultCartItems() {
    return [
        {
            id: '1',
            name: 'Vitamin C 1000mg',
            price: 320000,
            quantity: 2,
            image: '/assets/images/product-1.jpg',
            category: 'Vitamin'
        },
        {
            id: '2',
            name: 'Máy đo huyết áp Omron',
            price: 1250000,
            quantity: 1,
            image: '/assets/images/product-2.jpg',
            category: 'Thiết bị y tế'
        },
        {
            id: '3',
            name: 'Paracetamol 500mg',
            price: 25000,
            quantity: 3,
            image: '/assets/images/product-3.jpg',
            category: 'Thuốc'
        }
    ];
}

// Render cart items
function renderCartItems() {
    const cartContainer = document.querySelector('.cart-items');
    if (!cartContainer) return;
    
    if (cartData.items.length === 0) {
        cartContainer.innerHTML = `
            <div class="empty-cart text-center py-5">
                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                <h4>Giỏ hàng trống</h4>
                <p class="text-muted">Bạn chưa có sản phẩm nào trong giỏ hàng</p>
                <a href="/shop.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag me-2"></i>Tiếp tục mua sắm
                </a>
            </div>
        `;
        return;
    }
    
    const itemsHTML = cartData.items.map(item => `
        <div class="cart-item" data-id="${item.id}">
            <div class="row align-items-center">
                <div class="col-md-2">
                    <div class="product-image">
                        <img src="${item.image}" alt="${item.name}" class="img-fluid rounded">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="product-info">
                        <h5 class="product-name">${item.name}</h5>
                        <p class="product-category text-muted">${item.category}</p>
                        <div class="product-rating">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-muted"></i>
                            <span class="ms-2 text-muted">(4.0)</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="quantity-controls">
                        <button class="quantity-btn minus" data-id="${item.id}">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" class="quantity-input" value="${item.quantity}" min="1" data-id="${item.id}">
                        <button class="quantity-btn plus" data-id="${item.id}">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="product-price text-center">
                        <div class="unit-price text-muted">${formatPrice(item.price)}</div>
                        <div class="total-price h5">${formatPrice(item.price * item.quantity)}</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="item-actions text-center">
                        <button class="btn btn-outline-danger btn-sm remove-item" data-id="${item.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                        <button class="btn btn-outline-secondary btn-sm ms-2 save-later" data-id="${item.id}">
                            <i class="fas fa-heart"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
    
    cartContainer.innerHTML = itemsHTML;
}

// Initialize quantity controls
function initializeQuantityControls() {
    document.addEventListener('click', (e) => {
        if (e.target.closest('.quantity-btn')) {
            const btn = e.target.closest('.quantity-btn');
            const itemId = btn.getAttribute('data-id');
            const isPlus = btn.classList.contains('plus');
            
            updateQuantity(itemId, isPlus ? 1 : -1);
        }
    });
    
    document.addEventListener('change', (e) => {
        if (e.target.classList.contains('quantity-input')) {
            const input = e.target;
            const itemId = input.getAttribute('data-id');
            const newQuantity = parseInt(input.value) || 1;
            
            setQuantity(itemId, newQuantity);
        }
    });
}

// Update quantity
function updateQuantity(itemId, change) {
    const item = cartData.items.find(item => item.id === itemId);
    if (!item) return;
    
    item.quantity = Math.max(1, item.quantity + change);
    
    // Update input value
    const input = document.querySelector(`.quantity-input[data-id="${itemId}"]`);
    if (input) {
        input.value = item.quantity;
    }
    
    updateItemTotal(itemId);
    updateCartSummary();
    saveCartData();
    
    // Add animation
    animateQuantityChange(itemId);
}

// Set quantity
function setQuantity(itemId, quantity) {
    const item = cartData.items.find(item => item.id === itemId);
    if (!item) return;
    
    item.quantity = Math.max(1, quantity);
    
    updateItemTotal(itemId);
    updateCartSummary();
    saveCartData();
}

// Update item total
function updateItemTotal(itemId) {
    const item = cartData.items.find(item => item.id === itemId);
    if (!item) return;
    
    const totalPriceElement = document.querySelector(`[data-id="${itemId}"] .total-price`);
    if (totalPriceElement) {
        totalPriceElement.textContent = formatPrice(item.price * item.quantity);
    }
}

// Initialize remove buttons
function initializeRemoveButtons() {
    document.addEventListener('click', (e) => {
        if (e.target.closest('.remove-item')) {
            const btn = e.target.closest('.remove-item');
            const itemId = btn.getAttribute('data-id');
            
            removeItem(itemId);
        }
        
        if (e.target.closest('.save-later')) {
            const btn = e.target.closest('.save-later');
            const itemId = btn.getAttribute('data-id');
            
            saveForLater(itemId);
        }
    });
}

// Remove item from cart
function removeItem(itemId) {
    const itemElement = document.querySelector(`[data-id="${itemId}"]`);
    if (!itemElement) return;
    
    // Add remove animation
    itemElement.style.transform = 'translateX(-100%)';
    itemElement.style.opacity = '0';
    
    setTimeout(() => {
        cartData.items = cartData.items.filter(item => item.id !== itemId);
        renderCartItems();
        updateCartSummary();
        saveCartData();
        
        showNotification('Đã xóa sản phẩm khỏi giỏ hàng', 'success');
    }, 300);
}

// Save for later
function saveForLater(itemId) {
    const item = cartData.items.find(item => item.id === itemId);
    if (!item) return;
    
    // Save to wishlist (simulate)
    const wishlist = JSON.parse(localStorage.getItem('qickmed_wishlist') || '[]');
    wishlist.push(item);
    localStorage.setItem('qickmed_wishlist', JSON.stringify(wishlist));
    
    // Remove from cart
    removeItem(itemId);
    
    showNotification('Đã lưu sản phẩm vào danh sách yêu thích', 'info');
}

// Update cart summary
function updateCartSummary() {
    cartData.subtotal = cartData.items.reduce((total, item) => total + (item.price * item.quantity), 0);
    cartData.total = cartData.subtotal - cartData.discount + cartData.shipping;
    
    // Update summary display
    const subtotalElement = document.querySelector('.subtotal-amount');
    const discountElement = document.querySelector('.discount-amount');
    const shippingElement = document.querySelector('.shipping-amount');
    const totalElement = document.querySelector('.total-amount');
    
    if (subtotalElement) subtotalElement.textContent = formatPrice(cartData.subtotal);
    if (discountElement) discountElement.textContent = formatPrice(cartData.discount);
    if (shippingElement) shippingElement.textContent = cartData.shipping === 0 ? 'Miễn phí' : formatPrice(cartData.shipping);
    if (totalElement) totalElement.textContent = formatPrice(cartData.total);
    
    // Update item count
    const itemCount = cartData.items.reduce((total, item) => total + item.quantity, 0);
    const itemCountElements = document.querySelectorAll('.cart-item-count');
    itemCountElements.forEach(element => {
        element.textContent = itemCount;
    });
}

// Initialize coupon functionality
function initializeCouponCode() {
    const couponForm = document.querySelector('.coupon-form');
    if (!couponForm) return;
    
    couponForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const couponInput = couponForm.querySelector('input[name="coupon"]');
        const couponCode = couponInput.value.trim().toUpperCase();
        
        applyCoupon(couponCode);
    });
}

// Apply coupon
function applyCoupon(code) {
    const validCoupons = {
        'QICKMED10': { discount: 0.1, type: 'percentage', description: 'Giảm 10%' },
        'FREESHIP': { discount: 30000, type: 'fixed', description: 'Miễn phí vận chuyển' },
        'NEWUSER': { discount: 50000, type: 'fixed', description: 'Giảm 50.000đ' },
        'HEALTH20': { discount: 0.2, type: 'percentage', description: 'Giảm 20%' }
    };
    
    const coupon = validCoupons[code];
    const couponInput = document.querySelector('input[name="coupon"]');
    const applyBtn = document.querySelector('.coupon-form button');
    
    if (!coupon) {
        showNotification('Mã giảm giá không hợp lệ', 'error');
        couponInput.classList.add('is-invalid');
        return;
    }
    
    // Calculate discount
    let discountAmount = 0;
    if (coupon.type === 'percentage') {
        discountAmount = cartData.subtotal * coupon.discount;
    } else {
        discountAmount = coupon.discount;
        // If it's shipping discount, apply to shipping instead
        if (code === 'FREESHIP') {
            cartData.shipping = 0;
        }
    }
    
    cartData.discount = discountAmount;
    
    // Update UI
    couponInput.classList.add('is-valid');
    couponInput.disabled = true;
    applyBtn.innerHTML = '<i class="fas fa-check me-2"></i>Đã áp dụng';
    applyBtn.disabled = true;
    applyBtn.className = 'btn btn-success';
    
    // Show applied coupon
    showAppliedCoupon(code, coupon.description);
    
    updateCartSummary();
    showNotification(`Đã áp dụng mã giảm giá: ${coupon.description}`, 'success');
}

// Show applied coupon
function showAppliedCoupon(code, description) {
    const couponContainer = document.querySelector('.applied-coupons');
    if (!couponContainer) return;
    
    const couponElement = document.createElement('div');
    couponElement.className = 'applied-coupon alert alert-success d-flex justify-content-between align-items-center';
    couponElement.innerHTML = `
        <div>
            <i class="fas fa-tag me-2"></i>
            <strong>${code}</strong>: ${description}
        </div>
        <button class="btn btn-sm btn-outline-danger remove-coupon" data-code="${code}">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    couponContainer.appendChild(couponElement);
    
    // Add remove functionality
    couponElement.querySelector('.remove-coupon').addEventListener('click', () => {
        removeCoupon(code);
        couponElement.remove();
    });
}

// Remove coupon
function removeCoupon(code) {
    cartData.discount = 0;
    if (code === 'FREESHIP') {
        cartData.shipping = 30000;
    }
    
    // Reset coupon form
    const couponInput = document.querySelector('input[name="coupon"]');
    const applyBtn = document.querySelector('.coupon-form button');
    
    couponInput.value = '';
    couponInput.disabled = false;
    couponInput.classList.remove('is-valid');
    applyBtn.innerHTML = 'Áp dụng';
    applyBtn.disabled = false;
    applyBtn.className = 'btn btn-outline-primary';
    
    updateCartSummary();
    showNotification('Đã hủy mã giảm giá', 'info');
}

// Initialize payment methods
function initializePaymentMethods() {
    const paymentOptions = document.querySelectorAll('input[name="payment_method"]');
    
    paymentOptions.forEach(option => {
        option.addEventListener('change', (e) => {
            selectPaymentMethod(e.target.value);
        });
    });
}

// Select payment method
function selectPaymentMethod(method) {
    const paymentDetails = document.querySelector('.payment-details');
    if (!paymentDetails) return;
    
    // Remove existing payment forms
    paymentDetails.innerHTML = '';
    
    switch (method) {
        case 'cod':
            paymentDetails.innerHTML = `
                <div class="payment-info alert alert-info">
                    <i class="fas fa-money-bill-wave me-2"></i>
                    Bạn sẽ thanh toán bằng tiền mặt khi nhận hàng
                </div>
            `;
            break;
        case 'vnpay':
            paymentDetails.innerHTML = `
                <div class="payment-info alert alert-primary">
                    <i class="fas fa-credit-card me-2"></i>
                    Bạn sẽ được chuyển đến VNPay để thanh toán
                </div>
            `;
            break;
        case 'momo':
            paymentDetails.innerHTML = `
                <div class="payment-info alert alert-warning">
                    <i class="fas fa-mobile-alt me-2"></i>
                    Thanh toán qua ví điện tử MoMo
                </div>
            `;
            break;
    }
}

// Initialize checkout process
function initializeCheckout() {
    const checkoutBtn = document.querySelector('.checkout-btn');
    if (!checkoutBtn) return;
    
    checkoutBtn.addEventListener('click', (e) => {
        e.preventDefault();
        processCheckout();
    });
}

// Process checkout
function processCheckout() {
    // Validate cart
    if (cartData.items.length === 0) {
        showNotification('Giỏ hàng trống', 'error');
        return;
    }
    
    // Create form to send cart data to checkout page
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'checkout.php';
    
    const cartInput = document.createElement('input');
    cartInput.type = 'hidden';
    cartInput.name = 'cart_data';
    cartInput.value = JSON.stringify(cartData.items);
    
    form.appendChild(cartInput);
    document.body.appendChild(form);
    form.submit();
}

// Proceed to checkout function for cart page
function proceedToCheckout() {
    processCheckout();
}

// Initialize animations
function initializeAnimations() {
    // Animate cart items on load
    const cartItems = document.querySelectorAll('.cart-item');
    cartItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            item.style.transition = 'all 0.5s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

// Animate quantity change
function animateQuantityChange(itemId) {
    const totalPrice = document.querySelector(`[data-id="${itemId}"] .total-price`);
    if (totalPrice) {
        totalPrice.style.transform = 'scale(1.1)';
        totalPrice.style.color = '#667eea';
        
        setTimeout(() => {
            totalPrice.style.transform = 'scale(1)';
            totalPrice.style.color = '';
        }, 200);
    }
}

// Utility functions
function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(price);
}

function saveCartData() {
    localStorage.setItem('qickmed_cart', JSON.stringify(cartData.items));
    updateCartCount(cartData.items.reduce((total, item) => total + item.quantity, 0));
}

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
        info: '#667eea',
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
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    `;
    document.head.appendChild(style);
    
    document.body.appendChild(notification);
    
    // Remove after 5 seconds
    setTimeout(() => {
        notification.style.animation = 'slideInRight 0.3s ease reverse';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

// Add CSS styles
const style = document.createElement('style');
style.textContent = `
    .cart-item {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }
    
    .cart-item:hover {
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    
    .product-image img {
        width: 80px;
        height: 80px;
        object-fit: cover;
    }
    
    .quantity-controls {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .quantity-btn {
        width: 35px;
        height: 35px;
        border: 1px solid #dee2e6;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .quantity-btn:hover {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }
    
    .quantity-input {
        width: 60px;
        text-align: center;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 0.5rem;
    }
    
    .coupon-form {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    
    .applied-coupon {
        margin-bottom: 0.5rem;
    }
    
    .payment-method-option {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .payment-method-option:hover {
        border-color: #667eea;
    }
    
    .payment-method-option.selected {
        border-color: #667eea;
        background: rgba(102, 126, 234, 0.1);
    }
    
    .cart-summary {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 15px;
        padding: 1.5rem;
        position: sticky;
        top: 100px;
    }
    
    .empty-cart {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 15px;
        padding: 3rem;
    }
`;
document.head.appendChild(style);

// Update cart count in header
function updateCartCount(count) {
    const cartCountElement = document.querySelector('.cart-count');
    if (cartCountElement) {
        cartCountElement.textContent = count;
        
        // Hiển thị số lượng nếu > 0, ẩn nếu = 0
        if (count > 0) {
            cartCountElement.style.display = 'flex';
        } else {
            cartCountElement.style.display = 'none';
        }
    }
} 