-- BẢNG user_addresses (giả sử bạn đã có, nếu chưa thì thêm dòng này)
CREATE TABLE IF NOT EXISTS user_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address_line VARCHAR(255) NOT NULL,
    ward VARCHAR(100),
    district VARCHAR(100),
    city VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'Vietnam',
    is_default TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- BẢNG orders
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,                    -- Mã đơn hàng hoặc giỏ hàng
    user_id INT NOT NULL,                                       -- Người sở hữu
    address_id INT,                                             -- Liên kết đến bảng user_addresses (id)
    shipping_address TEXT,                                      -- Snapshot địa chỉ tại thời điểm đặt
    total DECIMAL(16, 0),                                       -- Tổng tiền
    payment_method VARCHAR(50),                                 -- COD / Momo / VNPay...
    payment_status VARCHAR(50) DEFAULT 'pending',               -- Trạng thái thanh toán
    status ENUM('cart', 'pending', 'processing', 'shipped', 'completed', 'cancelled') DEFAULT 'cart',
    order_note TEXT,                                            -- Ghi chú của khách
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,             -- Thời điểm tạo đơn
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (address_id) REFERENCES user_addresses(id)
) ENGINE=InnoDB;

-- BẢNG order_items
CREATE TABLE order_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,                     -- Khóa chính
    order_id INT NOT NULL,                                      -- Đơn hàng
    product_id INT NOT NULL,                                    -- Sản phẩm
    quantity INT NOT NULL,                                      -- Số lượng
    unit_price DECIMAL(16, 0) NOT NULL,                         -- Giá lúc đặt

    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id)
) ENGINE=InnoDB;

-- BẢNG payments
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,                  -- Khóa chính
    user_id INT,                                                -- Người thanh toán
    order_id INT NOT NULL,                                      -- Đơn hàng
    payment_method VARCHAR(50) NOT NULL,                        -- VNPay, Momo, COD...
    payment_status VARCHAR(50) DEFAULT 'pending',               -- pending, completed, failed
    amount DECIMAL(16, 0) NOT NULL,                             -- Số tiền
    payment_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,           -- Thời gian

    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;