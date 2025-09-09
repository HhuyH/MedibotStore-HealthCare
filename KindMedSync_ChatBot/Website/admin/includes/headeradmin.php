<header class="header">
    <nav class="navbar navbar-light bg-white shadow-sm fixed-top">
        <div class="container-fluid px-3">
            <div class="d-flex align-items-center w-100">
                <!-- Toggle Button -->
                <button class="btn btn-outline-secondary btn-sm me-3" type="button" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>

                <!-- Brand -->
                <a class="navbar-brand me-auto" href="dashboard.php">
                    <i class="fas fa-clinic-medical text-primary me-2"></i>
                    <span class="fw-bold">MediCare</span>
                    <small class="text-muted ms-1 d-none d-md-inline">Admin</small>
                </a>

                <!-- Right Side -->
                <div class="d-flex align-items-center">
                    <!-- Search Toggle (Mobile) -->
                    <button class="btn btn-outline-secondary btn-sm me-2 d-md-none" type="button" 
                            data-bs-toggle="collapse" data-bs-target="#mobileSearch">
                        <i class="fas fa-search"></i>
                    </button>

                    <!-- Search Form (Desktop) -->
                    <form class="me-3 d-none d-md-block">
                        <div class="input-group input-group-sm">
                            <input class="form-control" type="search" placeholder="Tìm kiếm..." style="width: 200px;">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>

                    <!-- Notifications -->
                    <div class="dropdown me-2">
                        <button class="btn btn-outline-secondary btn-sm position-relative" type="button" 
                                data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge" 
                                  style="font-size: 0.6rem; display: none;">0</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow" style="width: 300px;">
                            <li class="dropdown-header d-flex justify-content-between">
                                <span>Thông báo</span>
                                <span class="badge bg-primary" id="notificationCount">0</span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <div id="notificationList" style="max-height: 300px; overflow-y: auto;">
                                <li class="px-3 py-2 text-center text-muted">
                                    <i class="fas fa-spinner fa-spin me-2"></i>Đang tải...
                                </li>
                            </div>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-center" href="#" id="markAllRead">
                                    <i class="fas fa-check-double me-2"></i>Đánh dấu tất cả đã đọc
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- User Menu -->
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm d-flex align-items-center" type="button" 
                                data-bs-toggle="dropdown">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" 
                                 style="width: 24px; height: 24px; font-size: 0.75rem;">
                                <?= strtoupper(substr($_SESSION['full_name'] ?? $_SESSION['username'], 0, 1)) ?>
                            </div>
                            <span class="d-none d-lg-inline small"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']) ?></span>
                            <i class="fas fa-chevron-down ms-1 small"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li class="dropdown-header">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" 
                                         style="width: 32px; height: 32px;">
                                        <?= strtoupper(substr($_SESSION['full_name'] ?? $_SESSION['username'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-medium"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']) ?></div>
                                        <small class="text-muted">Administrator</small>
                                    </div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user me-2"></i>Hồ sơ cá nhân
                            </a></li>
                            <li><a class="dropdown-item" href="settings.php">
                                <i class="fas fa-cog me-2"></i>Cài đặt
                            </a></li>
                            <li><a class="dropdown-item" href="../index.php" target="_blank">
                                <i class="fas fa-external-link-alt me-2"></i>Xem website
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Search -->
    <div class="collapse" id="mobileSearch">
        <div class="bg-light p-3 border-bottom">
            <form>
                <div class="input-group input-group-sm">
                    <input class="form-control" type="search" placeholder="Tìm kiếm...">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</header>

<style>
.header {
    margin-bottom: 56px;
}

.navbar {
    height: 56px;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.navbar-brand {
    font-size: 1.1rem;
    text-decoration: none;
    color: #333;
}

.navbar-brand:hover {
    color: var(--bs-primary);
}

.notification-badge {
    min-width: 16px;
    height: 16px;
}

.notification-item-clickable:hover {
    background-color: #f8f9fa !important;
    transition: background-color 0.2s ease;
}

/* Mobile adjustments */
@media (max-width: 767.98px) {
    .navbar-brand {
        font-size: 1rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Lấy dữ liệu thông báo từ API
    function fetchNotifications() {
        fetch('api/get-notifications.php')
            .then(response => response.json())
            .then(data => {
                const notificationList = document.getElementById('notificationList');
                const notificationCount = document.getElementById('notificationCount');
                const notificationBadge = document.querySelector('.notification-badge');
                
                // Kiểm tra nếu API trả về thành công
                if (data.success && data.notifications) {
                    const notifications = data.notifications;
                    
                    // Cập nhật số lượng thông báo chưa đọc
                    const unreadCount = data.unread_count || 0;
                notificationCount.textContent = unreadCount;
                
                    // Chỉ hiển thị badge khi có thông báo chưa đọc
                if (unreadCount > 0) {
                    notificationBadge.style.display = 'inline-flex';
                    notificationBadge.textContent = unreadCount;
                } else {
                    notificationBadge.style.display = 'none';
                }

                    // Xóa loading message
                notificationList.innerHTML = '';

                    // Nếu không có thông báo
                    if (notifications.length === 0) {
                    notificationList.innerHTML = `
                        <li class="px-3 py-2 text-center text-muted">
                            <i class="fas fa-bell-slash me-2"></i>Không có thông báo nào
                        </li>
                    `;
                    return;
                }

                    // Hiển thị các thông báo
                    notifications.forEach(notification => {
                    const notificationItem = document.createElement('li');
                    notificationItem.className = 'px-3 py-2 border-bottom notification-item-clickable';
                    notificationItem.style.cursor = 'pointer';
                        
                        // Highlight thông báo chưa đọc
                        if (!notification.is_read) {
                        notificationItem.classList.add('bg-light');
                    }
                    
                    notificationItem.innerHTML = `
                        <div class="small">
                            <div class="d-flex align-items-center">
                                    <i class="${notification.icon || getNotificationIcon(notification.type)} text-primary me-2"></i>
                                <div class="flex-grow-1">
                                        <div class="${!notification.is_read ? 'fw-bold' : ''}">${notification.title || notification.message}</div>
                                        <div class="text-muted small">${notification.message !== notification.title ? notification.message : ''}</div>
                                        <small class="text-muted">${notification.time}</small>
                                </div>
                            </div>
                        </div>
                    `;
                    
                        // Thêm sự kiện click
                    notificationItem.addEventListener('click', function() {
                            if (notification.link && notification.link !== '#') {
                                window.location.href = notification.link;
                            }
                    });
                    
                    notificationList.appendChild(notificationItem);
                });
                } else {
                    throw new Error(data.error || 'Invalid response format');
                }
            })
            .catch(error => {
                console.error('Error fetching notifications:', error);
                document.getElementById('notificationList').innerHTML = `
                    <li class="px-3 py-2 text-center text-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>Không thể tải thông báo
                    </li>
                `;
                
                // Hiển thị dữ liệu test khi API không khả dụng
                showTestNotifications();
            });
    }

    // Hiển thị dữ liệu thông báo test khi API không khả dụng
    function showTestNotifications() {
        const notificationList = document.getElementById('notificationList');
        const notificationCount = document.getElementById('notificationCount');
        const notificationBadge = document.querySelector('.notification-badge');
        
        // Dữ liệu test
        const testNotifications = [
            {
                id: 'test_1',
                title: 'Lịch hẹn mới',
                message: 'Có 3 lịch hẹn mới cần xác nhận',
                type: 'appointment',
                icon: 'fas fa-calendar-plus',
                is_read: false,
                time: 'Vừa xong',
                link: 'appointments.php'
            },
            {
                id: 'test_2',
                title: 'Đơn hàng mới', 
                message: 'Đơn hàng #12345 đã được thanh toán',
                type: 'order',
                icon: 'fas fa-shopping-cart',
                is_read: false,
                time: '1 giờ trước',
                link: 'orders.php'
            },
            {
                id: 'test_3',
                title: 'Người dùng mới',
                message: 'Nguyễn Văn A đăng ký làm bệnh nhân',
                type: 'user',
                icon: 'fas fa-user-plus',
                is_read: true,
                time: '2 giờ trước',
                link: 'users.php'
            }
        ];
        
        const unreadCount = testNotifications.filter(n => !n.is_read).length;
        notificationCount.textContent = unreadCount;
        
        if (unreadCount > 0) {
            notificationBadge.style.display = 'inline-flex';
            notificationBadge.textContent = unreadCount;
        } else {
            notificationBadge.style.display = 'none';
        }
        
        notificationList.innerHTML = '';
        testNotifications.forEach(notification => {
            const notificationItem = document.createElement('li');
            notificationItem.className = 'px-3 py-2 border-bottom notification-item-clickable';
            notificationItem.style.cursor = 'pointer';
            if (!notification.is_read) {
                notificationItem.classList.add('bg-light');
            }
            
            notificationItem.innerHTML = `
                <div class="small">
                    <div class="d-flex align-items-center">
                        <i class="${notification.icon} text-primary me-2"></i>
                        <div class="flex-grow-1">
                            <div class="${!notification.is_read ? 'fw-bold' : ''}">${notification.title}</div>
                            <div class="text-muted small">${notification.message}</div>
                            <small class="text-muted">${notification.time}</small>
                        </div>
                    </div>
                </div>
            `;
            
            // Thêm sự kiện click
            notificationItem.addEventListener('click', function() {
                if (notification.link) {
                    window.location.href = notification.link;
                }
            });
            
            notificationList.appendChild(notificationItem);
        });
    }

    // Lấy icon cho từng loại thông báo
    function getNotificationIcon(type) {
        const icons = {
            'appointment': 'fas fa-calendar-check',
            'order': 'fas fa-shopping-cart',
            'user': 'fas fa-user',
            'system': 'fas fa-cog',
            'default': 'fas fa-bell'
        };
        return icons[type] || icons.default;
    }

    // Đánh dấu tất cả thông báo đã đọc
    document.getElementById('markAllRead').addEventListener('click', function(e) {
        e.preventDefault();
        
        // Đơn giản hóa: chỉ cập nhật UI
            document.querySelector('.notification-badge').style.display = 'none';
            document.getElementById('notificationCount').textContent = '0';
            
        // Xóa highlight của các thông báo chưa đọc
            document.querySelectorAll('#notificationList .bg-light').forEach(item => {
                item.classList.remove('bg-light');
                const boldText = item.querySelector('.fw-bold');
                if (boldText) {
                    boldText.classList.remove('fw-bold');
                }
            });
        
        // Thông báo cho người dùng
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
        alertDiv.style.cssText = 'top: 70px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>Đã đánh dấu tất cả thông báo là đã đọc
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);
        
        // Tự động ẩn sau 3 giây
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 3000);
    });

    // Khởi tạo tải thông báo
    fetchNotifications();

    // Cập nhật thông báo mỗi 2 phút
    setInterval(fetchNotifications, 120000);
});
</script> 