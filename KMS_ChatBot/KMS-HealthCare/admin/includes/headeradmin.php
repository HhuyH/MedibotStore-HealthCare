<header class="header">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
        <div class="container-fluid">
            <!-- Toggle Button -->
            <button class="btn btn-link text-dark sidebar-toggle me-3" type="button" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Brand -->
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <div class="brand-logo me-2">
                    <i class="fas fa-clinic-medical text-primary"></i>
                </div>
                <span class="brand-text">MediCare <small class="admin-badge">Admin Panel</small></span>
            </a>

            <!-- Search Form -->
            <form class="search-form d-none d-md-flex ms-auto me-4">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input class="form-control" type="text" placeholder="Tìm kiếm..." />
                </div>
            </form>

            <!-- Navbar -->
            <ul class="navbar-nav">
                <!-- Notifications -->
                <li class="nav-item dropdown me-2">
                    <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">
                            3
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="min-width: 320px;" id="notificationDropdown">
                        <li class="dropdown-header d-flex justify-content-between">
                            <span>Thông báo</span>
                            <span class="badge bg-primary" id="notificationCount">0</span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <div id="notificationList">
                            <li class="notification-loading">
                                <i class="fas fa-spinner fa-spin me-2"></i>Đang tải...
                            </li>
                        </div>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-center small" href="#" id="markAllRead">
                                <i class="fas fa-check-double me-2"></i>Đánh dấu tất cả đã đọc
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- User Menu -->
                <li class="nav-item dropdown">
                    <a class="nav-link d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <div class="user-avatar-navbar me-2">
                            <span>
                                <?= strtoupper(substr($_SESSION['full_name'] ?? $_SESSION['username'], 0, 1)) ?>
                            </span>
                        </div>
                        <span class="d-none d-lg-inline user-info-text">
                            <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']) ?>
                        </span>
                        <i class="fas fa-chevron-down ms-2 small"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <li class="user-dropdown-header">
                            <div class="d-flex align-items-center">
                                <div class="user-dropdown-avatar me-3">
                                    <span>
                                        <?= strtoupper(substr($_SESSION['full_name'] ?? $_SESSION['username'], 0, 1)) ?>
                                    </span>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']) ?></h6>
                                    <small>Administrator</small>
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
                </li>
            </ul>
        </div>
    </nav>
</header> 