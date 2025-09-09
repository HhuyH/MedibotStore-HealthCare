// Blog Post JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all blog post features
    // initScrollToTop(); // Tắt button scroll-to-top
    initImageLazyLoading();
    initSocialShare();
    initReadingProgress();
    initCopyLink();
    initTableOfContents();
    // initPrintFunction();
    // initFontSizeControl(); // Tắt tính năng chọn cỡ chữ
    
    // Smooth scrolling for anchor links
    initSmoothScrolling();
    
    // Initialize AOS animations
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true,
            offset: 100
        });
    }
});

// Scroll to Top Button
function initScrollToTop() {
    const scrollBtn = document.createElement('button');
    scrollBtn.className = 'scroll-to-top';
    scrollBtn.innerHTML = '<i class="fas fa-chevron-up"></i>';
    scrollBtn.setAttribute('aria-label', 'Scroll to top');
    document.body.appendChild(scrollBtn);
    
    // Show/hide scroll button
    function toggleScrollButton() {
        if (window.pageYOffset > 300) {
            scrollBtn.classList.add('show');
        } else {
            scrollBtn.classList.remove('show');
        }
    }
    
    window.addEventListener('scroll', throttle(toggleScrollButton, 100));
    
    // Scroll to top on click
    scrollBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// Image Lazy Loading and Enhancement
function initImageLazyLoading() {
    const images = document.querySelectorAll('.post-content img');
    
    images.forEach(img => {
        // Add loading class initially
        img.classList.add('loading');
        
        // Create loading placeholder
        const placeholder = document.createElement('div');
        placeholder.className = 'image-placeholder';
        placeholder.style.cssText = `
            width: 100%;
            height: 200px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 14px;
            border-radius: 8px;
            margin: 1.5rem 0;
        `;
        placeholder.innerHTML = '<i class="fas fa-image"></i> Đang tải...';
        
        // Replace image with placeholder initially
        img.parentNode.insertBefore(placeholder, img);
        img.style.display = 'none';
        
        // Load image
        img.onload = function() {
            this.classList.remove('loading');
            this.classList.add('loaded');
            this.style.display = 'block';
            placeholder.remove();
        };
        
        img.onerror = function() {
            placeholder.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Không thể tải ảnh';
            placeholder.style.color = '#dc3545';
        };
        
        // If image is already loaded (cached)
        if (img.complete) {
            img.onload();
        }
    });
}

// Social Share Enhancement
function initSocialShare() {
    const shareButtons = document.querySelectorAll('.share-btn');
    
    shareButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            // Add click animation
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
            
            // Track share (you can implement analytics here)
            const platform = this.classList.contains('facebook') ? 'facebook' :
                           this.classList.contains('twitter') ? 'twitter' :
                           this.classList.contains('linkedin') ? 'linkedin' : 'email';
            
            console.log('Shared on:', platform);
        });
    });
    
    // Copy link functionality
    const copyLinkBtn = document.createElement('button');
    copyLinkBtn.className = 'share-btn copy-link';
    copyLinkBtn.innerHTML = '<i class="fas fa-link"></i> Sao chép liên kết';
    copyLinkBtn.style.cssText = `
        background: #28a745;
        color: white;
    `;
    
    const shareContainer = document.querySelector('.share-buttons');
    if (shareContainer) {
        shareContainer.appendChild(copyLinkBtn);
        
        copyLinkBtn.addEventListener('click', function() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i> Đã sao chép!';
                this.style.background = '#17a2b8';
                
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.style.background = '#28a745';
                }, 2000);
            }).catch(() => {
                showNotification('Không thể sao chép liên kết', 'error');
            });
        });
    }
}

// Reading Progress Indicator
function initReadingProgress() {
    const progressBar = document.createElement('div');
    progressBar.className = 'reading-progress';
    progressBar.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 0%;
        height: 4px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        z-index: 9999;
        transition: width 0.2s ease;
        box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
    `;
    document.body.appendChild(progressBar);
    
    function updateProgress() {
        const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
        const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrolled = (winScroll / height) * 100;
        progressBar.style.width = scrolled + '%';
    }
    
    window.addEventListener('scroll', throttle(updateProgress, 10));
}

// Copy Link Functionality
function initCopyLink() {
    // Add copy link button to post header
    const postHeader = document.querySelector('.post-header');
    if (postHeader) {
        const copyBtn = document.createElement('button');
        copyBtn.className = 'copy-link-btn';
        copyBtn.innerHTML = '<i class="fas fa-link"></i>';
        copyBtn.title = 'Sao chép liên kết bài viết';
        copyBtn.style.cssText = `
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(102, 126, 234, 0.1);
            border: 1px solid #667eea;
            color: #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        `;
        
        postHeader.style.position = 'relative';
        postHeader.appendChild(copyBtn);
        
        copyBtn.addEventListener('click', function() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                this.innerHTML = '<i class="fas fa-check"></i>';
                this.style.background = '#28a745';
                this.style.borderColor = '#28a745';
                this.style.color = 'white';
                
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-link"></i>';
                    this.style.background = 'rgba(102, 126, 234, 0.1)';
                    this.style.borderColor = '#667eea';
                    this.style.color = '#667eea';
                }, 2000);
            });
        });
    }
}

// Table of Contents (if headings exist)
function initTableOfContents() {
    const headings = document.querySelectorAll('.post-content h2, .post-content h3');
    
    if (headings.length > 2) {
        const toc = document.createElement('div');
        toc.className = 'table-of-contents';
        toc.innerHTML = '<h4>Mục lục</h4><ul class="toc-list"></ul>';
        toc.style.cssText = `
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 2rem 0;
            position: sticky;
            top: 2rem;
        `;
        
        const tocList = toc.querySelector('.toc-list');
        tocList.style.cssText = `
            list-style: none;
            padding: 0;
            margin: 0;
        `;
        
        headings.forEach((heading, index) => {
            const id = `heading-${index}`;
            heading.id = id;
            
            const li = document.createElement('li');
            li.style.cssText = `
                margin-bottom: 0.5rem;
                padding-left: ${heading.tagName === 'H3' ? '1rem' : '0'};
            `;
            
            const link = document.createElement('a');
            link.href = `#${id}`;
            link.textContent = heading.textContent;
            link.style.cssText = `
                text-decoration: none;
                color: #667eea;
                font-size: 0.9rem;
                transition: color 0.3s ease;
            `;
            
            link.addEventListener('click', function(e) {
                e.preventDefault();
                heading.scrollIntoView({ behavior: 'smooth' });
            });
            
            li.appendChild(link);
            tocList.appendChild(li);
        });
        
        // Insert TOC after first paragraph
        const firstParagraph = document.querySelector('.post-content p');
        if (firstParagraph) {
            firstParagraph.parentNode.insertBefore(toc, firstParagraph.nextSibling);
        }
    }
}

// Print Function
function initPrintFunction() {
    const printBtn = document.createElement('button');
    printBtn.className = 'print-btn';
    printBtn.innerHTML = '<i class="fas fa-print"></i> In bài viết';
    printBtn.style.cssText = `
        background: #6c757d;
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        margin-left: 1rem;
    `;
    
    const shareContainer = document.querySelector('.share-buttons');
    if (shareContainer) {
        shareContainer.appendChild(printBtn);
        
        printBtn.addEventListener('click', function() {
            window.print();
        });
    }
}

// Font Size Control
function initFontSizeControl() {
    const fontControls = document.createElement('div');
    fontControls.className = 'font-controls';
    fontControls.innerHTML = `
        <button class="font-btn" data-size="small">A</button>
        <button class="font-btn" data-size="medium">A</button>
        <button class="font-btn" data-size="large">A</button>
    `;
    fontControls.style.cssText = `
        position: fixed;
        left: 2rem;
        top: 50%;
        transform: translateY(-50%);
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 0.5rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        display: none;
    `;
    
    document.body.appendChild(fontControls);
    
    // Font size buttons
    const fontBtns = fontControls.querySelectorAll('.font-btn');
    fontBtns.forEach((btn, index) => {
        btn.style.cssText = `
            border: none;
            background: none;
            padding: 0.5rem;
            margin: 0.2rem;
            cursor: pointer;
            border-radius: 4px;
            font-size: ${index === 0 ? '12px' : index === 1 ? '16px' : '20px'};
            transition: all 0.3s ease;
        `;
        
        btn.addEventListener('click', function() {
            const size = this.dataset.size;
            const postContent = document.querySelector('.post-content');
            
            if (postContent) {
                postContent.classList.remove('font-small', 'font-medium', 'font-large');
                postContent.classList.add(`font-${size}`);
                
                // Update active button
                fontBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Save preference
                localStorage.setItem('blog-font-size', size);
            }
        });
    });
    
    // Set default font size
    const savedSize = localStorage.getItem('blog-font-size') || 'medium';
    document.querySelector(`[data-size="${savedSize}"]`).click();
    
    // Show font controls on scroll
    window.addEventListener('scroll', throttle(function() {
        if (window.pageYOffset > 300) {
            fontControls.style.display = 'block';
        } else {
            fontControls.style.display = 'none';
        }
    }, 100));
}

// Smooth Scrolling
function initSmoothScrolling() {
    const links = document.querySelectorAll('a[href^="#"]');
    
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            
            if (target) {
                const offsetTop = target.offsetTop - 80;
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });
}

// Utility Functions
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 2rem;
        right: 2rem;
        background: ${type === 'error' ? '#dc3545' : type === 'success' ? '#28a745' : '#17a2b8'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        z-index: 9999;
        transition: all 0.3s ease;
        transform: translateX(100%);
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// CSS for font sizes - Đã tắt tính năng này
// const fontSizeStyles = document.createElement('style');
// fontSizeStyles.textContent = `
//     .post-content.font-small { font-size: 0.9rem; }
//     .post-content.font-medium { font-size: 1.1rem; }
//     .post-content.font-large { font-size: 1.3rem; }
//     
//     .font-controls .font-btn.active {
//         background: #667eea;
//         color: white;
//     }
//     
//     .font-controls .font-btn:hover {
//         background: #f8f9fa;
//     }
// `;
// document.head.appendChild(fontSizeStyles); 