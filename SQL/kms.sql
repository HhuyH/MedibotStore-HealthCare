-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 24, 2025 at 09:31 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kms`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_all_users_by_role` (IN `input_role_id` INT)   BEGIN
    SELECT 
        u.user_id,
        u.username,
        u.email,
        u.phone_number,
        r.role_name,
        ui.full_name,
        ui.gender,
        ui.date_of_birth,
        ua.address_line,
        ua.ward,
        ua.district,
        ua.city,
        ua.country,
        u.created_at
    FROM users u
    LEFT JOIN users_info ui ON u.user_id = ui.user_id
    LEFT JOIN roles r ON u.role_id = r.role_id
    LEFT JOIN user_addresses ua ON u.user_id = ua.user_id AND ua.is_default = TRUE
    WHERE (input_role_id = 0 OR u.role_id = input_role_id)
    ORDER BY u.user_id DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_user_addresses` (IN `in_user_id` INT)   BEGIN
    SELECT 
        a.id AS `Địa chỉ ID`,
        a.address_line AS `Địa chỉ`,
        a.ward AS `Phường/Xã`,
        a.district AS `Quận/Huyện`,
        a.city AS `Thành phố`,
        a.postal_code AS `Mã bưu chính`,
        a.country AS `Quốc gia`,
        a.is_default AS `Là mặc định`,
        a.created_at AS `Ngày tạo`,
        a.updated_at AS `Ngày cập nhật`
    FROM user_addresses a
    WHERE a.user_id = in_user_id
    ORDER BY a.is_default DESC, a.updated_at DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_user_details` (IN `in_user_id` INT)  COMMENT '--CALL get_user_details(2);' BEGIN
    SELECT 
        u.user_id AS `User ID`,
        u.username AS `Username`,
        u.email AS `Email`,
        u.phone_number AS `Số điện thoại`,
        r.role_name AS `Vai trò`,
        ui.full_name AS `Họ tên`,
        ui.gender AS `Giới tính`,
        ui.date_of_birth AS `Ngày sinh`,
        ui.profile_picture AS `Ảnh đại diện`,
        a.address_line AS `Địa chỉ`,
        a.ward AS `Phường/Xã`,
        a.district AS `Quận/Huyện`,
        a.city AS `Thành phố`,
        a.country AS `Quốc gia`,
        a.is_default AS `Là địa chỉ mặc định`
    FROM users u
    LEFT JOIN users_info ui ON u.user_id = ui.user_id
    LEFT JOIN roles r ON u.role_id = r.role_id
    LEFT JOIN user_addresses a ON u.user_id = a.user_id AND a.is_default = TRUE
    WHERE u.user_id = in_user_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_user_symptom_history` (IN `in_user_id` INT)   BEGIN
    SELECT 
        u.full_name AS `Họ tên`,
        h.notes AS `Ghi chú`,
        s.name AS `Triệu chứng`
    FROM user_symptom_history h
    JOIN symptoms s ON h.symptom_id = s.symptom_id
    JOIN users_info u ON u.user_id = h.user_id
    WHERE h.user_id = in_user_id
    ORDER BY h.record_date;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `login_user` (IN `input_username_or_email` VARCHAR(100), IN `input_password_hash` VARCHAR(255))   BEGIN
    DECLARE user_id_result INT;
    DECLARE role_name_result VARCHAR(50);
    
    -- Truy vấn người dùng có tồn tại không
    SELECT u.user_id, r.role_name
    INTO user_id_result, role_name_result
    FROM users u
    JOIN roles r ON u.role_id = r.role_id
    WHERE (u.username = input_username_or_email OR u.email = input_username_or_email)
      AND u.password_hash = input_password_hash
    LIMIT 1;

    -- Nếu tìm được thì trả kết quả
    IF user_id_result IS NOT NULL THEN
        SELECT TRUE AS success, user_id_result AS user_id, role_name_result AS role;
    ELSE
        SELECT FALSE AS success, NULL AS user_id, NULL AS role;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_get_patient_history` (IN `p_user_id` INT)   BEGIN
    SELECT a.appointment_id, a.appointment_time, a.reason, 
           mr.diagnosis, mr.recommendations,
           p.medications, p.notes
    FROM appointments a
    LEFT JOIN medical_records mr ON a.appointment_id = mr.appointment_id
    LEFT JOIN prescriptions p ON a.appointment_id = p.appointment_id
    WHERE a.user_id = p_user_id OR a.guest_id IN (
        SELECT guest_id FROM guest_users WHERE phone = (
            SELECT phone FROM users WHERE user_id = p_user_id
        )
    )
    ORDER BY a.appointment_time DESC;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `guest_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) NOT NULL,
  `clinic_id` int(11) DEFAULT NULL,
  `appointment_time` datetime NOT NULL,
  `reason` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `user_id`, `guest_id`, `doctor_id`, `clinic_id`, `appointment_time`, `reason`, `status`, `created_at`, `updated_at`) VALUES
(1, 4, NULL, 1, 1, '2025-05-28 09:00:00', 'Khám huyết áp và mệt mỏi kéo dài', 'confirmed', '2025-05-24 07:15:05', '2025-05-24 14:15:05'),
(2, 4, NULL, 1, 1, '2025-06-01 14:30:00', 'Theo dõi tiểu đường định kỳ', 'pending', '2025-05-24 07:15:05', '2025-05-24 14:15:05'),
(3, NULL, 1, 1, 1, '2025-05-25 10:00:00', 'Đau đầu và cao huyết áp gần đây', 'confirmed', '2025-05-24 07:15:05', '2025-05-24 14:15:05'),
(4, NULL, 2, 2, 2, '2025-05-27 08:00:00', 'Khó thở, nghi ngờ bệnh tim', 'pending', '2025-05-24 07:15:05', '2025-05-24 14:15:05'),
(5, NULL, 3, 2, 2, '2025-05-29 15:00:00', 'Đặt lịch kiểm tra tim định kỳ', 'canceled', '2025-05-24 07:15:05', '2025-05-24 14:15:05');

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_knowledge_base`
--

CREATE TABLE `chatbot_knowledge_base` (
  `kb_id` int(11) NOT NULL,
  `intent` varchar(100) DEFAULT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_logs`
--

CREATE TABLE `chat_logs` (
  `chat_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `guest_id` int(11) DEFAULT NULL,
  `intent` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `sender` enum('user','bot') NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `clinics`
--

CREATE TABLE `clinics` (
  `clinic_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clinics`
--

INSERT INTO `clinics` (`clinic_id`, `name`, `address`, `phone`, `email`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Phòng khám Đa khoa Hòa Hảo', '254 Hòa Hảo, Quận 10, TP.HCM', '02838553085', 'hoahao@example.com', 'Phòng khám tư nhân uy tín với nhiều chuyên khoa.', '2025-05-24 06:11:09', '2025-05-24 13:11:09'),
(2, 'Bệnh viện Chợ Rẫy', '201B Nguyễn Chí Thanh, Quận 5, TP.HCM', '02838554137', 'choray@hospital.vn', 'Bệnh viện tuyến trung ương chuyên điều trị các ca nặng.', '2025-05-24 06:11:09', '2025-05-24 13:11:09'),
(3, 'Phòng khám Quốc tế Victoria Healthcare', '79 Điện Biên Phủ, Quận 1, TP.HCM', '02839101717', 'info@victoriavn.com', 'Dịch vụ khám chữa bệnh theo tiêu chuẩn quốc tế.', '2025-05-24 06:11:09', '2025-05-24 13:11:09'),
(4, 'Bệnh viện Đại học Y Dược', '215 Hồng Bàng, Quận 5, TP.HCM', '02838552307', 'contact@umc.edu.vn', 'Bệnh viện trực thuộc Đại học Y Dược TP.HCM.', '2025-05-24 06:11:09', '2025-05-24 13:11:09'),
(5, 'Phòng khám đa khoa Pasteur', '27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '02838232299', 'pasteurclinic@vnmail.com', 'Chuyên nội tổng quát, tim mạch, tiêu hóa.', '2025-05-24 06:11:09', '2025-05-24 13:11:09');

-- --------------------------------------------------------

--
-- Table structure for table `diseases`
--

CREATE TABLE `diseases` (
  `disease_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `treatment_guidelines` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `diseases`
--

INSERT INTO `diseases` (`disease_id`, `name`, `description`, `treatment_guidelines`, `category_id`, `created_at`, `updated_at`) VALUES
(1, 'Tăng huyết áp', 'Huyết áp cao mãn tính', 'Theo dõi huyết áp thường xuyên, dùng thuốc hạ áp', 1, '2025-05-22 08:31:54', '2025-05-22 15:31:54'),
(2, 'Đột quỵ', 'Rối loạn tuần hoàn não nghiêm trọng', 'Can thiệp y tế khẩn cấp, phục hồi chức năng', 1, '2025-05-22 08:31:54', '2025-05-22 15:31:54'),
(3, 'Hen suyễn', 'Bệnh mãn tính ảnh hưởng đến đường thở', 'Sử dụng thuốc giãn phế quản và kiểm soát dị ứng', 2, '2025-05-22 08:31:54', '2025-05-22 15:31:54'),
(4, 'Viêm phổi', 'Nhiễm trùng phổi do vi khuẩn hoặc virus', 'Kháng sinh, nghỉ ngơi và điều trị hỗ trợ', 2, '2025-05-22 08:31:54', '2025-05-22 15:31:54'),
(5, 'Viêm dạ dày', 'Viêm lớp niêm mạc dạ dày', 'Tránh thức ăn cay, dùng thuốc kháng acid', 3, '2025-05-22 08:31:54', '2025-05-22 15:31:54'),
(6, 'Xơ gan', 'Tổn thương gan mạn tính', 'Kiểm soát nguyên nhân, chế độ ăn và theo dõi y tế', 3, '2025-05-22 08:31:54', '2025-05-22 15:31:54'),
(7, 'Động kinh', 'Rối loạn thần kinh gây co giật lặp lại', 'Dùng thuốc chống động kinh, theo dõi điện não đồ', 4, '2025-05-22 08:31:54', '2025-05-22 15:31:54'),
(8, 'Trầm cảm', 'Rối loạn tâm trạng kéo dài', 'Liệu pháp tâm lý và thuốc chống trầm cảm', 4, '2025-05-22 08:31:54', '2025-05-22 15:31:54'),
(9, 'Viêm da cơ địa', 'Bệnh da mãn tính gây ngứa và phát ban', 'Dưỡng ẩm, thuốc bôi chống viêm', 5, '2025-05-22 08:31:54', '2025-05-22 15:31:54'),
(10, 'Nấm da', 'Nhiễm trùng da do nấm', 'Thuốc kháng nấm dạng bôi hoặc uống', 5, '2025-05-22 08:31:54', '2025-05-22 15:31:54');

-- --------------------------------------------------------

--
-- Table structure for table `disease_symptoms`
--

CREATE TABLE `disease_symptoms` (
  `disease_id` int(11) NOT NULL,
  `symptom_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `disease_symptoms`
--

INSERT INTO `disease_symptoms` (`disease_id`, `symptom_id`) VALUES
(1, 1),
(1, 5),
(1, 6),
(2, 1),
(2, 6),
(2, 7),
(3, 2),
(3, 5),
(3, 6),
(4, 2),
(4, 4),
(4, 6),
(5, 3),
(5, 4),
(5, 10),
(6, 6),
(6, 10),
(7, 1),
(7, 7),
(8, 6),
(8, 10),
(9, 8),
(9, 9),
(10, 8),
(10, 9);

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `doctor_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `specialty_id` int(11) NOT NULL,
  `clinic_id` int(11) DEFAULT NULL,
  `biography` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`doctor_id`, `user_id`, `specialty_id`, `clinic_id`, `biography`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 1, 'Bác sĩ Nội khoa với hơn 10 năm kinh nghiệm trong điều trị tiểu đường, huyết áp. Tốt nghiệp Đại học Y Dược TP.HCM.', '2025-05-24 06:23:51', '2025-05-24 13:23:51'),
(2, 6, 4, 2, 'Bác sĩ Tim mạch từng công tác tại Viện Tim TP.HCM. Có bằng Thạc sĩ Y khoa từ Đại học Paris, Pháp.', '2025-05-24 06:23:51', '2025-05-24 13:23:51');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_schedules`
--

CREATE TABLE `doctor_schedules` (
  `schedule_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `clinic_id` int(11) DEFAULT NULL,
  `day_of_week` varchar(20) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor_schedules`
--

INSERT INTO `doctor_schedules` (`schedule_id`, `doctor_id`, `clinic_id`, `day_of_week`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Monday', '08:00:00', '12:00:00', '2025-05-24 06:25:08', '2025-05-24 13:25:08'),
(2, 1, 1, 'Wednesday', '08:00:00', '12:00:00', '2025-05-24 06:25:08', '2025-05-24 13:25:08'),
(3, 1, 1, 'Friday', '13:30:00', '17:30:00', '2025-05-24 06:25:08', '2025-05-24 13:25:08'),
(4, 2, 2, 'Tuesday', '09:00:00', '12:00:00', '2025-05-24 06:25:08', '2025-05-24 13:25:08'),
(5, 2, 2, 'Thursday', '14:00:00', '18:00:00', '2025-05-24 06:25:08', '2025-05-24 13:25:08'),
(6, 2, 2, 'Saturday', '08:30:00', '11:30:00', '2025-05-24 06:25:08', '2025-05-24 13:25:08');

-- --------------------------------------------------------

--
-- Table structure for table `guest_users`
--

CREATE TABLE `guest_users` (
  `guest_id` int(11) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guest_users`
--

INSERT INTO `guest_users` (`guest_id`, `full_name`, `phone`, `email`, `created_at`, `updated_at`) VALUES
(1, 'Nguyễn Văn A', '0909123456', 'nva@example.com', '2025-05-24 07:11:16', '2025-05-24 07:11:16'),
(2, 'Trần Thị B', '0911234567', 'ttb@example.com', '2025-05-24 07:11:16', '2025-05-24 07:11:16'),
(3, 'Lê Văn C', '0922345678', 'lvc@example.com', '2025-05-24 07:11:16', '2025-05-24 07:11:16');

-- --------------------------------------------------------

--
-- Table structure for table `health_predictions`
--

CREATE TABLE `health_predictions` (
  `prediction_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `chat_id` int(11) DEFAULT NULL,
  `prediction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `confidence_score` float DEFAULT NULL,
  `details` text DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `health_records`
--

CREATE TABLE `health_records` (
  `record_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `record_date` date NOT NULL,
  `weight` float DEFAULT NULL,
  `blood_pressure` varchar(20) DEFAULT NULL,
  `sleep_hours` float DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `invoice_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_id` int(11) NOT NULL,
  `invoice_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(16,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_details`
--

CREATE TABLE `invoice_details` (
  `detail_id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(16,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medical_categories`
--

CREATE TABLE `medical_categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_categories`
--

INSERT INTO `medical_categories` (`category_id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Tim mạch', 'Chuyên khoa liên quan đến tim và mạch máu', '2025-05-22 08:31:42', '2025-05-22 15:31:42'),
(2, 'Hô hấp', 'Chuyên khoa về phổi và hệ hô hấp', '2025-05-22 08:31:42', '2025-05-22 15:31:42'),
(3, 'Tiêu hóa', 'Chuyên khoa về dạ dày, ruột, gan...', '2025-05-22 08:31:42', '2025-05-22 15:31:42'),
(4, 'Thần kinh', 'Chuyên khoa về não và hệ thần kinh', '2025-05-22 08:31:42', '2025-05-22 15:31:42'),
(5, 'Da liễu', 'Chuyên khoa về da, tóc và móng', '2025-05-22 08:31:42', '2025-05-22 15:31:42');

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

CREATE TABLE `medical_records` (
  `med_rec_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `note_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `diagnosis` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_records`
--

INSERT INTO `medical_records` (`med_rec_id`, `appointment_id`, `note_date`, `diagnosis`, `recommendations`, `created_at`) VALUES
(1, 1, '2025-05-24 07:18:17', 'Tăng huyết áp giai đoạn 1', 'Cần điều chỉnh chế độ ăn và tập thể dục. Uống thuốc đều đặn.', '2025-05-24 07:18:17'),
(2, 2, '2025-05-24 07:18:17', 'Tiểu đường tuýp 2', 'Kiểm tra HbA1c 3 tháng/lần. Hạn chế đường và tinh bột.', '2025-05-24 07:18:17'),
(3, 3, '2025-05-24 07:18:17', 'Cao huyết áp do căng thẳng', 'Nghỉ ngơi hợp lý, tránh thức khuya. Theo dõi huyết áp hàng ngày.', '2025-05-24 07:18:17');

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `medicine_id` int(11) NOT NULL,
  `active_ingredient` varchar(255) DEFAULT NULL,
  `dosage_form` varchar(100) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `usage_instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `target_role_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `is_global` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_id` int(11) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total` decimal(16,0) NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `shipping_address` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(16,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_status` varchar(50) DEFAULT 'pending',
  `amount` decimal(16,0) NOT NULL,
  `payment_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prediction_diseases`
--

CREATE TABLE `prediction_diseases` (
  `id` int(11) NOT NULL,
  `prediction_id` int(11) NOT NULL,
  `disease_name` varchar(255) NOT NULL,
  `confidence` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `prescription_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `prescribed_date` date DEFAULT curdate(),
  `medications` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`prescription_id`, `appointment_id`, `prescribed_date`, `medications`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, '2025-05-28', '[\r\n  {\"name\": \"Thuốc hạ áp Amlodipine\", \"dosage\": \"5mg\", \"frequency\": \"1 viên/ngày\"},\r\n  {\"name\": \"Paracetamol\", \"dosage\": \"500mg\", \"frequency\": \"2 viên/ngày khi đau đầu\"}\r\n]', 'Uống vào buổi sáng sau ăn. Tránh dùng với rượu bia.', '2025-05-24 07:18:07', '2025-05-24 14:18:07'),
(2, 2, '2025-06-01', '[\r\n  {\"name\": \"Metformin\", \"dosage\": \"500mg\", \"frequency\": \"2 lần/ngày\"},\r\n  {\"name\": \"Glimepiride\", \"dosage\": \"2mg\", \"frequency\": \"1 lần/ngày trước ăn sáng\"}\r\n]', 'Kiểm tra đường huyết trước mỗi lần dùng thuốc.', '2025-05-24 07:18:07', '2025-05-24 14:18:07'),
(3, 3, '2025-05-25', '[\r\n  {\"name\": \"Losartan\", \"dosage\": \"50mg\", \"frequency\": \"1 viên mỗi sáng\"},\r\n  {\"name\": \"Vitamin B1\", \"dosage\": \"100mg\", \"frequency\": \"1 viên/ngày\"}\r\n]', 'Tái khám sau 1 tuần nếu triệu chứng không giảm.', '2025-05-24 07:18:07', '2025-05-24 14:18:07');

-- --------------------------------------------------------

--
-- Table structure for table `prescription_products`
--

CREATE TABLE `prescription_products` (
  `prescription_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `dosage` text DEFAULT NULL,
  `usage_time` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(16,0) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `image_url` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Ẩn/hiện sản phẩm (TRUE = hiển thị, FALSE = ẩn)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `review_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `description`) VALUES
(1, 'Admin', NULL),
(2, 'Doctor', NULL),
(3, 'Patient', NULL),
(4, 'Guest', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `specialties`
--

CREATE TABLE `specialties` (
  `specialty_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `specialties`
--

INSERT INTO `specialties` (`specialty_id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Nội khoa', 'Chẩn đoán và điều trị không phẫu thuật các bệnh lý nội tạng.', '2025-05-24 06:11:18', '2025-05-24 13:11:18'),
(2, 'Ngoại khoa', 'Chẩn đoán và điều trị bệnh thông qua phẫu thuật.', '2025-05-24 06:11:18', '2025-05-24 13:11:18'),
(3, 'Tai - Mũi - Họng', 'Khám và điều trị các bệnh lý về tai, mũi và họng.', '2025-05-24 06:11:18', '2025-05-24 13:11:18'),
(4, 'Tim mạch', 'Chuyên điều trị bệnh về tim và hệ tuần hoàn.', '2025-05-24 06:11:18', '2025-05-24 13:11:18'),
(5, 'Nhi khoa', 'Chăm sóc và điều trị cho trẻ em từ sơ sinh đến 15 tuổi.', '2025-05-24 06:11:18', '2025-05-24 13:11:18'),
(6, 'Da liễu', 'Chẩn đoán và điều trị các bệnh về da, tóc và móng.', '2025-05-24 06:11:18', '2025-05-24 13:11:18'),
(7, 'Tiêu hóa', 'Chuyên về hệ tiêu hóa như dạ dày, gan, ruột.', '2025-05-24 06:11:18', '2025-05-24 13:11:18'),
(8, 'Thần kinh', 'Khám và điều trị các bệnh về hệ thần kinh trung ương và ngoại biên.', '2025-05-24 06:11:18', '2025-05-24 13:11:18');

-- --------------------------------------------------------

--
-- Table structure for table `symptoms`
--

CREATE TABLE `symptoms` (
  `symptom_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `symptoms`
--

INSERT INTO `symptoms` (`symptom_id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Đau đầu', 'Cảm giác đau ở vùng đầu hoặc cổ', '2025-05-22 08:32:05', '2025-05-22 15:32:05'),
(2, 'Khó thở', 'Khó khăn trong việc hít thở bình thường', '2025-05-22 08:32:05', '2025-05-22 15:32:05'),
(3, 'Buồn nôn', 'Cảm giác muốn nôn mửa', '2025-05-22 08:32:05', '2025-05-22 15:32:05'),
(4, 'Sốt', 'Nhiệt độ cơ thể cao hơn bình thường', '2025-05-22 08:32:05', '2025-05-22 15:32:05'),
(5, 'Tức ngực', 'Cảm giác đau hoặc áp lực ở ngực', '2025-05-22 08:32:05', '2025-05-22 15:32:05'),
(6, 'Mệt mỏi', 'Cảm giác kiệt sức, thiếu năng lượng', '2025-05-22 08:32:05', '2025-05-22 15:32:05'),
(7, 'Co giật', 'Chuyển động không kiểm soát của cơ', '2025-05-22 08:32:05', '2025-05-22 15:32:05'),
(8, 'Ngứa da', 'Cảm giác châm chích khiến muốn gãi', '2025-05-22 08:32:05', '2025-05-22 15:32:05'),
(9, 'Phát ban', 'Vùng da bị nổi mẩn đỏ hoặc sưng', '2025-05-22 08:32:05', '2025-05-22 15:32:05'),
(10, 'Chán ăn', 'Mất cảm giác thèm ăn', '2025-05-22 08:32:05', '2025-05-22 15:32:05');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `phone_number`, `password_hash`, `role_id`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@gmail.com', '0123456789', '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC', 1, '2025-05-22 06:49:02', '2025-05-22 06:49:02'),
(2, 'huy', 'hoanhuy12@gmail.com', '0901647655', '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC', 1, '2025-05-22 06:49:02', '2025-05-22 06:49:02'),
(3, 'dr.hanh', 'doctor@example.com', '0999999999', '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC', 2, '2025-05-22 06:49:02', '2025-05-22 06:49:02'),
(4, 'nguyenvana', 'vana@example.com', '0901234567', '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC', 3, '2025-05-22 08:38:06', '2025-05-22 08:38:06'),
(6, 'linh', 'linh@gmail.com', '0123466789', '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC', 2, '2025-05-24 06:15:12', '2025-05-24 06:15:12');

-- --------------------------------------------------------

--
-- Table structure for table `users_info`
--

CREATE TABLE `users_info` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `gender` enum('Nam','Nữ','Khác') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users_info`
--

INSERT INTO `users_info` (`id`, `user_id`, `full_name`, `gender`, `date_of_birth`, `profile_picture`, `created_at`, `updated_at`) VALUES
(1, 1, 'Quản trị viên', 'Nam', '1990-01-01', NULL, '2025-05-22 06:49:55', '2025-05-22 06:49:55'),
(2, 2, 'Hoàn Huy', 'Nam', '1999-09-09', NULL, '2025-05-22 06:49:55', '2025-05-24 07:07:40'),
(3, 3, 'John Doe', 'Nam', '2000-12-01', NULL, '2025-05-22 06:49:55', '2025-05-22 06:49:55'),
(4, 4, 'Nguyễn Văn A', 'Nam', '1995-08-15', NULL, '2025-05-22 08:39:27', '2025-05-22 08:39:27'),
(5, 6, 'Dr.Linh', 'Nữ', '1995-08-15', NULL, '2025-05-24 06:17:47', '2025-05-24 06:17:47');

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_line` varchar(255) NOT NULL,
  `ward` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Vietnam',
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`id`, `user_id`, `address_line`, `ward`, `district`, `city`, `postal_code`, `country`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 1, '123 Đường Trần Hưng Đạo', 'Phường Nguyễn Cư Trinh', 'Quận 1', 'TP.HCM', '700000', 'Vietnam', 1, '2025-05-22 15:12:26', '2025-05-22 15:12:26'),
(2, 2, '456 Đường Lê Lợi', 'Phường Bến Nghé', 'Quận 1', 'TP.HCM', '700000', 'Vietnam', 1, '2025-05-22 15:12:26', '2025-05-22 15:12:26'),
(3, 2, '111 Đường long', 'Phường 11', 'Quận 11', 'TP.HCM', '110000', 'Vietnam', 0, '2025-05-22 15:12:26', '2025-05-22 16:02:32'),
(4, 3, '789 Đường Lý Thường Kiệt', 'Phường 7', 'Quận 10', 'TP.HCM', '700000', 'Vietnam', 1, '2025-05-22 15:12:26', '2025-05-22 15:12:26'),
(5, 4, '123 Đường Lý Thường Kiệt', 'Phường 7', 'Quận 10', 'TP.HCM', '70000', 'Vietnam', 1, '2025-05-22 15:40:10', '2025-05-22 15:40:10');

-- --------------------------------------------------------

--
-- Table structure for table `user_notifications`
--

CREATE TABLE `user_notifications` (
  `id` int(11) NOT NULL,
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `received_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_symptom_history`
--

CREATE TABLE `user_symptom_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `symptom_id` int(11) NOT NULL,
  `record_date` date NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_symptom_history`
--

INSERT INTO `user_symptom_history` (`id`, `user_id`, `symptom_id`, `record_date`, `notes`) VALUES
(1, 4, 1, '2025-05-18', 'Sốt cao 39 độ, kéo dài 2 ngày'),
(2, 4, 2, '2025-05-18', 'Ho khan, không có đờm'),
(3, 4, 3, '2025-05-19', 'Cổ họng đau rát, nuốt đau'),
(4, 4, 5, '2025-05-20', 'Đau đầu nhẹ, chủ yếu vào buổi sáng'),
(5, 4, 4, '2025-05-21', 'Cảm giác khó thở nhẹ khi vận động'),
(6, 4, 4, '2025-05-18', 'Sốt cao 39 độ, kéo dài 2 ngày'),
(7, 4, 1, '2025-05-18', 'Đau đầu âm ỉ vùng trán và sau gáy'),
(8, 4, 2, '2025-05-19', 'Khó thở nhẹ, đặc biệt khi leo cầu thang'),
(9, 4, 6, '2025-05-20', 'Cảm thấy mệt mỏi suốt cả ngày'),
(10, 4, 5, '2025-05-21', 'Cảm giác tức ngực nhẹ khi hít sâu');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `guest_id` (`guest_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `clinic_id` (`clinic_id`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `chatbot_knowledge_base`
--
ALTER TABLE `chatbot_knowledge_base`
  ADD PRIMARY KEY (`kb_id`);

--
-- Indexes for table `chat_logs`
--
ALTER TABLE `chat_logs`
  ADD PRIMARY KEY (`chat_id`),
  ADD KEY `guest_id` (`guest_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `clinics`
--
ALTER TABLE `clinics`
  ADD PRIMARY KEY (`clinic_id`);

--
-- Indexes for table `diseases`
--
ALTER TABLE `diseases`
  ADD PRIMARY KEY (`disease_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `disease_symptoms`
--
ALTER TABLE `disease_symptoms`
  ADD PRIMARY KEY (`disease_id`,`symptom_id`),
  ADD KEY `symptom_id` (`symptom_id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`doctor_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `specialty_id` (`specialty_id`),
  ADD KEY `clinic_id` (`clinic_id`);

--
-- Indexes for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `clinic_id` (`clinic_id`);

--
-- Indexes for table `guest_users`
--
ALTER TABLE `guest_users`
  ADD PRIMARY KEY (`guest_id`);

--
-- Indexes for table `health_predictions`
--
ALTER TABLE `health_predictions`
  ADD PRIMARY KEY (`prediction_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `record_id` (`record_id`),
  ADD KEY `chat_id` (`chat_id`);

--
-- Indexes for table `health_records`
--
ALTER TABLE `health_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`invoice_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `invoice_details`
--
ALTER TABLE `invoice_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `medical_categories`
--
ALTER TABLE `medical_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`med_rec_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`medicine_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `target_role_id` (`target_role_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `address_id` (`address_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `prediction_diseases`
--
ALTER TABLE `prediction_diseases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prediction_id` (`prediction_id`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`prescription_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `prescription_products`
--
ALTER TABLE `prescription_products`
  ADD PRIMARY KEY (`prescription_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `specialties`
--
ALTER TABLE `specialties`
  ADD PRIMARY KEY (`specialty_id`);

--
-- Indexes for table `symptoms`
--
ALTER TABLE `symptoms`
  ADD PRIMARY KEY (`symptom_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone_number` (`phone_number`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `users_info`
--
ALTER TABLE `users_info`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notification_id` (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_symptom_history`
--
ALTER TABLE `user_symptom_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `symptom_id` (`symptom_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chatbot_knowledge_base`
--
ALTER TABLE `chatbot_knowledge_base`
  MODIFY `kb_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_logs`
--
ALTER TABLE `chat_logs`
  MODIFY `chat_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clinics`
--
ALTER TABLE `clinics`
  MODIFY `clinic_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `diseases`
--
ALTER TABLE `diseases`
  MODIFY `disease_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `doctor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `guest_users`
--
ALTER TABLE `guest_users`
  MODIFY `guest_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `health_predictions`
--
ALTER TABLE `health_predictions`
  MODIFY `prediction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `health_records`
--
ALTER TABLE `health_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `invoice_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice_details`
--
ALTER TABLE `invoice_details`
  MODIFY `detail_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medical_categories`
--
ALTER TABLE `medical_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `med_rec_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prediction_diseases`
--
ALTER TABLE `prediction_diseases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `prescription_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `specialties`
--
ALTER TABLE `specialties`
  MODIFY `specialty_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `symptoms`
--
ALTER TABLE `symptoms`
  MODIFY `symptom_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users_info`
--
ALTER TABLE `users_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_notifications`
--
ALTER TABLE `user_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_symptom_history`
--
ALTER TABLE `user_symptom_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`guest_id`) REFERENCES `guest_users` (`guest_id`),
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`),
  ADD CONSTRAINT `appointments_ibfk_4` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`);

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `chat_logs`
--
ALTER TABLE `chat_logs`
  ADD CONSTRAINT `chat_logs_ibfk_1` FOREIGN KEY (`guest_id`) REFERENCES `guest_users` (`guest_id`),
  ADD CONSTRAINT `chat_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `diseases`
--
ALTER TABLE `diseases`
  ADD CONSTRAINT `diseases_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `medical_categories` (`category_id`);

--
-- Constraints for table `disease_symptoms`
--
ALTER TABLE `disease_symptoms`
  ADD CONSTRAINT `disease_symptoms_ibfk_1` FOREIGN KEY (`disease_id`) REFERENCES `diseases` (`disease_id`),
  ADD CONSTRAINT `disease_symptoms_ibfk_2` FOREIGN KEY (`symptom_id`) REFERENCES `symptoms` (`symptom_id`);

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `doctors_ibfk_2` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`specialty_id`),
  ADD CONSTRAINT `doctors_ibfk_3` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`);

--
-- Constraints for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD CONSTRAINT `doctor_schedules_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`),
  ADD CONSTRAINT `doctor_schedules_ibfk_2` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`);

--
-- Constraints for table `health_predictions`
--
ALTER TABLE `health_predictions`
  ADD CONSTRAINT `health_predictions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `health_predictions_ibfk_2` FOREIGN KEY (`record_id`) REFERENCES `health_records` (`record_id`),
  ADD CONSTRAINT `health_predictions_ibfk_3` FOREIGN KEY (`chat_id`) REFERENCES `chat_logs` (`chat_id`);

--
-- Constraints for table `health_records`
--
ALTER TABLE `health_records`
  ADD CONSTRAINT `health_records_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `invoice_details`
--
ALTER TABLE `invoice_details`
  ADD CONSTRAINT `invoice_details_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`invoice_id`),
  ADD CONSTRAINT `invoice_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD CONSTRAINT `medical_records_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`);

--
-- Constraints for table `medicines`
--
ALTER TABLE `medicines`
  ADD CONSTRAINT `medicines_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`target_role_id`) REFERENCES `roles` (`role_id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`address_id`) REFERENCES `user_addresses` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `prediction_diseases`
--
ALTER TABLE `prediction_diseases`
  ADD CONSTRAINT `prediction_diseases_ibfk_1` FOREIGN KEY (`prediction_id`) REFERENCES `health_predictions` (`prediction_id`);

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `prescriptions_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`);

--
-- Constraints for table `prescription_products`
--
ALTER TABLE `prescription_products`
  ADD CONSTRAINT `prescription_products_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`prescription_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescription_products_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`category_id`);

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);

--
-- Constraints for table `users_info`
--
ALTER TABLE `users_info`
  ADD CONSTRAINT `users_info_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD CONSTRAINT `user_notifications_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`notification_id`),
  ADD CONSTRAINT `user_notifications_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `user_symptom_history`
--
ALTER TABLE `user_symptom_history`
  ADD CONSTRAINT `user_symptom_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `user_symptom_history_ibfk_2` FOREIGN KEY (`symptom_id`) REFERENCES `symptoms` (`symptom_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
