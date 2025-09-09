// Modern Services Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all features
    initializeMaintenanceModal();
    initializeAnimations();
    initializeServiceCards();
    initializePricingCards();
    initializeScrollAnimations();
    initializeSmoothScrolling();
    initializeParallaxEffects();
    
    // Add loading animation
    addLoadingAnimation();
});

// Initialize maintenance modal
function initializeMaintenanceModal() {
    const modal = document.getElementById('maintenanceModal');
    const bookingButtons = document.querySelectorAll('.btn-book, [href*="booking"], [href*="appointment"]');
    
    // Add event listeners to all booking buttons
    bookingButtons.forEach(button => {
        // Remove existing href to prevent navigation
        if (button.tagName === 'A') {
            button.setAttribute('data-original-href', button.getAttribute('href'));
            button.removeAttribute('href');
            button.style.cursor = 'pointer';
        }
        
        button.addEventListener('click', function(e) {
            e.preventDefault();
            showMaintenanceModal();
        });
    });
    
    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeMaintenance();
        }
    });
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeMaintenance();
        }
    });
}

// Show maintenance modal
function showMaintenanceModal() {
    const modal = document.getElementById('maintenanceModal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Add entrance animation
    setTimeout(() => {
        const content = modal.querySelector('.maintenance-modal-content');
        content.style.animation = 'modalEnter 0.3s ease forwards';
    }, 50);
}

// Close maintenance modal
function closeMaintenance() {
    const modal = document.getElementById('maintenanceModal');
    const content = modal.querySelector('.maintenance-modal-content');
    
    // Add exit animation
    content.style.animation = 'modalExit 0.3s ease forwards';
    
    setTimeout(() => {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }, 300);
}

// Initialize scroll-triggered animations
function initializeAnimations() {
    // Animate hero content on load
    const heroContent = document.querySelector('.hero-content');
    
    if (heroContent) {
        heroContent.style.opacity = '0';
        heroContent.style.transform = 'translateY(50px)';
        
        setTimeout(() => {
            heroContent.style.transition = 'all 1s cubic-bezier(0.4, 0, 0.2, 1)';
            heroContent.style.opacity = '1';
            heroContent.style.transform = 'translateY(0)';
        }, 300);
    }
    
    // Animate section titles
    const sectionTitles = document.querySelectorAll('.section-title');
    sectionTitles.forEach((title, index) => {
        title.style.opacity = '0';
        title.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            title.style.transition = `all 0.8s cubic-bezier(0.4, 0, 0.2, 1) ${index * 0.1}s`;
            title.style.opacity = '1';
            title.style.transform = 'translateY(0)';
        }, 500);
    });
}

// Service card interactions
function initializeServiceCards() {
    const serviceCards = document.querySelectorAll('.service-card');
    
    serviceCards.forEach(card => {
        const icon = card.querySelector('.service-icon');
        const features = card.querySelectorAll('.service-features li');
        const button = card.querySelector('.btn-book');
        
        card.addEventListener('mouseenter', () => {
            // Animate icon
            if (icon) {
                icon.style.transform = 'scale(1.1) rotate(5deg)';
                icon.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
            }
            
            // Animate features
            features.forEach((feature, index) => {
                setTimeout(() => {
                    feature.style.transform = 'translateX(10px)';
                    feature.style.transition = 'all 0.3s ease';
                    feature.style.background = 'rgba(14, 165, 233, 0.05)';
                    feature.style.borderRadius = '8px';
                    feature.style.padding = '4px 8px';
                }, index * 50);
            });
            
            // Animate button
            if (button) {
                button.style.transform = 'translateY(-2px) scale(1.02)';
                button.style.boxShadow = '0 8px 25px rgba(14, 165, 233, 0.3)';
            }
        });
        
        card.addEventListener('mouseleave', () => {
            // Reset icon
            if (icon) {
                icon.style.transform = 'scale(1) rotate(0deg)';
            }
            
            // Reset features
            features.forEach(feature => {
                feature.style.transform = 'translateX(0)';
                feature.style.background = 'transparent';
                feature.style.padding = '0';
            });
            
            // Reset button
            if (button) {
                button.style.transform = 'translateY(0) scale(1)';
                button.style.boxShadow = '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)';
            }
        });
    });
}

// Pricing card interactions
function initializePricingCards() {
    const pricingCards = document.querySelectorAll('.package-card');
    
    pricingCards.forEach(card => {
        const features = card.querySelectorAll('.package-features li');
        const button = card.querySelector('.btn-book');
        const header = card.querySelector('.package-header');
        
        card.addEventListener('mouseenter', () => {
            // Animate header
            if (header) {
                header.style.background = 'linear-gradient(135deg, #0ea5e9, #0284c7)';
                header.style.color = 'white';
                header.style.transition = 'all 0.3s ease';
            }
            
            // Animate features
            features.forEach((feature, index) => {
                setTimeout(() => {
                    feature.style.transform = 'translateX(5px)';
                    feature.style.background = 'rgba(14, 165, 233, 0.05)';
                    feature.style.borderRadius = '6px';
                    feature.style.padding = '2px 6px';
                    feature.style.transition = 'all 0.3s ease';
                }, index * 30);
            });
            
            // Animate button
            if (button) {
                button.style.transform = 'translateY(-2px) scale(1.05)';
                button.style.boxShadow = '0 10px 25px rgba(14, 165, 233, 0.3)';
            }
        });
        
        card.addEventListener('mouseleave', () => {
            // Reset header
            if (header) {
                header.style.background = 'linear-gradient(135deg, #f0f9ff, #e0f2fe)';
                header.style.color = 'inherit';
            }
            
            // Reset features
            features.forEach(feature => {
                feature.style.transform = 'translateX(0)';
                feature.style.background = 'transparent';
                feature.style.padding = '0';
            });
            
            // Reset button
            if (button) {
                button.style.transform = 'translateY(0) scale(1)';
                button.style.boxShadow = '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)';
            }
        });
    });
}

// Initialize scroll animations
function initializeScrollAnimations() {
    const animatedElements = document.querySelectorAll(
        '.service-card, .feature-card, .package-card'
    );
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                
                // Add stagger effect
                const siblings = Array.from(entry.target.parentElement.children);
                const index = siblings.indexOf(entry.target);
                
                setTimeout(() => {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, index * 100);
                
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '50px'
    });
    
    animatedElements.forEach(element => {
        // Set initial state
        element.style.opacity = '0';
        element.style.transform = 'translateY(30px)';
        element.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
        
        observer.observe(element);
    });
}

// Smooth scrolling for internal links
function initializeSmoothScrolling() {
    const links = document.querySelectorAll('a[href^="#"]');
    
    links.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = link.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                const headerOffset = 100;
                const elementPosition = targetElement.offsetTop;
                const offsetPosition = elementPosition - headerOffset;
                
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
}

// Initialize parallax effects
function initializeParallaxEffects() {
    const parallaxElements = document.querySelectorAll('.hero-section, .cta-section');
    
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        
        parallaxElements.forEach(element => {
            const speed = 0.5;
            const yPos = -(scrolled * speed);
            
            if (element.querySelector('::before')) {
                element.style.backgroundPositionY = `${yPos}px`;
            }
        });
    });
}

// Add loading animation
function addLoadingAnimation() {
    const loadingOverlay = document.createElement('div');
    loadingOverlay.className = 'loading-overlay';
    loadingOverlay.innerHTML = `
        <div class="loading-spinner">
            <div class="spinner-icon">
                <i class="fas fa-heartbeat"></i>
            </div>
            <div class="loading-text">Đang tải...</div>
        </div>
    `;
    
    // Add styles
    const style = document.createElement('style');
    style.textContent = `
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.5s ease;
        }
        
        .loading-spinner {
            text-align: center;
            color: white;
        }
        
        .spinner-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            animation: pulse 2s ease-in-out infinite;
        }
        
        .loading-text {
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        @keyframes modalEnter {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        
        @keyframes modalExit {
            from {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
            to {
                opacity: 0;
                transform: scale(0.9) translateY(20px);
            }
        }
        
        .animate-in {
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
    `;
    
    document.head.appendChild(style);
    document.body.appendChild(loadingOverlay);
    
    // Remove loading overlay after page load
    window.addEventListener('load', () => {
        setTimeout(() => {
            loadingOverlay.style.opacity = '0';
            setTimeout(() => {
                loadingOverlay.remove();
            }, 500);
        }, 500);
    });
}

// Initialize service filter (for future use)
function initializeServiceFilter() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const serviceCards = document.querySelectorAll('.service-card');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            const filter = button.getAttribute('data-filter');
            
            // Update active button
            filterButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            
            // Filter cards
            serviceCards.forEach(card => {
                const category = card.getAttribute('data-category');
                
                if (filter === 'all' || category === filter) {
                    card.style.display = 'block';
                    card.style.animation = 'fadeIn 0.5s ease';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
}

// Add scroll progress indicator
function addScrollProgress() {
    const progressBar = document.createElement('div');
    progressBar.className = 'scroll-progress';
    progressBar.innerHTML = '<div class="progress-fill"></div>';
    
    const style = document.createElement('style');
    style.textContent = `
        .scroll-progress {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
            z-index: 9998;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #0ea5e9, #0284c7);
            width: 0%;
            transition: width 0.1s ease;
        }
    `;
    
    document.head.appendChild(style);
    document.body.appendChild(progressBar);
    
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const maxScroll = document.body.scrollHeight - window.innerHeight;
        const progress = (scrolled / maxScroll) * 100;
        
        progressBar.querySelector('.progress-fill').style.width = `${progress}%`;
    });
}

// Initialize scroll progress
addScrollProgress();

// Add custom cursor effect
function addCustomCursor() {
    const cursor = document.createElement('div');
    cursor.className = 'custom-cursor';
    
    const style = document.createElement('style');
    style.textContent = `
        .custom-cursor {
            position: fixed;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: rgba(14, 165, 233, 0.5);
            pointer-events: none;
            z-index: 9999;
            transition: transform 0.1s ease;
        }
        
        .custom-cursor.hover {
            transform: scale(2);
            background: rgba(14, 165, 233, 0.3);
        }
    `;
    
    document.head.appendChild(style);
    document.body.appendChild(cursor);
    
    document.addEventListener('mousemove', (e) => {
        cursor.style.left = e.clientX - 10 + 'px';
        cursor.style.top = e.clientY - 10 + 'px';
    });
    
    // Add hover effect for interactive elements
    const interactiveElements = document.querySelectorAll('a, button, .service-card, .feature-card, .package-card');
    
    interactiveElements.forEach(element => {
        element.addEventListener('mouseenter', () => cursor.classList.add('hover'));
        element.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
    });
}

// Add custom cursor on desktop
if (window.innerWidth > 768) {
    addCustomCursor();
}

// Global function to be called from HTML
window.closeMaintenance = closeMaintenance;
window.showMaintenanceModal = showMaintenanceModal; 