----------------------------------------------USERS----------------------------------------------------------------------------------------------------------------
INSERT INTO users (user_id, username, email, phone_number, password_hash, role_id, created_at)
VALUES
(1, 'admin', 'admin@gmail.com', '0123456789',
 '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC',--password 123
 1, NOW()),

(2, 'huy', 'hoanhuy12@gmail.com', '0999999999',
 '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC',
 1, NOW()),

(3, 'dr.hanh', 'docter@example.com', '0888888888',
 '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC',
 2, NOW());

('nguyenvana', 'vana@example.com', '0901234567',
 '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC', 
 3, NOW());


----------------------------------------------USERS_info----------------------------------------------------------------------------------------------------------------
INSERT INTO users_info (user_id, full_name, gender, date_of_birth)
VALUES
(1, 'Quản trị viên', 'Nam', '1990-01-01'),
(2, 'Huy', 'Nam', '1985-06-15'),
(3, 'Dr.Hand', 'nữ', '2000-12-01');
(4, 'Nguyễn Văn A', 'Nam', '1995-08-15');

----------------------------------------------USERS_ADDRESSES----------------------------------------------------------------------------------------------------------------
INSERT INTO user_addresses (
    user_id, address_line, ward, district, city, postal_code, country, is_default
)
VALUES
-- Quản trị viên (user_id = 1)
(1, '123 Trần Hưng Đạo', 'Nguyễn Cư Trinh', 'Quận 1', 'TP.HCM', '700000', 'Vietnam', TRUE),

-- Hòa Huy (user_id = 2)
(2, '456 Lê Lợi', 'Bến Nghé', 'Quận 1', 'TP.HCM', '700000', 'Vietnam', TRUE),
(2, '111 Đường long', 'Bến Nghé', 'Quận 11', 'TP.HCM', '110000', 'Vietnam', TRUE),

-- John Doe (user_id = 3)
(3, '789 Lý Thường Kiệt', 'Phường 7', 'Quận 10', 'TP.HCM', '700000', 'Vietnam', TRUE);

-- Nguyễn văn A (user_id=4)
(4, '123 Đường Lý Thường Kiệt', 'Phường 7', 'Quận 10', 'TP.HCM', '70000', TRUE);

-------------------------------------------------------medical_categories--------------------------------------------------------------------------------------------------------------
INSERT INTO medical_categories (name, description) VALUES
('Tim mạch', 'Chuyên khoa liên quan đến tim và mạch máu'),
('Hô hấp', 'Chuyên khoa về phổi và hệ hô hấp'),
('Tiêu hóa', 'Chuyên khoa về dạ dày, ruột, gan...'),
('Thần kinh', 'Chuyên khoa về não và hệ thần kinh'),
('Da liễu', 'Chuyên khoa về da, tóc và móng');


-------------------------------------------------------diseases--------------------------------------------------------------------------------------------------------------

INSERT INTO diseases (name, description, treatment_guidelines, category_id) VALUES
('Tăng huyết áp', 'Huyết áp cao mãn tính', 'Theo dõi huyết áp thường xuyên, dùng thuốc hạ áp', 1),
('Đột quỵ', 'Rối loạn tuần hoàn não nghiêm trọng', 'Can thiệp y tế khẩn cấp, phục hồi chức năng', 1),
('Hen suyễn', 'Bệnh mãn tính ảnh hưởng đến đường thở', 'Sử dụng thuốc giãn phế quản và kiểm soát dị ứng', 2),
('Viêm phổi', 'Nhiễm trùng phổi do vi khuẩn hoặc virus', 'Kháng sinh, nghỉ ngơi và điều trị hỗ trợ', 2),
('Viêm dạ dày', 'Viêm lớp niêm mạc dạ dày', 'Tránh thức ăn cay, dùng thuốc kháng acid', 3),
('Xơ gan', 'Tổn thương gan mạn tính', 'Kiểm soát nguyên nhân, chế độ ăn và theo dõi y tế', 3),
('Động kinh', 'Rối loạn thần kinh gây co giật lặp lại', 'Dùng thuốc chống động kinh, theo dõi điện não đồ', 4),
('Trầm cảm', 'Rối loạn tâm trạng kéo dài', 'Liệu pháp tâm lý và thuốc chống trầm cảm', 4),
('Viêm da cơ địa', 'Bệnh da mãn tính gây ngứa và phát ban', 'Dưỡng ẩm, thuốc bôi chống viêm', 5),
('Nấm da', 'Nhiễm trùng da do nấm', 'Thuốc kháng nấm dạng bôi hoặc uống', 5);


-------------------------------------------------------symptoms--------------------------------------------------------------------------------------------------------------
INSERT INTO symptoms (name, description) VALUES
('Đau đầu', 'Cảm giác đau ở vùng đầu hoặc cổ'),
('Khó thở', 'Khó khăn trong việc hít thở bình thường'),
('Buồn nôn', 'Cảm giác muốn nôn mửa'),
('Sốt', 'Nhiệt độ cơ thể cao hơn bình thường'),
('Tức ngực', 'Cảm giác đau hoặc áp lực ở ngực'),
('Mệt mỏi', 'Cảm giác kiệt sức, thiếu năng lượng'),
('Co giật', 'Chuyển động không kiểm soát của cơ'),
('Ngứa da', 'Cảm giác châm chích khiến muốn gãi'),
('Phát ban', 'Vùng da bị nổi mẩn đỏ hoặc sưng'),
('Chán ăn', 'Mất cảm giác thèm ăn');


-------------------------------------------------------liên kết diseases với symptoms--------------------------------------------------------------------------------------------------------------
-- Tăng huyết áp
INSERT INTO disease_symptoms (disease_id, symptom_id) VALUES
(1, 1), -- Đau đầu
(1, 5), -- Tức ngực
(1, 6); -- Mệt mỏi

-- Đột quỵ
INSERT INTO disease_symptoms (disease_id, symptom_id) VALUES
(2, 1),
(2, 6),
(2, 7); -- Co giật

-- Hen suyễn
INSERT INTO disease_symptoms (disease_id, symptom_id) VALUES
(3, 2),
(3, 5),
(3, 6);

-- Viêm phổi
INSERT INTO disease_symptoms (disease_id, symptom_id) VALUES
(4, 2),
(4, 4),
(4, 6);

-- Viêm dạ dày
INSERT INTO disease_symptoms (disease_id, symptom_id) VALUES
(5, 3),
(5, 4),
(5, 10); -- Chán ăn

-- Xơ gan
INSERT INTO disease_symptoms (disease_id, symptom_id) VALUES
(6, 6),
(6, 10);

-- Động kinh
INSERT INTO disease_symptoms (disease_id, symptom_id) VALUES
(7, 1),
(7, 7);

-- Trầm cảm
INSERT INTO disease_symptoms (disease_id, symptom_id) VALUES
(8, 6),
(8, 10);

-- Viêm da cơ địa
INSERT INTO disease_symptoms (disease_id, symptom_id) VALUES
(9, 8),
(9, 9);

-- Nấm da
INSERT INTO disease_symptoms (disease_id, symptom_id) VALUES
(10, 8),
(10, 9);

-------------------------------------------------------Lịch sử chiệu chứng của bênh nhân Nguyễn Văn A user_id = 4--------------------------------------------------------------------------------------------------------------
INSERT INTO user_symptom_history (user_id, symptom_id, record_date, notes) VALUES
(4, 4, '2025-05-18', 'Sốt cao 39 độ, kéo dài 2 ngày'),
(4, 1, '2025-05-18', 'Đau đầu âm ỉ vùng trán và sau gáy'),
(4, 2, '2025-05-19', 'Khó thở nhẹ, đặc biệt khi leo cầu thang'),
(4, 6, '2025-05-20', 'Cảm thấy mệt mỏi suốt cả ngày'),
(4, 5, '2025-05-21', 'Cảm giác tức ngực nhẹ khi hít sâu');


