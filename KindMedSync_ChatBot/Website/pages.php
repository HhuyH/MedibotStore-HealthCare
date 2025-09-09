<?php
session_start();
require_once 'includes/db.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> MediBot Store Medical & Health Care</title>
    <meta name="description" content="Khám phá các trang thông tin về Qickmed - đội ngũ bác sĩ, chuyên khoa, đánh giá khách hàng và câu hỏi thường gặp.">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/pages.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-overlay"></div>
            <div class="container">
                <div class="row align-items-center min-vh-100">
                    <div class="col-lg-8 mx-auto text-center">
                        <div class="hero-content">
                            <h1 class="hero-title">Khám phá Qickmed</h1>
                            <p class="hero-subtitle">
                                Tìm hiểu chi tiết về đội ngũ y bác sĩ, các chuyên khoa và những dịch vụ 
                                chăm sóc sức khỏe tốt nhất dành cho bạn
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pages Grid -->
        <section class="pages-section py-5">
            <div class="container">
                <div class="row g-4">
                    <!-- Doctors Page -->
                    <div class="col-lg-6 col-md-6">
                        <div class="page-card">
                            <div class="page-image">
                                <img src="/assets/images/doctors-team.jpg" alt="Đội ngũ bác sĩ" class="img-fluid">
                                <div class="page-overlay">
                                    <div class="page-icon">
                                        <i class="fas fa-user-md"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="page-content">
                                <h3>Đội ngũ bác sĩ</h3>
                                <p>Gặp gỡ đội ngũ y bác sĩ giàu kinh nghiệm với chuyên môn cao trong các lĩnh vực khác nhau.</p>
                                <ul class="page-features">
                                    <li>50+ bác sĩ chuyên khoa</li>
                                    <li>Trình độ chuyên môn cao</li>
                                    <li>Kinh nghiệm quốc tế</li>
                                    <li>Tận tâm với bệnh nhân</li>
                                </ul>
                                <a href="/pages/doctors.php" class="btn btn-primary">Xem chi tiết <i class="fas fa-arrow-right ms-2"></i></a>
                            </div>
                        </div>
                    </div>

                    <!-- Departments Page -->
                    <div class="col-lg-6 col-md-6">
                        <div class="page-card">
                            <div class="page-image">
                                <img src="/assets/images/departments.jpg" alt="Các chuyên khoa" class="img-fluid">
                                <div class="page-overlay">
                                    <div class="page-icon">
                                        <i class="fas fa-hospital"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="page-content">
                                <h3>Chuyên khoa</h3>
                                <p>Khám phá các chuyên khoa với trang thiết bị hiện đại và phương pháp điều trị tiên tiến.</p>
                                <ul class="page-features">
                                    <li>15+ chuyên khoa</li>
                                    <li>Trang thiết bị hiện đại</li>
                                    <li>Quy trình chuẩn quốc tế</li>
                                    <li>Điều trị toàn diện</li>
                                </ul>
                                <a href="/pages/departments.php" class="btn btn-primary">Xem chi tiết <i class="fas fa-arrow-right ms-2"></i></a>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonials Page -->
                    <div class="col-lg-6 col-md-6">
                        <div class="page-card">
                            <div class="page-image">
                                <img src="/assets/images/testimonials.jpg" alt="Đánh giá khách hàng" class="img-fluid">
                                <div class="page-overlay">
                                    <div class="page-icon">
                                        <i class="fas fa-star"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="page-content">
                                <h3>Đánh giá khách hàng</h3>
                                <p>Cảm nhận của bệnh nhân về chất lượng dịch vụ và sự chăm sóc tận tình của chúng tôi.</p>
                                <ul class="page-features">
                                    <li>1000+ đánh giá tích cực</li>
                                    <li>Độ hài lòng 98%</li>
                                    <li>Chia sẻ thật từ bệnh nhân</li>
                                    <li>Minh bạch và trung thực</li>
                                </ul>
                                <a href="/pages/testimonials.php" class="btn btn-primary">Xem chi tiết <i class="fas fa-arrow-right ms-2"></i></a>
                            </div>
                        </div>
                    </div>

                    <!-- FAQ Page -->
                    <div class="col-lg-6 col-md-6">
                        <div class="page-card">
                            <div class="page-image">
                                <img src="/assets/images/faq.jpg" alt="Câu hỏi thường gặp" class="img-fluid">
                                <div class="page-overlay">
                                    <div class="page-icon">
                                        <i class="fas fa-question-circle"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="page-content">
                                <h3>Câu hỏi thường gặp</h3>
                                <p>Tìm câu trả lời cho những thắc mắc phổ biến về dịch vụ y tế và quy trình khám chữa bệnh.</p>
                                <ul class="page-features">
                                    <li>50+ câu hỏi phổ biến</li>
                                    <li>Trả lời chi tiết và rõ ràng</li>
                                    <li>Cập nhật thường xuyên</li>
                                    <li>Hỗ trợ 24/7</li>
                                </ul>
                                <a href="/pages/faq.php" class="btn btn-primary">Xem chi tiết <i class="fas fa-arrow-right ms-2"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Quick Links Section -->
        <section class="quick-links-section py-5 bg-light">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8 mx-auto text-center mb-5">
                        <h2 class="section-title">Liên kết nhanh</h2>
                        <p class="section-description">
                            Truy cập nhanh các thông tin quan trọng và dịch vụ hỗ trợ
                        </p>
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-lg-3 col-md-6">
                        <div class="quick-link-card">
                            <div class="quick-link-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <h5>Đặt lịch hẹn</h5>
                            <p>Đặt lịch khám bệnh online dễ dàng và thuận tiện</p>
                            <a href="/booking.php" class="btn btn-outline-primary btn-sm">Đặt ngay</a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="quick-link-card">
                            <div class="quick-link-icon">
                                <i class="fas fa-file-medical-alt"></i>
                            </div>
                            <h5>Hồ sơ bệnh án</h5>
                            <p>Quản lý và theo dõi hồ sơ sức khỏe cá nhân</p>
                            <a href="/medical-records.php" class="btn btn-outline-primary btn-sm">Xem hồ sơ</a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="quick-link-card">
                            <div class="quick-link-icon">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <h5>Liên hệ</h5>
                            <p>Kết nối với chúng tôi để được tư vấn và hỗ trợ</p>
                            <a href="/pages/contact.php" class="btn btn-outline-primary btn-sm">Liên hệ</a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="quick-link-card">
                            <div class="quick-link-icon">
                                <i class="fas fa-ambulance"></i>
                            </div>
                            <h5>Cấp cứu 24/7</h5>
                            <p>Dịch vụ cấp cứu luôn sẵn sàng mọi lúc mọi nơi</p>
                            <a href="tel:0123456789" class="btn btn-danger btn-sm">Gọi ngay</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section py-5">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8 mx-auto text-center">
                        <h2 class="cta-title">Cần hỗ trợ thêm thông tin?</h2>
                        <p class="cta-description">
                            Đội ngũ chăm sóc khách hàng của chúng tôi luôn sẵn sàng hỗ trợ bạn 24/7
                        </p>
                        <div class="cta-buttons">
                            <a href="tel:0123456789" class="btn btn-primary btn-lg me-3">
                                <i class="fas fa-phone me-2"></i>Hotline: 0123 456 789
                            </a>
                            <a href="/pages/contact.php" class="btn btn-outline-light btn-lg">
                                <i class="fas fa-envelope me-2"></i>Gửi tin nhắn
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS Animation -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <!-- Global Enhancements -->
    <script src="assets/js/global-enhancements.js"></script>
    <!-- Custom JS -->
    <script src="/assets/js/pages.js"></script>
</body>
</html> 