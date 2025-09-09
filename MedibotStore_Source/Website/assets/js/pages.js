// Pages Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize animations
    initializeAnimations();
    
    // Initialize page card interactions
    initializePageCards();
    
    // Initialize quick link cards
    initializeQuickLinks();
    
    // Initialize scroll animations
    initializeScrollAnimations();
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

// Page card interactions
function initializePageCards() {
    const pageCards = document.querySelectorAll('.page-card');
    
    pageCards.forEach(card => {
        const overlay = card.querySelector('.page-overlay');
        const icon = card.querySelector('.page-icon');
        const features = card.querySelectorAll('.page-features li');
        const button = card.querySelector('.btn');
        
        card.addEventListener('mouseenter', () => {
            // Animate overlay
            if (overlay) {
                overlay.style.opacity = '1';
            }
            
            // Animate icon
            if (icon) {
                icon.style.transform = 'scale(1.2) rotate(10deg)';
            }
            
            // Animate features
            features.forEach((feature, index) => {
                setTimeout(() => {
                    feature.style.transform = 'translateX(10px)';
                    feature.style.color = '#667eea';
                }, index * 100);
            });
            
            // Animate button
            if (button) {
                button.style.transform = 'translateY(-3px)';
                button.style.boxShadow = '0 10px 25px rgba(102, 126, 234, 0.3)';
            }
        });
        
        card.addEventListener('mouseleave', () => {
            // Reset overlay
            if (overlay) {
                overlay.style.opacity = '0';
            }
            
            // Reset icon
            if (icon) {
                icon.style.transform = 'scale(1) rotate(0deg)';
            }
            
            // Reset features
            features.forEach(feature => {
                feature.style.transform = 'translateX(0)';
                feature.style.color = '';
            });
            
            // Reset button
            if (button) {
                button.style.transform = 'translateY(0)';
                button.style.boxShadow = '';
            }
        });
    });
}

// Quick link card interactions
function initializeQuickLinks() {
    const quickLinkCards = document.querySelectorAll('.quick-link-card');
    
    quickLinkCards.forEach(card => {
        const icon = card.querySelector('.quick-link-icon');
        const button = card.querySelector('.btn');
        
        card.addEventListener('mouseenter', () => {
            // Animate icon
            if (icon) {
                icon.style.transform = 'scale(1.1) rotate(5deg)';
                icon.style.background = 'linear-gradient(135deg, #667eea, #764ba2)';
            }
            
            // Animate button
            if (button) {
                button.style.transform = 'scale(1.05)';
            }
            
            // Add glow effect
            card.style.boxShadow = '0 15px 35px rgba(102, 126, 234, 0.2)';
        });
        
        card.addEventListener('mouseleave', () => {
            // Reset icon
            if (icon) {
                icon.style.transform = 'scale(1) rotate(0deg)';
                icon.style.background = '';
            }
            
            // Reset button
            if (button) {
                button.style.transform = 'scale(1)';
            }
            
            // Remove glow effect
            card.style.boxShadow = '';
        });
    });
}

// Initialize scroll animations
function initializeScrollAnimations() {
    const animatedElements = document.querySelectorAll(
        '.page-card, .quick-link-card, .section-title'
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
        element.style.transition = `all 0.6s ease ${index * 0.1}s`;
        
        observer.observe(element);
    });
    
    // Add CSS for animate-in class
    const style = document.createElement('style');
    style.textContent = `
        .animate-in {
            opacity: 1 !important;
            transform: translateY(0) !important;
        }
        
        .page-card.animate-in {
            animation: fadeInUp 0.6s ease forwards;
        }
        
        .quick-link-card.animate-in {
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
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
    `;
    document.head.appendChild(style);
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
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
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
        <style>
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>
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

// Initialize quick access functionality
function initializeQuickAccess() {
    const quickAccessBtns = document.querySelectorAll('[data-quick-access]');
    
    quickAccessBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const action = btn.getAttribute('data-quick-access');
            
            switch (action) {
                case 'appointment':
                    showAppointmentModal();
                    break;
                case 'emergency':
                    showEmergencyModal();
                    break;
                case 'contact':
                    window.location.href = '/contact.php';
                    break;
                default:
                    console.log('Unknown quick access action:', action);
            }
        });
    });
}

// Show appointment modal
function showAppointmentModal() {
    const modal = document.createElement('div');
    modal.innerHTML = `
        <div class="modal fade" id="appointmentModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Đặt lịch hẹn</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Chức năng đặt lịch hẹn sẽ được triển khai sớm.</p>
                        <p>Hiện tại, vui lòng liên hệ hotline: <a href="tel:0123456789">0123 456 789</a></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <a href="tel:0123456789" class="btn btn-primary">Gọi ngay</a>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    const bootstrapModal = new bootstrap.Modal(document.getElementById('appointmentModal'));
    bootstrapModal.show();
    
    // Remove modal when hidden
    document.getElementById('appointmentModal').addEventListener('hidden.bs.modal', () => {
        modal.remove();
    });
}

// Show emergency modal
function showEmergencyModal() {
    const modal = document.createElement('div');
    modal.innerHTML = `
        <div class="modal fade" id="emergencyModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-ambulance me-2"></i>Cấp cứu 24/7
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="emergency-icon mb-3">
                            <i class="fas fa-phone-alt" style="font-size: 3rem; color: #dc3545;"></i>
                        </div>
                        <h4>Gọi ngay số cấp cứu</h4>
                        <p class="mb-3">Dịch vụ cấp cứu 24/7 luôn sẵn sàng</p>
                        <div class="emergency-numbers">
                            <a href="tel:0123456789" class="btn btn-danger btn-lg mb-2 w-100">
                                <i class="fas fa-phone me-2"></i>0123 456 789
                            </a>
                            <a href="tel:115" class="btn btn-outline-danger btn-lg w-100">
                                <i class="fas fa-ambulance me-2"></i>115 (Cấp cứu quốc gia)
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    const bootstrapModal = new bootstrap.Modal(document.getElementById('emergencyModal'));
    bootstrapModal.show();
    
    // Remove modal when hidden
    document.getElementById('emergencyModal').addEventListener('hidden.bs.modal', () => {
        modal.remove();
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    addLoadingAnimation();
    initializeSmoothScrolling();
    initializeQuickAccess();
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

// Add typing effect for hero title
function addTypingEffect() {
    const heroTitle = document.querySelector('.hero-title');
    if (!heroTitle) return;
    
    const text = heroTitle.textContent;
    heroTitle.textContent = '';
    heroTitle.style.borderRight = '2px solid white';
    
    let i = 0;
    const typeWriter = () => {
        if (i < text.length) {
            heroTitle.textContent += text.charAt(i);
            i++;
            setTimeout(typeWriter, 100);
        } else {
            // Remove cursor after typing
            setTimeout(() => {
                heroTitle.style.borderRight = 'none';
            }, 1000);
        }
    };
    
    // Start typing after a delay
    setTimeout(typeWriter, 500);
}

// Initialize typing effect
setTimeout(addTypingEffect, 1000); 