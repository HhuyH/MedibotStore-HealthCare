<?php
// Lấy current page để highlight menu
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-content">
        <!-- User Info -->
        <div class="sidebar-user">
            <div class="user-avatar">
                <div class="avatar-lg">
                    <div class="avatar-title bg-primary text-white rounded-circle">
                        <?= strtoupper(substr($_SESSION['full_name'] ?? $_SESSION['username'], 0, 1)) ?>
                    </div>
                </div>
            </div>
            <div class="user-info">
                <h6 class="mb-0"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']) ?></h6>
                <span class="text-muted small">Administrator</span>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="sidebar-nav">
            <ul class="nav flex-column">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <!-- Quản lý người dùng -->
                <li class="nav-item">
                    <a class="nav-link <?= in_array($current_page, ['users.php', 'user-add.php', 'user-edit.php', 'user-view.php']) ? 'active' : '' ?>" 
                       href="#userSubmenu" data-bs-toggle="collapse" aria-expanded="false">
                        <i class="fas fa-users"></i>
                        <span>Quản lý người dùng</span>
                        <i class="fas fa-chevron-down ms-auto"></i>
                    </a>
                    <ul class="collapse nav flex-column ms-3 <?= in_array($current_page, ['users.php', 'user-add.php', 'user-edit.php', 'user-view.php']) ? 'show' : '' ?>" 
                        id="userSubmenu">
                        <li class="nav-item">
                            <a class="nav-link <?= ($current_page == 'users.php' && (!isset($_GET['role']) || $_GET['role'] == 'all')) || $current_page == 'user-view.php' ? 'active' : '' ?>" 
                               href="users.php">
                                <i class="fas fa-list"></i>
                                <span>Tất cả người dùng</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'users.php' && isset($_GET['role']) && $_GET['role'] == 'patient' ? 'active' : '' ?>" 
                               href="users.php?role=patient">
                                <i class="fas fa-user-injured"></i>
                                <span>Bệnh nhân</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'users.php' && isset($_GET['role']) && $_GET['role'] == 'doctor' ? 'active' : '' ?>" 
                               href="users.php?role=doctor">
                                <i class="fas fa-user-md"></i>
                                <span>Bác sĩ</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'users.php' && isset($_GET['role']) && $_GET['role'] == 'admin' ? 'active' : '' ?>" 
                               href="users.php?role=admin">
                                <i class="fas fa-user-shield"></i>
                                <span>Quản trị viên</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Quản lý sản phẩm -->
                <li class="nav-item">
                    <a class="nav-link <?= in_array($current_page, ['products.php', 'product-categories.php']) ? 'active' : '' ?>" 
                       href="#productSubmenu" data-bs-toggle="collapse" aria-expanded="false">
                        <i class="fas fa-pills"></i>
                        <span>Quản lý sản phẩm</span>
                        <i class="fas fa-chevron-down ms-auto"></i>
                    </a>
                    <ul class="collapse nav flex-column ms-3 <?= in_array($current_page, ['products.php', 'product-categories.php']) ? 'show' : '' ?>" 
                        id="productSubmenu">
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'products.php' ? 'active' : '' ?>" 
                               href="products.php">
                                <i class="fas fa-list"></i>
                                <span>Danh sách sản phẩm</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'product-categories.php' ? 'active' : '' ?>" 
                               href="product-categories.php">
                                <i class="fas fa-tags"></i>
                                <span>Danh mục sản phẩm</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Quản lý đơn hàng -->
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'orders.php' ? 'active' : '' ?>" 
                       href="orders.php">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Quản lý đơn hàng</span>
                    </a>
                </li>

                <!-- Quản lý lịch hẹn -->
                <li class="nav-item">
                    <a class="nav-link <?= in_array($current_page, ['appointments.php', 'appointment-add.php', 'appointment-edit.php', 'appointment-view.php']) ? 'active' : '' ?>" 
                       href="appointments.php">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Quản lý lịch hẹn</span>
                    </a>
                </li>

                <!-- Quản lý dịch vụ -->
                <li class="nav-item">
                    <a class="nav-link <?= in_array($current_page, ['services.php', 'service-add.php', 'service-edit.php']) ? 'active' : '' ?>" 
                       href="#serviceSubmenu" data-bs-toggle="collapse" aria-expanded="false">
                        <i class="fas fa-medical-kit"></i>
                        <span>Quản lý dịch vụ</span>
                        <i class="fas fa-chevron-down ms-auto"></i>
                    </a>
                    <ul class="collapse nav flex-column ms-3 <?= in_array($current_page, ['services.php', 'service-add.php', 'service-edit.php', 'categories.php']) ? 'show' : '' ?>" 
                        id="serviceSubmenu">
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'services.php' ? 'active' : '' ?>" 
                               href="services.php">
                                <i class="fas fa-list"></i>
                                <span>Danh sách dịch vụ</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'categories.php' ? 'active' : '' ?>" 
                               href="categories.php">
                                <i class="fas fa-tags"></i>
                                <span>Danh mục dịch vụ</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Báo cáo & Thống kê -->
                <li class="nav-item">
                    <a class="nav-link <?= in_array($current_page, ['reports.php', 'analytics.php']) ? 'active' : '' ?>" 
                       href="#reportSubmenu" data-bs-toggle="collapse" aria-expanded="false">
                        <i class="fas fa-chart-bar"></i>
                        <span>Báo cáo & Thống kê</span>
                        <i class="fas fa-chevron-down ms-auto"></i>
                    </a>
                    <ul class="collapse nav flex-column ms-3 <?= in_array($current_page, ['reports.php', 'analytics.php']) ? 'show' : '' ?>" 
                        id="reportSubmenu">
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'reports.php' ? 'active' : '' ?>" 
                               href="reports.php">
                                <i class="fas fa-file-alt"></i>
                                <span>Báo cáo tổng hợp</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'analytics.php' ? 'active' : '' ?>" 
                               href="analytics.php">
                                <i class="fas fa-analytics"></i>
                                <span>Phân tích dữ liệu</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Quản lý nội dung -->
                <li class="nav-item">
                    <a class="nav-link <?= in_array($current_page, ['blog/index.php', 'blog/posts.php', 'blog/categories.php']) ? 'active' : '' ?>" 
                       href="#contentSubmenu" data-bs-toggle="collapse" aria-expanded="false">
                        <i class="fas fa-edit"></i>
                        <span>Quản lý nội dung</span>
                        <i class="fas fa-chevron-down ms-auto"></i>
                    </a>
                    <ul class="collapse nav flex-column ms-3 <?= in_array($current_page, ['blog/index.php', 'blog/posts.php', 'blog/categories.php']) ? 'show' : '' ?>" 
                        id="contentSubmenu">
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'blog/posts.php' ? 'active' : '' ?>" 
                               href="blog/posts.php">
                                <i class="fas fa-newspaper"></i>
                                <span>Bài viết blog</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'blog/categories.php' ? 'active' : '' ?>" 
                               href="blog/categories.php">
                                <i class="fas fa-tags"></i>
                                <span>Danh mục blog</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Separator -->
                <li class="nav-divider"></li>

                <!-- Quản lý Email -->
                <li class="nav-item">
                    <a class="nav-link <?= in_array($current_page, ['email-logs.php', 'email-settings.php']) ? 'active' : '' ?>" 
                       href="#emailSubmenu" data-bs-toggle="collapse" aria-expanded="false">
                        <i class="fas fa-envelope"></i>
                        <span>Quản lý Email</span>
                        <i class="fas fa-chevron-down ms-auto"></i>
                    </a>
                    <ul class="collapse nav flex-column ms-3 <?= in_array($current_page, ['email-logs.php', 'email-settings.php']) ? 'show' : '' ?>" 
                        id="emailSubmenu">
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'email-logs.php' ? 'active' : '' ?>" 
                               href="email-logs.php">
                                <i class="fas fa-history"></i>
                                <span>Lịch sử gửi email</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'email-settings.php' ? 'active' : '' ?>" 
                               href="email-settings.php">
                                <i class="fas fa-cog"></i>
                                <span>Cài đặt email</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <!-- Separator -->
                <!-- <li class="nav-divider"></li> -->

                <!-- AI Chat Box -->
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'ai-chat-box-admin.php' ? 'active' : '' ?>" 
                       href="ai-chat-box-admin.php">
                        <i class="fas fa-robot"></i>
                        <span>AI Chat Assistant</span>
                    </a>
                </li>

                <!-- Cài đặt hệ thống -->
                <li class="nav-item">
                    <a class="nav-link <?= in_array($current_page, ['settings.php', 'backup.php', 'maintenance.php']) ? 'active' : '' ?>" 
                       href="#systemSubmenu" data-bs-toggle="collapse" aria-expanded="false">
                        <i class="fas fa-cogs"></i>
                        <span>Cài đặt hệ thống</span>
                        <i class="fas fa-chevron-down ms-auto"></i>
                    </a>
                    <ul class="collapse nav flex-column ms-3 <?= in_array($current_page, ['settings.php', 'backup.php', 'maintenance.php']) ? 'show' : '' ?>" 
                        id="systemSubmenu">
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'settings.php' ? 'active' : '' ?>" 
                               href="settings.php">
                                <i class="fas fa-cog"></i>
                                <span>Cấu hình chung</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'backup.php' ? 'active' : '' ?>" 
                               href="backup.php">
                                <i class="fas fa-database"></i>
                                <span>Sao lưu & Khôi phục</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'maintenance.php' ? 'active' : '' ?>" 
                               href="maintenance.php">
                                <i class="fas fa-tools"></i>
                                <span>Bảo trì hệ thống</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Nhật ký -->
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'activity-log.php' ? 'active' : '' ?>" 
                       href="activity-log.php">
                        <i class="fas fa-history"></i>
                        <span>Nhật ký hoạt động</span>
                    </a>
                </li>

                <!-- Hồ sơ cá nhân -->
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'profile.php' ? 'active' : '' ?>" 
                       href="profile.php">
                        <i class="fas fa-user"></i>
                        <span>Hồ sơ cá nhân</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'doctor-schedules.php' ? 'active' : '' ?>" 
                       href="doctor-schedules.php">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Lịch Trực Bác Sĩ</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'doctor-schedule-view.php' ? 'active' : '' ?>" 
                       href="doctor-schedule-view.php">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Lịch làm việc</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Sidebar Footer -->
        <div class="sidebar-footer">
            <div class="d-grid gap-2">
                <a href="../index.php" class="btn btn-outline-light btn-sm" target="_blank">
                    <i class="fas fa-external-link-alt me-2"></i>Xem website
                </a>
                <a href="../logout.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                </a>
            </div>
            <div class="text-center mt-3">
                <small style="color: black !important;" class="text-muted">© 2025 Dalziel</small>
            </div>
        </div>
    </div>
</aside> 