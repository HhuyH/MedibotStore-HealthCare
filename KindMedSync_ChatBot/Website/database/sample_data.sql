-- Sample data for testing appointment system

-- Insert sample clinics
INSERT INTO clinics (name, address, phone, email, description) VALUES
('Phòng khám Đa khoa QickMed', '123 Nguyễn Huệ, Quận 1, TP.HCM', '028-3822-1234', 'contact@qickmed.vn', 'Phòng khám đa khoa hiện đại với đội ngũ bác sĩ giàu kinh nghiệm'),
('Trung tâm Y tế QickMed Plus', '456 Lê Lợi, Quận 3, TP.HCM', '028-3933-5678', 'plus@qickmed.vn', 'Trung tâm y tế cao cấp với trang thiết bị hiện đại'),
('Phòng khám Chuyên khoa Tim mạch', '789 Hai Bà Trưng, Quận 1, TP.HCM', '028-3844-9012', 'cardio@qickmed.vn', 'Chuyên khoa tim mạch với các bác sĩ đầu ngành');

-- Insert sample specialties
INSERT INTO specialties (name, description) VALUES
('Nội khoa', 'Chuyên khoa điều trị các bệnh lý nội tạng'),
('Tim mạch', 'Chuyên khoa tim mạch và mạch máu'),
('Tiêu hóa', 'Chuyên khoa tiêu hóa và gan mật'),
('Thần kinh', 'Chuyên khoa thần kinh và tâm thần'),
('Da liễu', 'Chuyên khoa da liễu và thẩm mỹ'),
('Nhi khoa', 'Chuyên khoa nhi - sức khỏe trẻ em'),
('Sản phụ khoa', 'Chuyên khoa sản phụ khoa'),
('Răng hàm mặt', 'Chuyên khoa răng hàm mặt'),
('Mắt', 'Chuyên khoa mắt'),
('Tai mũi họng', 'Chuyên khoa tai mũi họng');

-- Insert sample users for doctors (assuming some users already exist)
-- You may need to adjust user_ids based on your existing users table

-- Sample doctor user accounts (role_id = 3 for doctors)
INSERT INTO users (username, email, password, role_id, status) VALUES
('dr_nguyenvana', 'nguyenvana@qickmed.vn', 'password123', 3, 'active'),
('dr_tranthib', 'tranthib@qickmed.vn', 'password123', 3, 'active'),
('dr_levantam', 'levantam@qickmed.vn', 'password123', 3, 'active'),
('dr_hoangthimai', 'hoangthimai@qickmed.vn', 'password123', 3, 'active'),
('dr_phamvanminh', 'phamvanminh@qickmed.vn', 'password123', 3, 'active');

-- Get the user_ids for the doctors (adjust these based on actual IDs)
SET @doctor1_user_id = LAST_INSERT_ID();
SET @doctor2_user_id = LAST_INSERT_ID() + 1;
SET @doctor3_user_id = LAST_INSERT_ID() + 2;
SET @doctor4_user_id = LAST_INSERT_ID() + 3;
SET @doctor5_user_id = LAST_INSERT_ID() + 4;

-- Insert doctor user info
INSERT INTO users_info (user_id, full_name, gender, date_of_birth, profile_picture) VALUES
(@doctor1_user_id, 'BS. Nguyễn Văn A', 'Nam', '1980-05-15', '/assets/images/doctor1.jpg'),
(@doctor2_user_id, 'BS. Trần Thị B', 'Nữ', '1985-08-22', '/assets/images/doctor2.jpg'),
(@doctor3_user_id, 'BS. Lê Văn Tâm', 'Nam', '1978-12-10', '/assets/images/doctor3.jpg'),
(@doctor4_user_id, 'BS. Hoàng Thị Mai', 'Nữ', '1982-03-18', '/assets/images/doctor4.jpg'),
(@doctor5_user_id, 'BS. Phạm Văn Minh', 'Nam', '1975-09-25', '/assets/images/doctor5.jpg');

-- Insert doctors
INSERT INTO doctors (user_id, specialty_id, clinic_id, biography) VALUES
(@doctor1_user_id, 1, 1, 'Bác sĩ nội khoa với 15 năm kinh nghiệm, từng công tác tại nhiều bệnh viện lớn'),
(@doctor2_user_id, 2, 3, 'Chuyên gia tim mạch hàng đầu, có nhiều công trình nghiên cứu quốc tế'),
(@doctor3_user_id, 3, 1, 'Bác sĩ tiêu hóa giàu kinh nghiệm, chuyên điều trị các bệnh lý phức tạp'),
(@doctor4_user_id, 6, 2, 'Bác sĩ nhi khoa tận tâm, được nhiều phụ huynh tin tưởng'),
(@doctor5_user_id, 4, 2, 'Chuyên gia thần kinh với nhiều năm kinh nghiệm điều trị');

-- Insert doctor schedules (working hours)
INSERT INTO doctor_schedules (doctor_id, clinic_id, day_of_week, start_time, end_time) VALUES
-- BS. Nguyễn Văn A (doctor_id = 1)
(1, 1, 'Monday', '08:00:00', '17:00:00'),
(1, 1, 'Tuesday', '08:00:00', '17:00:00'),
(1, 1, 'Wednesday', '08:00:00', '17:00:00'),
(1, 1, 'Thursday', '08:00:00', '17:00:00'),
(1, 1, 'Friday', '08:00:00', '17:00:00'),

-- BS. Trần Thị B (doctor_id = 2)
(2, 3, 'Monday', '09:00:00', '16:00:00'),
(2, 3, 'Wednesday', '09:00:00', '16:00:00'),
(2, 3, 'Friday', '09:00:00', '16:00:00'),
(2, 3, 'Saturday', '08:00:00', '12:00:00'),

-- BS. Lê Văn Tâm (doctor_id = 3)
(3, 1, 'Tuesday', '08:30:00', '17:30:00'),
(3, 1, 'Thursday', '08:30:00', '17:30:00'),
(3, 1, 'Saturday', '08:00:00', '12:00:00'),

-- BS. Hoàng Thị Mai (doctor_id = 4)
(4, 2, 'Monday', '08:00:00', '17:00:00'),
(4, 2, 'Tuesday', '08:00:00', '17:00:00'),
(4, 2, 'Wednesday', '08:00:00', '17:00:00'),
(4, 2, 'Thursday', '08:00:00', '17:00:00'),
(4, 2, 'Friday', '08:00:00', '17:00:00'),

-- BS. Phạm Văn Minh (doctor_id = 5)
(5, 2, 'Monday', '09:00:00', '16:00:00'),
(5, 2, 'Wednesday', '09:00:00', '16:00:00'),
(5, 2, 'Friday', '09:00:00', '16:00:00');

-- Sample appointments (assuming you have some patient users)
-- You'll need to replace user_ids with actual patient user IDs from your users table

-- INSERT INTO appointments (user_id, doctor_id, clinic_id, appointment_time, reason, status) VALUES
-- (2, 1, 1, '2024-12-25 09:00:00', 'Khám tổng quát', 'pending'),
-- (3, 2, 3, '2024-12-26 10:30:00', 'Khám tim mạch', 'confirmed'),
-- (4, 3, 1, '2024-12-27 14:00:00', 'Đau dạ dày', 'pending'),
-- (5, 4, 2, '2024-12-28 15:30:00', 'Khám cho bé', 'confirmed');

-- Note: Uncomment and adjust the above INSERT for appointments once you have patient users 