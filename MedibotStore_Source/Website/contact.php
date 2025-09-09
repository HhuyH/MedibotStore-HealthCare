<?php
session_start();
require_once 'includes/db.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên hệ - MediBot Store Medical & Health Care</title>
    <meta name="description" content="Liên hệ với MediBot Store để được tư vấn và hỗ trợ. Chúng tôi luôn sẵn sàng lắng nghe và giải đáp mọi thắc mắc của bạn.">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/contact.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-overlay"></div>
            <div class="container">
                <div class="hero-content">
                    <h1 class="hero-title">Liên hệ với chúng tôi</h1>
                    <p class="hero-subtitle">
                        Chúng tôi luôn sẵn sàng lắng nghe và hỗ trợ bạn 24/7
                    </p>
                </div>
            </div>
        </section>

        <!-- Quick Contact Section -->
        <section class="quick-contact">
            <div class="container">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="quick-contact-item">
                            <div class="icon">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <h3>Hotline</h3>
                            <p>Gọi ngay: <a href="tel:0123456789">0123 456 789</a></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="quick-contact-item">
                            <div class="icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <h3>Email</h3>
                            <p>Gửi mail: <a href="mailto:support@medibotstore.vn">support@medibotstore.vn</a></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="quick-contact-item">
                            <div class="icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <h3>Địa chỉ</h3>
                            <p>123 Đường Sức Khỏe, Quận 1, TP.HCM</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Contact Section -->
        <section class="contact-section">
            <div class="container">
                <div class="row g-4">
                    <!-- Contact Form -->
                    <div class="col-lg-7">
                        <div class="contact-form-wrapper">
                            <div class="section-header">
                                <h2>Gửi tin nhắn cho chúng tôi</h2>
                                <p>Điền thông tin vào form bên dưới, chúng tôi sẽ phản hồi trong vòng 24h</p>
                            </div>
                            
                            <form class="contact-form" id="contactForm">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="firstName" placeholder="Họ" required>
                                            <label for="firstName">Họ *</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="lastName" placeholder="Tên" required>
                                            <label for="lastName">Tên *</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="email" class="form-control" id="email" placeholder="Email" required>
                                            <label for="email">Email *</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="tel" class="form-control" id="phone" placeholder="Số điện thoại">
                                            <label for="phone">Số điện thoại</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <select class="form-select" id="subject" required>
                                                <option value="">Chọn chủ đề</option>
                                                <option value="appointment">Đặt lịch khám</option>
                                                <option value="consultation">Tư vấn y tế</option>
                                                <option value="feedback">Góp ý dịch vụ</option>
                                                <option value="other">Vấn đề khác</option>
                                            </select>
                                            <label for="subject">Chủ đề *</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <textarea class="form-control" id="message" placeholder="Tin nhắn" style="height: 150px" required></textarea>
                                            <label for="message">Tin nhắn *</label>
                                        </div>
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
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-paper-plane me-2"></i>Gửi tin nhắn
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Contact Info -->
                    <div class="col-lg-5">
                        <div class="contact-info-wrapper">
                            <div class="section-header">
                                <h2>Thông tin liên hệ</h2>
                                <p>Liên hệ trực tiếp với chúng tôi qua các kênh sau</p>
                            </div>

                            <div class="contact-items-wrapper">
                                <div class="contact-item-wrapper">
                                    <div class="contact-icon-wrapper ">
                                        <i class="fas fa-map-marked-alt"></i>
                                    </div>
                                    <div class="contact-details-wrapper ">
                                        <h4>Trụ sở chính</h4>
                                        <p>123 Đường Sức Khỏe, Quận 1, TP.HCM, Việt Nam</p>
                                    </div>
                                </div>

                                <div class="contact-item-wrapper ">
                                    <div class="contact-icon-wrapper ">
                                        <i class="fas fa-phone-volume"></i>
                                    </div>
                                    <div class="contact-details-wrapper ">
                                        <h4>Đường dây nóng</h4>
                                        <p>
                                            <a href="tel:0123456789">0123 456 789</a> (Tư vấn)<br>
                                            <a href="tel:0987654321">0987 654 321</a> (Cấp cứu)
                                        </p>
                                    </div>
                                </div>

                                <div class="contact-item-wrapper ">
                                    <div class="contact-icon-wrapper ">
                                        <i class="fas fa-envelope-open-text"></i>
                                    </div>
                                    <div class="contact-details-wrapper ">
                                        <h4>Email</h4>
                                        <p>
                                            <a href="mailto:info@medibotstore.vn">info@medibotstore.vn</a> (Thông tin)<br>
                                            <a href="mailto:support@medibotstore.vn">support@medibotstore.vn</a> (Hỗ trợ)
                                        </p>
                                    </div>
                                </div>

                                <div class="contact-item-wrapper ">
                                    <div class="contact-icon-wrapper ">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="contact-details-wrapper ">
                                        <h4>Giờ làm việc</h4>
                                        <p>
                                            <strong>Khám thường:</strong> 7:00 - 20:00<br>
                                            <strong>Cấp cứu:</strong> 24/7
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="social-media">
                                <h4>Kết nối với chúng tôi</h4>
                                <div class="social-links">
                                    <a href="#" class="social-link facebook" title="Facebook">
                                        <i class="fab fa-facebook-f"></i>
                                    </a>
                                    <a href="#" class="social-link instagram" title="Instagram">
                                        <i class="fab fa-instagram"></i>
                                    </a>
                                    <a href="#" class="social-link youtube" title="Youtube">
                                        <i class="fab fa-youtube"></i>
                                    </a>
                                    <a href="#" class="social-link zalo" title="Zalo">
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
            <div class="container">
                <div class="map-wrapper">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.325296406604!2d106.70207131476237!3d10.779169892308897!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x317525c0c0b9f0b5%3A0x8e1b2e6e8f8f8f8f!2sHCM%20City%2C%20Vietnam!5e0!3m2!1sen!2s!4v1608888888888!5m2!1sen!2s"
                        allowfullscreen="" 
                        loading="lazy">
                    </iframe>
                    <div class="map-overlay">
                        <div class="map-info">
                            <h3>MediBot Store Medical Center</h3>
                            <p><i class="fas fa-map-marker-alt me-2"></i>123 Đường Sức Khỏe, Quận 1, TP.HCM</p>
                            <a href="#" class="btn btn-primary">
                                <i class="fas fa-directions me-2"></i>Chỉ đường
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="faq-section">
            <div class="container">
                <div class="section-header text-center">
                    <h2>Câu hỏi thường gặp</h2>
                    <p>Những thắc mắc phổ biến về dịch vụ của chúng tôi</p>
                </div>

                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="accordion" id="faqAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                        Làm thế nào để đặt lịch khám?
                                    </button>
                                </h2>
                                <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Bạn có thể đặt lịch khám qua nhiều cách:
                                        <ul>
                                            <li>Gọi hotline: 0123 456 789</li>
                                            <li>Đặt lịch trực tuyến trên website</li>
                                            <li>Gửi yêu cầu qua email</li>
                                            <li>Đến trực tiếp phòng khám</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                        Có dịch vụ cấp cứu 24/7 không?
                                    </button>
                                </h2>
                                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Có, chúng tôi có đội ngũ cấp cứu hoạt động 24/7. Trong trường hợp khẩn cấp, vui lòng gọi số hotline cấp cứu: 0987 654 321
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                        Chi phí khám bệnh như thế nào?
                                    </button>
                                </h2>
                                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Chi phí khám bệnh được niêm yết công khai và phụ thuộc vào loại dịch vụ. Chúng tôi chấp nhận thanh toán qua:
                                        <ul>
                                            <li>Tiền mặt</li>
                                            <li>Thẻ ngân hàng</li>
                                            <li>Bảo hiểm y tế</li>
                                            <li>Bảo hiểm tư nhân</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

   
    <?php include 'includes/appointment-modal.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/contact.js"></script>
    
    <?php include 'includes/footer.php'; ?> 
</body>
</html> 
