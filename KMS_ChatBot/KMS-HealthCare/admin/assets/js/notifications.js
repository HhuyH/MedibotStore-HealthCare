// Notifications JavaScript - MediCare Admin
class NotificationManager {
    constructor() {
        this.apiUrl = 'api/get-notifications.php';
        this.refreshInterval = 30000; // 30 seconds
        this.isLoading = false;
        this.notifications = [];
        
        this.init();
    }
    
    init() {
        // Load notifications when page loads
        this.loadNotifications();
        
        // Set up periodic refresh
        setInterval(() => {
            this.loadNotifications();
        }, this.refreshInterval);
        
        // Set up event listeners
        this.setupEventListeners();
        
        // Load notifications when dropdown is shown
        document.addEventListener('shown.bs.dropdown', (e) => {
            if (e.target.closest('#notificationDropdown')) {
                this.loadNotifications();
            }
        });
    }
    
    setupEventListeners() {
        // Mark all as read
        const markAllReadBtn = document.getElementById('markAllRead');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.markAllAsRead();
            });
        }
    }
    
    async loadNotifications() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        
        try {
            const response = await fetch(this.apiUrl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.notifications = data.notifications;
                this.updateUI(data);
            } else {
                this.showError(data.error || 'Không thể tải thông báo');
            }
            
        } catch (error) {
            console.error('Error loading notifications:', error);
            this.showError('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            this.isLoading = false;
        }
    }
    
    updateUI(data) {
        // Update notification count
        this.updateNotificationCount(data.unread_count);
        
        // Update notification list
        this.updateNotificationList(data.notifications);
        
        // Update badge animation
        this.updateBadgeAnimation(data.unread_count);
    }
    
    updateNotificationCount(count) {
        const countElement = document.getElementById('notificationCount');
        const badgeElement = document.querySelector('.notification-badge');
        
        if (countElement) {
            countElement.textContent = count > 0 ? `${count} mới` : '0';
        }
        
        if (badgeElement) {
            badgeElement.textContent = count;
            badgeElement.style.display = count > 0 ? 'flex' : 'none';
        }
    }
    
    updateNotificationList(notifications) {
        const listElement = document.getElementById('notificationList');
        if (!listElement) return;
        
        if (notifications.length === 0) {
            listElement.innerHTML = `
                <li class="notification-empty">
                    <i class="fas fa-bell-slash"></i>
                    <p>Không có thông báo nào</p>
                </li>
            `;
            return;
        }
        
        // Chỉ hiển thị tối đa 5 notifications để tránh scroll
        const displayNotifications = notifications.slice(0, 5);
        
        const notificationHTML = displayNotifications.map(notification => {
            return `
                <li>
                    <a class="dropdown-item notification-item ${notification.is_read ? 'read' : 'unread'}" 
                       href="${notification.link}" 
                       data-notification-id="${notification.id}">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <div class="notification-icon ${notification.icon_color}">
                                    <i class="${notification.icon}"></i>
                                </div>
                            </div>
                            <div class="notification-content">
                                <h6>${this.escapeHtml(notification.title)}</h6>
                                <p>${this.escapeHtml(notification.message)}</p>
                                <small><i class="fas fa-clock"></i> ${this.escapeHtml(notification.time)}</small>
                            </div>
                        </div>
                    </a>
                </li>
            `;
        }).join('');
        
        listElement.innerHTML = notificationHTML;
        
        // Add click handlers to notification items
        this.setupNotificationClickHandlers();
    }
    
    setupNotificationClickHandlers() {
        const notificationItems = document.querySelectorAll('.notification-item');
        notificationItems.forEach(item => {
            item.addEventListener('click', (e) => {
                const notificationId = item.dataset.notificationId;
                if (notificationId && !item.classList.contains('read')) {
                    this.markAsRead(notificationId);
                }
            });
        });
    }
    
    updateBadgeAnimation(count) {
        const badgeElement = document.querySelector('.notification-badge');
        if (badgeElement && count > 0) {
            badgeElement.style.animation = 'none';
            setTimeout(() => {
                badgeElement.style.animation = 'bounce 2s infinite';
            }, 10);
        }
    }
    
    async markAsRead(notificationId) {
        try {
            const response = await fetch('api/mark-notification-read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ notification_id: notificationId })
            });
            
            if (response.ok) {
                // Reload notifications to update count
                this.loadNotifications();
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }
    
    async markAllAsRead() {
        try {
            const response = await fetch('api/mark-all-notifications-read.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (response.ok) {
                // Reload notifications to update count
                this.loadNotifications();
                
                // Show success message
                this.showToast('Đã đánh dấu tất cả thông báo là đã đọc', 'success');
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
            this.showToast('Có lỗi xảy ra', 'error');
        }
    }
    
    showError(message) {
        const listElement = document.getElementById('notificationList');
        if (listElement) {
            listElement.innerHTML = `
                <li class="notification-error">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${this.escapeHtml(message)}
                </li>
            `;
        }
    }
    
    showToast(message, type = 'info') {
        // Simple alert for now
        alert(message);
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Public methods
    refresh() {
        this.loadNotifications();
    }
    
    getNotifications() {
        return this.notifications;
    }
    
    getUnreadCount() {
        return this.notifications.filter(n => !n.is_read).length;
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're in admin area
    if (document.querySelector('#notificationDropdown')) {
        window.notificationManager = new NotificationManager();
    }
});

// Add CSS for unread indicator
const style = document.createElement('style');
style.textContent = `
    .notification-item.unread {
        background: linear-gradient(135deg, rgba(52, 152, 219, 0.05), rgba(41, 128, 185, 0.05));
        border-left: 3px solid #3498db;
    }
    
    .unread-indicator {
        width: 8px;
        height: 8px;
        background: #3498db;
        border-radius: 50%;
        position: absolute;
        top: 50%;
        right: 15px;
        transform: translateY(-50%);
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { transform: translateY(-50%) scale(1); opacity: 1; }
        50% { transform: translateY(-50%) scale(1.2); opacity: 0.7; }
        100% { transform: translateY(-50%) scale(1); opacity: 1; }
    }
    
    .notification-item {
        position: relative;
    }
    
    .notification-item.read {
        opacity: 0.8;
    }
    
    .toast-container {
        z-index: 9999 !important;
    }
`;
document.head.appendChild(style); 