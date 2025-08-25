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
    z-index: 100000;
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
    position: relative;
    z-index: 9999;
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

/* =====================================================
   MOBILE RESPONSIVE DESIGN - CLEAN & MODERN
   ===================================================== */

@media (max-width: 991.98px) {
    /* Reset body padding for mobile */
    body {
        padding-top: 70px !important;
    }
    
    /* Hide top bar completely on mobile */
    .top-bar {
        display: none !important;
    }
    
    /* Mobile header container */
    .medical-header {
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1) !important;
    }
    
    /* Clean mobile header */
    .main-header {
        padding: 0.875rem 0 !important;
        background: linear-gradient(135deg, #ffffff 0%, #f8faff 100%) !important;
        border-bottom: none !important;
        box-shadow: 0 2px 12px rgba(25, 118, 210, 0.08) !important;
    }
    
    .main-header-content {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        padding: 0 1rem !important;
        gap: 1rem !important;
        flex-wrap: nowrap !important;
        max-width: 100% !important;
        margin: 0 !important;
    }
    
    /* Logo section - optimized for mobile */
    .logo-section {
        flex-shrink: 0 !important;
        display: flex !important;
        align-items: center !important;
    }
    
    .logo-image {
        width: 40px !important;
        height: 40px !important;
        margin-right: 12px !important;
        border-radius: 10px !important;
        box-shadow: 0 2px 8px rgba(25, 118, 210, 0.15) !important;
    }
    
    .logo-text {
        display: flex !important;
        flex-direction: column !important;
    }
    
    .logo-name {
        font-size: 1.35rem !important;
        font-weight: 800 !important;
        color: #1976d2 !important;
        line-height: 1.1 !important;
        margin: 0 !important;
    }
    
    .logo-subtitle {
        font-size: 0.65rem !important;
        font-weight: 600 !important;
        color: #64b5f6 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        margin: 0 !important;
    }
    
    /* Hide desktop search bar on mobile */
    .search-bar {
        display: none !important;
    }
    
    /* Mobile action area - clean button layout */
    .action-area {
        display: flex !important;
        align-items: center !important;
        gap: 0.625rem !important;
        flex-shrink: 0 !important;
    }
    
    /* Search toggle button - mobile only */
    .search-toggle-btn {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 42px !important;
        height: 42px !important;
        border: none !important;
        border-radius: 12px !important;
        background: #1976d2 !important;
        color: white !important;
        font-size: 1rem !important;
        cursor: pointer !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        box-shadow: 0 3px 8px rgba(25, 118, 210, 0.25) !important;
    }
    
    .search-toggle-btn:hover,
    .search-toggle-btn.active {
        background: #1565c0 !important;
        transform: translateY(-2px) scale(1.05) !important;
        box-shadow: 0 6px 20px rgba(25, 118, 210, 0.4) !important;
    }
    
    /* Beautiful mobile buttons */
    .action-icon,
    .custom-dropdown-btn,
    .auth-btn,
    .mobile-toggle {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 42px !important;
        height: 42px !important;
        border: none !important;
        border-radius: 12px !important;
        background: #f8faff !important;
        border: 2px solid #e3f2fd !important;
        color: #1976d2 !important;
        font-size: 1rem !important;
        cursor: pointer !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        text-decoration: none !important;
        position: relative !important;
        box-shadow: 0 2px 6px rgba(25, 118, 210, 0.1) !important;
    }
    
    /* Button hover effects */
    .action-icon:hover,
    .custom-dropdown-btn:hover,
    .auth-btn:hover,
    .mobile-toggle:hover {
        background: #1976d2 !important;
        border-color: #1976d2 !important;
        color: white !important;
        transform: translateY(-2px) scale(1.05) !important;
        box-shadow: 0 6px 20px rgba(25, 118, 210, 0.3) !important;
    }
    
    /* Active states */
    .mobile-toggle.active {
        background: #1976d2 !important;
        border-color: #1976d2 !important;
        color: white !important;
        transform: scale(0.95) !important;
        box-shadow: 0 2px 8px rgba(25, 118, 210, 0.4) !important;
    }
    
    /* Cart icon styling */
    .cart-icon {
        position: relative !important;
    }
    
    .cart-count {
        position: absolute !important;
        top: -8px !important;
        right: -8px !important;
        background: #ff4757 !important;
        color: white !important;
        font-size: 0.7rem !important;
        font-weight: 800 !important;
        width: 20px !important;
        height: 20px !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        border: 3px solid white !important;
        z-index: 10 !important;
        box-shadow: 0 2px 8px rgba(255, 71, 87, 0.4) !important;
        line-height: 1 !important;
    }
    
    /* Beautiful mobile navigation animation */
    @keyframes mobileNavSlide {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* User account button */
    .custom-dropdown-btn {
        padding: 0.25rem !important;
        width: auto !important;
        min-width: 42px !important;
        gap: 0.5rem !important;
    }
    
    /* Hide user info text on mobile */
    .user-info {
        display: none !important;
    }
    
    /* Mobile user avatar */
    .user-avatar {
        width: 30px !important;
        height: 30px !important;
        border-radius: 8px !important;
        background: linear-gradient(135deg, #1976d2, #42a5f5) !important;
        color: white !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 0.8rem !important;
        font-weight: 700 !important;
        margin: 0 !important;
    }
    
    /* Login button mobile */
    .auth-btn {
        padding: 0 !important;
        font-size: 0.95rem !important;
    }
    
    /* Hide appointment button on mobile */
    .appointment-btn {
        display: none !important;
    }
    
    /* Mobile hamburger menu */
    .mobile-toggle {
        flex-direction: column !important;
        gap: 4px !important;
        order: 10 !important;
        background: #f8faff !important;
        border: 2px solid #e3f2fd !important;
    }
    
    .mobile-toggle:hover {
        background: #1976d2 !important;
        border-color: #1976d2 !important;
    }
    
    .mobile-toggle:hover .toggle-line {
        background: white !important;
    }
    
    .toggle-line {
        width: 18px !important;
        height: 2.5px !important;
        background: #1976d2 !important;
        border-radius: 2px !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    
    .mobile-toggle.active .toggle-line:nth-child(1) {
        transform: rotate(45deg) translate(5px, 5px) !important;
    }
    
    .mobile-toggle.active .toggle-line:nth-child(2) {
        opacity: 0 !important;
        transform: translateX(-10px) !important;
    }
    
    .mobile-toggle.active .toggle-line:nth-child(3) {
        transform: rotate(-45deg) translate(5px, -5px) !important;
    }
    
    /* Mobile navigation menu */
    .nav-bar {
        display: none !important;
        position: absolute !important;
        top: 100% !important;
        left: 0 !important;
        right: 0 !important;
        background: linear-gradient(135deg, #f8faff 0%, #ffffff 100%) !important;
        border-top: 1px solid #e3f2fd !important;
        box-shadow: 0 8px 25px rgba(25, 118, 210, 0.15) !important;
        z-index: 9998 !important;
    }
    
    .nav-bar.show {
        display: block !important;
        animation: mobileNavSlide 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    
    .nav-content {
        padding: 0 !important;
        background: transparent !important;
    }
    
    .main-nav {
        flex-direction: column !important;
        gap: 0 !important;
        padding: 1.5rem 1rem 2rem !important;
        margin: 0 !important;
        list-style: none !important;
    }
    
    .nav-item {
        width: 100% !important;
        margin: 0 !important;
    }
    
    .nav-link {
        display: flex !important;
        align-items: center !important;
        padding: 1rem 1.25rem !important;
        color: #374151 !important;
        text-decoration: none !important;
        border-radius: 12px !important;
        margin-bottom: 0.75rem !important;
        font-weight: 500 !important;
        font-size: 0.95rem !important;
        background: white !important;
        border: 1px solid #f1f5f9 !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        position: relative !important;
        overflow: hidden !important;
    }
    
    .nav-link::before {
        content: '' !important;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 0 !important;
        height: 100% !important;
        background: linear-gradient(90deg, #1976d2, #42a5f5) !important;
        transition: width 0.3s ease !important;
    }
    
    .nav-link:hover::before,
    .nav-link.active::before {
        width: 4px !important;
    }
    
    .nav-link:hover,
    .nav-link.active {
        background: #f8faff !important;
        color: #1976d2 !important;
        transform: translateX(4px) !important;
        box-shadow: 0 4px 12px rgba(25, 118, 210, 0.15) !important;
        border-color: #e3f2fd !important;
    }
    
    .nav-link i {
        margin-right: 1rem !important;
        width: 20px !important;
        text-align: center !important;
        font-size: 1rem !important;
        color: #64b5f6 !important;
        transition: all 0.2s ease !important;
    }
    
    .nav-link:hover i,
    .nav-link.active i {
        color: #1976d2 !important;
        transform: scale(1.1) !important;
    }
    
    /* Mobile dropdown menus */
    .dropdown-menu {
        position: static !important;
        background: #f9fafb !important;
        border: none !important;
        box-shadow: none !important;
        margin: 0.5rem 0 0 2.5rem !important;
        padding: 0.75rem !important;
        border-radius: 8px !important;
        width: auto !important;
        border: 1px solid #f1f5f9 !important;
    }
    
    .dropdown-item {
        padding: 0.75rem 1rem !important;
        color: #6b7280 !important;
        border-radius: 6px !important;
        font-size: 0.9rem !important;
        margin-bottom: 0.25rem !important;
        transition: all 0.2s ease !important;
    }
    
    .dropdown-item:hover {
        background: #e5e7eb !important;
        color: #374151 !important;
        transform: translateX(2px) !important;
    }
    
    /* Mobile user dropdown - bottom sheet style */
    .custom-dropdown-menu {
        position: fixed !important;
        top: auto !important;
        bottom: 0 !important;
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
        background: white !important;
        border-radius: 20px 20px 0 0 !important;
        box-shadow: 0 -10px 30px rgba(0, 0, 0, 0.15) !important;
        z-index: 100002 !important;
        max-height: 80vh !important;
        overflow-y: auto !important;
        transform: translateY(100%) !important;
        transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        padding: 0 !important;
    }
    
    .custom-dropdown-menu.active {
        transform: translateY(0) !important;
    }
    
    /* Drag handle */
    .custom-dropdown-menu::before {
        content: '' !important;
        position: absolute !important;
        top: 12px !important;
        left: 50% !important;
        transform: translateX(-50%) !important;
        width: 40px !important;
        height: 4px !important;
        background: #d1d5db !important;
        border-radius: 2px !important;
        opacity: 0.6 !important;
    }
    
    /* Mobile menu items */
    .menu-item {
        margin: 0 !important;
        padding: 0 1.5rem !important;
    }
    
    .menu-item:first-child {
        margin-top: 2rem !important;
    }
    
    .menu-item a {
        display: flex !important;
        align-items: center !important;
        padding: 1rem 0 !important;
        color: #374151 !important;
        text-decoration: none !important;
        border-bottom: 1px solid #f3f4f6 !important;
        transition: all 0.2s ease !important;
        font-size: 0.95rem !important;
        font-weight: 500 !important;
    }
    
    .menu-item:last-child a {
        border-bottom: none !important;
        margin-bottom: 1.5rem !important;
    }
    
    .menu-item a:hover {
        color: #1976d2 !important;
        padding-left: 0.5rem !important;
    }
    
    .menu-item i {
        width: 20px !important;
        margin-right: 1rem !important;
        color: #64b5f6 !important;
        font-size: 1rem !important;
        text-align: center !important;
    }
    
    .menu-item a:hover i {
        color: #1976d2 !important;
    }
    
    .menu-divider {
        height: 1px !important;
        background: #f3f4f6 !important;
        margin: 0.5rem 1.5rem !important;
        border: none !important;
    }
    
    .menu-item.logout a {
        color: #ef4444 !important;
    }
    
    .menu-item.logout i {
        color: #ef4444 !important;
    }
    
    .menu-item.logout a:hover {
        color: #dc2626 !important;
        background: #fef2f2 !important;
        margin: 0 -1.5rem !important;
        padding-left: 2rem !important;
        border-radius: 8px !important;
    }
    
    /* Animation for mobile navigation */
    @keyframes mobileNavSlide {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
}

/* Tablet specific optimizations */
@media (max-width: 768px) {
    body {
        padding-top: 65px !important;
    }
    
    .main-header {
        padding: 0.75rem 0 !important;
    }
    
    .main-header-content {
        padding: 0 0.875rem !important;
        gap: 0.875rem !important;
    }
    
    .logo-image {
        width: 36px !important;
        height: 36px !important;
        margin-right: 10px !important;
    }
    
    .logo-name {
        font-size: 1.25rem !important;
    }
    
    .logo-subtitle {
        font-size: 0.6rem !important;
    }
    
    .action-area {
        gap: 0.5rem !important;
    }
    
    .action-icon,
    .custom-dropdown-btn,
    .auth-btn,
    .mobile-toggle {
        width: 38px !important;
        height: 38px !important;
        font-size: 0.9rem !important;
    }
    
    .user-avatar {
        width: 26px !important;
        height: 26px !important;
        font-size: 0.75rem !important;
    }
    
    .cart-count {
        width: 16px !important;
        height: 16px !important;
        font-size: 0.65rem !important;
        top: -4px !important;
        right: -4px !important;
    }
}

/* Small phone optimizations */
@media (max-width: 576px) {
    body {
        padding-top: 62px !important;
    }
    
    .main-header {
        padding: 0.625rem 0 !important;
    }
    
    .main-header-content {
        padding: 0 0.75rem !important;
        gap: 0.75rem !important;
    }
    
    .logo-image {
        width: 34px !important;
        height: 34px !important;
        margin-right: 8px !important;
    }
    
    .logo-name {
        font-size: 1.15rem !important;
    }
    
    .logo-subtitle {
        font-size: 0.55rem !important;
    }
    
    .action-area {
        gap: 0.425rem !important;
    }
    
    .action-icon,
    .custom-dropdown-btn,
    .auth-btn,
    .mobile-toggle {
        width: 36px !important;
        height: 36px !important;
        font-size: 0.85rem !important;
        border-radius: 10px !important;
    }
    
    .user-avatar {
        width: 24px !important;
        height: 24px !important;
        font-size: 0.7rem !important;
    }
    
    .cart-count {
        width: 14px !important;
        height: 14px !important;
        font-size: 0.6rem !important;
        top: -3px !important;
        right: -3px !important;
    }
    
    .toggle-line {
        width: 14px !important;
    }
    
    .main-nav {
        padding: 1.25rem 0.75rem 1.75rem !important;
    }
    
    .nav-link {
        padding: 0.875rem 1rem !important;
        font-size: 0.9rem !important;
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
    z-index: 100001 !important;
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

/* Desktop styles - hide mobile elements */
@media (min-width: 992px) {
    /* Hide search toggle button on desktop */
    .search-toggle-btn {
        display: none !important;
    }
    
    /* Show desktop search bar */
    .search-bar {
        display: flex !important;
    }
    
    /* Hide mobile toggle on desktop */
    .mobile-toggle {
        display: none !important;
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
                <img src="/assets/images/default-avatar.png" alt="Qickmed" class="logo-image">
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
                <!-- Search Toggle for Mobile -->
                <button class="search-toggle-btn" title="Tìm kiếm" onclick="toggleMobileSearch()">
                    <i class="fas fa-search"></i>
                </button>
                
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
                            <a href="#" onclick="showLogoutModal(); return false;">
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

<?php if (isset($_SESSION['user_id'])): ?>
    <?php include 'includes/logout_modal.php'; ?>
    <script src="/assets/js/logout.js"></script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add header class to body for styling reference
    document.body.classList.add('has-fixed-header');
    
    // Mobile menu and search state
    const mobileToggle = document.getElementById('mobileToggle');
    const navBar = document.getElementById('navBar');
    const searchToggleBtn = document.querySelector('.search-toggle-btn');
    const searchBar = document.querySelector('.search-bar');
    let isMenuOpen = false;
    let isSearchOpen = false;
    
    // Mobile menu toggle
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            isMenuOpen = !isMenuOpen;
            navBar.classList.toggle('show');
            this.classList.toggle('active');
            
            // Close search if open
            if (isSearchOpen && searchBar && searchToggleBtn) {
                isSearchOpen = false;
                searchBar.classList.remove('mobile-active');
                searchToggleBtn.classList.remove('active');
            }
        });
    }
    
    // Mobile search toggle
    if (searchToggleBtn && searchBar) {
        searchToggleBtn.addEventListener('click', function() {
            isSearchOpen = !isSearchOpen;
            searchBar.classList.toggle('mobile-active');
            this.classList.toggle('active');
            
            // Close menu if open
            if (isMenuOpen && navBar && mobileToggle) {
                isMenuOpen = false;
                navBar.classList.remove('show');
                mobileToggle.classList.remove('active');
            }
            
            // Focus search input when opened
            if (isSearchOpen) {
                setTimeout(() => {
                    const searchInput = searchBar.querySelector('.search-input');
                    if (searchInput) {
                        searchInput.focus();
                    }
                }, 100);
            }
        });
    }

    // Search functionality
    const searchForm = document.querySelector('.search-bar form');
    const searchInput = document.querySelector('.search-input');
    
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

    // Close menus when clicking outside
    document.addEventListener('click', function(e) {
        let shouldClose = false;
        
        if (!e.target.closest('.mobile-toggle') && 
            !e.target.closest('.nav-bar') && 
            !e.target.closest('.search-toggle-btn') && 
            !e.target.closest('.search-bar')) {
            shouldClose = true;
        }
        
        if (shouldClose) {
            if (isMenuOpen && navBar && mobileToggle) {
                isMenuOpen = false;
                navBar.classList.remove('show');
                mobileToggle.classList.remove('active');
            }
            
            if (isSearchOpen && searchBar && searchToggleBtn) {
                isSearchOpen = false;
                searchBar.classList.remove('mobile-active');
                searchToggleBtn.classList.remove('active');
            }
        }
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 991) {
            // Reset mobile states on desktop
            if (mobileToggle) mobileToggle.classList.remove('active');
            if (navBar) navBar.classList.remove('show');
            if (searchToggleBtn) searchToggleBtn.classList.remove('active');
            if (searchBar) searchBar.classList.remove('mobile-active');
            isMenuOpen = false;
            isSearchOpen = false;
        }
    });
});

// Mobile search toggle function
function toggleMobileSearch() {
    const searchToggleBtn = document.querySelector('.search-toggle-btn');
    if (searchToggleBtn) {
        searchToggleBtn.click();
    }
}

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


