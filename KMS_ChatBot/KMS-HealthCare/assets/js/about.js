// About Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize AOS (Animate On Scroll)
    AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true,
        offset: 100
    });

    // Counter Animation
    function animateCounters() {
        const counters = document.querySelectorAll('[data-count]');
        
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-count'));
            const duration = 2000; // 2 seconds
            const increment = target / (duration / 16); // 60fps
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
            
            // Start animation when element is in view
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

    // Smooth Scrolling for anchor links
    function initSmoothScrolling() {
        const links = document.querySelectorAll('a[href^="#"]');
        
        links.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
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

    // Parallax Effect for Hero Section
    function initParallaxEffect() {
        const heroSection = document.querySelector('.hero-section');
        const floatingShapes = document.querySelectorAll('.shape');
        
        if (heroSection) {
            window.addEventListener('scroll', () => {
                const scrolled = window.pageYOffset;
                const rate = scrolled * -0.5;
                
                // Move floating shapes
                floatingShapes.forEach((shape, index) => {
                    const speed = 0.3 + (index * 0.1);
                    shape.style.transform = `translateY(${scrolled * speed}px) rotate(${scrolled * 0.1}deg)`;
                });
            });
        }
    }

    // Interactive Mission Cards
    function initMissionCards() {
        const missionCards = document.querySelectorAll('.mission-card');
        
        missionCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-15px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(-10px) scale(1)';
            });
        });
    }

    // Team Card Interactions
    function initTeamCards() {
        const teamCards = document.querySelectorAll('.team-card');
        
        teamCards.forEach(card => {
            const socialLinks = card.querySelectorAll('.social-link');
            
            card.addEventListener('mouseenter', function() {
                socialLinks.forEach((link, index) => {
                    setTimeout(() => {
                        link.style.transform = 'translateY(0)';
                        link.style.opacity = '1';
                    }, index * 100);
                });
            });
            
            card.addEventListener('mouseleave', function() {
                socialLinks.forEach(link => {
                    link.style.transform = 'translateY(20px)';
                    link.style.opacity = '0';
                });
            });
        });
    }

    // Timeline Animation
    function initTimelineAnimation() {
        const timelineItems = document.querySelectorAll('.timeline-item');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                }
            });
        }, { threshold: 0.3 });
        
        timelineItems.forEach(item => {
            observer.observe(item);
        });
    }

    // Scroll Progress Indicator
    function initScrollProgress() {
        const progressBar = document.createElement('div');
        progressBar.className = 'scroll-progress';
        progressBar.innerHTML = '<div class="scroll-progress-bar"></div>';
        document.body.appendChild(progressBar);
        
        const progressBarFill = progressBar.querySelector('.scroll-progress-bar');
        
        window.addEventListener('scroll', () => {
            const scrollTop = window.pageYOffset;
            const docHeight = document.body.scrollHeight - window.innerHeight;
            const scrollPercent = (scrollTop / docHeight) * 100;
            
            progressBarFill.style.width = scrollPercent + '%';
        });
    }

    // Back to Top Button
    function initBackToTop() {
        const backToTopBtn = document.createElement('button');
        backToTopBtn.className = 'back-to-top';
        backToTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
        backToTopBtn.setAttribute('aria-label', 'Back to top');
        document.body.appendChild(backToTopBtn);
        
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

    // Lazy Loading for Images
    function initLazyLoading() {
        const images = document.querySelectorAll('img[data-src]');
        
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        images.forEach(img => {
            imageObserver.observe(img);
        });
    }

    // Typing Effect for Hero Title
    function initTypingEffect() {
        const heroTitle = document.querySelector('.hero-title');
        if (!heroTitle) return;
        
        const text = heroTitle.textContent;
        const gradientText = heroTitle.querySelector('.text-gradient');
        
        if (gradientText) {
            const gradientTextContent = gradientText.textContent;
            const beforeGradient = text.split(gradientTextContent)[0];
            const afterGradient = text.split(gradientTextContent)[1] || '';
            
            heroTitle.innerHTML = '';
            
            let i = 0;
            const typeWriter = () => {
                if (i < beforeGradient.length) {
                    heroTitle.innerHTML += beforeGradient.charAt(i);
                    i++;
                    setTimeout(typeWriter, 100);
                } else if (i === beforeGradient.length) {
                    heroTitle.innerHTML += `<span class="text-gradient">${gradientTextContent}</span>`;
                    i++;
                    setTimeout(typeWriter, 100);
                } else if (i - beforeGradient.length - 1 < afterGradient.length) {
                    const currentAfterIndex = i - beforeGradient.length - 1;
                    const currentAfterText = afterGradient.substring(0, currentAfterIndex + 1);
                    heroTitle.innerHTML = beforeGradient + `<span class="text-gradient">${gradientTextContent}</span>` + currentAfterText;
                    i++;
                    setTimeout(typeWriter, 100);
                }
            };
            
            // Start typing effect after a delay
            setTimeout(typeWriter, 1000);
        }
    }

    // Interactive Decorations
    function initInteractiveDecorations() {
        const decorations = document.querySelectorAll('.decoration-item');
        
        decorations.forEach(decoration => {
            decoration.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.3)';
                this.style.opacity = '1';
            });
            
            decoration.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
                this.style.opacity = '0.7';
            });
        });
    }

    // Video Modal Enhancement
    function initVideoModal() {
        const videoModal = document.getElementById('videoModal');
        const iframe = videoModal?.querySelector('iframe');
        
        if (videoModal && iframe) {
            videoModal.addEventListener('hidden.bs.modal', function() {
                // Stop video when modal is closed
                const src = iframe.src;
                iframe.src = '';
                iframe.src = src;
            });
        }
    }

    // Floating Animation for Stats Cards
    function initFloatingStats() {
        const statCards = document.querySelectorAll('.stat-card');
        
        statCards.forEach((card, index) => {
            // Add floating animation with different delays
            card.style.animation = `float 3s ease-in-out infinite ${index * 0.5}s`;
        });
    }

    // Mouse Cursor Effect
    function initCursorEffect() {
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
        
        // Add hover effects for interactive elements
        const interactiveElements = document.querySelectorAll('a, button, .mission-card, .team-card, .timeline-card');
        
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

    // Initialize all functions
    animateCounters();
    initSmoothScrolling();
    initParallaxEffect();
    initMissionCards();
    initTeamCards();
    initTimelineAnimation();
    initScrollProgress();
    initBackToTop();
    initLazyLoading();
    initInteractiveDecorations();
    initVideoModal();
    initFloatingStats();
    
    // Initialize cursor effect only on desktop
    if (window.innerWidth > 768) {
        initCursorEffect();
}

// Add loading animation
    window.addEventListener('load', function() {
        document.body.classList.add('loaded');
        
        // Trigger entrance animations
        setTimeout(() => {
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((el, index) => {
            setTimeout(() => {
                    el.classList.add('visible');
                }, index * 100);
            });
            }, 500);
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        // Reinitialize AOS on resize
        AOS.refresh();
    });
});

// Add CSS for additional animations and effects
const additionalStyles = `
    .scroll-progress {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: rgba(255, 255, 255, 0.1);
        z-index: 9999;
    }
    
    .scroll-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #667eea, #764ba2);
        width: 0%;
        transition: width 0.3s ease;
    }
    
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
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }
    
    .back-to-top.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    
    .back-to-top:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    }
    
    .custom-cursor {
        position: fixed;
        width: 30px;
        height: 30px;
        border: 2px solid #667eea;
        border-radius: 50%;
        pointer-events: none;
        z-index: 9999;
        transition: transform 0.1s ease;
    }
    
    .cursor-dot {
        position: fixed;
        width: 6px;
        height: 6px;
        background: #667eea;
        border-radius: 50%;
        pointer-events: none;
        z-index: 9999;
    }
    
    .custom-cursor.cursor-hover {
        transform: scale(1.5);
        background: rgba(102, 126, 234, 0.1);
    }
    
    .cursor-dot.cursor-hover {
        transform: scale(2);
    }
    
    .timeline-item.animate-in .timeline-card {
        animation: slideInUp 0.6s ease forwards;
    }
    
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .lazy {
        opacity: 0;
        transition: opacity 0.3s;
    }
    
    body.loaded .lazy {
        opacity: 1;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }
    
    @media (max-width: 768px) {
        .custom-cursor,
        .cursor-dot {
            display: none;
        }
        
        .back-to-top {
            bottom: 20px;
            right: 20px;
            width: 45px;
            height: 45px;
        }
    }
`;

// Inject additional styles
const styleSheet = document.createElement('style');
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet); 