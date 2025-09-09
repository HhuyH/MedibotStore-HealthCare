// Shop Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize animations
    initializeAnimations();
    
    // Initialize product cards
    initializeProductCards();
    
    // Initialize category cards
    initializeCategoryCards();
    
    // Initialize search functionality
    initializeSearch();
    
    // Initialize cart functionality
    initializeCart();
    
    // Initialize scroll animations
    initializeScrollAnimations();
    
    // Xử lý sự kiện click vào nút Xem nhanh
    document.querySelectorAll('.quick-view').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.id;
            // Chuyển hướng đến trang chi tiết sản phẩm
            window.location.href = `/shop/details.php?id=${productId}`;
        });
    });
});

// Initialize scroll-triggered animations
function initializeAnimations() {
    // Animate hero content on load
    const heroContent = document.querySelector('.hero-content');
    
    if (heroContent) {
        heroContent.style.opacity = '0';
        heroContent.style.transform = 'translateY(50px)';
        
        setTimeout(() => {
            heroContent.style.transition = 'all 1s ease';
            heroContent.style.opacity = '1';
            heroContent.style.transform = 'translateY(0)';
        }, 300);
    }
}

// Product card interactions
function initializeProductCards() {
    const productCards = document.querySelectorAll('.product-card');
    
    productCards.forEach(card => {
        const image = card.querySelector('.product-image img');
        const actions = card.querySelector('.product-actions');
        const addToCartBtn = card.querySelector('.add-to-cart');
        
        card.addEventListener('mouseenter', () => {
            // Show action buttons
            if (actions) {
                actions.style.opacity = '1';
                actions.style.transform = 'translateY(0)';
            }
            
            // Add glow effect
            card.style.transform = 'translateY(-5px)';
            card.style.boxShadow = '0 15px 35px rgba(102, 126, 234, 0.2)';
        });
        
        card.addEventListener('mouseleave', () => {
            // Hide action buttons
            if (actions) {
                actions.style.opacity = '0';
                actions.style.transform = 'translateY(10px)';
            }
            
            // Remove glow effect
            card.style.transform = 'translateY(0)';
            card.style.boxShadow = '';
        });
        
        // Add to cart animation - REMOVED TO AVOID CONFLICT WITH cart-new.js
        // if (addToCartBtn) {
        //     addToCartBtn.addEventListener('click', (e) => {
        //         e.preventDefault();
        //         e.stopPropagation();
        //         
        //         const productId = addToCartBtn.getAttribute('data-id');
        //         addToCart(productId);
        //         
        //         // Animation feedback
        //         addToCartBtn.innerHTML = '<i class="fas fa-check"></i>';
        //         addToCartBtn.style.background = '#28a745';
        //         
        //         setTimeout(() => {
        //             addToCartBtn.innerHTML = '<i class="fas fa-cart-plus"></i>';
        //             addToCartBtn.style.background = '';
        //         }, 1500);
        //     });
        // }
    });
}

// Category card interactions
function initializeCategoryCards() {
    const categoryCards = document.querySelectorAll('.category-card');
    
    categoryCards.forEach(card => {
        const icon = card.querySelector('.category-icon');
        const count = card.querySelector('.category-count');
        
        card.addEventListener('mouseenter', () => {
            // Animate icon
            if (icon) {
                icon.style.transform = 'scale(1.1) rotate(10deg)';
            }
            
            // Animate count
            if (count) {
                count.style.transform = 'scale(1.1)';
                count.style.background = '#667eea';
                count.style.color = 'white';
            }
        });
        
        card.addEventListener('mouseleave', () => {
            // Reset icon
            if (icon) {
                icon.style.transform = 'scale(1) rotate(0deg)';
            }
            
            // Reset count
            if (count) {
                count.style.transform = 'scale(1)';
                count.style.background = '';
                count.style.color = '';
            }
        });
    });
}

// Search functionality
function initializeSearch() {
    const searchInput = document.querySelector('.search-box input');
    const searchBtn = document.querySelector('.search-box button');
    
    if (searchInput && searchBtn) {
        // Search on button click
        searchBtn.addEventListener('click', () => {
            performSearch(searchInput.value);
        });
        
        // Search on Enter key
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                performSearch(searchInput.value);
            }
        });
        
        // Real-time search suggestions
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value;
            if (query.length > 2) {
                showSearchSuggestions(query);
            } else {
                hideSearchSuggestions();
            }
        });
    }
}

// Perform search
function performSearch(query) {
    if (!query.trim()) return;
    
    console.log('Searching for:', query);
    
    // Show loading
    showSearchLoading();
    
    // Simulate API call
    setTimeout(() => {
        hideSearchLoading();
        // Redirect to search results or filter products
        window.location.href = `/search.php?q=${encodeURIComponent(query)}`;
    }, 1000);
}

// Show search suggestions
function showSearchSuggestions(query) {
    // Mock suggestions
    const suggestions = [
        'Vitamin C 1000mg',
        'Máy đo huyết áp',
        'Paracetamol',
        'Omega 3',
        'Thuốc cảm cúm'
    ].filter(item => item.toLowerCase().includes(query.toLowerCase()));
    
    if (suggestions.length > 0) {
        const suggestionsHtml = suggestions.map(suggestion => 
            `<div class="search-suggestion" onclick="selectSuggestion('${suggestion}')">${suggestion}</div>`
        ).join('');
        
        showSuggestionDropdown(suggestionsHtml);
    }
}

// Hide search suggestions
function hideSearchSuggestions() {
    const dropdown = document.querySelector('.search-suggestions-dropdown');
    if (dropdown) {
        dropdown.remove();
    }
}

// Show suggestion dropdown
function showSuggestionDropdown(html) {
    hideSearchSuggestions();
    
    const searchContainer = document.querySelector('.search-box').parentElement;
    const dropdown = document.createElement('div');
    dropdown.className = 'search-suggestions-dropdown';
    dropdown.innerHTML = html;
    dropdown.style.cssText = `
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        z-index: 1000;
        max-height: 200px;
        overflow-y: auto;
    `;
    
    // Style suggestions
    const style = document.createElement('style');
    style.textContent = `
        .search-suggestion {
            padding: 12px 16px;
            cursor: pointer;
            transition: background 0.2s ease;
            border-bottom: 1px solid #f8f9fa;
        }
        .search-suggestion:hover {
            background: #f8f9fa;
            color: #667eea;
        }
        .search-suggestion:last-child {
            border-bottom: none;
        }
    `;
    document.head.appendChild(style);
    
    searchContainer.style.position = 'relative';
    searchContainer.appendChild(dropdown);
}

// Select suggestion
function selectSuggestion(suggestion) {
    const searchInput = document.querySelector('.search-box input');
    if (searchInput) {
        searchInput.value = suggestion;
        performSearch(suggestion);
    }
    hideSearchSuggestions();
}

// Show search loading
function showSearchLoading() {
    const searchBtn = document.querySelector('.search-box button');
    if (searchBtn) {
        searchBtn.innerHTML = '<div class="loading"></div>';
        searchBtn.disabled = true;
    }
}

// Hide search loading
function hideSearchLoading() {
    const searchBtn = document.querySelector('.search-box button');
    if (searchBtn) {
        searchBtn.innerHTML = '<i class="fas fa-search"></i>';
        searchBtn.disabled = false;
    }
}

// Cart functionality
function initializeCart() {
    // Initialize cart from localStorage
    loadCartFromStorage();
    updateCartUI();
}

// Add to cart
function addToCart(productId) {
    const cart = getCart();
    const existingItem = cart.find(item => item.id === productId);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        // Mock product data - in real app, fetch from API
        const productData = getProductData(productId);
        cart.push({
            id: productId,
            ...productData,
            quantity: 1
        });
    }
    
    saveCartToStorage(cart);
    updateCartUI();
    showCartNotification('Đã thêm vào giỏ hàng!');
}

// Get product data (mock)
function getProductData(productId) {
    const products = {
        '1': { name: 'Vitamin C 1000mg', price: 320000, image: '/assets/images/product-1.jpg' },
        '2': { name: 'Máy đo huyết áp Omron', price: 1250000, image: '/assets/images/product-2.jpg' },
        '3': { name: 'Paracetamol 500mg', price: 25000, image: '/assets/images/product-3.jpg' },
        '4': { name: 'Omega 3 Fish Oil', price: 580000, image: '/assets/images/product-4.jpg' }
    };
    
    return products[productId] || { name: 'Sản phẩm', price: 0, image: '' };
}

// Get cart
function getCart() {
    return JSON.parse(localStorage.getItem('qickmed_cart') || '[]');
}

// Save cart to storage
function saveCartToStorage(cart) {
    localStorage.setItem('qickmed_cart', JSON.stringify(cart));
}

// Load cart from storage
function loadCartFromStorage() {
    const cart = getCart();
    // Process cart data if needed
}

// Update cart UI
function updateCartUI() {
    const cart = getCart();
    const cartCount = cart.reduce((total, item) => total + item.quantity, 0);
    
    // Update cart icon badge
    const cartIcon = document.querySelector('.clinic-icon-btn i.fa-cart-shopping');
    if (cartIcon) {
        let badge = cartIcon.parentElement.querySelector('.cart-badge');
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'cart-badge';
            badge.style.cssText = `
                position: absolute;
                top: -5px;
                right: -5px;
                background: #dc3545;
                color: white;
                border-radius: 50%;
                width: 20px;
                height: 20px;
                font-size: 0.8rem;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 600;
            `;
            cartIcon.parentElement.style.position = 'relative';
            cartIcon.parentElement.appendChild(badge);
        }
        
        badge.textContent = cartCount;
        badge.style.display = cartCount > 0 ? 'flex' : 'none';
    }
}

// Show cart notification
function showCartNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'cart-notification';
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: #28a745;
        color: white;
        padding: 12px 20px;
        border-radius: 25px;
        z-index: 1000;
        animation: slideInRight 0.3s ease;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    `;
    
    // Add animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    `;
    document.head.appendChild(style);
    
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideInRight 0.3s ease reverse';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Initialize scroll animations
function initializeScrollAnimations() {
    const animatedElements = document.querySelectorAll(
        '.category-card, .product-card, .feature-card, .section-title'
    );
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '50px'
    });
    
    animatedElements.forEach((element, index) => {
        // Set initial state
        element.style.opacity = '0';
        element.style.transform = 'translateY(30px)';
        element.style.transition = `all 0.6s ease ${index * 0.05}s`;
        
        observer.observe(element);
    });
    
    // Add CSS for animate-in class
    const style = document.createElement('style');
    style.textContent = `
        .animate-in {
            opacity: 1 !important;
            transform: translateY(0) !important;
        }
        
        .category-card.animate-in,
        .product-card.animate-in,
        .feature-card.animate-in {
            animation: fadeInUp 0.6s ease forwards;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .product-actions {
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }
        
        .loading {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
}

// Add loading animation
function addLoadingAnimation() {
    const loadingOverlay = document.createElement('div');
    loadingOverlay.innerHTML = `
        <div style="
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.5s ease;
        ">
            <div style="
                width: 50px;
                height: 50px;
                border: 3px solid #f3f3f3;
                border-top: 3px solid #667eea;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            "></div>
        </div>
    `;
    
    document.body.appendChild(loadingOverlay);
    
    window.addEventListener('load', () => {
        setTimeout(() => {
            loadingOverlay.style.opacity = '0';
            setTimeout(() => {
                loadingOverlay.remove();
            }, 500);
        }, 800);
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    addLoadingAnimation();
});

// Add scroll progress indicator
function addScrollProgress() {
    const progressBar = document.createElement('div');
    progressBar.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 0%;
        height: 3px;
        background: linear-gradient(90deg, #667eea, #764ba2);
        z-index: 1000;
        transition: width 0.1s ease;
    `;
    document.body.appendChild(progressBar);
    
    window.addEventListener('scroll', () => {
        const scrollTop = window.pageYOffset;
        const docHeight = document.body.scrollHeight - window.innerHeight;
        const scrollPercent = (scrollTop / docHeight) * 100;
        progressBar.style.width = scrollPercent + '%';
    });
}

// Initialize scroll progress
document.addEventListener('DOMContentLoaded', addScrollProgress);

// Hàm format giá tiền
function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN').format(price);
} 