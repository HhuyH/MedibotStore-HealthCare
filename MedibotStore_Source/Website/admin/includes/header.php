<header class="header">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <!-- Toggle Button -->
            <button class="btn btn-link text-white sidebar-toggle me-3" type="button" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Brand -->
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-clinic-medical me-2"></i>
                QickMed Admin
            </a>

            <!-- Search Form -->
            <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
                <div class="input-group">
                    <input class="form-control" type="text" placeholder="Tìm kiếm..." aria-label="Search" aria-describedby="btnNavbarSearch" />
                    <button class="btn btn-light" id="btnNavbarSearch" type="button">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            <!-- Navbar -->
            <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
                <!-- Notifications -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" id="navbarDropdownNotifications" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <span class="badge bg-danger position-absolute top-0 start-100 translate-middle badge-sm">3</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownNotifications">
                        <li><h6 class="dropdown-header">Thông báo mới</h6></li>
                        <li><a class="dropdown-item" href="#">
                            <div class="dropdown-item-content">
                                <i class="fas fa-calendar-plus me-2 text-primary"></i>
                                Lịch hẹn mới từ bệnh nhân
                                <div class="small text-muted">2 phút trước</div>
                            </div>
                        </a></li>
                        <li><a class="dropdown-item" href="#">
                            <div class="dropdown-item-content">
                                <i class="fas fa-user-plus me-2 text-success"></i>
                                Bệnh nhân mới đăng ký
                                <div class="small text-muted">15 phút trước</div>
                            </div>
                        </a></li>
                        <li><a class="dropdown-item" href="#">
                            <div class="dropdown-item-content">
                                <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
                                Lịch hẹn cần xác nhận
                                <div class="small text-muted">1 giờ trước</div>
                            </div>
                        </a></li>
                        <li><hr class="dropdown-divider" /></li>
                        <li><a class="dropdown-item" href="#">Xem tất cả thông báo</a></li>
                    </ul>
                </li>

                <!-- User Menu -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="d-flex align-items-center">
                            <div class="avatar-xs me-2">
                                <div class="avatar-title bg-light text-primary rounded-circle">
                                    <?= strtoupper(substr($_SESSION['full_name'] ?? $_SESSION['username'], 0, 1)) ?>
                                </div>
                            </div>
                            <span class="d-none d-lg-inline"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']) ?></span>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><h6 class="dropdown-header">Xin chào, <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']) ?>!</h6></li>
                        <li><hr class="dropdown-divider" /></li>
                        <li><a class="dropdown-item" href="profile.php">
                            <i class="fas fa-user me-2"></i>Hồ sơ cá nhân
                        </a></li>
                        <li><a class="dropdown-item" href="settings.php">
                            <i class="fas fa-cog me-2"></i>Cài đặt
                        </a></li>
                        <li><a class="dropdown-item" href="activity-log.php">
                            <i class="fas fa-list me-2"></i>Nhật ký hoạt động
                        </a></li>
                        <li><hr class="dropdown-divider" /></li>
                        <li><a class="dropdown-item" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
</header> 