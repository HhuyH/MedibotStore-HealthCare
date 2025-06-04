----------------------------------------------USERS----------------------------------------------------------------------------------------------------------------
INSERT INTO users (username, email, password_hash, role_id, created_at)
VALUES
('admin', 'admin@gmail.com',
 '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC',--id 1
 1, NOW()),

('huy', 'hoanhuy12@gmail.com',
 '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC',--id 2
 1, NOW()),

('dr.hanh', 'docter@example.com',
 '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC',--id 3
 2, NOW());

('nguyenvana', 'vana@example.com',
 '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC',--id 4
 3, NOW());

('linh', 'linh@gmail.com',
 '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC',--id 6
 2, NOW()), 

----------------------------------------------GUEST_USERS----------------------------------------------------------------------------------------------------------------
INSERT INTO guest_users (full_name, phone, email)
VALUES
('Nguyễn Văn A', '0909123456', 'nva@example.com'),
('Trần Thị B', '0911234567', 'ttb@example.com'),
('Lê Văn C', '0922345678', 'lvc@example.com');

----------------------------------------------USERS_info----------------------------------------------------------------------------------------------------------------
INSERT INTO users_info (user_id, full_name, gender, date_of_birth, phone)
VALUES
(1, 'Quản trị viên', 'Nam', '1990-01-01', '0123456789'),
(2, 'Huy', 'Nam', '1985-06-15','0999999999'),
(3, 'Dr.Hand', 'nữ', '2000-12-01', '0888888888');
(4, 'Nguyễn Văn A', 'Nam', '1995-08-15', '0901234567');
(6, 'Dr.Linh', 'Nữ', '1995-08-15', '0123466789');

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
('Cảm lạnh thông thường', 'Nhiễm virus nhẹ gây hắt hơi, sổ mũi', 'Nghỉ ngơi, uống nhiều nước, dùng thuốc giảm triệu chứng', 2),
('Đau đầu căng thẳng', 'Đau đầu do căng thẳng, stress hoặc sai tư thế', 'Nghỉ ngơi, thư giãn, thuốc giảm đau nếu cần', 4),
('Viêm họng cấp', 'Viêm vùng họng do virus hoặc vi khuẩn', 'Súc họng, uống nước ấm, thuốc kháng sinh nếu cần', 2),
('Nổi mề đay', 'Phản ứng dị ứng gây ngứa, nổi ban đỏ', 'Thuốc kháng histamin, tránh tác nhân gây dị ứng', 5),
('Táo bón chức năng', 'Khó đi tiêu do rối loạn tiêu hoá nhẹ', 'Ăn nhiều chất xơ, uống đủ nước, luyện tập thể dục', 3),
('Đau bụng kinh', 'Đau bụng khi hành kinh', 'Thuốc giảm đau, nghỉ ngơi, chườm ấm', 3),
('Lupus ban đỏ hệ thống', 'Bệnh tự miễn tấn công nhiều cơ quan', 'Dùng thuốc ức chế miễn dịch và theo dõi định kỳ', 4),
('Bạch cầu cấp', 'Ung thư máu tiến triển nhanh', 'Hóa trị, ghép tủy, chăm sóc đặc biệt', 4),
('Xơ cứng bì', 'Bệnh tự miễn hiếm gây dày cứng da và tổn thương nội tạng', 'Điều trị triệu chứng và ức chế miễn dịch', 5),
('Xơ nang', 'Rối loạn di truyền ảnh hưởng phổi và tiêu hóa', 'Điều trị hỗ trợ hô hấp, enzyme tiêu hóa', 2),
('U não ác tính', 'Khối u trong não gây triệu chứng thần kinh nghiêm trọng', 'Phẫu thuật, xạ trị, hóa trị', 4);
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
('Ho', 'Phản xạ đẩy không khí ra khỏi phổi để làm sạch đường hô hấp'),
('Hắt hơi', 'Phản xạ mạnh của mũi để đẩy chất gây kích ứng ra ngoài'),
('Chảy nước mũi', 'Dịch nhầy chảy ra từ mũi do viêm hoặc dị ứng'),
('Đau họng', 'Cảm giác đau hoặc rát ở vùng họng'),
('Khó nuốt', 'Cảm giác vướng hoặc đau khi nuốt thức ăn hoặc nước'),
('Đau bụng', 'Cảm giác khó chịu hoặc đau ở vùng bụng'),
('Tiêu chảy', 'Đi ngoài phân lỏng, thường xuyên'),
('Táo bón', 'Đi đại tiện khó khăn hoặc không thường xuyên'),
('Hoa mắt chóng mặt', 'Cảm giác quay cuồng hoặc mất thăng bằng'),
('Đổ mồ hôi nhiều', 'Ra mồ hôi quá mức, không do vận động'),
('Run tay chân', 'Chuyển động không tự chủ ở tay hoặc chân'),
('Khó ngủ', 'Gặp vấn đề khi ngủ hoặc ngủ không ngon giấc'),
('Thở gấp', 'Hơi thở nhanh, ngắn do thiếu oxy'),
('Tim đập nhanh', 'Nhịp tim tăng bất thường, có thể do lo âu hoặc bệnh'),
('Tê tay chân', 'Mất cảm giác hoặc cảm giác châm chích ở tay hoặc chân');
('Đau đầu', 'Cảm giác đau ở vùng đầu hoặc cổ'),
('Khó thở', 'Khó khăn trong việc hít thở bình thường'),
('Buồn nôn', 'Cảm giác muốn nôn mửa'),
('Sốt', 'Nhiệt độ cơ thể cao hơn bình thường'),
('Tức ngực', 'Cảm giác đau hoặc áp lực ở ngực'),
('Mệt mỏi', 'Cảm giác kiệt sức, thiếu năng lượng'),
('Co giật', 'Chuyển động không kiểm soát của cơ'),
('Ngứa da', 'Cảm giác châm chích khiến muốn gãi'),
('Phát ban', 'Vùng da bị nổi mẩn đỏ hoặc sưng'),
('Đau lưng', 'Cảm giác đau hoặc khó chịu ở vùng lưng'),
('Chán ăn', 'Mất cảm giác thèm ăn, không muốn ăn uống'),
('Buồn nôn', 'Cảm giác muốn nôn mửa'),
('Đau cơ', 'Cảm giác đau hoặc căng cứng cơ bắp'),
('Mất ngủ', 'Không thể ngủ hoặc ngủ không sâu giấc'),
('Hơi thở hôi', 'Có mùi khó chịu khi thở ra'),
('Nấc cụt', 'Hiện tượng thở ra đột ngột gây tiếng nấc'),
('Đau họng', 'Cảm giác đau hoặc rát ở vùng họng'),
('Chóng mặt', 'Cảm giác quay cuồng hoặc mất thăng bằng'),
('Mờ mắt', 'Giảm khả năng nhìn rõ hoặc bị mờ mắt'),
('Phù nề', 'Sưng lên do tích tụ dịch ở các mô'),
('Khó thở khi nằm', 'Cảm giác khó thở tăng lên khi nằm xuống'),


-------------------------------------------------------liên kết diseases với symptoms--------------------------------------------------------------------------------------------------------------

CREATE TABLE disease_symptom (
    disease_id INT,
    symptom_id INT,
    PRIMARY KEY (disease_id, symptom_id),
    FOREIGN KEY (disease_id) REFERENCES diseases(id),
    FOREIGN KEY (symptom_id) REFERENCES symptoms(id)
);

-- Cảm lạnh thông thường
INSERT INTO disease_symptom (disease_id, symptom_id) VALUES
(1, 1),  -- Ho
(1, 2),  -- Hắt hơi
(1, 3),  -- Chảy nước mũi
(1, 19), -- Sốt
(1, 21); -- Mệt mỏi

-- Viêm họng cấp
INSERT INTO disease_symptom (disease_id, symptom_id) VALUES
(3, 1),   -- Ho
(3, 4),   -- Đau họng
(3, 5),   -- Khó nuốt
(3, 19);  -- Sốt

-- Nổi mề đay
INSERT INTO disease_symptom (disease_id, symptom_id) VALUES
(4, 22),  -- Ngứa da
(4, 23);  -- Phát ban

-- Táo bón chức năng
INSERT INTO disease_symptom (disease_id, symptom_id) VALUES
(5, 8),   -- Táo bón
(5, 6),   -- Đau bụng
(5, 24);  -- Chán ăn

-- Đau bụng kinh
INSERT INTO disease_symptom (disease_id, symptom_id) VALUES
(6, 6),   -- Đau bụng
(6, 21);  -- Mệt mỏi

-- Lupus ban đỏ hệ thống
INSERT INTO disease_symptom (disease_id, symptom_id) VALUES
(7, 23),  -- Phát ban
(7, 21);  -- Mệt mỏi

-- Bạch cầu cấp
INSERT INTO disease_symptom (disease_id, symptom_id) VALUES
(8, 21),  -- Mệt mỏi
(8, 19),  -- Sốt
(8, 24);  -- Chán ăn

-- Xơ cứng bì
INSERT INTO disease_symptom (disease_id, symptom_id) VALUES
(9, 22),  -- Ngứa da
(9, 21);  -- Mệt mỏi

-- Xơ nang
INSERT INTO disease_symptom (disease_id, symptom_id) VALUES
(10, 1),  -- Ho
(10, 17), -- Khó thở
(10, 6);  -- Đau bụng

-- U não ác tính
INSERT INTO disease_symptom (disease_id, symptom_id) VALUES
(11, 16), -- Đau đầu
(11, 12), -- Khó ngủ
(11, 14); -- Tim đập nhanh

-- Tăng huyết áp
INSERT INTO disease_symptom (disease_id, symptom_id) VALUES
(12, 14), -- Tim đập nhanh
(12, 9);  -- Hoa mắt chóng mặt

-- Đột quỵ
INSERT INTO disease_symptom (disease_id, symptom_id) VALUES
(13, 14), -- Tim đập nhanh
(13, 15); -- Tê tay chân

-- Hen suyễn
INSERT INTO disease_symptom (disease_id, symptom_id) VALUES
(14, 17), -- Khó thở
(14, 1),  -- Ho
(14, 13); -- Thở gấp

-- Viêm phổi
INSERT INTO disease_symptom (disease_id, symptom_id) VALUES
(15, 1),   -- Ho
(15, 19),  -- Sốt
(15, 17);  -- Khó thở

-- Viêm dạ dày
INSERT INTO disease_symptom (disease_id, symptom_id) VALUES
(16, 6),   -- Đau bụng
(16, 18),  -- Buồn nôn
(16, 24);  -- Chán ăn

-- Xơ gan
INSERT INTO disease_symptom (disease_id, symptom_id) VALUES
(17, 6),   -- Đau bụng
(17, 21);  -- Mệt mỏi

-- Động kinh
INSERT INTO disease_symptom (disease_id, symptom_id) VALUES
(18, 7),   -- Co giật
(18, 15);  -- Tê tay chân

-- Trầm cảm
INSERT INTO disease_symptom (disease_id, symptom_id) VALUES
(19, 12), -- Khó ngủ
(19, 21); -- Mệt mỏi

-- Viêm da cơ địa
INSERT INTO disease_symptom (disease_id, symptom_id) VALUES
(20, 22), -- Ngứa da
(20, 23); -- Phát ban

-- Nấm da
INSERT INTO disease_symptom (disease_id, symptom_id) VALUES
(21, 22), -- Ngứa da
(21, 23); -- Phát ban




-------------------------------------------------------Lịch sử chiệu chứng của bênh nhân Nguyễn Văn A user_id = 4--------------------------------------------------------------------------------------------------------------
INSERT INTO user_symptom_history (user_id, symptom_id, record_date, notes) VALUES
(4, 19, '2025-05-18', 'Sốt cao 39 độ, kéo dài 2 ngày'),
(4, 16, '2025-05-18', 'Đau đầu âm ỉ vùng trán và sau gáy'),
(4, 17, '2025-05-19', 'Khó thở nhẹ, đặc biệt khi leo cầu thang'),
(4, 21, '2025-05-20', 'Cảm thấy mệt mỏi suốt cả ngày'),
(4, 20, '2025-05-21', 'Cảm giác tức ngực nhẹ khi hít sâu');

-------------------------------------------------------Phòng khám--------------------------------------------------------------------------------------------------------------
INSERT INTO clinics (name, address, phone, email, description) VALUES
('Phòng khám Đa khoa Hòa Hảo', '254 Hòa Hảo, Quận 10, TP.HCM', '02838553085', 'hoahao@example.com', 'Phòng khám tư nhân uy tín với nhiều chuyên khoa.'),
('Bệnh viện Chợ Rẫy', '201B Nguyễn Chí Thanh, Quận 5, TP.HCM', '02838554137', 'choray@hospital.vn', 'Bệnh viện tuyến trung ương chuyên điều trị các ca nặng.'),
('Phòng khám Quốc tế Victoria Healthcare', '79 Điện Biên Phủ, Quận 1, TP.HCM', '02839101717', 'info@victoriavn.com', 'Dịch vụ khám chữa bệnh theo tiêu chuẩn quốc tế.'),
('Bệnh viện Đại học Y Dược', '215 Hồng Bàng, Quận 5, TP.HCM', '02838552307', 'contact@umc.edu.vn', 'Bệnh viện trực thuộc Đại học Y Dược TP.HCM.'),
('Phòng khám đa khoa Pasteur', '27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '02838232299', 'pasteurclinic@vnmail.com', 'Chuyên nội tổng quát, tim mạch, tiêu hóa.');

---------------------------------------------------------------------------------Khoa--------------------------------------------------------------------------------------------------------------
INSERT INTO specialties (name, description) VALUES
('Nội khoa', 'Chẩn đoán và điều trị không phẫu thuật các bệnh lý nội tạng.'),
('Ngoại khoa', 'Chẩn đoán và điều trị bệnh thông qua phẫu thuật.'),
('Tai - Mũi - Họng', 'Khám và điều trị các bệnh lý về tai, mũi và họng.'),
('Tim mạch', 'Chuyên điều trị bệnh về tim và hệ tuần hoàn.'),
('Nhi khoa', 'Chăm sóc và điều trị cho trẻ em từ sơ sinh đến 15 tuổi.'),
('Da liễu', 'Chẩn đoán và điều trị các bệnh về da, tóc và móng.'),
('Tiêu hóa', 'Chuyên về hệ tiêu hóa như dạ dày, gan, ruột.'),
('Thần kinh', 'Khám và điều trị các bệnh về hệ thần kinh trung ương và ngoại biên.');

---------------------------------------------------------------------------------Bác sĩ---------------------------------------------------------------------------------------------------------------------
-- user_id = 3 là bác sĩ Nội khoa tại Phòng khám Đa khoa Hòa Hảo
-- user_id = 6 là bác sĩ Tim mạch tại Bệnh viện Chợ Rẫy

INSERT INTO doctors (user_id, specialty_id, clinic_id, biography)
VALUES
(3, 1, 1, 'Bác sĩ Nội khoa với hơn 10 năm kinh nghiệm trong điều trị tiểu đường, huyết áp. Tốt nghiệp Đại học Y Dược TP.HCM.'),
(6, 4, 2, 'Bác sĩ Tim mạch từng công tác tại Viện Tim TP.HCM. Có bằng Thạc sĩ Y khoa từ Đại học Paris, Pháp.');

---------------------------------------------------------------------------------Lịch làm việc bác sĩ---------------------------------------------------------------------------------------------------------------------
-- Lịch bác sĩ Nội khoa (doctor_id = 1) tại phòng khám 1
INSERT INTO doctor_schedules (doctor_id, clinic_id, day_of_week, start_time, end_time)
VALUES
(1, 1, 'Monday', '08:00:00', '12:00:00'),
(1, 1, 'Wednesday', '08:00:00', '12:00:00'),
(1, 1, 'Friday', '13:30:00', '17:30:00');

-- Lịch bác sĩ Tim mạch (doctor_id = 2) tại phòng khám 2
INSERT INTO doctor_schedules (doctor_id, clinic_id, day_of_week, start_time, end_time)
VALUES
(2, 2, 'Tuesday', '09:00:00', '12:00:00'),
(2, 2, 'Thursday', '14:00:00', '18:00:00'),
(2, 2, 'Saturday', '08:30:00', '11:30:00');

---------------------------------------------------------------------------------Đặt lịch khám---------------------------------------------------------------------------------------------------------------------

-- user_id = 4 đặt khám bác sĩ Nội khoa (user_id = 3, doctor_id = 1) tại Phòng khám Đa khoa Hòa Hảo
INSERT INTO appointments (user_id, doctor_id, clinic_id, appointment_time, reason, status)
VALUES 
(4, 1, 1, '2025-05-28 09:00:00', 'Khám huyết áp và mệt mỏi kéo dài', 'confirmed'),
(4, 1, 1, '2025-06-01 14:30:00', 'Theo dõi tiểu đường định kỳ', 'pending');

-- guest_id = 1 khám Nội khoa (doctor_id = 1) tại Phòng khám Đa khoa Hòa Hảo
-- guest_id = 2 khám Tim mạch (doctor_id = 2) tại Bệnh viện Chợ Rẫy
-- guest_id = 3 khám Tim mạch (doctor_id = 2) tại Bệnh viện Chợ Rẫy

INSERT INTO appointments (guest_id, doctor_id, clinic_id, appointment_time, reason, status)
VALUES
(1, 1, 1, '2025-05-25 10:00:00', 'Đau đầu và cao huyết áp gần đây', 'confirmed'),
(2, 2, 2, '2025-05-27 08:00:00', 'Khó thở, nghi ngờ bệnh tim', 'pending'),
(3, 2, 2, '2025-05-29 15:00:00', 'Đặt lịch kiểm tra tim định kỳ', 'canceled');

---------------------------------------------------------------------------------Đơn thuốc---------------------------------------------------------------------------------------------------------------------

-- Đơn thuốc cho lịch hẹn của user_id = 4 (appointment_id = 1 và 2)
INSERT INTO prescriptions (appointment_id, prescribed_date, medications, notes)
VALUES
(1, '2025-05-28', '[
  {"name": "Thuốc hạ áp Amlodipine", "dosage": "5mg", "frequency": "1 viên/ngày"},
  {"name": "Paracetamol", "dosage": "500mg", "frequency": "2 viên/ngày khi đau đầu"}
]', 'Uống vào buổi sáng sau ăn. Tránh dùng với rượu bia.'),

(2, '2025-06-01', '[
  {"name": "Metformin", "dosage": "500mg", "frequency": "2 lần/ngày"},
  {"name": "Glimepiride", "dosage": "2mg", "frequency": "1 lần/ngày trước ăn sáng"}
]', 'Kiểm tra đường huyết trước mỗi lần dùng thuốc.');

-- Đơn thuốc cho khách vãng lai guest_id = 1 (appointment_id = 3)
INSERT INTO prescriptions (appointment_id, prescribed_date, medications, notes)
VALUES
(3, '2025-05-25', '[
  {"name": "Losartan", "dosage": "50mg", "frequency": "1 viên mỗi sáng"},
  {"name": "Vitamin B1", "dosage": "100mg", "frequency": "1 viên/ngày"}
]', 'Tái khám sau 1 tuần nếu triệu chứng không giảm.');

---------------------------------------------------------------------------------Ghi chú của bác sĩ---------------------------------------------------------------------------------------------------------------------

-- Ghi chú khám của bác sĩ cho các lịch hẹn của user_id = 4
INSERT INTO medical_records (appointment_id, diagnosis, recommendations)
VALUES
(1, 'Tăng huyết áp giai đoạn 1', 'Cần điều chỉnh chế độ ăn và tập thể dục. Uống thuốc đều đặn.'),
(2, 'Tiểu đường tuýp 2', 'Kiểm tra HbA1c 3 tháng/lần. Hạn chế đường và tinh bột.');

-- Ghi chú khám cho khách guest_id = 1
INSERT INTO medical_records (appointment_id, diagnosis, recommendations)
VALUES
(3, 'Cao huyết áp do căng thẳng', 'Nghỉ ngơi hợp lý, tránh thức khuya. Theo dõi huyết áp hàng ngày.');

----------------------------------------------------------------4. Thương mại điện tử-------------------------------------------------------------------------------

--🗂️ product_categories: Danh mục sản phẩm
INSERT INTO product_categories (name, description) VALUES
('Thuốc điều trị', 'Các loại thuốc dùng để điều trị bệnh lý.'),
('Thực phẩm chức năng', 'Sản phẩm hỗ trợ tăng cường sức khỏe.'),
('Thiết bị y tế', 'Các thiết bị và dụng cụ y tế sử dụng trong chẩn đoán và điều trị.'),
('Vật tư tiêu hao', 'Găng tay, khẩu trang, bông băng,... sử dụng một lần.');

--📦 products: Danh sách sản phẩm
INSERT INTO products (category_id, name, description, price, stock, image_url)
VALUES
(1, 'Paracetamol 500mg', 'Thuốc hạ sốt, giảm đau thường dùng.', 15000, 100, 'https://example.com/images/paracetamol.jpg'),
(1, 'Amoxicillin 500mg', 'Kháng sinh phổ rộng nhóm penicillin.', 28000, 60, 'https://example.com/images/amoxicillin.jpg'),
(2, 'Vitamin C 1000mg', 'Hỗ trợ tăng cường đề kháng.', 50000, 200, 'https://example.com/images/vitaminC.jpg'),
(3, 'Máy đo huyết áp điện tử', 'Thiết bị đo huyết áp tại nhà.', 650000, 15, 'https://example.com/images/blood_pressure_monitor.jpg'),
(4, 'Khẩu trang y tế 4 lớp', 'Hộp 50 cái, đạt chuẩn kháng khuẩn.', 40000, 500, 'https://example.com/images/face_mask.jpg');

------------------------------------------------------------💊 medicines: Thông tin chi tiết thuốc (chỉ áp dụng với sản phẩm là thuốc)------------------------------------------------------------------------------------
INSERT INTO medicines (medicine_id, active_ingredient, dosage_form, unit, usage_instructions)
VALUES
(1, 'Paracetamol', 'Viên nén', 'viên', 'Uống 1–2 viên mỗi 4–6 giờ nếu cần. Không dùng quá 8 viên/ngày.'),
(2, 'Amoxicillin', 'Viên nang', 'viên', 'Uống 1 viên mỗi 8 giờ, duy trì trong 5–7 ngày.');

--------------------------------------------------- prescription_products: Sản phẩm thực tế được kê trong đơn thuốc------------------------------------------------------------------------------------
-- Đơn thuốc 1 (của user_id = 4, appointment_id = 1)
INSERT INTO prescription_products (prescription_id, product_id, quantity, dosage, usage_time)
VALUES
(1, 1, 10, '500mg', '2 viên/ngày khi đau đầu'),    -- Paracetamol
(1, NULL, 7, '5mg', '1 viên/ngày');                -- Amlodipine chưa có trong products, có thể là thuốc ngoài danh mục

-- Đơn thuốc 2 (của user_id = 4, appointment_id = 2)
INSERT INTO prescription_products (prescription_id, product_id, quantity, dosage, usage_time)
VALUES
(2, NULL, 14, '500mg', '2 lần/ngày'),              -- Metformin, không có trong bảng `products`
(2, NULL, 7, '2mg', '1 lần/ngày trước ăn sáng');   -- Glimepiride, cũng không có trong bảng `products`

-- Đơn thuốc 3 (của guest_id = 1, appointment_id = 3)
INSERT INTO prescription_products (prescription_id, product_id, quantity, dosage, usage_time)
VALUES
(3, NULL, 7, '50mg', '1 viên mỗi sáng'),           -- Losartan
(3, NULL, 7, '100mg', '1 viên/ngày');              -- Vitamin B1


-------------------------------------------------------------------------------------- product_reviews------------------------------------------------------------------------------------
-- Huy (user_id = 2) đánh giá Paracetamol (product_id = 1)
INSERT INTO product_reviews (product_id, user_id, rating, comment)
VALUES
(1, 2, 5, 'Thuốc giảm đau hiệu quả, ít tác dụng phụ.'),

-- Huy (user_id = 2) đánh giá Amoxicillin (product_id = 2)
(2, 2, 4, 'Tốt nhưng gây buồn nôn nhẹ.'),

-- Admin (user_id = 1) đánh giá máy đo huyết áp (product_id = 4)
(4, 1, 5, 'Dễ sử dụng và rất chính xác.'),

-- Người dùng "dr.hanh" (user_id = 3) đánh giá Vitamin C (product_id = 3)
(3, 3, 4, 'Khá ổn để tăng sức đề kháng. Đóng gói đẹp.');

INSERT INTO chatbot_knowledge_base (intent, question, answer, category)
VALUES
-- Hành chính
('ask_working_hours', 'Bệnh viện làm việc vào thời gian nào?', 'Bệnh viện làm việc từ 7h00 đến 17h00 từ thứ 2 đến thứ 7.', 'Thông tin chung'),
('ask_contact_info', 'Tôi có thể liên hệ bệnh viện qua số điện thoại nào?', 'Bạn có thể gọi đến số 1900-1234 để được hỗ trợ.', 'Thông tin chung'),
('ask_location', 'Địa chỉ bệnh viện là gì?', 'Bệnh viện nằm tại số 123 Đường Sức Khỏe, Quận 10, TP.HCM.', 'Thông tin chung'),
('ask_services', 'Bệnh viện có những dịch vụ gì?', 'Chúng tôi cung cấp các dịch vụ khám bệnh, xét nghiệm, chẩn đoán hình ảnh và điều trị nội trú.', 'Thông tin chung'),
-- Đặt lịch hẹn
('booking_procedure', 'Làm sao để đặt lịch khám?', 'Bạn có thể đặt lịch khám trực tuyến qua website hoặc gọi đến số tổng đài 1900-1234.', 'Đặt lịch'),
('booking_available_slots', 'Tôi muốn biết lịch khám của bác sĩ A vào tuần tới?', 'Bạn có thể kiểm tra lịch khám của bác sĩ A trên trang web hoặc ứng dụng của bệnh viện.', 'Đặt lịch'),
('booking_cancellation', 'Tôi muốn huỷ lịch hẹn đã đặt thì làm sao?', 'Bạn có thể huỷ lịch hẹn qua tài khoản cá nhân hoặc liên hệ tổng đài để được hỗ trợ.', 'Đặt lịch'),
('booking_confirmation', 'Tôi đã đặt lịch khám nhưng chưa nhận được xác nhận, phải làm sao?', 'Bạn có thể kiểm tra lại trong mục "Lịch sử đặt lịch" trên tài khoản của mình hoặc liên hệ tổng đài để được hỗ trợ.', 'Đặt lịch'),
('reschedule_booking', 'Tôi muốn thay đổi lịch hẹn đã đặt thì làm sao?', 'Bạn có thể thay đổi lịch hẹn qua tài khoản cá nhân hoặc liên hệ tổng đài để được hỗ trợ.', 'Đặt lịch'),
('cancel_booking', 'Tôi muốn huỷ lịch hẹn thì làm sao?', 'Bạn có thể huỷ lịch qua tài khoản cá nhân hoặc liên hệ tổng đài để được hỗ trợ.', 'Đặt lịch'),

-- Y tế / chuyên môn
('symptom_analysis', 'Tôi bị sốt, mệt mỏi và ho, có thể là bệnh gì?', 
 'Triệu chứng như vậy có thể do cảm lạnh, viêm họng, hoặc dị ứng thời tiết gây ra. Bạn nên nghỉ ngơi, uống nhiều nước và theo dõi kỹ. Nếu không đỡ thì đi khám nha.', 
 'Triệu chứng chung'),

('symptom_analysis', 'Tôi bị đau đầu và chóng mặt, có thể là bệnh gì?', 
 'Đau đầu và chóng mặt có thể do căng thẳng, thiếu ngủ, hoặc các vấn đề về huyết áp. Nếu cảm thấy nghiêm trọng, bạn nên đi khám để được kiểm tra kỹ hơn.', 
 'Triệu chứng chung'),

('symptom_analysis', 'Tôi bị khó thở và tức ngực, có thể là bệnh gì?', 
 'Khó thở và tức ngực có thể liên quan đến nhiều bệnh như hen suyễn, viêm phổi hoặc các bệnh tim mạch. Bạn nên đi khám để được chẩn đoán chính xác.', 
 'Triệu chứng chung'),

('symptom_analysis', 'Tôi bị ngứa da và phát ban, có thể là do bệnh gì?', 
 'Ngứa da và phát ban có thể do dị ứng, viêm da cơ địa hoặc nhiễm nấm da. Nên tránh tiếp xúc với các chất gây kích ứng và đi khám nếu triệu chứng kéo dài.', 
 'Triệu chứng chung'),

('symptom_analysis', 'Tôi bị buồn nôn và chán ăn, có thể do bệnh gì?', 
 'Buồn nôn và chán ăn có thể là dấu hiệu của nhiều vấn đề như rối loạn tiêu hóa, stress hoặc nhiễm trùng nhẹ. Nếu triệu chứng kéo dài, bạn nên đến bác sĩ để kiểm tra.', 
 'Triệu chứng chung');
('disease_info', 'Bệnh tiểu đường có những triệu chứng gì?', 'Các triệu chứng bao gồm khát nước nhiều, đi tiểu thường xuyên, mệt mỏi và giảm cân không rõ nguyên nhân.', 'Thông tin bệnh'),
('medicine_usage', 'Tôi nên uống thuốc hạ sốt như thế nào?', 'Bạn nên uống thuốc theo chỉ định bác sĩ. Thông thường, thuốc hạ sốt được dùng khi nhiệt độ trên 38.5°C.', 'Hướng dẫn dùng thuốc'),
('disease_info', 'Bệnh tiểu đường có những triệu chứng gì?', 'Các triệu chứng bao gồm khát nước nhiều, đi tiểu thường xuyên, mệt mỏi và giảm cân không rõ nguyên nhân.', 'Thông tin bệnh'),
('medicine_usage', 'Tôi nên uống thuốc hạ sốt như thế nào?', 'Bạn nên uống thuốc theo chỉ định bác sĩ. Thông thường, thuốc hạ sốt được dùng khi nhiệt độ trên 38.5°C.', 'Hướng dẫn dùng thuốc'),

-- Hỗ trợ kỹ thuật
('account_help', 'Tôi quên mật khẩu đăng nhập thì phải làm sao?', 'Bạn có thể sử dụng chức năng "Quên mật khẩu" trên trang đăng nhập để đặt lại mật khẩu.', 'Hỗ trợ tài khoản'),
('app_issue', 'Ứng dụng bị lỗi khi tôi mở lên, phải làm sao?', 'Bạn hãy thử khởi động lại ứng dụng hoặc cập nhật lên phiên bản mới nhất. Nếu vẫn gặp lỗi, vui lòng liên hệ bộ phận hỗ trợ.', 'Hỗ trợ kỹ thuật'),
('payment_issue', 'Tôi không thể thanh toán đơn thuốc, phải làm sao?', 'Bạn hãy kiểm tra lại thông tin thẻ hoặc tài khoản ngân hàng. Nếu vẫn không thanh toán được, vui lòng liên hệ bộ phận hỗ trợ.', 'Hỗ trợ thanh toán');

----------------------------------------------------------------5. Dịch vụ y tế-------------------------------------------------------------------------------

----------------------------------------------------------------Dữ liệu mẫu cho categories--------------------------------------------------------------------------------------------------------------------------
INSERT INTO service_categories (name, slug, icon, description) VALUES
('Khám Tổng Quát', 'kham-tong-quat', 'fas fa-stethoscope', 'Dịch vụ khám sức khỏe tổng quát và tầm soát bệnh'),
('Tim Mạch', 'tim-mach', 'fas fa-heartbeat', 'Chẩn đoán và điều trị các bệnh lý tim mạch'),
('Tiêu Hóa', 'tieu-hoa', 'fas fa-prescription-bottle-alt', 'Điều trị các bệnh về đường tiêu hóa'),
('Thần Kinh', 'than-kinh', 'fas fa-brain', 'Điều trị các bệnh lý thần kinh'),
('Chấn Thương Chỉnh Hình', 'chan-thuong-chinh-hinh', 'fas fa-bone', 'Điều trị chấn thương và bệnh lý xương khớp'),
('Cấp Cứu', 'cap-cuu', 'fas fa-ambulance', 'Dịch vụ cấp cứu 24/7');

----------------------------------------------------------------Dữ liệu mẫu cho services--------------------------------------------------------------------------------------------------------------------------
INSERT INTO services (category_id, name, slug, short_description, price_from, price_to, is_featured, is_emergency) VALUES
(1, 'Khám Tổng Quát', 'kham-tong-quat', 'Khám sức khỏe định kỳ và tầm soát các bệnh lý thường gặp', 200000, 500000, FALSE, FALSE),
(2, 'Khám Tim Mạch', 'kham-tim-mach', 'Chẩn đoán và điều trị các bệnh lý tim mạch với trang thiết bị hiện đại', 300000, 2000000, TRUE, FALSE),
(3, 'Khám Tiêu Hóa', 'kham-tieu-hoa', 'Chẩn đoán và điều trị các bệnh lý về đường tiêu hóa, gan mật', 250000, 1500000, FALSE, FALSE),
(6, 'Dịch Vụ Cấp Cứu', 'dich-vu-cap-cuu', 'Dịch vụ cấp cứu 24/7 với đội ngũ y bác sĩ luôn sẵn sàng', NULL, NULL, FALSE, TRUE);

----------------------------------------------------------------Dữ liệu mẫu cho service_features----------------------------------------------------------------
INSERT INTO service_features (service_id, feature_name) VALUES
(1, 'Khám lâm sàng toàn diện'),
(1, 'Xét nghiệm máu cơ bản'),
(1, 'Đo huyết áp, nhịp tim'),
(1, 'Tư vấn dinh dưỡng'),
(2, 'Siêu âm tim'),
(2, 'Điện tim'),
(2, 'Holter 24h'),
(2, 'Thăm dò chức năng tim');

----------------------------------------------------------------Dữ liệu mẫu cho service_packages----------------------------------------------------------------
INSERT INTO service_packages (name, slug, description, price, duration, is_featured) VALUES
('Gói Cơ Bản', 'goi-co-ban', 'Gói khám sức khỏe cơ bản', 1500000, '/lần', FALSE),
('Gói Nâng Cao', 'goi-nang-cao', 'Gói khám sức khỏe nâng cao', 3500000, '/lần', TRUE),
('Gói Cao Cấp', 'goi-cao-cap', 'Gói khám sức khỏe cao cấp', 6500000, '/lần', FALSE);

----------------------------------------------------------------Dữ liệu mẫu cho --------------------------------------------------------------------------------------------------------------------------------
INSERT INTO package_features (package_id, feature_name) VALUES
(1, 'Khám lâm sàng tổng quát'),
(1, 'Xét nghiệm máu cơ bản'),
(1, 'Xét nghiệm nước tiểu'),
(1, 'X-quang phổi'),
(1, 'Điện tim'),
(1, 'Tư vấn kết quả'),
(2, 'Tất cả gói cơ bản'),
(2, 'Siêu âm bụng tổng quát'),
(2, 'Siêu âm tim');
