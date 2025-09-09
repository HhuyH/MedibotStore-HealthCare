-- Bảng lưu trữ token reset password
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_at TIMESTAMP NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_email (email),
    INDEX idx_expires (expires_at),
    INDEX idx_used (used)
);

-- Xóa token cũ (tự động dọn dẹp)
CREATE EVENT IF NOT EXISTS cleanup_expired_password_tokens
ON SCHEDULE EVERY 1 HOUR
DO
DELETE FROM password_reset_tokens 
WHERE expires_at < NOW() OR used = TRUE AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY); 