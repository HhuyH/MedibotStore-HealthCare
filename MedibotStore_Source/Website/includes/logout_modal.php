<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="logoutModalLabel" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="closeLogoutModal()"></button>
            </div>
            <div class="modal-body text-center pt-0">
                <div class="logout-icon mb-3">
                    <i class="fas fa-sign-out-alt text-danger" style="font-size: 4rem;"></i>
                </div>
                <h4 class="mb-3">Xác nhận đăng xuất</h4>
                <p class="text-muted mb-4">Bạn có chắc chắn muốn đăng xuất khỏi hệ thống?</p>
                
                <div class="d-flex justify-content-center gap-3">
                    <button type="button" class="btn btn-danger px-4" id="logoutBtn" onclick="performLogout()">
                        <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                    </button>
                    <button type="button" class="btn btn-secondary px-4" onclick="closeLogoutModal()">
                        <i class="fas fa-times me-2"></i>Hủy
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="logoutSuccessModal" tabindex="-1" role="dialog" aria-labelledby="logoutSuccessModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="success-icon mb-3">
                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                </div>
                <h5 class="mb-2">Đăng xuất thành công!</h5>
                <p class="text-muted mb-0">Đang chuyển về trang đăng nhập...</p>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Modal JavaScript -->
<script>
let logoutModalInstance = null;
let successModalInstance = null;

// Function để xử lý logout - luôn hiển thị modal cho tất cả trang
function handleLogout() {
    console.log('handleLogout called - showing modal for all pages');
    showLogoutModal();
}

// Function để mở logout modal từ bất kỳ đâu
function showLogoutModal() {
    console.log('showLogoutModal called');
    try {
        const modalElement = document.getElementById('logoutModal');
        if (modalElement) {
            // Remove any existing backdrop
            const existingBackdrop = document.querySelector('.modal-backdrop');
            if (existingBackdrop) {
                existingBackdrop.remove();
            }
            
            // Destroy existing instance if any
            if (logoutModalInstance) {
                logoutModalInstance.dispose();
                logoutModalInstance = null;
            }
            
            // Ensure modal is hidden first
            modalElement.style.display = 'none';
            modalElement.classList.remove('show');
            
            // Create new instance with specific options
            logoutModalInstance = new bootstrap.Modal(modalElement, {
                backdrop: 'static', // Prevent backdrop clicks from closing
                keyboard: true,
                focus: true
            });
            
            // Add event listeners
            modalElement.addEventListener('shown.bs.modal', function() {
                console.log('Modal shown event fired');
                // Ensure modal content is clickable
                const modalDialog = modalElement.querySelector('.modal-dialog');
                if (modalDialog) {
                    modalDialog.style.pointerEvents = 'auto';
                    modalDialog.style.zIndex = '1056';
                }
            });
            
            modalElement.addEventListener('hidden.bs.modal', function() {
                console.log('Modal hidden event fired');
                // Clean up backdrop
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
            });
            
            logoutModalInstance.show();
            console.log('Modal shown successfully');
        } else {
            console.error('Modal element not found');
        }
    } catch (error) {
        console.error('Error showing modal:', error);
    }
}

// Function để đóng logout modal
function closeLogoutModal() {
    console.log('closeLogoutModal called');
    try {
        if (logoutModalInstance) {
            logoutModalInstance.hide();
        } else {
            // Fallback method
            const modalElement = document.getElementById('logoutModal');
            if (modalElement) {
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }
            }
        }
        
        // Force remove backdrop
        setTimeout(() => {
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
        }, 100);
        
    } catch (error) {
        console.error('Error closing modal:', error);
    }
}

// Function để thực hiện logout
function performLogout() {
    console.log('performLogout called');
    
    // Disable button and show loading
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.disabled = true;
        logoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang đăng xuất...';
    }
    
    // Perform AJAX logout
    fetch('logout.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'logout=1'
    })
    .then(response => response.json())
    .then(data => {
        console.log('Logout response:', data);
        
        if (data.success) {
            // Close current modal
            closeLogoutModal();
            
            // Show success modal
            setTimeout(() => {
                try {
                    const successModalElement = document.getElementById('logoutSuccessModal');
                    if (successModalElement) {
                        successModalInstance = new bootstrap.Modal(successModalElement, {
                            backdrop: 'static',
                            keyboard: false
                        });
                        successModalInstance.show();
                        
                        // Redirect after 1.5 seconds
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 1500);
                    }
                } catch (error) {
                    console.error('Error showing success modal:', error);
                    // Fallback redirect
                    window.location.href = 'login.php';
                }
            }, 300);
        } else {
            console.error('Logout failed:', data.message);
            // Reset button
            if (logoutBtn) {
                logoutBtn.disabled = false;
                logoutBtn.innerHTML = '<i class="fas fa-sign-out-alt me-2"></i>Đăng xuất';
            }
            // alert('Đăng xuất thất bại: ' + (data.message || 'Lỗi không xác định'));
        }
    })
    .catch(error => {
        console.error('Logout error:', error);
        // Reset button
        if (logoutBtn) {
            logoutBtn.disabled = false;
            logoutBtn.innerHTML = '<i class="fas fa-sign-out-alt me-2"></i>Đăng xuất';
        }
        alert('Có lỗi xảy ra khi đăng xuất. Vui lòng thử lại.');
    });
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, setting up modal events');
    
    // Force high z-index for modals
    const modal = document.getElementById('logoutModal');
    const successModal = document.getElementById('logoutSuccessModal');
    
    if (modal) {
        modal.style.zIndex = '999999';
        modal.style.position = 'fixed';
    }
    if (successModal) {
        successModal.style.zIndex = '999999';
        successModal.style.position = 'fixed';
    }
    
    // Handle modal events
    const logoutModal = document.getElementById('logoutModal');
    if (logoutModal) {
        logoutModal.addEventListener('shown.bs.modal', function () {
            console.log('Modal shown event fired');
            // Force modal to be on top
            this.style.zIndex = '999999';
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.style.zIndex = '999998';
            }
        });
        
        logoutModal.addEventListener('hidden.bs.modal', function () {
            console.log('Modal hidden event fired');
            // Reset button if modal is closed
            const logoutBtn = document.getElementById('logoutBtn');
            if (logoutBtn) {
                logoutBtn.disabled = false;
                logoutBtn.innerHTML = '<i class="fas fa-sign-out-alt me-2"></i>Đăng xuất';
            }
        });
    }
    
    // Debug function for index.php
    window.debugModal = function() {
        console.log('Modal debug info:');
        console.log('Modal element:', document.getElementById('logoutModal'));
        console.log('Modal z-index:', window.getComputedStyle(document.getElementById('logoutModal')).zIndex);
        console.log('Backdrop:', document.querySelector('.modal-backdrop'));
        console.log('Medical header z-index:', window.getComputedStyle(document.querySelector('.medical-header')).zIndex);
    };
});
</script>

<style>
/* Logout Modal Styles - Clean and Working */
.modal {
    z-index: 999999 !important;
}

.modal-backdrop {
    z-index: 999998 !important;
    background-color: rgba(0, 0, 0, 0.5) !important;
}

.modal.show {
    display: block !important;
}

.modal-dialog {
    margin: 1.75rem auto !important;
    pointer-events: none !important;
}

.modal-content {
    pointer-events: auto !important;
}

.modal-content {
    border: none !important;
    border-radius: 20px !important;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4) !important;
    background: #ffffff !important;
    position: relative !important;
}

/* Close button */
.btn-close {
    position: absolute !important;
    top: 15px !important;
    right: 15px !important;
    z-index: 10000000 !important;
    opacity: 0.7 !important;
    font-size: 1.2rem !important;
}

.btn-close:hover {
    opacity: 1 !important;
    transform: scale(1.1) !important;
}

/* Modal body styling */
.modal-body {
    padding: 2rem !important;
}

/* Logout icon */
.logout-icon i {
    color: #dc3545 !important;
    text-shadow: 0 0 20px rgba(220, 53, 69, 0.3) !important;
}

/* Success icon */
.success-icon i {
    color: #28a745 !important;
    text-shadow: 0 0 20px rgba(40, 167, 69, 0.3) !important;
    animation: pulse 1s ease-in-out !important;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
}

/* Button styling */
.modal .btn {
    border-radius: 25px !important;
    font-weight: 600 !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    padding: 12px 24px !important;
    font-size: 0.95rem !important;
    border: none !important;
    position: relative !important;
    overflow: hidden !important;
    cursor: pointer !important;
    user-select: none !important;
}

/* .btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10%;
    transform: translate(-50%, -50%);
    transition: width 0.3s, height 0.3s;
} */

.btn:hover::before {
    width: 300px;
    height: 300px;
}

.btn:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2) !important;
}

.btn:active {
    transform: translateY(-1px) !important;
}

/* Danger button */
.btn-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
    color: white !important;
}

.btn-danger:hover {
    background: linear-gradient(135deg, #c82333 0%, #a71e2a 100%) !important;
    box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4) !important;
    color: white !important;
}

.btn-danger:disabled {
    background: #6c757d !important;
    cursor: not-allowed !important;
    transform: none !important;
}

/* Secondary button */
.btn-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%) !important;
    color: white !important;
}

.btn-secondary:hover {
    background: linear-gradient(135deg, #5a6268 0%, #495057 100%) !important;
    box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4) !important;
    color: white !important;
}

/* Success modal special styling */
#logoutSuccessModal .modal-content {
    border: 3px solid #28a745 !important;
    background: linear-gradient(135deg, #ffffff 0%, #f8fff9 100%) !important;
}

#logoutSuccessModal h5 {
    color: #155724 !important;
    font-weight: 700 !important;
}

#logoutSuccessModal .text-muted {
    color: #6c757d !important;
}

/* Loading spinner */
.fa-spinner {
    animation: spin 1s linear infinite !important;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Modal animations */
.modal.fade .modal-dialog {
    transform: translate(0, -50px) !important;
    transition: transform 0.3s ease-out !important;
}

.modal.show .modal-dialog {
    transform: translate(0, 0) !important;
}

/* Responsive design */
@media (max-width: 576px) {
    .modal-dialog {
        margin: 10px !important;
    }
    
    .modal-content {
        border-radius: 15px !important;
    }
    
    .modal-body {
        padding: 1.5rem !important;
    }
    
    .btn {
        padding: 10px 20px !important;
        font-size: 0.9rem !important;
    }
}

/* Modal positioning - fix backdrop issues */
.modal {
    z-index: 1055 !important;
}

.modal-backdrop {
    z-index: 1050 !important;
    pointer-events: none !important; /* Allow clicks to pass through backdrop */
}

.modal-backdrop.show {
    opacity: 0.5 !important;
}

/* Ensure modal content is clickable */
.modal-dialog {
    pointer-events: auto !important;
    z-index: 1056 !important;
    position: relative !important;
}

/* Ensure proper modal centering */
.modal-dialog-centered {
    display: flex !important;
    align-items: center !important;
    min-height: calc(100% - 1rem) !important;
}

/* Force all modal elements to be clickable */
.modal-content,
.modal-content * {
    pointer-events: auto !important;
}
</style> 