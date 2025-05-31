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

