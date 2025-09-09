-- Thêm reviews mẫu để test rating system
-- Chạy file này để có dữ liệu rating hiển thị

-- Xóa reviews cũ nếu có
DELETE FROM product_reviews WHERE review_id > 0;

-- Reset auto increment
ALTER TABLE product_reviews AUTO_INCREMENT = 1;

-- Thêm reviews mẫu cho các sản phẩm
INSERT INTO product_reviews (product_id, rating, comment, created_at) VALUES

-- Reviews cho Vitamin C (product_id = 1) - Rating 4.5
(1, 5, 'Sản phẩm rất tốt, tăng cường sức đề kháng hiệu quả', '2024-12-20 10:00:00'),
(1, 4, 'Chất lượng ổn, giá hợp lý', '2024-12-21 14:30:00'),
(1, 5, 'Đóng gói cẩn thận, giao hàng nhanh', '2024-12-22 09:15:00'),
(1, 4, 'Dùng được, không tác dụng phụ', '2024-12-23 16:45:00'),
(1, 5, 'Rất hài lòng, sẽ mua lại', '2024-12-24 08:20:00'),

-- Reviews cho Omega 3 (product_id = 2) - Rating 4.7
(2, 5, 'Dầu cá chất lượng cao, không tanh', '2024-12-20 11:00:00'),
(2, 5, 'Hiệu quả rõ rệt sau 1 tháng dùng', '2024-12-21 15:30:00'),
(2, 4, 'Tốt cho sức khỏe tim mạch', '2024-12-22 10:15:00'),
(2, 5, 'Đáng tiền, chất lượng xuất sắc', '2024-12-23 17:45:00'),
(2, 5, 'Bao bì đẹp, sản phẩm chính hãng', '2024-12-24 09:30:00'),
(2, 4, 'Sử dụng tốt, không mùi khó chịu', '2024-12-25 12:15:00'),

-- Reviews cho Calcium D3 (product_id = 3) - Rating 4.2
(3, 4, 'Viên uống dễ nuốt, hiệu quả tốt', '2024-12-20 12:00:00'),
(3, 4, 'Bổ sung canxi hiệu quả', '2024-12-21 16:30:00'),
(3, 5, 'Tốt cho xương khớp', '2024-12-22 11:15:00'),
(3, 4, 'Chất lượng ổn định', '2024-12-23 18:45:00'),
(3, 3, 'Bình thường, không có gì đặc biệt', '2024-12-24 10:30:00'),

-- Reviews cho Paracetamol (product_id = 4) - Rating 4.8
(4, 5, 'Giảm đau nhanh chóng và hiệu quả', '2024-12-20 13:00:00'),
(4, 5, 'An toàn, không tác dụng phụ', '2024-12-21 17:30:00'),
(4, 4, 'Tốt, giá rẻ', '2024-12-22 12:15:00'),
(4, 5, 'Luôn dự trữ trong nhà', '2024-12-23 19:45:00'),
(4, 5, 'Hiệu quả cao, đáng tin cậy', '2024-12-24 11:30:00'),

-- Reviews cho Amoxicillin (product_id = 5) - Rating 4.3
(5, 4, 'Kháng sinh tốt, điều trị hiệu quả', '2024-12-20 14:00:00'),
(5, 5, 'Chất lượng cao, có hiệu quả', '2024-12-21 18:30:00'),
(5, 4, 'Dùng theo chỉ định bác sĩ rất tốt', '2024-12-22 13:15:00'),
(5, 4, 'Ổn, không dị ứng', '2024-12-23 20:45:00'),

-- Reviews cho sản phẩm khác nếu có
(6, 4, 'Máy đo huyết áp chính xác', '2024-12-20 15:00:00'),
(6, 5, 'Dễ sử dụng, hiển thị rõ ràng', '2024-12-21 19:30:00'),
(6, 4, 'Tốt cho người già', '2024-12-22 14:15:00'),

(7, 5, 'Nhiệt kế đo nhanh và chính xác', '2024-12-20 16:00:00'),
(7, 4, 'Tiện lợi, dễ dùng', '2024-12-21 20:30:00'),
(7, 5, 'Chất lượng tốt', '2024-12-22 15:15:00'),

(8, 4, 'Sát khuẩn hiệu quả', '2024-12-20 17:00:00'),
(8, 4, 'Mùi thơm nhẹ, không gây khó chịu', '2024-12-21 21:30:00'),

(9, 5, 'Băng gạc mềm mại, thấm hút tốt', '2024-12-20 18:00:00'),
(9, 4, 'Chất lượng ổn, giá hợp lý', '2024-12-21 22:30:00');

-- Kiểm tra kết quả
SELECT 
    p.product_id,
    p.name,
    AVG(pr.rating) as avg_rating,
    COUNT(pr.review_id) as review_count
FROM products p 
LEFT JOIN product_reviews pr ON p.product_id = pr.product_id 
WHERE p.is_active = 1
GROUP BY p.product_id, p.name
ORDER BY avg_rating DESC; 