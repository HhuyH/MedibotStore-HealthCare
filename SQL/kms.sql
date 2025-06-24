-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 24, 2025 at 07:49 PM
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_user_details` (IN `in_user_id` INT)   BEGIN
    SELECT 
        u.user_id AS `User ID`,
        u.username AS `Username`,
        u.email AS `Email`,
        ui.phone AS `Số điện thoại`,
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_user_info` (IN `input_login` VARCHAR(100))   BEGIN
    SELECT u.user_id, u.username, u.email, u.password_hash, r.role_name
    FROM users u
    JOIN roles r ON u.role_id = r.role_id
    WHERE u.username = input_login OR u.email = input_login
    LIMIT 1;
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
-- Table structure for table `blog_authors`
--

CREATE TABLE `blog_authors` (
  `author_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blog_categories`
--

CREATE TABLE `blog_categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blog_posts`
--

CREATE TABLE `blog_posts` (
  `post_id` int(11) NOT NULL,
  `author_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `excerpt` text DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `is_featured` tinyint(1) DEFAULT 0,
  `view_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `published_at` timestamp NULL DEFAULT NULL
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

--
-- Dumping data for table `chatbot_knowledge_base`
--

INSERT INTO `chatbot_knowledge_base` (`kb_id`, `intent`, `question`, `answer`, `category`, `created_at`, `updated_at`) VALUES
(1, 'ask_working_hours', 'Bệnh viện làm việc vào thời gian nào?', 'Bệnh viện hoạt động từ 7h00 đến 17h00, từ thứ Hai đến thứ Bảy.', 'Thông tin chung', '2025-06-05 12:55:00', '2025-06-05 19:55:00'),
(2, 'ask_contact_info', 'Tôi có thể liên hệ bệnh viện qua số điện thoại nào?', 'Bạn có thể gọi đến số 1900-1234 để được hỗ trợ.', 'Thông tin chung', '2025-06-05 12:55:00', '2025-06-05 19:55:00'),
(3, 'ask_location', 'Địa chỉ bệnh viện là gì?', 'Bệnh viện tọa lạc tại số 123 Đường Sức Khỏe, Quận 10, TP.HCM.', 'Thông tin chung', '2025-06-05 12:55:00', '2025-06-05 19:55:00'),
(4, 'ask_services', 'Bệnh viện có những dịch vụ gì?', 'Chúng tôi cung cấp khám chữa bệnh, xét nghiệm, chẩn đoán hình ảnh, điều trị nội trú và các dịch vụ chuyên khoa khác.', 'Thông tin chung', '2025-06-05 12:55:00', '2025-06-05 19:55:00'),
(5, 'symptom_analysis', 'Tôi bị sốt, mệt mỏi và ho, có thể là bệnh gì?', 'Đây là triệu chứng thường gặp của cảm lạnh, viêm họng hoặc cúm. Bạn nên nghỉ ngơi, uống nhiều nước và theo dõi. Nếu không đỡ sau vài ngày, hãy đi khám.', 'Triệu chứng chung', '2025-06-05 12:55:00', '2025-06-05 19:55:00'),
(6, 'symptom_analysis', 'Tôi bị đau đầu và chóng mặt, có thể là bệnh gì?', 'Triệu chứng này có thể do căng thẳng, thiếu ngủ, hoặc huyết áp bất thường. Nếu kéo dài hoặc nặng hơn, bạn nên đi khám.', 'Triệu chứng chung', '2025-06-05 12:55:00', '2025-06-05 19:55:00'),
(7, 'symptom_analysis', 'Tôi bị khó thở và tức ngực, có thể là bệnh gì?', 'Triệu chứng này có thể liên quan đến hen suyễn, viêm phổi, hoặc bệnh tim mạch. Bạn cần được kiểm tra y tế càng sớm càng tốt.', 'Triệu chứng chung', '2025-06-05 12:55:00', '2025-06-05 19:55:00'),
(8, 'symptom_analysis', 'Tôi bị ngứa da và phát ban, có thể là do bệnh gì?', 'Đây có thể là dấu hiệu của dị ứng, viêm da cơ địa, hoặc nhiễm nấm da. Tránh gãi và nên đến bác sĩ da liễu nếu triệu chứng nặng.', 'Triệu chứng chung', '2025-06-05 12:55:00', '2025-06-05 19:55:00'),
(9, 'symptom_analysis', 'Tôi bị buồn nôn và chán ăn, có thể do bệnh gì?', 'Có thể do rối loạn tiêu hóa, căng thẳng hoặc nhiễm trùng nhẹ. Nếu kéo dài nhiều ngày, bạn nên đi khám để xác định nguyên nhân.', 'Triệu chứng chung', '2025-06-05 12:55:00', '2025-06-05 19:55:00'),
(10, 'disease_info', 'Bệnh tiểu đường có những triệu chứng gì?', 'Các triệu chứng bao gồm: khát nước liên tục, đi tiểu nhiều lần, mệt mỏi, mờ mắt và sụt cân không rõ nguyên nhân.', 'Thông tin bệnh', '2025-06-05 12:55:00', '2025-06-05 19:55:00'),
(11, 'medicine_usage', 'Tôi nên uống thuốc hạ sốt như thế nào?', 'Bạn nên uống thuốc hạ sốt theo đúng liều bác sĩ chỉ định. Thường chỉ dùng khi sốt từ 38.5°C trở lên.', 'Hướng dẫn dùng thuốc', '2025-06-05 12:55:00', '2025-06-05 19:55:00'),
(12, 'account_help', 'Tôi quên mật khẩu đăng nhập thì phải làm sao?', 'Bạn hãy dùng chức năng \"Quên mật khẩu\" trên màn hình đăng nhập để đặt lại mật khẩu.', 'Hỗ trợ tài khoản', '2025-06-05 12:55:00', '2025-06-05 19:55:00'),
(13, 'app_issue', 'Ứng dụng bị lỗi khi tôi mở lên, phải làm sao?', 'Bạn nên thử khởi động lại ứng dụng hoặc cập nhật phiên bản mới nhất. Nếu vẫn gặp lỗi, hãy liên hệ bộ phận hỗ trợ.', 'Hỗ trợ kỹ thuật', '2025-06-05 12:55:00', '2025-06-05 19:55:00'),
(14, 'payment_issue', 'Tôi không thể thanh toán đơn thuốc, phải làm sao?', 'Bạn hãy kiểm tra lại thông tin tài khoản ngân hàng hoặc phương thức thanh toán. Nếu vẫn không được, hãy liên hệ bộ phận hỗ trợ.', 'Hỗ trợ thanh toán', '2025-06-05 12:55:00', '2025-06-05 19:55:00');

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
  `severity` enum('nhẹ','trung bình','nghiêm trọng') DEFAULT 'trung bình',
  `category_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `diseases`
--

INSERT INTO `diseases` (`disease_id`, `name`, `description`, `treatment_guidelines`, `severity`, `category_id`, `created_at`, `updated_at`) VALUES
(-1, 'Chưa rõ', 'Dự đoán từ GPT nhưng chưa có trong cơ sở dữ liệu', NULL, 'trung bình', NULL, '2025-06-24 10:52:01', '2025-06-24 17:52:01'),
(1, 'Tăng huyết áp', 'Huyết áp cao mãn tính', 'Theo dõi huyết áp thường xuyên, dùng thuốc hạ áp', 'trung bình', 1, '2025-06-10 07:34:39', '2025-06-10 14:34:39'),
(2, 'Đột quỵ', 'Rối loạn tuần hoàn não nghiêm trọng', 'Can thiệp y tế khẩn cấp, phục hồi chức năng', 'nghiêm trọng', 1, '2025-06-10 07:34:39', '2025-06-18 20:53:17'),
(3, 'Hen suyễn', 'Bệnh mãn tính ảnh hưởng đến đường thở', 'Sử dụng thuốc giãn phế quản và kiểm soát dị ứng', 'trung bình', 2, '2025-06-10 07:34:39', '2025-06-10 14:34:39'),
(4, 'Viêm phổi', 'Nhiễm trùng phổi do vi khuẩn hoặc virus', 'Kháng sinh, nghỉ ngơi và điều trị hỗ trợ', 'nghiêm trọng', 2, '2025-06-10 07:34:39', '2025-06-18 20:53:17'),
(5, 'Viêm dạ dày', 'Viêm lớp niêm mạc dạ dày', 'Tránh thức ăn cay, dùng thuốc kháng acid', 'nhẹ', 3, '2025-06-10 07:34:39', '2025-06-18 20:53:17'),
(6, 'Xơ gan', 'Tổn thương gan mạn tính', 'Kiểm soát nguyên nhân, chế độ ăn và theo dõi y tế', 'nghiêm trọng', 3, '2025-06-10 07:34:39', '2025-06-18 20:53:17'),
(7, 'Động kinh', 'Rối loạn thần kinh gây co giật lặp lại', 'Dùng thuốc chống động kinh, theo dõi điện não đồ', 'nghiêm trọng', 4, '2025-06-10 07:34:39', '2025-06-18 20:53:17'),
(8, 'Trầm cảm', 'Rối loạn tâm trạng kéo dài', 'Liệu pháp tâm lý và thuốc chống trầm cảm', 'trung bình', 4, '2025-06-10 07:34:39', '2025-06-10 14:34:39'),
(9, 'Viêm da cơ địa', 'Bệnh da mãn tính gây ngứa và phát ban', 'Dưỡng ẩm, thuốc bôi chống viêm', 'nhẹ', 5, '2025-06-10 07:34:39', '2025-06-18 20:53:17'),
(10, 'Nấm da', 'Nhiễm trùng da do nấm', 'Thuốc kháng nấm dạng bôi hoặc uống', 'nhẹ', 5, '2025-06-10 07:34:39', '2025-06-18 20:53:17'),
(11, 'Viêm đa cơ', 'Bệnh tự miễn ảnh hưởng đến cơ', 'Dùng thuốc ức chế miễn dịch, vật lý trị liệu', 'trung bình', 4, '2025-06-12 13:32:50', '2025-06-12 20:32:50'),
(12, 'Tiểu đường tuýp 2', 'Tình trạng rối loạn chuyển hóa đường máu mạn tính', 'Kiểm soát chế độ ăn, tập luyện, dùng thuốc hạ đường huyết', 'trung bình', 1, '2025-06-18 14:01:47', '2025-06-18 21:01:47'),
(13, 'Suy tim', 'Tình trạng tim không bơm đủ máu cho cơ thể', 'Dùng thuốc lợi tiểu, ức chế men chuyển, theo dõi sát', 'nghiêm trọng', 1, '2025-06-18 14:01:47', '2025-06-18 21:01:47'),
(14, 'Viêm phế quản', 'Tình trạng viêm đường thở lớn (phế quản)', 'Nghỉ ngơi, dùng thuốc giảm viêm và long đờm', 'trung bình', 2, '2025-06-18 14:01:47', '2025-06-18 21:01:47'),
(15, 'Viêm họng cấp', 'Viêm niêm mạc họng do virus hoặc vi khuẩn', 'Súc miệng nước muối, thuốc giảm đau, kháng sinh nếu cần', 'nhẹ', 2, '2025-06-18 14:01:47', '2025-06-18 21:01:47'),
(16, 'Loét dạ dày tá tràng', 'Tổn thương niêm mạc dạ dày hoặc tá tràng', 'Thuốc ức chế acid, tránh rượu bia, stress', 'trung bình', 3, '2025-06-18 14:01:47', '2025-06-18 21:01:47'),
(17, 'Viêm gan B mạn tính', 'Nhiễm HBV kéo dài, gây tổn thương gan', 'Theo dõi chức năng gan, dùng thuốc kháng virus nếu cần', 'trung bình', 3, '2025-06-18 14:01:47', '2025-06-18 21:01:47'),
(18, 'Thiếu máu', 'Giảm số lượng hồng cầu hoặc hemoglobin', 'Bổ sung sắt, acid folic hoặc điều trị nguyên nhân nền', 'nhẹ', 1, '2025-06-18 14:01:47', '2025-06-18 21:01:47'),
(19, 'Gút', 'Tình trạng viêm khớp do tinh thể urat', 'Dùng colchicine, allopurinol, hạn chế đạm', 'trung bình', 4, '2025-06-18 14:01:47', '2025-06-18 21:01:47'),
(20, 'Viêm khớp dạng thấp', 'Bệnh tự miễn gây viêm nhiều khớp', 'Dùng DMARDs, thuốc chống viêm và vật lý trị liệu', 'nghiêm trọng', 4, '2025-06-18 14:01:47', '2025-06-18 21:01:47'),
(21, 'Trào ngược dạ dày thực quản', 'Dịch dạ dày trào lên thực quản gây kích ứng', 'Nâng đầu giường, hạn chế ăn đêm, dùng thuốc PPI', 'nhẹ', 3, '2025-06-18 14:01:47', '2025-06-18 21:01:47'),
(22, 'Rối loạn lo âu', 'Tình trạng tâm lý gây lo lắng kéo dài', 'Liệu pháp hành vi nhận thức, thuốc chống lo âu', 'trung bình', 4, '2025-06-18 14:01:47', '2025-06-18 21:01:47'),
(23, 'Cảm cúm', 'Nhiễm virus cúm gây mệt, sốt, đau họng', 'Nghỉ ngơi, hạ sốt, uống nhiều nước', 'nhẹ', 2, '2025-06-18 14:01:47', '2025-06-18 21:01:47'),
(24, 'Đau thần kinh tọa', 'Đau do chèn ép dây thần kinh hông lớn', 'Dùng thuốc giảm đau, vật lý trị liệu, nghỉ ngơi', 'trung bình', 4, '2025-06-18 14:01:47', '2025-06-18 21:01:47'),
(25, 'Viêm kết mạc', 'Viêm màng mắt ngoài do vi khuẩn, virus hoặc dị ứng', 'Thuốc nhỏ mắt kháng sinh hoặc chống dị ứng', 'nhẹ', 5, '2025-06-18 14:01:47', '2025-06-18 21:01:47'),
(26, 'Chàm (eczema)', 'Bệnh da mãn tính gây ngứa, khô và viêm', 'Dưỡng ẩm, thuốc bôi corticoid, tránh dị nguyên', 'nhẹ', 5, '2025-06-18 14:01:47', '2025-06-18 21:01:47');

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
(1, 20),
(1, 24),
(1, 26),
(2, 1),
(2, 6),
(2, 7),
(2, 19),
(2, 26),
(3, 2),
(3, 11),
(3, 13),
(3, 23),
(4, 2),
(4, 4),
(4, 11),
(4, 14),
(4, 28),
(5, 3),
(5, 10),
(5, 16),
(5, 18),
(5, 27),
(6, 6),
(6, 10),
(6, 16),
(6, 17),
(7, 6),
(7, 7),
(7, 19),
(7, 21),
(8, 6),
(8, 10),
(8, 22),
(8, 25),
(9, 8),
(9, 9),
(10, 8),
(10, 9),
(11, 29),
(12, 6),
(12, 10),
(12, 35),
(13, 2),
(13, 5),
(13, 6),
(13, 24),
(14, 4),
(14, 11),
(14, 14),
(15, 12),
(15, 13),
(15, 14),
(16, 3),
(16, 16),
(16, 27),
(16, 32),
(17, 6),
(17, 10),
(17, 16),
(17, 33),
(18, 6),
(18, 25),
(18, 26),
(19, 16),
(19, 36),
(20, 16),
(20, 29),
(20, 37),
(21, 3),
(21, 16),
(21, 32),
(22, 6),
(22, 22),
(22, 34),
(23, 4),
(23, 11),
(23, 12),
(23, 13),
(24, 16),
(24, 25),
(24, 40),
(25, 13),
(25, 38),
(26, 8),
(26, 9);

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

--
-- Dumping data for table `health_predictions`
--

INSERT INTO `health_predictions` (`prediction_id`, `user_id`, `record_id`, `chat_id`, `prediction_date`, `confidence_score`, `details`) VALUES
(1, 4, 1, NULL, '2025-06-12 13:55:06', 1, '{\"symptoms\": [\"Ho\", \"Ch\\u1ea3y n\\u01b0\\u1edbc m\\u0169i\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(2, 4, 2, NULL, '2025-06-12 15:26:48', 1, '{\"symptoms\": [\"Ch\\u1ea3y n\\u01b0\\u1edbc m\\u0169i\", \"Ho\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(3, 4, 3, NULL, '2025-06-12 16:53:29', 1, '{\"symptoms\": [\"Ho\", \"Ch\\u1ea3y n\\u01b0\\u1edbc m\\u0169i\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(4, 4, 4, NULL, '2025-06-15 04:50:20', 1, '{\"symptoms\": [\"Ho\", \"Ch\\u1ea3y n\\u01b0\\u1edbc m\\u0169i\", \"S\\u1ed1t\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(5, 4, 5, NULL, '2025-06-16 17:58:48', 1, '{\"symptoms\": [\"\\u0110au \\u0111\\u1ea7u\", \"Hoa m\\u1eaft ch\\u00f3ng m\\u1eb7t\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(6, 4, 6, NULL, '2025-06-16 18:01:22', 1, '{\"symptoms\": [\"\\u0110au \\u0111\\u1ea7u\", \"Hoa m\\u1eaft ch\\u00f3ng m\\u1eb7t\", \"M\\u1ec7t m\\u1ecfi\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(7, 4, 7, NULL, '2025-06-17 06:41:22', 1, '{\"symptoms\": [\"M\\u1ec7t m\\u1ecfi\", \"Hoa m\\u1eaft ch\\u00f3ng m\\u1eb7t\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(8, 4, 8, NULL, '2025-06-17 06:48:22', 1, '{\"symptoms\": [\"M\\u1ec7t m\\u1ecfi\", \"Hoa m\\u1eaft ch\\u00f3ng m\\u1eb7t\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(9, 4, 9, NULL, '2025-06-17 09:38:48', 1, '{\"symptoms\": [\"Hoa m\\u1eaft ch\\u00f3ng m\\u1eb7t\", \"Hoa m\\u1eaft ch\\u00f3ng m\\u1eb7t\", \"M\\u1ec7t m\\u1ecfi\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(10, 4, 10, NULL, '2025-06-17 09:55:12', 1, '{\"symptoms\": [\"Hoa m\\u1eaft ch\\u00f3ng m\\u1eb7t\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(11, 4, 11, NULL, '2025-06-17 10:04:07', 1, '{\"symptoms\": [\"Hoa m\\u1eaft ch\\u00f3ng m\\u1eb7t\", \"Kh\\u00f3 ng\\u1ee7\", \"Hoa m\\u1eaft ch\\u00f3ng m\\u1eb7t\", \"Kh\\u00f3 ng\\u1ee7\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(12, 4, 12, NULL, '2025-06-17 10:07:17', 1, '{\"symptoms\": [\"Ho\", \"Kh\\u00f3 th\\u1edf\", \"M\\u1ec7t m\\u1ecfi\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(13, 4, 13, NULL, '2025-06-17 10:18:32', 1, '{\"symptoms\": [\"Ho\", \"Ho\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(14, 4, 14, NULL, '2025-06-17 10:44:43', 1, '{\"symptoms\": [\"Ho\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(15, 4, 15, NULL, '2025-06-17 10:47:14', 1, '{\"symptoms\": [\"Ho\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(16, 4, 16, NULL, '2025-06-17 10:51:30', 1, '{\"symptoms\": [\"Ho\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(17, 4, 17, NULL, '2025-06-17 10:54:56', 1, '{\"symptoms\": [\"Ho\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(18, 4, 18, NULL, '2025-06-17 10:58:08', 1, '{\"symptoms\": [\"Ho\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(19, 4, 19, NULL, '2025-06-17 11:05:37', 1, '{\"symptoms\": [\"Ho\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(20, 4, 20, NULL, '2025-06-18 07:54:11', 1, '{\"symptoms\": [\"Hoa m\\u1eaft ch\\u00f3ng m\\u1eb7t\", \"Tim \\u0111\\u1eadp nhanh\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(21, 4, 21, NULL, '2025-06-18 07:55:57', 1, '{\"symptoms\": [\"Hoa m\\u1eaft ch\\u00f3ng m\\u1eb7t\", \"Tim \\u0111\\u1eadp nhanh\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(22, 4, 22, NULL, '2025-06-18 08:11:41', 0.19, '{\"symptoms\": [\"Hoa m\\u1eaft ch\\u00f3ng m\\u1eb7t\", \"Tim \\u0111\\u1eadp nhanh\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(23, 4, 23, NULL, '2025-06-18 08:17:35', 0.19, '{\"symptoms\": [\"Hoa m\\u1eaft ch\\u00f3ng m\\u1eb7t\", \"Tim \\u0111\\u1eadp nhanh\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(24, 4, 24, NULL, '2025-06-18 08:21:07', 0.19, '{\"symptoms\": [\"Hoa m\\u1eaft ch\\u00f3ng m\\u1eb7t\", \"Tim \\u0111\\u1eadp nhanh\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(25, 4, 25, NULL, '2025-06-18 08:25:56', 0.19, '{\"symptoms\": [\"Hoa m\\u1eaft ch\\u00f3ng m\\u1eb7t\", \"Tim \\u0111\\u1eadp nhanh\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(26, 4, 26, NULL, '2025-06-18 08:32:07', 0.19, '{\"symptoms\": [\"Hoa m\\u1eaft ch\\u00f3ng m\\u1eb7t\", \"Tim \\u0111\\u1eadp nhanh\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(27, 4, 27, NULL, '2025-06-18 08:36:56', 0.19, '{\"symptoms\": [\"Hoa m\\u1eaft ch\\u00f3ng m\\u1eb7t\", \"Tim \\u0111\\u1eadp nhanh\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(28, 4, 28, NULL, '2025-06-18 08:41:53', 0.19, '{\"symptoms\": [\"Hoa m\\u1eaft ch\\u00f3ng m\\u1eb7t\", \"Tim \\u0111\\u1eadp nhanh\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(29, 4, 29, NULL, '2025-06-18 08:46:16', 0.19, '{\"symptoms\": [\"Hoa m\\u1eaft ch\\u00f3ng m\\u1eb7t\", \"Tim \\u0111\\u1eadp nhanh\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(30, 4, 30, NULL, '2025-06-18 08:47:43', 0.19, '{\"symptoms\": [\"Hoa m\\u1eaft ch\\u00f3ng m\\u1eb7t\", \"Tim \\u0111\\u1eadp nhanh\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(31, 4, 31, NULL, '2025-06-18 08:57:45', 0.19, '{\"symptoms\": [\"Hoa m\\u1eaft ch\\u00f3ng m\\u1eb7t\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(32, 4, 32, NULL, '2025-06-18 08:59:40', 0.38, '{\"symptoms\": [\"Hoa m\\u1eaft ch\\u00f3ng m\\u1eb7t\", \"M\\u1ec7t m\\u1ecfi\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(33, 4, 33, NULL, '2025-06-18 13:35:47', 0.19, '{\"symptoms\": [\"Ch\\u00f3ng m\\u1eb7t\", \"Ch\\u00f3ng m\\u1eb7t\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(34, 4, 34, NULL, '2025-06-18 16:42:23', 0.3, '{\"symptoms\": [\"Ch\\u00f3ng m\\u1eb7t\", \"\\u0110au \\u0111\\u1ea7u\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(35, 4, 35, NULL, '2025-06-19 08:37:33', 0.3, '{\"symptoms\": [\"Ch\\u00f3ng m\\u1eb7t\", \"\\u0110au \\u0111\\u1ea7u\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}'),
(36, 4, 36, NULL, '2025-06-19 08:39:46', 0.38, '{\"symptoms\": [\"Ho\", \"Ch\\u1ea3y n\\u01b0\\u1edbc m\\u0169i\"], \"summary\": \"AI predicted diseases based on reported symptoms\"}');

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

--
-- Dumping data for table `health_records`
--

INSERT INTO `health_records` (`record_id`, `user_id`, `record_date`, `weight`, `blood_pressure`, `sleep_hours`, `notes`, `created_at`, `updated_at`) VALUES
(1, 4, '2025-06-12', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Ho, Chảy nước mũi', '2025-06-12 13:55:06', '2025-06-12 20:55:06'),
(2, 4, '2025-06-12', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Chảy nước mũi, Ho', '2025-06-12 15:26:48', '2025-06-12 22:26:48'),
(3, 4, '2025-06-12', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Ho, Chảy nước mũi', '2025-06-12 16:53:29', '2025-06-12 23:53:29'),
(4, 4, '2025-06-15', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Ho, Chảy nước mũi, Sốt', '2025-06-15 04:50:20', '2025-06-15 11:50:20'),
(5, 4, '2025-06-17', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Đau đầu, Hoa mắt chóng mặt', '2025-06-16 17:58:48', '2025-06-17 00:58:48'),
(6, 4, '2025-06-17', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Đau đầu, Hoa mắt chóng mặt, Mệt mỏi', '2025-06-16 18:01:22', '2025-06-17 01:01:22'),
(7, 4, '2025-06-17', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Mệt mỏi, Hoa mắt chóng mặt', '2025-06-17 06:41:22', '2025-06-17 13:41:22'),
(8, 4, '2025-06-17', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Mệt mỏi, Hoa mắt chóng mặt', '2025-06-17 06:48:22', '2025-06-17 13:48:22'),
(9, 4, '2025-06-17', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Hoa mắt chóng mặt, Hoa mắt chóng mặt, Mệt mỏi', '2025-06-17 09:38:48', '2025-06-17 16:38:48'),
(10, 4, '2025-06-17', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Hoa mắt chóng mặt', '2025-06-17 09:55:12', '2025-06-17 16:55:12'),
(11, 4, '2025-06-17', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Hoa mắt chóng mặt, Khó ngủ, Hoa mắt chóng mặt, Khó ngủ', '2025-06-17 10:04:07', '2025-06-17 17:04:07'),
(12, 4, '2025-06-17', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Ho, Khó thở, Mệt mỏi', '2025-06-17 10:07:17', '2025-06-17 17:07:17'),
(13, 4, '2025-06-17', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Ho, Ho', '2025-06-17 10:18:32', '2025-06-17 17:18:32'),
(14, 4, '2025-06-17', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Ho', '2025-06-17 10:44:43', '2025-06-17 17:44:43'),
(15, 4, '2025-06-17', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Ho', '2025-06-17 10:47:14', '2025-06-17 17:47:14'),
(16, 4, '2025-06-17', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Ho', '2025-06-17 10:51:30', '2025-06-17 17:51:30'),
(17, 4, '2025-06-17', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Ho', '2025-06-17 10:54:56', '2025-06-17 17:54:56'),
(18, 4, '2025-06-17', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Ho', '2025-06-17 10:58:08', '2025-06-17 17:58:08'),
(19, 4, '2025-06-17', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Ho', '2025-06-17 11:05:36', '2025-06-17 18:05:36'),
(20, 4, '2025-06-18', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Hoa mắt chóng mặt, Tim đập nhanh', '2025-06-18 07:54:11', '2025-06-18 14:54:11'),
(21, 4, '2025-06-18', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Hoa mắt chóng mặt, Tim đập nhanh', '2025-06-18 07:55:57', '2025-06-18 14:55:57'),
(22, 4, '2025-06-18', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Hoa mắt chóng mặt, Tim đập nhanh', '2025-06-18 08:11:41', '2025-06-18 15:11:41'),
(23, 4, '2025-06-18', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Hoa mắt chóng mặt, Tim đập nhanh', '2025-06-18 08:17:35', '2025-06-18 15:17:35'),
(24, 4, '2025-06-18', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Hoa mắt chóng mặt, Tim đập nhanh', '2025-06-18 08:21:07', '2025-06-18 15:21:07'),
(25, 4, '2025-06-18', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Hoa mắt chóng mặt, Tim đập nhanh', '2025-06-18 08:25:56', '2025-06-18 15:25:56'),
(26, 4, '2025-06-18', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Hoa mắt chóng mặt, Tim đập nhanh', '2025-06-18 08:32:07', '2025-06-18 15:32:07'),
(27, 4, '2025-06-18', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Hoa mắt chóng mặt, Tim đập nhanh', '2025-06-18 08:36:56', '2025-06-18 15:36:56'),
(28, 4, '2025-06-18', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Hoa mắt chóng mặt, Tim đập nhanh', '2025-06-18 08:41:53', '2025-06-18 15:41:53'),
(29, 4, '2025-06-18', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Hoa mắt chóng mặt, Tim đập nhanh', '2025-06-18 08:46:16', '2025-06-18 15:46:16'),
(30, 4, '2025-06-18', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Hoa mắt chóng mặt, Tim đập nhanh', '2025-06-18 08:47:43', '2025-06-18 15:47:43'),
(31, 4, '2025-06-18', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Hoa mắt chóng mặt', '2025-06-18 08:57:45', '2025-06-18 15:57:45'),
(32, 4, '2025-06-18', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Hoa mắt chóng mặt, Mệt mỏi', '2025-06-18 08:59:40', '2025-06-18 15:59:40'),
(33, 4, '2025-06-18', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Chóng mặt, Chóng mặt', '2025-06-18 13:35:47', '2025-06-18 20:35:47'),
(34, 4, '2025-06-18', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Chóng mặt, Đau đầu', '2025-06-18 16:42:23', '2025-06-18 23:42:23'),
(35, 4, '2025-06-19', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Chóng mặt, Đau đầu', '2025-06-19 08:37:33', '2025-06-19 15:37:33'),
(36, 4, '2025-06-19', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Ho, Chảy nước mũi', '2025-06-19 08:39:46', '2025-06-19 15:39:46');

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

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`medicine_id`, `active_ingredient`, `dosage_form`, `unit`, `usage_instructions`, `created_at`, `updated_at`) VALUES
(1, 'Paracetamol', 'Viên nén', 'viên', 'Uống 1–2 viên mỗi 4–6 giờ nếu cần. Không dùng quá 8 viên/ngày.', '2025-05-28 07:02:02', '2025-05-28 14:02:02'),
(2, 'Amoxicillin', 'Viên nang', 'viên', 'Uống 1 viên mỗi 8 giờ, duy trì trong 5–7 ngày.', '2025-05-28 07:02:02', '2025-05-28 14:02:02');

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
  `address_id` int(11) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `total` decimal(16,0) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` varchar(50) DEFAULT 'pending',
  `status` enum('cart','pending','processing','shipped','completed','cancelled') DEFAULT 'cart',
  `order_note` text DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
-- Table structure for table `package_features`
--

CREATE TABLE `package_features` (
  `id` int(11) NOT NULL,
  `package_id` int(11) DEFAULT NULL,
  `feature_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `package_features`
--

INSERT INTO `package_features` (`id`, `package_id`, `feature_name`, `description`, `display_order`, `created_at`) VALUES
(1, 1, 'Khám lâm sàng tổng quát', NULL, 0, '2025-06-04 06:33:57'),
(2, 1, 'Xét nghiệm máu cơ bản', NULL, 0, '2025-06-04 06:33:57'),
(3, 1, 'Xét nghiệm nước tiểu', NULL, 0, '2025-06-04 06:33:57'),
(4, 1, 'X-quang phổi', NULL, 0, '2025-06-04 06:33:57'),
(5, 1, 'Điện tim', NULL, 0, '2025-06-04 06:33:57'),
(6, 1, 'Tư vấn kết quả', NULL, 0, '2025-06-04 06:33:57'),
(7, 2, 'Tất cả gói cơ bản', NULL, 0, '2025-06-04 06:33:57'),
(8, 2, 'Siêu âm bụng tổng quát', NULL, 0, '2025-06-04 06:33:57'),
(9, 2, 'Siêu âm tim', NULL, 0, '2025-06-04 06:33:57');

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
  `disease_id` int(11) NOT NULL,
  `disease_name_raw` varchar(255) DEFAULT NULL,
  `confidence` float DEFAULT NULL CHECK (`confidence` between 0 and 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prediction_diseases`
--

INSERT INTO `prediction_diseases` (`id`, `prediction_id`, `disease_id`, `disease_name_raw`, `confidence`) VALUES
(12, 6, 2, NULL, 1),
(13, 6, 7, NULL, 0.67),
(14, 6, 1, NULL, 0.33),
(15, 6, 6, NULL, 0.33),
(16, 6, 8, NULL, 0.33),
(17, 7, 2, NULL, 1),
(18, 7, 7, NULL, 1),
(19, 7, 6, NULL, 0.5),
(20, 7, 8, NULL, 0.5),
(21, 8, 2, NULL, 1),
(22, 8, 7, NULL, 1),
(23, 8, 6, NULL, 0.5),
(24, 8, 8, NULL, 0.5),
(25, 9, 2, NULL, 1),
(26, 9, 7, NULL, 1),
(27, 9, 6, NULL, 0.5),
(28, 9, 8, NULL, 0.5),
(29, 10, 2, NULL, 1),
(30, 10, 7, NULL, 1),
(31, 11, 2, NULL, 1),
(32, 11, 7, NULL, 1),
(33, 11, 8, NULL, 1),
(34, 12, 3, NULL, 1),
(35, 12, 4, NULL, 1),
(36, 12, 2, NULL, 0.5),
(37, 12, 6, NULL, 0.5),
(38, 12, 7, NULL, 0.5),
(39, 12, 8, NULL, 0.5),
(40, 13, 3, NULL, 1),
(41, 13, 4, NULL, 1),
(42, 14, 3, NULL, 1),
(43, 14, 4, NULL, 1),
(44, 15, 3, NULL, 1),
(45, 15, 4, NULL, 1),
(46, 16, 3, NULL, 1),
(47, 16, 4, NULL, 1),
(48, 17, 3, NULL, 1),
(49, 17, 4, NULL, 1),
(50, 18, 3, NULL, 1),
(51, 18, 4, NULL, 1),
(52, 19, 3, NULL, 1),
(53, 19, 4, NULL, 1),
(54, 20, 2, NULL, 1),
(55, 20, 7, NULL, 1),
(56, 20, 1, NULL, 1),
(57, 21, 2, NULL, 1),
(58, 21, 7, NULL, 1),
(59, 21, 1, NULL, 1),
(60, 22, 7, NULL, 0.19),
(61, 22, 1, NULL, 0.15),
(62, 22, 2, NULL, 0.15),
(63, 23, 7, NULL, 0.19),
(64, 23, 1, NULL, 0.15),
(65, 23, 2, NULL, 0.15),
(66, 24, 7, NULL, 0.19),
(67, 24, 1, NULL, 0.15),
(68, 24, 2, NULL, 0.15),
(69, 25, 7, NULL, 0.19),
(70, 25, 1, NULL, 0.15),
(71, 25, 2, NULL, 0.15),
(72, 26, 7, NULL, 0.19),
(73, 26, 1, NULL, 0.15),
(74, 26, 2, NULL, 0.15),
(75, 27, 7, NULL, 0.19),
(76, 27, 1, NULL, 0.15),
(77, 27, 2, NULL, 0.15),
(78, 28, 7, NULL, 0.19),
(79, 28, 1, NULL, 0.15),
(80, 28, 2, NULL, 0.15),
(81, 29, 7, NULL, 0.19),
(82, 29, 1, NULL, 0.15),
(83, 29, 2, NULL, 0.15),
(84, 30, 7, NULL, 0.19),
(85, 30, 1, NULL, 0.15),
(86, 30, 2, NULL, 0.15),
(87, 31, 7, NULL, 0.19),
(88, 31, 2, NULL, 0.15),
(89, 32, 7, NULL, 0.38),
(90, 32, 2, NULL, 0.3),
(91, 32, 6, NULL, 0.19),
(92, 32, 8, NULL, 0.19),
(93, 33, 7, NULL, 0.19),
(94, 33, 2, NULL, 0.15),
(95, 34, 2, NULL, 0.3),
(96, 34, 7, NULL, 0.19),
(97, 34, 1, NULL, 0.15),
(98, 35, 2, NULL, 0.3),
(99, 35, 7, NULL, 0.19),
(100, 35, 1, NULL, 0.15),
(101, 36, 3, NULL, 0.38),
(102, 36, 23, NULL, 0.38),
(103, 36, 25, NULL, 0.38),
(104, 36, 14, NULL, 0.25),
(105, 36, 15, NULL, 0.25),
(106, 36, 4, NULL, 0.15);

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
  `id` int(11) NOT NULL,
  `prescription_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `dosage` text DEFAULT NULL,
  `usage_time` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescription_products`
--

INSERT INTO `prescription_products` (`id`, `prescription_id`, `product_id`, `quantity`, `dosage`, `usage_time`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 10, '500mg', '2 viên/ngày khi đau đầu', '2025-05-28 07:16:52', '2025-05-28 14:16:52'),
(2, 1, NULL, 7, '5mg', '1 viên/ngày', '2025-05-28 07:16:52', '2025-05-28 14:16:52'),
(3, 2, NULL, 14, '500mg', '2 lần/ngày', '2025-05-28 07:16:52', '2025-05-28 14:16:52'),
(4, 2, NULL, 7, '2mg', '1 lần/ngày trước ăn sáng', '2025-05-28 07:16:52', '2025-05-28 14:16:52'),
(5, 3, NULL, 7, '50mg', '1 viên mỗi sáng', '2025-05-28 07:16:52', '2025-05-28 14:16:52'),
(6, 3, NULL, 7, '100mg', '1 viên/ngày', '2025-05-28 07:16:52', '2025-05-28 14:16:52');

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

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `category_id`, `name`, `description`, `price`, `stock`, `image_url`, `created_at`, `updated_at`, `is_active`) VALUES
(1, 1, 'Paracetamol 500mg', 'Thuốc hạ sốt, giảm đau thường dùng.', 15000, 100, 'https://example.com/images/paracetamol.jpg', '2025-05-28 07:02:02', '2025-05-28 14:02:02', 1),
(2, 1, 'Amoxicillin 500mg', 'Kháng sinh phổ rộng nhóm penicillin.', 28000, 60, 'https://example.com/images/amoxicillin.jpg', '2025-05-28 07:02:02', '2025-05-28 14:02:02', 1),
(3, 2, 'Vitamin C 1000mg', 'Hỗ trợ tăng cường đề kháng.', 50000, 200, 'https://example.com/images/vitaminC.jpg', '2025-05-28 07:02:02', '2025-05-28 14:02:02', 1),
(4, 3, 'Máy đo huyết áp điện tử', 'Thiết bị đo huyết áp tại nhà.', 650000, 15, 'https://example.com/images/blood_pressure_monitor.jpg', '2025-05-28 07:02:02', '2025-05-28 14:02:02', 1),
(5, 4, 'Khẩu trang y tế 4 lớp', 'Hộp 50 cái, đạt chuẩn kháng khuẩn.', 40000, 500, 'https://example.com/images/face_mask.jpg', '2025-05-28 07:02:02', '2025-05-28 14:02:02', 1);

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

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`category_id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Thuốc điều trị', 'Các loại thuốc dùng để điều trị bệnh lý.', '2025-05-28 07:02:01', '2025-05-28 14:02:01'),
(2, 'Thực phẩm chức năng', 'Sản phẩm hỗ trợ tăng cường sức khỏe.', '2025-05-28 07:02:01', '2025-05-28 14:02:01'),
(3, 'Thiết bị y tế', 'Các thiết bị và dụng cụ y tế sử dụng trong chẩn đoán và điều trị.', '2025-05-28 07:02:01', '2025-05-28 14:02:01'),
(4, 'Vật tư tiêu hao', 'Găng tay, khẩu trang, bông băng,... sử dụng một lần.', '2025-05-28 07:02:01', '2025-05-28 14:02:01');

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

--
-- Dumping data for table `product_reviews`
--

INSERT INTO `product_reviews` (`review_id`, `product_id`, `user_id`, `rating`, `comment`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 5, 'Thuốc giảm đau hiệu quả, ít tác dụng phụ.', '2025-05-28 07:17:08', '2025-05-28 14:17:08'),
(2, 2, 2, 4, 'Tốt nhưng gây buồn nôn nhẹ.', '2025-05-28 07:17:08', '2025-05-28 14:17:08'),
(3, 4, 1, 5, 'Dễ sử dụng và rất chính xác.', '2025-05-28 07:17:08', '2025-05-28 14:17:08'),
(4, 3, 3, 4, 'Khá ổn để tăng sức đề kháng. Đóng gói đẹp.', '2025-05-28 07:17:08', '2025-05-28 14:17:08');

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
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `full_description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `price_from` decimal(16,0) DEFAULT NULL,
  `price_to` decimal(16,0) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_emergency` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `category_id`, `name`, `slug`, `short_description`, `full_description`, `icon`, `image`, `price_from`, `price_to`, `is_featured`, `is_emergency`, `is_active`, `display_order`, `created_at`, `updated_at`) VALUES
(1, 1, 'Khám Tổng Quát', 'kham-tong-quat', 'Khám sức khỏe định kỳ và tầm soát các bệnh lý thường gặp', NULL, NULL, NULL, 200000, 500000, 0, 0, 1, 0, '2025-06-04 06:33:33', '2025-06-04 06:33:33'),
(2, 2, 'Khám Tim Mạch', 'kham-tim-mach', 'Chẩn đoán và điều trị các bệnh lý tim mạch với trang thiết bị hiện đại', NULL, NULL, NULL, 300000, 2000000, 1, 0, 1, 0, '2025-06-04 06:33:33', '2025-06-04 06:33:33'),
(3, 3, 'Khám Tiêu Hóa', 'kham-tieu-hoa', 'Chẩn đoán và điều trị các bệnh lý về đường tiêu hóa, gan mật', NULL, NULL, NULL, 250000, 1500000, 0, 0, 1, 0, '2025-06-04 06:33:33', '2025-06-04 06:33:33'),
(4, 6, 'Dịch Vụ Cấp Cứu', 'dich-vu-cap-cuu', 'Dịch vụ cấp cứu 24/7 với đội ngũ y bác sĩ luôn sẵn sàng', NULL, NULL, NULL, NULL, NULL, 0, 1, 1, 0, '2025-06-04 06:33:33', '2025-06-04 06:33:33');

-- --------------------------------------------------------

--
-- Table structure for table `service_categories`
--

CREATE TABLE `service_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `icon` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_categories`
--

INSERT INTO `service_categories` (`id`, `name`, `slug`, `icon`, `description`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Khám Tổng Quát', 'kham-tong-quat', 'fas fa-stethoscope', 'Dịch vụ khám sức khỏe tổng quát và tầm soát bệnh', 0, 1, '2025-06-04 06:33:25', '2025-06-04 06:33:25'),
(2, 'Tim Mạch', 'tim-mach', 'fas fa-heartbeat', 'Chẩn đoán và điều trị các bệnh lý tim mạch', 0, 1, '2025-06-04 06:33:25', '2025-06-04 06:33:25'),
(3, 'Tiêu Hóa', 'tieu-hoa', 'fas fa-prescription-bottle-alt', 'Điều trị các bệnh về đường tiêu hóa', 0, 1, '2025-06-04 06:33:25', '2025-06-04 06:33:25'),
(4, 'Thần Kinh', 'than-kinh', 'fas fa-brain', 'Điều trị các bệnh lý thần kinh', 0, 1, '2025-06-04 06:33:25', '2025-06-04 06:33:25'),
(5, 'Chấn Thương Chỉnh Hình', 'chan-thuong-chinh-hinh', 'fas fa-bone', 'Điều trị chấn thương và bệnh lý xương khớp', 0, 1, '2025-06-04 06:33:25', '2025-06-04 06:33:25'),
(6, 'Cấp Cứu', 'cap-cuu', 'fas fa-ambulance', 'Dịch vụ cấp cứu 24/7', 0, 1, '2025-06-04 06:33:25', '2025-06-04 06:33:25');

-- --------------------------------------------------------

--
-- Table structure for table `service_features`
--

CREATE TABLE `service_features` (
  `id` int(11) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `feature_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_features`
--

INSERT INTO `service_features` (`id`, `service_id`, `feature_name`, `description`, `icon`, `display_order`, `created_at`) VALUES
(1, 1, 'Khám lâm sàng toàn diện', NULL, NULL, 0, '2025-06-04 06:33:41'),
(2, 1, 'Xét nghiệm máu cơ bản', NULL, NULL, 0, '2025-06-04 06:33:41'),
(3, 1, 'Đo huyết áp, nhịp tim', NULL, NULL, 0, '2025-06-04 06:33:41'),
(4, 1, 'Tư vấn dinh dưỡng', NULL, NULL, 0, '2025-06-04 06:33:41'),
(5, 2, 'Siêu âm tim', NULL, NULL, 0, '2025-06-04 06:33:41'),
(6, 2, 'Điện tim', NULL, NULL, 0, '2025-06-04 06:33:41'),
(7, 2, 'Holter 24h', NULL, NULL, 0, '2025-06-04 06:33:41'),
(8, 2, 'Thăm dò chức năng tim', NULL, NULL, 0, '2025-06-04 06:33:41');

-- --------------------------------------------------------

--
-- Table structure for table `service_packages`
--

CREATE TABLE `service_packages` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(16,0) DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_packages`
--

INSERT INTO `service_packages` (`id`, `name`, `slug`, `description`, `price`, `duration`, `is_featured`, `is_active`, `display_order`, `created_at`, `updated_at`) VALUES
(1, 'Gói Cơ Bản', 'goi-co-ban', 'Gói khám sức khỏe cơ bản', 1500000, '/lần', 0, 1, 0, '2025-06-04 06:33:50', '2025-06-04 06:33:50'),
(2, 'Gói Nâng Cao', 'goi-nang-cao', 'Gói khám sức khỏe nâng cao', 3500000, '/lần', 1, 1, 0, '2025-06-04 06:33:50', '2025-06-04 06:33:50'),
(3, 'Gói Cao Cấp', 'goi-cao-cap', 'Gói khám sức khỏe cao cấp', 6500000, '/lần', 0, 1, 0, '2025-06-04 06:33:50', '2025-06-04 06:33:50');

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
  `alias` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `followup_question` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `symptoms`
--

INSERT INTO `symptoms` (`symptom_id`, `name`, `alias`, `description`, `followup_question`, `created_at`, `updated_at`) VALUES
(1, 'Đau đầu', 'đau đầu,căng đầu,nhức đầu', 'Cảm giác đau ở vùng đầu hoặc cổ', 'Cơn đau đầu xuất hiện vào lúc nào trong ngày (sáng, trưa, tối)? Mức độ đau từ nhẹ đến dữ dội ra sao?', '2025-06-10 07:34:51', '2025-06-12 20:25:04'),
(2, 'Khó thở', 'khó hít thở,ngộp thở,thở không ra hơi', 'Khó khăn trong việc hít thở bình thường', 'Bạn thấy khó thở khi nghỉ ngơi, khi vận động hay vào ban đêm?', '2025-06-10 07:34:51', '2025-06-12 20:15:07'),
(3, 'Buồn nôn', 'muốn ói,nôn nao,ói mửa,khó chịu bụng, muốn nôn', 'Cảm giác muốn nôn mửa', 'Bạn cảm thấy buồn nôn vào thời điểm nào trong ngày? Có thường xảy ra sau khi ăn hoặc khi ngửi mùi mạnh không?', '2025-06-10 07:34:51', '2025-06-20 19:21:18'),
(4, 'Sốt', 'nóng sốt,sốt cao,sốt nhẹ,thân nhiệt cao', 'Nhiệt độ cơ thể cao hơn bình thường', 'Bạn bị sốt liên tục hay theo từng cơn? Nhiệt độ cao nhất bạn đo được là bao nhiêu?', '2025-06-10 07:34:51', '2025-06-12 20:16:02'),
(5, 'Tức ngực', 'đau ngực,nặng ngực,ép ngực', 'Cảm giác đau hoặc áp lực ở ngực', 'Bạn cảm thấy tức ngực vào lúc nào? Có thay đổi theo tư thế hoặc khi gắng sức không?', '2025-06-10 07:34:51', '2025-06-12 20:25:04'),
(6, 'Mệt mỏi', 'mệt,uể oải,đuối sức,yếu người', 'Cảm giác kiệt sức, thiếu năng lượng', 'Bạn cảm thấy mệt theo kiểu uể oải, buồn ngủ, hay kiệt sức sau khi làm gì đó? Tình trạng này kéo dài bao lâu rồi?', '2025-06-10 07:34:51', '2025-06-23 14:49:17'),
(7, 'Co giật', 'giật cơ,co rút,co cứng', 'Chuyển động không kiểm soát của cơ', 'Cơn co giật xảy ra đột ngột hay có dấu hiệu báo trước? Kéo dài bao lâu và có kèm mất ý thức không?', '2025-06-10 07:34:51', '2025-06-12 18:49:23'),
(8, 'Ngứa da', 'ngứa,ngứa ngáy,muốn gãi', 'Cảm giác châm chích khiến muốn gãi', 'Bạn bị ngứa ở vùng nào trên cơ thể (tay, chân, lưng…)? Có kèm nổi mẩn đỏ, bong tróc da hoặc lan rộng không?', '2025-06-10 07:34:51', '2025-06-12 18:49:23'),
(9, 'Phát ban', 'mẩn đỏ,nổi mẩn,da dị ứng', 'Vùng da bị nổi mẩn đỏ hoặc sưng', 'Phát ban xuất hiện lần đầu vào thời điểm nào? Có ngứa, đau hay lan rộng sang vùng da khác không?', '2025-06-10 07:34:51', '2025-06-12 18:49:23'),
(10, 'Chán ăn', 'không thèm ăn,bỏ ăn,ăn không ngon miệng', 'Mất cảm giác thèm ăn, không muốn ăn uống', 'Bạn chán ăn trong bao lâu? Có thay đổi cân nặng hoặc cảm thấy đắng miệng, đầy bụng không?', '2025-06-10 07:34:51', '2025-06-12 18:49:23'),
(11, 'Ho', 'ho khan,ho có đờm,ho dữ dội', 'Phản xạ đẩy không khí ra khỏi phổi để làm sạch đường hô hấp', 'Cơn ho xảy ra vào thời điểm nào trong ngày (sáng, trưa, tối)? Có tệ hơn khi bạn nằm xuống, vận động hoặc hít phải không khí lạnh không?', '2025-06-10 07:34:51', '2025-06-12 18:49:23'),
(12, 'Hắt hơi', 'hắt xì,hắt xì hơi,nhảy mũi', 'Phản xạ mạnh của mũi để đẩy chất gây kích ứng ra ngoài', 'Bạn hắt hơi thường xuyên vào thời gian nào? Có kèm theo chảy nước mũi hay ngứa mắt không?', '2025-06-10 07:34:51', '2025-06-12 18:49:23'),
(13, 'Chảy nước mũi', 'nước mũi,nước mũi chảy,chảy dịch mũi, sổ mũi', 'Dịch nhầy chảy ra từ mũi do viêm hoặc dị ứng', 'Dịch mũi có màu gì (trong, vàng, xanh)? Có kèm theo nghẹt mũi hoặc mùi lạ không?', '2025-06-10 07:34:51', '2025-06-12 20:54:29'),
(14, 'Đau họng', 'rát họng,viêm họng,ngứa họng', 'Cảm giác đau hoặc rát ở vùng họng', 'Bạn đau họng trong hoàn cảnh nào (nuốt, nói chuyện...)? Cảm giác đau kéo dài bao lâu?', '2025-06-10 07:34:51', '2025-06-12 20:25:04'),
(15, 'Khó nuốt', 'nuốt đau,khó ăn,vướng cổ họng', 'Cảm giác vướng hoặc đau khi nuốt thức ăn hoặc nước', 'Bạn cảm thấy khó nuốt với loại thức ăn nào (cứng, mềm, lỏng)? Cảm giác có bị nghẹn không?', '2025-06-10 07:34:51', '2025-06-12 20:25:05'),
(16, 'Đau bụng', 'đầy bụng,đau bụng dưới,đau bụng trên', 'Cảm giác khó chịu hoặc đau ở vùng bụng', 'Bạn đau bụng ở vùng nào (trên, dưới, bên trái, bên phải)? Cơn đau có lan sang nơi khác hoặc liên tục không?', '2025-06-10 07:34:51', '2025-06-12 18:49:23'),
(17, 'Tiêu chảy', 'tiêu lỏng,phân lỏng,đi cầu nhiều', 'Đi ngoài phân lỏng, thường xuyên', 'Bạn bị tiêu chảy bao nhiêu lần mỗi ngày? Phân có lẫn máu, chất nhầy hoặc có mùi bất thường không?', '2025-06-10 07:34:51', '2025-06-12 18:49:23'),
(18, 'Táo bón', 'bón,khó đi ngoài,ít đi cầu, khó đi cầu', 'Đi đại tiện khó khăn hoặc không thường xuyên', 'Bạn bị táo bón trong bao lâu? Có cảm thấy đau khi đi ngoài hoặc phân khô cứng không?', '2025-06-10 07:34:51', '2025-06-12 23:00:50'),
(19, 'Chóng mặt', 'chóng mặt,quay cuồng,mất thăng bằng,đầu quay', 'Cảm giác quay cuồng, mất thăng bằng hoặc như đang bị xoay vòng, thường kèm cảm giác muốn ngã.', 'Bạn cảm thấy chóng mặt vào thời điểm nào? Có xuất hiện khi thay đổi tư thế, đứng lâu, hoặc sau khi ngủ dậy không?', '2025-06-10 07:34:51', '2025-06-18 20:32:25'),
(20, 'Đổ mồ hôi nhiều', 'ra mồ hôi,nhiều mồ hôi,ướt người, Đổ mồ hôi nhiều', 'Ra mồ hôi quá mức, không do vận động', 'Bạn đổ mồ hôi nhiều vào thời điểm nào? Tình trạng này có lặp đi lặp lại không?', '2025-06-10 07:34:51', '2025-06-16 23:22:35'),
(21, 'Run tay chân', 'tay chân run,rung người,run rẩy', 'Chuyển động không tự chủ ở tay hoặc chân', 'Tay chân bạn run khi nghỉ ngơi, khi thực hiện việc gì đó hay cả hai? Run có tăng khi lo lắng không?', '2025-06-10 07:34:51', '2025-06-12 18:49:23'),
(22, 'Khó ngủ', 'mất ngủ,khó ngủ,khó chợp mắt', 'Gặp vấn đề khi ngủ hoặc ngủ không ngon giấc', 'Bạn khó ngủ vì lý do gì (lo lắng, đau nhức, không rõ lý do)? Tình trạng này kéo dài bao lâu rồi?', '2025-06-10 07:34:51', '2025-06-12 18:49:23'),
(23, 'Thở gấp', 'thở nhanh,thở gấp,gấp gáp', 'Hơi thở nhanh, ngắn do thiếu oxy', 'Bạn cảm thấy thở gấp trong hoàn cảnh nào? Có xảy ra khi vận động hoặc khi hồi hộp không?', '2025-06-10 07:34:51', '2025-06-12 20:25:05'),
(24, 'Tim đập nhanh', 'tim nhanh,đánh trống ngực,tim đập mạnh', 'Nhịp tim tăng bất thường, có thể do lo âu hoặc bệnh lý', 'Bạn thường cảm nhận tim đập nhanh vào thời điểm nào trong ngày? Tình trạng kéo dài bao lâu?', '2025-06-10 07:34:51', '2025-06-12 20:25:05'),
(25, 'Tê tay chân', 'tê bì,châm chích,mất cảm giác tay chân', 'Mất cảm giác hoặc cảm giác châm chích ở tay hoặc chân', 'Bạn cảm thấy tê tay chân ở vùng nào? Có lan rộng ra các khu vực khác không?', '2025-06-10 07:34:51', '2025-06-12 20:25:05'),
(26, 'Hoa mắt', 'hoa mắt,choáng nhẹ,thoáng mờ mắt,mắt tối sầm', 'Cảm giác mờ mắt thoáng qua, mắt tối sầm hoặc mất thị lực tạm thời trong vài giây, thường liên quan đến huyết áp hoặc thiếu máu.', 'Bạn cảm thấy hoa mắt vào lúc nào? Có kèm theo mất tập trung, mệt mỏi, hoặc sau khi thay đổi tư thế không?', '2025-06-12 13:25:47', '2025-06-18 20:32:25'),
(27, 'Nôn mửa', 'nôn ói,nôn nhiều', 'Hành động đẩy mạnh chất trong dạ dày ra ngoài qua đường miệng', 'Bạn nôn mửa bao nhiêu lần trong ngày? Có liên quan đến bữa ăn hay mùi vị nào không?', '2025-06-12 13:25:47', '2025-06-12 20:25:47'),
(28, 'Khàn giọng', 'giọng khàn,khó nói', 'Sự thay đổi trong giọng nói, thường trở nên trầm và khô', 'Bạn bị khàn giọng trong bao lâu? Có ảnh hưởng đến việc nói chuyện hàng ngày không?', '2025-06-12 13:25:47', '2025-06-12 20:25:47'),
(29, 'Yếu cơ', 'yếu sức,yếu cơ,bại cơ', 'Giảm khả năng vận động hoặc sức mạnh cơ bắp', 'Bạn cảm thấy yếu ở tay, chân hay toàn thân? Có trở ngại khi làm các hoạt động thường ngày không?', '2025-06-12 13:25:47', '2025-06-12 20:25:47'),
(30, 'Chóng mặt khi đứng dậy', 'choáng khi đứng,chóng mặt tư thế', 'Cảm giác choáng váng khi thay đổi tư thế đứng lên', 'Bạn thường cảm thấy choáng khi đứng dậy hay ngồi dậy đột ngột không?', '2025-06-18 14:19:11', '2025-06-18 21:19:11'),
(31, 'Khò khè', 'thở rít,khò khè', 'Âm thanh rít khi thở, thường gặp khi đường thở bị hẹp', 'Bạn nghe tiếng khò khè vào lúc nào trong ngày hoặc khi làm gì?', '2025-06-18 14:19:11', '2025-06-18 21:19:11'),
(32, 'Ợ nóng', 'nóng rát ngực,ợ chua', 'Cảm giác nóng rát từ dạ dày lên cổ họng, thường sau ăn', 'Bạn có cảm thấy nóng rát ở ngực sau khi ăn không? Có bị vào ban đêm không?', '2025-06-18 14:19:11', '2025-06-18 21:19:11'),
(33, 'Vàng da', 'vàng da,vàng mắt', 'Da và mắt có màu vàng do rối loạn chức năng gan', 'Bạn có nhận thấy da hoặc lòng trắng mắt chuyển vàng trong thời gian gần đây không?', '2025-06-18 14:19:11', '2025-06-18 21:19:11'),
(34, 'Cảm giác vô vọng', 'chán nản,vô vọng', 'Tâm trạng tiêu cực kéo dài, mất niềm tin vào tương lai', 'Bạn có thường cảm thấy mọi thứ đều vô ích hoặc không có lối thoát không?', '2025-06-18 14:19:11', '2025-06-18 21:19:11'),
(35, 'Khát nước liên tục', 'khát nhiều,uống nhiều nước', 'Cảm giác khát nước kéo dài không rõ lý do', 'Bạn cảm thấy khát thường xuyên dù đã uống đủ nước chưa?', '2025-06-18 14:19:11', '2025-06-18 21:19:11'),
(36, 'Đau khớp đột ngột', 'đau khớp ngón chân,cơn gút', 'Đau dữ dội và sưng ở khớp, thường là ngón chân cái', 'Cơn đau bắt đầu ở khớp nào? Có sưng đỏ và đau nhiều vào ban đêm không?', '2025-06-18 14:19:11', '2025-06-18 21:19:11'),
(37, 'Cứng khớp buổi sáng', 'khớp cứng,khó cử động', 'Khó cử động khớp vào buổi sáng hoặc sau khi nghỉ ngơi', 'Bạn có bị cứng khớp vào sáng sớm không? Tình trạng kéo dài bao lâu?', '2025-06-18 14:19:11', '2025-06-18 21:19:11'),
(38, 'Đỏ mắt', 'mắt đỏ,viêm mắt', 'Mắt bị đỏ do giãn mạch máu kết mạc', 'Bạn bị đỏ mắt một bên hay hai bên? Có chảy ghèn hoặc cảm giác xốn cộm không?', '2025-06-18 14:19:11', '2025-06-18 21:19:11'),
(39, 'Đau cơ', 'đau bắp thịt,đau cơ', 'Cảm giác đau ở cơ bắp, đặc biệt khi vận động', 'Bạn đau cơ ở vùng nào? Cơn đau có giảm khi nghỉ ngơi không?', '2025-06-18 14:19:11', '2025-06-18 21:19:11'),
(40, 'Đau lan từ lưng xuống chân', 'đau lưng lan chân,thần kinh tọa', 'Cơn đau bắt nguồn từ lưng dưới và lan theo dây thần kinh xuống chân', 'Cơn đau có lan xuống mông, đùi, hoặc gót chân không? Có tê hay yếu cơ kèm theo không?', '2025-06-18 14:19:11', '2025-06-18 21:19:11');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','inactive','suspended') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `role_id`, `created_at`, `updated_at`, `status`) VALUES
(1, 'admin', 'admin@gmail.com', '123', 1, '2025-05-22 06:49:02', '2025-06-03 07:25:19', 'active'),
(2, 'huy', 'hoanhuy12@gmail.com', '123', 1, '2025-05-22 06:49:02', '2025-06-06 06:10:42', 'active'),
(3, 'dr.hanh', 'doctor@example.com', '123', 2, '2025-05-22 06:49:02', '2025-06-06 06:10:34', 'active'),
(4, 'vana', 'vana@example.com', '123', 3, '2025-05-22 08:38:06', '2025-06-10 08:28:14', 'active'),
(6, 'linh', 'linh@gmail.com', '123', 2, '2025-05-24 06:15:12', '2025-06-06 06:10:49', 'active');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `phone` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users_info`
--

INSERT INTO `users_info` (`id`, `user_id`, `full_name`, `gender`, `date_of_birth`, `profile_picture`, `created_at`, `updated_at`, `phone`) VALUES
(1, 1, 'Quản trị viên', 'Nam', '1990-01-01', NULL, '2025-05-22 06:49:55', '2025-05-22 06:49:55', NULL),
(2, 2, 'Hoàn Huy', 'Nam', '1999-09-09', NULL, '2025-05-22 06:49:55', '2025-05-24 07:07:40', NULL),
(3, 3, 'John Doe', 'Nam', '2000-12-01', NULL, '2025-05-22 06:49:55', '2025-05-22 06:49:55', NULL),
(4, 4, 'Nguyễn Văn A', 'Nam', '1995-08-15', NULL, '2025-05-22 08:39:27', '2025-05-22 08:39:27', NULL),
(5, 6, 'Dr.Linh', 'Nữ', '1995-08-15', NULL, '2025-05-24 06:17:47', '2025-05-24 06:17:47', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `address_id` int(11) NOT NULL,
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

INSERT INTO `user_addresses` (`address_id`, `user_id`, `address_line`, `ward`, `district`, `city`, `postal_code`, `country`, `is_default`, `created_at`, `updated_at`) VALUES
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
(44, 4, 6, '2025-06-17', 'Bệnh nhân báo cáo không có triệu chứng hay lo ngại nào vào thời điểm này.'),
(45, 4, 19, '2025-06-17', 'Bệnh nhân báo cáo không có triệu chứng hay lo ngại nào vào thời điểm này.'),
(46, 4, 11, '2025-06-17', 'Người dùng bị ho, và triệu chứng tệ hơn khi nằm xuống. Thời gian bắt đầu và nguyên nhân không rõ ràng.'),
(47, 4, 11, '2025-06-17', 'Người dùng bị ho, và triệu chứng tệ hơn khi nằm xuống. Thời gian bắt đầu và nguyên nhân không rõ ràng.'),
(48, 4, 11, '2025-06-17', 'Người dùng bị ho và cảm thấy triệu chứng tệ hơn khi nằm xuống. Thời gian bắt đầu và nguyên nhân cụ thể không rõ ràng.'),
(49, 4, 11, '2025-06-17', 'Người dùng bị ho, và triệu chứng trở nên tệ hơn khi nằm xuống. Thời gian bắt đầu và nguyên nhân không rõ ràng.'),
(50, 4, 11, '2025-06-17', 'Người dùng bị ho và cảm thấy triệu chứng tệ hơn khi nằm xuống. Thời gian bắt đầu và nguyên nhân không rõ ràng.'),
(51, 4, 11, '2025-06-17', 'Người dùng bị ho, nhưng không rõ nguyên nhân và không đề cập đến thời gian bắt đầu hay các yếu tố kích thích.'),
(52, 4, 19, '2025-06-18', 'Người dùng cảm thấy hơi chóng mặt khi đứng dậy và thỉnh thoảng có cảm giác tim đập nhanh. Người dùng không rõ nguyên nhân gây ra triệu chứng này.'),
(53, 4, 24, '2025-06-18', 'Người dùng cảm thấy hơi chóng mặt khi đứng dậy và thỉnh thoảng có cảm giác tim đập nhanh. Người dùng không rõ nguyên nhân gây ra triệu chứng này.'),
(54, 4, 19, '2025-06-18', 'Người dùng thỉnh thoảng cảm thấy hơi lảo đảo nhẹ và không chắc có phải do tim đập nhanh hay không.'),
(55, 4, 24, '2025-06-18', 'Người dùng thỉnh thoảng cảm thấy hơi lảo đảo nhẹ và không chắc có phải do tim đập nhanh hay không.'),
(56, 4, 19, '2025-06-18', 'Người dùng cảm thấy hơi lảo đảo khi đứng dậy và có lúc tim đập nhanh bất thường. Thời gian bắt đầu và nguyên nhân không rõ ràng.'),
(57, 4, 24, '2025-06-18', 'Người dùng cảm thấy hơi lảo đảo khi đứng dậy và có lúc tim đập nhanh bất thường. Thời gian bắt đầu và nguyên nhân không rõ ràng.'),
(58, 4, 19, '2025-06-18', 'Người dùng cảm thấy hơi lảo đảo khi đứng dậy và có lúc tim đập nhanh bất thường. Thời gian và nguyên nhân cụ thể không được rõ ràng.'),
(59, 4, 24, '2025-06-18', 'Người dùng cảm thấy hơi lảo đảo khi đứng dậy và có lúc tim đập nhanh bất thường. Thời gian và nguyên nhân cụ thể không được rõ ràng.'),
(60, 4, 19, '2025-06-18', 'Người dùng cảm thấy hơi lảo đảo khi đứng dậy và có lúc tim đập nhanh bất thường. Thời gian và nguyên nhân cụ thể không rõ ràng.'),
(61, 4, 24, '2025-06-18', 'Người dùng cảm thấy hơi lảo đảo khi đứng dậy và có lúc tim đập nhanh bất thường. Thời gian và nguyên nhân cụ thể không rõ ràng.'),
(62, 4, 19, '2025-06-18', 'Người dùng cảm thấy hơi lảo đảo khi đứng dậy và có lúc tim đập nhanh bất thường, nhưng không rõ nguyên nhân cụ thể.'),
(63, 4, 24, '2025-06-18', 'Người dùng cảm thấy hơi lảo đảo khi đứng dậy và có lúc tim đập nhanh bất thường, nhưng không rõ nguyên nhân cụ thể.'),
(64, 4, 19, '2025-06-18', 'Người dùng cảm thấy hơi lảo đảo khi đứng dậy và có lúc tim đập nhanh bất thường. Thời gian bắt đầu và nguyên nhân không rõ ràng.'),
(65, 4, 24, '2025-06-18', 'Người dùng cảm thấy hơi lảo đảo khi đứng dậy và có lúc tim đập nhanh bất thường. Thời gian bắt đầu và nguyên nhân không rõ ràng.'),
(66, 4, 19, '2025-06-18', 'Người dùng cảm thấy hơi lảo đảo khi đứng dậy và có lúc tim đập nhanh bất thường. Thời gian bắt đầu và nguyên nhân không rõ ràng.'),
(67, 4, 24, '2025-06-18', 'Người dùng cảm thấy hơi lảo đảo khi đứng dậy và có lúc tim đập nhanh bất thường. Thời gian bắt đầu và nguyên nhân không rõ ràng.'),
(68, 4, 19, '2025-06-18', 'Người dùng cảm thấy hơi lảo đảo khi đứng dậy và có lúc tim đập nhanh bất thường. Người dùng không rõ nguyên nhân cụ thể gây ra triệu chứng này.'),
(69, 4, 24, '2025-06-18', 'Người dùng cảm thấy hơi lảo đảo khi đứng dậy và có lúc tim đập nhanh bất thường. Người dùng không rõ nguyên nhân cụ thể gây ra triệu chứng này.'),
(70, 4, 19, '2025-06-18', 'Người dùng cảm thấy hơi lảo đảo khi đứng dậy và có lúc tim đập nhanh bất thường. Thời gian bắt đầu và nguyên nhân cụ thể không rõ ràng.'),
(71, 4, 24, '2025-06-18', 'Người dùng cảm thấy hơi lảo đảo khi đứng dậy và có lúc tim đập nhanh bất thường. Thời gian bắt đầu và nguyên nhân cụ thể không rõ ràng.'),
(72, 4, 19, '2025-06-18', 'Người dùng cảm thấy hơi lảo đảo khi đứng dậy và có lúc tim đập nhanh bất thường. Thời gian xuất hiện các triệu chứng này không rõ ràng.'),
(73, 4, 24, '2025-06-18', 'Người dùng cảm thấy hơi lảo đảo khi đứng dậy và có lúc tim đập nhanh bất thường. Thời gian xuất hiện các triệu chứng này không rõ ràng.'),
(74, 4, 19, '2025-06-18', 'Người dùng cảm thấy chóng mặt. Thông tin về thời gian bắt đầu và nguyên nhân không rõ ràng.'),
(75, 4, 19, '2025-06-18', 'Người dùng cảm thấy chóng mặt và mệt mỏi, đặc biệt là khi thay đổi tư thế nằm. Họ không rõ nguyên nhân cụ thể gây ra triệu chứng này.'),
(76, 4, 6, '2025-06-18', 'Người dùng cảm thấy chóng mặt và mệt mỏi, đặc biệt là khi thay đổi tư thế nằm. Họ không rõ nguyên nhân cụ thể gây ra triệu chứng này.'),
(77, 4, 6, '2025-06-18', 'Người dùng cảm thấy không khỏe, nhưng không rõ nguyên nhân cụ thể.'),
(78, 4, 1, '2025-06-18', 'Người dùng cảm thấy không khỏe và bị đau đầu. Thời gian bắt đầu và nguyên nhân cụ thể không rõ ràng.'),
(79, 4, 19, '2025-06-18', 'Người dùng cảm thấy đau đầu và chóng mặt khi vừa đứng dậy. Triệu chứng này xuất hiện đột ngột và không rõ nguyên nhân.'),
(80, 4, 19, '2025-06-18', 'Người dùng cảm thấy đau đầu và chóng mặt khi vừa đứng dậy. Triệu chứng này xuất hiện đột ngột và không rõ nguyên nhân.'),
(81, 4, 19, '2025-06-18', 'Người dùng cho biết bị chóng mặt và nhức đầu. Thời gian bắt đầu và nguyên nhân không rõ ràng.'),
(82, 4, 1, '2025-06-18', 'Người dùng cho biết bị chóng mặt và nhức đầu. Thời gian bắt đầu và nguyên nhân không rõ ràng.'),
(83, 4, 19, '2025-06-19', 'Người dùng bị chóng mặt và nhức đầu, nhưng không rõ nguyên nhân và thời điểm bắt đầu.'),
(84, 4, 1, '2025-06-19', 'Người dùng bị chóng mặt và nhức đầu, nhưng không rõ nguyên nhân và thời điểm bắt đầu.'),
(85, 4, 11, '2025-06-19', 'Người dùng bị ho và chảy nước mũi. Thời gian bắt đầu và nguyên nhân không rõ ràng.'),
(86, 4, 13, '2025-06-19', 'Người dùng bị ho và chảy nước mũi. Thời gian bắt đầu và nguyên nhân không rõ ràng.'),
(87, 4, 25, '2025-06-24', 'Người dùng không báo cáo triệu chứng cụ thể nào, chỉ cho biết không có thêm cảm giác nào khác như chóng mặt hay mệt mỏi.'),
(88, 4, 29, '2025-06-24', 'Người dùng không báo cáo triệu chứng cụ thể nào, chỉ cho biết không có thêm cảm giác nào khác như chóng mặt hay mệt mỏi.'),
(89, 4, 1, '2025-06-24', 'Người dùng không báo cáo triệu chứng cụ thể nào, chỉ cho biết không có thêm cảm giác nào khác như chóng mặt hay mệt mỏi.');

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
-- Indexes for table `blog_authors`
--
ALTER TABLE `blog_authors`
  ADD PRIMARY KEY (`author_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `blog_categories`
--
ALTER TABLE `blog_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD PRIMARY KEY (`post_id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `category_id` (`category_id`);

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
  ADD UNIQUE KEY `unique_disease_name` (`name`),
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
-- Indexes for table `package_features`
--
ALTER TABLE `package_features`
  ADD PRIMARY KEY (`id`),
  ADD KEY `package_id` (`package_id`);

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
  ADD KEY `prediction_id` (`prediction_id`),
  ADD KEY `disease_id` (`disease_id`);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `prescription_id` (`prescription_id`),
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
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `service_categories`
--
ALTER TABLE `service_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `service_features`
--
ALTER TABLE `service_features`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `service_packages`
--
ALTER TABLE `service_packages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `specialties`
--
ALTER TABLE `specialties`
  ADD PRIMARY KEY (`specialty_id`);

--
-- Indexes for table `symptoms`
--
ALTER TABLE `symptoms`
  ADD PRIMARY KEY (`symptom_id`),
  ADD UNIQUE KEY `unique_symptom_name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
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
  ADD PRIMARY KEY (`address_id`),
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
-- AUTO_INCREMENT for table `blog_authors`
--
ALTER TABLE `blog_authors`
  MODIFY `author_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blog_categories`
--
ALTER TABLE `blog_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blog_posts`
--
ALTER TABLE `blog_posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chatbot_knowledge_base`
--
ALTER TABLE `chatbot_knowledge_base`
  MODIFY `kb_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

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
  MODIFY `disease_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

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
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

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
-- AUTO_INCREMENT for table `package_features`
--
ALTER TABLE `package_features`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prediction_diseases`
--
ALTER TABLE `prediction_diseases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `prescription_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `prescription_products`
--
ALTER TABLE `prescription_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `service_categories`
--
ALTER TABLE `service_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `service_features`
--
ALTER TABLE `service_features`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `service_packages`
--
ALTER TABLE `service_packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `specialties`
--
ALTER TABLE `specialties`
  MODIFY `specialty_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `symptoms`
--
ALTER TABLE `symptoms`
  MODIFY `symptom_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

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
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_notifications`
--
ALTER TABLE `user_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_symptom_history`
--
ALTER TABLE `user_symptom_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

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
-- Constraints for table `blog_authors`
--
ALTER TABLE `blog_authors`
  ADD CONSTRAINT `blog_authors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD CONSTRAINT `blog_posts_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `blog_authors` (`author_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `blog_posts_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`category_id`) ON DELETE SET NULL;

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
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`address_id`) REFERENCES `user_addresses` (`address_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `package_features`
--
ALTER TABLE `package_features`
  ADD CONSTRAINT `package_features_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `service_packages` (`id`);

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
  ADD CONSTRAINT `prediction_diseases_ibfk_1` FOREIGN KEY (`prediction_id`) REFERENCES `health_predictions` (`prediction_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `prediction_diseases_ibfk_2` FOREIGN KEY (`disease_id`) REFERENCES `diseases` (`disease_id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `service_categories` (`id`);

--
-- Constraints for table `service_features`
--
ALTER TABLE `service_features`
  ADD CONSTRAINT `service_features_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`);

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
