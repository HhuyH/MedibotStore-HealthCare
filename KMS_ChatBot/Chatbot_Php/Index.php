<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Qickmed - Trang Blog Y Khoa</title>
  <!-- Bootstrap 5 CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Animate.css CDN for animation -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <!-- Font Awesome 6 CDN for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <!-- SwiperJS CSS for slider -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css"/>
  <!-- Custom CSS -->
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="assets/css/team.css">
  <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>

  <!-- Hero Section -->
  <section class="clinic-hero" style="background: url('/assets/images/main-slider-bg-1-1.png') center/cover no-repeat; min-height: 600px;">
    <div class="container position-relative">
      <div class="hero-header-wrapper" style="padding-top:0;margin-top:-50px;">
        <?php include 'includes/header.php'; ?>
      </div>
      <div class="row align-items-center pt-4 pt-lg-5">
        <div class="col-lg-7 col-12">
          <div class="mb-3 animate__animated animate__fadeInLeft animate__delay-1s" style="color:#1ec0f7;font-weight:700;letter-spacing:2px;font-size:1.08rem;background:#e3f6fd;display:inline-block;padding:6px 18px 6px 14px;border-radius:15px;box-shadow:0 2px 8px rgba(33,150,243,0.10);">DỊCH VỤ CẤP CỨU 24/7</div>
          <h1 class="clinic-hero-title mb-3 animate__animated animate__fadeInDown animate__faster" style="text-align:left;text-shadow:0 2px 12px rgba(33,150,243,0.10);font-size:2.4rem;">
            Chăm Sóc Sức Khỏe <span class="highlight">Tốt Nhất</span>
            <span class="ms-2 align-middle" style="display:inline-block;vertical-align:middle;">
              <span style="position:relative;display:inline-block;">
                <img src="https://images.unsplash.com/photo-1515378791036-0648a3ef77b2?auto=format&fit=facearea&w=70&h=42" alt="video" style="width:70px;height:42px;border-radius:18px;object-fit:cover;">
                <span class="animate__animated animate__pulse animate__infinite" style="position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);">
                  <i class="fas fa-play-circle" style="font-size:1.7rem;color:#1ec0f7;opacity:0.85;"></i>
                </span>
              </span>
            </span>
          </h1>
          <div class="clinic-hero-desc mb-3 animate__animated animate__fadeInUp animate__delay-1s" style="text-align:left;max-width:470px;font-size:1.08rem;">Với đội ngũ y bác sĩ giàu kinh nghiệm và trang thiết bị hiện đại, chúng tôi cam kết mang đến dịch vụ chăm sóc sức khỏe chất lượng cao nhất cho bạn và gia đình.</div>
          <a href="#services" class="btn clinic-hero-btn animate__animated animate__fadeInUp animate__delay-2s" style="display:inline-flex;align-items:center;gap:8px;background:linear-gradient(90deg,#1976d2 0%,#1ec0f7 100%);font-size:1.08rem;padding:12px 32px;box-shadow:0 2px 8px rgba(33,150,243,0.13);">Xem Tất Cả Dịch Vụ <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="col-lg-5 d-none d-lg-block position-relative animate__animated animate__fadeInRight animate__slower" style="min-width:320px;">
          <img src="/assets/images/default-avatar.png" alt="Doctor" style="max-width:320px;max-height:420px;object-fit:contain;">
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
              <img src="/assets/images/about-hospital.jpg" alt="Bệnh viện" class="img-fluid rounded-4 shadow-lg" style="object-fit:cover;">
              <div class="position-absolute bottom-0 start-0 translate-middle-y" style="left:24px;bottom:-32px;">
                <div class="experience-box">
                  <div class="experience-number">25<span>+</span></div>
                  <div class="experience-text">Năm kinh nghiệm<br>trong lĩnh vực y tế</div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="text-center mb-5">
              <div class="section-badge-wrapper">
                <span class="section-badge">Về Chúng Tôi</span>
              </div>
              <h2 class="section-title display-5 fw-bold mb-3">Chăm Sóc Sức Khỏe Tốt Nhất</h2>
              <p class="section-desc">Với hơn 20 năm kinh nghiệm trong lĩnh vực y tế, chúng tôi tự hào mang đến những dịch vụ chăm sóc sức khỏe chất lượng cao.</p>
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
                <img src="/assets/images/founder.jpg" alt="Người sáng lập" class="rounded-circle">
                <div>
                  <div class="founder-name">Nguyễn Văn A</div>
                  <div class="founder-title">Đồng sáng lập</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- Our Services -->
    <section id="services" class="py-5 position-relative overflow-hidden">
      <div class="services-bg"></div>
      <div class="container position-relative">
        <div class="text-center mb-5">
          <div class="section-badge-wrapper">
            <span class="section-badge">Dịch Vụ</span>
          </div>
          <h2 class="section-title display-5 fw-bold mb-3">Dịch Vụ Của Chúng Tôi</h2>
          <p class="section-desc">Cung cấp các dịch vụ y tế chất lượng cao với đội ngũ bác sĩ chuyên môn giàu kinh nghiệm</p>
        </div>
        <div class="row g-4">
          <div class="col-md-4">
            <div class="service-card animate__animated animate__fadeInLeft">
              <div class="service-icon">
                <i class="fa-solid fa-person-pregnant"></i>
              </div>
              <div class="service-content">
                <div class="service-header">
                  <h5>Sản Phụ Khoa</h5>
                  <span class="badge">05+ Bác sĩ</span>
                </div>
                <p>Chăm sóc sức khỏe toàn diện cho phụ nữ, đặc biệt là trong thời kỳ mang thai và sinh nở.</p>
                <ul class="service-features">
                  <li><i class="fas fa-check"></i> Khám thai định kỳ</li>
                  <li><i class="fas fa-check"></i> Siêu âm 4D</li>
                  <li><i class="fas fa-check"></i> Tư vấn dinh dưỡng</li>
                </ul>
                <a href="#" class="service-link">Tìm hiểu thêm <i class="fas fa-arrow-right"></i></a>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="service-card animate__animated animate__fadeInUp">
              <div class="service-icon">
                <i class="fa-solid fa-bone"></i>
              </div>
              <div class="service-content">
                <div class="service-header">
                  <h5>Chỉnh Hình</h5>
                  <span class="badge">15+ Bác sĩ</span>
                </div>
                <p>Điều trị chuyên sâu về xương khớp, cột sống và các bệnh lý về cơ xương khớp.</p>
                <ul class="service-features">
                  <li><i class="fas fa-check"></i> Phẫu thuật nội soi</li>
                  <li><i class="fas fa-check"></i> Vật lý trị liệu</li>
                  <li><i class="fas fa-check"></i> Phục hồi chức năng</li>
                </ul>
                <a href="#" class="service-link">Tìm hiểu thêm <i class="fas fa-arrow-right"></i></a>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="service-card animate__animated animate__fadeInRight">
              <div class="service-icon">
                <i class="fa-solid fa-heart-pulse"></i>
              </div>
              <div class="service-content">
                <div class="service-header">
                  <h5>Tim Mạch</h5>
                  <span class="badge">20+ Bác sĩ</span>
                </div>
                <p>Chăm sóc toàn diện cho các bệnh lý về tim mạch với trang thiết bị hiện đại.</p>
                <ul class="service-features">
                  <li><i class="fas fa-check"></i> Điện tâm đồ</li>
                  <li><i class="fas fa-check"></i> Siêu âm tim</li>
                  <li><i class="fas fa-check"></i> Thông tim can thiệp</li>
                </ul>
                <a href="#" class="service-link">Tìm hiểu thêm <i class="fas fa-arrow-right"></i></a>
              </div>
            </div>
          </div>
        </div>
        <div class="text-center mt-5">
          <a href="#" class="btn btn-light btn-lg px-5 py-3 rounded-pill animate__animated animate__pulse animate__infinite" style="font-weight: 600; font-size: 1.1rem; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
            Xem Thêm Dịch Vụ <i class="fa-solid fa-arrow-right ms-2"></i>
          </a>
        </div>
      </div>
    </section>
    <!-- Products -->
    <section id="products" class="py-5 position-relative overflow-hidden">
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
    </section>
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
            <a href="#" class="blog-category-badge active">Tất cả</a>
            <a href="#" class="blog-category-badge">Dinh dưỡng</a>
            <a href="#" class="blog-category-badge">Phòng chữa bệnh</a>
            <a href="#" class="blog-category-badge">Người cao tuổi</a>
            <a href="#" class="blog-category-badge">Khỏe đẹp</a>
            <a href="#" class="blog-category-badge">Mẹ và bé</a>
            <a href="#" class="blog-category-badge">Giới tính</a>
            <a href="#" class="blog-category-badge">Tin tức khuyến mại</a>
            <a href="#" class="blog-category-badge">Tin tức sức khỏe</a>
          </div>
        </div>

        <div class="row g-4">
          <div class="col-lg-8">
            <div class="row g-3">
              <div class="col-12">
                <div class="blog-banner position-relative rounded-4 overflow-hidden mb-3">
                  <img src="/assets/images/anh_ngau_nhien.jpg" class="w-100 h-100 object-fit-cover" style="min-height:220px;max-height:260px;object-fit:cover;" alt="Banner Blog">
                  <div class="position-absolute bottom-0 start-0 p-4 w-100" style="background: linear-gradient(0deg,rgba(0,0,0,0.55) 60%,rgba(0,0,0,0.01) 100%);">
                    <div class="text-white fw-bold" style="font-size:1.2rem;">Dự phòng viêm phổi do RSV cho trẻ sinh non - tim bẩm sinh</div>
                    <div class="text-white-50" style="font-size:0.98rem;">Thuốc hàng đầu thế giới đã có tại Long Châu</div>
                  </div>
                </div>
              </div>
              <div class="col-12">
                <div class="featured-article bg-white rounded-4 shadow-sm p-4 mb-2">
                  <div class="mb-2 text-primary fw-bold" style="font-size:0.98rem;">Truyền thông</div>
                  <h3 class="mb-2" style="font-size:1.3rem;line-height:1.3;">Giải pháp bảo vệ trẻ sinh non, tim bẩm sinh trước nguy cơ viêm phổi do RSV chính thức có mặt ở Việt Nam</h3>
                  <p class="mb-2 text-muted" style="font-size:1rem;">Tìm hiểu về các giải pháp phòng ngừa viêm phổi do RSV cho trẻ sinh non, tim bẩm sinh và vai trò của thuốc mới trên thị trường Việt Nam...</p>
                  <a href="#" class="btn btn-link text-primary p-0">Đọc thêm <i class="fas fa-arrow-right ms-2"></i></a>
                </div>
              </div>
            </div>
          </div>
          <!-- Right Column: List of Articles -->
          <div class="col-lg-4">
            <div class="d-flex flex-column gap-3">
              <div class="mini-article d-flex align-items-center bg-white rounded-4 shadow-sm p-2">
                <img src="/assets/images/anh_ngau_nhien.jpg" class="rounded-3 me-3" style="width:64px;height:64px;object-fit:cover;" alt="Bài viết 1">
                <div>
                  <div class="text-primary fw-bold small mb-1">Kiến thức y khoa</div>
                  <div class="fw-semibold" style="font-size:1rem;line-height:1.3;">Bà bầu bị tay chân miệng có sao không? Nguy hiểm tiềm ẩn và những điều cần biết</div>
                </div>
              </div>
              <div class="mini-article d-flex align-items-center bg-white rounded-4 shadow-sm p-2">
                <img src="/assets/images/anh_ngau_nhien.jpg" class="rounded-3 me-3" style="width:64px;height:64px;object-fit:cover;" alt="Bài viết 2">
                <div>
                  <div class="text-success fw-bold small mb-1">Truyền thông</div>
                  <div class="fw-semibold" style="font-size:1rem;line-height:1.3;">Cục Quản lý dược chi cách tra cứu thông tin thuốc, 'kỹ năng' tránh mua thuốc giả</div>
                </div>
              </div>
              <div class="mini-article d-flex align-items-center bg-white rounded-4 shadow-sm p-2">
                <img src="/assets/images/anh_ngau_nhien.jpg" class="rounded-3 me-3" style="width:64px;height:64px;object-fit:cover;" alt="Bài viết 3">
                <div>
                  <div class="text-primary fw-bold small mb-1">Kiến thức y khoa</div>
                  <div class="fw-semibold" style="font-size:1rem;line-height:1.3;">Sơ cứu đột quỵ tại nhà đúng cách giúp bạn thoát khỏi nguy hiểm!</div>
                </div>
              </div>
              <div class="mini-article d-flex align-items-center bg-white rounded-4 shadow-sm p-2">
                <img src="/assets/images/anh_ngau_nhien.jpg" class="rounded-3 me-3" style="width:64px;height:64px;object-fit:cover;" alt="Bài viết 4">
                <div>
                  <div class="text-success fw-bold small mb-1">Truyền thông</div>
                  <div class="fw-semibold" style="font-size:1rem;line-height:1.3;">FPT Long Châu lên tiếng về thông tin sai lệch liên quan sản phẩm Happy Mom</div>
                </div>
              </div>
              <div class="mini-article d-flex align-items-center bg-white rounded-4 shadow-sm p-2">
                <img src="/assets/images/anh_ngau_nhien.jpg" class="rounded-3 me-3" style="width:64px;height:64px;object-fit:cover;" alt="Bài viết 5">
                <div>
                  <div class="text-primary fw-bold small mb-1">Kiến thức y khoa</div>
                  <div class="fw-semibold" style="font-size:1rem;line-height:1.3;">Chất béo chuyển hóa là gì? Tác hại của chất béo chuyển hóa như thế nào?</div>
                </div>
              </div>
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
    <section id="book" class="py-5 position-relative overflow-hidden">
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
            <a href="/booking.php" class="btn btn-primary btn-booking">
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
    </section>
    <!-- We Are Skillful Health Care -->
    <section id="skillful">
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
    </section>
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
                          <p>Tải app Qickmed để đặt lịch dễ dàng</p>
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
                    Qickmed có làm việc cuối tuần không?
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
          <div class="chat-bubble">
            <i class="fas fa-robot"></i>
            <span>Bạn cần hỗ trợ thêm?</span>
          </div>
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
      <div class="ai-chat-float">
        <div class="chat-pulse"></div>
        <button class="ai-chat-float-btn" id="floating-ai-chat">
          <i class="fas fa-robot"></i>
        </button>
      </div>
    </section>
  </main>

  <!-- Footer -->
  <?php include 'includes/footer.php'; ?>
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
  
  <?php include 'includes/floating_chat.php'; ?>
  <script src="assets/js/team.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    const slides = document.querySelectorAll('.skillful-slide');
    const dots = document.querySelectorAll('.skillful-dot');
    let currentSlide = 0;

    function showSlide(index) {
      slides.forEach(slide => slide.classList.remove('active'));
      dots.forEach(dot => dot.classList.remove('active'));
      slides[index].classList.add('active');
      dots[index].classList.add('active');
    }

    function nextSlide() {
      currentSlide = (currentSlide + 1) % slides.length;
      showSlide(currentSlide);
    }

    // Auto change slide every 10 seconds
    setInterval(nextSlide, 10000);

    // Click on dots to change slide
    dots.forEach((dot, index) => {
      dot.addEventListener('click', () => {
        currentSlide = index;
        showSlide(currentSlide);
      });
    });
  });
  </script>
</body>
</html>

