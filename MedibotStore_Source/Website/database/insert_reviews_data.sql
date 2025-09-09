-- Thêm reviews mẫu cho sản phẩm
-- Chạy file này để có dữ liệu rating hiển thị trên trang shop

-- Xóa reviews cũ nếu có
DELETE FROM product_reviews WHERE review_id > 0;

-- Reset auto increment
ALTER TABLE product_reviews AUTO_INCREMENT = 1;

-- Thêm reviews mẫu cho các sản phẩm
INSERT INTO product_reviews (product_id, user_id, rating, comment, created_at) VALUES

-- Reviews cho Vitamin C (product_id = 1) - Rating trung bình 4.5
(1, 1, 5, 'Sản phẩm rất tốt, tăng cường sức đề kháng hiệu quả. Uống 1 tháng thấy cơ thể khỏe hơn rõ rệt.', '2024-12-20 10:00:00'),
(1, 2, 4, 'Chất lượng ổn, giá hợp lý. Đóng gói đẹp, giao hàng nhanh.', '2024-12-21 14:30:00'),
(1, 3, 5, 'Đóng gói cẩn thận, hàng chính hãng. Sẽ mua lại lần sau.', '2024-12-22 09:15:00'),
(1, 1, 4, 'Dùng được, không có tác dụng phụ. Hòa tan nhanh trong nước.', '2024-12-23 16:45:00'),
(1, 2, 5, 'Rất hài lòng với sản phẩm này. Gia đình tôi đều dùng.', '2024-12-24 08:20:00'),
(1, 3, 5, 'Vitamin C chất lượng cao, bổ sung tốt cho sức khỏe.', '2024-12-25 11:30:00'),

-- Reviews cho Omega 3 (product_id = 2) - Rating trung bình 4.7
(2, 1, 5, 'Dầu cá omega 3 chất lượng cao, không có mùi tanh khó chịu.', '2024-12-20 11:00:00'),
(2, 2, 5, 'Hiệu quả rõ rệt sau 1 tháng dùng. Tim mạch ổn định hơn.', '2024-12-21 15:30:00'),
(2, 3, 4, 'Tốt cho sức khỏe tim mạch. Viên nhỏ dễ nuốt.', '2024-12-22 10:15:00'),
(2, 1, 5, 'Đáng tiền, chất lượng xuất sắc. Nhập khẩu từ Na Uy.', '2024-12-23 17:45:00'),
(2, 2, 5, 'Bao bì đẹp, sản phẩm chính hãng có tem phiếu đầy đủ.', '2024-12-24 09:30:00'),
(2, 3, 4, 'Sử dụng tốt, không mùi khó chịu. Bổ sung DHA EPA hiệu quả.', '2024-12-25 12:15:00'),
(2, 1, 5, 'Omega 3 tốt nhất tôi từng dùng. Sẽ giới thiệu bạn bè.', '2024-12-26 14:20:00'),

-- Reviews cho Calcium D3 (product_id = 3) - Rating trung bình 4.2  
(3, 1, 4, 'Viên uống dễ nuốt, hiệu quả tốt cho xương khớp.', '2024-12-20 12:00:00'),
(3, 2, 4, 'Bổ sung canxi hiệu quả, không bị táo bón.', '2024-12-21 16:30:00'),
(3, 3, 5, 'Tốt cho xương khớp, phù hợp với người cao tuổi.', '2024-12-22 11:15:00'),
(3, 1, 4, 'Chất lượng ổn định, giá cả phải chăng.', '2024-12-23 18:45:00'),
(3, 2, 3, 'Bình thường, không có gì đặc biệt. Uống được.', '2024-12-24 10:30:00'),
(3, 3, 5, 'Kết hợp Canxi + D3 rất tốt. Hấp thu tối ưu.', '2024-12-25 13:45:00'),

-- Reviews cho Paracetamol (product_id = 4) - Rating trung bình 4.8
(4, 1, 5, 'Thuốc giảm đau nhanh chóng và hiệu quả. An toàn cho trẻ em.', '2024-12-20 13:00:00'),
(4, 2, 5, 'An toàn, không có tác dụng phụ. Luôn có sẵn trong tủ thuốc.', '2024-12-21 17:30:00'),
(4, 3, 4, 'Hiệu quả tốt, giá rẻ. Phù hợp với túi tiền mọi gia đình.', '2024-12-22 12:15:00'),
(4, 1, 5, 'Luôn dự trữ thuốc này trong nhà. Hạ sốt nhanh.', '2024-12-23 19:45:00'),
(4, 2, 5, 'Hiệu quả cao, đáng tin cậy. Thuốc quốc dân.', '2024-12-24 11:30:00'),
(4, 3, 5, 'Giảm đau đầu, nhức mỏi rất nhanh. Không đau dạ dày.', '2024-12-25 15:20:00'),

-- Reviews cho Amoxicillin (product_id = 5) - Rating trung bình 4.3
(5, 1, 4, 'Kháng sinh tốt, điều trị nhiễm khuẩn hiệu quả.', '2024-12-20 14:00:00'),
(5, 2, 5, 'Chất lượng cao, có hiệu quả trong điều trị viêm họng.', '2024-12-21 18:30:00'),
(5, 3, 4, 'Dùng theo chỉ định bác sĩ rất tốt. Ít tác dụng phụ.', '2024-12-22 13:15:00'),
(5, 1, 4, 'Ổn, không bị dị ứng. Điều trị viêm đường hô hấp tốt.', '2024-12-23 20:45:00'),
(5, 2, 4, 'Kháng sinh phổ rộng, hiệu quả cao.', '2024-12-24 12:40:00'),

-- Reviews cho các sản phẩm khác nếu có trong database
(6, 1, 4, 'Máy đo huyết áp chính xác, màn hình hiển thị rõ ràng.', '2024-12-20 15:00:00'),
(6, 2, 5, 'Dễ sử dụng, hiển thị số liệu rõ ràng. Phù hợp người già.', '2024-12-21 19:30:00'),
(6, 3, 4, 'Tốt cho người cao tuổi. Đo nhanh, kết quả chính xác.', '2024-12-22 14:15:00'),
(6, 1, 5, 'Thiết bị y tế gia đình cần thiết. Chất lượng Nhật Bản.', '2024-12-23 16:30:00'),

(7, 1, 5, 'Nhiệt kế đo nhanh và chính xác. Tự động báo khi hoàn thành.', '2024-12-20 16:00:00'),
(7, 2, 4, 'Tiện lợi, dễ dùng. Pin bền, màn hình LED sáng.', '2024-12-21 20:30:00'),
(7, 3, 5, 'Chất lượng tốt, đo nhiệt độ chính xác. An toàn cho trẻ em.', '2024-12-22 15:15:00'),
(7, 1, 4, 'Nhiệt kế điện tử tiện dụng. Tốc độ đo nhanh.', '2024-12-23 17:45:00'),

(8, 1, 4, 'Dung dịch sát khuẩn hiệu quả, diệt vi khuẩn nhanh.', '2024-12-20 17:00:00'),
(8, 2, 4, 'Mùi thơm nhẹ, không gây khó chịu. Dễ sử dụng.', '2024-12-21 21:30:00'),
(8, 3, 5, 'Sát khuẩn tốt, không làm khô da tay. Chai pump tiện lợi.', '2024-12-22 16:30:00'),

(9, 1, 5, 'Băng gạc mềm mại, thấm hút tốt. Y tế chất lượng cao.', '2024-12-20 18:00:00'),
(9, 2, 4, 'Chất lượng ổn, giá hợp lý. Băng gạc vô trùng an toàn.', '2024-12-21 22:30:00'),
(9, 3, 5, 'Băng gạc y tế chuẩn, thấm hút tốt. Không gây dị ứng.', '2024-12-22 17:15:00');

-- Kiểm tra kết quả sau khi thêm
SELECT 
    p.product_id,
    p.name,
    ROUND(AVG(pr.rating), 1) as avg_rating,
    COUNT(pr.review_id) as review_count
FROM products p 
LEFT JOIN product_reviews pr ON p.product_id = pr.product_id 
WHERE p.is_active = 1
GROUP BY p.product_id, p.name
ORDER BY avg_rating DESC;

-- Thông báo hoàn thành
SELECT 'Đã thêm reviews mẫu thành công! Giờ rating sẽ hiển thị trên trang shop.' as message; 