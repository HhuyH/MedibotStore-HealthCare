// Blog Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize animations
    initializeAnimations();
    
    // Initialize blog post interactions
    initializeBlogPosts();
    
    // Initialize sidebar functionality
    initializeSidebar();
    
    // Initialize search functionality
    initializeSearch();
    
    // Initialize scroll animations
    initializeScrollAnimations();

    // Smooth scrolling for anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Search form enhancement
    const searchForm = document.querySelector('.search-form');
    const searchInput = document.querySelector('.search-form input[name="search"]');
    
    if (searchForm && searchInput) {
        // Auto-focus search input on page load if there's a search query
        if (searchInput.value.trim() !== '') {
            searchInput.focus();
        }
        
        // Clear search functionality
        const clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = 'search-clear';
        clearBtn.innerHTML = '<i class="fas fa-times"></i>';
        clearBtn.style.cssText = `
            position: absolute;
            right: 3rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 0.25rem;
            display: none;
        `;
        
        searchInput.parentNode.appendChild(clearBtn);
        
        // Show/hide clear button
        function toggleClearButton() {
            if (searchInput.value.trim() !== '') {
                clearBtn.style.display = 'block';
            } else {
                clearBtn.style.display = 'none';
            }
        }
        
        searchInput.addEventListener('input', toggleClearButton);
        toggleClearButton(); // Initial check
        
        // Clear search
        clearBtn.addEventListener('click', function() {
            searchInput.value = '';
            searchInput.focus();
            toggleClearButton();
        });
        
        // Search suggestions (if needed)
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length >= 2) {
                searchTimeout = setTimeout(() => {
                    // You can implement search suggestions here
                    console.log('Search query:', query);
                }, 300);
            }
        });
    }

    // Category tags smooth scroll
    const categoryTags = document.querySelectorAll('.category-tag');
    categoryTags.forEach(tag => {
        tag.addEventListener('click', function(e) {
            // Add loading state
            this.style.opacity = '0.7';
            setTimeout(() => {
                this.style.opacity = '1';
            }, 200);
        });
    });

    // Post cards hover effects
    const postCards = document.querySelectorAll('.post-card, .featured-post');
    postCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Newsletter form
    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const emailInput = this.querySelector('input[type="email"]');
            const submitBtn = this.querySelector('button[type="submit"]');
            const email = emailInput.value.trim();
            
            if (!email) {
                showNotification('Vui lòng nhập email của bạn', 'error');
                return;
            }
            
            // Validate email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showNotification('Email không hợp lệ', 'error');
                return;
            }
            
            // Show loading state
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
            submitBtn.disabled = true;
            
            // Simulate API call
            setTimeout(() => {
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                // Clear form
                emailInput.value = '';
                
                // Show success message
                showNotification('Đăng ký thành công! Cảm ơn bạn đã quan tâm.', 'success');
            }, 2000);
        });
    }

    // Lazy loading for images
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));

    // Back to top button
    const backToTopBtn = document.createElement('button');
    backToTopBtn.className = 'back-to-top';
    backToTopBtn.innerHTML = '<i class="fas fa-chevron-up"></i>';
    backToTopBtn.style.cssText = `
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        background: #2563eb;
        color: white;
        border: none;
        border-radius: 50%;
        width: 3rem;
        height: 3rem;
        cursor: pointer;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        transition: all 0.3s ease;
        opacity: 0;
        visibility: hidden;
        z-index: 1000;
    `;
    
    document.body.appendChild(backToTopBtn);
    
    // Show/hide back to top button
    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 300) {
            backToTopBtn.style.opacity = '1';
            backToTopBtn.style.visibility = 'visible';
        } else {
            backToTopBtn.style.opacity = '0';
            backToTopBtn.style.visibility = 'hidden';
        }
    });
    
    // Back to top functionality
    backToTopBtn.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    // Reading progress bar
    const progressBar = document.createElement('div');
    progressBar.className = 'reading-progress';
    progressBar.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 0%;
        height: 3px;
        background: linear-gradient(90deg, #2563eb, #1d4ed8);
        z-index: 9999;
        transition: width 0.1s ease;
    `;
    
    document.body.appendChild(progressBar);
    
    // Update reading progress
    window.addEventListener('scroll', () => {
        const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
        const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrolled = (winScroll / height) * 100;
        progressBar.style.width = scrolled + '%';
    });

    // Enhanced pagination
    const paginationLinks = document.querySelectorAll('.pagination .page-link');
    paginationLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!this.parentElement.classList.contains('disabled')) {
                // Add loading state
                this.style.opacity = '0.7';
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            }
        });
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
        
        // Escape to clear search
        if (e.key === 'Escape' && searchInput && document.activeElement === searchInput) {
            searchInput.blur();
        }
    });

    // Print functionality
    window.addEventListener('beforeprint', function() {
        // Hide unnecessary elements when printing
        const elementsToHide = document.querySelectorAll('.back-to-top, .reading-progress, .notification');
        elementsToHide.forEach(el => el.style.display = 'none');
    });
    
    window.addEventListener('afterprint', function() {
        // Restore elements after printing
        const elementsToShow = document.querySelectorAll('.back-to-top, .reading-progress');
        elementsToShow.forEach(el => el.style.display = '');
    });

    // Performance optimization: Debounce scroll events
    let scrollTimeout;
    function debounceScroll(func, wait) {
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(scrollTimeout);
                func(...args);
            };
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(later, wait);
        };
    }

    // Apply debouncing to scroll events
    const debouncedScrollHandler = debounceScroll(() => {
        // Your scroll handling code here
    }, 10);

    window.addEventListener('scroll', debouncedScrollHandler);

    console.log('Blog page JavaScript loaded successfully!');
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

// Blog post interactions
function initializeBlogPosts() {
    const blogPosts = document.querySelectorAll('.blog-post');
    
    blogPosts.forEach(post => {
        const image = post.querySelector('.post-image img');
        const readMore = post.querySelector('.read-more');
        const category = post.querySelector('.post-category');
        
        post.addEventListener('mouseenter', () => {
            // Add glow effect
            post.style.transform = 'translateY(-5px)';
            post.style.boxShadow = '0 15px 35px rgba(102, 126, 234, 0.2)';
            
            // Animate category
            if (category) {
                category.style.transform = 'translateX(5px)';
                category.style.background = '#667eea';
                category.style.color = 'white';
            }
            
            // Animate read more
            if (readMore) {
                readMore.style.transform = 'translateX(10px)';
            }
        });
        
        post.addEventListener('mouseleave', () => {
            // Remove glow effect
            post.style.transform = 'translateY(0)';
            post.style.boxShadow = '';
            
            // Reset category
            if (category) {
                category.style.transform = 'translateX(0)';
                category.style.background = '';
                category.style.color = '';
            }
            
            // Reset read more
            if (readMore) {
                readMore.style.transform = 'translateX(0)';
            }
        });
        
        // Click to read functionality
        post.addEventListener('click', (e) => {
            if (e.target.tagName !== 'A') {
                const readMoreLink = post.querySelector('.read-more');
                if (readMoreLink) {
                    readMoreLink.click();
                }
            }
        });
    });
    
    // Featured post special interactions
    const featuredPost = document.querySelector('.blog-post.featured');
    if (featuredPost) {
        const badge = featuredPost.querySelector('.post-badge');
        
        featuredPost.addEventListener('mouseenter', () => {
            if (badge) {
                badge.style.transform = 'scale(1.1) rotate(5deg)';
            }
        });
        
        featuredPost.addEventListener('mouseleave', () => {
            if (badge) {
                badge.style.transform = 'scale(1) rotate(0deg)';
            }
        });
    }
}

// Sidebar functionality
function initializeSidebar() {
    // Category list interactions
    const categoryItems = document.querySelectorAll('.category-list a');
    categoryItems.forEach(item => {
        const count = item.querySelector('.category-count');
        
        item.addEventListener('mouseenter', () => {
            if (count) {
                count.style.background = '#667eea';
                count.style.color = 'white';
                count.style.transform = 'scale(1.1)';
            }
        });
        
        item.addEventListener('mouseleave', () => {
            if (count) {
                count.style.background = '';
                count.style.color = '';
                count.style.transform = 'scale(1)';
            }
        });
    });
    
    // Recent post interactions
    const recentPosts = document.querySelectorAll('.recent-post');
    recentPosts.forEach(post => {
        const image = post.querySelector('.recent-post-image img');
        
        post.addEventListener('mouseenter', () => {
            post.style.background = 'rgba(102, 126, 234, 0.05)';
            post.style.borderRadius = '10px';
            post.style.padding = '0.75rem';
            post.style.margin = '0 -0.75rem';
            
            if (image) {
                image.style.transform = 'scale(1.05)';
            }
        });
        
        post.addEventListener('mouseleave', () => {
            post.style.background = '';
            post.style.borderRadius = '';
            post.style.padding = '';
            post.style.margin = '';
            
            if (image) {
                image.style.transform = 'scale(1)';
            }
        });
    });
    
    // Newsletter form
    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const email = newsletterForm.querySelector('input[type="email"]').value;
            handleNewsletterSignup(email);
        });
    }
}

// Handle newsletter signup
function handleNewsletterSignup(email) {
    if (!email) return;
    
    const button = document.querySelector('.newsletter-form button');
    const originalText = button.textContent;
    
    // Show loading
    button.innerHTML = '<div class="loading"></div>';
    button.disabled = true;
    
    // Simulate API call
    setTimeout(() => {
        button.innerHTML = '<i class="fas fa-check"></i> Đã đăng ký';
        button.style.background = '#28a745';
        
        showNotification('Đăng ký nhận tin thành công!', 'success');
        
        // Reset after 3 seconds
        setTimeout(() => {
            button.textContent = originalText;
            button.style.background = '';
            button.disabled = false;
            document.querySelector('.newsletter-form input').value = '';
        }, 3000);
    }, 1500);
}

// Search functionality
function initializeSearch() {
    const searchForm = document.querySelector('.search-box');
    const searchInput = searchForm?.querySelector('input');
    const searchBtn = searchForm?.querySelector('button');
    
    if (searchForm && searchInput && searchBtn) {
        // Search on button click
        searchBtn.addEventListener('click', (e) => {
            e.preventDefault();
            performSearch(searchInput.value);
        });
        
        // Search on form submit
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            performSearch(searchInput.value);
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
        
        // Hide suggestions when clicking outside
        document.addEventListener('click', (e) => {
            if (!searchForm.contains(e.target)) {
                hideSearchSuggestions();
            }
        });
    }
}

// Perform search
function performSearch(query) {
    if (!query.trim()) return;
    
    console.log('Searching blog for:', query);
    
    // Show loading
    showSearchLoading();
    
    // Simulate API call
    setTimeout(() => {
        hideSearchLoading();
        // In real app, filter posts or redirect to search results
        filterBlogPosts(query);
    }, 1000);
}

// Show search suggestions
function showSearchSuggestions(query) {
    const suggestions = [
        'Chăm sóc sức khỏe',
        'Dinh dưỡng',
        'Tập thể dục',
        'Giấc ngủ',
        'Vitamin',
        'Tim mạch',
        'Miễn dịch',
        'Stress'
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
    
    const searchWidget = document.querySelector('.search-widget');
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
    
    searchWidget.style.position = 'relative';
    searchWidget.appendChild(dropdown);
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

// Filter blog posts
function filterBlogPosts(query) {
    const posts = document.querySelectorAll('.blog-post');
    let visibleCount = 0;
    
    posts.forEach(post => {
        const title = post.querySelector('h2, h3')?.textContent?.toLowerCase() || '';
        const content = post.querySelector('p')?.textContent?.toLowerCase() || '';
        const category = post.querySelector('.post-category')?.textContent?.toLowerCase() || '';
        
        const matches = title.includes(query.toLowerCase()) || 
                       content.includes(query.toLowerCase()) || 
                       category.includes(query.toLowerCase());
        
        if (matches) {
            post.style.display = 'block';
            post.style.opacity = '1';
            visibleCount++;
        } else {
            post.style.opacity = '0.3';
            post.style.filter = 'grayscale(100%)';
        }
    });
    
    // Show search results info
    showSearchResults(query, visibleCount);
}

// Show search results info
function showSearchResults(query, count) {
    // Remove existing search info
    const existingInfo = document.querySelector('.search-results-info');
    if (existingInfo) existingInfo.remove();
    
    // Create new search info
    const searchInfo = document.createElement('div');
    searchInfo.className = 'search-results-info';
    searchInfo.innerHTML = `
        <div class="alert alert-info">
            <i class="fas fa-search me-2"></i>
            Tìm thấy ${count} bài viết cho từ khóa "<strong>${query}</strong>"
            <button class="btn btn-sm btn-outline-primary ms-3" onclick="clearSearch()">
                <i class="fas fa-times me-1"></i>Xóa bộ lọc
            </button>
        </div>
    `;
    
    const blogContent = document.querySelector('.blog-section .container');
    blogContent.insertBefore(searchInfo, blogContent.firstChild);
}

// Clear search
function clearSearch() {
    const posts = document.querySelectorAll('.blog-post');
    posts.forEach(post => {
        post.style.display = '';
        post.style.opacity = '';
        post.style.filter = '';
    });
    
    const searchInfo = document.querySelector('.search-results-info');
    if (searchInfo) searchInfo.remove();
    
    const searchInput = document.querySelector('.search-box input');
    if (searchInput) searchInput.value = '';
}

// Initialize scroll animations
function initializeScrollAnimations() {
    const animatedElements = document.querySelectorAll(
        '.blog-post, .sidebar-widget, .section-title'
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
        
        .blog-post.animate-in,
        .sidebar-widget.animate-in {
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
        
        .loading {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-radius: 50%;
            border-top-color: #667eea;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
}

// Notification system
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
            <button class="notification-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 2rem;
        right: 2rem;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#2563eb'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
        z-index: 9999;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        max-width: 400px;
    `;
    
    notification.querySelector('.notification-content').style.cssText = `
        display: flex;
        align-items: center;
        gap: 0.75rem;
    `;
    
    notification.querySelector('.notification-close').style.cssText = `
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        padding: 0.25rem;
        margin-left: auto;
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
    
    // Close button
    notification.querySelector('.notification-close').addEventListener('click', () => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => notification.remove(), 300);
    });
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