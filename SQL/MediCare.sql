-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 05, 2025 at 08:14 AM
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
-- Database: `medicare`
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `reset_test_data_by_date` (IN `target_date` DATE)   BEGIN
  -- 1. Xóa prediction_diseases trước (dựa vào prediction_id từ health_predictions)
  DELETE FROM prediction_diseases
  WHERE prediction_id IN (
      SELECT prediction_id FROM (
          SELECT prediction_id FROM health_predictions
          WHERE DATE(prediction_date) = target_date
      ) AS sub
  );

  -- 2. Xóa bản ghi từ health_predictions trong ngày
  DELETE FROM health_predictions
  WHERE DATE(prediction_date) = target_date;

  -- 3. Xóa dữ liệu triệu chứng đã lưu trong ngày từ user_symptom_history
  DELETE FROM user_symptom_history
  WHERE record_date = target_date;
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
(5, NULL, 3, 2, 2, '2025-05-29 15:00:00', 'Đặt lịch kiểm tra tim định kỳ', 'canceled', '2025-05-24 07:15:05', '2025-05-24 14:15:05'),
(6, 4, NULL, 2, 2, '2025-07-22 09:00:00', '', 'confirmed', '2025-07-15 09:41:34', '2025-07-21 00:04:19'),
(7, 4, NULL, 1, 1, '2025-07-16 08:00:00', '', 'pending', '2025-07-15 12:15:02', '2025-07-15 19:15:02'),
(8, 1, NULL, 2, 2, '2025-07-21 09:30:00', '', 'confirmed', '2025-07-20 12:52:24', '2025-07-20 23:43:14'),
(9, 1, NULL, 2, 2, '2025-07-23 11:30:00', 'rrwe', 'confirmed', '2025-07-20 17:00:45', '2025-07-21 00:00:56'),
(10, 4, NULL, 2, 2, '2025-07-30 14:00:00', '', 'pending', '2025-07-23 16:09:31', '2025-07-23 23:09:31');

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

--
-- Dumping data for table `blog_authors`
--

INSERT INTO `blog_authors` (`author_id`, `user_id`, `name`, `avatar`, `bio`, `title`, `created_at`, `updated_at`) VALUES
(1, NULL, 'BS. Nguyễn Văn A', '/assets/images/authors/author-1.jpg', 'Bác sĩ chuyên khoa Nội tổng quát với 10 năm kinh nghiệm', 'Bác sĩ chuyên khoa', '2025-07-20 13:01:26', '2025-07-20 13:01:26'),
(2, NULL, 'BS. Trần Thị B', '/assets/images/authors/author-2.jpg', 'Bác sĩ chuyên khoa Dinh dưỡng', 'Bác sĩ dinh dưỡng', '2025-07-20 13:01:26', '2025-07-20 13:01:26'),
(3, NULL, 'BS. Lê Văn C', '/assets/images/authors/author-3.jpg', 'Bác sĩ chuyên khoa Thể thao', 'Bác sĩ thể thao', '2025-07-20 13:01:26', '2025-07-20 13:01:26'),
(4, NULL, 'BS. Phạm Thị D', '/assets/images/authors/author-4.jpg', 'Bác sĩ chuyên khoa Tâm lý', 'Bác sĩ tâm lý', '2025-07-20 13:01:26', '2025-07-20 13:01:26'),
(5, NULL, 'ThS. Hoàng Văn E', '/assets/images/authors/author-5.jpg', 'Thạc sĩ Y học cổ truyền', 'Thạc sĩ y học', '2025-07-20 13:01:26', '2025-07-20 13:01:26');

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

--
-- Dumping data for table `blog_categories`
--

INSERT INTO `blog_categories` (`category_id`, `name`, `slug`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Chăm sóc sức khỏe', 'cham-soc-suc-khoe', 'Các bài viết về chăm sóc sức khỏe tổng quát', '2025-07-20 13:01:25', '2025-07-20 13:01:25'),
(2, 'Dinh dưỡng', 'dinh-duong', 'Các bài viết về dinh dưỡng và chế độ ăn uống', '2025-07-20 13:01:25', '2025-07-20 13:01:25'),
(3, 'Thể dục', 'the-duc', 'Các bài viết về thể dục và vận động', '2025-07-20 13:01:25', '2025-07-20 13:01:25'),
(4, 'Giấc ngủ', 'giac-ngu', 'Các bài viết về giấc ngủ và sức khỏe', '2025-07-20 13:01:25', '2025-07-20 13:01:25'),
(5, 'Tâm lý', 'tam-ly', 'Các bài viết về sức khỏe tâm lý', '2025-07-20 13:01:25', '2025-07-20 13:01:25'),
(6, 'Y học', 'y-hoc', 'Các bài viết về y học và điều trị', '2025-07-20 13:01:25', '2025-07-20 13:01:25');

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

--
-- Dumping data for table `blog_posts`
--

INSERT INTO `blog_posts` (`post_id`, `author_id`, `category_id`, `title`, `slug`, `content`, `excerpt`, `featured_image`, `status`, `is_featured`, `view_count`, `created_at`, `updated_at`, `published_at`) VALUES
(1, 1, 1, '10 Cách Tăng Cường Hệ Miễn Dịch Tự Nhiên', '10-c-ch-t-ng-c-ng-h-mi-n-d-ch-t-nhi-n', '<p>Nội dung chi tiết về c&aacute;ch tăng cường hệ miễn dịch...</p>', 'Khám phá những phương pháp đơn giản nhưng hiệu quả để tăng cường hệ miễn dịch của bạn thông qua chế độ ăn uống, lối sống và các hoạt động hàng ngày...', 'https://i.pinimg.com/736x/d7/22/35/d72235c87cfe02dfcdc92c89d46096c0.jpg', 'published', 1, 3, '2025-07-20 13:01:26', '2025-07-21 06:36:37', '2025-07-20 13:01:26'),
(2, 2, 2, 'Chế Độ Ăn Uống Lành Mạnh Cho Tim Mạch', 'che-do-an-uong-lanh-manh-cho-tim-mach', '<p>Nội dung chi tiết về chế độ ăn uống tốt cho tim mạch...</p>', 'Tìm hiểu về những thực phẩm tốt cho tim mạch và cách xây dựng chế độ ăn uống khoa học...', '/assets/images/blog/post-1.jpg', 'published', 0, 0, '2025-07-20 13:01:26', '2025-07-20 13:01:26', '2025-07-20 13:01:26'),
(3, 3, 3, 'Lợi Ích Của Việc Tập Thể Dục Đều Đặn', 'loi-ich-cua-viec-tap-the-duc-deu-dan', '<p>Nội dung chi tiết về lợi ích của tập thể dục...</p>', 'Khám phá những lợi ích tuyệt vời của việc duy trì thói quen tập luyện thể dục hàng ngày...', '/assets/images/blog/post-2.jpg', 'published', 0, 0, '2025-07-20 13:01:26', '2025-07-20 13:01:26', '2025-07-20 13:01:26'),
(4, 4, 4, 'Tầm Quan Trọng Của Giấc Ngủ Chất Lượng', 'tam-quan-trong-cua-giac-ngu-chat-luong', '<p>Nội dung chi tiết về giấc ngủ...</p>', 'Hiểu rõ về tác động của giấc ngủ đến sức khỏe và cách cải thiện chất lượng giấc ngủ...', '/assets/images/blog/post-3.jpg', 'published', 0, 0, '2025-07-20 13:01:26', '2025-07-20 13:01:26', '2025-07-20 13:01:26'),
(5, 5, 5, 'Quản Lý Stress Hiệu Quả Trong Cuộc Sống', 'quan-ly-stress-hieu-qua-trong-cuoc-song', '<p>Nội dung chi tiết về quản lý stress...</p>', 'Học cách nhận biết và quản lý stress để duy trì sức khỏe tinh thần tốt...', '/assets/images/blog/post-4.jpg', 'published', 0, 0, '2025-07-20 13:01:26', '2025-07-20 13:01:26', '2025-07-20 13:01:26');

-- --------------------------------------------------------

--
-- Table structure for table `blog_tags`
--

CREATE TABLE `blog_tags` (
  `tag_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_logs`
--

INSERT INTO `chat_logs` (`chat_id`, `user_id`, `guest_id`, `intent`, `message`, `sender`, `sent_at`) VALUES
(1201, 4, NULL, 'booking_request', 'minh song o tphcm', 'user', '2025-07-12 09:53:52'),
(1202, 4, NULL, 'booking_request', 'Bạn vui lòng cho biết tên phòng khám mà bạn muốn đến để mình hỗ trợ nhé.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-12 09:53:52'),
(1203, 4, NULL, 'booking_request', 'benh vien cho ray', 'user', '2025-07-12 09:54:54'),
(1204, 4, NULL, 'booking_request', 'Xin vui lòng cho biết tên phòng khám mà bạn muốn đến.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-12 09:54:54'),
(1205, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-12 09:55:38'),
(1206, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết về vấn đề sức khỏe hoặc loại hẹn mà bạn muốn đặt không?', 'bot', '2025-07-12 09:55:38'),
(1207, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-12 09:55:49'),
(1208, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết số điện thoại của bạn không?', 'bot', '2025-07-12 09:55:49'),
(1209, 4, NULL, 'booking_request', '090111111111', 'user', '2025-07-12 09:56:01'),
(1210, 4, NULL, 'booking_request', 'Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-12 09:56:01'),
(1211, 4, NULL, 'booking_request', 'minh song o tphcm', 'user', '2025-07-12 09:56:09'),
(1212, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết tên phòng khám mà bạn muốn đến không?\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-12 09:56:09'),
(1213, 4, NULL, 'booking_request', 'bệnh viễn chợ rẫy đi', 'user', '2025-07-12 09:56:56'),
(1214, 4, NULL, 'booking_request', 'Xin vui lòng chọn phòng khám từ danh sách đã cho.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-12 09:56:56'),
(1215, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-12 10:01:15'),
(1216, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết về vấn đề sức khỏe hoặc loại hẹn bạn muốn đặt không?', 'bot', '2025-07-12 10:01:15'),
(1217, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-12 10:01:27'),
(1218, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết số điện thoại của bạn được không?', 'bot', '2025-07-12 10:01:27'),
(1219, 4, NULL, 'booking_request', '111111111', 'user', '2025-07-12 10:01:36'),
(1220, 4, NULL, 'booking_request', 'Mình cần biết bạn ở đâu để gợi ý địa điểm phù hợp nhé.', 'bot', '2025-07-12 10:01:36'),
(1221, 4, NULL, 'booking_request', 'minh song o tphcm', 'user', '2025-07-12 10:01:45'),
(1222, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết tên phòng khám hoặc bệnh viện mà bạn muốn đến không?\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-12 10:01:45'),
(1223, 4, NULL, 'booking_request', 'Bệnh viện Chợ Rẫy', 'user', '2025-07-12 10:02:10'),
(1224, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết tên phòng khám hoặc bệnh viện mà bạn muốn đến không?\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-12 10:02:10'),
(1225, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-12 10:07:55'),
(1226, 4, NULL, 'booking_request', 'Xin chào Nguyễn Văn A! Bạn có thể cho mình biết về vấn đề sức khỏe hoặc loại hẹn bạn muốn đặt không?', 'bot', '2025-07-12 10:07:56'),
(1227, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-12 10:08:08'),
(1228, 4, NULL, 'booking_request', 'Bạn vui lòng cho mình biết số điện thoại của bạn để mình có thể đặt lịch khám nhé.', 'bot', '2025-07-12 10:08:08'),
(1229, 4, NULL, 'booking_request', '090111111111', 'user', '2025-07-12 10:08:21'),
(1230, 4, NULL, 'booking_request', 'Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-12 10:08:21'),
(1231, 4, NULL, 'booking_request', 'minh song o tphcm', 'user', '2025-07-12 10:08:36'),
(1232, 4, NULL, 'booking_request', 'Bạn vui lòng cho mình biết tên phòng khám mà bạn muốn đến để mình có thể tiếp tục giúp bạn nhé.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-12 10:08:36'),
(1233, 4, NULL, 'booking_request', 'Bệnh viện Chợ Rẫy', 'user', '2025-07-12 10:08:47'),
(1234, 4, NULL, 'booking_request', 'Bạn vui lòng chọn lại phòng khám từ danh sách đã hiển thị nhé.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-12 10:08:47'),
(1235, 4, NULL, 'booking_request', 'Bệnh viện Chợ Rẫy', 'user', '2025-07-12 10:20:32'),
(1236, 4, NULL, 'booking_request', 'Bạn vui lòng chọn lại phòng khám từ danh sách đã hiển thị nhé.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-12 10:20:32'),
(1237, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-12 10:22:29'),
(1238, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết về vấn đề sức khỏe mà bạn muốn đặt lịch không?', 'bot', '2025-07-12 10:22:29'),
(1239, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-12 10:22:48'),
(1240, 4, NULL, 'booking_request', 'Bạn vui lòng cho mình biết số điện thoại của bạn để mình có thể đặt lịch khám nhé.', 'bot', '2025-07-12 10:22:49'),
(1241, 4, NULL, 'booking_request', '090111111111', 'user', '2025-07-12 10:23:30'),
(1242, 4, NULL, 'booking_request', 'Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-12 10:23:30'),
(1243, 4, NULL, 'booking_request', 'minh o thanh pho ho chi minh', 'user', '2025-07-12 10:23:43'),
(1244, 4, NULL, 'booking_request', 'Bạn vui lòng cho mình biết tên phòng khám bạn muốn đến hoặc chọn lại từ danh sách đã hiển thị nhé.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-12 10:23:43'),
(1245, 4, NULL, 'booking_request', 'Bệnh viện Chợ Rẫy đi', 'user', '2025-07-12 10:25:20'),
(1246, 4, NULL, 'booking_request', 'Vui lòng xác nhận lại tên phòng khám hoặc chọn lại từ danh sách đã hiển thị nhé.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-12 10:25:20'),
(1247, 4, NULL, 'booking_request', 'dat lich kham', 'user', '2025-07-12 10:34:22'),
(1248, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết bạn đang gặp vấn đề sức khỏe gì hoặc muốn đặt lịch khám với chuyên khoa nào không?', 'bot', '2025-07-12 10:34:23'),
(1249, 4, NULL, 'booking_request', 'dat lich kham', 'user', '2025-07-12 10:41:07'),
(1250, 4, NULL, 'booking_request', 'Bạn muốn khám bệnh về vấn đề gì? Hãy cho mình biết để mình giúp bạn đặt lịch nhé.', 'bot', '2025-07-12 10:41:07'),
(1251, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-12 10:41:22'),
(1252, 4, NULL, 'booking_request', 'Mình cần số điện thoại của bạn để tiếp tục đặt lịch khám nhé.', 'bot', '2025-07-12 10:41:22'),
(1253, 4, NULL, 'booking_request', '09011111111', 'user', '2025-07-12 10:42:47'),
(1254, 4, NULL, 'booking_request', 'Mình cần biết bạn ở đâu để gợi ý địa điểm phù hợp nhé.', 'bot', '2025-07-12 10:42:47'),
(1255, 4, NULL, 'booking_request', 'minh o tphcm', 'user', '2025-07-12 10:43:12'),
(1256, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết tên phòng khám mà bạn muốn đến không?\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-12 10:43:12'),
(1257, 4, NULL, 'booking_request', 'Bệnh viện Chợ Rẫy', 'user', '2025-07-12 10:43:31'),
(1258, 4, NULL, 'booking_request', 'Bạn vui lòng chọn lại phòng khám từ danh sách đã được hiển thị nhé.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-12 10:43:31'),
(1259, 4, NULL, 'booking_request', 'benh vien cho ray', 'user', '2025-07-12 10:52:12'),
(1260, 4, NULL, 'booking_request', 'Bạn vui lòng chọn lại phòng khám từ danh sách đã được hiển thị nhé.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-12 10:52:12'),
(1261, 4, NULL, 'booking_request', 'dat lich kham', 'user', '2025-07-12 10:52:44'),
(1262, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết bạn muốn khám về vấn đề sức khỏe nào không?', 'bot', '2025-07-12 10:52:45'),
(1263, 4, NULL, 'symptom_query', 'tim mach', 'user', '2025-07-12 10:54:44'),
(1264, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **tim mạch** có thể liên quan đến một vài nguyên nhân khác nhau. Cảm giác này có thể xuất phát từ căng thẳng, thiếu ngủ, hoặc thậm chí là sự thay đổi tư thế đột ngột. Bạn nên thử nghỉ ngơi và theo dõi cảm giác của mình thêm nhé.\n\nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau:\n\n📌 **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **tim mạch** không ổn định.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây ra cảm giác **tim mạch** không ổn định khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.\n\n📌 **Thiếu nước hoặc dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **tim mạch** không ổn định.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.\n\n—\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha ��\nNếu cần, mình có thể hỗ trợ bạn đặt lịch khám phù hợp nha.', 'bot', '2025-07-12 10:54:44'),
(1265, 4, NULL, 'booking_request', 'minh muon dat lich kham', 'user', '2025-07-12 10:54:59'),
(1266, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết bạn muốn khám về vấn đề sức khỏe nào không?', 'bot', '2025-07-12 10:54:59'),
(1267, 4, NULL, 'symptom_query', 'kham tim mach', 'user', '2025-07-12 10:56:56'),
(1268, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **kham tim mach**. Điều này có thể liên quan đến một vài nguyên nhân khác nhau. \n\nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau: \n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây ra cảm giác hồi hộp hoặc khó chịu trong ngực.  \n→ Bạn nên kiểm tra huyết áp thường xuyên và uống đủ nước.  \n\n�� **Rối loạn nhịp tim**  \nNếu tim đập không đều, bạn có thể cảm thấy lo lắng hoặc mệt mỏi.  \n→ Hãy chú ý xem tình trạng này có xảy ra thường xuyên không và cần khám bác sĩ nếu có triệu chứng nghiêm trọng.  \n\n📌 **Căng thẳng hoặc lo âu**  \nÁp lực cuộc sống có thể khiến bạn cảm thấy không thoải mái trong cơ thể.  \n→ Bạn có thể thử các kỹ thuật thư giãn như thiền hoặc yoga để giảm bớt căng thẳng.  \n\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha 🌿\n\nNếu cần, mình có thể hỗ trợ bạn đặt lịch khám phù hợp nha.', 'bot', '2025-07-12 10:56:56'),
(1269, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-12 10:57:03'),
(1270, 4, NULL, 'booking_request', 'Bạn vui lòng cho mình biết số điện thoại của bạn để mình hỗ trợ đặt lịch khám nhé.', 'bot', '2025-07-12 10:57:03'),
(1271, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-12 10:57:35'),
(1272, 4, NULL, 'booking_request', 'Bạn vui lòng cho mình biết số điện thoại của bạn để mình hỗ trợ đặt lịch khám nhé.', 'bot', '2025-07-12 10:57:36'),
(1273, 4, NULL, 'booking_request', '0901111111', 'user', '2025-07-12 10:57:50'),
(1274, 4, NULL, 'booking_request', 'Cảm ơn bạn đã cung cấp số điện thoại. Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-12 10:57:50'),
(1275, 4, NULL, 'booking_request', 'tphcm', 'user', '2025-07-12 10:58:18'),
(1276, 4, NULL, 'booking_request', 'Xin vui lòng cung cấp thêm thông tin cụ thể về tên phòng khám hoặc địa chỉ để mình có thể hỗ trợ bạn tốt hơn.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-12 10:58:18'),
(1277, 4, NULL, 'booking_request', 'benh vien cho ray', 'user', '2025-07-12 10:58:50'),
(1278, 4, NULL, 'booking_request', 'Xin vui lòng cung cấp thêm thông tin cụ thể về tên phòng khám hoặc địa chỉ để mình có thể hỗ trợ bạn tốt hơn.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-12 10:58:50'),
(1279, 4, NULL, 'booking_request', 'dat lich kham', 'user', '2025-07-12 11:01:13'),
(1280, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết về vấn đề sức khỏe mà bạn muốn khám không?', 'bot', '2025-07-12 11:01:14'),
(1281, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-12 11:02:32'),
(1282, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết số điện thoại của bạn được không?', 'bot', '2025-07-12 11:02:32'),
(1283, 4, NULL, 'booking_request', 'dat lich kham', 'user', '2025-07-12 11:02:48'),
(1284, 4, NULL, 'booking_request', 'Bạn muốn đặt lịch khám về vấn đề sức khỏe nào? Mình có thể giúp bạn tìm bác sĩ phù hợp.', 'bot', '2025-07-12 11:02:48'),
(1285, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-12 11:03:13'),
(1286, 4, NULL, 'booking_request', 'Cảm ơn bạn đã cung cấp thông tin về tình trạng sức khỏe. Bạn vui lòng cho mình biết số điện thoại của bạn được không?', 'bot', '2025-07-12 11:03:13'),
(1287, 4, NULL, 'booking_request', '0901647655', 'user', '2025-07-12 11:03:27'),
(1288, 4, NULL, 'booking_request', 'Bạn vui lòng cho mình biết bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-12 11:03:27'),
(1289, 4, NULL, 'booking_request', 'tphcm', 'user', '2025-07-12 11:03:44'),
(1290, 4, NULL, 'booking_request', 'Vui lòng cho biết tên đầy đủ hoặc địa chỉ chính xác để mình có thể tìm phòng khám phù hợp.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-12 11:03:44'),
(1291, 4, NULL, 'booking_request', 'khám ở chợ rẫy đi', 'user', '2025-07-12 11:04:08'),
(1292, 4, NULL, 'booking_request', 'Xin vui lòng xác nhận tên đầy đủ hoặc địa chỉ chính xác để mình tìm phòng khám phù hợp.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-12 11:04:08'),
(1293, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-12 11:22:56'),
(1294, 4, NULL, 'booking_request', 'Bạn muốn khám về vấn đề sức khỏe nào? Mình cần biết để giúp bạn đặt lịch khám phù hợp.', 'bot', '2025-07-12 11:22:56'),
(1295, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-12 11:23:36'),
(1296, 4, NULL, 'booking_request', 'Mình cần số điện thoại của bạn để tiếp tục đặt lịch khám nhé.', 'bot', '2025-07-12 11:23:36'),
(1297, 4, NULL, 'booking_request', '0901647111', 'user', '2025-07-12 11:23:53'),
(1298, 4, NULL, 'booking_request', 'Mình cần biết bạn ở đâu để gợi ý địa điểm phù hợp nhé.', 'bot', '2025-07-12 11:23:53'),
(1299, 4, NULL, 'booking_request', 'minh song o tphcm', 'user', '2025-07-12 11:24:01'),
(1300, 4, NULL, 'booking_request', 'Mình cần bạn xác nhận lại thông tin địa chỉ hoặc tên đầy đủ để giúp bạn đặt lịch khám nhé.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-12 11:24:01'),
(1301, 4, NULL, 'booking_request', 'Bệnh viện Chợ Rẫy', 'user', '2025-07-12 11:24:18'),
(1302, 4, NULL, 'booking_request', 'Mình đã ghi nhận thông tin của bạn, giờ mình sẽ tiếp tục giúp bạn đặt lịch khám.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-12 11:24:18'),
(1303, 4, NULL, 'booking_request', 'minh muon dat lich', 'user', '2025-07-14 10:36:11'),
(1304, 4, NULL, 'booking_request', 'Bạn muốn đặt lịch cho vấn đề sức khỏe nào? Hãy cho mình biết để mình hỗ trợ nhé.', 'bot', '2025-07-14 10:36:11'),
(1305, 4, NULL, 'booking_request', 'minh muon kham ve van de tim mach di', 'user', '2025-07-14 10:36:27'),
(1306, 4, NULL, 'booking_request', 'Cảm ơn bạn đã cung cấp thông tin về lĩnh vực khám bệnh. Bạn có thể cho mình biết số điện thoại liên lạc của bạn không?', 'bot', '2025-07-14 10:36:27'),
(1307, 4, NULL, 'booking_request', '0901111111', 'user', '2025-07-14 10:36:45'),
(1308, 4, NULL, 'booking_request', 'Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-14 10:36:45'),
(1309, 4, NULL, 'booking_request', 'tphcm', 'user', '2025-07-14 10:36:55'),
(1310, 4, NULL, 'booking_request', 'Xin vui lòng cung cấp thêm thông tin để mình giúp bạn tìm phòng khám phù hợp.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-14 10:36:55'),
(1311, 4, NULL, 'booking_request', 'benh vien cho ray di', 'user', '2025-07-14 10:37:12'),
(1312, 4, NULL, 'booking_request', 'Bạn đã chọn Bệnh viện Chợ Rẫy. Vui lòng cung cấp thêm thông tin để hoàn tất đặt lịch khám.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-14 10:37:12'),
(1313, 4, NULL, 'booking_request', 'minh muon dat lich kham', 'user', '2025-07-14 10:51:02'),
(1314, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết bạn muốn khám về vấn đề sức khỏe nào không?', 'bot', '2025-07-14 10:51:02'),
(1315, 4, NULL, 'symptom_query', 'minh muon kham tim mach', 'user', '2025-07-14 10:52:44'),
(1316, 4, NULL, 'symptom_query', 'Bạn có thể mô tả rõ hơn bạn cảm thấy gì không?', 'bot', '2025-07-14 10:52:44'),
(1317, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-14 11:03:05'),
(1318, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết về triệu chứng hoặc lý do bạn muốn đặt lịch khám không?', 'bot', '2025-07-14 11:03:06'),
(1319, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-14 11:03:14'),
(1320, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết bạn đang ở khu vực nào không để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-14 11:03:14'),
(1321, 4, NULL, 'booking_request', 'minh o tphcm', 'user', '2025-07-14 11:03:25'),
(1322, 4, NULL, 'booking_request', 'Mình cần số điện thoại của bạn để tiếp tục đặt lịch khám nhé.', 'bot', '2025-07-14 11:03:25'),
(1323, 4, NULL, 'booking_request', '0901647655', 'user', '2025-07-14 11:03:39'),
(1324, 4, NULL, 'booking_request', 'Mình cần biết bạn ở đâu để gợi ý địa điểm phù hợp nhé.', 'bot', '2025-07-14 11:03:39'),
(1325, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-14 11:41:00'),
(1326, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết bạn muốn khám về vấn đề sức khỏe gì không?', 'bot', '2025-07-14 11:41:00'),
(1327, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-14 11:41:10'),
(1328, 4, NULL, 'booking_request', 'Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-14 11:41:10'),
(1329, 4, NULL, 'booking_request', 'tphcm', 'user', '2025-07-14 11:41:21'),
(1330, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết số điện thoại của bạn không?', 'bot', '2025-07-14 11:41:21'),
(1331, 4, NULL, 'booking_request', '090111111111', 'user', '2025-07-14 11:41:38'),
(1332, 4, NULL, 'booking_request', 'Mình cần biết bạn ở khu vực nào để gợi ý địa điểm phù hợp nhé.', 'bot', '2025-07-14 11:41:38'),
(1333, 4, NULL, 'booking_request', 'tphcm', 'user', '2025-07-14 11:42:00'),
(1334, 4, NULL, 'booking_request', 'Mình đã tìm thấy Bệnh viện Chợ Rẫy cho bạn. Hãy cho mình biết khi nào bạn muốn đặt lịch nhé!\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-14 11:42:00'),
(1335, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-14 12:38:24'),
(1336, 4, NULL, 'booking_request', 'Bạn muốn đặt khám ở chuyên khoa nào? Hãy cho mình biết về vấn đề sức khỏe của bạn nhé.', 'bot', '2025-07-14 12:38:24'),
(1337, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-14 12:38:43'),
(1338, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết bạn ở khu vực nào không? Mình cần thông tin này để giúp bạn tìm phòng khám gần nhất.', 'bot', '2025-07-14 12:38:43'),
(1339, 4, NULL, 'booking_request', 'tphcm', 'user', '2025-07-14 12:38:55'),
(1340, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết số điện thoại của bạn không?', 'bot', '2025-07-14 12:38:55'),
(1341, 4, NULL, 'booking_request', '0901111111', 'user', '2025-07-14 12:39:12'),
(1342, 4, NULL, 'booking_request', 'Mình cần biết bạn ở khu vực nào để gợi ý địa điểm phù hợp nhé.', 'bot', '2025-07-14 12:39:12'),
(1343, 4, NULL, 'booking_request', 'minh muon dat lich kham', 'user', '2025-07-14 12:42:10'),
(1344, 4, NULL, 'booking_request', 'Bạn muốn đặt lịch khám ở chuyên khoa nào? Hãy cho mình biết về vấn đề sức khỏe của bạn nhé.', 'bot', '2025-07-14 12:42:10'),
(1345, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-14 12:42:29'),
(1346, 4, NULL, 'booking_request', 'Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-14 12:42:29'),
(1347, 4, NULL, 'booking_request', 'minh o tphcm', 'user', '2025-07-14 12:42:53'),
(1348, 4, NULL, 'booking_request', 'Mình cần số điện thoại của bạn để hoàn tất việc đặt lịch khám nhé.', 'bot', '2025-07-14 12:42:53'),
(1349, 4, NULL, 'booking_request', '09011111111', 'user', '2025-07-14 12:43:29'),
(1350, 4, NULL, 'booking_request', 'Mình cần biết bạn ở đâu để gợi ý địa điểm phù hợp nhé.', 'bot', '2025-07-14 12:43:29'),
(1351, 4, NULL, 'booking_request', 'minh o q6', 'user', '2025-07-14 12:45:05'),
(1352, 4, NULL, 'booking_request', 'Vui lòng cung cấp thêm thông tin như tên đầy đủ hoặc địa chỉ cụ thể hơn để mình có thể hỗ trợ tốt hơn nhé.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-14 12:45:05'),
(1353, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-14 13:00:01'),
(1354, 4, NULL, 'booking_request', 'Bạn muốn khám ở chuyên khoa nào? Ví dụ: Da liễu, Nội khoa, Ngoại khoa, Nhi khoa, Tai - Mũi - Họng, Thần kinh, Tiêu hóa, Tim mạch.', 'bot', '2025-07-14 13:00:02'),
(1355, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-14 13:00:15'),
(1356, 4, NULL, 'booking_request', 'Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-14 13:00:15'),
(1357, 4, NULL, 'booking_request', 'minh song o tphcm', 'user', '2025-07-14 13:00:26'),
(1358, 4, NULL, 'booking_request', 'Mình cần số điện thoại của bạn để hoàn tất đặt lịch khám. Bạn có thể cho mình biết được không?', 'bot', '2025-07-14 13:00:26'),
(1359, 4, NULL, 'booking_request', '090111111111', 'user', '2025-07-14 13:00:40'),
(1360, 4, NULL, 'booking_request', 'Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-14 13:00:40'),
(1361, 4, NULL, 'booking_request', 'minh o TPHCM', 'user', '2025-07-14 13:01:23'),
(1362, 4, NULL, 'booking_request', 'Mình đã tìm thấy Phòng khám cho bạn là Bệnh viện Chợ Rẫy. Bây giờ bạn có muốn chọn thời gian khám không?\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-14 13:01:23'),
(1363, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-14 13:11:42'),
(1364, 4, NULL, 'booking_request', 'Bạn muốn khám ở chuyên khoa nào? Ví dụ: Da liễu, Nội khoa, Ngoại khoa, Nhi khoa, Tai - Mũi - Họng, Thần kinh, Tiêu hóa, Tim mạch.', 'bot', '2025-07-14 13:11:42'),
(1365, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-14 13:11:55'),
(1366, 4, NULL, 'booking_request', 'Bạn vui lòng cho mình biết bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất nhé.', 'bot', '2025-07-14 13:11:55'),
(1367, 4, NULL, 'booking_request', 'minh song o tphcm', 'user', '2025-07-14 13:12:10'),
(1368, 4, NULL, 'booking_request', 'Bạn vui lòng cho mình biết số điện thoại của bạn để mình có thể đặt lịch khám nhé.', 'bot', '2025-07-14 13:12:10'),
(1369, 4, NULL, 'booking_request', '090111111111', 'user', '2025-07-14 13:12:36'),
(1370, 4, NULL, 'booking_request', 'Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất nhé.', 'bot', '2025-07-14 13:12:36'),
(1371, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-14 14:10:46'),
(1372, 4, NULL, 'booking_request', 'Bạn muốn khám ở chuyên khoa nào? Hãy cho mình biết về triệu chứng hoặc vấn đề sức khỏe của bạn nhé.', 'bot', '2025-07-14 14:10:47'),
(1373, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-14 14:11:05'),
(1374, 4, NULL, 'booking_request', 'Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-14 14:11:05'),
(1375, 4, NULL, 'booking_request', 'tphcm', 'user', '2025-07-14 14:11:20'),
(1376, 4, NULL, 'booking_request', 'Mình cần số điện thoại của bạn để hoàn tất thông tin đặt lịch khám nhé.', 'bot', '2025-07-14 14:11:20'),
(1377, 4, NULL, 'booking_request', '0901111111', 'user', '2025-07-14 14:11:42'),
(1378, 4, NULL, 'booking_request', 'Mình cần biết bạn ở đâu để gợi ý địa điểm phù hợp nhé.', 'bot', '2025-07-14 14:11:42'),
(1379, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-14 14:16:52'),
(1380, 4, NULL, 'booking_request', 'Bạn muốn khám ở chuyên khoa nào? Ví dụ: Da liễu, Ngoại khoa, Nhi khoa, Nội khoa, Tai - Mũi - Họng, Thần kinh, Tiêu hóa, hoặc Tim mạch.', 'bot', '2025-07-14 14:16:52'),
(1381, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-14 14:17:02'),
(1382, 4, NULL, 'booking_request', 'Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-14 14:17:02'),
(1383, 4, NULL, 'booking_request', 'tphcm', 'user', '2025-07-14 14:17:14'),
(1384, 4, NULL, 'booking_request', 'Bạn vui lòng cho mình biết số điện thoại của bạn để mình có thể đặt lịch khám nhé.', 'bot', '2025-07-14 14:17:14'),
(1385, 4, NULL, 'booking_request', '09011111111', 'user', '2025-07-14 14:17:27'),
(1386, 4, NULL, 'booking_request', 'Cảm ơn bạn đã cung cấp thông tin! Mình sẽ tìm phòng khám tim mạch gần bạn ở TP.HCM nhé.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-14 14:17:27'),
(1387, 4, NULL, 'booking_request', 'Bệnh viện Chợ Rẫy', 'user', '2025-07-14 14:17:51'),
(1388, 4, NULL, 'booking_request', 'Mình đã xác nhận phòng khám Bệnh viện Chợ Rẫy. Bạn có muốn chọn bác sĩ hay lịch khám không?\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-14 14:17:51'),
(1389, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-14 14:24:27'),
(1390, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết về vấn đề sức khỏe mà bạn muốn khám không?', 'bot', '2025-07-14 14:24:27'),
(1391, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-14 14:24:36'),
(1392, 4, NULL, 'booking_request', 'Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-14 14:24:36'),
(1393, 4, NULL, 'booking_request', 'minh song o tphcm', 'user', '2025-07-14 14:24:45'),
(1394, 4, NULL, 'booking_request', 'Mình cần số điện thoại của bạn để đặt lịch khám. Bạn có thể cho mình biết được không?', 'bot', '2025-07-14 14:24:45'),
(1395, 4, NULL, 'booking_request', '090111111111', 'user', '2025-07-14 14:25:04'),
(1396, 4, NULL, 'booking_request', 'Mình đã ghi nhận thông tin của bạn. Bây giờ mình sẽ tìm phòng khám tim mạch gần bạn ở TP.HCM nhé!\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-14 14:25:04'),
(1397, 4, NULL, 'booking_request', 'Bệnh viện Chợ Rẫy đi', 'user', '2025-07-14 14:26:19'),
(1398, 4, NULL, 'booking_request', 'Bạn đã chọn Bệnh viện Chợ Rẫy. Bạn có muốn biết bác sĩ hoặc lịch khám không?', 'bot', '2025-07-14 14:26:19'),
(1399, 4, NULL, 'booking_request', 'chon bac si di', 'user', '2025-07-14 14:41:00'),
(1400, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất không?', 'bot', '2025-07-14 14:41:00'),
(1401, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-14 14:41:39'),
(1402, 4, NULL, 'booking_request', 'Bạn muốn khám ở chuyên khoa nào? Ví dụ: Da liễu, Ngoại khoa, Nhi khoa, Nội khoa, Tai - Mũi - Họng, Thần kinh, Tiêu hóa, hay Tim mạch.', 'bot', '2025-07-14 14:41:39'),
(1403, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-14 14:41:53'),
(1404, 4, NULL, 'booking_request', 'Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-14 14:41:53'),
(1405, 4, NULL, 'booking_request', 'minh song o tphcm', 'user', '2025-07-14 14:42:04'),
(1406, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết số điện thoại của bạn được không?', 'bot', '2025-07-14 14:42:04'),
(1407, 4, NULL, 'booking_request', '090111111111', 'user', '2025-07-14 14:42:16'),
(1408, 4, NULL, 'booking_request', 'Thông tin của bạn đã đầy đủ! Mình sẽ giúp bạn tìm phòng khám Tim mạch gần nhất ở TP.HCM.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-14 14:42:16'),
(1409, 4, NULL, 'booking_request', 'Bệnh viện Chợ Rẫy di', 'user', '2025-07-14 14:42:27'),
(1410, 4, NULL, 'booking_request', 'Vui lòng xác nhận lại tên đầy đủ hoặc vị trí cụ thể để mình có thể giúp bạn tìm phòng khám chính xác hơn.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-14 14:42:28'),
(1411, 4, NULL, 'booking_request', 'Bệnh viện Chợ Rẫy', 'user', '2025-07-14 14:43:12'),
(1412, 4, NULL, 'booking_request', 'Bạn đã chọn Bệnh viện Chợ Rẫy. Vui lòng cho mình biết bác sĩ hoặc thời gian bạn cần đặt lịch khám nhé.', 'bot', '2025-07-14 14:43:12'),
(1413, 4, NULL, 'booking_request', 'chon bac si di', 'user', '2025-07-14 14:44:38'),
(1414, 4, NULL, 'booking_request', 'Bạn cần cho mình biết bác sĩ nào bạn muốn khám tại Bệnh viện Chợ Rẫy nhé.', 'bot', '2025-07-14 14:44:38'),
(1415, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-14 14:51:48'),
(1416, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết bạn muốn khám ở chuyên khoa nào không?', 'bot', '2025-07-14 14:51:49'),
(1417, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-14 14:52:01'),
(1418, 4, NULL, 'booking_request', 'Cảm ơn bạn đã chọn chuyên khoa Tim mạch. Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-14 14:52:01'),
(1419, 4, NULL, 'booking_request', 'minh song o tphcm', 'user', '2025-07-14 14:52:10'),
(1420, 4, NULL, 'booking_request', 'Mình cần số điện thoại của bạn để hoàn tất thông tin đặt lịch khám. Bạn có thể cho mình biết được không?', 'bot', '2025-07-14 14:52:10'),
(1421, 4, NULL, 'booking_request', '090111111111', 'user', '2025-07-14 14:52:21'),
(1422, 4, NULL, 'booking_request', 'Cảm ơn bạn đã cung cấp thông tin. Mình sẽ tìm phòng khám và bác sĩ phù hợp cho bạn.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-14 14:52:21'),
(1423, 4, NULL, 'booking_request', 'Bệnh viện Chợ Rẫy', 'user', '2025-07-14 14:52:32'),
(1424, 4, NULL, 'booking_request', 'Bạn đã chọn Bệnh viện Chợ Rẫy. Bạn muốn gặp bác sĩ nào hoặc có thời gian khám nào không?', 'bot', '2025-07-14 14:52:32'),
(1425, 4, NULL, 'booking_request', 'cho minh xem danh sach bac si di', 'user', '2025-07-14 14:52:55'),
(1426, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết bạn gặp vấn đề gì về sức khỏe để mình giúp chọn chuyên khoa thích hợp không?', 'bot', '2025-07-14 14:52:56'),
(1427, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-14 15:04:24'),
(1428, 4, NULL, 'booking_request', 'Bạn muốn đặt lịch khám ở chuyên khoa nào? Ví dụ: Da liễu, Ngoại khoa, Nhi khoa, Nội khoa, Tai - Mũi - Họng, Thần kinh, Tiêu hóa, hay Tim mạch.', 'bot', '2025-07-14 15:04:24'),
(1429, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-14 15:04:38'),
(1430, 4, NULL, 'booking_request', 'Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-14 15:04:38'),
(1431, 4, NULL, 'booking_request', 'minh song o tphcm', 'user', '2025-07-14 15:04:50'),
(1432, 4, NULL, 'booking_request', 'Mình cần số điện thoại của bạn để tiếp tục nhé.', 'bot', '2025-07-14 15:04:50'),
(1433, 4, NULL, 'booking_request', '090111111111', 'user', '2025-07-14 15:05:00'),
(1434, 4, NULL, 'booking_request', 'Cảm ơn bạn đã cung cấp thông tin. Mình sẽ giúp bạn tìm phòng khám Tim mạch gần nhất. Vui lòng chờ trong giây lát.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-14 15:05:00'),
(1435, 4, NULL, 'booking_request', 'Bệnh viện Chợ Rẫy', 'user', '2025-07-14 15:05:12'),
(1436, 4, NULL, 'booking_request', 'Bạn đã chọn Bệnh viện Chợ Rẫy. Vui lòng cho mình biết bạn muốn đặt lịch với bác sĩ nào hoặc bạn có muốn xem lịch khám không?', 'bot', '2025-07-14 15:05:12'),
(1437, 4, NULL, 'booking_request', 'cho minh xem danh sach bac si di', 'user', '2025-07-14 15:05:45'),
(1438, 4, NULL, 'booking_request', 'Bạn muốn chọn bác sĩ trước, hay xem lịch khám trước ạ? Mình sẽ hiển thị danh sách tương ứng để bạn lựa chọn nhé.', 'bot', '2025-07-14 15:05:45'),
(1439, 4, NULL, 'booking_request', 'Cho mình xem danh sách bác sĩ', 'user', '2025-07-14 15:06:45'),
(1440, 4, NULL, 'booking_request', 'Mình có bác sĩ Dr.Linh chuyên về Tim mạch. Bạn muốn chọn bác sĩ này chứ ạ?', 'bot', '2025-07-14 15:06:45'),
(1441, 4, NULL, 'booking_request', 'ok', 'user', '2025-07-14 15:09:43'),
(1442, 4, NULL, 'booking_request', 'Mình có bác sĩ Dr.Linh chuyên về Tim mạch. Bạn muốn chọn bác sĩ này chứ ạ?', 'bot', '2025-07-14 15:09:43'),
(1443, 4, NULL, 'booking_request', 'ok', 'user', '2025-07-14 15:10:05'),
(1444, 4, NULL, 'booking_request', 'Mình có bác sĩ Dr.Linh chuyên về Tim mạch. Bạn muốn chọn bác sĩ này chứ ạ?', 'bot', '2025-07-14 15:10:05'),
(1445, 4, NULL, 'booking_request', 'ok', 'user', '2025-07-14 15:20:54'),
(1446, 4, NULL, 'booking_request', 'Bạn muốn chọn bác sĩ nào trong danh sách dưới đây: Dr.Linh chuyên về Tim mạch.', 'bot', '2025-07-14 15:20:54'),
(1447, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-14 15:21:28'),
(1448, 4, NULL, 'booking_request', 'Bạn muốn khám ở chuyên khoa nào? Vui lòng cho mình biết về vấn đề sức khỏe của bạn.', 'bot', '2025-07-14 15:21:28'),
(1449, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-14 15:21:36'),
(1450, 4, NULL, 'booking_request', 'Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-14 15:21:36'),
(1451, 4, NULL, 'booking_request', 'minh song o tphcm', 'user', '2025-07-14 15:21:45'),
(1452, 4, NULL, 'booking_request', 'Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-14 15:21:45'),
(1453, 4, NULL, 'booking_request', 'minh song o tphcm', 'user', '2025-07-14 15:22:30'),
(1454, 4, NULL, 'booking_request', 'Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-14 15:22:30'),
(1455, 4, NULL, 'booking_request', 'tphcm', 'user', '2025-07-14 15:22:45'),
(1456, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết số điện thoại của bạn được không?', 'bot', '2025-07-14 15:22:45'),
(1457, 4, NULL, 'booking_request', '090111111111', 'user', '2025-07-14 15:22:56'),
(1458, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết về vấn đề sức khỏe của bạn không?', 'bot', '2025-07-14 15:22:56'),
(1459, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-14 15:23:25'),
(1460, 4, NULL, 'booking_request', 'Bạn muốn đặt lịch khám ở chuyên khoa nào? Ví dụ: Da liễu, Ngoại khoa, Nhi khoa, Nội khoa, Tai - Mũi - Họng, Thần kinh, Tiêu hóa, hoặc Tim mạch.', 'bot', '2025-07-14 15:23:25'),
(1461, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-14 15:23:44'),
(1462, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết bạn đang ở khu vực nào để mình giúp tìm phòng khám gần nhất không?', 'bot', '2025-07-14 15:23:44'),
(1463, 4, NULL, 'booking_request', 'minh song o tphcm', 'user', '2025-07-14 15:23:52'),
(1464, 4, NULL, 'booking_request', 'Bạn có thể cho mình xin số điện thoại của bạn được không?', 'bot', '2025-07-14 15:23:52'),
(1465, 4, NULL, 'booking_request', '090111111111', 'user', '2025-07-14 15:24:01'),
(1466, 4, NULL, 'booking_request', 'Cảm ơn bạn đã cung cấp thông tin. Bây giờ mình sẽ tìm phòng khám tim mạch gần bạn ở TP.HCM.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-14 15:24:01'),
(1467, 4, NULL, 'booking_request', 'Bệnh viện Chợ Rẫy', 'user', '2025-07-14 15:24:19'),
(1468, 4, NULL, 'booking_request', 'Bạn đã chọn Bệnh viện Chợ Rẫy. Bạn muốn đặt lịch hẹn với bác sĩ nào hoặc cần thông tin về lịch khám không?', 'bot', '2025-07-14 15:24:19'),
(1469, 4, NULL, 'booking_request', 'cho minh xem danh sach bac si di', 'user', '2025-07-14 15:24:31'),
(1470, 4, NULL, 'booking_request', 'Hiện tại có bác sĩ Dr.Linh tại Bệnh viện Chợ Rẫy. Bạn có muốn đặt lịch hẹn với bác sĩ này không, hay cần xem thêm bác sĩ khác?', 'bot', '2025-07-14 15:24:31'),
(1471, 4, NULL, 'booking_request', 'ok', 'user', '2025-07-14 15:26:28'),
(1472, 4, NULL, 'booking_request', 'Bạn muốn chọn bác sĩ trước, hay xem lịch khám trước ạ? Mình sẽ hiển thị danh sách tương ứng để bạn lựa chọn nhé.', 'bot', '2025-07-14 15:26:28'),
(1473, 4, NULL, 'booking_request', 'cho minh xem danh sach bac si', 'user', '2025-07-14 15:29:22'),
(1474, 4, NULL, 'booking_request', 'Dưới đây là danh sách bác sĩ tại Bệnh viện Chợ Rẫy: \n1. Dr.Linh - Bác sĩ Tim mạch từng công tác tại Viện Tim TP.HCM. Có bằng Thạc sĩ Y khoa từ Đại học Paris, Pháp. \nBạn muốn chọn bác sĩ nào?\n\n[{\'doctor_id\': 2, \'full_name\': \'Dr.Linh\', \'specialty\': \'Tim mạch\', \'biography\': \'Bác sĩ Tim mạch từng công tác tại Viện Tim TP.HCM. Có bằng Thạc sĩ Y khoa từ Đại học Paris, Pháp.\'}]Dưới đây là danh sách bác sĩ tại Bệnh viện Chợ Rẫy: \n1. Dr.Linh - Bác sĩ Tim mạch từng công tác tại Viện Tim TP.HCM. Có bằng Thạc sĩ Y khoa từ Đại học Paris, Pháp. \nBạn muốn chọn bác sĩ nào?', 'bot', '2025-07-14 15:29:22'),
(1475, 4, NULL, 'booking_request', 'ok vay cho minh dat kham bac si linh d', 'user', '2025-07-14 15:30:33'),
(1476, 4, NULL, 'booking_request', 'Bạn muốn chọn bác sĩ nào trong danh sách sau: \n1. Dr.Linh - Bác sĩ Tim mạch từng công tác tại Viện Tim TP.HCM. Có bằng Thạc sĩ Y khoa từ Đại học Paris, Pháp.\n\n[{\'doctor_id\': 2, \'full_name\': \'Dr.Linh\', \'specialty_name\': \'Tim mạch\', \'biography\': \'Bác sĩ Tim mạch từng công tác tại Viện Tim TP.HCM. Có bằng Thạc sĩ Y khoa từ Đại học Paris, Pháp.\', \'clinic_id\': \'2\'}]Bạn muốn chọn bác sĩ nào trong danh sách sau: \n1. Dr.Linh - Bác sĩ Tim mạch từng công tác tại Viện Tim TP.HCM. Có bằng Thạc sĩ Y khoa từ Đại học Paris, Pháp.', 'bot', '2025-07-14 15:30:33'),
(1477, 4, NULL, 'booking_request', 'ok vay chon dr.linh di', 'user', '2025-07-14 15:33:46'),
(1478, 4, NULL, 'booking_request', 'Bạn muốn chọn bác sĩ nào trong danh sách bác sĩ hiện có?', 'bot', '2025-07-14 15:33:46'),
(1479, 4, NULL, 'booking_request', 'bac si linh di', 'user', '2025-07-14 15:41:15'),
(1480, 4, NULL, 'booking_request', 'Bạn đã chọn bác sĩ Dr.Linh. Mình sẽ hiển thị danh sách lịch khám cho bạn lựa chọn nhé.\n\n[{\'doctor_id\': 2, \'full_name\': \'Dr.Linh\', \'specialty_name\': \'Tim mạch\', \'biography\': \'Bác sĩ Tim mạch từng công tác tại Viện Tim TP.HCM. Có bằng Thạc sĩ Y khoa từ Đại học Paris, Pháp.\', \'clinic_id\': \'2\'}]Bạn đã chọn bác sĩ Dr.Linh. Mình sẽ hiển thị danh sách lịch khám cho bạn lựa chọn nhé.', 'bot', '2025-07-14 15:41:15'),
(1481, 4, NULL, 'booking_request', 'bac si linh di', 'user', '2025-07-14 15:46:10'),
(1482, 4, NULL, 'booking_request', 'Bạn muốn chọn bác sĩ trước, hay xem lịch khám trước ạ? Mình sẽ hiển thị danh sách tương ứng để bạn lựa chọn nhé.', 'bot', '2025-07-14 15:46:10'),
(1483, 4, NULL, 'booking_request', 'chon bac si di', 'user', '2025-07-14 15:46:24'),
(1484, 4, NULL, 'booking_request', 'Bạn muốn chọn bác sĩ trước, hay xem lịch khám trước ạ? Mình sẽ hiển thị danh sách tương ứng để bạn lựa chọn nhé.', 'bot', '2025-07-14 15:46:24'),
(1485, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-14 15:55:43'),
(1486, 4, NULL, 'booking_request', 'Bạn muốn khám ở chuyên khoa nào? Ví dụ: Da liễu, Ngoại khoa, Nhi khoa, Nội khoa, Tai - Mũi - Họng, Thần kinh, Tiêu hóa, hay Tim mạch?', 'bot', '2025-07-14 15:55:43'),
(1487, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-14 15:55:55'),
(1488, 4, NULL, 'booking_request', 'Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-14 15:55:55'),
(1489, 4, NULL, 'booking_request', 'minh song o tphcm', 'user', '2025-07-14 15:56:05'),
(1490, 4, NULL, 'booking_request', 'Bạn vui lòng cho mình biết số điện thoại của bạn để mình có thể đặt lịch khám nhé.', 'bot', '2025-07-14 15:56:05'),
(1491, 4, NULL, 'booking_request', '090111111111', 'user', '2025-07-14 15:56:15'),
(1492, 4, NULL, 'booking_request', 'Cảm ơn bạn đã cung cấp thông tin. Mình sẽ giúp bạn tìm phòng khám tim mạch gần nhất ở TP.HCM.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-14 15:56:15'),
(1493, 4, NULL, 'booking_request', 'Bệnh viện Chợ Rẫ', 'user', '2025-07-14 15:56:30'),
(1494, 4, NULL, 'booking_request', 'Mình đã xác định được phòng khám của bạn là Bệnh viện Chợ Rẫy. Bạn muốn đặt lịch khám với bác sĩ nào hoặc thời gian nào?', 'bot', '2025-07-14 15:56:30'),
(1495, 4, NULL, 'booking_request', 'cho minh xem danh sach bac si di', 'user', '2025-07-14 15:56:52'),
(1496, 4, NULL, 'booking_request', 'Dưới đây là danh sách bác sĩ tại Bệnh viện Chợ Rẫy:\n\n- Dr.Linh (Bác sĩ Tim mạch từng công tác tại Viện Tim TP.HCM. Có bằng Thạc sĩ Y khoa từ Đại học Paris, Pháp.)\n\nBạn muốn chọn bác sĩ nào?\n\n[{\'doctor_id\': 2, \'full_name\': \'Dr.Linh\', \'specialty_name\': \'Tim mạch\', \'biography\': \'Bác sĩ Tim mạch từng công tác tại Viện Tim TP.HCM. Có bằng Thạc sĩ Y khoa từ Đại học Paris, Pháp.\', \'clinic_id\': \'2\'}]Dưới đây là danh sách bác sĩ tại Bệnh viện Chợ Rẫy:\n\n- Dr.Linh (Bác sĩ Tim mạch từng công tác tại Viện Tim TP.HCM. Có bằng Thạc sĩ Y khoa từ Đại học Paris, Pháp.)\n\nBạn muốn chọn bác sĩ nào?', 'bot', '2025-07-14 15:56:52'),
(1497, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-14 16:04:32'),
(1498, 4, NULL, 'booking_request', 'Bạn muốn khám ở chuyên khoa nào? Ví dụ: Da liễu, Ngoại khoa, Nhi khoa, Nội khoa, Tai - Mũi - Họng, Thần kinh, Tiêu hóa, hay Tim mạch.', 'bot', '2025-07-14 16:04:32'),
(1499, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-14 16:04:44'),
(1500, 4, NULL, 'booking_request', 'Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-14 16:04:45'),
(1501, 4, NULL, 'booking_request', 'minh song o tphcm', 'user', '2025-07-14 16:04:57'),
(1502, 4, NULL, 'booking_request', 'Bạn cần cung cấp số điện thoại của mình để mình có thể đặt lịch khám cho bạn nhé.', 'bot', '2025-07-14 16:04:57'),
(1503, 4, NULL, 'booking_request', '090111111111', 'user', '2025-07-14 16:05:06'),
(1504, 4, NULL, 'booking_request', 'Mình cần biết bạn ở khu vực nào để gợi ý địa điểm khám phù hợp nhé.', 'bot', '2025-07-14 16:05:06'),
(1505, 4, NULL, 'booking_request', 'tphcm', 'user', '2025-07-14 16:05:23'),
(1506, 4, NULL, 'booking_request', 'Mình cần biết thêm thông tin về phòng khám bạn muốn đến. Bạn có muốn mình tìm phòng khám tim mạch nào không?', 'bot', '2025-07-14 16:05:23'),
(1507, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-14 16:05:56'),
(1508, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết bạn muốn khám ở chuyên khoa nào không? Ví dụ: Da liễu, Nội khoa, Ngoại khoa, v.v.', 'bot', '2025-07-14 16:05:57'),
(1509, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-14 16:06:05'),
(1510, 4, NULL, 'booking_request', 'Bạn cần cho mình biết bạn ở đâu để mình giúp tìm phòng khám gần nhất nhé.', 'bot', '2025-07-14 16:06:05'),
(1511, 4, NULL, 'booking_request', 'minh song o tphcm', 'user', '2025-07-14 16:06:15'),
(1512, 4, NULL, 'booking_request', 'Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-14 16:06:15'),
(1513, 4, NULL, 'booking_request', 'mình ở thành phố hồ chí minh', 'user', '2025-07-14 16:06:46'),
(1514, 4, NULL, 'booking_request', 'Bạn cần cho mình biết số điện thoại của bạn để mình có thể đặt lịch khám nhé.', 'bot', '2025-07-14 16:06:46'),
(1515, 4, NULL, 'booking_request', '0901647655', 'user', '2025-07-14 16:07:07'),
(1516, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết bạn cần khám về vấn đề gì không? Mình sẽ giúp bạn xác định chuyên khoa phù hợp.', 'bot', '2025-07-14 16:07:07'),
(1517, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-14 16:13:36'),
(1518, 4, NULL, 'booking_request', 'Bạn muốn khám ở chuyên khoa nào? Ví dụ: Da liễu, Nội khoa, Ngoại khoa, v.v.', 'bot', '2025-07-14 16:13:36'),
(1519, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-14 16:14:21'),
(1520, 4, NULL, 'booking_request', 'Bạn muốn khám ở chuyên khoa nào? Mình có các chuyên khoa như: Da liễu, Ngoại khoa, Nhi khoa, Nội khoa, Tai - Mũi - Họng, Thần kinh, Tiêu hóa, và Tim mạch.', 'bot', '2025-07-14 16:14:22'),
(1521, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-14 16:14:36'),
(1522, 4, NULL, 'booking_request', 'Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-14 16:14:36'),
(1523, 4, NULL, 'booking_request', 'minh song o tphcm', 'user', '2025-07-14 16:14:46'),
(1524, 4, NULL, 'booking_request', 'Mình cần số điện thoại của bạn để hoàn tất việc đặt lịch khám. Bạn có thể cho mình biết được không?', 'bot', '2025-07-14 16:14:46'),
(1525, 4, NULL, 'booking_request', '09011111111', 'user', '2025-07-14 16:14:58'),
(1526, 4, NULL, 'booking_request', 'Cảm ơn bạn! Mình đã ghi nhận thông tin của bạn. Giờ mình sẽ tìm phòng khám Tim mạch gần bạn ở TP.HCM.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-14 16:14:58'),
(1527, 4, NULL, 'booking_request', 'Bệnh viện Chợ Rẫy', 'user', '2025-07-14 16:15:11'),
(1528, 4, NULL, 'booking_request', 'Bạn đã chọn Bệnh viện Chợ Rẫy. Mình sẽ giúp bạn tìm bác sĩ hoặc lịch khám sớm nhất.', 'bot', '2025-07-14 16:15:11'),
(1529, 4, NULL, 'booking_request', 'ok chon bac si di', 'user', '2025-07-14 16:15:37'),
(1530, 4, NULL, 'booking_request', 'Mình có bác sĩ Dr.Linh tại Bệnh viện Chợ Rẫy. Bạn muốn chọn bác sĩ này không?Mình có bác sĩ Dr.Linh tại Bệnh viện Chợ Rẫy. Bạn muốn chọn bác sĩ này không?', 'bot', '2025-07-14 16:15:37');
INSERT INTO `chat_logs` (`chat_id`, `user_id`, `guest_id`, `intent`, `message`, `sender`, `sent_at`) VALUES
(1531, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-14 16:28:31'),
(1532, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết về vấn đề sức khỏe mà bạn muốn khám không?', 'bot', '2025-07-14 16:28:31'),
(1533, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-14 16:28:42'),
(1534, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết bạn đang ở khu vực nào để mình giúp tìm phòng khám gần nhất không?', 'bot', '2025-07-14 16:28:42'),
(1535, 4, NULL, 'booking_request', 'minh song o tphcm', 'user', '2025-07-14 16:28:58'),
(1536, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết số điện thoại của bạn để mình giúp đặt lịch khám không?', 'bot', '2025-07-14 16:28:58'),
(1537, 4, NULL, 'booking_request', '0901647655', 'user', '2025-07-14 16:29:12'),
(1538, 4, NULL, 'booking_request', 'Mình đã ghi nhận thông tin của bạn. Bây giờ mình sẽ tìm phòng khám tim mạch gần bạn. Xin vui lòng chờ trong giây lát.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-14 16:29:12'),
(1539, 4, NULL, 'booking_request', 'Bệnh viện Chợ Rẫy', 'user', '2025-07-14 16:29:24'),
(1540, 4, NULL, 'booking_request', 'Mình đã ghi nhận phòng khám Bệnh viện Chợ Rẫy. Bạn có muốn chọn bác sĩ hoặc lịch khám không?', 'bot', '2025-07-14 16:29:24'),
(1541, 4, NULL, 'booking_request', 'cho minh xem danh sach bac si di', 'user', '2025-07-14 16:29:40'),
(1542, 4, NULL, 'booking_request', 'Mình có bác sĩ Tim mạch Dr.Linh. Bạn muốn chọn bác sĩ này không, hay cần xem thêm thông tin gì khác không?', 'bot', '2025-07-14 16:29:40'),
(1543, 4, NULL, 'booking_request', 'ok vay bac si linh di', 'user', '2025-07-14 16:30:04'),
(1544, 4, NULL, 'booking_request', 'Mình đã ghi nhận bác sĩ Dr.Linh. Bạn muốn chọn bác sĩ này không, hay cần xem thêm thông tin gì khác không?', 'bot', '2025-07-14 16:30:05'),
(1545, 4, NULL, 'booking_request', 'ok dr linh di', 'user', '2025-07-14 16:30:36'),
(1546, 4, NULL, 'booking_request', 'Bạn muốn chọn bác sĩ trước, hay xem lịch khám trước ạ? Mình sẽ hiển thị danh sách tương ứng để bạn lựa chọn nhé.', 'bot', '2025-07-14 16:30:36'),
(1547, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-14 16:47:23'),
(1548, 4, NULL, 'booking_request', 'Bạn muốn khám về chuyên khoa nào? Ví dụ: Da liễu, Nội khoa, Ngoại khoa,...', 'bot', '2025-07-14 16:47:24'),
(1549, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-14 16:47:40'),
(1550, 4, NULL, 'booking_request', 'Bạn vui lòng cho biết bạn đang ở khu vực nào để mình giúp tìm phòng khám gần nhất nhé.', 'bot', '2025-07-14 16:47:40'),
(1551, 4, NULL, 'booking_request', 'minh song o tphcm', 'user', '2025-07-14 16:47:50'),
(1552, 4, NULL, 'booking_request', 'Bạn vui lòng cung cấp số điện thoại của bạn để mình có thể đặt lịch khám nhé.', 'bot', '2025-07-14 16:47:50'),
(1553, 4, NULL, 'booking_request', '090111111111', 'user', '2025-07-14 16:48:16'),
(1554, 4, NULL, 'booking_request', 'Mình đã ghi nhận thông tin của bạn. Bây giờ, mình sẽ tìm các phòng khám tim mạch gần khu vực TP.HCM cho bạn nhé!\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-14 16:48:16'),
(1555, 4, NULL, 'booking_request', 'Bệnh viện Chợ Rẫy', 'user', '2025-07-14 16:48:31'),
(1556, 4, NULL, 'booking_request', 'Bạn đã chọn Bệnh viện Chợ Rẫy. Hãy cho mình biết bạn muốn đặt lịch với bác sĩ nào hoặc thời gian nào nhé!', 'bot', '2025-07-14 16:48:31'),
(1557, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-14 16:55:02'),
(1558, 4, NULL, 'booking_request', 'Bạn muốn khám về vấn đề sức khỏe nào? Mình có thể giúp bạn tìm chuyên khoa phù hợp.', 'bot', '2025-07-14 16:55:02'),
(1559, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-14 16:55:11'),
(1560, 4, NULL, 'booking_request', 'Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-14 16:55:11'),
(1561, 4, NULL, 'booking_request', 'minh song o tphcm', 'user', '2025-07-14 16:55:23'),
(1562, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết số điện thoại của bạn được không?', 'bot', '2025-07-14 16:55:23'),
(1563, 4, NULL, 'booking_request', '090111111111', 'user', '2025-07-14 16:55:35'),
(1564, 4, NULL, 'booking_request', 'Mình đã ghi nhận thông tin của bạn. Bây giờ, mình sẽ giúp bạn tìm phòng khám tim mạch gần nhất ở TP.HCM nhé!\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-14 16:55:35'),
(1565, 4, NULL, 'booking_request', 'Bệnh viện Chợ Rẫy', 'user', '2025-07-14 16:55:48'),
(1566, 4, NULL, 'booking_request', 'Mình đã ghi nhận phòng khám Bệnh viện Chợ Rẫy. Bạn muốn chọn bác sĩ nào hoặc xem lịch hẹn không?', 'bot', '2025-07-14 16:55:48'),
(1567, 4, NULL, 'booking_request', 'cho minh xem danh sach bac si di', 'user', '2025-07-14 16:56:07'),
(1568, 4, NULL, 'booking_request', 'Bạn muốn chọn bác sĩ trước, hay xem lịch khám trước ạ? Mình sẽ hiển thị danh sách tương ứng để bạn lựa chọn nhé.', 'bot', '2025-07-14 16:56:07'),
(1569, 4, NULL, 'booking_request', 'danh sach bac si', 'user', '2025-07-14 16:56:38'),
(1570, 4, NULL, 'booking_request', 'Bạn muốn chọn bác sĩ trước, hay xem lịch khám trước ạ? Mình sẽ hiển thị danh sách tương ứng để bạn lựa chọn nhé.', 'bot', '2025-07-14 16:56:38'),
(1571, 4, NULL, 'booking_request', 'cho minh xem danh sach bac si di', 'user', '2025-07-14 16:56:54'),
(1572, 4, NULL, 'booking_request', 'Bạn muốn chọn bác sĩ trước, hay xem lịch khám trước ạ? Mình sẽ hiển thị danh sách tương ứng để bạn lựa chọn nhé.', 'bot', '2025-07-14 16:56:54'),
(1573, 4, NULL, 'booking_request', 'Mình muốn chọn bác sĩ', 'user', '2025-07-14 16:57:38'),
(1574, 4, NULL, 'booking_request', 'Bạn muốn chọn bác sĩ trước, hay xem lịch khám trước ạ? Mình sẽ hiển thị danh sách tương ứng để bạn lựa chọn nhé.', 'bot', '2025-07-14 16:57:38'),
(1575, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-14 17:26:23'),
(1576, 4, NULL, 'booking_request', 'Bạn muốn khám ở chuyên khoa nào? Ví dụ: Da liễu, Ngoại khoa, Nhi khoa, Nội khoa, Tai - Mũi - Họng, Thần kinh, Tiêu hóa, hoặc Tim mạch.', 'bot', '2025-07-14 17:26:24'),
(1577, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-14 17:26:32'),
(1578, 4, NULL, 'booking_request', 'Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-14 17:26:32'),
(1579, 4, NULL, 'booking_request', 'minh song o tphcm', 'user', '2025-07-14 17:26:43'),
(1580, 4, NULL, 'booking_request', 'Bạn cần cung cấp số điện thoại của mình để mình có thể hoàn tất đặt lịch khám nhé.', 'bot', '2025-07-14 17:26:43'),
(1581, 4, NULL, 'booking_request', '090111111111', 'user', '2025-07-14 17:26:53'),
(1582, 4, NULL, 'booking_request', 'Mình đã ghi nhận thông tin của bạn. Bây giờ mình sẽ giúp bạn tìm phòng khám chuyên khoa Tim mạch gần nhất ở TP.HCM nhé.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-14 17:26:53'),
(1583, 4, NULL, 'booking_request', 'Bệnh viện Chợ Rẫy', 'user', '2025-07-14 17:27:08'),
(1584, 4, NULL, 'booking_request', 'Mình đã ghi nhận phòng khám là Bệnh viện Chợ Rẫy. Bây giờ bạn muốn chọn bác sĩ nào hay xem lịch khám?', 'bot', '2025-07-14 17:27:08'),
(1585, 4, NULL, 'booking_request', 'cho minh xem danh sach bac si di', 'user', '2025-07-14 17:27:18'),
(1586, 4, NULL, 'booking_request', 'Mình sẽ hiển thị danh sách bác sĩ cho bạn ngay bây giờ.', 'bot', '2025-07-14 17:27:18'),
(1587, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-14 17:36:09'),
(1588, 4, NULL, 'booking_request', 'Bạn muốn đặt lịch khám ở chuyên khoa nào? Ví dụ: Da liễu, Nội khoa, Ngoại khoa,...', 'bot', '2025-07-14 17:36:09'),
(1589, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-14 17:36:25'),
(1590, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết bạn đang ở khu vực nào để mình giúp tìm phòng khám gần nhất nhé?', 'bot', '2025-07-14 17:36:25'),
(1591, 4, NULL, 'booking_request', 'minh song o tphcm', 'user', '2025-07-14 17:38:26'),
(1592, 4, NULL, 'booking_request', 'Bạn có thể cho mình số điện thoại của bạn để mình hoàn tất việc đặt lịch khám không?', 'bot', '2025-07-14 17:38:26'),
(1593, 4, NULL, 'booking_request', '090111111111', 'user', '2025-07-14 17:38:36'),
(1594, 4, NULL, 'booking_request', 'Mình cần biết bạn ở khu vực nào để gợi ý địa điểm phù hợp nhé.', 'bot', '2025-07-14 17:38:36'),
(1595, 4, NULL, 'booking_request', 'minh song o tphcm', 'user', '2025-07-14 17:38:59'),
(1596, 4, NULL, 'booking_request', 'Mình cần biết bạn muốn khám tại phòng khám nào để tiếp tục nhé.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-14 17:38:59'),
(1597, 4, NULL, 'booking_request', 'Bệnh viện Chợ Rẫy', 'user', '2025-07-14 17:39:18'),
(1598, 4, NULL, 'booking_request', 'Bạn đã chọn Bệnh viện Chợ Rẫy. Xin vui lòng cho biết bạn muốn khám với bác sĩ nào hoặc thời gian khám nhé.', 'bot', '2025-07-14 17:39:18'),
(1599, 4, NULL, 'booking_request', 'cho minh xem danh sach bac si di', 'user', '2025-07-14 17:39:34'),
(1600, 4, NULL, 'booking_request', 'Bạn muốn chọn bác sĩ trước, hay xem lịch khám trước ạ? Mình sẽ hiển thị danh sách tương ứng để bạn lựa chọn nhé.', 'bot', '2025-07-14 17:39:34'),
(1601, 4, NULL, 'booking_request', 'danh sach bac si', 'user', '2025-07-14 17:40:01'),
(1602, 4, NULL, 'booking_request', 'Dưới đây là danh sách bác sĩ tại Bệnh viện Chợ Rẫy:\n- Dr.Linh: Bác sĩ Tim mạch từng công tác tại Viện Tim TP.HCM. Có bằng Thạc sĩ Y khoa từ Đại học Paris, Pháp.', 'bot', '2025-07-14 17:40:01'),
(1603, 4, NULL, 'booking_request', 'ok vay dat bac si linh di', 'user', '2025-07-14 17:41:14'),
(1604, 4, NULL, 'booking_request', 'Vui lòng xác nhận bác sĩ bạn muốn đặt lịch, ví dụ như \'Dr Linh\'.', 'bot', '2025-07-14 17:41:14'),
(1605, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-14 18:04:36'),
(1606, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết bạn muốn khám ở chuyên khoa nào không?', 'bot', '2025-07-14 18:04:36'),
(1607, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-14 18:05:20'),
(1608, 4, NULL, 'booking_request', 'Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-14 18:05:20'),
(1609, 4, NULL, 'booking_request', 'minh song o tphcm', 'user', '2025-07-14 18:05:34'),
(1610, 4, NULL, 'booking_request', 'Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-14 18:05:34'),
(1611, 4, NULL, 'booking_request', 'tphcm', 'user', '2025-07-14 18:10:10'),
(1612, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết số điện thoại của bạn không?', 'bot', '2025-07-14 18:10:10'),
(1613, 4, NULL, 'booking_request', '09011111111', 'user', '2025-07-14 18:13:07'),
(1614, 4, NULL, 'booking_request', 'Bạn muốn đặt lịch khám về vấn đề gì liên quan đến chuyên khoa Tim mạch?', 'bot', '2025-07-14 18:13:07'),
(1615, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-14 18:16:21'),
(1616, 4, NULL, 'booking_request', 'Bạn muốn khám ở chuyên khoa nào? Ví dụ: Da liễu, Ngoại khoa, Nhi khoa, Nội khoa, Tai - Mũi - Họng, Thần kinh, Tiêu hóa, hoặc Tim mạch.', 'bot', '2025-07-14 18:16:21'),
(1617, 4, NULL, 'booking_request', 'tim mach', 'user', '2025-07-14 18:16:54'),
(1618, 4, NULL, 'booking_request', 'Bạn đang ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-14 18:16:54'),
(1619, 4, NULL, 'booking_request', 'tphcm', 'user', '2025-07-14 18:17:07'),
(1620, 4, NULL, 'booking_request', 'Bạn vui lòng cho mình biết số điện thoại của bạn để mình có thể đặt lịch khám nhé.', 'bot', '2025-07-14 18:17:07'),
(1621, 4, NULL, 'booking_request', '0901647655', 'user', '2025-07-14 18:17:21'),
(1622, 4, NULL, 'booking_request', 'Mình đã ghi nhận thông tin của bạn. Bây giờ, mình sẽ tìm phòng khám Tim mạch gần bạn ở TP.HCM.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-14 18:17:21'),
(1623, 4, NULL, 'booking_request', 'Bệnh viện Chợ Rẫy', 'user', '2025-07-14 18:17:44'),
(1624, 4, NULL, 'booking_request', 'Mình đã ghi nhận thông tin phòng khám. Bây giờ bạn có muốn mình tìm bác sĩ hoặc lịch khám không?', 'bot', '2025-07-14 18:17:44'),
(1625, 4, NULL, 'booking_request', 'cho minh xem danh sach bac si di', 'user', '2025-07-14 18:18:01'),
(1626, 4, NULL, 'booking_request', 'Dưới đây là danh sách bác sĩ tại Bệnh viện Chợ Rẫy cho chuyên khoa Tim mạch:\n\n- Dr.Linh: Bác sĩ Tim mạch từng công tác tại Viện Tim TP.HCM. Có bằng Thạc sĩ Y khoa từ Đại học Paris, Pháp.', 'bot', '2025-07-14 18:18:01'),
(1627, 4, NULL, 'booking_request', 'ok vay chon bac si do di', 'user', '2025-07-14 18:30:43'),
(1628, 4, NULL, 'booking_request', 'Bạn muốn đặt lịch khám vào thời gian nào?\n\n📅 Lịch khám trong tuần:\n\nThứ 3:\n- Dr.Linh: 09:00 - 12:00\n\nThứ 5:\n- Dr.Linh: 14:00 - 18:00\n\nThứ 7:\n- Dr.Linh: 08:30 - 11:30', 'bot', '2025-07-14 18:30:43'),
(1629, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-15 08:15:00'),
(1630, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết triệu chứng hoặc lý do bạn muốn khám không?', 'bot', '2025-07-15 08:15:00'),
(1631, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-15 08:15:11'),
(1632, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết bạn đang ở khu vực nào không để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-15 08:15:11'),
(1633, 4, NULL, 'booking_request', 'minh song o tphcm', 'user', '2025-07-15 08:15:24'),
(1634, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết số điện thoại của bạn để mình có thể giúp đặt lịch khám không?', 'bot', '2025-07-15 08:15:24'),
(1635, 4, NULL, 'booking_request', '090111111111', 'user', '2025-07-15 08:15:34'),
(1636, 4, NULL, 'booking_request', 'Mình đã ghi nhận thông tin của bạn. Bây giờ mình sẽ giúp bạn tìm phòng khám tim mạch gần nhất.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-15 08:15:34'),
(1637, 4, NULL, 'booking_request', 'Bệnh viện Chợ Rẫy', 'user', '2025-07-15 08:15:50'),
(1638, 4, NULL, 'booking_request', 'Mình đã ghi nhận thông tin phòng khám. Bây giờ, bạn muốn chọn bác sĩ nào hoặc lịch khám nào?', 'bot', '2025-07-15 08:15:50'),
(1639, 4, NULL, 'booking_request', 'cho minh xem danh sach bac si di', 'user', '2025-07-15 08:20:35'),
(1640, 4, NULL, 'booking_request', 'Bạn muốn xem danh sách bác sĩ trước, hay xem các khung giờ khám trước ạ? Mình sẽ hiển thị danh sách phù hợp để bạn lựa chọn nhé.', 'bot', '2025-07-15 08:20:35'),
(1641, 4, NULL, 'booking_request', 'cho minh xem danh sach bac si di', 'user', '2025-07-15 08:20:52'),
(1642, 4, NULL, 'booking_request', 'Dưới đây là danh sách bác sĩ tại Bệnh viện Chợ Rẫy:\n- Dr.Linh: Bác sĩ Tim mạch từng công tác tại Viện Tim TP.HCM. Có bằng Thạc sĩ Y khoa từ Đại học Paris, Pháp.', 'bot', '2025-07-15 08:20:52'),
(1643, 4, NULL, 'booking_request', 'ok vay chon bac si linh di', 'user', '2025-07-15 09:24:25'),
(1644, 4, NULL, 'booking_request', 'Bạn muốn đặt vào thời gian nào? Dưới đây là các khung giờ khám của bác sĩ Linh:\n- Buổi sáng Thứ Bảy (08:30 - 11:30)\n- Buổi chiều Thứ Năm (14:00 - 18:00)\n- Buổi sáng Thứ Ba (09:00 - 12:00)\n\n📅 Lịch khám trong tuần:', 'bot', '2025-07-15 09:24:25'),
(1645, 4, NULL, 'booking_request', 'buoi sang thu 3 di', 'user', '2025-07-15 09:24:58'),
(1646, 4, NULL, 'booking_request', '✅ Bạn đã chọn đầy đủ thông tin:\nHọ tên: Nguyễn Văn A\nSĐT: 090111111111\nKhu vực: TP.HCM\nChuyên khoa: [\'Tim mạch\', \'%TP.HCM%\']\nPhòng khám: Bệnh viện Chợ Rẫy\nBác sĩ: Dr.Linh\nLịch hẹn: Buổi sáng Thứ Ba (09:00 - 12:00)\n\nBạn xác nhận đặt lịch này chứ?', 'bot', '2025-07-15 09:24:59'),
(1647, 4, NULL, 'booking_request', 'ok minh xac nhan', 'user', '2025-07-15 09:41:34'),
(1648, 4, NULL, 'booking_request', '✅ Đã đặt lịch thành công! Mã lịch hẹn của bạn là #6.\nLịch khám: Buổi sáng Thứ Ba (09:00 - 12:00)\nChúc bạn sức khỏe tốt!', 'bot', '2025-07-15 09:41:35'),
(1649, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-15 10:02:13'),
(1650, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè. Cảm giác đó thường kéo dài bao lâu mỗi lần bạn gặp vậy?', 'bot', '2025-07-15 10:02:13'),
(1651, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-15 10:02:49'),
(1652, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **đau đầu**, **chóng mặt**, và **buồn nôn** kéo dài khoảng 5-10 phút. Những triệu chứng này có thể liên quan đến một số nguyên nhân như sau:\n\n**Đau đầu** có thể là do bạn thiếu ngủ hoặc căng thẳng. Bạn thử nghỉ ngơi xem sao nha.\n\n**Chóng mặt** có thể do thay đổi tư thế đột ngột hoặc thiếu nước nhẹ. Bạn có thể thử uống nước từ từ và ngồi nghỉ.\n\n**Buồn nôn** có thể xảy ra khi dạ dày của bạn không ổn hoặc do căng thẳng. Hãy chú ý tới chế độ ăn uống của mình nhé.\n\nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau:\n\n📌 **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **đau đầu** và **buồn nôn**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n\n�� **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.\n\n�� **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.\n\n—\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha ��\nNếu cần, mình có thể hỗ trợ bạn đặt lịch khám phù hợp nha.', 'bot', '2025-07-15 10:02:50'),
(1653, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-15 10:43:49'),
(1654, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **đau đầu** nè, có khi nào bạn thấy đau hơn khi mình đang đứng lên hay ngồi xuống không ha?', 'bot', '2025-07-15 10:43:50'),
(1655, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-15 10:44:08'),
(1656, 4, NULL, 'symptom_query', 'Khi bạn cảm thấy **chóng mặt**, cảm giác đó thường kéo dài bao lâu? Có khi nào bạn cảm thấy nó nhiều hơn khi vừa đứng lên hay ngồi xuống không nhỉ?', 'bot', '2025-07-15 10:44:08'),
(1657, 4, NULL, 'symptom_query', 'minh cung khong ro no tu nhien chong mat thoi | chat do sang h minh chua an gi', 'user', '2025-07-15 10:44:27'),
(1658, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết cảm giác **buồn nôn** của bạn có xuất hiện thường xuyên không? Hoặc có khi nào bạn thấy nó nặng hơn sau khi ăn hay khi bạn đang nghỉ ngơi không ha?', 'bot', '2025-07-15 10:44:27'),
(1659, 4, NULL, 'symptom_query', 'khi minh ngui thay 1 mui gi do kha nang thi co cam giac buon non', 'user', '2025-07-15 10:44:47'),
(1660, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có thêm cảm giác nào khác không, như là **đau bụng**, **mệt mỏi**, hay có cảm giác **nôn mửa** không nhỉ? Những triệu chứng này có thể liên quan đến cảm giác bạn đang gặp phải.', 'bot', '2025-07-15 10:44:47'),
(1661, 4, NULL, 'symptom_query', 'khong co', 'user', '2025-07-15 10:45:21'),
(1662, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **đau đầu**, **chóng mặt**, và **buồn nôn**. Những triệu chứng này có thể do nhiều nguyên nhân khác nhau. \n\n**Đau đầu** có thể là do bạn thiếu ngủ hoặc căng thẳng. Bạn thử nghỉ ngơi xem sao nha.\n**Chóng mặt** có thể do thay đổi tư thế đột ngột hoặc thiếu nước nhẹ. Bạn có thể thử uống nước từ từ và ngồi nghỉ.\n**Buồn nôn** có thể liên quan đến việc bạn ăn không đúng giờ hoặc cảm giác căng thẳng. Nếu có thể, bạn hãy thử ăn nhẹ nhàng và nghỉ ngơi một chút.\n\nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau: \n\n📌 **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **đau đầu** và **buồn nôn**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n\n�� **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.\n\n�� **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.\n\n—\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha �� \nNếu cần, mình có thể hỗ trợ bạn đặt lịch khám phù hợp nha.', 'bot', '2025-07-15 10:45:21'),
(1663, 4, NULL, 'booking_request', 'ok giup minh dat lich kham di', 'user', '2025-07-15 10:56:40'),
(1664, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết bạn đang ở khu vực nào để mình giúp tìm phòng khám gần nhất không?', 'bot', '2025-07-15 10:56:40'),
(1665, 4, NULL, 'booking_request', 'minh o tphcm', 'user', '2025-07-15 10:56:57'),
(1666, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết số điện thoại của bạn để mình có thể đặt lịch khám cho bạn không?', 'bot', '2025-07-15 10:56:58'),
(1667, 4, NULL, 'booking_request', '09011111111', 'user', '2025-07-15 10:57:25'),
(1668, 4, NULL, 'booking_request', 'Mình đã ghi nhận thông tin của bạn. Bây giờ, mình sẽ tìm phòng khám Nội khoa gần bạn nhất.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Bệnh viện Đại học Y Dược - 215 Hồng Bàng, Quận 5, TP.HCM\n- Phòng khám Quốc tế Victoria Healthcare - 79 Điện Biên Phủ, Quận 1, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-15 10:57:25'),
(1669, 4, NULL, 'booking_request', 'ok giup minh dat lich kham di', 'user', '2025-07-15 11:06:44'),
(1670, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết vấn đề sức khỏe mà bạn muốn khám không? Mình sẽ giúp bạn tìm chuyên khoa phù hợp.', 'bot', '2025-07-15 11:06:44'),
(1671, 4, NULL, 'booking_request', 'dat lich kham', 'user', '2025-07-15 11:09:29'),
(1672, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết bạn đang gặp vấn đề gì về sức khỏe để mình giúp bạn đặt lịch khám không?', 'bot', '2025-07-15 11:09:29'),
(1673, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-15 11:14:26'),
(1674, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè. Cảm giác đó thường kéo dài bao lâu mỗi lần bạn gặp phải vậy?', 'bot', '2025-07-15 11:14:26'),
(1675, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-15 11:15:35'),
(1676, 4, NULL, 'booking_request', 'Dựa trên triệu chứng đau đầu, chóng mặt và buồn nôn, bạn có thể cần khám chuyên khoa Nội khoa hoặc Nội thần kinh.\n\nBạn muốn đặt lịch khám ở chuyên khoa nào?', 'bot', '2025-07-15 11:15:35'),
(1677, 4, NULL, 'booking_request', 'noi khoa di', 'user', '2025-07-15 11:16:13'),
(1678, 4, NULL, 'booking_request', 'Bạn vui lòng cho biết số điện thoại của bạn để mình giúp đặt lịch khám nhé.', 'bot', '2025-07-15 11:16:13'),
(1679, 4, NULL, 'booking_request', '09011111111', 'user', '2025-07-15 11:16:29'),
(1680, 4, NULL, 'booking_request', 'Bạn đã chọn chuyên khoa Nội khoa. Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Bệnh viện Đại học Y Dược - 215 Hồng Bàng, Quận 5, TP.HCM\n- Phòng khám Quốc tế Victoria Healthcare - 79 Điện Biên Phủ, Quận 1, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-15 11:16:29'),
(1681, 4, NULL, 'booking_request', 'Bệnh viện Chợ Rẫy', 'user', '2025-07-15 11:16:46'),
(1682, 4, NULL, 'booking_request', 'Bạn đã chọn Bệnh viện Chợ Rẫy. Bạn có muốn chọn bác sĩ hoặc lịch khám không?', 'bot', '2025-07-15 11:16:46'),
(1683, 4, NULL, 'booking_request', 'chon bac si di', 'user', '2025-07-15 11:17:04'),
(1684, 4, NULL, 'booking_request', 'Bạn muốn xem danh sách bác sĩ trước, hay xem các khung giờ khám trước ạ? Mình sẽ hiển thị danh sách phù hợp để bạn lựa chọn nhé.', 'bot', '2025-07-15 11:17:04'),
(1685, 4, NULL, 'booking_request', 'cho minh xem khung gio kham', 'user', '2025-07-15 11:17:39'),
(1686, 4, NULL, 'booking_request', 'Bạn muốn xem danh sách bác sĩ trước, hay xem các khung giờ khám trước ạ? Mình sẽ hiển thị danh sách phù hợp để bạn lựa chọn nhé.', 'bot', '2025-07-15 11:17:39'),
(1687, 4, NULL, 'booking_request', 'Cho mình xem lịch khám', 'user', '2025-07-15 11:18:43'),
(1688, 4, NULL, 'booking_request', 'Xin lỗi, hiện không có lịch khám nào phù hợp. Bạn muốn chọn lại thời gian khác không?', 'bot', '2025-07-15 11:18:44'),
(1689, 4, NULL, 'booking_request', 'giup minh dat lich kham', 'user', '2025-07-15 11:25:21'),
(1690, 4, NULL, 'booking_request', 'Dựa trên triệu chứng của bạn như đau đầu, chóng mặt và buồn nôn, bạn có thể cần khám chuyên khoa Nội. Bạn có muốn đặt lịch khám ở chuyên khoa nào không?', 'bot', '2025-07-15 11:25:21'),
(1691, 4, NULL, 'booking_request', 'vay kham noi khoa di', 'user', '2025-07-15 11:25:50'),
(1692, 4, NULL, 'booking_request', 'Bạn muốn tìm phòng khám ở khu vực nào?', 'bot', '2025-07-15 11:25:50'),
(1693, 4, NULL, 'booking_request', 'o tphcm', 'user', '2025-07-15 11:26:05'),
(1694, 4, NULL, 'booking_request', 'Bạn đã chọn khám chuyên khoa Nội. Mình cần số điện thoại của bạn để đặt lịch khám nhé.', 'bot', '2025-07-15 11:26:05'),
(1695, 4, NULL, 'booking_request', '0901647655', 'user', '2025-07-15 11:26:24'),
(1696, 4, NULL, 'booking_request', 'Bạn đã chọn khám chuyên khoa Nội. Mình sẽ giúp bạn tìm phòng khám phù hợp nhé.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Bệnh viện Đại học Y Dược - 215 Hồng Bàng, Quận 5, TP.HCM\n- Phòng khám Quốc tế Victoria Healthcare - 79 Điện Biên Phủ, Quận 1, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-15 11:26:24'),
(1697, 4, NULL, 'booking_request', 'Phòng khám Đa khoa Hòa Hảo di', 'user', '2025-07-15 11:26:42'),
(1698, 4, NULL, 'booking_request', 'Mình không chắc chắn về phòng khám bạn đã chọn. Bạn có thể cung cấp tên đầy đủ hoặc địa chỉ chính xác hơn không?\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Bệnh viện Đại học Y Dược - 215 Hồng Bàng, Quận 5, TP.HCM\n- Phòng khám Quốc tế Victoria Healthcare - 79 Điện Biên Phủ, Quận 1, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-15 11:26:42'),
(1699, 4, NULL, 'booking_request', 'Phòng khám Đa khoa Hòa Hảo', 'user', '2025-07-15 11:26:59'),
(1700, 4, NULL, 'booking_request', 'Mình không chắc chắn về phòng khám bạn đã chọn. Bạn có thể cung cấp tên đầy đủ hoặc địa chỉ chính xác hơn không?\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Bệnh viện Đại học Y Dược - 215 Hồng Bàng, Quận 5, TP.HCM\n- Phòng khám Quốc tế Victoria Healthcare - 79 Điện Biên Phủ, Quận 1, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-15 11:26:59'),
(1701, 4, NULL, 'booking_request', '254 Hòa Hảo', 'user', '2025-07-15 11:27:36'),
(1702, 4, NULL, 'booking_request', 'Mình không chắc chắn về phòng khám bạn đã chọn. Bạn có thể cung cấp tên đầy đủ hoặc địa chỉ chính xác hơn không?\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Bệnh viện Đại học Y Dược - 215 Hồng Bàng, Quận 5, TP.HCM\n- Phòng khám Quốc tế Victoria Healthcare - 79 Điện Biên Phủ, Quận 1, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-15 11:27:36'),
(1703, 4, NULL, 'booking_request', 'Bệnh viện Chợ Rẫy', 'user', '2025-07-15 11:27:54'),
(1704, 4, NULL, 'booking_request', 'Mình không chắc chắn về phòng khám bạn đã chọn. Bạn có thể cung cấp tên đầy đủ hoặc địa chỉ chính xác hơn không?\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Bệnh viện Đại học Y Dược - 215 Hồng Bàng, Quận 5, TP.HCM\n- Phòng khám Quốc tế Victoria Healthcare - 79 Điện Biên Phủ, Quận 1, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-15 11:27:54'),
(1705, 4, NULL, 'booking_request', 'Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM', 'user', '2025-07-15 11:28:21'),
(1706, 4, NULL, 'booking_request', 'Bạn đã chọn Phòng khám Đa khoa Hòa Hảo. Bạn muốn đặt lịch khám với bác sĩ nào hoặc có thời gian nào không?', 'bot', '2025-07-15 11:28:21'),
(1707, 4, NULL, 'booking_request', 'cho minh xem lich kham', 'user', '2025-07-15 11:28:46'),
(1708, 4, NULL, 'booking_request', 'Bạn muốn xem danh sách bác sĩ trước, hay xem các khung giờ khám trước ạ? Mình sẽ hiển thị danh sách phù hợp để bạn lựa chọn nhé.', 'bot', '2025-07-15 11:28:46'),
(1709, 4, NULL, 'booking_request', 'Cho mình xem lịch khám', 'user', '2025-07-15 11:30:41'),
(1710, 4, NULL, 'booking_request', 'Mình sẽ hiển thị các khung giờ khám cho bạn tại Phòng khám Đa khoa Hòa Hảo nhé.\n\n📅 Lịch khám trong tuần:\n\nThứ Hai:\n- John Doe: 08:00 - 12:00\n\nThứ Tư:\n- John Doe: 08:00 - 12:00\n\nThứ Sáu:\n- John Doe: 13:30 - 17:30', 'bot', '2025-07-15 11:30:41'),
(1711, 4, NULL, 'booking_request', 'ok vay dat thu 6 di', 'user', '2025-07-15 11:35:19'),
(1712, 4, NULL, 'booking_request', 'Thời gian bạn chọn hiện có nhiều bác sĩ phù hợp. Bạn muốn đặt với bác sĩ nào ạ?\n\n📅 Lịch khám trong tuần:\n\nThứ Hai:\n- John Doe: 08:00 - 12:00\n\nThứ Tư:\n- John Doe: 08:00 - 12:00\n\nThứ Sáu:\n- John Doe: 13:30 - 17:30', 'bot', '2025-07-15 11:35:19'),
(1713, 4, NULL, 'booking_request', 'john Doe thu 6', 'user', '2025-07-15 11:35:42'),
(1714, 4, NULL, 'booking_request', 'Thời gian bạn chọn hiện có nhiều bác sĩ phù hợp. Bạn muốn đặt với bác sĩ nào ạ?\n\n📅 Lịch khám trong tuần:\n\nThứ Hai:\n- John Doe: 08:00 - 12:00\n\nThứ Tư:\n- John Doe: 08:00 - 12:00\n\nThứ Sáu:\n- John Doe: 13:30 - 17:30', 'bot', '2025-07-15 11:35:42'),
(1715, 4, NULL, 'booking_request', 'vay dat vao thu 6 do', 'user', '2025-07-15 11:38:54'),
(1716, 4, NULL, 'booking_request', 'Thời gian bạn chọn hiện có nhiều bác sĩ phù hợp. Bạn muốn đặt với bác sĩ nào ạ?\n\n📅 Lịch khám trong tuần:\n\nThứ Hai:\n- John Doe: 08:00 - 12:00\n\nThứ Tư:\n- John Doe: 08:00 - 12:00\n\nThứ Sáu:\n- John Doe: 13:30 - 17:30', 'bot', '2025-07-15 11:38:54'),
(1717, 4, NULL, 'booking_request', 'giup minh dat lich kham', 'user', '2025-07-15 11:39:34'),
(1718, 4, NULL, 'booking_request', 'Dựa trên triệu chứng đau đầu, chóng mặt và buồn nôn, bạn có thể cần khám chuyên khoa Nội khoa hoặc Thần kinh. Bạn muốn đặt lịch khám ở chuyên khoa nào?\n\nMình cần biết bạn ở đâu để gợi ý địa điểm phù hợp nhé.', 'bot', '2025-07-15 11:39:34'),
(1719, 4, NULL, 'booking_request', 'vay kham noi khoa di va minh o tphcm', 'user', '2025-07-15 11:39:57'),
(1720, 4, NULL, 'booking_request', 'Bạn vui lòng cung cấp số điện thoại của mình để mình có thể đặt lịch khám cho bạn nhé.', 'bot', '2025-07-15 11:39:58'),
(1721, 4, NULL, 'booking_request', '090111111', 'user', '2025-07-15 11:40:10'),
(1722, 4, NULL, 'booking_request', 'Bạn đã chọn chuyên khoa Nội khoa. Mình sẽ hỗ trợ bạn tìm phòng khám phù hợp. Bạn vui lòng cho mình biết bạn muốn khám vào thời gian nào nhé!\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Bệnh viện Đại học Y Dược - 215 Hồng Bàng, Quận 5, TP.HCM\n- Phòng khám Quốc tế Victoria Healthcare - 79 Điện Biên Phủ, Quận 1, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM\n- Phòng khám đa khoa Pasteur - 27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'bot', '2025-07-15 11:40:10'),
(1723, 4, NULL, 'booking_request', 'Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM', 'user', '2025-07-15 11:40:20'),
(1724, 4, NULL, 'booking_request', 'Bạn đã chọn Phòng khám Đa khoa Hòa Hảo. Bạn muốn khám vào thời gian nào?', 'bot', '2025-07-15 11:40:20'),
(1725, 4, NULL, 'booking_request', 'cho minh xem lich kham di', 'user', '2025-07-15 11:42:37'),
(1726, 4, NULL, 'booking_request', 'Bạn muốn xem danh sách bác sĩ trước, hay xem các khung giờ khám trước ạ? Mình sẽ hiển thị danh sách phù hợp để bạn lựa chọn nhé.', 'bot', '2025-07-15 11:42:37'),
(1727, 4, NULL, 'booking_request', 'cho minh xem lich kham di', 'user', '2025-07-15 11:42:52'),
(1728, 4, NULL, 'booking_request', 'Bạn muốn xem danh sách bác sĩ trước, hay xem các khung giờ khám trước ạ? Mình sẽ hiển thị danh sách phù hợp để bạn lựa chọn nhé.', 'bot', '2025-07-15 11:42:52'),
(1729, 4, NULL, 'booking_request', 'Cho mình xem lịch khám', 'user', '2025-07-15 11:43:22'),
(1730, 4, NULL, 'booking_request', 'Bạn muốn xem danh sách bác sĩ trước, hay xem các khung giờ khám trước ạ? Mình sẽ hiển thị danh sách phù hợp để bạn lựa chọn nhé.', 'bot', '2025-07-15 11:43:22'),
(1731, 4, NULL, 'booking_request', 'Cho mình xem lịch khám', 'user', '2025-07-15 11:45:30'),
(1732, 4, NULL, 'booking_request', 'Bạn muốn xem danh sách bác sĩ trước, hay xem các khung giờ khám trước ạ? Mình sẽ hiển thị danh sách phù hợp để bạn lựa chọn nhé.', 'bot', '2025-07-15 11:45:30'),
(1733, 4, NULL, 'booking_request', 'cho minh xem lich kham', 'user', '2025-07-15 11:46:24'),
(1734, 4, NULL, 'booking_request', 'Bạn muốn xem lịch khám của bác sĩ nào trước nhé?\n\n📅 Lịch khám trong tuần:\n\nThứ Hai:\n- John Doe: 08:00 - 12:00\n\nThứ Tư:\n- John Doe: 08:00 - 12:00\n\nThứ Sáu:\n- John Doe: 13:30 - 17:30', 'bot', '2025-07-15 11:46:24'),
(1735, 4, NULL, 'booking_request', 'vay minh dat lich vao t6 di', 'user', '2025-07-15 11:47:10'),
(1736, 4, NULL, 'booking_request', 'Dưới đây là các khung giờ khám còn trống của bác sĩ John Doe, bạn muốn chọn lịch nào?\n\n1. Buổi trưa Thứ Sáu (13:30 - 17:30)\n2. Buổi sáng Thứ Hai (08:00 - 12:00)\n3. Buổi sáng Thứ Tư (08:00 - 12:00)\n\n📅 Lịch khám trong tuần:', 'bot', '2025-07-15 11:47:10'),
(1737, 4, NULL, 'booking_request', 'vay cho minh dat vao buoi sang thu tu di', 'user', '2025-07-15 12:14:47'),
(1738, 4, NULL, 'booking_request', '✅ Bạn đã chọn đầy đủ thông tin:\nHọ tên: Nguyễn Văn A\nSĐT: 090111111\nKhu vực: TP.HCM\nChuyên khoa: Nội khoa\nPhòng khám: Phòng khám Đa khoa Hòa Hảo\nBác sĩ: John Doe\nLịch hẹn: Buổi sáng Thứ Tư (08:00 - 12:00)\n\nBạn xác nhận đặt lịch này chứ?', 'bot', '2025-07-15 12:14:47'),
(1739, 4, NULL, 'booking_request', 'ok minh xac nhan', 'user', '2025-07-15 12:15:02'),
(1740, 4, NULL, 'booking_request', '✅ Đã đặt lịch thành công! Mã lịch hẹn của bạn là #7.\nLịch khám: Buổi sáng Thứ Tư (08:00 - 12:00)\nChúc bạn sức khỏe tốt!', 'bot', '2025-07-15 12:15:02'),
(1741, 4, NULL, 'general_chat', 'cho minh xem tran doan cua nguyen van a', 'user', '2025-07-15 13:02:21'),
(1742, 4, NULL, 'general_chat', 'Xin lỗi bạn nha, hiện tại bạn chưa có quyền truy cập chức năng này. Bạn có thể đăng nhập hoặc liên hệ quản trị viên để được hỗ trợ thêm nhé!', 'bot', '2025-07-15 13:02:21'),
(1743, 4, NULL, 'booking_request', 'minh muon dat lich kham', 'user', '2025-07-15 13:12:52'),
(1744, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết bạn muốn khám về vấn đề sức khỏe nào không? Mình sẽ giúp bạn tìm chuyên khoa phù hợp.Bạn có thể cho mình biết bạn muốn khám về vấn đề sức khỏe nào không? Mình sẽ giúp bạn tìm chuyên khoa phù hợp.', 'bot', '2025-07-15 13:12:52'),
(1745, 2, NULL, 'general_chat', 'huy', 'user', '2025-07-23 11:09:03'),
(1746, 2, NULL, 'general_chat', 'Um, mình hiểu rồi nè. Bạn có muốn chia sẻ thêm điều gì không?', 'bot', '2025-07-23 11:09:03'),
(1747, 2, NULL, 'general_chat', 'cho minh xem danh sach san pham', 'user', '2025-07-23 11:09:45'),
(1748, 2, NULL, 'general_chat', 'Xin lỗi bạn nha, hiện tại bạn chưa có quyền truy cập chức năng này. Bạn có thể đăng nhập hoặc liên hệ quản trị viên để được hỗ trợ thêm nhé!', 'bot', '2025-07-23 11:09:45'),
(1749, 2, NULL, 'general_chat', 'cho minh xem danh sach san pham', 'user', '2025-07-23 11:14:09'),
(1750, 2, NULL, 'general_chat', 'Xin lỗi bạn nha, hiện tại bạn chưa có quyền truy cập chức năng này. Bạn có thể đăng nhập hoặc liên hệ quản trị viên để được hỗ trợ thêm nhé!', 'bot', '2025-07-23 11:14:09'),
(1751, 2, NULL, 'sql_query', 'cho minh xem danbh sach san pham', 'user', '2025-07-23 11:20:23'),
(1752, 2, NULL, 'sql_query', '{\n  \"natural_text\": \"📦 Dưới đây là danh sách các sản phẩm hiện có.\",\n  \"sql_query\": \"SELECT name AS \'Tên sản phẩm\', price AS \'Giá\' FROM products WHERE is_active = 1\"\n}', 'bot', '2025-07-23 11:20:23'),
(1753, 2, NULL, 'sql_query', 'cho minh xem danbh sach san pham', 'user', '2025-07-23 11:20:23'),
(1754, 2, NULL, 'sql_query', '📦 Dưới đây là danh sách các sản phẩm hiện có.', 'bot', '2025-07-23 11:20:24'),
(1755, 2, NULL, 'sql_query', 'cho xem cac don hang hien co', 'user', '2025-07-23 11:22:13'),
(1756, 2, NULL, 'sql_query', '{\n  \"natural_text\": \"📦 Dưới đây là danh sách các đơn hàng hiện có.\",\n  \"sql_query\": \"SELECT order_id AS \'Mã đơn hàng\', user_id AS \'Người dùng\', total AS \'Tổng giá\', payment_status AS \'Trạng thái thanh toán\', status AS \'Trạng thái đơn hàng\', order_date AS \'Ngày tạo đơn\' FROM orders\"\n}', 'bot', '2025-07-23 11:22:13'),
(1757, 2, NULL, 'sql_query', 'cho xem cac don hang hien co', 'user', '2025-07-23 11:22:13'),
(1758, 2, NULL, 'sql_query', '📦 Dưới đây là danh sách các đơn hàng hiện có.', 'bot', '2025-07-23 11:22:13'),
(1759, 2, NULL, 'sql_query', 'cho minh xem tat ca don hang hien co', 'user', '2025-07-23 11:22:58'),
(1760, 2, NULL, 'sql_query', '{\n  \"natural_text\": \"📦 Đây là danh sách các đơn hàng hiện có.\",\n  \"sql_query\": \"SELECT order_id AS \'ID đơn hàng\', user_id AS \'ID người dùng\', address_id AS \'ID địa chỉ\', shipping_address AS \'Địa chỉ giao hàng\', total AS \'Tổng giá\', payment_method AS \'Phương thức thanh toán\', payment_status AS \'Trạng thái thanh toán\', status AS \'Trạng thái\', order_note AS \'Ghi chú đơn hàng\', order_date AS \'Ngày đặt hàng\', updated_at AS \'Cập nhật gần nhất\' FROM orders\"\n}', 'bot', '2025-07-23 11:22:58'),
(1761, 2, NULL, 'sql_query', 'cho minh xem tat ca don hang hien co', 'user', '2025-07-23 11:22:58'),
(1762, 2, NULL, 'sql_query', '📦 Đây là danh sách các đơn hàng hiện có.', 'bot', '2025-07-23 11:22:58'),
(1763, 2, NULL, 'product_query', 'cho xem cac don hang hien co', 'user', '2025-07-23 11:35:43'),
(1764, 2, NULL, 'product_query', '{\n  \"natural_text\": \"📦 Đây là danh sách các đơn hàng hiện có.\",\n  \"sql_query\": \"SELECT order_id AS \'Mã đơn hàng\', user_id AS \'Mã người dùng\', address_id AS \'Địa chỉ giao hàng\', shipping_address AS \'Địa chỉ giao hàng\', total AS \'Tổng số tiền\', payment_method AS \'Phương thức thanh toán\', payment_status AS \'Trạng thái thanh toán\', status AS \'Trạng thái đơn hàng\', order_note AS \'Ghi chú đơn hàng\', order_date AS \'Thời gian đặt hàng\', updated_at AS \'Thời gian cập nhật\' FROM orders\"\n}', 'bot', '2025-07-23 11:35:43'),
(1765, 2, NULL, 'product_query', 'cho xem cac don hang hien co', 'user', '2025-07-23 11:35:44'),
(1766, 2, NULL, 'product_query', '📦 Đây là danh sách các đơn hàng hiện có.', 'bot', '2025-07-23 11:35:44'),
(1767, 2, NULL, 'sql_query', 'cho minh xem cac don hang hien co', 'user', '2025-07-23 12:48:35'),
(1768, 2, NULL, 'sql_query', '{\n  \"natural_text\": \"📦 Dưới đây là danh sách các đơn hàng hiện có.\",\n  \"sql_query\": \"SELECT order_id AS \'Mã đơn hàng\', user_id AS \'Mã người dùng\', address_id AS \'Mã địa chỉ\', shipping_address AS \'Địa chỉ giao hàng\', total AS \'Tổng số tiền\', payment_method AS \'Phương thức thanh toán\', payment_status AS \'Trạng thái thanh toán\', status AS \'Trạng thái đơn hàng\', order_note AS \'Ghi chú đơn hàng\', order_date AS \'Thời gian đặt hàng\' FROM orders\"\n}', 'bot', '2025-07-23 12:48:35'),
(1769, 2, NULL, 'sql_query', 'cho minh xem cac don hang hien co', 'user', '2025-07-23 12:48:35'),
(1770, 2, NULL, 'sql_query', 'cho minh xem cac don hang hien co', 'user', '2025-07-23 12:51:46'),
(1771, 2, NULL, 'sql_query', '{\n  \"natural_text\": \"📦 Dưới đây là danh sách các đơn hàng hiện có.\",\n  \"sql_query\": \"SELECT order_id AS \'Mã đơn hàng\', user_id AS \'Mã người dùng\', address_id AS \'Mã địa chỉ\', shipping_address AS \'Địa chỉ giao hàng\', total AS \'Tổng số tiền\', payment_method AS \'Phương thức thanh toán\', payment_status AS \'Trạng thái thanh toán\', status AS \'Trạng thái đơn hàng\', order_note AS \'Ghi chú đơn hàng\', order_date AS \'Thời gian đặt hàng\' FROM orders\"\n}', 'bot', '2025-07-23 12:51:46'),
(1772, 2, NULL, 'sql_query', 'cho minh xem cac don hang hien co', 'user', '2025-07-23 12:51:46'),
(1773, 2, NULL, 'sql_query', '{\"description\": \"📦 Dưới đây là danh sách các đơn hàng hiện có.\", \"data\": [{\"Mã đơn hàng\": 1, \"Mã người dùng\": 1, \"Mã địa chỉ\": null, \"Địa chỉ giao hàng\": \"Quản trị viên\\n09777313131\\n123 Đường Trần Hưng Đạo\\nPhường Nguyễn Cư Trinh, Quận 1, TP.HCM\", \"Tổng số tiền\": 20000.0, \"Phương thức thanh toán\": \"cod\", \"Trạng thái thanh toán\": \"pending\", \"Trạng thái đơn hàng\": \"pending\", \"Ghi chú đơn hàng\": \"\", \"Thời gian đặt hàng\": \"2025-07-21 00:14:14\"}]}', 'bot', '2025-07-23 12:51:46'),
(1774, 2, NULL, 'sql_query', 'cho minh xem danh sach san pham', 'user', '2025-07-23 12:56:21'),
(1775, 2, NULL, 'sql_query', '{\n  \"natural_text\": \"📦 Dưới đây là danh sách các sản phẩm hiện có.\",\n  \"sql_query\": \"SELECT name AS \'Tên sản phẩm\', price AS \'Giá\' FROM products WHERE is_active = 1\"\n}', 'bot', '2025-07-23 12:56:21'),
(1776, 2, NULL, 'sql_query', 'cho minh xem danh sach san pham', 'user', '2025-07-23 12:56:21'),
(1777, 2, NULL, 'sql_query', '{\"description\": \"📦 Dưới đây là danh sách các sản phẩm hiện có.\", \"data\": [{\"Tên sản phẩm\": \"Paracetamol 500mg\", \"Giá\": 15000.0}, {\"Tên sản phẩm\": \"Amoxicillin 500mg\", \"Giá\": 28000.0}, {\"Tên sản phẩm\": \"Vitamin C 1000mg\", \"Giá\": 50000.0}, {\"Tên sản phẩm\": \"Máy đo huyết áp điện tử\", \"Giá\": 650000.0}, {\"Tên sản phẩm\": \"Khẩu trang y tế 4 lớp\", \"Giá\": 40000.0}, {\"Tên sản phẩm\": \"Ibuprofen 200mg\", \"Giá\": 20000.0}, {\"Tên sản phẩm\": \"Kẽm Gluconat 50mg\", \"Giá\": 45000.0}, {\"Tên sản phẩm\": \"Men tiêu hóa Biolactyl\", \"Giá\": 70000.0}, {\"Tên sản phẩm\": \"Máy xông mũi họng mini\", \"Giá\": 350000.0}, {\"Tên sản phẩm\": \"Kem dưỡng ẩm da nhạy cảm\", \"Giá\": 120000.0}, {\"Tên sản phẩm\": \"Trà ngủ ngon Hoa Cúc\", \"Giá\": 65000.0}]}', 'bot', '2025-07-23 12:56:21'),
(1778, 2, NULL, 'sql_query', 'cho minh xem don hang', 'user', '2025-07-23 13:09:47'),
(1779, 2, NULL, 'sql_query', 'Bạn muốn mình giúp bạn kiểm tra thông tin về đơn hàng nào ạ?', 'bot', '2025-07-23 13:09:48'),
(1780, 2, NULL, 'sql_query', 'cho minh xem danh sach don hang', 'user', '2025-07-23 13:11:28'),
(1781, 2, NULL, 'sql_query', 'Bạn muốn mình hỗ trợ xem danh sách đơn hàng nào ạ?', 'bot', '2025-07-23 13:11:29'),
(1782, 2, NULL, 'product_query', 'tat ca don hang', 'user', '2025-07-23 13:11:47'),
(1783, 2, NULL, 'product_query', 'Bạn muốn mình hỗ trợ xem tất cả đơn hàng của bạn, phải không nè? Mình sẽ giúp bạn!', 'bot', '2025-07-23 13:11:48'),
(1784, 2, NULL, 'sql_query', 'tat ca don hang', 'user', '2025-07-23 13:12:57'),
(1785, 2, NULL, 'sql_query', 'Um, mình hiểu rồi nè! Bạn muốn xem tất cả đơn hàng của mình, đúng không? Mình sẽ giúp bạn ngay đây.', 'bot', '2025-07-23 13:12:57'),
(1786, 2, NULL, 'sql_query', 'cho minh xem danh sach san pham', 'user', '2025-07-23 13:13:19'),
(1787, 2, NULL, 'sql_query', '{\n  \"natural_text\": \"📦 Đây là danh sách sản phẩm hiện có.\",\n  \"sql_query\": \"SELECT name AS \'Tên sản phẩm\', price AS \'Giá\' FROM products WHERE is_active = 1\"\n}', 'bot', '2025-07-23 13:13:19'),
(1788, 2, NULL, 'sql_query', 'cho minh xem danh sach san pham', 'user', '2025-07-23 13:13:19'),
(1789, 2, NULL, 'sql_query', '{\"description\": \"📦 Đây là danh sách sản phẩm hiện có.\", \"data\": [{\"Tên sản phẩm\": \"Paracetamol 500mg\", \"Giá\": 15000.0}, {\"Tên sản phẩm\": \"Amoxicillin 500mg\", \"Giá\": 28000.0}, {\"Tên sản phẩm\": \"Vitamin C 1000mg\", \"Giá\": 50000.0}, {\"Tên sản phẩm\": \"Máy đo huyết áp điện tử\", \"Giá\": 650000.0}, {\"Tên sản phẩm\": \"Khẩu trang y tế 4 lớp\", \"Giá\": 40000.0}, {\"Tên sản phẩm\": \"Ibuprofen 200mg\", \"Giá\": 20000.0}, {\"Tên sản phẩm\": \"Kẽm Gluconat 50mg\", \"Giá\": 45000.0}, {\"Tên sản phẩm\": \"Men tiêu hóa Biolactyl\", \"Giá\": 70000.0}, {\"Tên sản phẩm\": \"Máy xông mũi họng mini\", \"Giá\": 350000.0}, {\"Tên sản phẩm\": \"Kem dưỡng ẩm da nhạy cảm\", \"Giá\": 120000.0}, {\"Tên sản phẩm\": \"Trà ngủ ngon Hoa Cúc\", \"Giá\": 65000.0}]}', 'bot', '2025-07-23 13:13:19'),
(1790, 2, NULL, 'sql_query', 'cho minh xem cac don hang hien co', 'user', '2025-07-23 13:14:35'),
(1791, 2, NULL, 'sql_query', '{\n  \"natural_text\": \"🛒 Dưới đây là danh sách các đơn hàng hiện có.\",\n  \"sql_query\": \"SELECT order_id AS \'Mã đơn hàng\', user_id AS \'Mã người dùng\', status AS \'Trạng thái\', total AS \'Tổng giá\', order_date AS \'Ngày đặt hàng\' FROM orders WHERE status IN (\'pending\', \'processing\', \'shipped\', \'completed\')\"\n}', 'bot', '2025-07-23 13:14:36'),
(1792, 2, NULL, 'sql_query', 'cho minh xem cac don hang hien co', 'user', '2025-07-23 13:14:36'),
(1793, 2, NULL, 'sql_query', '{\"description\": \"🛒 Dưới đây là danh sách các đơn hàng hiện có.\", \"data\": [{\"Mã đơn hàng\": 1, \"Mã người dùng\": 1, \"Trạng thái\": \"pending\", \"Tổng giá\": 20000.0, \"Ngày đặt hàng\": \"2025-07-21 00:14:14\"}]}', 'bot', '2025-07-23 13:14:36'),
(1794, 2, NULL, 'sql_query', 'cho minh xem cac don hang hien co', 'user', '2025-07-23 13:22:30'),
(1795, 2, NULL, 'sql_query', '{\n  \"natural_text\": \"🛒 Dưới đây là danh sách các đơn hàng hiện có.\",\n  \"sql_query\": \"SELECT order_id AS \'Mã đơn hàng\', user_id AS \'Mã người dùng\', status AS \'Trạng thái\', total AS \'Tổng giá\', order_date AS \'Ngày đặt hàng\' FROM orders\"\n}', 'bot', '2025-07-23 13:22:30'),
(1796, 2, NULL, 'sql_query', 'cho minh xem cac don hang hien co', 'user', '2025-07-23 13:22:30'),
(1797, 2, NULL, 'sql_query', '{\"description\": \"🛒 Dưới đây là danh sách các đơn hàng hiện có.\", \"data\": [{\"Mã đơn hàng\": 1, \"Mã người dùng\": 1, \"Trạng thái\": \"pending\", \"Tổng giá\": 20000.0, \"Ngày đặt hàng\": \"2025-07-21 00:14:14\"}]}', 'bot', '2025-07-23 13:22:30'),
(1798, 2, NULL, 'sql_query', 'cho minh xem cac don hang hien co', 'user', '2025-07-23 13:24:16'),
(1799, 2, NULL, 'sql_query', '{\n  \"natural_text\": \"🛒 Dưới đây là danh sách các đơn hàng hiện có.\",\n  \"sql_query\": \"SELECT order_id AS \'Mã đơn hàng\', user_id AS \'Mã người dùng\', payment_status AS \'Trạng thái thanh toán\', status AS \'Trạng thái\', total AS \'Tổng giá\', order_date AS \'Ngày đặt hàng\' FROM orders\"\n}', 'bot', '2025-07-23 13:24:16'),
(1800, 2, NULL, 'sql_query', 'cho minh xem cac don hang hien co', 'user', '2025-07-23 13:24:16'),
(1801, 2, NULL, 'sql_query', '{\"description\": \"🛒 Dưới đây là danh sách các đơn hàng hiện có.\", \"data\": [{\"Mã đơn hàng\": 1, \"Mã người dùng\": 1, \"Trạng thái thanh toán\": \"pending\", \"Trạng thái\": \"pending\", \"Tổng giá\": 20000.0, \"Ngày đặt hàng\": \"2025-07-21 00:14:14\"}]}', 'bot', '2025-07-23 13:24:16'),
(1804, 2, NULL, 'sql_query', 'danh sach san pham', 'user', '2025-07-23 13:25:15'),
(1805, 2, NULL, 'sql_query', '{\"description\": \"📦 Dưới đây là danh sách các sản phẩm hiện có.\", \"data\": [{\"Tên sản phẩm\": \"Paracetamol 500mg\", \"Giá\": 15000.0, \"Mô tả\": \"Thuốc hạ sốt, giảm đau thường dùng.\", \"Số lượng trong kho\": 100}, {\"Tên sản phẩm\": \"Amoxicillin 500mg\", \"Giá\": 28000.0, \"Mô tả\": \"Kháng sinh phổ rộng nhóm penicillin.\", \"Số lượng trong kho\": 60}, {\"Tên sản phẩm\": \"Vitamin C 1000mg\", \"Giá\": 50000.0, \"Mô tả\": \"Hỗ trợ tăng cường đề kháng.\", \"Số lượng trong kho\": 200}, {\"Tên sản phẩm\": \"Máy đo huyết áp điện tử\", \"Giá\": 650000.0, \"Mô tả\": \"Thiết bị đo huyết áp tại nhà.\", \"Số lượng trong kho\": 15}, {\"Tên sản phẩm\": \"Khẩu trang y tế 4 lớp\", \"Giá\": 40000.0, \"Mô tả\": \"Hộp 50 cái, đạt chuẩn kháng khuẩn.\", \"Số lượng trong kho\": 500}, {\"Tên sản phẩm\": \"Ibuprofen 200mg\", \"Giá\": 20000.0, \"Mô tả\": \"Thuốc giảm đau, kháng viêm, hạ sốt.\", \"Số lượng trong kho\": 80}, {\"Tên sản phẩm\": \"Kẽm Gluconat 50mg\", \"Giá\": 45000.0, \"Mô tả\": \"Hỗ trợ miễn dịch, chống viêm nhiễm.\", \"Số lượng trong kho\": 150}, {\"Tên sản phẩm\": \"Men tiêu hóa Biolactyl\", \"Giá\": 70000.0, \"Mô tả\": \"Giúp cân bằng hệ vi sinh đường ruột.\", \"Số lượng trong kho\": 90}, {\"Tên sản phẩm\": \"Máy xông mũi họng mini\", \"Giá\": 350000.0, \"Mô tả\": \"Hỗ trợ điều trị viêm mũi, cảm cúm tại nhà.\", \"Số lượng trong kho\": 25}, {\"Tên sản phẩm\": \"Kem dưỡng ẩm da nhạy cảm\", \"Giá\": 120000.0, \"Mô tả\": \"Phục hồi và giữ ẩm cho da khô, kích ứng.\", \"Số lượng trong kho\": 50}, {\"Tên sản phẩm\": \"Trà ngủ ngon Hoa Cúc\", \"Giá\": 65000.0, \"Mô tả\": \"Giúp thư giãn, cải thiện giấc ngủ tự nhiên.\", \"Số lượng trong kho\": 70}]}', 'bot', '2025-07-23 13:25:15'),
(1806, 6, NULL, 'patient_summary_request', 'cho minh tong quat phong doan benh gan nhat cua benh nhan nguyen van a', 'user', '2025-07-23 13:36:01');
INSERT INTO `chat_logs` (`chat_id`, `user_id`, `guest_id`, `intent`, `message`, `sender`, `sent_at`) VALUES
(1807, 6, NULL, 'patient_summary_request', '<p>\n    <strong>Đau đầu</strong> (15/07/2025): Bệnh nhân báo cáo bị đau đầu kéo dài từ 5-10 phút khi đứng lên hoặc ngồi xuống, kèm theo triệu chứng <strong>chóng mặt</strong> và <strong>buồn nôn</strong> nhẹ.<br>\n    <strong>Chóng mặt</strong> (15/07/2025): Bệnh nhân cảm thấy chóng mặt tự nhiên, đặc biệt khi chưa ăn gì.<br>\n    <strong>Buồn nôn</strong> (15/07/2025): Bệnh nhân có cảm giác buồn nôn nhẹ khi ngửi thấy một mùi gì đó.<br>\n    <strong>Đau đầu</strong> (12/07/2025): Bệnh nhân muốn khám về vấn đề tim mạch.<br>\n    <strong>Đau đầu</strong> (11/07/2025): Bệnh nhân báo cáo có vấn đề liên quan đến tim mạch.<br>\n    <strong>Đau đầu</strong> (09/07/2025): Bệnh nhân cảm giác đau đầu kéo dài khoảng 5-10 phút khi vừa nằm xuống.<br>\n    <strong>Chóng mặt</strong> (09/07/2025): Bệnh nhân có cảm giác chóng mặt khi vừa đứng dậy.<br>\n    <strong>Đau đầu</strong> (03/07/2025): Bệnh nhân bị đau đầu khi vừa nằm xuống, cơn đau kéo dài khoảng 5-10 phút.<br>\n    <strong>Chóng mặt</strong> (03/07/2025): Bệnh nhân cảm thấy chóng mặt từ sáng tới giờ, triệu chứng không rõ nguyên nhân.<br>\n    <strong>Buồn nôn</strong> (03/07/2025): Bệnh nhân có cảm giác buồn nôn nhẹ khi ngửi thấy một mùi gì đó.<br>\n    <strong>Tê tay chân</strong> (01/07/2025): Bệnh nhân đã mô tả một số triệu chứng trong cuộc trò chuyện.<br>\n    <strong>Khàn giọng</strong> (01/07/2025): Bệnh nhân đã mô tả một số triệu chứng trong cuộc trò chuyện.<br>\n    <strong>Yếu cơ</strong> (01/07/2025): Bệnh nhân đã mô tả một số triệu chứng trong cuộc trò chuyện.<br>\n    <strong>Đau đầu</strong> (01/07/2025): Bệnh nhân đã mô tả một số triệu chứng trong cuộc trò chuyện.<br>\n    <strong>Khàn giọng</strong> (25/06/2025): Bệnh nhân bị tê tay chân bên trái, nói chuyện ngọng, hay quên, và gần đây cảm thấy mệt mỏi cùng với mất thăng bằng khi đi lại.<br>\n    <strong>Mệt mỏi</strong> (25/06/2025): Bệnh nhân cảm thấy mệt mỏi cùng với tê tay chân bên trái và khàn giọng.<br>\n    <strong>Chóng mặt</strong> (25/06/2025): Bệnh nhân bị tê tay chân bên trái, nói chuyện ng', 'bot', '2025-07-23 13:36:01'),
(1808, 1, NULL, 'general_chat', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-23 13:57:08'),
(1809, 1, NULL, 'general_chat', 'Um, mình hiểu rồi nè. Bạn đang cảm thấy không khỏe với triệu chứng đau đầu, chóng mặt và buồn nôn. Bạn có muốn tiếp tục chia sẻ về cảm giác này hoặc cần mình hỗ trợ gì không?', 'bot', '2025-07-23 13:57:08'),
(1810, 4, NULL, 'general_chat', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-23 14:05:40'),
(1811, 4, NULL, 'general_chat', 'Um, mình hiểu rồi nè. Bạn cảm thấy không thoải mái với những triệu chứng đó. Bạn có muốn mình hỗ trợ thêm về cách giảm nhẹ triệu chứng hay muốn đặt lịch khám bác sĩ không?', 'bot', '2025-07-23 14:05:40'),
(1812, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-23 14:07:09'),
(1813, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có thấy cảm giác **đau đầu** này thường kéo dài bao lâu mỗi lần bạn gặp không ha? Có khi nào bạn thấy đỡ hơn sau khi nghỉ ngơi không?', 'bot', '2025-07-23 14:07:09'),
(1814, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-23 14:19:03'),
(1815, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn cảm thấy **chóng mặt** này thường xảy ra khi bạn thay đổi tư thế, lúc đang ngồi hay lúc vừa đứng lên không nhỉ?', 'bot', '2025-07-23 14:19:03'),
(1816, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-23 14:20:19'),
(1817, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có cảm giác **buồn nôn** này thường đi kèm với cảm giác mệt mỏi hoặc không thoải mái nào khác không ha? Bạn có thấy tình trạng này xảy ra nhiều khi nào không?', 'bot', '2025-07-23 14:20:19'),
(1818, 4, NULL, 'symptom_query', 'khi minh ngui thay 1 mui gi do kha nang thi co cam giac buon non', 'user', '2025-07-23 14:21:32'),
(1819, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **đau đầu**, **chóng mặt**, và **buồn nôn**. Cảm giác **đau đầu** có thể là do bạn thiếu ngủ hoặc căng thẳng. Bạn thử nghỉ ngơi xem sao nha.\n**Chóng mặt** có thể do thay đổi tư thế đột ngột hoặc thiếu nước nhẹ. Bạn có thể thử uống nước từ từ và ngồi nghỉ.\n**Buồn nôn** thường liên quan đến cảm giác khó chịu trong dạ dày, có thể do stress hoặc ăn uống không đúng cách. Bạn hãy chú ý đến chế độ ăn uống của mình nhé.\n\nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau:\n\n📌 **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **đau đầu** và **buồn nôn**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n\n📌 **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.\n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.\n\n—\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha 🌿\nNếu cần, mình có thể hỗ trợ bạn đặt lịch khám phù hợp nha.', 'bot', '2025-07-23 14:21:33'),
(1820, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-23 14:29:33'),
(1821, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **đau đầu**, **chóng mặt**, và **buồn nôn**... Cảm giác **đau đầu** có thể là do bạn thiếu ngủ hoặc căng thẳng. Bạn thử nghỉ ngơi xem sao nha. **Chóng mặt** có thể do thay đổi tư thế đột ngột hoặc thiếu nước nhẹ. Bạn có thể thử uống nước từ từ và ngồi nghỉ. **Buồn nôn** thường liên quan đến cảm giác khó chịu trong dạ dày, có thể do stress hoặc ăn uống không đúng cách.\n\nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau:\n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.\n\n�� **Thiếu năng lượng nhẹ**  \nCơ thể bị hạ đường huyết tạm thời nếu nhịn ăn lâu.  \n→ Bạn có thể ăn nhẹ hoặc uống sữa để lấy lại sức.\n\n📌 **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **đau đầu** và **buồn nôn**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n\n—\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha 🌿\nNếu cần, mình có thể hỗ trợ bạn đặt lịch khám phù hợp nha.', 'bot', '2025-07-23 14:29:33'),
(1822, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-23 14:30:28'),
(1823, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **đau đầu** của bạn nè. Bạn có thấy cơn đau này thường kéo dài bao lâu mỗi lần gặp hay có lúc nào thấy đỡ hơn sau khi nghỉ ngơi không ha?', 'bot', '2025-07-23 14:30:28'),
(1824, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac dau dau va no keo dai tam 5-10p gi do', 'user', '2025-07-23 14:32:29'),
(1825, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có thấy cơn **chóng mặt** này xảy ra nhiều khi nào, chẳng hạn như lúc vừa đứng lên hay khi đang nằm nghỉ không ha?', 'bot', '2025-07-23 14:32:29'),
(1826, 4, NULL, 'symptom_query', 'minh cung khong ro no tu nhien chong mat thoi', 'user', '2025-07-23 14:33:09'),
(1827, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **buồn nôn** của bạn nè. Bạn có thấy cảm giác này có kèm theo mệt mỏi hoặc là thường xảy ra trong lúc nào, như khi ăn hay uống nước không ha?', 'bot', '2025-07-23 14:33:10'),
(1828, 4, NULL, 'symptom_query', 'khi minh ngui thay 1 mui gi do kha nang thi co cam giac buon non', 'user', '2025-07-23 14:33:57'),
(1829, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có thêm cảm giác nào khác nữa không, chẳng hạn như có thấy mệt mỏi, tim đập nhanh hay có cảm giác tức ngực gì không nha?', 'bot', '2025-07-23 14:33:58'),
(1830, 4, NULL, 'symptom_query', 'ko co', 'user', '2025-07-23 14:35:57'),
(1831, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **đau đầu**, **chóng mặt**, và **buồn nôn**. \nCảm giác **đau đầu** có thể là do bạn thiếu ngủ hoặc căng thẳng. Bạn thử nghỉ ngơi xem sao nha. \nCảm giác **chóng mặt** có thể do thay đổi tư thế đột ngột hoặc thiếu nước nhẹ. Bạn có thể thử uống nước từ từ và ngồi nghỉ. \nCảm giác **buồn nôn** có thể liên quan đến việc ăn uống không đều hoặc căng thẳng. Hãy thử ăn nhẹ nhàng và nghỉ ngơi một chút nhé. \n\nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau: \n\n�� **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **đau đầu** và **buồn nôn**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân. \n\n�� **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày. \n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha. \n\n— \nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha �� \nNếu cần, mình có thể hỗ trợ bạn đặt lịch khám phù hợp nha.', 'bot', '2025-07-23 14:35:57'),
(1832, 4, NULL, 'symptom_query', 'khong co', 'user', '2025-07-23 14:39:22'),
(1833, 4, NULL, 'symptom_query', 'Mình thấy bạn mô tả cảm giác như **đau đầu**, **chóng mặt**, và **buồn nôn**. Những triệu chứng này có thể liên quan đến một số nguyên nhân tự nhiên như căng thẳng, thiếu ngủ hoặc không ăn đúng bữa.\n\n**Đau đầu** có thể là do bạn thiếu ngủ hoặc căng thẳng. Bạn thử nghỉ ngơi xem sao nha.\n**Chóng mặt** có thể do thay đổi tư thế đột ngột hoặc thiếu nước nhẹ. Bạn có thể thử uống nước từ từ và ngồi nghỉ.\n**Buồn nôn** có thể liên quan đến việc ăn uống không đều hoặc căng thẳng. Hãy thử ăn nhẹ nhàng và nghỉ ngơi một chút nhé.\n\nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau:\n\n�� **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **đau đầu** và **buồn nôn**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n\n📌 **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.\n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.\n\n—\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha 🌿\nNếu cần, mình có thể hỗ trợ bạn đặt lịch khám phù hợp nha.', 'bot', '2025-07-23 14:39:22'),
(1834, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-23 14:42:04'),
(1835, 4, NULL, 'symptom_query', 'Mình đang tò mò không biết cảm giác **đau đầu** của bạn có thường kéo dài bao lâu mỗi lần bạn gặp phải không nhỉ?', 'bot', '2025-07-23 14:42:04'),
(1836, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-23 14:43:34'),
(1837, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **đau đầu** nè. Bạn có thường thấy nó xuất hiện khi nào, như lúc đang ngồi hay lúc vừa đứng lên không ha?', 'bot', '2025-07-23 14:43:34'),
(1838, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-23 14:44:13'),
(1839, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **đau đầu**, **chóng mặt**, và **buồn nôn**. \n\n**Đau đầu** có thể là do bạn thiếu ngủ hoặc căng thẳng. Bạn thử nghỉ ngơi xem sao nha. \n**Chóng mặt** có thể do thay đổi tư thế đột ngột hoặc thiếu nước nhẹ. Bạn có thể thử uống nước từ từ và ngồi nghỉ. \n**Buồn nôn** có thể liên quan đến việc dạ dày không ổn hoặc căng thẳng. Hãy thử ăn nhẹ hoặc nghỉ ngơi nhé. \n\nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau: \n\n📌 **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **đau đầu** và **buồn nôn**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân. \n\n📌 **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày. \n\n�� **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha. \n\n— \nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha 🌿 \nNếu cần, mình có thể hỗ trợ bạn đặt lịch khám phù hợp nha.', 'bot', '2025-07-23 14:44:13'),
(1840, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-23 14:48:00'),
(1841, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **đau đầu** của bạn nè. Bạn có thấy tình trạng này thường kéo dài bao lâu mỗi lần gặp phải không? Có khi nào bạn cảm thấy đỡ hơn sau khi nghỉ ngơi không ha?', 'bot', '2025-07-23 14:48:00'),
(1842, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-23 14:49:16'),
(1843, 4, NULL, 'symptom_query', 'Khi bạn cảm thấy **chóng mặt**, nó thường kéo dài bao lâu? Có phải chỉ khi bạn đứng lên hoặc thay đổi tư thế không ha?', 'bot', '2025-07-23 14:49:17'),
(1844, 4, NULL, 'symptom_query', 'minh cung khong ro no tu nhien chong mat thoi | chat do sang h minh chua an gi', 'user', '2025-07-23 14:49:52'),
(1845, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có thấy thêm cảm giác gì khác không, như là **hoa mắt**, **mệt mỏi** hay có cảm giác gì không ổn khác không nha?', 'bot', '2025-07-23 14:49:52'),
(1846, 4, NULL, 'symptom_query', 'khong co', 'user', '2025-07-23 14:50:42'),
(1847, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **đau đầu**, **chóng mặt**, và **buồn nôn**. Những triệu chứng này có thể liên quan đến một số nguyên nhân nhẹ nhàng như sau:\n\n**Đau đầu** có thể là do bạn thiếu ngủ hoặc căng thẳng. Bạn thử nghỉ ngơi xem sao nha.\n\n**Chóng mặt** có thể do thay đổi tư thế đột ngột hoặc thiếu nước nhẹ. Bạn có thể thử uống nước từ từ và ngồi nghỉ.\n\n**Buồn nôn** có thể là do dạ dày không được thoải mái hoặc căng thẳng. Bạn có thể thử uống một tách trà nhẹ để dịu dạ dày.\n\nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau:\n\n📌 **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.\n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.\n\n📌 **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **đau đầu** và **buồn nôn**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n\n—\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha 🌿\nNếu bạn muốn chắc chắn, bạn có thể đi khám để kiểm tra kỹ hơn.', 'bot', '2025-07-23 14:50:42'),
(1848, 4, NULL, 'symptom_query', 'khong co', 'user', '2025-07-23 14:53:37'),
(1849, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **đau đầu**, **chóng mặt**, và **buồn nôn**. **Đau đầu** có thể là do bạn thiếu ngủ hoặc căng thẳng. Bạn thử nghỉ ngơi xem sao nha. **Chóng mặt** có thể do thay đổi tư thế đột ngột hoặc thiếu nước nhẹ. Bạn có thể thử uống nước từ từ và ngồi nghỉ. **Buồn nôn** có thể là do dạ dày không được thoải mái hoặc căng thẳng. Bạn có thể thử uống một tách trà nhẹ để dịu dạ dày.\n\nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau:\n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn chưa ăn gì.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.\n\n📌 **Thiếu năng lượng nhẹ**  \nCơ thể bị hạ đường huyết tạm thời nếu nhịn ăn lâu.  \n→ Bạn có thể ăn nhẹ hoặc uống sữa để lấy lại sức.\n\n�� **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **đau đầu** và **buồn nôn**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n\n—\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha ��\nNếu bạn muốn chắc chắn, bạn có thể đi khám để kiểm tra kỹ hơn.', 'bot', '2025-07-23 14:53:37'),
(1850, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-23 14:59:00'),
(1851, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè. Cảm giác đó thường kéo dài bao lâu mỗi lần bạn gặp vậy?', 'bot', '2025-07-23 14:59:00'),
(1852, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-23 15:01:17'),
(1853, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè. Cảm giác đó thường kéo dài bao lâu mỗi lần bạn gặp vậy?', 'bot', '2025-07-23 15:01:17'),
(1854, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-23 15:02:24'),
(1855, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **đau đầu**, **chóng mặt**, và **buồn nôn**. Những triệu chứng này có thể do nhiều nguyên nhân khác nhau gây ra.\n\n**Đau đầu** có thể là do bạn thiếu ngủ hoặc căng thẳng. Bạn thử nghỉ ngơi xem sao nha.\n\n**Chóng mặt** có thể do thay đổi tư thế đột ngột hoặc thiếu nước nhẹ. Bạn có thể thử uống nước từ từ và ngồi nghỉ.\n\n**Buồn nôn** có thể xảy ra khi cơ thể bạn căng thẳng hoặc gặp vấn đề về tiêu hóa. Nghỉ ngơi và giữ cho cơ thể đủ nước có thể giúp bạn cảm thấy dễ chịu hơn.\n\nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau:\n\n📌 **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **đau đầu** và **buồn nôn**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n\n📌 **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.\n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.\n\n—\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha 🌿\nNếu cần, mình có thể hỗ trợ bạn đặt lịch khám phù hợp nha.', 'bot', '2025-07-23 15:02:24'),
(1856, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-23 15:03:01'),
(1857, 4, NULL, 'symptom_query', 'Cảm giác **đau đầu** của bạn thường kéo dài bao lâu mỗi lần gặp phải vậy? Nó có hay đi kèm với cảm giác mệt mỏi hoặc buồn nôn không ha?', 'bot', '2025-07-23 15:03:01'),
(1858, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-23 15:03:21'),
(1859, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **chóng mặt** của bạn nè. Bạn có thấy tình trạng này xảy ra khi đang ngồi hay lúc vừa đứng lên không? 🌿', 'bot', '2025-07-23 15:03:22'),
(1860, 4, NULL, 'symptom_query', 'minh cung khong ro no tu nhien chong mat thoi | chat do sang h minh chua an gi', 'user', '2025-07-23 15:03:48'),
(1861, 4, NULL, 'symptom_query', 'Mình đang muốn hỏi thêm về cảm giác **buồn nôn** của bạn nè. Bạn có thấy cảm giác này xảy ra sau khi ăn hay lúc nào khác không ha?', 'bot', '2025-07-23 15:03:48'),
(1862, 4, NULL, 'symptom_query', 'khi minh ngui thay 1 mui gi do kha nang thi co cam giac buon non', 'user', '2025-07-23 15:04:15'),
(1863, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có thấy thêm cảm giác nào khác như **tim đập nhanh**, **mệt mỏi**, hay **tức ngực** không nhỉ? Những điều đó có thể giúp mình hiểu rõ hơn về tình trạng hiện tại của bạn.', 'bot', '2025-07-23 15:04:15'),
(1864, 4, NULL, 'symptom_query', 'khong co', 'user', '2025-07-23 15:04:54'),
(1865, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **đau đầu**, **chóng mặt**, và **buồn nôn**. Những triệu chứng này có thể là dấu hiệu của một số tình trạng khác nhau. \n\n**Đau đầu** có thể là do bạn thiếu ngủ hoặc căng thẳng. Bạn thử nghỉ ngơi xem sao nha.\n\n**Chóng mặt** có thể do thay đổi tư thế đột ngột hoặc thiếu nước nhẹ. Bạn có thể thử uống nước từ từ và ngồi nghỉ.\n\n**Buồn nôn** đôi khi xảy ra khi dạ dày không ổn định hoặc do căng thẳng. Hãy thử ăn nhẹ và nghỉ ngơi một chút.\n\nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau:  \n\n�� **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **đau đầu** và **buồn nôn**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.  \n\n�� **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.  \n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.  \n\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha 🌿  \nNếu bạn muốn chắc chắn, bạn có thể đi khám để kiểm tra kỹ hơn.', 'bot', '2025-07-23 15:04:54'),
(1866, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-23 15:07:31'),
(1867, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **đau đầu** của bạn nè. Có khi nào bạn cảm thấy **đau đầu** nhiều hơn khi đang làm việc hay khi nghỉ ngơi không? ��', 'bot', '2025-07-23 15:07:31'),
(1868, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-23 15:11:28'),
(1869, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **đau đầu** kiểu nhói ở thái dương, kèm theo **chóng mặt** và **buồn nôn** nhẹ. **Đau đầu** có thể là do bạn thiếu ngủ hoặc căng thẳng. Bạn thử nghỉ ngơi xem sao nha. **Chóng mặt** có thể do thay đổi tư thế đột ngột hoặc thiếu nước nhẹ. Bạn có thể thử uống nước từ từ và ngồi nghỉ. **Buồn nôn** thường liên quan đến cảm giác không thoải mái trong dạ dày, có thể do căng thẳng hoặc lo âu.\n\nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau:\n\n📌 **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **đau đầu** và **buồn nôn**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n\n�� **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.\n\n�� **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.\n\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha 🌿\n\nNếu bạn muốn chắc chắn, bạn có thể đi khám để kiểm tra kỹ hơn.', 'bot', '2025-07-23 15:11:28'),
(1870, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-23 15:13:22'),
(1871, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **đau đầu**, **chóng mặt**, và **buồn nôn**. Những triệu chứng này có thể xuất phát từ nhiều nguyên nhân khác nhau.\n\n**Đau đầu** có thể là do bạn thiếu ngủ hoặc căng thẳng. Bạn thử nghỉ ngơi xem sao nha.\n**Chóng mặt** có thể do thay đổi tư thế đột ngột hoặc thiếu nước nhẹ. Bạn có thể thử uống nước từ từ và ngồi nghỉ.\n**Buồn nôn** có thể là dấu hiệu của dạ dày không ổn định hoặc căng thẳng. Nếu có thể, bạn hãy thử ăn một chút gì đó nhẹ để xem có đỡ hơn không.\n\nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau:\n\n📌 **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **đau đầu** và **buồn nôn**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n\n📌 **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.\n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.\n\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha ��\n\nNếu cần, mình có thể hỗ trợ bạn đặt lịch khám phù hợp nha.', 'bot', '2025-07-23 15:13:22'),
(1872, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-23 15:17:43'),
(1873, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có thấy cơn **đau đầu** này kéo dài bao lâu rồi? Có khi nào bạn thấy đỡ hơn sau khi nghỉ ngơi không ha?', 'bot', '2025-07-23 15:17:44'),
(1874, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-23 15:18:20'),
(1875, 4, NULL, 'symptom_query', 'Mình đang muốn hỏi thêm một chút về cảm giác **chóng mặt** của bạn nè. Có phải cảm giác đó thường xuất hiện khi bạn vừa đứng lên hoặc thay đổi tư thế không? Hay là nó xảy ra khi bạn đang ngồi yên?', 'bot', '2025-07-23 15:18:20'),
(1876, 4, NULL, 'symptom_query', 'minh cung khong ro no tu nhien chong mat thoi | chat do sang h minh chua an gi', 'user', '2025-07-23 15:19:00'),
(1877, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **đau đầu**, **chóng mặt**, và **buồn nôn**. Những triệu chứng này có thể xuất phát từ một số nguyên nhân khác nhau. \n\n**Đau đầu** có thể là do bạn thiếu ngủ hoặc căng thẳng. Bạn thử nghỉ ngơi xem sao nha. \n**Chóng mặt** có thể do thay đổi tư thế đột ngột hoặc thiếu nước nhẹ. Bạn có thể thử uống nước từ từ và ngồi nghỉ. \n**Buồn nôn** có thể liên quan đến dạ dày hoặc căng thẳng, bạn có thể thử ăn nhẹ nhàng xem có đỡ hơn không. \n\nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau: \n\n�� **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **đau đầu** và **buồn nôn**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân. \n\n�� **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày. \n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha. \n\n—\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha 🌿\nNếu bạn muốn chắc chắn, bạn có thể đi khám để kiểm tra kỹ hơn.', 'bot', '2025-07-23 15:19:01'),
(1878, 4, NULL, 'booking_request', 'um giup minh dat lich kham di', 'user', '2025-07-23 15:37:44'),
(1879, 4, NULL, 'booking_request', 'Dựa trên triệu chứng đau đầu, chóng mặt và buồn nôn, bạn có thể cần khám chuyên khoa Thần kinh. \n\nBạn có thể cho mình biết bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất nhé?Dựa trên triệu chứng đau đầu, chóng mặt và buồn nôn, bạn có thể cần khám chuyên khoa Thần kinh. \n\nBạn có thể cho mình biết bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất nhé?', 'bot', '2025-07-23 15:37:44'),
(1880, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-23 15:38:24'),
(1881, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết triệu chứng hoặc lý do bạn muốn khám để mình có thể giúp bạn chọn chuyên khoa phù hợp không?Bạn có thể cho mình biết triệu chứng hoặc lý do bạn muốn khám để mình có thể giúp bạn chọn chuyên khoa phù hợp không?', 'bot', '2025-07-23 15:38:24'),
(1882, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-23 15:42:38'),
(1883, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết bạn muốn khám bệnh gì không? Mình sẽ giúp bạn đặt lịch khám.', 'bot', '2025-07-23 15:42:38'),
(1884, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-23 15:42:58'),
(1885, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết bạn đang gặp vấn đề sức khỏe gì không? Mình sẽ giúp bạn tìm chuyên khoa phù hợp.', 'bot', '2025-07-23 15:42:58'),
(1886, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-23 15:46:44'),
(1887, 4, NULL, 'booking_request', 'Dựa trên vấn đề bạn nêu ra về tim mạch, mình đã xác định chuyên khoa Tim mạch là phù hợp.\n\nBạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-23 15:46:44'),
(1888, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-23 15:49:28'),
(1889, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết bạn đang gặp vấn đề gì về sức khỏe không? Mình sẽ giúp bạn chọn chuyên khoa phù hợp.', 'bot', '2025-07-23 15:49:29'),
(1890, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-23 15:50:09'),
(1891, 4, NULL, 'booking_request', 'Dựa trên yêu cầu khám tim mạch của bạn, mình sẽ giúp bạn đặt lịch. Bạn có thể cho mình biết bạn ở khu vực nào để mình tìm phòng khám gần nhất nhé?', 'bot', '2025-07-23 15:50:09'),
(1892, 4, NULL, 'booking_request', 'minh song o tphcm', 'user', '2025-07-23 15:52:56'),
(1893, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết số điện thoại của bạn được không?', 'bot', '2025-07-23 15:52:56'),
(1894, 4, NULL, 'booking_request', '0901111111', 'user', '2025-07-23 15:53:50'),
(1895, 4, NULL, 'booking_request', 'Tôi đã ghi nhận thông tin của bạn. Bây giờ, mình sẽ giúp bạn tìm phòng khám tim mạch gần nhất. Bạn có muốn đặt lịch khám không?\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM', 'bot', '2025-07-23 15:53:50'),
(1896, 4, NULL, 'booking_request', 'Bệnh viện Chợ Rẫy', 'user', '2025-07-23 15:54:15'),
(1897, 4, NULL, 'booking_request', '', 'bot', '2025-07-23 15:54:15'),
(1898, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-23 15:55:49'),
(1899, 4, NULL, 'booking_request', 'Dựa trên triệu chứng của bạn như đau đầu, chóng mặt, và buồn nôn, bạn có thể cần khám chuyên khoa Nội khoa hoặc Thần kinh.\n\nBạn muốn đặt lịch khám ở chuyên khoa nào?', 'bot', '2025-07-23 15:55:49'),
(1900, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-23 15:56:07'),
(1901, 4, NULL, 'booking_request', 'Dựa trên triệu chứng của bạn, bạn có thể cần khám chuyên khoa Tim mạch.\n\nBạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?', 'bot', '2025-07-23 15:56:07'),
(1902, 4, NULL, 'booking_request', 'tphcm', 'user', '2025-07-23 15:56:27'),
(1903, 4, NULL, 'booking_request', 'Bạn vui lòng cung cấp số điện thoại của mình để mình giúp bạn đặt lịch khám nhé.', 'bot', '2025-07-23 15:56:27'),
(1904, 4, NULL, 'booking_request', '0901111111', 'user', '2025-07-23 15:56:49'),
(1905, 4, NULL, 'booking_request', 'Tôi đã ghi nhận thông tin của bạn. Bây giờ, mình sẽ tìm kiếm phòng khám chuyên khoa Tim mạch gần bạn tại TP.HCM.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM', 'bot', '2025-07-23 15:56:49'),
(1906, 4, NULL, 'booking_request', 'Bệnh viện Chợ Rẫy', 'user', '2025-07-23 15:57:04'),
(1907, 4, NULL, 'booking_request', '', 'bot', '2025-07-23 15:57:04'),
(1908, 4, NULL, 'booking_request', 'Minh muon dat lich kham', 'user', '2025-07-23 16:01:02'),
(1909, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết rõ hơn về vấn đề sức khỏe mà bạn đang gặp phải không? Mình sẽ giúp bạn tìm chuyên khoa phù hợp.', 'bot', '2025-07-23 16:01:02'),
(1910, 4, NULL, 'booking_request', 'minh muon kham tim mach', 'user', '2025-07-23 16:01:10'),
(1911, 4, NULL, 'booking_request', 'Dựa trên yêu cầu khám tim mạch của bạn, mình cần biết bạn ở khu vực nào để tìm phòng khám gần nhất nhé.', 'bot', '2025-07-23 16:01:10'),
(1912, 4, NULL, 'booking_request', 'minh song o tphcm', 'user', '2025-07-23 16:01:23'),
(1913, 4, NULL, 'booking_request', 'Bạn có thể cho mình biết số điện thoại của bạn để mình có thể đặt lịch khám được không?', 'bot', '2025-07-23 16:01:23'),
(1914, 4, NULL, 'booking_request', '090111111111', 'user', '2025-07-23 16:01:34'),
(1915, 4, NULL, 'booking_request', 'Mình đã ghi nhận thông tin của bạn. Bây giờ mình sẽ tìm kiếm phòng khám tim mạch gần bạn ở TP.HCM nhé.\n\n- Bệnh viện Chợ Rẫy - 201B Nguyễn Chí Thanh, Quận 5, TP.HCM\n- Phòng khám Đa khoa Hòa Hảo - 254 Hòa Hảo, Quận 10, TP.HCM', 'bot', '2025-07-23 16:01:34'),
(1916, 4, NULL, 'booking_request', 'Bệnh viện Chợ Rẫy', 'user', '2025-07-23 16:01:43'),
(1917, 4, NULL, 'booking_request', 'Bạn đã chọn Bệnh viện Chợ Rẫy. Bây giờ, bạn muốn xem bác sĩ nào hoặc lịch khám ra sao?', 'bot', '2025-07-23 16:01:43'),
(1918, 4, NULL, 'booking_request', 'cho minh xem danh sach bac si di', 'user', '2025-07-23 16:01:55'),
(1919, 4, NULL, 'booking_request', 'Bạn muốn xem danh sách bác sĩ trước, hay xem các khung giờ khám trước ạ? Mình sẽ hiển thị danh sách phù hợp để bạn lựa chọn nhé.', 'bot', '2025-07-23 16:01:55'),
(1920, 4, NULL, 'booking_request', 'cho minh xem danh sach bac si di', 'user', '2025-07-23 16:02:20'),
(1921, 4, NULL, 'booking_request', 'Dưới đây là danh sách bác sĩ tại Bệnh viện Chợ Rẫy:\n- Dr.Linh: Bác sĩ Tim mạch từng công tác tại Viện Tim TP.HCM. Có bằng Thạc sĩ Y khoa từ Đại học Paris, Pháp.', 'bot', '2025-07-23 16:02:20'),
(1922, 4, NULL, 'booking_request', 'ok vậy chọn xác sĩ linh đi', 'user', '2025-07-23 16:02:55'),
(1923, 4, NULL, 'booking_request', 'Bạn muốn đặt lịch với bác sĩ Linh vào thời gian nào? Dưới đây là các khung giờ khám:\n- Buổi sáng 1 (09:00 - 12:00) vào thứ 2\n- Buổi chiều 2 (14:00 - 17:00) vào thứ 3\n- Buổi sáng 3 (09:00 - 12:00) vào thứ 4\n- Buổi chiều 4 (14:00 - 17:00) vào thứ 5\n- Buổi sáng 5 (09:00 - 12:00) vào thứ 6.', 'bot', '2025-07-23 16:02:55'),
(1924, 4, NULL, 'booking_request', 'vậy thứ 3 đi', 'user', '2025-07-23 16:03:08'),
(1925, 4, NULL, 'booking_request', 'Dưới đây là các lịch khám của bác sĩ Dr.Linh:\n- Buổi chiều Thứ Ba (14:00 - 17:00)\n- Buổi sáng Thứ Tư (09:00 - 12:00)\n- Buổi chiều Thứ Năm (14:00 - 17:00)\n- Buổi sáng Thứ Sáu (09:00 - 12:00). Bạn muốn đặt lịch vào thời gian nào ạ?', 'bot', '2025-07-23 16:03:08'),
(1926, 4, NULL, 'booking_request', 'vậy thứ 3 đi', 'user', '2025-07-23 16:03:27'),
(1927, 4, NULL, 'booking_request', '✅ Bạn đã chọn đầy đủ thông tin:\nHọ tên: Nguyễn Văn A\nSĐT: 090111111111\nKhu vực: TP.HCM\nChuyên khoa: Tim mạch\nPhòng khám: Bệnh viện Chợ Rẫy\nBác sĩ: Dr.Linh\nLịch hẹn: Buổi chiều 2 (14:00 - 17:00)\n\nBạn xác nhận đặt lịch này chứ?', 'bot', '2025-07-23 16:03:27'),
(1928, 4, NULL, 'booking_request', 'ok minh xac nhan', 'user', '2025-07-23 16:09:31'),
(1929, 4, NULL, 'booking_request', '✅ Đã đặt lịch thành công! Mã lịch hẹn của bạn là #10.\nLịch khám: Buổi chiều 2 (14:00 - 17:00)\nChúc bạn sức khỏe tốt!', 'bot', '2025-07-23 16:09:31'),
(1930, 2, NULL, 'general_chat', 'hello', 'user', '2025-08-05 05:53:44'),
(1931, 2, NULL, 'general_chat', 'Cảm ơn bạn đã nhắn tin! Bạn cần hỗ trợ gì hôm nay không?', 'bot', '2025-08-05 05:53:44'),
(1932, 2, NULL, 'general_chat', 'hello?', 'user', '2025-08-05 05:54:01'),
(1933, 2, NULL, 'general_chat', 'Um, mình hiểu rồi nè! Bạn cần hỗ trợ gì hôm nay không?', 'bot', '2025-08-05 05:54:01'),
(1934, 2, NULL, 'general_chat', 'ban la ai?', 'user', '2025-08-05 05:54:13'),
(1935, 2, NULL, 'general_chat', 'Um, mình là trợ lý ảo của KMS Health Care, sẵn sàng hỗ trợ bạn với mọi câu hỏi hay nhu cầu về sức khỏe. Bạn cần giúp gì hôm nay không?', 'bot', '2025-08-05 05:54:13'),
(1936, 2, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-08-05 05:56:20'),
(1937, 2, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nhé. Cảm giác này thường kéo dài bao lâu mỗi lần bạn gặp phải vậy?', 'bot', '2025-08-05 05:56:20'),
(1938, 2, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac dau dau va no keo dai tam 5-10p gi do', 'user', '2025-08-05 05:56:51'),
(1939, 2, NULL, 'symptom_query', 'Khi bạn cảm thấy **chóng mặt**, có khi nào cảm giác này xuất hiện khi bạn vừa đứng lên hay khi đang ngồi không? Mình muốn biết thêm về trải nghiệm của bạn.', 'bot', '2025-08-05 05:56:52'),
(1940, 2, NULL, 'symptom_query', 'minh cung khong ro no tu nhien chong mat thoi | chat do sang h minh chua an gi', 'user', '2025-08-05 05:58:54'),
(1941, 2, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **buồn nôn** của bạn nè. Có khi nào nó xuất hiện sau khi bạn ăn hay uống gì không, hay nó thường xảy ra vào lúc nào?', 'bot', '2025-08-05 05:58:54'),
(1942, 2, NULL, 'symptom_query', 'khi minh ngui thay 1 mui gi do kha nang thi co cam giac buon non', 'user', '2025-08-05 05:59:08'),
(1943, 2, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có thêm cảm giác nào khác nữa không, như là mệt mỏi, hoặc có cảm giác như tim đập nhanh không ha? Điều này có thể giúp mình hiểu rõ hơn về tình trạng của bạn.', 'bot', '2025-08-05 05:59:08'),
(1944, 2, NULL, 'symptom_query', 'khong co', 'user', '2025-08-05 06:00:04'),
(1945, 2, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **đau đầu**, **chóng mặt**, và **buồn nôn**. Những triệu chứng này có thể liên quan đến một số nguyên nhân nhẹ nhàng như căng thẳng, thiếu ngủ hoặc thay đổi thời tiết.\n\n**Đau đầu** có thể là do bạn thiếu ngủ hoặc căng thẳng. Bạn thử nghỉ ngơi xem sao nha.\n\n**Chóng mặt** có thể do thay đổi tư thế đột ngột hoặc thiếu nước nhẹ. Bạn có thể thử uống nước từ từ và ngồi nghỉ.\n\n**Buồn nôn** có thể xảy ra khi bạn đang căng thẳng hoặc không ăn uống điều độ. Hãy thử ăn một bữa nhẹ nhàng và xem cảm giác ra sao nhé.\n\nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau:\n\n�� **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.\n\n�� **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **đau đầu** và **buồn nôn**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.\n\n—\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha 🌿\nNếu bạn muốn chắc chắn, bạn có thể đi khám để kiểm tra kỹ hơn.', 'bot', '2025-08-05 06:00:04');

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
-- Table structure for table `clinic_specialties`
--

CREATE TABLE `clinic_specialties` (
  `clinic_id` int(11) NOT NULL,
  `specialty_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clinic_specialties`
--

INSERT INTO `clinic_specialties` (`clinic_id`, `specialty_id`) VALUES
(1, 1),
(1, 3),
(1, 4),
(1, 7),
(2, 1),
(2, 2),
(2, 4),
(2, 8),
(3, 1),
(3, 5),
(3, 6);

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
-- Table structure for table `doctor_off_days`
--

CREATE TABLE `doctor_off_days` (
  `off_day_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `off_date` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor_off_days`
--

INSERT INTO `doctor_off_days` (`off_day_id`, `doctor_id`, `off_date`, `reason`, `created_at`) VALUES
(1, 1, '2025-07-25', 'Đi công tác', '2025-07-20 06:51:05'),
(2, 2, '2025-07-30', 'Nghỉ phép', '2025-07-20 06:51:05');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_schedules`
--

CREATE TABLE `doctor_schedules` (
  `schedule_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `clinic_id` int(11) DEFAULT NULL,
  `day_of_week` int(11) NOT NULL COMMENT '1-7: Thứ 2 - Chủ nhật',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor_schedules`
--

INSERT INTO `doctor_schedules` (`schedule_id`, `doctor_id`, `clinic_id`, `day_of_week`, `start_time`, `end_time`, `is_available`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, '08:00:00', '12:00:00', 1, '2025-05-24 06:25:08', '2025-05-24 13:25:08'),
(2, 1, 1, 2, '08:00:00', '12:00:00', 1, '2025-05-24 06:25:08', '2025-05-24 13:25:08'),
(3, 1, 1, 3, '13:30:00', '17:30:00', 1, '2025-05-24 06:25:08', '2025-05-24 13:25:08'),
(4, 1, 1, 4, '08:00:00', '12:00:00', 1, '2025-05-24 06:25:08', '2025-05-24 13:25:08'),
(5, 1, 1, 5, '13:30:00', '17:30:00', 1, '2025-05-24 06:25:08', '2025-05-24 13:25:08'),
(6, 2, 2, 1, '09:00:00', '12:00:00', 0, '2025-05-24 06:25:08', '2025-07-21 13:41:11'),
(7, 2, 2, 2, '14:00:00', '17:00:00', 0, '2025-05-24 06:25:08', '2025-07-21 13:41:11'),
(8, 2, 2, 3, '09:00:00', '12:00:00', 0, '2025-05-24 06:25:08', '2025-07-21 13:41:11'),
(9, 2, 2, 4, '14:00:00', '17:00:00', 0, '2025-05-24 06:25:08', '2025-07-21 13:41:12'),
(10, 2, 2, 5, '09:00:00', '12:00:00', 0, '2025-05-24 06:25:08', '2025-07-21 13:41:12'),
(11, 1, NULL, 6, '08:00:00', '12:00:00', 1, '2025-07-20 16:42:29', '2025-07-20 23:42:29'),
(12, 2, NULL, 7, '09:00:00', '12:00:00', 1, '2025-07-21 06:41:34', '2025-07-21 13:41:34');

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL,
  `recipient` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('success','failed','error') NOT NULL,
  `error_message` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_logs`
--

INSERT INTO `email_logs` (`id`, `recipient`, `subject`, `status`, `error_message`, `sent_at`) VALUES
(1, 'admin@gmail.com', 'Xác nhận đơn hàng #1 - MediSync Hospital', 'success', 'Email sent successfully via SMTP', '2025-07-20 17:14:18'),
(2, 'hoanhuy12@gmail.com', 'Đặt lại mật khẩu - QickMed', 'success', 'Email sent successfully via SMTP', '2025-07-21 06:23:11'),
(3, 'dvtdang1101@gmail.com', 'Đặt lại mật khẩu - QickMed', 'success', 'Email sent successfully via SMTP', '2025-07-21 06:23:24'),
(4, 'hoanhuy12@gmail.com', 'Xác nhận đơn hàng #2 - MediSync Hospital', 'success', 'Email sent successfully via SMTP', '2025-08-05 06:10:04');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `health_predictions`
--

INSERT INTO `health_predictions` (`prediction_id`, `user_id`, `record_id`, `chat_id`, `prediction_date`, `confidence_score`, `details`) VALUES
(37, 4, 37, NULL, '2025-06-25 10:43:45', 0.9, '{\"symptoms\": [\"T\\u00ea tay ch\\u00e2n\", \"M\\u1ec7t m\\u1ecfi\", \"Ch\\u00f3ng m\\u1eb7t\"], \"diseases\": [{\"name\": \"\\u0110\\u1ed9t qu\\u1ef5 nh\\u1eb9\", \"confidence\": 0.9, \"summary\": \"T\\u00ecnh tr\\u1ea1ng khi m\\u00e1u kh\\u00f4ng \\u0111\\u1ee7 cung c\\u1ea5p cho m\\u1ed9t ph\\u1ea7n n\\u00e3o, g\\u00e2y ra t\\u00ea v\\u00e0 kh\\u00f3 n\\u00f3i.\", \"care\": \"C\\u1ea7n \\u0111\\u1ebfn g\\u1eb7p b\\u00e1c s\\u0129 ngay n\\u1ebfu tri\\u1ec7u ch\\u1ee9ng nghi\\u00eam tr\\u1ecdng.\"}, {\"name\": \"Thi\\u1ebfu vitamin B12\", \"confidence\": 0.7, \"summary\": \"Thi\\u1ebfu vitamin n\\u00e0y c\\u00f3 th\\u1ec3 g\\u00e2y ra t\\u00ea v\\u00e0 y\\u1ebfu c\\u01a1, c\\u0169ng nh\\u01b0 c\\u00e1c v\\u1ea5n \\u0111\\u1ec1 v\\u1ec1 tr\\u00ed nh\\u1edb.\", \"care\": \"B\\u1ed5 sung th\\u1ef1c ph\\u1ea9m gi\\u00e0u vitamin B12 nh\\u01b0 th\\u1ecbt, c\\u00e1 v\\u00e0 s\\u1ea3n ph\\u1ea9m t\\u1eeb s\\u1eefa.\"}, {\"name\": \"R\\u1ed1i lo\\u1ea1n lo \\u00e2u ho\\u1eb7c c\\u0103ng th\\u1eb3ng\", \"confidence\": 0.6, \"summary\": \"C\\u0103ng th\\u1eb3ng c\\u00f3 th\\u1ec3 d\\u1eabn \\u0111\\u1ebfn c\\u1ea3m gi\\u00e1c m\\u1ec7t m\\u1ecfi v\\u00e0 ch\\u00f3ng m\\u1eb7t.\", \"care\": \"Th\\u01b0 gi\\u00e3n v\\u00e0 t\\u00ecm c\\u00e1ch gi\\u1ea3m c\\u0103ng th\\u1eb3ng c\\u00f3 th\\u1ec3 gi\\u00fap \\u00edch.\"}]}'),
(38, 4, 38, NULL, '2025-06-25 11:13:39', 0.85, '{\"symptoms\": [\"Tê tay chân\", \"Mệt mỏi\", \"Chóng mặt\", \"Đau cơ\"], \"diseases\": [{\"name\": \"Thiếu vitamin B12\", \"confidence\": 0.85, \"summary\": \"Thiếu hụt vitamin B12 có thể gây ra cảm giác tê và mệt mỏi.\", \"care\": \"Bổ sung thực phẩm như thịt, cá, trứng và sữa.\"}, {\"name\": \"Rối loạn tuần hoàn\", \"confidence\": 0.75, \"summary\": \"Rối loạn tuần hoàn có thể dẫn đến cảm giác tê tay chân và chóng mặt.\", \"care\": \"Vận động nhẹ nhàng để cải thiện tuần hoàn máu.\"}, {\"name\": \"Căng thẳng hoặc lo âu\", \"confidence\": 0.6, \"summary\": \"Căng thẳng có thể gây ra các triệu chứng như tê và mệt mỏi.\", \"care\": \"Thư giãn và nghỉ ngơi có thể giúp bạn cảm thấy tốt hơn.\"}]}'),
(39, 4, 39, NULL, '2025-06-25 12:49:55', 0.9, '{\"symptoms\": [\"Tê tay chân\", \"Khàn giọng\", \"Mệt mỏi\", \"Chóng mặt\"], \"diseases\": [{\"name\": \"Đột quỵ nhẹ\", \"confidence\": 0.9, \"summary\": \"Tình trạng khi máu không được cung cấp đủ cho một phần của não, có thể gây ra tê tay chân và khó nói.\", \"care\": \"Cần đến bác sĩ ngay nếu triệu chứng kéo dài hoặc nặng hơn.\"}, {\"name\": \"Thiếu vitamin B12\", \"confidence\": 0.6, \"summary\": \"Thiếu hụt vitamin B12 có thể dẫn đến tê bì tay chân và mệt mỏi.\", \"care\": \"Bổ sung thực phẩm giàu vitamin B12 như thịt, cá, trứng và sữa.\"}, {\"name\": \"Rối loạn lo âu\", \"confidence\": 0.3, \"summary\": \"Căng thẳng và lo âu có thể gây ra cảm giác mệt mỏi và chóng mặt.\", \"care\": \"Thử các phương pháp thư giãn như yoga hoặc thiền.\"}]}'),
(40, 4, 40, NULL, '2025-06-25 13:00:31', 0.85, '{\"symptoms\": [\"Tê tay chân\", \"Khàn giọng\", \"Mệt mỏi\", \"Chóng mặt\"], \"diseases\": [{\"name\": \"Thiếu nước\", \"confidence\": 0.85, \"summary\": \"Khi cơ thể không đủ nước, có thể gây ra cảm giác mệt mỏi, chóng mặt.\", \"care\": \"Uống đủ nước trong suốt cả ngày.\"}, {\"name\": \"Căng thẳng hoặc mệt mỏi\", \"confidence\": 0.85, \"summary\": \"Cảm giác mệt mỏi và chóng mặt có thể do căng thẳng hoặc thiếu ngủ.\", \"care\": \"Thư giãn và nghỉ ngơi có thể giúp bạn cảm thấy tốt hơn.\"}, {\"name\": \"Vấn đề về tuần hoàn\", \"confidence\": 0.85, \"summary\": \"Tê tay chân và chóng mặt có thể liên quan đến vấn đề tuần hoàn.\", \"care\": \"Nếu triệu chứng kéo dài, bạn nên đến gặp bác sĩ để kiểm tra kỹ hơn.\"}]}'),
(41, 4, 41, NULL, '2025-06-25 16:14:07', 0.9, '{\"symptoms\": [\"Tê tay chân\", \"Khàn giọng\"], \"diseases\": [{\"name\": \"Đột quỵ nhẹ\", \"confidence\": 0.9, \"summary\": \"Tình trạng khi máu không đến được một phần của não, có thể gây ra tê liệt hoặc yếu ở một bên cơ thể.\", \"care\": \"Theo dõi triệu chứng và đến gặp bác sĩ ngay nếu có dấu hiệu nghiêm trọng.\"}, {\"name\": \"Rối loạn thần kinh\", \"confidence\": 0.7, \"summary\": \"Các vấn đề liên quan đến hệ thần kinh có thể ảnh hưởng đến khả năng điều khiển cơ bắp và trí nhớ.\", \"care\": \"Thăm khám bác sĩ để được tư vấn và điều trị kịp thời.\"}]}'),
(42, 4, 42, NULL, '2025-06-25 16:55:55', 0.9, '{\"symptoms\": [\"Tê tay chân\", \"Khàn giọng\"], \"diseases\": [{\"name\": \"Đột quỵ nhẹ\", \"confidence\": 0.9, \"summary\": \"Tình trạng khi máu không đến được một phần của não, gây ra tê hoặc yếu ở một bên cơ thể và khó nói.\", \"care\": \"Theo dõi triệu chứng và đến gặp bác sĩ ngay nếu có dấu hiệu nghiêm trọng.\"}, {\"name\": \"Rối loạn thần kinh\", \"confidence\": 0.7, \"summary\": \"Một số rối loạn có thể gây ra triệu chứng như tê và khó nói.\", \"care\": \"Theo dõi thêm tình trạng của bạn và tìm sự hỗ trợ từ chuyên gia nếu cần.\"}]}'),
(43, 4, 43, NULL, '2025-06-25 17:08:45', 0.9, '{\"symptoms\": [\"Tê tay chân\", \"Khàn giọng\"], \"diseases\": [{\"name\": \"Đột quỵ nhẹ\", \"confidence\": 0.9, \"summary\": \"Đột quỵ nhẹ có thể gây ra triệu chứng tê hoặc yếu ở một bên cơ thể và khó khăn trong việc nói.\", \"care\": \"Nếu có triệu chứng này, bạn nên đến gặp bác sĩ ngay.\"}, {\"name\": \"Căng thẳng\", \"confidence\": 0.6, \"summary\": \"Căng thẳng có thể gây ra cảm giác tê và khàn giọng.\", \"care\": \"Thư giãn và nghỉ ngơi có thể giúp cải thiện tình trạng này.\"}]}'),
(45, 4, 45, NULL, '2025-06-27 17:15:49', 0.85, '{\"symptoms\": [\"Đau đầu\", \"Chóng mặt\", \"Buồn nôn\", \"Mệt mỏi\"], \"predicted_diseases\": [\"Chóng mặt\", \"Căng thẳng\", \"Thiếu ngủ\"]}'),
(46, 4, 46, NULL, '2025-06-27 17:20:21', 0.9, '{\"symptoms\": [\"Đau đầu\", \"Chóng mặt\", \"Buồn nôn\", \"Mệt mỏi\"], \"predicted_diseases\": [\"Migrain\", \"Thiếu nước\", \"Căng thẳng\"]}'),
(53, 4, 53, NULL, '2025-07-01 16:04:23', 0.9, '{\"symptoms\": [\"Tê tay chân\", \"Khàn giọng\", \"Yếu cơ\", \"Đau đầu\"], \"predicted_diseases\": [\"Đột quỵ nhẹ\", \"Rối loạn thần kinh\", \"Thiếu máu\"]}'),
(60, 4, 60, NULL, '2025-07-03 11:38:57', 0.9, '{\"symptoms\": [\"Chóng mặt\"], \"predicted_diseases\": [\"Huyết áp thấp\", \"Thiếu năng lượng nhẹ\", \"Căng thẳng hoặc lo âu\"]}'),
(62, 4, 62, NULL, '2025-07-08 13:30:10', 0.9, '{\"symptoms\": [\"Đau đầu\", \"Chóng mặt\", \"Buồn nôn\"], \"predicted_diseases\": [\"Căng thẳng hoặc lo âu\", \"Mất nước hoặc thiếu dinh dưỡng\", \"Huyết áp thấp\"]}'),
(68, 4, 68, NULL, '2025-07-09 08:11:53', 0.9, '{\"symptoms\": [\"Đau đầu\", \"Chóng mặt\"], \"predicted_diseases\": [\"Căng thẳng hoặc lo âu\", \"Mất nước hoặc thiếu dinh dưỡng\", \"Huyết áp thấp\"]}'),
(69, 4, 69, NULL, '2025-07-11 16:50:24', 0.85, '{\"symptoms\": [], \"predicted_diseases\": [\"Căng thẳng hoặc lo âu\", \"Mất nước hoặc thiếu dinh dưỡng\", \"Huyết áp thấp\"]}'),
(70, 4, 70, NULL, '2025-07-12 10:56:49', 0.85, '{\"symptoms\": [], \"predicted_diseases\": [\"Huyết áp thấp\", \"Rối loạn nhịp tim\", \"Căng thẳng hoặc lo âu\"]}'),
(71, 4, 71, NULL, '2025-07-15 10:45:11', 0.9, '{\"symptoms\": [\"Đau đầu\", \"Chóng mặt\", \"Buồn nôn\"], \"predicted_diseases\": [\"Căng thẳng hoặc lo âu\", \"Mất nước hoặc thiếu dinh dưỡng\", \"Huyết áp thấp\"]}'),
(77, 4, 77, NULL, '2025-07-23 15:18:50', 0.9, '{\"symptoms\": [\"Đau đầu\", \"Chóng mặt\", \"Buồn nôn\"], \"predicted_diseases\": [\"Căng thẳng hoặc lo âu\", \"Mất nước hoặc thiếu dinh dưỡng\", \"Huyết áp thấp\"]}'),
(78, 2, 78, NULL, '2025-08-05 05:59:54', 0.9, '{\"symptoms\": [\"Đau đầu\", \"Chóng mặt\", \"Buồn nôn\"], \"predicted_diseases\": [\"Mất nước hoặc thiếu dinh dưỡng\", \"Căng thẳng hoặc lo âu\", \"Huyết áp thấp\"]}');

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
(36, 4, '2025-06-19', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Ho, Chảy nước mũi', '2025-06-19 08:39:46', '2025-06-19 15:39:46'),
(37, 4, '2025-06-25', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Tê tay chân, Mệt mỏi, Chóng mặt', '2025-06-25 10:43:45', '2025-06-25 17:43:45'),
(38, 4, '2025-06-25', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Tê tay chân, Mệt mỏi, Chóng mặt, Đau cơ', '2025-06-25 11:13:39', '2025-06-25 18:13:39'),
(39, 4, '2025-06-25', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Tê tay chân, Khàn giọng, Mệt mỏi, Chóng mặt', '2025-06-25 12:49:55', '2025-06-25 19:49:55'),
(40, 4, '2025-06-25', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Tê tay chân, Khàn giọng, Mệt mỏi, Chóng mặt', '2025-06-25 13:00:31', '2025-06-25 20:00:31'),
(41, 4, '2025-06-25', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Tê tay chân, Khàn giọng', '2025-06-25 16:14:07', '2025-06-25 23:14:07'),
(42, 4, '2025-06-25', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Tê tay chân, Khàn giọng', '2025-06-25 16:55:55', '2025-06-25 23:55:55'),
(43, 4, '2025-06-26', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Tê tay chân, Khàn giọng', '2025-06-25 17:08:45', '2025-06-26 00:08:45'),
(44, 4, '2025-06-27', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Đau đầu, Chóng mặt, Chóng mặt khi đứng dậy', '2025-06-27 14:12:05', '2025-06-27 21:12:05'),
(45, 4, '2025-06-28', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Đau đầu, Chóng mặt, Buồn nôn, Mệt mỏi', '2025-06-27 17:15:49', '2025-06-28 00:15:49'),
(46, 4, '2025-06-28', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Đau đầu, Chóng mặt, Buồn nôn, Mệt mỏi', '2025-06-27 17:20:21', '2025-06-28 00:20:21'),
(47, 4, '2025-07-01', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Đau đầu', '2025-06-30 17:36:13', '2025-07-01 00:36:13'),
(48, 4, '2025-07-01', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Tê tay chân, Khàn giọng, Yếu cơ, Đau đầu', '2025-07-01 10:59:40', '2025-07-01 17:59:40'),
(49, 4, '2025-07-01', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Khàn giọng, Yếu cơ, Đau đầu', '2025-07-01 13:31:10', '2025-07-01 20:31:10'),
(50, 4, '2025-07-01', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Khàn giọng, Yếu cơ, Đau đầu', '2025-07-01 14:10:33', '2025-07-01 21:10:33'),
(51, 4, '2025-07-01', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Khàn giọng, Yếu cơ, Đau đầu, Đau cơ, Mệt mỏi', '2025-07-01 15:21:47', '2025-07-01 22:21:47'),
(52, 4, '2025-07-01', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Tê tay chân, Khàn giọng, Yếu cơ, Đau đầu, Mệt mỏi', '2025-07-01 15:30:01', '2025-07-01 22:30:01'),
(53, 4, '2025-07-01', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Tê tay chân, Khàn giọng, Yếu cơ, Đau đầu', '2025-07-01 16:04:23', '2025-07-01 23:04:23'),
(54, 4, '2025-07-02', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Đau đầu, Chóng mặt, Buồn nôn, Đau bụng', '2025-07-02 08:56:02', '2025-07-02 15:56:02'),
(55, 4, '2025-07-02', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Đau đầu, Chóng mặt, Buồn nôn', '2025-07-02 11:27:40', '2025-07-02 18:27:40'),
(56, 4, '2025-07-02', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Đau đầu, Chóng mặt, Buồn nôn', '2025-07-02 13:30:44', '2025-07-02 20:30:44'),
(57, 4, '2025-07-02', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Đau đầu, Chóng mặt, Buồn nôn', '2025-07-02 13:32:53', '2025-07-02 20:32:53'),
(58, 4, '2025-07-03', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Đau đầu, Chóng mặt, Buồn nôn', '2025-07-02 17:28:29', '2025-07-03 00:28:29'),
(59, 4, '2025-07-03', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Đau đầu, Chóng mặt, Buồn nôn', '2025-07-03 08:33:55', '2025-07-03 15:33:55'),
(60, 4, '2025-07-03', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Đau đầu, Chóng mặt, Buồn nôn', '2025-07-03 11:38:57', '2025-07-03 18:38:57'),
(61, 4, '2025-07-08', NULL, NULL, NULL, 'Triệu chứng ghi nhận: ', '2025-07-08 12:04:29', '2025-07-08 19:04:29'),
(62, 4, '2025-07-08', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Đau đầu, Chóng mặt, Buồn nôn', '2025-07-08 13:30:10', '2025-07-08 20:30:10'),
(63, 4, '2025-07-09', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Đau đầu, Chóng mặt, Buồn nôn', '2025-07-09 07:22:38', '2025-07-09 14:22:38'),
(64, 4, '2025-07-09', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Đau đầu, Chóng mặt, Buồn nôn', '2025-07-09 07:31:39', '2025-07-09 14:31:39'),
(65, 4, '2025-07-09', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Đau đầu, Chóng mặt, Buồn nôn', '2025-07-09 07:46:11', '2025-07-09 14:46:11'),
(66, 4, '2025-07-09', NULL, NULL, NULL, 'Triệu chứng ghi nhận: ', '2025-07-09 08:05:31', '2025-07-09 15:05:31'),
(67, 4, '2025-07-09', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Đau đầu, Chóng mặt, Buồn nôn, Khó thở', '2025-07-09 08:08:26', '2025-07-09 15:08:26'),
(68, 4, '2025-07-09', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Đau đầu, Chóng mặt', '2025-07-09 08:11:53', '2025-07-09 15:11:53'),
(69, 4, '2025-07-11', NULL, NULL, NULL, 'Triệu chứng ghi nhận: ', '2025-07-11 16:50:24', '2025-07-11 23:50:24'),
(70, 4, '2025-07-12', NULL, NULL, NULL, 'Triệu chứng ghi nhận: ', '2025-07-12 10:56:49', '2025-07-12 17:56:49'),
(71, 4, '2025-07-15', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Đau đầu, Chóng mặt, Buồn nôn', '2025-07-15 10:45:11', '2025-07-15 17:45:11'),
(72, 4, '2025-07-23', NULL, NULL, NULL, 'Triệu chứng ghi nhận: ', '2025-07-23 14:39:10', '2025-07-23 21:39:10'),
(73, 4, '2025-07-23', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Chóng mặt', '2025-07-23 14:53:28', '2025-07-23 21:53:28'),
(74, 4, '2025-07-23', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Đau đầu, Chóng mặt, Buồn nôn', '2025-07-23 15:02:14', '2025-07-23 22:02:14'),
(75, 4, '2025-07-23', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Đau đầu', '2025-07-23 15:11:17', '2025-07-23 22:11:17'),
(76, 4, '2025-07-23', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Đau đầu, Chóng mặt, Buồn nôn', '2025-07-23 15:13:11', '2025-07-23 22:13:11'),
(77, 4, '2025-07-23', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Đau đầu, Chóng mặt, Buồn nôn', '2025-07-23 15:18:50', '2025-07-23 22:18:50'),
(78, 2, '2025-08-05', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Đau đầu, Chóng mặt, Buồn nôn', '2025-08-05 05:59:54', '2025-08-05 12:59:54');

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
  `product_id` int(11) NOT NULL,
  `active_ingredient` varchar(255) DEFAULT NULL,
  `dosage_form` varchar(100) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `usage_instructions` text DEFAULT NULL,
  `medicine_type` enum('OTC','Kê đơn','Kháng sinh','Bổ sung') DEFAULT 'OTC',
  `side_effects` text DEFAULT NULL,
  `contraindications` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`product_id`, `active_ingredient`, `dosage_form`, `unit`, `usage_instructions`, `medicine_type`, `side_effects`, `contraindications`, `created_at`, `updated_at`) VALUES
(1, 'Paracetamol', 'Viên nén', 'viên', 'Uống 1–2 viên mỗi 4–6 giờ nếu cần. Không dùng quá 8 viên/ngày.', 'OTC', 'Buồn nôn, phát ban nhẹ, rối loạn tiêu hoá (hiếm).', 'Người bị bệnh gan, nghiện rượu nặng.', '2025-05-28 07:02:02', '2025-07-04 20:23:51'),
(2, 'Amoxicillin', 'Viên nang', 'viên', 'Uống 1 viên mỗi 8 giờ, duy trì trong 5–7 ngày.', 'Kê đơn', 'Tiêu chảy, nổi mẩn da, dị ứng.', 'Người dị ứng với penicillin hoặc cephalosporin.', '2025-05-28 07:02:02', '2025-07-04 20:23:51'),
(3, 'Vitamin C', 'Viên nén sủi bọt', 'viên', 'Uống 1 viên mỗi ngày sau bữa ăn. Không dùng quá 2000mg/ngày.', 'Bổ sung', 'Buồn nôn, tiêu chảy nếu dùng liều cao.', 'Người bị sỏi thận, thiếu men G6PD.', '2025-07-04 13:16:10', '2025-07-04 20:23:51'),
(6, 'Ibuprofen', 'Viên nén bao phim', 'viên', 'Uống sau ăn. Người lớn uống 1 viên mỗi 6–8 giờ nếu cần. Không quá 6 viên/ngày.', 'OTC', 'Đau bụng, buồn nôn, chóng mặt, loét dạ dày nếu lạm dụng.', 'Người bị loét dạ dày tá tràng, suy gan/thận nặng.', '2025-07-04 13:05:58', '2025-07-04 20:23:51'),
(7, 'Zinc gluconate', 'Viên nén', 'viên', 'Uống 1 viên mỗi ngày sau bữa ăn. Không dùng quá 40mg kẽm/ngày.', 'Bổ sung', 'Buồn nôn, kích ứng tiêu hóa nhẹ.', 'Không dùng đồng thời với tetracycline (kháng sinh).', '2025-07-04 13:05:58', '2025-07-04 20:23:51'),
(8, 'Bacillus clausii', 'Gói bột', 'gói', 'Uống 1–2 gói/ngày, pha với nước nguội. Không uống chung với kháng sinh.', 'Bổ sung', 'Rất hiếm: đầy hơi, rối loạn nhẹ đường tiêu hóa.', 'Không dùng cho người bị suy giảm miễn dịch nghiêm trọng.', '2025-07-04 13:05:58', '2025-07-04 20:23:51');

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
  `total_amount` decimal(16,0) DEFAULT 0,
  `order_note` text DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `address_id`, `shipping_address`, `total`, `payment_method`, `payment_status`, `status`, `total_amount`, `order_note`, `order_date`, `updated_at`) VALUES
(1, 1, NULL, 'Quản trị viên\n09777313131\n123 Đường Trần Hưng Đạo\nPhường Nguyễn Cư Trinh, Quận 1, TP.HCM', 20000, 'cod', 'pending', 'pending', 645000, '', '2025-07-20 17:14:14', '2025-07-21 00:14:14'),
(2, 2, NULL, 'Hoàn Huy\n\n456 Đường Lê Lợi\nPhường Bến Nghé, Quận 1, TP.HCM', 20000, 'momo', 'pending', 'pending', 80000, '', '2025-08-05 06:09:59', '2025-08-05 13:09:59');

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

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`item_id`, `order_id`, `product_id`, `quantity`, `unit_price`) VALUES
(9, 1, 4, 1, 640000),
(10, 1, 1, 1, 5000),
(11, 2, 3, 2, 40000);

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
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`id`, `user_id`, `email`, `token`, `created_at`, `expires_at`, `used`, `ip_address`, `user_agent`) VALUES
(1, 2, 'hoanhuy12@gmail.com', 'b82813ba8f3e9a5afa46d2b35c5390a28cbbcc23473a5b25a47f3840d6c518ae', '2025-07-21 06:23:08', '2025-07-22 13:23:08', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(2, 1, 'dvtdang1101@gmail.com', '587d2d7370f6a2ca3d5eaf7c9e85ca63da1cc64f2d21a7d66882c48f3b925150', '2025-07-21 06:23:21', '2025-07-22 13:23:21', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36');

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
  `confidence` float DEFAULT NULL CHECK (`confidence` between 0 and 1),
  `disease_summary` text DEFAULT NULL,
  `disease_care` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prediction_diseases`
--

INSERT INTO `prediction_diseases` (`id`, `prediction_id`, `disease_id`, `disease_name_raw`, `confidence`, `disease_summary`, `disease_care`) VALUES
(107, 37, -1, 'Đột quỵ nhẹ', 0.9, NULL, NULL),
(108, 37, -1, 'Thiếu vitamin B12', 0.7, NULL, NULL),
(109, 37, -1, 'Rối loạn lo âu hoặc căng thẳng', 0.6, NULL, NULL),
(110, 38, -1, 'Thiếu vitamin B12', 0.85, 'Thiếu hụt vitamin B12 có thể gây ra cảm giác tê và mệt mỏi.', 'Bổ sung thực phẩm như thịt, cá, trứng và sữa.'),
(111, 38, -1, 'Rối loạn tuần hoàn', 0.75, 'Rối loạn tuần hoàn có thể dẫn đến cảm giác tê tay chân và chóng mặt.', 'Vận động nhẹ nhàng để cải thiện tuần hoàn máu.'),
(112, 38, -1, 'Căng thẳng hoặc lo âu', 0.6, 'Căng thẳng có thể gây ra các triệu chứng như tê và mệt mỏi.', 'Thư giãn và nghỉ ngơi có thể giúp bạn cảm thấy tốt hơn.'),
(113, 39, -1, 'Đột quỵ nhẹ', 0.9, 'Tình trạng khi máu không được cung cấp đủ cho một phần của não, có thể gây ra tê tay chân và khó nói.', 'Cần đến bác sĩ ngay nếu triệu chứng kéo dài hoặc nặng hơn.'),
(114, 39, -1, 'Thiếu vitamin B12', 0.6, 'Thiếu hụt vitamin B12 có thể dẫn đến tê bì tay chân và mệt mỏi.', 'Bổ sung thực phẩm giàu vitamin B12 như thịt, cá, trứng và sữa.'),
(115, 39, 22, NULL, 0.3, 'Căng thẳng và lo âu có thể gây ra cảm giác mệt mỏi và chóng mặt.', 'Thử các phương pháp thư giãn như yoga hoặc thiền.'),
(116, 40, -1, 'Thiếu nước', 0.85, 'Khi cơ thể không đủ nước, có thể gây ra cảm giác mệt mỏi, chóng mặt.', 'Uống đủ nước trong suốt cả ngày.'),
(117, 40, -1, 'Căng thẳng hoặc mệt mỏi', 0.85, 'Cảm giác mệt mỏi và chóng mặt có thể do căng thẳng hoặc thiếu ngủ.', 'Thư giãn và nghỉ ngơi có thể giúp bạn cảm thấy tốt hơn.'),
(118, 40, -1, 'Vấn đề về tuần hoàn', 0.85, 'Tê tay chân và chóng mặt có thể liên quan đến vấn đề tuần hoàn.', 'Nếu triệu chứng kéo dài, bạn nên đến gặp bác sĩ để kiểm tra kỹ hơn.'),
(119, 41, -1, 'Đột quỵ nhẹ', 0.9, 'Tình trạng khi máu không đến được một phần của não, có thể gây ra tê liệt hoặc yếu ở một bên cơ thể.', 'Theo dõi triệu chứng và đến gặp bác sĩ ngay nếu có dấu hiệu nghiêm trọng.'),
(120, 41, -1, 'Rối loạn thần kinh', 0.7, 'Các vấn đề liên quan đến hệ thần kinh có thể ảnh hưởng đến khả năng điều khiển cơ bắp và trí nhớ.', 'Thăm khám bác sĩ để được tư vấn và điều trị kịp thời.'),
(121, 42, -1, 'Đột quỵ nhẹ', 0.9, 'Tình trạng khi máu không đến được một phần của não, gây ra tê hoặc yếu ở một bên cơ thể và khó nói.', 'Theo dõi triệu chứng và đến gặp bác sĩ ngay nếu có dấu hiệu nghiêm trọng.'),
(122, 42, -1, 'Rối loạn thần kinh', 0.7, 'Một số rối loạn có thể gây ra triệu chứng như tê và khó nói.', 'Theo dõi thêm tình trạng của bạn và tìm sự hỗ trợ từ chuyên gia nếu cần.'),
(123, 43, -1, 'Đột quỵ nhẹ', 0.9, 'Đột quỵ nhẹ có thể gây ra triệu chứng tê hoặc yếu ở một bên cơ thể và khó khăn trong việc nói.', 'Nếu có triệu chứng này, bạn nên đến gặp bác sĩ ngay.'),
(124, 43, -1, 'Căng thẳng', 0.6, 'Căng thẳng có thể gây ra cảm giác tê và khàn giọng.', 'Thư giãn và nghỉ ngơi có thể giúp cải thiện tình trạng này.'),
(127, 45, -1, 'Chóng mặt', 0.85, 'Chóng mặt có thể gây ra cảm giác mất thăng bằng hoặc quay cuồng.', 'Uống đủ nước và nghỉ ngơi nhiều hơn.'),
(128, 45, -1, 'Căng thẳng', 0.75, 'Căng thẳng có thể dẫn đến các triệu chứng thể chất như chóng mặt và buồn nôn.', 'Thử các phương pháp thư giãn như yoga hoặc thiền.'),
(129, 45, -1, 'Thiếu ngủ', 0.8, 'Thiếu ngủ có thể làm giảm khả năng tập trung và gây mệt mỏi.', 'Cố gắng sắp xếp thời gian ngủ hợp lý.'),
(130, 46, -1, 'Migrain', 0.9, 'Migrain là một dạng đau đầu thường gây ra cảm giác buồn nôn và nhạy cảm với ánh sáng.', 'Nghỉ ngơi trong phòng tối và uống nước ấm có thể giúp giảm cơn đau.'),
(131, 46, -1, 'Thiếu nước', 0.6, 'Thiếu nước có thể dẫn đến cảm giác chóng mặt và mệt mỏi.', 'Đảm bảo uống đủ nước trong suốt cả ngày.'),
(132, 46, -1, 'Căng thẳng', 0.6, 'Căng thẳng có thể làm tăng cường độ đau đầu và cảm giác buồn nôn.', 'Thử các phương pháp thư giãn như yoga hoặc thiền.'),
(151, 53, -1, 'Đột quỵ nhẹ', 0.9, 'Triệu chứng này có thể là dấu hiệu của đột quỵ nhẹ, nhất là khi có tê một bên mặt và khó nói.', 'Nếu có triệu chứng này, bạn cần kiểm tra ngay với bác sĩ.'),
(152, 53, -1, 'Rối loạn thần kinh', 0.6, 'Các triệu chứng như khàn giọng và đau đầu có thể liên quan đến rối loạn thần kinh.', 'Nghỉ ngơi và theo dõi thêm các triệu chứng khác.'),
(153, 53, 18, NULL, 0.6, 'Mệt mỏi và yếu cơ có thể do thiếu máu hoặc vấn đề dinh dưỡng.', 'Đảm bảo bạn ăn uống đủ chất và nghỉ ngơi.'),
(172, 60, -1, 'Căng thẳng hoặc lo âu', 0.9, 'Áp lực công việc hoặc cuộc sống có thể gây ra cảm giác đau đầu và buồn nôn.', 'Thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.'),
(173, 60, -1, 'Mất nước hoặc thiếu dinh dưỡng', 0.85, 'Thiếu nước hoặc năng lượng có thể dẫn đến chóng mặt và mệt mỏi.', 'Uống đủ nước và ăn uống đầy đủ trong ngày.'),
(174, 60, -1, 'Huyết áp thấp', 0.8, 'Huyết áp thấp có thể gây chóng mặt khi thay đổi tư thế.', 'Ngồi nghỉ và uống nước từ từ.'),
(175, 60, -1, 'Thiếu năng lượng nhẹ', 0.65, 'Cơ thể bị hạ đường huyết tạm thời nếu nhịn ăn lâu.', 'Bạn có thể ăn nhẹ hoặc uống sữa để lấy lại sức.'),
(179, 62, -1, 'Căng thẳng hoặc lo âu', 0.9, 'Áp lực công việc hoặc cuộc sống có thể gây ra cảm giác đau đầu và buồn nôn.', 'Nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.'),
(180, 62, -1, 'Mất nước hoặc thiếu dinh dưỡng', 0.85, 'Thiếu nước hoặc năng lượng có thể dẫn đến chóng mặt và mệt mỏi.', 'Uống đủ nước và ăn uống đầy đủ.'),
(181, 62, -1, 'Huyết áp thấp', 0.75, 'Có thể gây chóng mặt khi thay đổi tư thế đột ngột.', 'Ngồi nghỉ và uống nước từ từ.'),
(197, 68, -1, 'Căng thẳng hoặc lo âu', 0.85, 'Đôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác đau đầu và buồn nôn.', 'Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.'),
(198, 68, -1, 'Mất nước hoặc thiếu dinh dưỡng', 0.9, 'Nếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy chóng mặt hoặc mệt mỏi.', 'Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.'),
(199, 68, -1, 'Huyết áp thấp', 0.85, 'Tình trạng này có thể gây cảm giác chóng mặt nhẹ khi bạn thay đổi tư thế đột ngột.', 'Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.'),
(200, 69, -1, 'Căng thẳng hoặc lo âu', 0.85, 'Đôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác không bình thường.', 'Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.'),
(201, 69, -1, 'Mất nước hoặc thiếu dinh dưỡng', 0.85, 'Nếu cơ thể không được cung cấp đủ nước hoặc năng lượng, có thể cảm thấy không ổn định.', 'Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.'),
(202, 69, -1, 'Huyết áp thấp', 0.85, 'Tình trạng này có thể gây cảm giác không ổn khi thay đổi tư thế đột ngột.', 'Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn.'),
(203, 70, -1, 'Huyết áp thấp', 0.85, 'Tình trạng này có thể gây ra cảm giác hồi hộp hoặc khó chịu trong ngực.', 'Bạn nên kiểm tra huyết áp thường xuyên và uống đủ nước.'),
(204, 70, -1, 'Rối loạn nhịp tim', 0.85, 'Nếu tim đập không đều, bạn có thể cảm thấy lo lắng hoặc mệt mỏi.', 'Hãy chú ý xem tình trạng này có xảy ra thường xuyên không và cần khám bác sĩ nếu có triệu chứng nghiêm trọng.'),
(205, 70, -1, 'Căng thẳng hoặc lo âu', 0.7, 'Áp lực cuộc sống có thể khiến bạn cảm thấy không thoải mái trong cơ thể.', 'Bạn có thể thử các kỹ thuật thư giãn như thiền hoặc yoga để giảm bớt căng thẳng.'),
(206, 71, -1, 'Căng thẳng hoặc lo âu', 0.9, 'Đôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác đau đầu và buồn nôn.', 'Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.'),
(207, 71, -1, 'Mất nước hoặc thiếu dinh dưỡng', 0.85, 'Nếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy chóng mặt hoặc mệt mỏi.', 'Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.'),
(208, 71, -1, 'Huyết áp thấp', 0.75, 'Tình trạng này có thể gây cảm giác chóng mặt nhẹ khi bạn thay đổi tư thế đột ngột.', 'Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.'),
(224, 77, -1, 'Căng thẳng hoặc lo âu', 0.9, 'Đôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác đau đầu và buồn nôn.', 'Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.'),
(225, 77, -1, 'Mất nước hoặc thiếu dinh dưỡng', 0.85, 'Nếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy chóng mặt hoặc mệt mỏi.', 'Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.'),
(226, 77, -1, 'Huyết áp thấp', 0.8, 'Tình trạng này có thể gây cảm giác chóng mặt nhẹ khi bạn thay đổi tư thế đột ngột.', 'Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.'),
(227, 78, -1, 'Mất nước hoặc thiếu dinh dưỡng', 0.85, 'Tình trạng cơ thể không được cung cấp đủ nước hoặc dinh dưỡng có thể gây ra cảm giác chóng mặt và mệt mỏi.', 'Uống đủ nước và ăn uống đầy đủ sẽ giúp cải thiện.'),
(228, 78, -1, 'Căng thẳng hoặc lo âu', 0.9, 'Áp lực từ công việc hoặc cuộc sống có thể gây ra cảm giác đau đầu và buồn nôn.', 'Nghỉ ngơi và thư giãn là cách tốt để giảm bớt triệu chứng.'),
(229, 78, -1, 'Huyết áp thấp', 0.6, 'Huyết áp thấp có thể dẫn đến cảm giác chóng mặt khi thay đổi tư thế.', 'Ngồi nghỉ và uống nước từ từ có thể giúp bạn cảm thấy khá hơn.');

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
  `is_medicine` tinyint(1) DEFAULT 0,
  `image_url` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Ẩn/hiện sản phẩm (TRUE = hiển thị, FALSE = ẩn)',
  `discount_amount` decimal(16,0) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `category_id`, `name`, `description`, `price`, `stock`, `is_medicine`, `image_url`, `created_at`, `updated_at`, `is_active`, `discount_amount`) VALUES
(1, 1, 'Paracetamol 500mg', 'Thuốc hạ sốt, giảm đau thường dùng.', 15000, 100, 1, 'https://i.pinimg.com/736x/63/72/7c/63727c4fe25f05abee1f32debe73e5ca.jpg', '2025-05-28 07:02:02', '2025-07-20 13:19:15', 1, 10000),
(2, 1, 'Amoxicillin 500mg', 'Kháng sinh phổ rộng nhóm penicillin.', 28000, 60, 1, 'https://example.com/images/amoxicillin.jpg', '2025-05-28 07:02:02', '2025-07-20 13:19:21', 1, 10000),
(3, 2, 'Vitamin C 1000mg', 'Hỗ trợ tăng cường đề kháng.', 50000, 200, 1, 'https://example.com/images/vitaminC.jpg', '2025-05-28 07:02:02', '2025-07-20 13:19:23', 1, 10000),
(4, 3, 'Máy đo huyết áp điện tử', 'Thiết bị đo huyết áp tại nhà.', 650000, 15, 0, 'https://example.com/images/blood_pressure_monitor.jpg', '2025-05-28 07:02:02', '2025-07-20 13:19:25', 1, 10000),
(5, 4, 'Khẩu trang y tế 4 lớp', 'Hộp 50 cái, đạt chuẩn kháng khuẩn.', 40000, 500, 0, 'https://example.com/images/face_mask.jpg', '2025-05-28 07:02:02', '2025-07-20 13:19:27', 1, 10000),
(6, 1, 'Ibuprofen 200mg', 'Thuốc giảm đau, kháng viêm, hạ sốt.', 20000, 80, 1, 'assets/images/products/product_1752991945_687c88c9aba5a.png', '2025-07-04 12:55:40', '2025-07-20 13:19:30', 1, 10000),
(7, 2, 'Kẽm Gluconat 50mg', 'Hỗ trợ miễn dịch, chống viêm nhiễm.', 45000, 150, 1, 'https://example.com/images/zinc.jpg', '2025-07-04 12:55:40', '2025-07-20 13:19:31', 1, 10000),
(8, 2, 'Men tiêu hóa Biolactyl', 'Giúp cân bằng hệ vi sinh đường ruột.', 70000, 90, 1, 'https://example.com/images/probiotic.jpg', '2025-07-04 12:55:40', '2025-07-20 13:19:33', 1, 10000),
(9, 3, 'Máy xông mũi họng mini', 'Hỗ trợ điều trị viêm mũi, cảm cúm tại nhà.', 350000, 25, 0, 'https://example.com/images/nebulizer.jpg', '2025-07-04 12:55:40', '2025-07-20 13:19:35', 1, 10000),
(10, 5, 'Kem dưỡng ẩm da nhạy cảm', 'Phục hồi và giữ ẩm cho da khô, kích ứng.', 120000, 50, 0, 'https://example.com/images/moisturizer.jpg', '2025-07-04 12:55:40', '2025-07-20 13:19:36', 1, 10000),
(11, 6, 'Trà ngủ ngon Hoa Cúc', 'Giúp thư giãn, cải thiện giấc ngủ tự nhiên.', 65000, 70, 0, 'https://i.pinimg.com/736x/d7/22/35/d72235c87cfe02dfcdc92c89d46096c0.jpg', '2025-07-04 12:55:40', '2025-07-21 13:37:48', 1, 10000);

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
(4, 'Vật tư tiêu hao', 'Găng tay, khẩu trang, bông băng,... sử dụng một lần.', '2025-05-28 07:02:01', '2025-05-28 14:02:01'),
(5, 'Chăm sóc da', 'Sản phẩm hỗ trợ điều trị và chăm sóc da.', '2025-07-04 12:55:32', '2025-07-20 20:59:29'),
(6, 'Tiêu hóa', 'Sản phẩm hỗ trợ hệ tiêu hóa.', '2025-07-04 12:55:32', '2025-07-04 19:55:32'),
(7, 'Miễn dịch', 'Sản phẩm tăng cường sức đề kháng.', '2025-07-04 12:55:32', '2025-07-04 19:55:32'),
(8, 'Giấc ngủ & thư giãn', 'Giúp cải thiện giấc ngủ và thư giãn.', '2025-07-04 12:55:32', '2025-07-04 19:55:32');

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
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(19, 'Chóng mặt', 'chóng mặt,quay cuồng,mất thăng bằng, đầu quay, choáng, choáng váng, quay vòng vòng', 'Cảm giác quay cuồng, mất thăng bằng hoặc như đang bị xoay vòng, thường kèm cảm giác muốn ngã.', 'Bạn cảm thấy chóng mặt vào thời điểm nào? Có xuất hiện khi thay đổi tư thế, đứng lâu, hoặc sau khi ngủ dậy không?', '2025-06-10 07:34:51', '2025-06-25 22:27:39'),
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
(1, 'admin', 'dvtdang1101@gmail.com', '123', 1, '2025-05-22 06:49:02', '2025-07-21 06:09:54', 'active'),
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
(1, 1, 'Quản trị viên', 'Nam', '1990-01-01', NULL, '2025-05-22 06:49:55', '2025-07-21 06:09:57', '09777313131'),
(2, 2, 'Hoàn Huy', 'Nam', '1999-09-09', NULL, '2025-05-22 06:49:55', '2025-05-24 07:07:40', NULL),
(3, 3, 'John Doe', 'Nam', '2000-12-01', NULL, '2025-05-22 06:49:55', '2025-05-22 06:49:55', NULL),
(4, 4, 'Nguyễn Văn A', 'Nam', '1995-08-15', NULL, '2025-05-22 08:39:27', '2025-05-22 08:39:27', NULL),
(5, 6, 'Dr.Linh', 'Nữ', '1995-08-15', NULL, '2025-05-24 06:17:47', '2025-07-20 13:22:15', '');

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
(101, 4, 28, '2025-06-25', 'Người dùng bị tê tay chân bên trái, nói chuyện ngọng, hay quên, và gần đây cảm thấy mệt mỏi cùng với mất thăng bằng khi đi lại. Thời gian bắt đầu và nguyên nhân không rõ ràng.'),
(102, 4, 6, '2025-06-25', 'Người dùng bị tê tay chân bên trái, nói chuyện ngọng, hay quên, và gần đây cảm thấy mệt mỏi cùng với mất thăng bằng khi đi lại. Thời gian bắt đầu và nguyên nhân không rõ ràng.'),
(103, 4, 19, '2025-06-25', 'Người dùng bị tê tay chân bên trái, nói chuyện ngọng, hay quên, và gần đây cảm thấy mệt mỏi cùng với mất thăng bằng khi đi lại. Thời gian bắt đầu và nguyên nhân không rõ ràng.'),
(104, 4, 25, '2025-06-25', 'Người dùng cảm thấy tê tay chân nhưng không rõ vùng nào và không biết nguyên nhân. Ngoài ra, người dùng cũng bị khàn giọng nhưng không rõ tình trạng này đã kéo dài bao lâu và có ảnh hưởng đến việc nói chuyện hàng ngày hay không.'),
(105, 4, 28, '2025-06-25', 'Người dùng cảm thấy tê tay chân nhưng không rõ vùng nào và không biết nguyên nhân. Ngoài ra, người dùng cũng bị khàn giọng nhưng không rõ tình trạng này đã kéo dài bao lâu và có ảnh hưởng đến việc nói chuyện hàng ngày hay không.'),
(106, 4, 6, '2025-06-25', 'Người dùng cảm thấy tê tay chân nhưng không rõ vùng nào và không biết nguyên nhân. Ngoài ra, người dùng cũng bị khàn giọng nhưng không rõ tình trạng này đã kéo dài bao lâu và có ảnh hưởng đến việc nói chuyện hàng ngày hay không.'),
(107, 4, 19, '2025-06-25', 'Người dùng cảm thấy tê tay chân nhưng không rõ vùng nào và không biết nguyên nhân. Ngoài ra, người dùng cũng bị khàn giọng nhưng không rõ tình trạng này đã kéo dài bao lâu và có ảnh hưởng đến việc nói chuyện hàng ngày hay không.'),
(108, 4, 25, '2025-06-25', 'Người dùng bị tê tay chân bên trái kéo dài liên tục từ sáng đến giờ, kèm theo tình trạng nói chuyện bị ngọng và khàn giọng cũng kéo dài liên tục. Người dùng không rõ nguyên nhân gây ra các triệu chứng này.'),
(109, 4, 28, '2025-06-25', 'Người dùng bị tê tay chân bên trái kéo dài liên tục từ sáng đến giờ, kèm theo tình trạng nói chuyện bị ngọng và khàn giọng cũng kéo dài liên tục. Người dùng không rõ nguyên nhân gây ra các triệu chứng này.'),
(110, 4, 25, '2025-06-25', 'Người dùng bị tê tay chân bên trái kéo dài liên tục đến tận giờ, kèm theo tình trạng nói chuyện bị ngọng và đôi khi quên. Họ cảm thấy tình trạng khàn giọng có vẻ nặng hơn khi nói chuyện.'),
(111, 4, 28, '2025-06-25', 'Người dùng bị tê tay chân bên trái kéo dài liên tục đến tận giờ, kèm theo tình trạng nói chuyện bị ngọng và đôi khi quên. Họ cảm thấy tình trạng khàn giọng có vẻ nặng hơn khi nói chuyện.'),
(133, 4, 25, '2025-07-01', 'Người dùng đã mô tả một số triệu chứng trong cuộc trò chuyện.'),
(134, 4, 28, '2025-07-01', 'Người dùng đã mô tả một số triệu chứng trong cuộc trò chuyện.'),
(135, 4, 29, '2025-07-01', 'Người dùng đã mô tả một số triệu chứng trong cuộc trò chuyện.'),
(136, 4, 1, '2025-07-01', 'Người dùng đã mô tả một số triệu chứng trong cuộc trò chuyện.'),
(156, 4, 1, '2025-07-03', 'Người dùng bị đau đầu khi vừa nằm xuống, cơn đau kéo dài khoảng 5-10 phút.'),
(157, 4, 19, '2025-07-03', 'Người dùng cảm thấy chóng mặt từ sáng tới giờ, triệu chứng không rõ nguyên nhân và xảy ra một cách tự nhiên. Hôm nay, người dùng chưa ăn gì từ sáng, có thể là nguyên nhân gây ra triệu chứng. Cảm giác chóng mặt lần này quay nhiều hơn bình thường, và dù đã ngồi xuống một lúc, người dùng vẫn cảm thấy lâng lâng. Triệu chứng vẫn chưa hết. Ngày 7/3.'),
(158, 4, 3, '2025-07-03', 'Người dùng có cảm giác buồn nôn nhẹ khi ngửi thấy một mùi gì đó.'),
(163, 4, 1, '2025-07-09', 'Người dùng cảm giác đau đầu kéo dài khoảng 5-10 phút khi vừa nằm xuống.'),
(164, 4, 19, '2025-07-09', 'Người dùng có cảm giác chóng mặt khi vừa đứng dậy.'),
(165, 4, 1, '2025-07-11', 'Người dùng báo cáo có vấn đề liên quan đến tim mạch.'),
(166, 4, 1, '2025-07-12', 'Người dùng muốn khám về vấn đề tim mạch.'),
(167, 4, 1, '2025-07-15', 'Bệnh nhân báo cáo bị đau đầu kéo dài từ 5-10 phút khi đứng lên hoặc ngồi xuống. Từ sáng đến giờ, bệnh nhân cảm thấy đau đầu kiểu nhói ở thái dương, kèm theo triệu chứng chóng mặt và buồn nôn nhẹ.'),
(168, 4, 19, '2025-07-15', 'Người dùng cảm thấy chóng mặt tự nhiên, đặc biệt khi chưa ăn gì và có thể kéo dài không xác định.'),
(169, 4, 3, '2025-07-15', 'Người dùng có cảm giác buồn nôn nhẹ khi ngửi thấy một mùi gì đó.'),
(186, 4, 1, '2025-07-23', 'Người dùng bị đau đầu kiểu nhói ở thái dương từ sáng đến giờ và cơn đau kéo dài khoảng 5-10 phút.'),
(187, 4, 19, '2025-07-23', 'Người dùng cảm thấy chóng mặt tự nhiên và chưa ăn gì từ sáng.'),
(188, 4, 3, '2025-07-23', 'Người dùng có cảm giác buồn nôn nhẹ.'),
(189, 2, 1, '2025-08-05', 'Người dùng bị đau đầu kéo dài khoảng 5-10 phút sau khi ngồi dậy.'),
(190, 2, 19, '2025-08-05', 'Người dùng cảm thấy chóng mặt tự nhiên, xảy ra khi chưa ăn gì vào buổi sáng.'),
(191, 2, 3, '2025-08-05', 'Người dùng có cảm giác buồn nôn nhẹ khi ngửi thấy một mùi gì đó.');

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
-- Indexes for table `blog_tags`
--
ALTER TABLE `blog_tags`
  ADD PRIMARY KEY (`tag_id`),
  ADD UNIQUE KEY `slug` (`slug`);

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
-- Indexes for table `clinic_specialties`
--
ALTER TABLE `clinic_specialties`
  ADD PRIMARY KEY (`clinic_id`,`specialty_id`),
  ADD KEY `specialty_id` (`specialty_id`);

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
-- Indexes for table `doctor_off_days`
--
ALTER TABLE `doctor_off_days`
  ADD PRIMARY KEY (`off_day_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`);

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
  ADD PRIMARY KEY (`product_id`);

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
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `email` (`email`),
  ADD KEY `token` (`token`),
  ADD KEY `expires_at` (`expires_at`);

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
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

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
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `blog_authors`
--
ALTER TABLE `blog_authors`
  MODIFY `author_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `blog_categories`
--
ALTER TABLE `blog_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `blog_posts`
--
ALTER TABLE `blog_posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `blog_tags`
--
ALTER TABLE `blog_tags`
  MODIFY `tag_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chatbot_knowledge_base`
--
ALTER TABLE `chatbot_knowledge_base`
  MODIFY `kb_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `chat_logs`
--
ALTER TABLE `chat_logs`
  MODIFY `chat_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1946;

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
-- AUTO_INCREMENT for table `doctor_off_days`
--
ALTER TABLE `doctor_off_days`
  MODIFY `off_day_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `guest_users`
--
ALTER TABLE `guest_users`
  MODIFY `guest_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `health_predictions`
--
ALTER TABLE `health_predictions`
  MODIFY `prediction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `health_records`
--
ALTER TABLE `health_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

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
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `package_features`
--
ALTER TABLE `package_features`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prediction_diseases`
--
ALTER TABLE `prediction_diseases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=230;

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
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=192;

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
-- Constraints for table `clinic_specialties`
--
ALTER TABLE `clinic_specialties`
  ADD CONSTRAINT `clinic_specialties_ibfk_1` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `clinic_specialties_ibfk_2` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`specialty_id`) ON DELETE CASCADE;

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
-- Constraints for table `doctor_off_days`
--
ALTER TABLE `doctor_off_days`
  ADD CONSTRAINT `fk_offday_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD CONSTRAINT `doctor_schedules_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `fk_medicines_products` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medicines_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

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
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

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
