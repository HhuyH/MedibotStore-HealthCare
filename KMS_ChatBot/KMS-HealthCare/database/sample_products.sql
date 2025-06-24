-- Sample products data
INSERT INTO products (category_id, name, description, price, stock, image_url, is_active) VALUES
(1, 'Paracetamol 500mg', 'Thuốc giảm đau, hạ sốt hiệu quả', 25000, 100, '/assets/images/paracetamol.jpg', TRUE),
(1, 'Vitamin C 1000mg', 'Tăng cường sức đề kháng, chống oxy hóa', 180000, 50, '/assets/images/vitamin-c.jpg', TRUE),
(2, 'Máy đo huyết áp Omron', 'Máy đo huyết áp tự động chính xác', 1200000, 20, '/assets/images/blood-pressure.jpg', TRUE),
(2, 'Nhiệt kế điện tử', 'Nhiệt kế đo nhiệt độ nhanh chóng', 150000, 30, '/assets/images/thermometer.jpg', TRUE),
(3, 'Omega 3 Fish Oil', 'Bổ sung omega 3 từ dầu cá tự nhiên', 580000, 25, '/assets/images/omega3.jpg', TRUE),
(3, 'Collagen Marine Plus', 'Collagen từ da cá giúp đẹp da', 750000, 15, '/assets/images/collagen.jpg', TRUE),
(1, 'Aspirin 100mg', 'Thuốc chống đông máu, ngăn ngừa đột quỵ', 35000, 80, '/assets/images/aspirin.jpg', TRUE),
(2, 'Khẩu trang y tế 4 lớp', 'Khẩu trang kháng khuẩn cao cấp', 85000, 200, '/assets/images/mask.jpg', TRUE);

-- Sample product reviews để tạo rating
INSERT INTO product_reviews (product_id, user_id, rating, comment, created_at) VALUES
-- Paracetamol (4.8 rating - sẽ được giảm giá)
(1, 1, 5, 'Hiệu quả nhanh, giá rẻ', '2024-01-15 10:00:00'),
(1, 2, 5, 'Dùng tốt, an toàn', '2024-01-16 14:30:00'),
(1, 3, 4, 'Ổn, không tác dụng phụ', '2024-01-17 09:15:00'),
(1, 4, 5, 'Tốt, sẽ mua lại', '2024-01-18 16:45:00'),

-- Vitamin C (4.7 rating - sẽ được giảm giá)
(2, 1, 5, 'Tăng sức đề kháng rất tốt', '2024-01-15 11:00:00'),
(2, 2, 5, 'Chất lượng cao', '2024-01-16 15:30:00'),
(2, 3, 4, 'Hiệu quả rõ rệt', '2024-01-17 10:15:00'),
(2, 4, 5, 'Đáng tiền', '2024-01-18 17:45:00'),
(2, 5, 5, 'Sản phẩm tốt', '2024-01-19 08:30:00'),

-- Máy đo huyết áp (4.5 rating - vừa đủ được giảm giá)
(3, 1, 5, 'Chính xác, dễ sử dụng', '2024-01-15 12:00:00'),
(3, 2, 4, 'Tốt, giá hợp lý', '2024-01-16 16:30:00'),
(3, 3, 5, 'Đo chính xác', '2024-01-17 11:15:00'),
(3, 4, 4, 'Chất lượng ổn', '2024-01-18 18:45:00'),

-- Nhiệt kế (4.2 rating - không giảm giá)
(4, 1, 4, 'Đo nhanh, tiện lợi', '2024-01-15 13:00:00'),
(4, 2, 4, 'Tạm ổn', '2024-01-16 17:30:00'),
(4, 3, 5, 'Dùng tốt', '2024-01-17 12:15:00'),
(4, 4, 4, 'Bình thường', '2024-01-18 19:45:00'),

-- Omega 3 (4.9 rating - giảm giá cao)
(5, 1, 5, 'Chất lượng xuất sắc', '2024-01-15 14:00:00'),
(5, 2, 5, 'Rất tốt cho sức khỏe', '2024-01-16 18:30:00'),
(5, 3, 5, 'Hiệu quả cao', '2024-01-17 13:15:00'),
(5, 4, 5, 'Đáng mua', '2024-01-18 20:45:00'),
(5, 5, 4, 'Sản phẩm chất lượng', '2024-01-19 09:30:00'),

-- Collagen (4.6 rating - giảm giá)
(6, 1, 5, 'Da đẹp hơn sau dùng', '2024-01-15 15:00:00'),
(6, 2, 4, 'Hiệu quả rõ rệt', '2024-01-16 19:30:00'),
(6, 3, 5, 'Tốt cho làn da', '2024-01-17 14:15:00'),
(6, 4, 5, 'Chất lượng cao', '2024-01-18 21:45:00'),

-- Aspirin (3.8 rating - không giảm giá)
(7, 1, 4, 'Hiệu quả tốt', '2024-01-15 16:00:00'),
(7, 2, 3, 'Bình thường', '2024-01-16 20:30:00'),
(7, 3, 4, 'Dùng được', '2024-01-17 15:15:00'),
(7, 4, 4, 'Tạm ổn', '2024-01-18 22:45:00'),

-- Khẩu trang (4.5 rating - vừa đủ giảm giá)
(8, 1, 5, 'Chất lượng tốt', '2024-01-15 17:00:00'),
(8, 2, 4, 'Thoáng khí', '2024-01-16 21:30:00'),
(8, 3, 5, 'Đeo thoải mái', '2024-01-17 16:15:00'),
(8, 4, 4, 'Ổn định', '2024-01-18 23:45:00'); 