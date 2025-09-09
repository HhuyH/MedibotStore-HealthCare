-- Cập nhật cài đặt SMTP cho MediSyncNoreply
-- Chạy file này để cập nhật thông tin email mới

-- Cập nhật cài đặt SMTP
UPDATE `settings` SET `setting_value` = 'smtp.gmail.com' WHERE `setting_key` = 'smtp_host';
UPDATE `settings` SET `setting_value` = '587' WHERE `setting_key` = 'smtp_port';
UPDATE `settings` SET `setting_value` = 'medisyncnoreplybot@gmail.com' WHERE `setting_key` = 'smtp_username';
UPDATE `settings` SET `setting_value` = 'zvgk wleu zgyd ljyr' WHERE `setting_key` = 'smtp_password';
UPDATE `settings` SET `setting_value` = 'tls' WHERE `setting_key` = 'smtp_secure';

-- Cập nhật thông tin email gửi đi
UPDATE `settings` SET `setting_value` = 'MediSyncNoreply' WHERE `setting_key` = 'email_from_name';
UPDATE `settings` SET `setting_value` = 'medisyncnoreplybot@gmail.com' WHERE `setting_key` = 'email_from_address';

-- Nếu chưa có bản ghi thì insert mới
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('smtp_host', 'smtp.gmail.com'),
('smtp_port', '587'),
('smtp_username', 'medisyncnoreplybot@gmail.com'),
('smtp_password', 'zvgk wleu zgyd ljyr'),
('smtp_secure', 'tls'),
('email_from_name', 'MediSyncNoreply'),
('email_from_address', 'medisyncnoreplybot@gmail.com');

-- Kích hoạt email notifications
UPDATE `settings` SET `setting_value` = '1' WHERE `setting_key` = 'email_notifications_enabled';
UPDATE `settings` SET `setting_value` = '1' WHERE `setting_key` = 'email_welcome_enabled';
UPDATE `settings` SET `setting_value` = '1' WHERE `setting_key` = 'email_appointment_enabled';
UPDATE `settings` SET `setting_value` = '1' WHERE `setting_key` = 'email_order_enabled';

INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('email_notifications_enabled', '1'),
('email_welcome_enabled', '1'),
('email_appointment_enabled', '1'),
('email_order_enabled', '1');

-- Hiển thị kết quả
SELECT 'SMTP Configuration Updated Successfully' as Status;
SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'smtp_%' OR setting_key LIKE 'email_%'; 