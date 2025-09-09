<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determine current page for active navigation
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<style>
/* Body padding to prevent content being hidden behind fixed header */
body {
    padding-top: 140px; /* Adjust based on header height */
}

/* Two-Tier Medical Header Styles */
.medical-header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 999990;
    width: 100%;
    box-shadow: 0 4px 20px rgba(25, 118, 210, 0.1);
    background: white;
    transition: top 0.3s ease-in-out; /*  hiệu ứng scolll */
}

/* Enhanced scrolled state */
.medical-header.scrolled {
    box-shadow: 0 8px 30px rgba(25, 118, 210, 0.2);
}

.medical-header.scrolled .top-bar {
    background: linear-gradient(135deg, #1565c0 0%, #1976d2 100%);
}

/* Ensure header is always on top */
.medical-header * {
    position: relative;
    z-index: inherit;
}

/* Top Bar */
.top-bar {
    background: linear-gradient(135deg, #1976d2 0%, #1e88e5 100%);
    color: white;
    padding: 0.6rem 0;
    font-size: 0.85rem;
}

.top-bar-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 100%;
    padding: 0 2rem;
    margin: 0;
}

.top-bar-left {
    display: flex;
    align-items: center;
    gap: 2rem;
}

.top-bar-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: rgba(255, 255, 255, 0.9);
    text-decoration: none;
    transition: all 0.3s ease;
    font-weight: 500;
}

.top-bar-item:hover {
    color: white;
    transform: translateY(-1px);
}

.top-bar-item i {
    font-size: 0.9rem;
}

.top-bar-right {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.download-app {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255, 255, 255, 0.15);
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
    font-weight: 500;
}

.download-app:hover {
    background: rgba(255, 255, 255, 0.25);
    color: white;
    transform: translateY(-1px);
}

/* Main Header */
.main-header {
    background: #ffffff;
    border-bottom: 1px solid #e3f2fd;
    padding: 1rem 0;
}

.main-header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 100%;
    padding: 0 2rem;
    margin: 0;
}

/* Logo Section */
.logo-section {
    display: flex;
    align-items: center;
    text-decoration: none;
    transition: all 0.3s ease;
}

.logo-section:hover {
    transform: translateY(-2px);
}

.logo-image {
    width: 55px;
    height: 55px;
    margin-right: 15px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(25, 118, 210, 0.2);
    transition: all 0.3s ease;
}

.logo-section:hover .logo-image {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(25, 118, 210, 0.3);
}

.logo-text {
    display: flex;
    flex-direction: column;
}

.logo-name {
    font-size: 1.8rem;
    font-weight: 800;
    color: #1976d2;
    line-height: 1.1;
    margin: 0;
    letter-spacing: -0.5px;
}

.logo-subtitle {
    font-size: 0.75rem;
    font-weight: 600;
    color: #64b5f6;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    margin: 0;
}

/* Search Bar */
.search-bar {
    flex: 1;
    max-width: 500px;
    margin: 0 2rem;
    position: relative;
}

.search-input-group {
    position: relative;
    display: flex;
    align-items: center;
}

.search-input {
    width: 100%;
    padding: 0.8rem 3.5rem 0.8rem 1rem;
    border: 2px solid #e3f2fd;
    border-radius: 25px;
    font-size: 0.95rem;
    outline: none;
    transition: all 0.3s ease;
    background: #f8faff;
}

.search-input:focus {
    border-color: #1976d2;
    background: white;
    box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
}

.search-input::placeholder {
    color: #90a4ae;
}

.search-btn {
    position: absolute;
    right: 8px;
    background: #1976d2;
    border: none;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
}

.search-btn:hover {
    background: #1565c0;
    transform: scale(1.05);
}

.search-suggestions {
    display: flex;
    gap: 1rem;
    margin-top: 0.5rem;
    flex-wrap: wrap;
}

.suggestion-tag {
    background: #e3f2fd;
    color: #1976d2;
    padding: 0.3rem 0.8rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.suggestion-tag:hover {
    background: #1976d2;
    color: white;
    transform: translateY(-1px);
}

/* Action Area */
.action-area {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.action-icon {
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: #f8faff;
    border: 2px solid #e3f2fd;
    color: #1976d2;
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
    font-size: 1.1rem;
}

.action-icon:hover {
    background: #e3f2fd;
    border-color: #bbdefb;
    color: #1565c0;
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(25, 118, 210, 0.2);
}

/* Cart with Badge */
.cart-icon {
    position: relative;
}

.cart-count {
    position: absolute;
    top: -8px;
    right: -8px;
    background: linear-gradient(135deg, #f44336, #e53935);
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 700;
    border: 2px solid white;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* User Account */
.user-account {
    position: relative;
    z-index: 999999;
}

.user-btn {
    position: relative;
    z-index: 999999;
    display: flex;
    align-items: center;
    padding: 0.6rem 1rem;
    border-radius: 10px;
    background: #f8faff;
    border: 2px solid #e3f2fd;
    color: #37474f;
    font-weight: 500;
    transition: all 0.3s ease;
    text-decoration: none;
    cursor: pointer;
}

.user-btn:hover {
    background: #e3f2fd;
    border-color: #bbdefb;
    color: #1976d2;
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(25, 118, 210, 0.15);
}

.user-avatar {
    width: 35px;
    height: 35px;
    border-radius: 8px;
    background: linear-gradient(135deg, #1976d2, #42a5f5);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    margin-right: 0.7rem;
    font-size: 0.95rem;
}

.user-info {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.user-name {
    font-size: 0.95rem;
    font-weight: 600;
    line-height: 1.2;
    max-width: 120px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.user-role {
    font-size: 0.75rem;
    color: #64b5f6;
    line-height: 1;
}

/* Auth Buttons */
.auth-btn {
    display: flex;
    align-items: center;
    padding: 0.7rem 1.3rem;
    border-radius: 10px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 0.95rem;
}

.login-btn {
    background: #f8faff;
    border: 2px solid #1976d2;
    color: #1976d2;
}

.login-btn:hover {
    background: #1976d2;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(25, 118, 210, 0.3);
}

.appointment-btn {
    background: linear-gradient(135deg, #4caf50, #66bb6a);
    color: white;
    border: 2px solid transparent;
    margin-left: 0.5rem;
}

.appointment-btn:hover {
    background: linear-gradient(135deg, #43a047, #5cb85c);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(76, 175, 80, 0.3);
}

/* Navigation Bar */
.nav-bar {
    background: #ffffff;
    border-top: 1px solid #e3f2fd;
    padding: 0.8rem 0;
}

.nav-content {
    display: flex;
    align-items: center;
    justify-content: center;
    max-width: 100%;
    padding: 0 2rem;
    margin: 0;
}

.main-nav {
    display: flex;
    align-items: center;
    gap: 3rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.nav-item {
    position: relative;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 0.6rem 1rem;
    font-weight: 500;
    font-size: 0.95rem;
    color: #37474f;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
    position: relative;
}

.nav-link:hover {
    color: #1976d2;
    background: rgba(25, 118, 210, 0.08);
}

.nav-link.active {
    color: #1976d2;
    background: rgba(25, 118, 210, 0.12);
    font-weight: 600;
}

.nav-link i {
    margin-right: 0.5rem;
    width: 16px;
    text-align: center;
    font-size: 0.9rem;
}

/* Navigation dropdown */
.dropdown-menu {
    position: absolute !important;
    top: 100% !important;
    left: 0 !important;
    min-width: 250px;
    padding: 0.5rem;
    margin: 0 !important;
    background: #ffffff;
    border: 1px solid #e3f2fd;
    border-radius: 0 0 10px 10px;
    box-shadow: 0 10px 30px rgba(25, 118, 210, 0.15);
    z-index: 10000 !important;
    display: none;
    animation: dropdownFadeIn 0.2s ease;
    transform: none !important;
}

.dropdown-menu.show {
    display: block;
    transform: none !important;
}

/* Ghi đè style của Bootstrap dropdown */
.dropdown-menu[data-bs-popper] {
    transform: none !important;
    top: 100% !important;
    margin-top: 0 !important;
    z-index: 10000 !important;
}

/* User account dropdown */
.user-account .dropdown-menu {
    right: 0 !important;
    left: auto !important;
    top: calc(100% - 2px) !important; /* Dính liền với button */
    border-radius: 0 0 10px 10px;
   
}

.dropdown-item {
    position: relative;
    z-index: 100003;
    padding: 0.7rem 1rem;
    border-radius: 6px;
    font-weight: 500;
    color: #37474f;
    transition: background-color 0.2s ease;
    display: flex;
    align-items: center;
    margin-bottom: 0.2rem;
    text-decoration: none;
}

.dropdown-item:hover {
    background-color: rgba(25, 118, 210, 0.08);
    color: #1976d2;
}

.dropdown-item i {
    width: 18px;
    margin-right: 0.7rem;
    color: #64b5f6;
    font-size: 0.9rem;
    transition: color 0.2s ease;
}

.dropdown-item:hover i {
    color: #1976d2;
}

@keyframes dropdownFadeIn {
    from {
        opacity: 0;
        transform: translateY(-5px); /* Giảm khoảng cách animation */
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Mobile responsive */
@media (max-width: 991.98px) {
    body {
        padding-top: 120px; /* Smaller padding for mobile */
    }
    
    .top-bar {
        display: none;
    }
    
    .main-header-content {
        padding: 0 1rem;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .search-bar {
        order: 3;
        width: 100%;
        margin: 0;
        max-width: none;
    }
    
    .mobile-toggle {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
    
    .nav-bar {
        display: none; /* Hide by default on mobile */
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 1999;
        background: white;
        border-top: 1px solid #e3f2fd;
        box-shadow: 0 4px 15px rgba(25, 118, 210, 0.1);
    }
    
    .nav-bar.show {
        display: block; /* Only show when toggled */
        animation: slideDown 0.3s ease;
    }
    
    .main-nav {
        flex-direction: column;
        gap: 0;
        padding: 1rem;
    }
    
    .nav-link {
        padding: 1rem;
        width: 100%;
        justify-content: flex-start;
        border-radius: 8px;
        margin-bottom: 0.5rem;
    }
    
    .action-area {
        gap: 0.5rem;
    }
    
    .appointment-btn {
        display: none;
    }
    
    .search-suggestions {
        display: none;
    }
    
    .user-account .dropdown-menu,
    .nav-item .dropdown-menu {
        position: static !important;
        box-shadow: none;
        border: none;
        background: #f8faff !important;
        animation: none;
        margin-top: 0.5rem;
        padding: 0.5rem;
        width: 100%;
    }

    /* Add slide down animation for mobile menu */
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
}

@media (max-width: 768px) {
    body {
        padding-top: 100px; /* Even smaller for tablets */
    }
    
    .top-bar-content {
        padding: 0 1rem;
    }
    
    .top-bar-left {
        gap: 1rem;
    }
    
    .top-bar-right {
        gap: 1rem;
    }
    
    .logo-name {
        font-size: 1.5rem;
    }
    
    .logo-subtitle {
        font-size: 0.7rem;
    }
    
    .user-info {
        display: none;
    }
}

@media (max-width: 576px) {
    body {
        padding-top: 90px; /* Smallest for phones */
    }
    
    .top-bar-left .top-bar-item:nth-child(2) {
        display: none;
    }
    
    .main-header-content {
        padding: 0 0.5rem;
    }
    
    .nav-content {
        padding: 0 0.5rem;
    }
}

/* Animations */
.medical-header {
    animation: slideDown 0.6s ease;
}

@keyframes slideDown {
    from {
        transform: translateY(-100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Smooth scrolling */
* {
    scroll-behavior: smooth;
}

/* Custom Dropdown Styles */
.custom-dropdown {
    position: relative;
    display: inline-block;
}

.custom-dropdown-btn {
    display: flex;
    align-items: center;
    padding: 8px 16px;
    background: #f8faff;
    border: 2px solid #e3f2fd;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.custom-dropdown-btn:hover {
    background: #e3f2fd;
    border-color: #bbdefb;
}

.user-avatar {
    width: 35px;
    height: 35px;
    border-radius: 8px;
    background: linear-gradient(135deg, #1976d2, #42a5f5);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    margin-right: 12px;
}

.user-info {
    text-align: left;
}

.user-name {
    font-weight: 600;
    color: #37474f;
    margin-bottom: 2px;
    font-size: 0.95rem;
}

.user-role {
    color: #64b5f6;
    font-size: 0.75rem;
}

.custom-dropdown-menu {
    position: absolute !important;
    top: calc(100% + 12px) !important;
    right: 0 !important;
    width: 280px;
    background: #ffffff !important;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(102, 126, 234, 0.15), 0 4px 16px rgba(0, 0, 0, 0.08) !important;
    border: 1px solid rgba(102, 126, 234, 0.1);
    padding: 12px;
    display: none !important;
    z-index: 999999 !important;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-15px) scale(0.95);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.custom-dropdown-menu::before {
    content: '';
    position: absolute;
    top: -8px;
    right: 20px;
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-bottom: 8px solid #ffffff;
    filter: drop-shadow(0 -2px 4px rgba(102, 126, 234, 0.1));
}

.custom-dropdown-menu.active {
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
    transform: translateY(0) scale(1) !important;
}

.menu-item {
    margin: 2px 0;
    position: relative;
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.menu-item a {
    display: flex !important;
    align-items: center;
    padding: 14px 18px;
    color: #2c3e50 !important;
    text-decoration: none !important;
    border-radius: 12px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    width: 100%;
    box-sizing: border-box;
    font-weight: 500;
    font-size: 14px;
    position: relative;
    overflow: hidden;
}

.menu-item a:hover {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white !important;
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.menu-item a:hover i {
    color: white !important;
    transform: scale(1.1);
}

.menu-item i {
    width: 20px;
    margin-right: 14px;
    color: #667eea;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.menu-divider {
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.2), transparent);
    margin: 12px 16px;
    border: none;
}

.menu-item.logout a {
    color: #e74c3c !important;
}

.menu-item.logout a:hover {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%) !important;
    color: white !important;
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
}

.menu-item.logout i {
    color: #e74c3c !important;
}

.menu-item.logout a:hover i {
    color: white !important;
}

@keyframes menuFadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Force visibility for all menu items */
.custom-dropdown-menu .menu-item,
.custom-dropdown-menu .menu-item a,
.custom-dropdown-menu .menu-item span,
.custom-dropdown-menu .menu-item i {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

/* Override any conflicting styles */
.custom-dropdown {
    position: static !important;
    z-index: 999999 !important;
}

.custom-dropdown .custom-dropdown-menu {
    position: absolute !important;
    z-index: 999999 !important;
    top: calc(100% + 8px) !important;
    right: 0 !important;
    pointer-events: auto !important;
}

.custom-dropdown .custom-dropdown-menu.active {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    transform: translateY(0) !important;
    pointer-events: auto !important;
}

@media (max-width: 768px) {
    .custom-dropdown-menu {
        position: fixed;
        top: auto;
        bottom: 0;
        left: 0;
        right: 0;
        width: 100%;
        border-radius: 20px 20px 0 0;
        box-shadow: 0 -5px 20px rgba(0,0,0,0.1);
        padding: 16px;
    }

    .menu-item a {
        padding: 16px;
    }
}

/* Mobile Toggle Button */
.mobile-toggle {
    display: none;
    background: transparent;
    border: none;
    padding: 10px;
    cursor: pointer;
    position: relative;
    z-index: 2000;
    width: 45px;
    height: 45px;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.mobile-toggle:hover {
    background: rgba(25, 118, 210, 0.08);
}

.mobile-toggle .toggle-line {
    width: 24px;
    height: 2px;
    background: #1976d2;
    display: block;
    margin: 5px auto;
    position: relative;
    transition: all 0.3s ease-in-out;
    border-radius: 2px;
}

.mobile-toggle.active {
    background: rgba(25, 118, 210, 0.12);
}

.mobile-toggle.active .toggle-line:nth-child(1) {
    transform: rotate(45deg) translate(5px, 5px);
}

.mobile-toggle.active .toggle-line:nth-child(2) {
    opacity: 0;
    transform: translateX(-10px);
}

.mobile-toggle.active .toggle-line:nth-child(3) {
    transform: rotate(-45deg) translate(5px, -5px);
}

@media (max-width: 991.98px) {
    .mobile-toggle {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
}
</style>

<!-- Two-Tier Medical Header -->
<header class="medical-header">
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container-fluid top-bar-content">
            <div class="top-bar-left">
                <a href="#" class="top-bar-item">
                    <i class="fas fa-search"></i>
                    <span>Tra thuốc chính hãng</span>
                    <strong>Kiểm tra ngay</strong>
                </a>
                <a href="tel:18006928" class="top-bar-item">
                    <i class="fas fa-phone"></i>
                    <span>Tư vấn ngay: Dalziel Đẹp Trai</span>
                </a>
            </div>
            <div class="top-bar-right">
                <a href="#" class="download-app">
                    <i class="fas fa-mobile-alt"></i>
                    <span>Tải ứng dụng</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <div class="main-header">
        <div class="container-fluid main-header-content">
            <!-- Logo Section -->
            <a href="/index.php" class="logo-section">
                <img src="/assets/images/default-avatar.png" alt="MediBot Store" class="logo-image">
                <div class="logo-text">
                    <h1 class="logo-name">MediSync</h1>
                    <p class="logo-subtitle">Medical & Healthcare</p>
            </div>
            </a>

            <!-- Search Bar -->
            <div class="search-bar">
                <form action="/search.php" method="GET" class="search-input-group">
                    <input type="text" name="q" class="search-input" placeholder="Tìm thuốc, thực phẩm chức năng, thiết bị y tế..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                <!-- <div class="search-suggestions">
                    <span class="suggestion-tag">Omega 3</span>
                    <span class="suggestion-tag">Canxi</span>
                    <span class="suggestion-tag">Vitamin</span>
                    <span class="suggestion-tag">Máy đo huyết áp</span>
                </div> -->
            </div>

            <!-- Mobile Toggle -->
            <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle menu">
                <span class="toggle-line"></span>
                <span class="toggle-line"></span>
                <span class="toggle-line"></span>
            </button>

            <!-- Action Area -->
            <div class="action-area">
                <!-- Cart -->
                <a href="/cart.php" class="action-icon cart-icon" title="Giỏ hàng">
                    <i class="fas fa-shopping-cart"></i>
                    <?php
                    $cart_count = 0;
                    if (isset($_SESSION['user_id'])) {
                        $cart_sql = "
                            SELECT SUM(oi.quantity) as total_quantity
                            FROM order_items oi
                            JOIN orders o ON oi.order_id = o.order_id
                            WHERE o.user_id = ? AND o.status = 'cart'
                        ";
                        $cart_stmt = $conn->prepare($cart_sql);
                        $cart_stmt->bind_param('i', $_SESSION['user_id']);
                        $cart_stmt->execute();
                        $cart_result = $cart_stmt->get_result()->fetch_assoc();
                        $cart_count = $cart_result['total_quantity'] ?? 0;
                    }
                    ?>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                </a>

                <!-- User Account -->
                <?php if (isset($_SESSION['user_id'])): ?>
                <div class="custom-dropdown">
                    <button class="custom-dropdown-btn" onclick="toggleUserMenu(event)">
                        <div class="user-avatar">
                            <?= strtoupper(substr($_SESSION['full_name'] ?? $_SESSION['username'], 0, 1)) ?>
                        </div>
                        <div class="user-info d-none d-md-block">
                            <div class="user-name">
                                <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']) ?>
                            </div>
                            <div class="user-role">
                                <?php 
                                $role_names = ["admin" => 'Quản trị viên', "patient" => 'Bệnh nhân', "doctor" => 'Bác sĩ'];
                                echo $role_names[$_SESSION['role_name']] ?? 'Người dùng';
                                ?>
                            </div>
                        </div>
                    </button>
                    <div class="custom-dropdown-menu" id="userMenu">
                        <div class="menu-item">
                            <a href="/profile.php">
                                <i class="fas fa-user"></i>
                                <span>Hồ sơ cá nhân</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a href="/appointments.php">
                                <i class="fas fa-calendar-check"></i>
                                <span>Lịch hẹn</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a href="/medical-records.php">
                                <i class="fas fa-file-medical"></i>
                                <span>Hồ sơ bệnh án</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a href="/orders.php">
                                <i class="fas fa-shopping-bag"></i>
                                <span>Đơn hàng</span>
                            </a>
                        </div>
                        <?php if ($_SESSION['role_name'] == 'admin'): ?>
                        <div class="menu-divider"></div>
                        <div class="menu-item">
                            <a href="/admin/dashboard.php">
                                <i class="fas fa-cogs"></i>
                                <span>Quản trị</span>
                            </a>
                        </div>
                        <?php endif; ?>
                        <div class="menu-divider"></div>
                        <div class="menu-item logout">
                            <a href="/logout.php">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Đăng xuất</span>
                            </a>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <a href="/login.php" class="auth-btn login-btn">
                    <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập
                </a>
                <?php endif; ?>
                
                <!-- Appointment Button -->
                <button type="button" class="auth-btn appointment-btn d-none d-lg-flex" onclick="openAppointmentModal()">
                    <i class="fas fa-calendar-plus me-2"></i>Đặt lịch khám
                </button>
      </div>
    </div>
  </div>

    <!-- Navigation Bar -->
    <div class="nav-bar" id="navBar">
        <div class="container-fluid nav-content">
            <nav class="main-nav">
                <div class="nav-item">
                    <a href="/index.php" class="nav-link <?= ($current_page == 'index') ? 'active' : '' ?>">
                        <i class="fas fa-home"></i>Trang chủ
                    </a>
                </div>
                
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle <?= ($current_page == 'services') ? 'active' : '' ?>" data-bs-toggle="dropdown">
                        <i class="fas fa-stethoscope"></i>Dịch vụ khám
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/services.php"><i class="fas fa-list"></i>Tất cả dịch vụ</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/services/cardiology.php"><i class="fas fa-heartbeat"></i>Tim mạch</a></li>
                        <li><a class="dropdown-item" href="/services/orthopedics.php"><i class="fas fa-bone"></i>Chỉnh hình</a></li>
                        <li><a class="dropdown-item" href="/services/gynecology.php"><i class="fas fa-person-pregnant"></i>Sản phụ khoa</a></li>
                        <li><a class="dropdown-item" href="/services/pediatrics.php"><i class="fas fa-baby"></i>Nhi khoa</a></li>
                    </ul>
        </div>
        
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle <?= ($current_page == 'doctors') ? 'active' : '' ?>" data-bs-toggle="dropdown">
                        <i class="fas fa-user-md"></i>Đội ngũ bác sĩ
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/doctors.php"><i class="fas fa-users"></i>Tất cả bác sĩ</a></li>
                        <li><a class="dropdown-item" href="/appointments.php"><i class="fas fa-calendar-plus"></i>Đặt lịch khám</a></li>
                    </ul>
            </div>

                <div class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle <?= ($current_page == 'shop') ? 'active' : '' ?>" data-bs-toggle="dropdown">
                        <i class="fas fa-store"></i>Cửa hàng
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/shop.php"><i class="fas fa-pills"></i>Tất cả sản phẩm</a></li>
                        <li><a class="dropdown-item" href="/shop.php?cat=medicine"><i class="fas fa-capsules"></i>Thuốc</a></li>
                        <li><a class="dropdown-item" href="/shop.php?cat=supplements"><i class="fas fa-leaf"></i>Thực phẩm chức năng</a></li>
                        <li><a class="dropdown-item" href="/shop.php?cat=devices"><i class="fas fa-heartbeat"></i>Thiết bị y tế</a></li>
                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item" href="orders.php"><i class="fas fa-shopping-bag"></i>Đơn hàng của tôi</a></li>
                    </ul>
        </div>
        
                <div class="nav-item">
                    <a href="/about.php" class="nav-link <?= ($current_page == 'about') ? 'active' : '' ?>">
                        <i class="fas fa-info-circle"></i>Giới thiệu
                    </a>
                </div>

                <div class="nav-item">
                    <a href="/blog.php" class="nav-link <?= ($current_page == 'blog') ? 'active' : '' ?>">
                        <i class="fas fa-newspaper"></i>Tin tức
                    </a>
                </div>

                <div class="nav-item">
                    <a href="/contact.php" class="nav-link <?= ($current_page == 'contact') ? 'active' : '' ?>">
                        <i class="fas fa-phone"></i>Liên hệ
                    </a>
                </div>
            </nav>
                </div>
                </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add header class to body for styling reference
    document.body.classList.add('has-fixed-header');
    
    // Mobile menu toggle
    const mobileToggle = document.getElementById('mobileToggle');
    const navBar = document.getElementById('navBar');
    let isMenuOpen = false;
    
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            isMenuOpen = !isMenuOpen;
            navBar.classList.toggle('show');
            this.classList.toggle('active');
        });
    }

    // Search functionality
    const searchForm = document.querySelector('.search-bar form');
    const searchInput = document.querySelector('.search-input');
    const searchBtn = document.querySelector('.search-btn');
    
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const query = searchInput.value.trim();
            if (!query) {
                e.preventDefault();
                searchInput.focus();
            }
        });
    }

    // Suggestion tags
    document.querySelectorAll('.suggestion-tag').forEach(tag => {
        tag.addEventListener('click', function() {
            const query = this.textContent.trim();
            searchInput.value = query;
            searchForm.submit();
        });
    });

    // Close mobile menu when clicking outside
    document.addEventListener('click', function(e) {
        if (isMenuOpen && !e.target.closest('.mobile-toggle') && !e.target.closest('.nav-bar')) {
            navBar.classList.remove('show');
            mobileToggle.classList.remove('active');
            isMenuOpen = false;
        }
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 991) {
            navBar.style.display = 'block'; // Show menu on desktop
            mobileToggle.classList.remove('active');
            isMenuOpen = false;
        } else {
            if (!isMenuOpen) {
                navBar.classList.remove('show');
            }
        }
    });
});

function toggleUserMenu(event) {
    event.stopPropagation();
    event.preventDefault();
    
    const menu = document.getElementById('userMenu');
    const isActive = menu.classList.contains('active');
    
    // Close all other dropdowns first
    document.querySelectorAll('.custom-dropdown-menu').forEach(m => {
        m.classList.remove('active');
    });
    
    // Toggle current menu
    if (!isActive) {
        menu.classList.add('active');
    }
    
    // Close when clicking outside
    document.addEventListener('click', function closeMenu(e) {
        if (!e.target.closest('.custom-dropdown')) {
            menu.classList.remove('active');
            document.removeEventListener('click', closeMenu);
        }
    });
}

let lastScrollTop = 0;
  const header = document.querySelector(".medical-header");

  window.addEventListener("scroll", function () {
    const currentScroll = window.pageYOffset || document.documentElement.scrollTop;

    if (currentScroll > lastScrollTop && currentScroll > 100) {
      // Cuộn xuống
      header.style.top = "-140px"; // Ẩn đi
    } else {
      // Cuộn lên
      header.style.top = "0"; // Hiện lại
    }

    lastScrollTop = currentScroll <= 0 ? 0 : currentScroll; // Prevent negative
  });



  document.getElementById("logout").addEventListener("click", async () => {
  try {
    // Gọi logout.php để server logout
    const response = await fetch("logout.php", {
      method: "POST",
      credentials: "include" // giữ cookie/session
    });

    if (!response.ok) throw new Error("Logout không thành công");

    // Xóa localStorage
    localStorage.removeItem("userInfo");

    // Redirect về trang login
    window.location.href = "login.php";
  } catch (err) {
    alert("Có lỗi khi logout: " + err.message);
  }
});


</script>

