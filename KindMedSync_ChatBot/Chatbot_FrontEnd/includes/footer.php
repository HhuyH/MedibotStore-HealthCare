<footer class="footer pt-5 pb-4 mt-5 position-relative">
  <!-- Background Image -->
  <div class="footer-bg position-absolute top-0 start-0 w-100 h-100"></div>
  
  <!-- Overlay -->
  <div class="footer-overlay position-absolute top-0 start-0 w-100 h-100"></div>
  
  <div class="container position-relative footer-content">
    <!-- Main Footer Content -->
    <div class="row g-4 mb-4">
      <!-- Logo & Description -->
      <div class="col-lg-4 col-md-6 text-center text-md-start">
        <div class="footer-brand mb-3">
          <img src="/assets/img/logo-white.png" alt="MediSync Logo" class="footer-logo mb-3">
          <p class="footer-description">
            Phòng khám y khoa hiện đại với đội ngũ bác sĩ chuyên nghiệp, 
            mang đến dịch vụ chăm sóc sức khỏe toàn diện và tận tâm.
          </p>
        </div>
      </div>
      
      <!-- Contact Info -->
      <div class="col-lg-4 col-md-6">
        <h5 class="footer-title mb-3">Liên hệ</h5>
        <div class="footer-contact">
          <div class="contact-item mb-2">
            <i class="fas fa-map-marker-alt me-3"></i>
            <span>123 Đường Sức Khỏe, Quận 1, TP.HCM</span>
          </div>
          <div class="contact-item mb-2">
            <i class="fas fa-phone-alt me-3"></i>
            <a href="tel:0123456789" class="contact-link">0123 456 789</a>
          </div>
          <div class="contact-item mb-2">
            <i class="fas fa-envelope me-3"></i>
            <a href="mailto:info@qickmed.vn" class="contact-link">info@qickmed.vn</a>
          </div>
          <div class="contact-item">
            <i class="fas fa-clock me-3"></i>
            <span>Thứ 2 - Chủ nhật: 7:00 - 21:00</span>
          </div>
        </div>
      </div>
      
      <!-- Quick Links & Social -->
      <div class="col-lg-4 col-md-12">
        <h5 class="footer-title mb-3">Kết nối với chúng tôi</h5>
        <div class="footer-social mb-3">
          <a href="#" class="social-link facebook" title="Facebook">
            <i class="fab fa-facebook-f"></i>
          </a>
          <a href="#" class="social-link instagram" title="Instagram">
            <i class="fab fa-instagram"></i>
          </a>
          <a href="#" class="social-link youtube" title="YouTube">
            <i class="fab fa-youtube"></i>
          </a>
          <a href="#" class="social-link twitter" title="Twitter">
            <i class="fab fa-twitter"></i>
          </a>
        </div>
        <div class="footer-newsletter">
          <p class="mb-2">Đăng ký nhận tin tức y tế mới nhất</p>
          <div class="newsletter-form">
            <input type="email" class="form-control newsletter-input" placeholder="Email của bạn">
            <button class="btn newsletter-btn">
              <i class="fas fa-paper-plane"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Footer Bottom -->
    <div class="footer-bottom pt-3">
      <div class="row align-items-center">
        <div class="col-md-6 text-center text-md-start">
          <p class="copyright mb-0">
            &copy; 2024 <span class="brand-name">MediSync</span>. All rights reserved.
          </p>
        </div>
        <div class="col-md-6 text-center text-md-end">
          <div class="footer-links">
            <a href="#" class="footer-link">Chính sách bảo mật</a>
            <span class="separator">|</span>
            <a href="#" class="footer-link">Điều khoản sử dụng</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <style>
    .footer {
      background: #1a1a1a;
      color: #ffffff;
      position: relative;
      overflow: hidden;
      min-height: 400px;
    }
    
    .footer-bg {
      background: url('/assets/img/footer_bgk.jpg') center/cover no-repeat;
      z-index: 1;
    }
    
    .footer-overlay {
      background: linear-gradient(135deg, 
        rgba(25, 118, 210, 0.85) 0%, 
        rgba(30, 192, 247, 0.75) 50%, 
        rgba(25, 118, 210, 0.9) 100%);
      z-index: 2;
    }
    
    .footer-content {
      z-index: 3;
    }
    
    .footer-logo {
      height: 60px;
      filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
    }
    
    .footer-description {
      color: rgba(255, 255, 255, 0.9);
      font-size: 1rem;
      line-height: 1.6;
      margin: 0;
    }
    
    .footer-title {
      color: #ffffff;
      font-weight: 600;
      font-size: 1.25rem;
      margin-bottom: 1rem;
      position: relative;
      padding-bottom: 0.5rem;
    }
    
    .footer-title::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 40px;
      height: 3px;
      background: linear-gradient(90deg, #ffeb3b, #ffc107);
      border-radius: 2px;
    }
    
    .footer-contact .contact-item {
      display: flex;
      align-items: center;
      color: rgba(255, 255, 255, 0.9);
      font-size: 0.95rem;
      line-height: 1.5;
    }
    
    .footer-contact i {
      color: #ffeb3b;
      width: 20px;
      font-size: 1rem;
    }
    
    .contact-link {
      color: rgba(255, 255, 255, 0.9);
      text-decoration: none;
      transition: all 0.3s ease;
    }
    
    .contact-link:hover {
      color: #ffeb3b;
      text-decoration: none;
    }
    
    .footer-social {
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
    }
    
    .social-link {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 45px;
      height: 45px;
      background: rgba(255, 255, 255, 0.1);
      color: #ffffff;
      text-decoration: none;
      border-radius: 50%;
      font-size: 1.2rem;
      transition: all 0.3s ease;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .social-link:hover {
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
      color: #ffffff;
    }
    
    .social-link.facebook:hover {
      background: #1877f2;
    }
    
    .social-link.instagram:hover {
      background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
    }
    
    .social-link.youtube:hover {
      background: #ff0000;
    }
    
    .social-link.twitter:hover {
      background: #1da1f2;
    }
    
    .newsletter-form {
      display: flex;
      gap: 10px;
      margin-top: 10px;
    }
    
    .newsletter-input {
      flex: 1;
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.3);
      color: #ffffff;
      border-radius: 25px;
      padding: 10px 15px;
      font-size: 0.9rem;
    }
    
    .newsletter-input::placeholder {
      color: rgba(255, 255, 255, 0.7);
    }
    
    .newsletter-input:focus {
      background: rgba(255, 255, 255, 0.15);
      border-color: #ffeb3b;
      color: #ffffff;
      box-shadow: 0 0 0 0.2rem rgba(255, 235, 59, 0.25);
    }
    
    .newsletter-btn {
      background: linear-gradient(45deg, #ffeb3b, #ffc107);
      color: #1976d2;
      border: none;
      border-radius: 50%;
      width: 45px;
      height: 45px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
      transition: all 0.3s ease;
    }
    
    .newsletter-btn:hover {
      transform: scale(1.05);
      box-shadow: 0 3px 10px rgba(255, 235, 59, 0.4);
      color: #1976d2;
    }
    
    .footer-bottom {
      border-top: 1px solid rgba(255, 255, 255, 0.2);
      margin-top: 2rem;
    }
    
    .copyright {
      color: rgba(255, 255, 255, 0.8);
      font-size: 0.9rem;
    }
    
    .brand-name {
      color: #ffeb3b;
      font-weight: 600;
    }
    
    .footer-links {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }
    
    .footer-link {
      color: rgba(255, 255, 255, 0.8);
      text-decoration: none;
      font-size: 0.9rem;
      transition: color 0.3s ease;
    }
    
    .footer-link:hover {
      color: #ffeb3b;
      text-decoration: none;
    }
    
    .separator {
      color: rgba(255, 255, 255, 0.5);
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
      .footer {
        min-height: auto;
      }
      
      .footer-title::after {
        left: 50%;
        transform: translateX(-50%);
      }
      
      .footer-brand {
        text-align: center;
      }
      
      .footer-social {
        justify-content: center;
      }
      
      .footer-links {
        margin-top: 1rem;
      }
      
      .newsletter-form {
        max-width: 300px;
        margin: 10px auto 0;
      }
    }
    
    @media (max-width: 576px) {
      .footer-logo {
        height: 50px;
      }
      
      .footer-title {
        font-size: 1.1rem;
      }
      
      .footer-description {
        font-size: 0.9rem;
      }
      
      .contact-item {
        font-size: 0.9rem;
      }
      
      .social-link {
        width: 40px;
        height: 40px;
        font-size: 1rem;
      }
    }
  </style>
</footer>
