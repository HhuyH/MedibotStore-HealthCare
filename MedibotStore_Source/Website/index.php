<?php
// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';
require_once 'includes/blog_functions.php';

// Get blog data for homepage
$featured_post = get_featured_post();
$recent_posts = get_recent_posts(4); // Get 4 recent posts for homepage
$categories = get_blog_categories();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MediBot Store - Trang Blog Y Khoa</title>
  <!-- Bootstrap 5 CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Animate.css CDN for animation -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <!-- Font Awesome 6 CDN for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <!-- SwiperJS CSS for slider -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css"/>
  <!-- Custom CSS -->
  <!-- Base CSS -->
  <link rel="stylesheet" href="./assets/css/style.css">
  <link rel="stylesheet" href="./assets/css/team.css">
  <link rel="stylesheet" href="./assets/css/index.css">
  
  <!-- Fallback Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
  
  <style>
    /* Fallback font settings */
    body {
      font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
    }
    
    /* Fix image paths */
    img {
      max-width: 100%;
      height: auto;
    }
    
    /* Ensure icons display */
    .fas, .far, .fab {
      font-family: "Font Awesome 6 Free" !important;
    }
  </style>
  
  <!-- Index Page Header Override -->
  <style>
    /* Force mobile layout on index page */
    @media (max-width: 999.98px) {
        /* Ensure navigation bar is hidden on mobile for index page */
        .nav-bar {
            display: none !important;
        }
        
        /* Force mobile action area layout on index page */
        .action-area > *:not(.search-toggle-btn):not(.cart-icon) {
            display: none !important;
        }
        
        .action-area .auth-btn,
        .action-area .login-btn,
        .action-area .appointment-btn,
        .action-area .custom-dropdown,
        .action-area .user-account {
            display: none !important;
        }
        
        /* Force mobile toggle visibility */
        .mobile-toggle {
            display: flex !important;
        }
        
        /* Ensure mobile side menu works */
        .mobile-side-menu {
            display: block !important;
        }
        
        /* Override any hero wrapper interference */
        .hero-header-wrapper .medical-header {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 999990 !important;
        }
        
        .hero-header-wrapper .main-header-content {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 0 0.8rem !important;
            gap: 0.3rem !important;
            flex-wrap: nowrap !important;
            min-height: 55px !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
    }
    
    /* Medium screens override for index page */
    @media (min-width: 992px) and (max-width: 999.98px) {
        .nav-bar {
            display: none !important;
        }
        
        .action-area > *:not(.search-toggle-btn):not(.cart-icon) {
            display: none !important;
        }
        
        .mobile-toggle {
            display: flex !important;
        }
    }
  }

  /* Button styles */
  .btn-booking:hover,
  .btn-hotline:hover {
    background: rgba(255, 255, 255, 0.95);
    color: #1e40af;
    text-decoration: none;
  }

  .btn-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
    color: #fff;
  }

  .btn-booking .btn-icon,
  .btn-hotline .btn-icon {
    background: linear-gradient(135deg, #3b82f6, #1e40af);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
  }

  /* Override button hover effect */
  .btn::before {
    display: none !important;
  }
  </style>
</head>
<body>
<?php include 'includes/header.php'; ?>
  <!-- Hero Section -->
  <section class="clinic-hero" style="background: url('./assets/images/main-slider-bg-1-1.png') center/cover no-repeat; min-height: 600px;">
    <div class="container position-relative">
      <div class="hero-header-wrapper" style="padding-top:0;margin-top:-50px;">
       
      </div>
      <div class="row align-items-center pt-4 pt-lg-5">
        <div class="col-lg-7 col-12">
          <div class="mb-3 animate__animated animate__fadeInLeft animate__delay-1s" style="color:#1ec0f7;font-weight:700;letter-spacing:2px;font-size:1.08rem;background:#e3f6fd;display:inline-block;padding:6px 18px 6px 14px;border-radius:15px;box-shadow:0 2px 8px rgba(33,150,243,0.10);">DỊCH VỤ CẤP CỨU 24/7</div>
          <h1 class="clinic-hero-title mb-3 animate__animated animate__fadeInDown animate__faster" style="text-align:left;text-shadow:0 2px 12px rgba(33,150,243,0.10);font-size:2.4rem;">
            Chăm Sóc Sức Khỏe <span class="highlight">Tốt Nhất</span>
            <span class="ms-2 align-middle" style="display:inline-block;vertical-align:middle;">
              <span style="position:relative;display:inline-block;">
                <img src="./assets/images/thumbnail-chuyen-gia-scaled.jpg" alt="video" style="width:70px;height:42px;border-radius:18px;object-fit:cover;">
                <span class="animate__animated animate__pulse animate__infinite" style="position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);">
                  <i class="fas fa-play-circle" style="font-size:1.7rem;color:#1ec0f7;opacity:0.85;"></i>
                </span>
              </span>
            </span>
          </h1>
          <div class="clinic-hero-desc mb-3 animate__animated animate__fadeInUp animate__delay-1s" style="text-align:left;max-width:470px;font-size:1.08rem;">Với đội ngũ y bác sĩ giàu kinh nghiệm và trang thiết bị hiện đại, chúng tôi cam kết mang đến dịch vụ chăm sóc sức khỏe chất lượng cao nhất cho bạn và gia đình.</div>
          <a href="services.php" class="btn clinic-hero-btn animate__animated animate__fadeInUp animate__delay-2s" style="display:inline-flex;align-items:center;gap:8px;background:linear-gradient(90deg,#1976d2 0%,#1ec0f7 100%);font-size:1.08rem;padding:12px 32px;box-shadow:0 2px 8px rgba(33,150,243,0.13);">Xem Tất Cả Dịch Vụ <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="col-lg-5 d-none d-lg-block position-relative animate__animated animate__fadeInRight animate__slower" style="min-width:320px;">
          <img src="./assets/images/default-avatar.png" alt="Doctor" style="max-width:320px;max-height:420px;object-fit:contain;">
          <div style="position:absolute;bottom:18px;left:0;background:#fff;border-radius:15px;box-shadow:0 2px 12px rgba(33,150,243,0.10);padding:12px 22px;display:flex;align-items:center;gap:12px;min-width:170px;">
            <span style="font-size:1.4rem;color:#1ec0f7;"><i class="fas fa-star"></i></span>
            <div>
              <div style="font-size:1.08rem;font-weight:700;color:#1976d2;">100%</div>
              <div style="font-size:0.95rem;color:#888;">Bệnh Nhân Hài Lòng</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <main style="padding-top: 90px;">
    <!-- About Us -->
    <section id="about" class="section-bg py-5 position-relative overflow-hidden">
      <div class="about-bg"></div>
      <div class="container position-relative">
        <div class="row align-items-center g-5">
          <div class="col-lg-6">
            <div class="position-relative">
              <div class="rotating-images position-relative" style="height: 400px; border-radius: 1rem; overflow: hidden;">
                <img src="/assets/images/media.png" alt="Bệnh viện" class="rotating-image active" style="position: absolute; width: 100%; height: 100%; object-fit: cover; transition: opacity 0.5s ease;">
                <img src="/assets/images/thmd_2.png" alt="Bệnh viện" class="rotating-image" style="position: absolute; width: 100%; height: 100%; object-fit: cover; opacity: 0; transition: opacity 0.5s ease;">
                <img src="/assets/images/thmdt_1.png" alt="Bệnh viện" class="rotating-image" style="position: absolute; width: 100%; height: 100%; object-fit: cover; opacity: 0; transition: opacity 0.5s ease;">
              </div>
              <div class="position-absolute bottom-0 start-0 translate-middle-y" style="left:24px;bottom:-32px;">
                <!-- <div class="experience-box">
                  <div class="experience-number">25<span>+</span></div>
                  <div class="experience-text">Năm kinh nghiệm<br>trong lĩnh vực y tế</div>
                </div> -->
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="text-center mb-5">
              <div class="section-badge-wrapper">
                <span class="section-badge">Về Chúng Tôi</span>
              </div>
              <h2 class="section-title display-5 fw-bold mb-3">Chăm Sóc Sức Khỏe Tốt Nhất</h2>
              <div class="rotating-text-wrapper">
                <p class="section-desc rotating-text active">Với hơn 20 năm kinh nghiệm trong lĩnh vực y tế, chúng tôi tự hào mang đến những dịch vụ chăm sóc sức khỏe chất lượng cao.</p>
                <p class="section-desc rotating-text" style="display: none;">Đội ngũ y bác sĩ giàu kinh nghiệm cùng trang thiết bị hiện đại, chúng tôi cam kết mang đến dịch vụ y tế tốt nhất cho bạn.</p>
                <p class="section-desc rotating-text" style="display: none;">Sứ mệnh của chúng tôi là mang đến sự chăm sóc tận tâm và chuyên nghiệp, giúp bạn luôn khỏe mạnh và hạnh phúc.</p>
              </div>
            </div>
            <div class="row mb-4 g-3">
              <div class="col-6 col-md-4">
                <div class="stat-box text-center text-md-start animate__animated animate__fadeInLeft">
                  <div class="stat-number">89%</div>
                  <div class="stat-text">Dự án y tế<br>hàng đầu</div>
                </div>
              </div>
              <div class="col-6 col-md-4">
                <div class="stat-box text-center text-md-start animate__animated animate__fadeInRight">
                  <div class="stat-number">100%</div>
                  <div class="stat-text">Bệnh nhân<br>hài lòng</div>
                </div>
              </div>
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
              <a href="#" class="btn btn-primary btn-lg px-4 py-3 rounded-pill animate__animated animate__fadeInUp">
                <span>Tìm Hiểu Thêm</span>
                <i class="fa-solid fa-arrow-right ms-2"></i>
              </a>
              <div class="founder-box animate__animated animate__fadeInUp">
                <img src="./assets/images/logo_icon.png" alt="Người sáng lập" class="rounded-circle">
                <div>
                  <div class="founder-name">MediBot Store</div>
                  <div class="founder-title">Đồng sáng lập</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- Our Services -->
    <section id="services" class="py-5 position-relative overflow-hidden" style="background: linear-gradient(135deg, #1e88e5 0%, #64b5f6 100%);">
      <div class="container position-relative">
        <div class="text-center mb-5">
          <span class="d-inline-block px-4 py-2 rounded-pill mb-3" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); color: #fff;">
            Dịch Vụ
          </span>
          <h2 class="display-4 fw-bold mb-4 text-white">Dịch Vụ Của Chúng Tôi</h2>
          <p class="text-white-50 mx-auto" style="max-width: 600px;">Cung cấp các dịch vụ y tế chất lượng cao với đội ngũ bác sĩ chuyên môn giàu kinh nghiệm</p>
        </div>

        <div class="row g-4">
          <div class="col-lg-4">
            <div class="card h-100 border-0" style="background: rgba(255,255,255,0.95); border-radius: 20px; backdrop-filter: blur(10px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); transition: all 0.3s ease;">
              <div class="card-body p-4">
                <div class="service-icon-wrapper mb-4" style="width: 80px; height: 80px; background: #e3f2fd; border-radius: 20px; display: flex; align-items: center; justify-content: center;">
                  <i class="fa-solid fa-person-pregnant" style="font-size: 32px; color: #1976d2;"></i>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h4 class="fw-bold mb-0" style="color: #1976d2;">Sản Phụ Khoa</h4>
                  <span class="badge rounded-pill px-3" style="background: #e3f2fd; color: #1976d2;">05+ Bác sĩ</span>
                </div>
                <p class="text-muted mb-4" style="font-size: 0.95rem;">Chăm sóc sức khỏe toàn diện cho phụ nữ, đặc biệt là trong thời kỳ mang thai và sinh nở.</p>
                <div class="service-features mb-4">
                  <div class="d-flex align-items-center mb-3">
                    <div class="feature-icon me-3" style="width: 24px; height: 24px; background: #e3f2fd; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                      <i class="fas fa-check" style="font-size: 12px; color: #1976d2;"></i>
                    </div>
                    <span class="text-muted">Khám thai định kỳ</span>
                  </div>
                  <div class="d-flex align-items-center mb-3">
                    <div class="feature-icon me-3" style="width: 24px; height: 24px; background: #e3f2fd; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                      <i class="fas fa-check" style="font-size: 12px; color: #1976d2;"></i>
                    </div>
                    <span class="text-muted">Siêu âm 4D</span>
                  </div>
                  <div class="d-flex align-items-center">
                    <div class="feature-icon me-3" style="width: 24px; height: 24px; background: #e3f2fd; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                      <i class="fas fa-check" style="font-size: 12px; color: #1976d2;"></i>
                    </div>
                    <span class="text-muted">Tư vấn dinh dưỡng</span>
                  </div>
                </div>
                <a href="#" class="btn btn-outline-primary rounded-pill px-4 py-2 w-100">Tìm hiểu thêm</a>
              </div>
            </div>
          </div>

          <div class="col-lg-4">
            <div class="card h-100 border-0" style="background: rgba(255,255,255,0.95); border-radius: 20px; backdrop-filter: blur(10px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); transition: all 0.3s ease;">
              <div class="card-body p-4">
                <div class="service-icon-wrapper mb-4" style="width: 80px; height: 80px; background: #e3f2fd; border-radius: 20px; display: flex; align-items: center; justify-content: center;">
                  <i class="fa-solid fa-bone" style="font-size: 32px; color: #1976d2;"></i>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h4 class="fw-bold mb-0" style="color: #1976d2;">Chỉnh Hình</h4>
                  <span class="badge rounded-pill px-3" style="background: #e3f2fd; color: #1976d2;">15+ Bác sĩ</span>
                </div>
                <p class="text-muted mb-4" style="font-size: 0.95rem;">Điều trị chuyên sâu về xương khớp, cột sống và các bệnh lý về cơ xương khớp.</p>
                <div class="service-features mb-4">
                  <div class="d-flex align-items-center mb-3">
                    <div class="feature-icon me-3" style="width: 24px; height: 24px; background: #e3f2fd; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                      <i class="fas fa-check" style="font-size: 12px; color: #1976d2;"></i>
                    </div>
                    <span class="text-muted">Phẫu thuật nội soi</span>
                  </div>
                  <div class="d-flex align-items-center mb-3">
                    <div class="feature-icon me-3" style="width: 24px; height: 24px; background: #e3f2fd; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                      <i class="fas fa-check" style="font-size: 12px; color: #1976d2;"></i>
                    </div>
                    <span class="text-muted">Vật lý trị liệu</span>
                  </div>
                  <div class="d-flex align-items-center">
                    <div class="feature-icon me-3" style="width: 24px; height: 24px; background: #e3f2fd; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                      <i class="fas fa-check" style="font-size: 12px; color: #1976d2;"></i>
                    </div>
                    <span class="text-muted">Phục hồi chức năng</span>
                  </div>
                </div>
                <a href="#" class="btn btn-outline-primary rounded-pill px-4 py-2 w-100">Tìm hiểu thêm</a>
              </div>
            </div>
          </div>

          <div class="col-lg-4">
            <div class="card h-100 border-0" style="background: rgba(255,255,255,0.95); border-radius: 20px; backdrop-filter: blur(10px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); transition: all 0.3s ease;">
              <div class="card-body p-4">
                <div class="service-icon-wrapper mb-4" style="width: 80px; height: 80px; background: #e3f2fd; border-radius: 20px; display: flex; align-items: center; justify-content: center;">
                  <i class="fa-solid fa-heart-pulse" style="font-size: 32px; color: #1976d2;"></i>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h4 class="fw-bold mb-0" style="color: #1976d2;">Tim Mạch</h4>
                  <span class="badge rounded-pill px-3" style="background: #e3f2fd; color: #1976d2;">20+ Bác sĩ</span>
                </div>
                <p class="text-muted mb-4" style="font-size: 0.95rem;">Chăm sóc toàn diện cho các bệnh lý về tim mạch với trang thiết bị hiện đại.</p>
                <div class="service-features mb-4">
                  <div class="d-flex align-items-center mb-3">
                    <div class="feature-icon me-3" style="width: 24px; height: 24px; background: #e3f2fd; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                      <i class="fas fa-check" style="font-size: 12px; color: #1976d2;"></i>
                    </div>
                    <span class="text-muted">Điện tâm đồ</span>
                  </div>
                  <div class="d-flex align-items-center mb-3">
                    <div class="feature-icon me-3" style="width: 24px; height: 24px; background: #e3f2fd; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                      <i class="fas fa-check" style="font-size: 12px; color: #1976d2;"></i>
                    </div>
                    <span class="text-muted">Siêu âm tim</span>
                  </div>
                  <div class="d-flex align-items-center">
                    <div class="feature-icon me-3" style="width: 24px; height: 24px; background: #e3f2fd; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                      <i class="fas fa-check" style="font-size: 12px; color: #1976d2;"></i>
                    </div>
                    <span class="text-muted">Thông tim can thiệp</span>
                  </div>
                </div>
                <a href="#" class="btn btn-outline-primary rounded-pill px-4 py-2 w-100">Tìm hiểu thêm</a>
              </div>
            </div>
          </div>
        </div>

        <div class="text-center mt-5">
          <a href="services.php" class="btn btn-light btn-lg px-5 py-3 rounded-pill" style="font-weight: 600; font-size: 1.1rem; box-shadow: 0 10px 30px rgba(255,255,255,0.2);">
            Xem Thêm Dịch Vụ <i class="fa-solid fa-arrow-right ms-2"></i>
          </a>
        </div>
      </div>

      <style>
        .service-icon-wrapper {
          transition: all 0.3s ease;
        }
      </style>
    </section>
    <!-- Products -->
    <!-- <section id="products" class="py-5 position-relative overflow-hidden">
      <div class="products-bg"></div>
      <div class="container position-relative">
        <div class="text-center mb-5">
          <div class="section-badge-wrapper">
            <span class="section-badge">Sản Phẩm</span>
          </div>
          <h2 class="section-title display-5 fw-bold mb-3">Sản Phẩm Của Chúng Tôi</h2>
          <p class="section-desc">Cung cấp các sản phẩm chất lượng cao, an toàn và hiệu quả cho sức khỏe của bạn</p>
        </div>
        <div class="row g-4">
          <div class="col-md-4">
            <div class="product-card animate__animated animate__fadeInLeft">
              <div class="product-icon">
                <i class="fas fa-pills"></i>
              </div>
              <div class="product-image">
                <img src="/assets/images/thuc_pham_chuc_nang.jpg" alt="Thực phẩm chức năng">
                <div class="product-overlay">
                  <a href="#" class="btn btn-light btn-sm rounded-pill px-4">Chi tiết</a>
                </div>
              </div>
              <div class="product-content">
                <h4 class="product-title">Thực Phẩm Chức Năng</h4>
                <p class="product-description">Bổ sung dinh dưỡng, tăng cường sức khỏe với các sản phẩm được chứng nhận an toàn và hiệu quả.</p>
                <div class="product-features">
                  <span class="badge">Tăng cường miễn dịch</span>
                  <span class="badge">Bổ sung vitamin</span>
                  <span class="badge">Hỗ trợ tiêu hóa</span>
                </div>
                <a href="#" class="product-link">Tìm Hiểu Thêm <i class="fas fa-arrow-right"></i></a>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="product-card animate__animated animate__fadeInUp">
              <div class="product-icon">
                <i class="fas fa-capsules"></i>
              </div>
              <div class="product-image">
                <img src="/assets/images/thuoc_1.1.jpg" alt="Thuốc">
                <div class="product-overlay">
                  <a href="#" class="btn btn-light btn-sm rounded-pill px-4">Chi tiết</a>
                </div>
              </div>
              <div class="product-content">
                <h4 class="product-title">Thuốc</h4>
                <p class="product-description">Cung cấp các loại thuốc chất lượng cao, được kiểm định nghiêm ngặt và đảm bảo nguồn gốc xuất xứ.</p>
                <div class="product-features">
                  <span class="badge">Thuốc kê đơn</span>
                  <span class="badge">Thuốc không kê đơn</span>
                  <span class="badge">Thuốc đặc trị</span>
                </div>
                <a href="#" class="product-link">Tìm Hiểu Thêm <i class="fas fa-arrow-right"></i></a>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="product-card animate__animated animate__fadeInRight">
              <div class="product-icon">
                <i class="fas fa-stethoscope"></i>
              </div>
              <div class="product-image">
                <img src="/assets/images/thiet_bi_y_te.jpg" alt="Thiết bị y tế">
                <div class="product-overlay">
                  <a href="#" class="btn btn-light btn-sm rounded-pill px-4">Chi tiết</a>
                </div>
              </div>
              <div class="product-content">
                <h4 class="product-title">Thiết Bị Y Tế</h4>
                <p class="product-description">Các thiết bị y tế hiện đại, chính xác giúp theo dõi và chăm sóc sức khỏe tại nhà một cách hiệu quả.</p>
                <div class="product-features">
                  <span class="badge">Máy đo huyết áp</span>
                  <span class="badge">Máy đo đường huyết</span>
                  <span class="badge">Máy xông khí dung</span>
                </div>
                <a href="#" class="product-link">Tìm Hiểu Thêm <i class="fas fa-arrow-right"></i></a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section> -->
    <!-- Health Blog Section -->
    <section id="blog" class="py-5">
      <div class="container">
        <div class="blog-header text-center mb-5">
          <div class="section-badge-wrapper">
            <span class="section-badge">
              <i class="fas fa-stethoscope me-2"></i>Blog Y Tế
            </span>
          </div>
          <h2 class="section-title display-4 fw-bold mb-3">Góc Sức Khỏe</h2>
          <div class="section-line mb-4"></div>
          <p class="section-desc mx-auto">
            Cập nhật những thông tin y tế mới nhất, kiến thức chăm sóc sức khỏe hữu ích từ đội ngũ chuyên gia
          </p>
        </div>

        <div class="blog-categories mb-4">
          <div class="d-flex flex-wrap justify-content-center gap-2">
            <a href="/blog.php" class="blog-category-badge active">Tất cả</a>
            <?php foreach (array_slice($categories, 0, 8) as $category): ?>
            <a href="/blog.php?category=<?php echo $category['category_id']; ?>" class="blog-category-badge">
              <?php echo htmlspecialchars($category['name']); ?>
            </a>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="row g-4">
          <div class="col-lg-8">
            <div class="row g-3">
              <?php if ($featured_post): ?>
              <div class="col-12">
                <div class="blog-banner position-relative rounded-4 overflow-hidden mb-3">
                  <img src="<?php echo htmlspecialchars($featured_post['featured_image']); ?>" 
                       class="w-100 h-100 object-fit-cover" 
                       style="min-height:220px;max-height:260px;object-fit:cover;" 
                       alt="<?php echo htmlspecialchars($featured_post['title']); ?>">
                  <div class="position-absolute bottom-0 start-0 p-4 w-100" style="background: linear-gradient(0deg,rgba(0,0,0,0.55) 60%,rgba(0,0,0,0.01) 100%);">
                    <div class="text-white fw-bold" style="font-size:1.2rem;">
                      <?php echo htmlspecialchars($featured_post['title']); ?>
                    </div>
                    <div class="text-white-50" style="font-size:0.98rem;">
                      <?php echo htmlspecialchars($featured_post['category_name']); ?> • 
                      <?php echo date('d/m/Y', strtotime($featured_post['created_at'])); ?>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-12">
                <div class="featured-article bg-white rounded-4 shadow-sm p-4 mb-2">
                  <div class="mb-2 text-primary fw-bold" style="font-size:0.98rem;">
                    <?php echo htmlspecialchars($featured_post['category_name']); ?>
                  </div>
                  <h3 class="mb-2" style="font-size:1.3rem;line-height:1.3;">
                    <?php echo htmlspecialchars($featured_post['title']); ?>
                  </h3>
                  <p class="mb-2 text-muted" style="font-size:1rem;">
                    <?php echo htmlspecialchars($featured_post['excerpt']); ?>
                  </p>
                  <a href="/blog-post.php?slug=<?php echo $featured_post['slug']; ?>" class="btn btn-link text-primary p-0">
                    Đọc thêm <i class="fas fa-arrow-right ms-2"></i>
                  </a>
                </div>
              </div>
              <?php else: ?>
              <div class="col-12">
                <div class="text-center py-5">
                  <i class="fas fa-newspaper text-muted mb-3" style="font-size: 3rem;"></i>
                  <h4 class="text-muted">Chưa có bài viết nổi bật</h4>
                  <p class="text-muted">Hãy quay lại sau để xem những bài viết mới nhất</p>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <!-- Right Column: List of Articles -->
          <div class="col-lg-4">
            <div class="d-flex flex-column gap-3">
              <?php if (!empty($recent_posts)): ?>
                <?php foreach ($recent_posts as $post): ?>
                <div class="mini-article d-flex align-items-center bg-white rounded-4 shadow-sm p-2">
                  <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" 
                       class="rounded-3 me-3" 
                       style="width:64px;height:64px;object-fit:cover;" 
                       alt="<?php echo htmlspecialchars($post['title']); ?>">
                  <div>
                    <div class="text-primary fw-bold small mb-1">
                      <?php echo htmlspecialchars($post['category_name']); ?>
                    </div>
                    <div class="fw-semibold" style="font-size:1rem;line-height:1.3;">
                      <a href="/blog-post.php?slug=<?php echo $post['slug']; ?>" 
                         class="text-decoration-none text-dark">
                        <?php echo htmlspecialchars($post['title']); ?>
                      </a>
                    </div>
                    <div class="text-muted small mt-1">
                      <i class="far fa-calendar me-1"></i>
                      <?php echo date('d/m/Y', strtotime($post['created_at'])); ?>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="text-center py-4">
                  <i class="fas fa-newspaper text-muted mb-2" style="font-size: 2rem;"></i>
                  <p class="text-muted">Chưa có bài viết mới</p>
                </div>
              <?php endif; ?>
            </div>
            
            <!-- View All Button -->
            <div class="text-center mt-4">
              <a href="/blog.php" class="btn btn-primary rounded-pill px-4 py-2">
                <i class="fas fa-newspaper me-2"></i>
                Xem tất cả bài viết
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- Why Choose Us -->
    <section id="whychoose" class="py-5">
      <div class="container">
        <div class="text-center mb-5">
          <div class="section-badge-wrapper">
            <span class="section-badge">
              <i class="fas fa-check-circle me-2"></i>Lý Do
            </span>
          </div>
          <h2 class="section-title display-4 fw-bold mb-3">Vì Sao Chọn Chúng Tôi</h2>
          <div class="section-line mb-4"></div>
          <p class="section-desc mx-auto">
            Chúng tôi cam kết mang đến dịch vụ y tế chất lượng cao nhất cho bạn và gia đình
          </p>
        </div>

        <div class="row g-4">
          <div class="col-lg-3 col-md-6">
            <div class="why-choose-card">
              <div class="card-icon blue">
                <i class="fas fa-user-md"></i>
              </div>
              <h3 class="card-title">Bác sĩ giỏi</h3>
              <p class="card-desc">Đội ngũ chuyên gia đầu ngành với nhiều năm kinh nghiệm</p>
              <div class="card-stats">
                <div class="stats-number">50+</div>
                <div class="stats-text">Bác sĩ chuyên khoa</div>
              </div>
            </div>
          </div>

          <div class="col-lg-3 col-md-6">
            <div class="why-choose-card">
              <div class="card-icon green">
                <i class="fas fa-stethoscope"></i>
              </div>
              <h3 class="card-title">Thiết bị hiện đại</h3>
              <p class="card-desc">Công nghệ tiên tiến, đảm bảo an toàn tuyệt đối</p>
              <div class="card-stats">
                <div class="stats-number">100%</div>
                <div class="stats-text">Thiết bị nhập khẩu</div>
              </div>
            </div>
          </div>

          <div class="col-lg-3 col-md-6">
            <div class="why-choose-card">
              <div class="card-icon orange">
                <i class="fas fa-calendar-check"></i>
              </div>
              <h3 class="card-title">Đặt lịch dễ dàng</h3>
              <p class="card-desc">Đặt lịch online 24/7, tiết kiệm thời gian chờ đợi</p>
              <div class="card-stats">
                <div class="stats-number">24/7</div>
                <div class="stats-text">Hỗ trợ đặt lịch</div>
              </div>
            </div>
          </div>

          <div class="col-lg-3 col-md-6">
            <div class="why-choose-card">
              <div class="card-icon pink">
                <i class="fas fa-headset"></i>
              </div>
              <h3 class="card-title">Hỗ trợ tận tâm</h3>
              <p class="card-desc">Tư vấn miễn phí, chăm sóc khách hàng nhiệt tình</p>
              <div class="card-stats">
                <div class="stats-number">98%</div>
                <div class="stats-text">Khách hàng hài lòng</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- Book An Appointment -->
    <!-- <section id="book" class="py-5 position-relative overflow-hidden">
      <div class="booking-bg"></div>
      <div class="container position-relative">
        <div class="booking-wrapper text-center">
          <div class="section-badge-wrapper">
            <span class="section-badge">
              <i class="fas fa-calendar-alt me-2"></i>Đặt Lịch
            </span>
          </div>
          
          <h2 class="section-title display-4 fw-bold mb-4">Đặt Lịch Khám Ngay</h2>
          
          <p class="section-desc mx-auto mb-5">
            Nhanh chóng, tiện lợi và hoàn toàn miễn phí. <br>
            Đội ngũ chuyên gia của chúng tôi luôn sẵn sàng hỗ trợ bạn!
          </p>

            <div class="booking-button-group">
              <a href="/book-appointment.php" class="btn btn-outline-primary btn-booking">
                <span class="btn-icon">
                  <i class="fas fa-calendar-plus"></i>
                </span>
                <span class="btn-text">
                  <strong>Đặt Lịch Ngay</strong>
                  <small>Chỉ mất 2 phút của bạn</small>
                </span>
              </a>

              <div class="divider">
                <span>hoặc</span>
              </div>

              <a href="tel:0123456789" class="btn btn-outline-primary btn-hotline">
                <span class="btn-icon">
                  <i class="fas fa-phone-alt"></i>
                </span>
                <span class="btn-text">
                  <strong>Gọi Hotline 24/7</strong>
                  <small>0123 456 789</small>
                </span>
              </a>
            </div>

          <div class="booking-note mt-4">
            <i class="fas fa-clock text-primary me-2"></i>
            Thời gian làm việc: <strong>7:30 - 20:00</strong> (Thứ 2 - Chủ nhật)
          </div>
        </div>
      </div>
    </section> -->
    <!-- We Are Skillful Health Care -->
    <!-- <section id="skillful">
      <div class="container">
        <div class="skillful-grid">
          <div class="skillful-content">
            <div class="skillful-badge">Chuyên Môn Y Tế</div>
            <h2 class="skillful-title">
              Chăm Sóc Sức Khỏe
              <span>Chuyên Nghiệp</span>
            </h2>
            <p class="skillful-desc">
              Với đội ngũ y bác sĩ giàu kinh nghiệm và trang thiết bị hiện đại, chúng tôi cam kết mang đến dịch vụ chăm sóc sức khỏe tốt nhất cho bạn và gia đình.
            </p>
            <div class="skillful-features">
              <div class="feature-item">
                <div class="feature-icon">
                  <i class="fas fa-user-md"></i>
                </div>
                <div class="feature-text">
                  Đội ngũ bác sĩ chuyên môn cao
                </div>
              </div>
              <div class="feature-item">
                <div class="feature-icon">
                  <i class="fas fa-hospital"></i>
                </div>
                <div class="feature-text">
                  Cơ sở vật chất hiện đại
                </div>
              </div>
              <div class="feature-item">
                <div class="feature-icon">
                  <i class="fas fa-clock"></i>
                </div>
                <div class="feature-text">
                  Phục vụ 24/7
                </div>
              </div>
              <div class="feature-item">
                <div class="feature-icon">
                  <i class="fas fa-heart"></i>
                </div>
                <div class="feature-text">
                  Chăm sóc tận tâm
                </div>
              </div>
            </div>
            <a href="#" class="skillful-cta">
              Đặt Lịch Ngay
              <i class="fas fa-arrow-right"></i>
            </a>
          </div>
          <div class="skillful-stats">
            <div class="stat-card">
              <i class="fas fa-award stat-icon"></i>
              <div class="stat-number">25+</div>
              <div class="stat-text">Năm Kinh Nghiệm</div>
            </div>
            <div class="stat-card">
              <i class="fas fa-users stat-icon"></i>
              <div class="stat-number">10K+</div>
              <div class="stat-text">Khách Hàng Hài Lòng</div>
            </div>
            <div class="stat-card">
              <i class="fas fa-user-md stat-icon"></i>
              <div class="stat-number">50+</div>
              <div class="stat-text">Bác Sĩ Chuyên Khoa</div>
            </div>
            <div class="stat-card">
              <i class="fas fa-clinic-medical stat-icon"></i>
              <div class="stat-number">15+</div>
              <div class="stat-text">Chuyên Khoa</div>
            </div>
          </div>
        </div>
      </div>
    </section> -->
    <!-- FAQ Section -->
    <section id="faqs" class="py-5 position-relative overflow-hidden">
      <div class="faq-bg-pattern"></div>
      
      <div class="container section-content position-relative">
        <div class="text-center mb-5">
          <div class="section-badge-wrapper">
            <span class="section-badge">FAQs</span>
          </div>
          <h2 class="section-title display-5 fw-bold mb-3">Câu Hỏi Thường Gặp</h2>
          <p class="section-desc">Những thắc mắc phổ biến của khách hàng về dịch vụ của chúng tôi</p>
        </div>

        <div class="row justify-content-center">
          <div class="col-lg-10">
            <div class="faq-wrapper">
              <!-- FAQ Item 1 -->
              <div class="faq-item active">
                <div class="faq-header" data-bs-toggle="collapse" data-bs-target="#faq1">
                  <div class="faq-icon">
                    <i class="fas fa-calendar-check"></i>
                  </div>
                  <div class="faq-question">
                    Làm sao để đặt lịch khám?
                  </div>
                  <div class="faq-toggle">
                    <i class="fas fa-chevron-down"></i>
                  </div>
                </div>
                <div id="faq1" class="faq-body collapse show">
                  <div class="faq-answer">
                    <p>Bạn có thể đặt lịch khám bằng một trong các cách sau:</p>
                    <div class="faq-features">
                      <div class="faq-feature">
                        <div class="feature-icon">
                          <i class="fas fa-globe"></i>
                        </div>
                        <div class="feature-content">
                          <h4>Đặt lịch online</h4>
                          <p>Truy cập website và đặt lịch trực tuyến 24/7</p>
                        </div>
                      </div>
                      <div class="faq-feature">
                        <div class="feature-icon">
                          <i class="fas fa-phone-alt"></i>
                        </div>
                        <div class="feature-content">
                          <h4>Gọi hotline</h4>
                          <p>Liên hệ <a href="tel:0123456789" class="text-primary">0123 456 789</a> để được hỗ trợ</p>
                        </div>
                      </div>
                      <div class="faq-feature">
                        <div class="feature-icon">
                          <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div class="feature-content">
                          <h4>Ứng dụng di động</h4>
                          <p>Tải app MediBot Store để đặt lịch dễ dàng</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- FAQ Item 2 -->
              <div class="faq-item">
                <div class="faq-header" data-bs-toggle="collapse" data-bs-target="#faq2">
                  <div class="faq-icon">
                    <i class="fas fa-clock"></i>
                  </div>
                  <div class="faq-question">
                    MediBot Store có làm việc cuối tuần không?
                  </div>
                  <div class="faq-toggle">
                    <i class="fas fa-chevron-down"></i>
                  </div>
                </div>
                <div id="faq2" class="faq-body collapse">
                  <div class="faq-answer">
                    <div class="working-hours">
                      <div class="hours-item">
                        <div class="day">Thứ 2 - Thứ 6</div>
                        <div class="time">
                          <i class="far fa-clock me-2"></i>
                          7:30 - 20:00
                        </div>
                      </div>
                      <div class="hours-item">
                        <div class="day">Thứ 7</div>
                        <div class="time">
                          <i class="far fa-clock me-2"></i>
                          8:00 - 17:00
                        </div>
                      </div>
                      <div class="hours-item">
                        <div class="day">Chủ nhật</div>
                        <div class="time">
                          <i class="far fa-clock me-2"></i>
                          8:00 - 12:00
                        </div>
                      </div>
                    </div>
                    <div class="emergency-note">
                      <i class="fas fa-exclamation-circle text-warning me-2"></i>
                      Dịch vụ cấp cứu hoạt động 24/7
                    </div>
                  </div>
                </div>
              </div>

              <!-- FAQ Item 3 -->
              <div class="faq-item">
                <div class="faq-header" data-bs-toggle="collapse" data-bs-target="#faq3">
                  <div class="faq-icon">
                    <i class="fas fa-credit-card"></i>
                  </div>
                  <div class="faq-question">
                    Các phương thức thanh toán được chấp nhận?
                  </div>
                  <div class="faq-toggle">
                    <i class="fas fa-chevron-down"></i>
                  </div>
                </div>
                <div id="faq3" class="faq-body collapse">
                  <div class="faq-answer">
                    <div class="payment-methods">
                      <div class="payment-row">
                        <div class="payment-method">
                          <i class="fas fa-money-bill-wave"></i>
                          <span>Tiền mặt</span>
                        </div>
                        <div class="payment-method">
                          <i class="fas fa-credit-card"></i>
                          <span>Thẻ tín dụng</span>
                        </div>
                        <div class="payment-method">
                          <i class="fas fa-mobile-alt"></i>
                          <span>Ví điện tử</span>
                        </div>
                        <div class="payment-method">
                          <i class="fas fa-university"></i>
                          <span>Chuyển khoản</span>
                        </div>
                      </div>
                    </div>
                    <div class="payment-note">
                      <i class="fas fa-shield-alt text-success me-2"></i>
                      Thanh toán an toàn & bảo mật
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>

        <!-- AI Chat Section -->
        <div class="ai-chat-section text-center mt-5">
          <!-- <div class="chat-bubble">
            <i class="fas fa-robot"></i>
            <span>Bạn cần hỗ trợ thêm?</span>
          </div> -->
          <button class="btn btn-primary btn-lg rounded-pill shadow-lg px-5 py-3 ai-chat-btn" id="open-ai-chat">
            <i class="fas fa-comments me-2"></i>
            Tư vấn với AI
          </button>
        </div>

        <!-- Service Commitments -->
        <div class="service-commitments mt-5">
          <div class="row g-4">
            <div class="col-lg-3 col-md-6">
              <div class="commitment-item">
                <div class="commitment-icon">
                  <i class="fas fa-shield-alt"></i>
                </div>
                <div class="commitment-content">
                  <h4>Thuốc chính hãng</h4>
                  <p>đã đăng và chuyên sâu</p>
                </div>
              </div>
            </div>
            <div class="col-lg-3 col-md-6">
              <div class="commitment-item">
                <div class="commitment-icon">
                  <i class="fas fa-box-open"></i>
                </div>
                <div class="commitment-content">
                  <h4>Đổi trả trong 30 ngày</h4>
                  <p>kể từ ngày mua hàng</p>
                </div>
              </div>
            </div>
            <div class="col-lg-3 col-md-6">
              <div class="commitment-item">
                <div class="commitment-icon">
                  <i class="fas fa-thumbs-up"></i>
                </div>
                <div class="commitment-content">
                  <h4>Cam kết 100%</h4>
                  <p>chất lượng sản phẩm</p>
                </div>
              </div>
            </div>
            <div class="col-lg-3 col-md-6">
              <div class="commitment-item">
                <div class="commitment-icon">
                  <i class="fas fa-truck"></i>
                </div>
                <div class="commitment-content">
                  <h4>Miễn phí vận chuyển</h4>
                  <p>theo chính sách giao hàng</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Floating AI Chat Button -->
      <!-- <div class="ai-chat-float">
        <div class="chat-pulse"></div>
        <button class="ai-chat-float-btn" id="floating-ai-chat">
          <i class="fas fa-robot"></i>
        </button>
      </div> -->
    </section>
  </main>


  
  <!-- Appointment Modal -->
  <?php include 'includes/appointment-modal.php'; ?>
  
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <!-- AOS Animation -->
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  
  <!-- Global Enhancements -->
  <script src="assets/js/global-enhancements.js"></script>
  
  <?php include 'includes/floating_chat.php'; ?>
    <!-- Footer -->
  <?php include 'includes/footer.php'; ?>

  <script src="assets/js/team.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('.rotating-image');
    const texts = document.querySelectorAll('.rotating-text');
    let currentIndex = 0;

    function rotateContent() {
      // Hide all images and texts
      images.forEach(img => img.style.opacity = '0');
      texts.forEach(text => {
        text.style.display = 'none';
        text.classList.remove('active');
      });

      // Show current image and text
      images[currentIndex].style.opacity = '1';
      texts[currentIndex].style.display = 'block';
      texts[currentIndex].classList.add('active');

      // Update index
      currentIndex = (currentIndex + 1) % images.length;
    }

    // Initial state
    images[0].style.opacity = '1';
    texts[0].style.display = 'block';
    texts[0].classList.add('active');

    // Rotate every 15 seconds
    setInterval(rotateContent, 5000);

    // Initialize AOS animation
    AOS.init();
  });
document.getElementById('open-ai-chat').addEventListener('click', function () {
    window.open('/Chat/health-ai-chat.php', '_blank', );
  });

  </script>

  <style>
  /* Fix styles for VPS deployment */
  :root {
    --primary-color: #1976d2;
    --secondary-color: #1ec0f7;
    --text-color: #333;
    --light-bg: #f8f9fa;
    --border-color: #e0e0e0;
  }

  /* Ensure proper font loading */
  @font-face {
    font-family: 'Font Awesome 6 Free';
    font-display: swap;
    src: url('./assets/fonts/fa-solid-900.woff2') format('woff2');
  }

  /* Base styles */
  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }

  body {
    line-height: 1.6;
    color: var(--text-color);
    overflow-x: hidden;
  }

  /* Image optimizations */
  img {
    max-width: 100%;
    height: auto;
    display: block;
  }

  /* Animation fixes */
  .rotating-text.active {
    animation: fadeIn 0.5s ease-in;
    opacity: 1;
    transform: translateY(0);
  }

  @keyframes fadeIn {
    from { 
      opacity: 0; 
      transform: translateY(10px); 
    }
    to { 
      opacity: 1; 
      transform: translateY(0); 
    }
  }

  .rotating-image {
    transform: scale(1.02);
    transition: transform 0.3s ease, opacity 0.5s ease;
    backface-visibility: hidden;
  }

  .rotating-image.active {
    transform: scale(1);
  }

  /* Fix button styles */
  .btn {
    position: relative;
    overflow: hidden;
    z-index: 1;
  }

  .btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.1);
    transform: translateX(-100%);
    transition: transform 0.3s ease;
    z-index: -1;
  }

  .btn:hover::before {
    transform: translateX(0);
  }

  /* Fix card styles */
  .card {
    backface-visibility: hidden;
    transform: translateZ(0);
    -webkit-font-smoothing: subpixel-antialiased;
  }
  </style>
</body>
</html>


