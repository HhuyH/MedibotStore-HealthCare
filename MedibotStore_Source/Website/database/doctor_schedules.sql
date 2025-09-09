-- Tạo bảng doctor_schedules nếu chưa tồn tại
CREATE TABLE IF NOT EXISTS `doctor_schedules` (
  `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `day_of_week` tinyint(1) NOT NULL COMMENT '1-7: Thứ 2 đến Chủ nhật',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`schedule_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `day_of_week` (`day_of_week`),
  CONSTRAINT `doctor_schedules_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng doctor_off_days nếu chưa tồn tại
CREATE TABLE IF NOT EXISTS `doctor_off_days` (
  `off_day_id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `off_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`off_day_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `off_date` (`off_date`),
  CONSTRAINT `doctor_off_days_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm các chỉ mục cho bảng appointments nếu chưa có
ALTER TABLE `appointments`
ADD INDEX IF NOT EXISTS `idx_doctor_date` (`doctor_id`, `appointment_date`),
ADD INDEX IF NOT EXISTS `idx_status` (`status`);

-- Thêm các chỉ mục cho bảng users_info nếu chưa có
ALTER TABLE `users_info`
ADD INDEX IF NOT EXISTS `idx_user_id` (`user_id`); 