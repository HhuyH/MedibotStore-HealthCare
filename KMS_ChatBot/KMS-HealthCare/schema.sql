// Data user CURRENT_TIMESTAMP

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,                   -- Khóa chính, định danh người dùng
    username VARCHAR(50) UNIQUE NOT NULL,                     -- Tên đăng nhập, không được trùng
    email VARCHAR(100) UNIQUE NOT NULL,                       -- Email đăng ký, duy nhất
    phone_number VARCHAR(15) UNIQUE,                          -- Số điện thoại (nếu có), cũng duy nhất
    password_hash VARCHAR(255) NOT NULL,                      -- Mật khẩu đã mã hóa
    role_id INT NOT NULL,                                     -- Liên kết đến bảng roles
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,           -- Thời gian tạo tài khoản
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (role_id) REFERENCES roles(role_id)                -- Ràng buộc vai trò người dùng
);

-- Bảng lưu vai trò
CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,                   -- Khóa chính
    role_name VARCHAR(50) UNIQUE NOT NULL,                    -- Tên vai trò: guest, patient, admin, doctor
	description TEXT										  -- 'Mô tả vai trò nếu cần',
);

-- Bảng lưu thông tin người dùng
CREATE TABLE users_info (
    id INT AUTO_INCREMENT PRIMARY KEY,                        -- Khóa chính
    user_id INT NOT NULL,                                     -- Khóa ngoại liên kết với bảng users
    full_name VARCHAR(100),                                   -- Họ tên đầy đủ
    gender ENUM('Nam', 'Nữ', 'Khác'),                         -- Giới tính
    date_of_birth DATE,                                       -- Ngày sinh
    profile_picture VARCHAR(255),                             -- URL ảnh đại diện (nếu có)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Bảng lưu địa chỉ người dùng 
CREATE TABLE user_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,                -- Khóa chính, tự động tăng
    user_id INT NOT NULL,                             -- ID người dùng liên kết với bảng users
    address_line VARCHAR(255) NOT NULL,               -- Địa chỉ chi tiết: số nhà, tên đường, căn hộ...
    ward VARCHAR(100),                                -- Phường/xã
    district VARCHAR(100),                            -- Quận/huyện
    city VARCHAR(100),                                -- Thành phố
    postal_code VARCHAR(20),                          -- Mã bưu chính (nếu có)
    country VARCHAR(100) DEFAULT 'Vietnam',           -- Quốc gia, mặc định là Việt Nam
    is_default BOOLEAN DEFAULT FALSE,                 -- Địa chỉ mặc định (chỉ 1 địa chỉ của user là TRUE)
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,    -- Thời gian tạo địa chỉ
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,  -- Thời gian cập nhật địa chỉ
    
    FOREIGN KEY (user_id) REFERENCES users(user_id)        -- Khóa ngoại liên kết với bảng users
);