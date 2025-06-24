<?php
session_start();
require_once 'includes/db.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Về chúng tôi - Qickmed Medical & Health Care</title>
    <meta name="description" content="Tìm hiểu về Qickmed - Phòng khám y khoa hiện đại với đội ngũ bác sĩ chuyên nghiệp, mang đến dịch vụ chăm sóc sức khỏe toàn diện.">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/about.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-background">
            <div class="hero-overlay"></div>
                <div class="floating-shapes">
                    <div class="shape shape-1"></div>
                    <div class="shape shape-2"></div>
                    <div class="shape shape-3"></div>
                </div>
            </div>
            <div class="container">
                <div class="row align-items-center min-vh-100">
                    <div class="col-lg-6" data-aos="fade-right" data-aos-duration="1000">
                        <div class="hero-content">
                            <div class="hero-badge">
                                <i class="fas fa-award"></i>
                                <span>15+ năm kinh nghiệm</span>
                            </div>
                            <h1 class="hero-title">
                                Chăm sóc sức khỏe 
                                <span class="text-gradient">toàn diện</span>
                            </h1>
                            <p class="hero-subtitle">
                                Qickmed - Nơi hội tụ đội ngũ bác sĩ chuyên nghiệp, công nghệ y tế tiên tiến 
                                và dịch vụ chăm sóc sức khỏe chất lượng cao, mang đến sự an tâm tuyệt đối 
                                cho bạn và gia đình.
                            </p>
                            <div class="hero-buttons">
                                <a href="#mission" class="btn btn-primary btn-lg">
                                    <i class="fas fa-arrow-down me-2"></i>
                                    Tìm hiểu thêm
                                </a>
                                <a href="contact.php" class="btn btn-outline-light btn-lg">
                                    <i class="fas fa-phone me-2"></i>
                                    Liên hệ ngay
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6" data-aos="fade-left" data-aos-duration="1000" data-aos-delay="200">
                        <div class="hero-image-container">
                            <div class="hero-image">
                                <img src="assets/images/about-hero.jpg" alt="Đội ngũ y bác sĩ Qickmed" class="img-fluid">
                                <div class="image-overlay">
                                    <div class="play-button" data-bs-toggle="modal" data-bs-target="#videoModal">
                                        <i class="fas fa-play"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="hero-stats-floating">
                                <div class="stat-card" data-aos="zoom-in" data-aos-delay="400">
                                    <div class="stat-icon">
                                        <i class="fas fa-user-md"></i>
                                    </div>
                                    <div class="stat-info">
                                        <div class="stat-number">50+</div>
                                        <div class="stat-label">Bác sĩ</div>
                                    </div>
                                </div>
                                <div class="stat-card" data-aos="zoom-in" data-aos-delay="600">
                                    <div class="stat-icon">
                                        <i class="fas fa-heart"></i>
                                    </div>
                                    <div class="stat-info">
                                        <div class="stat-number">10K+</div>
                                        <div class="stat-label">Bệnh nhân</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="stats-section">
            <div class="container">
                <div class="stats-container" data-aos="fade-up">
                    <div class="row g-0">
                        <div class="col-lg-3 col-md-6">
                                <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number" data-count="15">0</div>
                                    <div class="stat-label">Năm kinh nghiệm</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                                <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-user-md"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number" data-count="50">0</div>
                                    <div class="stat-label">Bác sĩ chuyên khoa</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                                <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number" data-count="10000">0</div>
                                    <div class="stat-label">Bệnh nhân tin tưởng</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-award"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number" data-count="25">0</div>
                                    <div class="stat-label">Giải thưởng</div>
                        </div>
                    </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Mission Section -->
        <section id="mission" class="mission-section">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8 mx-auto text-center" data-aos="fade-up">
                        <div class="section-header">
                            <span class="section-badge">Sứ mệnh</span>
                            <h2 class="section-title">
                                Cam kết mang đến 
                                <span class="text-gradient">dịch vụ tốt nhất</span>
                            </h2>
                        <p class="section-description">
                                Với phương châm "Sức khỏe là tài sản quý giá nhất", chúng tôi không ngừng 
                                nỗ lực để mang đến những dịch vụ chăm sóc sức khỏe chất lượng cao nhất.
                        </p>
                        </div>
                    </div>
                </div>
                <div class="row g-4 mt-4">
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                        <div class="mission-card">
                            <div class="mission-icon">
                                <i class="fas fa-heart"></i>
                            </div>
                            <div class="mission-content">
                            <h4>Tận tâm chăm sóc</h4>
                                <p>Đặt sức khỏe và sự hài lòng của bệnh nhân lên hàng đầu trong mọi dịch vụ chăm sóc y tế.</p>
                            </div>
                            <div class="mission-hover">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                        <div class="mission-card">
                            <div class="mission-icon">
                                <i class="fas fa-microscope"></i>
                            </div>
                            <div class="mission-content">
                            <h4>Công nghệ tiên tiến</h4>
                                <p>Ứng dụng công nghệ y tế hiện đại nhất để nâng cao chất lượng chẩn đoán và điều trị.</p>
                            </div>
                            <div class="mission-hover">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                        <div class="mission-card">
                            <div class="mission-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="mission-content">
                            <h4>Đội ngũ chuyên nghiệp</h4>
                                <p>Bác sĩ có trình độ cao, giàu kinh nghiệm và được đào tạo bài bản từ các trường danh tiếng.</p>
                            </div>
                            <div class="mission-hover">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Values Section -->
        <section class="values-section">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6" data-aos="fade-right">
                        <div class="values-content">
                            <div class="section-header">
                                <span class="section-badge">Giá trị cốt lõi</span>
                                <h2 class="section-title">
                                    Những giá trị 
                                    <span class="text-gradient">định hướng</span>
                                </h2>
                            </div>
                            <div class="values-list">
                                <div class="value-item" data-aos="fade-up" data-aos-delay="100">
                                <div class="value-icon">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div class="value-content">
                                    <h4>An toàn tuyệt đối</h4>
                                        <p>Tuân thủ nghiêm ngặt các quy trình an toàn y tế quốc tế, đảm bảo môi trường điều trị an toàn nhất.</p>
                                    </div>
                                </div>
                                <div class="value-item" data-aos="fade-up" data-aos-delay="200">
                                <div class="value-icon">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div class="value-content">
                                    <h4>Chất lượng hàng đầu</h4>
                                        <p>Không ngừng nâng cao chất lượng dịch vụ, đầu tư trang thiết bị y tế hiện đại và đào tạo nhân viên.</p>
                                    </div>
                                </div>
                                <div class="value-item" data-aos="fade-up" data-aos-delay="300">
                                <div class="value-icon">
                                    <i class="fas fa-handshake"></i>
                                </div>
                                <div class="value-content">
                                        <h4>Minh bạch & Tin cậy</h4>
                                        <p>Thông tin rõ ràng về quy trình điều trị, chi phí dịch vụ và cam kết về chất lượng chăm sóc.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6" data-aos="fade-left">
                        <div class="values-visual">
                        <div class="values-image">
                                <img src="assets/images/values.jpg" alt="Giá trị cốt lõi Qickmed" class="img-fluid">
                                <div class="values-overlay">
                                    <div class="values-badge">
                                        <i class="fas fa-certificate"></i>
                                        <span>ISO 9001:2015</span>
                                    </div>
                                </div>
                            </div>
                            <div class="values-decoration">
                                <div class="decoration-item decoration-1">
                                    <i class="fas fa-plus"></i>
                                </div>
                                <div class="decoration-item decoration-2">
                                    <i class="fas fa-heart"></i>
                                </div>
                                <div class="decoration-item decoration-3">
                                    <i class="fas fa-stethoscope"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Team Section -->
        <section class="team-section">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8 mx-auto text-center" data-aos="fade-up">
                        <div class="section-header">
                            <span class="section-badge">Đội ngũ</span>
                            <h2 class="section-title">
                                Gặp gỡ 
                                <span class="text-gradient">đội ngũ lãnh đạo</span>
                            </h2>
                        <p class="section-description">
                                Những chuyên gia y tế hàng đầu với tầm nhìn và kinh nghiệm dày dặn, 
                                dẫn dắt Qickmed trở thành địa chỉ tin cậy trong lĩnh vực chăm sóc sức khỏe.
                        </p>
                        </div>
                    </div>
                </div>
                <div class="row g-4 mt-4">
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                        <div class="team-card">
                            <div class="team-image">
                                <img src="assets/images/doctor-1.jpg" alt="BS. CKI Nguyễn Văn A" class="img-fluid">
                                <div class="team-overlay">
                                    <div class="team-social">
                                        <a href="#" class="social-link">
                                            <i class="fab fa-facebook"></i>
                                        </a>
                                        <a href="#" class="social-link">
                                            <i class="fab fa-linkedin"></i>
                                        </a>
                                        <a href="#" class="social-link">
                                            <i class="fas fa-envelope"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="team-content">
                                <div class="team-badge">Giám đốc y khoa</div>
                                <h4>BS. CKI Nguyễn Văn A</h4>
                                <p class="team-specialty">Chuyên khoa Tim mạch</p>
                                <p class="team-description">20 năm kinh nghiệm trong lĩnh vực tim mạch, từng công tác tại Bệnh viện Chợ Rẫy.</p>
                                <div class="team-achievements">
                                    <span class="achievement-item">
                                        <i class="fas fa-award"></i>
                                        Bác sĩ xuất sắc 2023
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                        <div class="team-card">
                            <div class="team-image">
                                <img src="assets/images/doctor-2.jpg" alt="BS. CKII Trần Thị B" class="img-fluid">
                                <div class="team-overlay">
                                    <div class="team-social">
                                        <a href="#" class="social-link">
                                            <i class="fab fa-facebook"></i>
                                        </a>
                                        <a href="#" class="social-link">
                                            <i class="fab fa-linkedin"></i>
                                        </a>
                                        <a href="#" class="social-link">
                                            <i class="fas fa-envelope"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="team-content">
                                <div class="team-badge">Phó giám đốc</div>
                                <h4>BS. CKII Trần Thị B</h4>
                                <p class="team-specialty">Chuyên khoa Sản phụ khoa</p>
                                <p class="team-description">18 năm kinh nghiệm, chuyên về thai sản và phụ khoa, tốt nghiệp Đại học Y Hà Nội.</p>
                                <div class="team-achievements">
                                    <span class="achievement-item">
                                        <i class="fas fa-medal"></i>
                                        Thầy thuốc nhân dân
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                        <div class="team-card">
                            <div class="team-image">
                                <img src="assets/images/doctor-3.jpg" alt="ThS. BS Lê Văn C" class="img-fluid">
                                <div class="team-overlay">
                                    <div class="team-social">
                                        <a href="#" class="social-link">
                                            <i class="fab fa-facebook"></i>
                                        </a>
                                        <a href="#" class="social-link">
                                            <i class="fab fa-linkedin"></i>
                                        </a>
                                        <a href="#" class="social-link">
                                            <i class="fas fa-envelope"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="team-content">
                                <div class="team-badge">Trưởng khoa Nội</div>
                                <h4>ThS. BS Lê Văn C</h4>
                                <p class="team-specialty">Chuyên khoa Nội tổng hợp</p>
                                <p class="team-description">15 năm kinh nghiệm, Thạc sĩ Y học, chuyên điều trị các bệnh lý nội khoa phức tạp.</p>
                                <div class="team-achievements">
                                    <span class="achievement-item">
                                        <i class="fas fa-graduation-cap"></i>
                                        Thạc sĩ Y học
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- History Timeline -->
        <section class="history-section">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8 mx-auto text-center" data-aos="fade-up">
                        <div class="section-header">
                            <span class="section-badge">Lịch sử</span>
                            <h2 class="section-title">
                                Hành trình 
                                <span class="text-gradient">15 năm phát triển</span>
                            </h2>
                        <p class="section-description">
                                Từ một phòng khám nhỏ đến hệ thống y tế hiện đại, 
                                Qickmed đã không ngừng phát triển để phục vụ cộng đồng tốt hơn.
                        </p>
                        </div>
                    </div>
                </div>
                <div class="timeline-container mt-5">
                    <div class="timeline">
                        <div class="timeline-item" data-aos="fade-up" data-aos-delay="100">
                            <div class="timeline-marker">
                        <div class="timeline-year">2009</div>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-card">
                                    <div class="timeline-icon">
                                        <i class="fas fa-seedling"></i>
                                    </div>
                                    <h4>Khởi đầu hành trình</h4>
                                    <p>Thành lập phòng khám đầu tiên tại quận 1 với 5 bác sĩ chuyên khoa và trang thiết bị cơ bản.</p>
                                    <div class="timeline-stats">
                                        <span class="stat">5 bác sĩ</span>
                                        <span class="stat">1 phòng khám</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="timeline-item" data-aos="fade-up" data-aos-delay="200">
                            <div class="timeline-marker">
                                <div class="timeline-year">2015</div>
                            </div>
                        <div class="timeline-content">
                                <div class="timeline-card">
                                    <div class="timeline-icon">
                                        <i class="fas fa-expand-arrows-alt"></i>
                                    </div>
                                    <h4>Mở rộng quy mô</h4>
                                    <p>Mở thêm 3 chi nhánh tại các quận trung tâm và đầu tư hệ thống trang thiết bị y tế hiện đại.</p>
                                    <div class="timeline-stats">
                                        <span class="stat">20 bác sĩ</span>
                                        <span class="stat">4 chi nhánh</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="timeline-item" data-aos="fade-up" data-aos-delay="300">
                            <div class="timeline-marker">
                                <div class="timeline-year">2020</div>
                    </div>
                        <div class="timeline-content">
                                <div class="timeline-card">
                                    <div class="timeline-icon">
                                        <i class="fas fa-digital-tachograph"></i>
                                    </div>
                                    <h4>Chuyển đổi số</h4>
                                    <p>Triển khai hệ thống quản lý bệnh viện điện tử và dịch vụ telemedicine, tư vấn trực tuyến.</p>
                                    <div class="timeline-stats">
                                        <span class="stat">35 bác sĩ</span>
                                        <span class="stat">Hệ thống số</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="timeline-item" data-aos="fade-up" data-aos-delay="400">
                            <div class="timeline-marker">
                                <div class="timeline-year">2024</div>
                    </div>
                        <div class="timeline-content">
                                <div class="timeline-card">
                                    <div class="timeline-icon">
                                        <i class="fas fa-trophy"></i>
                                    </div>
                                    <h4>Thành tựu hiện tại</h4>
                                    <p>Phục vụ hơn 10,000 bệnh nhân với 50+ bác sĩ chuyên khoa, trở thành địa chỉ tin cậy hàng đầu.</p>
                                    <div class="timeline-stats">
                                        <span class="stat">50+ bác sĩ</span>
                                        <span class="stat">10K+ bệnh nhân</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="container">
                <div class="cta-container" data-aos="fade-up">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <div class="cta-content">
                                <h3>Sẵn sàng chăm sóc sức khỏe của bạn?</h3>
                                <p>Đặt lịch khám ngay hôm nay để được tư vấn từ đội ngũ bác sĩ chuyên nghiệp của chúng tôi.</p>
                            </div>
                        </div>
                        <div class="col-lg-4 text-lg-end">
                            <div class="cta-buttons">
                                <a href="appointment.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-calendar-plus me-2"></i>
                                    Đặt lịch khám
                                </a>
                                <a href="contact.php" class="btn btn-outline-primary btn-lg">
                                    <i class="fas fa-phone me-2"></i>
                                    Liên hệ
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Video Modal -->
    <div class="modal fade" id="videoModal" tabindex="-1" aria-labelledby="videoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="videoModalLabel">Giới thiệu Qickmed</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="ratio ratio-16x9">
                        <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="Qickmed Introduction" allowfullscreen></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS Animation -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/about.js"></script>
</body>
</html> 