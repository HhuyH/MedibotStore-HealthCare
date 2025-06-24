* sử dụng sql của xammpp
-- b1 mở xampp và chọn phần mysql
-- b2 start server - start mysql
-- b3 mở phpmyadmin
-- b4 tạo database với tên qickmed
-- xét đặc quyền cho sql 
-- copy sql từ dòng 15 đến dòng 143
-- paste vào query để tạo bảng
-- chạy lệnh show tables để kiểm tra xem có bảng nào được tạo không
-- sau đó vào lại project để sử dụng
-- vào include/config/db.php
-- sửa lại các thông số sao cho đúng với cơ sở dữ liệu mà bạn vừa tạo
-- vào file index.php
-- sửa lại các thông số sao cho đúng với cơ sở dữ liệu mà bạn vừa tạo

-- Tạo database
CREATE DATABASE IF NOT EXISTS qickmed CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE qickmed;

-- Tạo bảng roles trước (vì users có foreign key tới roles)
CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT
);

-- Thêm dữ liệu mẫu cho roles
INSERT INTO roles (role_name, description) VALUES 
('admin', 'Quản trị viên hệ thống'),
('patient', 'Bệnh nhân'),
('doctor', 'Bác sĩ');

-- Bảng lưu thông tin tài khoản
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone_number VARCHAR(15) UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL DEFAULT 2,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- Bảng lưu thông tin người dùng
CREATE TABLE users_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(100),
    gender ENUM('Nam', 'Nữ', 'Khác'),
    date_of_birth DATE,
    phone VARCHAR(15),
    profile_picture VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Bảng guest users
CREATE TABLE guest_users (
    guest_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255),
    phone VARCHAR(20),
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng lưu địa chỉ người dùng 
CREATE TABLE user_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address_line VARCHAR(255) NOT NULL,
    ward VARCHAR(100),
    district VARCHAR(100),
    city VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'Vietnam',
    is_default BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Bảng lưu token "Remember me"
CREATE TABLE remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_token (user_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Tạo tài khoản admin mặc định
-- Mật khẩu: admin123 (plain text)
INSERT INTO users (username, email, password, role_id) VALUES 
('admin', 'admin@qickmed.com', 'admin123', 1);

INSERT INTO users_info (user_id, full_name, gender) VALUES 
(1, 'Quản trị viên', 'Khác'); 


-- sau khi tạo xong các bảng, cần thêm các dữ liệu mẫu cho các bảng đó
ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive', 'suspended') DEFAULT 'active';
ALTER TABLE users_info ADD COLUMN phone VARCHAR(15);

-- update them cai nay vao