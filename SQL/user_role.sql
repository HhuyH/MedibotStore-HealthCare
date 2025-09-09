-- Tạo user mới
CREATE USER 'chatbot_user'@'localhost' IDENTIFIED BY 'StrongPassword123';

-- Cấp quyền SELECT, INSERT, UPDATE trên toàn bộ database
GRANT SELECT, INSERT, UPDATE ON medicare.* TO 'chatbot_user'@'localhost';

-- Lưu lại thay đổi
FLUSH PRIVILEGES;
