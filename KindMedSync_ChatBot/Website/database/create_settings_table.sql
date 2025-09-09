-- Tạo bảng settings cho hệ thống admin
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert các cài đặt mặc định
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'QuickMed Hospital'),
('site_description', 'Hệ thống quản lý bệnh viện và tư vấn sức khỏe'),
('site_keywords', 'bệnh viện, tư vấn sức khỏe, khám bệnh'),
('contact_email', 'admin@quickmed.com'),
('contact_phone', '0123456789'),
('address', '123 Đường ABC, Quận 1, TP.HCM'),
('smtp_host', 'smtp.gmail.com'),
('smtp_port', '587'),
('smtp_username', ''),
('smtp_password', ''),
('smtp_secure', 'tls'),
('facebook_url', ''),
('twitter_url', ''),
('instagram_url', ''),
('youtube_url', '')
ON DUPLICATE KEY UPDATE 
  `setting_value` = VALUES(`setting_value`),
  `updated_at` = CURRENT_TIMESTAMP; 