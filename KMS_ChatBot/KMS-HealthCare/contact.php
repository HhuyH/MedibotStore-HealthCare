<?php
session_start();
require_once 'includes/db.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên hệ - Qickmed Medical & Health Care</title>
    <meta name="description" content="Liên hệ với Qickmed để được tư vấn và hỗ trợ. Chúng tôi luôn sẵn sàng lắng nghe và giải đáp mọi thắc mắc của bạn.">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/contact.css">
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
                            <h1 class="hero-title">Liên hệ với chúng tôi</h1>
                            <p class="hero-subtitle">
                                Chúng tôi luôn sẵn sàng lắng nghe và hỗ trợ bạn. 
                                Hãy liên hệ để được tư vấn tốt nhất.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section class="contact-section py-5">
            <div class="container">
                <div class="row g-5">
                    <!-- Contact Form -->
                    <div class="col-lg-7">
                        <div class="contact-form-wrapper">
                            <h2>Gửi tin nhắn cho chúng tôi</h2>
                            <p>Điền thông tin vào form bên dưới và chúng tôi sẽ liên hệ với bạn sớm nhất có thể.</p>
                            
                            <form class="contact-form" id="contactForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="firstName" class="form-label">Họ *</label>
                                        <input type="text" class="form-control" id="firstName" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="lastName" class="form-label">Tên *</label>
                                        <input type="text" class="form-control" id="lastName" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email *</label>
                                        <input type="email" class="form-control" id="email" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Số điện thoại</label>
                                        <input type="tel" class="form-control" id="phone">
                                    </div>
                                    <div class="col-12">
                                        <label for="subject" class="form-label">Chủ đề *</label>
                                        <select class="form-select" id="subject" required>
                                            <option value="">Chọn chủ đề</option>
                                            <option value="appointment">Đặt lịch hẹn</option>
                                            <option value="consultation">Tư vấn y tế</option>
                                            <option value="complaint">Khiếu nại</option>
                                            <option value="feedback">Góp ý</option>
                                            <option value="other">Khác</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="message" class="form-label">Tin nhắn *</label>
                                        <textarea class="form-control" id="message" rows="5" required></textarea>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="agreement" required>
                                            <label class="form-check-label" for="agreement">
                                                Tôi đồng ý với <a href="#">điều khoản sử dụng</a> và <a href="#">chính sách bảo mật</a>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-paper-plane me-2"></i>Gửi tin nhắn
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Contact Info -->
                    <div class="col-lg-5">
                        <div class="contact-info">
                            <h2>Thông tin liên hệ</h2>
                            <p>Liên hệ trực tiếp với chúng tôi qua các kênh sau:</p>

                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="contact-details">
                                    <h4>Địa chỉ</h4>
                                    <p>123 Đường Sức Khỏe, Quận 1, TP.HCM, Việt Nam</p>
                                </div>
                            </div>

                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div class="contact-details">
                                    <h4>Điện thoại</h4>
                                    <p>
                                        <a href="tel:0123456789">0123 456 789</a><br>
                                        <a href="tel:0987654321">0987 654 321</a>
                                    </p>
                                </div>
                            </div>

                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="contact-details">
                                    <h4>Email</h4>
                                    <p>
                                        <a href="mailto:info@qickmed.vn">info@qickmed.vn</a><br>
                                        <a href="mailto:support@qickmed.vn">support@qickmed.vn</a>
                                    </p>
                                </div>
                            </div>

                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="contact-details">
                                    <h4>Giờ làm việc</h4>
                                    <p>
                                        Thứ 2 - Thứ 6: 7:00 - 21:00<br>
                                        Thứ 7 - Chủ nhật: 8:00 - 20:00
                                    </p>
                                </div>
                            </div>

                            <!-- Social Media -->
                            <div class="social-media mt-4">
                                <h4>Kết nối với chúng tôi</h4>
                                <div class="social-links">
                                    <a href="#" class="social-link facebook">
                                        <i class="fab fa-facebook-f"></i>
                                    </a>
                                    <a href="#" class="social-link instagram">
                                        <i class="fab fa-instagram"></i>
                                    </a>
                                    <a href="#" class="social-link youtube">
                                        <i class="fab fa-youtube"></i>
                                    </a>
                                    <a href="#" class="social-link zalo">
                                        <i class="fas fa-comments"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Map Section -->
        <section class="map-section">
            <div class="container-fluid p-0">
                <div class="map-wrapper">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.325296406604!2d106.70207131476237!3d10.779169892308897!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x317525c0c0b9f0b5%3A0x8e1b2e6e8f8f8f8f!2sHCM%20City%2C%20Vietnam!5e0!3m2!1sen!2s!4v1608888888888!5m2!1sen!2s"
                        width="100%" 
                        height="400" 
                        style="border:0;" 
                        allowfullscreen="" 
                        loading="lazy">
                    </iframe>
                    <div class="map-overlay">
                        <div class="map-info">
                            <h3>Qickmed Medical Center</h3>
                            <p>123 Đường Sức Khỏe, Quận 1, TP.HCM</p>
                            <a href="#" class="btn btn-primary">Chỉ đường</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="faq-section py-5 bg-light">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8 mx-auto text-center mb-5">
                        <h2 class="section-title">Câu hỏi thường gặp</h2>
                        <p class="section-description">
                            Tìm câu trả lời nhanh cho những thắc mắc phổ biến
                        </p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-8 mx-auto">
                        <div class="accordion" id="faqAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faq1">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                                        Làm thế nào để đặt lịch hẹn?
                                    </button>
                                </h2>
                                <div id="collapse1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Bạn có thể đặt lịch hẹn bằng cách gọi điện thoại, gửi email, hoặc sử dụng form liên hệ trên website. Chúng tôi sẽ xác nhận lịch hẹn trong vòng 24h.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faq2">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                                        Có dịch vụ cấp cứu 24/7 không?
                                    </button>
                                </h2>
                                <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Có, chúng tôi cung cấp dịch vụ cấp cứu 24/7. Trong trường hợp khẩn cấp, vui lòng gọi hotline 0123 456 789.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faq3">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">
                                        Chi phí khám bệnh như thế nào?
                                    </button>
                                </h2>
                                <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Chi phí khám bệnh phụ thuộc vào loại dịch vụ. Chúng tôi có bảng giá minh bạch và hỗ trợ thanh toán bảo hiểm y tế.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
      <!-- Appointment Modal -->
  <?php include 'includes/appointment-modal.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS Animation -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <!-- Global Enhancements -->
    <script src="assets/js/global-enhancements.js"></script>
    <!-- Custom JS -->
    <script src="/assets/js/contact.js"></script>
</body>
</html> 