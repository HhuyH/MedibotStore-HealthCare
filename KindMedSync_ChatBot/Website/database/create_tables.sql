-- Tạo bảng product_reviews
CREATE TABLE IF NOT EXISTS product_reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating DECIMAL(2,1) NOT NULL,
    comment TEXT,
    images JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm dữ liệu mẫu
INSERT INTO product_reviews (product_id, user_id, rating, comment, created_at) VALUES
(1, 1, 5.0, 'Sản phẩm rất tốt, đóng gói cẩn thận', '2024-03-15 10:00:00'),
(1, 2, 4.5, 'Chất lượng ổn, giao hàng nhanh', '2024-03-14 15:30:00'),
(2, 1, 4.0, 'Sản phẩm tốt nhưng giá hơi cao', '2024-03-13 09:45:00'),
(3, 3, 5.0, 'Rất hài lòng với sản phẩm này', '2024-03-12 14:20:00'); 