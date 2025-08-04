/**
 * Global JavaScript Enhancements
 * Advanced features for all pages
 */

class GlobalEnhancements {
    constructor() {
        this.init();
    }

    init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.initializeAll());
        } else {
            this.initializeAll();
        }
    }

    initializeAll() {
        // Initialize AOS if available
        this.initAOS();
        
        // Core features
        this.initCounterAnimation();
        this.initSmoothScrolling();
        this.initParallaxEffect();
        this.initScrollProgress();
        this.initBackToTop();
        this.initLazyLoading();
        this.initVideoModals();
        
        // Interactive features
        this.initInteractiveCards();
        this.initHoverEffects();
        this.initFloatingAnimations();
        
        // Advanced features (desktop only)
        if (window.innerWidth > 768) {
            this.initCustomCursor();
        }
        
        // Loading and resize handlers
        this.initLoadingEffects();
        this.initResizeHandlers();
        
        // Page-specific enhancements
        this.initPageSpecificFeatures();
    }

    // Initialize AOS (Animate On Scroll)
    initAOS() {
        if (typeof AOS !== 'undefined') {
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true,
                offset: 100,
                disable: 'mobile'
            });
        }
    }

    // Counter Animation
    initCounterAnimation() {
        const counters = document.querySelectorAll('[data-count]');
        
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-count'));
            const duration = 2000;
            const increment = target / (duration / 16);
            let current = 0;
            
            const updateCounter = () => {
                current += increment;
                if (current < target) {
                    counter.textContent = Math.floor(current).toLocaleString();
                    requestAnimationFrame(updateCounter);
                } else {
                    counter.textContent = target.toLocaleString();
                }
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        updateCounter();
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.5 });
            
            observer.observe(counter);
        });
    }

    // Smooth Scrolling
    initSmoothScrolling() {
        const links = document.querySelectorAll('a[href^="#"]');
        
        links.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                
                const targetId = link.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    const headerOffset = 80;
                    const elementPosition = targetElement.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                    
                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    }

    // Parallax Effects
    initParallaxEffect() {
        const parallaxElements = document.querySelectorAll('.parallax-element, .shape, .floating-shapes > *');
        
        if (parallaxElements.length > 0) {
            window.addEventListener('scroll', () => {
                const scrolled = window.pageYOffset;
                
                parallaxElements.forEach((element, index) => {
                    const speed = element.dataset.speed || (0.3 + (index * 0.1));
                    const yPos = -(scrolled * speed);
                    const rotation = scrolled * 0.1;
                    
                    element.style.transform = `translateY(${yPos}px) rotate(${rotation}deg)`;
                });
            });
        }
    }

    // Scroll Progress Indicator
    initScrollProgress() {
        // Create progress bar if it doesn't exist
        if (!document.querySelector('.scroll-progress')) {
            const progressBar = document.createElement('div');
            progressBar.className = 'scroll-progress';
            progressBar.innerHTML = '<div class="scroll-progress-bar"></div>';
            document.body.appendChild(progressBar);
        }
        
        const progressBarFill = document.querySelector('.scroll-progress-bar');
        
        if (progressBarFill) {
            window.addEventListener('scroll', () => {
                const scrollTop = window.pageYOffset;
                const docHeight = document.body.scrollHeight - window.innerHeight;
                const scrollPercent = Math.min((scrollTop / docHeight) * 100, 100);
                
                progressBarFill.style.width = scrollPercent + '%';
            });
        }
    }

    // Back to Top Button
    initBackToTop() {
        // Create button if it doesn't exist
        if (!document.querySelector('.back-to-top')) {
            const backToTopBtn = document.createElement('button');
            backToTopBtn.className = 'back-to-top';
            backToTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
            backToTopBtn.setAttribute('aria-label', 'Back to top');
            document.body.appendChild(backToTopBtn);
        }
        
        const backToTopBtn = document.querySelector('.back-to-top');
        
        if (backToTopBtn) {
            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 300) {
                    backToTopBtn.classList.add('show');
                } else {
                    backToTopBtn.classList.remove('show');
                }
            });
            
            backToTopBtn.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }
    }

    // Lazy Loading
    initLazyLoading() {
        const images = document.querySelectorAll('img[data-src]');
        
        if (images.length > 0) {
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        img.classList.add('loaded');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            images.forEach(img => {
                img.classList.add('lazy');
                imageObserver.observe(img);
            });
        }
    }

    // Video Modals Enhancement
    initVideoModals() {
        const videoModals = document.querySelectorAll('.modal');
        
        videoModals.forEach(modal => {
            const iframe = modal.querySelector('iframe');
            
            if (iframe) {
                modal.addEventListener('hidden.bs.modal', function() {
                    const src = iframe.src;
                    iframe.src = '';
                    iframe.src = src;
                });
            }
        });
    }

    // Interactive Cards
    initInteractiveCards() {
        const cards = document.querySelectorAll('.card, .mission-card, .service-card, .product-card, .blog-card, .team-card');
        
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
                this.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    }

    // Hover Effects
    initHoverEffects() {
        // Button hover effects
        const buttons = document.querySelectorAll('.btn:not(.no-hover)');
        
        buttons.forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.15)';
                this.style.transition = 'all 0.3s ease';
            });
            
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '';
            });
        });

        // Image hover effects
        const images = document.querySelectorAll('.hover-zoom img, .product-image img, .blog-image img');
        
        images.forEach(img => {
            const container = img.parentElement;
            container.style.overflow = 'hidden';
            
            img.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.1)';
                this.style.transition = 'transform 0.3s ease';
            });
            
            img.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });
    }

    // Floating Animations
    initFloatingAnimations() {
        const floatingElements = document.querySelectorAll('.float-animation, .stat-card, .feature-icon');
        
        floatingElements.forEach((element, index) => {
            element.style.animation = `float 3s ease-in-out infinite ${index * 0.5}s`;
        });
    }

    // Custom Cursor (Desktop only)
    initCustomCursor() {
        if (document.querySelector('.custom-cursor')) return;
        
        const cursor = document.createElement('div');
        cursor.className = 'custom-cursor';
        document.body.appendChild(cursor);
        
        const cursorDot = document.createElement('div');
        cursorDot.className = 'cursor-dot';
        document.body.appendChild(cursorDot);
        
        let mouseX = 0, mouseY = 0;
        let cursorX = 0, cursorY = 0;
        
        document.addEventListener('mousemove', (e) => {
            mouseX = e.clientX;
            mouseY = e.clientY;
            
            cursorDot.style.left = mouseX + 'px';
            cursorDot.style.top = mouseY + 'px';
        });
        
        const animateCursor = () => {
            cursorX += (mouseX - cursorX) * 0.1;
            cursorY += (mouseY - cursorY) * 0.1;
            
            cursor.style.left = cursorX + 'px';
            cursor.style.top = cursorY + 'px';
            
            requestAnimationFrame(animateCursor);
        };
        
        animateCursor();
        
        // Hover effects
        const interactiveElements = document.querySelectorAll('a, button, .card, input, textarea, select');
        
        interactiveElements.forEach(el => {
            el.addEventListener('mouseenter', () => {
                cursor.classList.add('cursor-hover');
                cursorDot.classList.add('cursor-hover');
            });
            
            el.addEventListener('mouseleave', () => {
                cursor.classList.remove('cursor-hover');
                cursorDot.classList.remove('cursor-hover');
            });
        });
    }

    // Loading Effects
    initLoadingEffects() {
        window.addEventListener('load', () => {
            document.body.classList.add('loaded');
            
            // Trigger entrance animations
            setTimeout(() => {
                const fadeElements = document.querySelectorAll('.fade-in');
                fadeElements.forEach((el, index) => {
                    setTimeout(() => {
                        el.classList.add('visible');
                    }, index * 100);
                });
            }, 300);
        });
    }

    // Resize Handlers
    initResizeHandlers() {
        let resizeTimer;
        
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                // Reinitialize AOS on resize
                if (typeof AOS !== 'undefined') {
                    AOS.refresh();
                }
                
                // Reinitialize cursor on desktop
                if (window.innerWidth > 768 && !document.querySelector('.custom-cursor')) {
                    this.initCustomCursor();
                } else if (window.innerWidth <= 768) {
                    const cursor = document.querySelector('.custom-cursor');
                    const cursorDot = document.querySelector('.cursor-dot');
                    if (cursor) cursor.remove();
                    if (cursorDot) cursorDot.remove();
                }
            }, 250);
        });
    }

    // Page-specific Features
    initPageSpecificFeatures() {
        const currentPage = window.location.pathname.split('/').pop().split('.')[0];
        
        switch (currentPage) {
            case 'index':
                this.initHomepageFeatures();
                break;
            case 'shop':
                this.initShopFeatures();
                break;
            case 'blog':
                this.initBlogFeatures();
                break;
            case 'contact':
                this.initContactFeatures();
                break;
            case 'services':
                this.initServicesFeatures();
                break;
        }
    }

    // Homepage specific features
    initHomepageFeatures() {
        // Hero typing effect
        const heroTitle = document.querySelector('.hero-title, .main-title');
        if (heroTitle && heroTitle.querySelector('.text-gradient')) {
            this.initTypingEffect(heroTitle);
        }

        // Stats animation
        const statsSection = document.querySelector('.stats-section, .numbers-section');
        if (statsSection) {
            this.initStatsAnimation(statsSection);
        }
    }

    // Shop specific features
    initShopFeatures() {
        // Product quick view
        const productCards = document.querySelectorAll('.product-card');
        productCards.forEach(card => {
            const quickViewBtn = card.querySelector('.quick-view');
            if (quickViewBtn) {
                quickViewBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.showQuickView(card);
                });
            }
        });
    }

    // Blog specific features
    initBlogFeatures() {
        // Reading progress for blog posts
        const blogPost = document.querySelector('.blog-post-content');
        if (blogPost) {
            this.initReadingProgress(blogPost);
        }
    }

    // Contact specific features
    initContactFeatures() {
        // Form enhancements
        const contactForm = document.querySelector('.contact-form');
        if (contactForm) {
            this.initFormEnhancements(contactForm);
        }
    }

    // Services specific features
    initServicesFeatures() {
        // Service tabs animation
        const serviceTabs = document.querySelectorAll('.service-tab');
        serviceTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                this.animateServiceContent(tab);
            });
        });
    }

    // Utility Methods
    initTypingEffect(element) {
        const text = element.textContent;
        const gradientText = element.querySelector('.text-gradient');
        
        if (gradientText) {
            const gradientTextContent = gradientText.textContent;
            const beforeGradient = text.split(gradientTextContent)[0];
            const afterGradient = text.split(gradientTextContent)[1] || '';
            
            element.innerHTML = '';
            
            let i = 0;
            const typeWriter = () => {
                if (i < beforeGradient.length) {
                    element.innerHTML += beforeGradient.charAt(i);
                    i++;
                    setTimeout(typeWriter, 100);
                } else if (i === beforeGradient.length) {
                    element.innerHTML += `<span class="text-gradient">${gradientTextContent}</span>`;
                    i++;
                    setTimeout(typeWriter, 100);
                } else if (i - beforeGradient.length - 1 < afterGradient.length) {
                    const currentAfterIndex = i - beforeGradient.length - 1;
                    const currentAfterText = afterGradient.substring(0, currentAfterIndex + 1);
                    element.innerHTML = beforeGradient + `<span class="text-gradient">${gradientTextContent}</span>` + currentAfterText;
                    i++;
                    setTimeout(typeWriter, 100);
                }
            };
            
            setTimeout(typeWriter, 1000);
        }
    }

    initStatsAnimation(statsSection) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-stats');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });
        
        observer.observe(statsSection);
    }

    initReadingProgress(blogPost) {
        const progressBar = document.createElement('div');
        progressBar.className = 'reading-progress';
        progressBar.innerHTML = '<div class="reading-progress-bar"></div>';
        document.body.appendChild(progressBar);
        
        const progressBarFill = progressBar.querySelector('.reading-progress-bar');
        
        window.addEventListener('scroll', () => {
            const postTop = blogPost.getBoundingClientRect().top + window.pageYOffset;
            const postHeight = blogPost.offsetHeight;
            const scrolled = window.pageYOffset - postTop;
            const progress = Math.min(Math.max(scrolled / postHeight, 0), 1) * 100;
            
            progressBarFill.style.width = progress + '%';
        });
    }

    initFormEnhancements(form) {
        const inputs = form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            // Floating labels
            input.addEventListener('focus', () => {
                input.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', () => {
                if (!input.value) {
                    input.parentElement.classList.remove('focused');
                }
            });
            
            // Real-time validation
            input.addEventListener('input', () => {
                this.validateField(input);
            });
        });
    }

    validateField(field) {
        const value = field.value.trim();
        const fieldContainer = field.parentElement;
        
        fieldContainer.classList.remove('error', 'success');
        
        if (field.required && !value) {
            fieldContainer.classList.add('error');
            return false;
        }
        
        if (field.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                fieldContainer.classList.add('error');
                return false;
            }
        }
        
        if (value) {
            fieldContainer.classList.add('success');
        }
        
        return true;
    }

    showQuickView(productCard) {
        // Implementation for product quick view
        console.log('Quick view for product:', productCard);
    }

    animateServiceContent(tab) {
        const targetContent = document.querySelector(tab.dataset.target);
        if (targetContent) {
            targetContent.style.opacity = '0';
            targetContent.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                targetContent.style.transition = 'all 0.3s ease';
                targetContent.style.opacity = '1';
                targetContent.style.transform = 'translateY(0)';
            }, 100);
        }
    }
}

// Global CSS Styles
const globalStyles = `
    /* Scroll Progress */
    .scroll-progress {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: rgba(96, 194, 247, 0.1);
        z-index: 9999;
        pointer-events: none;
    }
    
    .scroll-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #667eea, #764ba2);
        width: 0%;
        transition: width 0.3s ease;
    }
    
    .reading-progress {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: rgba(0, 0, 0, 0.1);
        z-index: 9998;
        pointer-events: none;
    }
    
    .reading-progress-bar {
        height: 100%;
        background: #007bff;
        width: 0%;
        transition: width 0.1s ease;
    }
    
    /* Back to Top */
    .back-to-top {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        opacity: 0;
        visibility: hidden;
        transform: translateY(20px);
        transition: all 0.3s ease;
        z-index: 1000;
        box-shadow: 0 4px 15px rgba(49, 198, 248, 0.3);
    }
    
    .back-to-top.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    
    .back-to-top:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(56, 185, 255, 0.96);
    }
    
    /* Custom Cursor */
    .custom-cursor {
        position: fixed;
        width: 30px;
        height: 30px;
        border: 2px solid #667eea;
        border-radius: 50%;
        pointer-events: none;
        z-index: 9999;
        transition: transform 0.1s ease;
        mix-blend-mode: difference;
    }
    
    .cursor-dot {
        position: fixed;
        width: 6px;
        height: 6px;
        background: #667eea;
        border-radius: 50%;
        pointer-events: none;
        z-index: 9999;
        transition: transform 0.1s ease;
    }
    
    .custom-cursor.cursor-hover {
        transform: scale(1.5);
        background: rgba(102, 126, 234, 0.1);
    }
    
    .cursor-dot.cursor-hover {
        transform: scale(2);
    }
    
    /* Lazy Loading */
    .lazy {
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .lazy.loaded {
        opacity: 1;
    }
    
    /* Animations */
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
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
    
    .fade-in {
        opacity: 0;
        transform: translateY(30px);
        transition: all 0.6s ease;
    }
    
    .fade-in.visible {
        opacity: 1;
        transform: translateY(0);
    }
    
    .animate-stats .stat-number {
        animation: countUp 2s ease-out;
    }
    
    @keyframes countUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* Form Enhancements */
    .form-group.focused label {
        transform: translateY(-20px) scale(0.8);
        color: #667eea;
    }
    
    .form-group.error input,
    .form-group.error textarea {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
    
    .form-group.success input,
    .form-group.success textarea {
        border-color: #28a745;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .custom-cursor,
        .cursor-dot {
            display: none !important;
        }
        
        .back-to-top {
            bottom: 20px;
            right: 20px;
            width: 45px;
            height: 45px;
        }
        
        .scroll-progress {
            height: 3px;
        }
    }
    
    /* Disable animations for users who prefer reduced motion */
    @media (prefers-reduced-motion: reduce) {
        *,
        *::before,
        *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }
`;

// Inject styles
const styleSheet = document.createElement('style');
styleSheet.textContent = globalStyles;
document.head.appendChild(styleSheet);

// Initialize when script loads
new GlobalEnhancements(); 