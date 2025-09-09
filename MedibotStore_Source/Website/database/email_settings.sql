-- Cài đặt email mẫu cho QuickMed Hospital
-- Chạy file này để thiết lập cài đặt email cơ bản

-- Tạo bảng settings nếu chưa có
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng email_logs nếu chưa có  
CREATE TABLE IF NOT EXISTS `email_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('success','failed','error') DEFAULT 'success',
  `error_message` text,
  `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm các cài đặt email cơ bản
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'QuickMed Hospital'),
('site_description', 'Hệ thống quản lý bệnh viện và tư vấn sức khỏe'),
('contact_email', 'info@quickmed.com'),
('contact_phone', '0123456789'),
('address', '123 Đường ABC, Quận 1, TP.HCM'),

-- Cài đặt SMTP (Gmail)
('smtp_host', 'smtp.gmail.com'),
('smtp_port', '587'),
('smtp_username', 'medisyncnoreplybot@gmail.com'),
('smtp_password', 'zvgk wleu zgyd ljyr'),
('smtp_secure', 'tls'),

-- Cài đặt email gửi đi
('email_from_name', 'MediSyncNoreply'),
('email_from_address', 'medisyncnoreplybot@gmail.com'),

-- Cài đặt email template
('email_footer_text', 'Cảm ơn bạn đã sử dụng dịch vụ của QuickMed Hospital'),
('email_logo_url', '/assets/images/logo.png'),
('email_signature', 'Đội ngũ QuickMed Hospital')

ON DUPLICATE KEY UPDATE 
  `setting_value` = VALUES(`setting_value`),
  `updated_at` = CURRENT_TIMESTAMP;

-- Thêm một số cài đặt notification
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('email_notifications_enabled', '1'),
('email_welcome_enabled', '1'),
('email_appointment_enabled', '1'),
('email_order_enabled', '1'),
('email_admin_notifications', '1')

ON DUPLICATE KEY UPDATE 
  `setting_value` = VALUES(`setting_value`),
  `updated_at` = CURRENT_TIMESTAMP;

-- Tạo index để tăng tốc truy vấn
CREATE INDEX IF NOT EXISTS idx_email_logs_recipient ON email_logs(recipient);
CREATE INDEX IF NOT EXISTS idx_email_logs_sent_at ON email_logs(sent_at);
CREATE INDEX IF NOT EXISTS idx_email_logs_status ON email_logs(status); 