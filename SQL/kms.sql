-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 09, 2025 at 04:08 PM
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

--
-- Dumping data for table `chat_logs`
--

INSERT INTO `chat_logs` (`chat_id`, `user_id`, `guest_id`, `intent`, `message`, `sender`, `sent_at`) VALUES
(76, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **chóng mặt** nè. Bạn có thấy tình trạng đó xuất hiện khi nào, lúc vừa đứng lên hay lúc đang ngồi vậy?', 'bot', '2025-07-02 11:40:32'),
(77, 4, NULL, 'symptom_query', 'à hình như mình hiểu tại sao mình cảm thấy chống mặt rồi', 'user', '2025-07-02 13:00:38'),
(78, 4, NULL, 'symptom_query', 'Cảm giác chóng mặt của bạn thường xuất hiện lúc nào, lúc ngồi hay lúc vừa đứng lên vậy?', 'bot', '2025-07-02 13:00:38'),
(79, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-02 13:30:49'),
(80, 4, NULL, 'symptom_query', 'Dựa trên những gì bạn chia sẻ, có thể triệu chứng của bạn liên quan đến một số tình trạng như sau: \n\n�� **Migraine**: Đây là cơn đau đầu dữ dội, thường kèm theo triệu chứng như chóng mặt và buồn nôn. Bạn có thể thử nghỉ ngơi trong một phòng tối hoặc yên tĩnh để giảm cơn đau. Nếu tình trạng này tái diễn, bạn nên đến bác sĩ để kiểm tra thêm nhé.\n\n📌 **Căng thẳng**: Đôi khi, stress hay lo âu cũng có thể gây ra những cơn đau đầu kèm theo chóng mặt và buồn nôn. Hãy thử thư giãn và uống nước nhiều hơn để cơ thể được phục hồi.\n\n📌 **Hạ huyết áp**: Nếu bạn cảm thấy chóng mặt khi đứng lên, có thể là do huyết áp của bạn giảm đột ngột. Đứng dậy từ từ và giữ cho cơ thể đủ nước có thể giúp bạn cảm thấy tốt hơn.\n\nNếu triệu chứng vẫn kéo dài, bạn nên đến gặp bác sĩ để kiểm tra kỹ hơn nhé.', 'bot', '2025-07-02 13:30:50'),
(81, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-02 13:32:58'),
(82, 4, NULL, 'symptom_query', 'Dựa trên những gì bạn chia sẻ, có thể triệu chứng của bạn liên quan đến một số vấn đề sức khỏe như sau:\n\n📌 **Migrain (Đau nửa đầu)**: Đây là loại đau đầu thường có cảm giác nhói và có thể đi kèm với chóng mặt, buồn nôn. Để giảm triệu chứng, bạn có thể thử nghỉ ngơi trong một không gian tối và yên tĩnh.\n\n📌 **Hạ huyết áp**: Khi huyết áp quá thấp, bạn có thể cảm thấy chóng mặt, đau đầu và buồn nôn. Hãy đảm bảo rằng bạn uống đủ nước và có thể thử ăn nhẹ nếu chưa ăn gì.\n\n�� **Căng thẳng hoặc thiếu ngủ**: Cũng có thể chỉ là do bạn đang mệt hoặc thiếu ngủ gần đây 🌿. Hãy thử thư giãn và cân nhắc việc nghỉ ngơi nhiều hơn.\n\nNếu triệu chứng vẫn kéo dài, bạn nên đến gặp bác sĩ để kiểm tra kỹ hơn nhé.', 'bot', '2025-07-02 13:32:58'),
(83, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-02 13:37:43'),
(84, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè, cảm giác đó thường kéo dài bao lâu mỗi lần bạn gặp vậy?', 'bot', '2025-07-02 13:37:43'),
(85, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac nhoi va no keo dai tam 5-10p gi do', 'user', '2025-07-02 13:38:20'),
(86, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè. Bạn thường thấy đau đầu khi nào, lúc đang ngồi hay khi vừa đứng lên vậy?', 'bot', '2025-07-02 13:38:21'),
(87, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-02 13:51:25'),
(88, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè, không biết cảm giác đó thường kéo dài bao lâu mỗi lần bạn gặp vậy?', 'bot', '2025-07-02 13:51:25'),
(89, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac nhoi va no keo dai tam 5-10p gi do', 'user', '2025-07-02 13:51:51'),
(90, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về **đau đầu** của bạn nè, cảm giác đó có thường kéo dài bao lâu mỗi lần bạn gặp phải không?', 'bot', '2025-07-02 13:51:51'),
(91, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-02 13:55:56'),
(92, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè, cảm giác đó thường kéo dài bao lâu mỗi lần bạn gặp vậy?', 'bot', '2025-07-02 13:55:56'),
(93, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac nhoi va no keo dai tam 5-10p gi do', 'user', '2025-07-02 13:56:06'),
(94, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **đau đầu** nè, bạn thường gặp tình trạng đó khi nào ha? Lúc đang ngồi hay lúc vừa đứng lên?', 'bot', '2025-07-02 13:56:06'),
(95, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-02 14:03:37'),
(96, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè, cảm giác đó thường xảy ra lúc nào, có khi nào bạn thấy đỡ hơn sau khi nghỉ ngơi không ha?', 'bot', '2025-07-02 14:03:37'),
(97, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac nhoi va no keo dai tam 5-10p gi do', 'user', '2025-07-02 14:06:04'),
(98, 4, NULL, 'symptom_query', 'Cảm giác **đau đầu** của bạn thường xảy ra khi nào, lúc bạn ngồi hay khi bạn đứng lên? 😊', 'bot', '2025-07-02 14:06:05'),
(99, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-02 14:36:54'),
(100, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè. Cảm giác đó thường kéo dài bao lâu mỗi lần bạn gặp vậy?', 'bot', '2025-07-02 14:36:54'),
(101, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac nhoi va no keo dai tam 5-10p gi do', 'user', '2025-07-02 14:37:18'),
(102, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **đau đầu** của bạn nè, có khi nào bạn cảm thấy nó thường xuất hiện lúc nào, như khi đang ngồi hay lúc vừa đứng lên không?', 'bot', '2025-07-02 14:37:18'),
(103, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-02 14:42:49'),
(104, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè, cảm giác đó thường kéo dài bao lâu mỗi lần bạn gặp vậy?', 'bot', '2025-07-02 14:42:49'),
(105, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac nhoi va no keo dai tam 5-10p gi do', 'user', '2025-07-02 14:43:14'),
(106, 4, NULL, 'symptom_query', 'Mình đang muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè, cảm giác đó thường kéo dài bao lâu mỗi lần bạn gặp vậy?', 'bot', '2025-07-02 14:43:14'),
(107, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-02 14:48:48'),
(108, 4, NULL, 'symptom_query', 'Mình đang muốn hỏi thêm một chút về cảm giác **đau đầu** của bạn nè. Bạn có cảm thấy cơn đau thường kéo dài bao lâu mỗi lần gặp không, hay chỉ thoáng qua thôi?', 'bot', '2025-07-02 14:48:48'),
(109, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac nhoi va no keo dai tam 5-10p gi do', 'user', '2025-07-02 14:48:59'),
(110, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **đau đầu** của bạn nè, có khi nào bạn cảm thấy đau hơn khi ngồi dậy hay đứng lên không ha?', 'bot', '2025-07-02 14:49:00'),
(111, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-02 14:51:57'),
(112, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè, cảm giác đó thường kéo dài bao lâu mỗi lần bạn gặp vậy?', 'bot', '2025-07-02 14:51:57'),
(113, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac nhoi va no keo dai tam 5-10p gi do', 'user', '2025-07-02 14:52:10'),
(114, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè, cảm giác đó thường kéo dài bao lâu mỗi lần bạn gặp vậy?', 'bot', '2025-07-02 14:52:10'),
(115, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-02 14:58:55'),
(116, 4, NULL, 'symptom_query', 'Cảm giác đau đầu của bạn thường xảy ra khi nào? Mình đang nghĩ không biết bạn có thấy đỡ hơn sau khi nghỉ ngơi không ha?', 'bot', '2025-07-02 14:58:55'),
(117, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-02 14:59:54'),
(118, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè. Cảm giác đó thường kéo dài bao lâu mỗi lần bạn gặp vậy?', 'bot', '2025-07-02 14:59:55'),
(119, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-02 15:01:03'),
(120, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** này nè, bạn cảm thấy đau nhói thường xảy ra khi nào, lúc đang ngồi hay lúc vừa đứng lên?', 'bot', '2025-07-02 15:01:03'),
(121, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-02 15:01:21'),
(122, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **đau đầu** của bạn nè, có khi nào bạn thấy tình trạng này xuất hiện khi bạn vừa đứng lên hay ngồi xuống không ha?', 'bot', '2025-07-02 15:01:21'),
(123, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-02 15:08:31'),
(124, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè. Bạn có thấy cơn đau này thường kéo dài bao lâu mỗi lần không?', 'bot', '2025-07-02 15:08:31'),
(125, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-02 15:08:57'),
(126, 4, NULL, 'symptom_query', 'Khi bạn cảm thấy **đau đầu** như vậy, có khi nào bạn thấy cơn đau này kéo dài lâu hơn khi bạn đang nằm hay ngồi không ha?', 'bot', '2025-07-02 15:08:57'),
(127, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-02 15:10:04'),
(128, 4, NULL, 'symptom_query', 'Cảm giác **đau đầu** của bạn thường kéo dài bao lâu mỗi lần bạn gặp phải vậy? Có khi nào bạn thấy đỡ hơn sau khi nghỉ ngơi không ha?', 'bot', '2025-07-02 15:10:04'),
(129, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-02 15:18:44'),
(130, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè, cảm giác đó thường kéo dài bao lâu mỗi lần bạn gặp vậy?', 'bot', '2025-07-02 15:18:45'),
(131, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-02 15:22:19'),
(132, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè. Cảm giác đó thường kéo dài bao lâu mỗi lần bạn gặp vậy?', 'bot', '2025-07-02 15:22:19'),
(133, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-02 15:22:52'),
(134, 4, NULL, 'symptom_query', 'Mình đang thắc mắc không biết cơn **chóng mặt** của bạn có xảy ra khi bạn đang ngồi hay khi vừa đứng lên không nhỉ?', 'bot', '2025-07-02 15:22:53'),
(135, 4, NULL, 'symptom_query', 'minh cung khong ro no tu nhien chong mat thoi', 'user', '2025-07-02 15:23:30'),
(136, 4, NULL, 'symptom_query', 'Bạn có thấy cơn **chóng mặt** này xuất hiện khi bạn đang ngồi hay lúc vừa đứng lên không nhỉ?', 'bot', '2025-07-02 15:23:31'),
(137, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-02 15:27:49'),
(138, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè, cảm giác đó thường kéo dài bao lâu mỗi lần bạn gặp vậy?', 'bot', '2025-07-02 15:27:50'),
(139, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-02 15:28:06'),
(140, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **chóng mặt** của bạn nè, bạn có thấy chóng mặt nhiều hơn khi đang đứng lên hay ngồi xuống không? 🌿', 'bot', '2025-07-02 15:28:06'),
(141, 4, NULL, 'symptom_query', 'minh cung khong ro no tu nhien chong mat thoi', 'user', '2025-07-02 15:28:28'),
(142, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **chóng mặt** của bạn nè, bạn có thấy chóng mặt nhiều hơn khi đang ngồi hay khi vừa đứng lên không? ��', 'bot', '2025-07-02 15:28:28'),
(143, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-02 15:31:54'),
(144, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** này nè. Bạn cảm thấy đau nhói kéo dài bao lâu mỗi lần vậy?', 'bot', '2025-07-02 15:31:54'),
(145, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-02 15:32:07'),
(146, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **chóng mặt** của bạn nè. Cảm giác đó có xuất hiện nhiều khi bạn vừa đứng dậy hay lúc đang ngồi không nhỉ?', 'bot', '2025-07-02 15:32:07'),
(147, 4, NULL, 'symptom_query', 'minh cung khong ro no tu nhien chong mat thoi', 'user', '2025-07-02 15:32:27'),
(148, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **chóng mặt** của bạn nè. Bạn có thấy chóng mặt nhiều hơn khi vừa đứng dậy hay khi đang ngồi không nhỉ?', 'bot', '2025-07-02 15:32:27'),
(149, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-02 15:41:01'),
(150, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **đau đầu** của bạn nè, bạn thấy cảm giác đó thường xảy ra lúc nào, lúc đang ngồi hay lúc vừa đứng lên?', 'bot', '2025-07-02 15:41:02'),
(151, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-02 15:41:21'),
(152, 4, NULL, 'symptom_query', 'Khi bạn cảm thấy **chóng mặt**, bạn có thấy điều đó xảy ra lúc đang đứng lên hay khi đang ngồi không nhỉ?', 'bot', '2025-07-02 15:41:21'),
(153, 4, NULL, 'symptom_query', 'minh cung khong ro no tu nhien chong mat thoi', 'user', '2025-07-02 15:41:49'),
(154, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **chóng mặt** của bạn nè, bạn có thấy điều đó xảy ra khi đang ngồi hay khi vừa đứng lên không ha?', 'bot', '2025-07-02 15:41:49'),
(155, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-02 15:49:26'),
(156, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè, bạn cảm thấy cơn đau này thường kéo dài bao lâu mỗi lần gặp phải vậy?', 'bot', '2025-07-02 15:49:26'),
(157, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-02 15:50:13'),
(158, 4, NULL, 'symptom_query', 'Khi bạn nằm xuống, cơn đau đầu của bạn có cảm giác như thế nào? Có khi nào bạn cảm thấy nặng hơn hay dễ chịu hơn không?', 'bot', '2025-07-02 15:50:13'),
(159, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-02 15:59:14'),
(160, 4, NULL, 'symptom_query', 'Cảm giác đau nhói ở thái dương của bạn thường kéo dài bao lâu mỗi lần vậy? Có khi nào bạn thấy đỡ hơn sau khi nghỉ ngơi không ha?', 'bot', '2025-07-02 15:59:14'),
(161, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-02 16:00:06'),
(162, 4, NULL, 'symptom_query', 'Cảm giác chóng mặt của bạn thường xảy ra khi nào, lúc đang ngồi hay khi vừa đứng lên vậy?', 'bot', '2025-07-02 16:00:06'),
(163, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-02 16:05:46'),
(164, 4, NULL, 'symptom_query', 'Bạn có thấy cơn **đau đầu** này thường kéo dài bao lâu mỗi lần không? Có khi nào bạn cảm thấy đỡ hơn sau khi nghỉ ngơi không ha?', 'bot', '2025-07-02 16:05:46'),
(165, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-02 16:05:57'),
(166, 4, NULL, 'symptom_query', 'Cảm giác chóng mặt của bạn có thường xảy ra khi bạn vừa đứng lên hay là khi đang ngồi không? 🤔', 'bot', '2025-07-02 16:05:58'),
(167, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-02 16:15:35'),
(168, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè, cảm giác đó thường kéo dài bao lâu mỗi lần bạn gặp vậy?', 'bot', '2025-07-02 16:15:35'),
(169, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-02 16:15:49'),
(170, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **chóng mặt** của bạn nè, bạn thường thấy chóng mặt khi nào, lúc đang ngồi hay lúc vừa đứng lên?', 'bot', '2025-07-02 16:15:49'),
(171, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-02 16:27:35'),
(172, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè. Cảm giác đó thường kéo dài bao lâu mỗi lần bạn gặp vậy?', 'bot', '2025-07-02 16:27:35'),
(173, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-02 16:28:04'),
(174, 4, NULL, 'symptom_query', 'Khi bạn bị **chóng mặt**, có khi nào cảm giác đó xảy ra khi bạn vừa đứng dậy hoặc lúc đang ngồi không nhỉ?', 'bot', '2025-07-02 16:28:04'),
(175, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-02 16:32:37'),
(176, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè, bạn có thấy cơn đau này thường kéo dài bao lâu mỗi lần không?', 'bot', '2025-07-02 16:32:37'),
(177, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-02 16:32:58'),
(178, 4, NULL, 'symptom_query', 'Cảm giác chóng mặt của bạn thường xuất hiện khi nào, lúc đang ngồi hay lúc vừa đứng lên vậy?', 'bot', '2025-07-02 16:32:58'),
(179, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-02 16:52:22'),
(180, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** này nè, bạn cảm thấy đau nhiều hơn khi nào, lúc ngồi hay khi vừa đứng lên?', 'bot', '2025-07-02 16:52:22'),
(181, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-02 16:52:54'),
(182, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **chóng mặt** của bạn, không biết lúc nào bạn thấy chóng mặt nhiều hơn, lúc đang ngồi hay khi vừa đứng lên?', 'bot', '2025-07-02 16:52:55'),
(183, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-02 16:58:19'),
(184, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè. Cảm giác đó thường kéo dài bao lâu mỗi lần bạn gặp vậy?', 'bot', '2025-07-02 16:58:19'),
(185, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-02 16:59:00'),
(186, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có cảm giác **chóng mặt** nhiều hơn khi đang nằm hay khi vừa đứng lên không ha?', 'bot', '2025-07-02 16:59:00'),
(187, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-02 17:04:21'),
(188, 4, NULL, 'symptom_query', 'Cảm giác **đau đầu** của bạn thường kéo dài bao lâu mỗi lần gặp vậy? Mình đang nghĩ không biết nó có xuất hiện khi bạn đang ngồi hay vừa đứng lên không ha?', 'bot', '2025-07-02 17:04:21'),
(189, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-02 17:04:39'),
(190, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết cảm giác **chóng mặt** của bạn có xảy ra khi bạn đang đứng lên từ vị trí nằm hay không nha?', 'bot', '2025-07-02 17:04:39'),
(191, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-02 17:10:34'),
(192, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè, cảm giác đó thường kéo dài bao lâu mỗi lần bạn gặp vậy?', 'bot', '2025-07-02 17:10:34'),
(193, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-02 17:10:55'),
(194, 4, NULL, 'symptom_query', 'Cảm giác đau đầu kiểu nhói ở thái dương của bạn có khi nào kéo dài lâu không? Mình đang nghĩ không biết nó thường kéo dài vài phút hay nhiều giờ nha.', 'bot', '2025-07-02 17:10:55'),
(195, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-02 17:11:08'),
(196, 4, NULL, 'symptom_query', 'Mình đang muốn hỏi thêm một chút về cảm giác **chóng mặt** của bạn nè, có khi nào bạn thấy chóng mặt nhiều hơn khi vừa đứng dậy không ha?', 'bot', '2025-07-02 17:11:08'),
(197, 4, NULL, 'symptom_query', 'minh cung khong ro no tu nhien chong mat thoi', 'user', '2025-07-02 17:11:31'),
(198, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **buồn nôn** của bạn nè, có khi nào nó đi kèm với cảm giác mệt mỏi hoặc là bạn thấy đỡ hơn sau khi nghỉ ngơi không ha?', 'bot', '2025-07-02 17:11:31'),
(199, 4, NULL, 'symptom_query', 'khi minh ngui thay 1 mui gi do kha nang thi co cam giac buon non', 'user', '2025-07-02 17:11:52'),
(200, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có cảm giác nào khác kèm theo không, như là **mệt mỏi**, **tim đập nhanh**, hay **đau bụng** gì không nha? 🌿', 'bot', '2025-07-02 17:11:52'),
(201, 4, NULL, 'general_chat', 'khong co', 'user', '2025-07-02 17:12:06'),
(202, 4, NULL, 'general_chat', 'Um, mình hiểu rồi nè. Nếu bạn có câu hỏi hay điều gì khác muốn chia sẻ, cứ cho mình biết nhé!', 'bot', '2025-07-02 17:12:06'),
(203, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-02 17:15:40'),
(204, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè, cảm giác đó thường kéo dài bao lâu mỗi lần bạn gặp vậy?', 'bot', '2025-07-02 17:15:40'),
(205, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-02 17:17:17'),
(206, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn, không biết nó thường kéo dài bao lâu mỗi lần bạn gặp phải vậy?', 'bot', '2025-07-02 17:17:17'),
(207, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-02 17:17:39'),
(208, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết cảm giác chóng mặt của bạn có xảy ra khi bạn vừa đứng lên hay ngồi xuống không ha?', 'bot', '2025-07-02 17:17:39'),
(209, 4, NULL, 'symptom_query', 'minh cung khong ro no tu nhien chong mat thoi', 'user', '2025-07-02 17:17:54'),
(210, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **buồn nôn** của bạn nè, không biết nó có thường đi kèm với cảm giác mệt mỏi hay không ha?', 'bot', '2025-07-02 17:17:54'),
(211, 4, NULL, 'symptom_query', 'khi minh ngui thay 1 mui gi do kha nang thi co cam giac buon non', 'user', '2025-07-02 17:18:06'),
(212, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có thêm cảm giác nào khác không, như là **đổ mồ hôi nhiều**, **tim đập nhanh** hay cảm giác **run tay chân** không nhỉ? Những triệu chứng này có thể giúp làm rõ hơn tình trạng của bạn đó.', 'bot', '2025-07-02 17:18:07'),
(213, 4, NULL, 'symptom_query', 'khong co', 'user', '2025-07-02 17:18:27'),
(214, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có thêm cảm giác nào khác như **đổ mồ hôi nhiều**, **tim đập nhanh** hay **run tay chân** không nhỉ? Những triệu chứng này có thể giúp mình hiểu rõ hơn tình trạng của bạn đó.', 'bot', '2025-07-02 17:18:27'),
(215, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-02 17:27:23'),
(216, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè, bạn thường cảm thấy cơn đau này kéo dài bao lâu mỗi lần bạn gặp vậy?', 'bot', '2025-07-02 17:27:23'),
(217, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-02 17:27:41'),
(218, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **chóng mặt** của bạn nè, thường thì bạn thấy chóng mặt nhiều hơn khi nào, lúc đang ngồi hay khi vừa đứng lên?', 'bot', '2025-07-02 17:27:41'),
(219, 4, NULL, 'symptom_query', 'minh cung khong ro no tu nhien chong mat thoi', 'user', '2025-07-02 17:27:56'),
(220, 4, NULL, 'symptom_query', 'Bạn có cảm thấy cơn **buồn nôn** này thường kéo dài bao lâu mỗi lần không? Có khi nào bạn thấy nó đi kèm với cảm giác mệt mỏi hay không?', 'bot', '2025-07-02 17:27:56'),
(221, 4, NULL, 'symptom_query', 'khi minh ngui thay 1 mui gi do kha nang thi co cam giac buon non', 'user', '2025-07-02 17:28:11'),
(222, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có thêm cảm giác nào khác không, như là **đau bụng**, **đổ mồ hôi nhiều**, hay **tim đập nhanh** không nhỉ? Điều đó có thể giúp mình hình dung rõ hơn về tình trạng của bạn.', 'bot', '2025-07-02 17:28:11'),
(223, 4, NULL, 'symptom_query', 'khong co', 'user', '2025-07-02 17:28:34'),
(224, 4, NULL, 'symptom_query', 'Dựa trên những gì bạn chia sẻ, có thể tình trạng của bạn liên quan đến một số nguyên nhân như sau: \n\n�� **Migraine (đau nửa đầu)**: Đây là cơn đau đầu mạnh, thường đi kèm với triệu chứng như chóng mặt và buồn nôn. Bạn có thể thử nghỉ ngơi trong không gian yên tĩnh và tránh những thức ăn có thể kích thích cơn đau như chocolate hay rượu.\n\n📌 **Cảm cúm hoặc viêm xoang**: Những triệu chứng này có thể do virus gây ra, khiến bạn cảm thấy mệt mỏi, chóng mặt và buồn nôn. Hãy chú ý uống đủ nước và có thể dùng thuốc giảm đau nếu cần.\n\n�� **Hạ huyết áp**: Nếu bạn cảm thấy chóng mặt khi đứng lên, có thể huyết áp của bạn thấp hơn bình thường. Bạn nên nghỉ ngơi và tránh đứng dậy quá nhanh. \n\nNgoài ra, cũng có thể chỉ là do bạn đang mệt hoặc thiếu ngủ gần đây 🌿. Nếu triệu chứng vẫn kéo dài, bạn nên đến gặp bác sĩ để kiểm tra kỹ hơn nhé.', 'bot', '2025-07-02 17:28:34'),
(225, 4, NULL, 'health_advice', 'à hình như mình hiểu tại sao mình cảm thấy chống mặt rồi', 'user', '2025-07-02 17:30:06'),
(226, 4, NULL, 'health_advice', 'Rất tốt khi bạn đã tìm ra lý do! Nếu bạn cảm thấy chóng mặt, thử một vài cách đơn giản sau đây nhé:\n\n1. **Uống nước** thường xuyên, cho cơ thể đủ nước giúp cảm thấy khá hơn. Bạn có thể thử uống một ly nước ấm với một chút chanh để dễ uống hơn 💧.\n\n2. **Ngồi nghỉ**: Nếu bạn cảm thấy chóng mặt, hãy ngồi hoặc nằm xuống một chút. Đôi khi chỉ cần thư giãn một lát là đã ổn định lại cảm giác rồi.\n\n3. **Hít thở sâu**: Thử hít vào thật sâu rồi thở ra chậm. Điều này giúp làm dịu thần kinh và có thể giúp bạn cảm thấy nhẹ nhàng hơn.\n\n4. **Tránh các hoạt động mạnh**: Thỉnh thoảng, nếu bạn đang hoạt động quá sức, hãy cho mình một khoảng thời gian nghỉ ngơi. \n\nThử xem sao nha! Nếu cảm giác này vẫn cứ kéo dài, thì đừng quên tìm bác sĩ để kiểm tra nhé. Chúc bạn mau khỏe! 🌿', 'bot', '2025-07-02 17:30:06'),
(227, 4, NULL, 'symptom_query', 'à hình như mình hiểu tại sao mình cảm thấy chống mặt rồi', 'user', '2025-07-02 17:39:48'),
(228, 4, NULL, 'symptom_query', 'Dựa trên những gì bạn chia sẻ, có thể tình trạng của bạn liên quan đến một số nguyên nhân như sau: \n\n�� **Migraine (đau nửa đầu)**: Đây là cơn đau đầu mạnh, thường đi kèm với triệu chứng như chóng mặt và buồn nôn. Bạn có thể thử nghỉ ngơi trong không gian yên tĩnh và tránh những thức ăn có thể kích thích cơn đau như chocolate hay rượu. \n\n📌 **Cảm cúm hoặc viêm xoang**: Những triệu chứng này có thể do virus gây ra, khiến bạn cảm thấy mệt mỏi, chóng mặt và buồn nôn. Hãy chú ý uống đủ nước và có thể dùng thuốc giảm đau nếu cần. \n\n📌 **Hạ huyết áp**: Nếu bạn cảm thấy chóng mặt khi đứng lên, có thể huyết áp của bạn thấp hơn bình thường. Bạn nên nghỉ ngơi và tránh đứng dậy quá nhanh. \n\nCũng có thể chỉ là do bạn đang mệt hoặc thiếu ngủ gần đây 🌿. Nếu triệu chứng vẫn kéo dài, bạn nên đến gặp bác sĩ để kiểm tra kỹ hơn nhé.', 'bot', '2025-07-02 17:39:48'),
(229, 4, NULL, 'symptom_query', 'à hình như mình hiểu tại sao mình cảm thấy chống mặt rồi', 'user', '2025-07-03 08:00:16'),
(230, 4, NULL, 'symptom_query', 'Cảm giác chóng mặt đó thường xảy ra khi bạn đang ngồi hay khi vừa đứng lên vậy? 😊', 'bot', '2025-07-03 08:00:16'),
(231, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-03 08:15:49'),
(232, 4, NULL, 'symptom_query', 'Cảm giác đau đầu của bạn có thường kéo dài bao lâu mỗi lần xảy ra không? Mình đang nghĩ không biết nó có hay xuất hiện lúc bạn đang ngồi hay vừa đứng lên không ha?', 'bot', '2025-07-03 08:15:49'),
(233, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-03 08:16:10'),
(234, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết cảm giác **chóng mặt** của bạn có hay xuất hiện khi bạn đứng lên hay ngồi lâu không nha?', 'bot', '2025-07-03 08:16:10'),
(235, 4, NULL, 'symptom_query', 'minh cung khong ro no tu nhien chong mat thoi', 'user', '2025-07-03 08:16:23'),
(236, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết cảm giác **buồn nôn** của bạn có thường đi kèm với cảm giác nào khác không, như là mệt mỏi hoặc đau bụng, hay chỉ là cảm giác riêng lẻ thôi nhỉ?', 'bot', '2025-07-03 08:16:23'),
(237, 4, NULL, 'symptom_query', 'khi minh ngui thay 1 mui gi do kha nang thi co cam giac buon non', 'user', '2025-07-03 08:19:20'),
(238, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có thấy thêm cảm giác nào khác không, như là **mệt mỏi**, **đau bụng** hay **hoa mắt** không nhỉ? Điều đó có thể giúp mình hiểu rõ hơn về tình hình của bạn.', 'bot', '2025-07-03 08:19:20'),
(239, 4, NULL, 'general_chat', 'khong co', 'user', '2025-07-03 08:19:30'),
(240, 4, NULL, 'general_chat', 'Cảm ơn bạn đã chia sẻ nghen. Nếu bạn có gì cần hỏi hoặc muốn thảo luận thêm, mình luôn sẵn sàng giúp đỡ!', 'bot', '2025-07-03 08:19:30'),
(241, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-03 08:32:42'),
(242, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** này nè, bạn có thấy cơn đau này thường kéo dài bao lâu mỗi lần bạn gặp phải không?', 'bot', '2025-07-03 08:32:42'),
(243, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-03 08:32:56'),
(244, 4, NULL, 'symptom_query', 'Cảm giác chóng mặt của bạn thường xảy ra khi nào, lúc đang ngồi hay lúc vừa đứng lên vậy?', 'bot', '2025-07-03 08:32:56'),
(245, 4, NULL, 'symptom_query', 'minh cung khong ro no tu nhien chong mat thoi', 'user', '2025-07-03 08:33:15'),
(246, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm chút về cảm giác buồn nôn của bạn nè, có phải bạn cảm thấy buồn nôn nhiều hơn sau khi ăn hay trong những lúc nào cụ thể không ha?', 'bot', '2025-07-03 08:33:15'),
(247, 4, NULL, 'symptom_query', 'khi minh ngui thay 1 mui gi do kha nang thi co cam giac buon non', 'user', '2025-07-03 08:33:29'),
(248, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có thêm cảm giác nào khác nữa không, như là cảm giác tức ngực, mệt mỏi hay nôn mửa chẳng hạn? Điều này có thể giúp mình hiểu rõ hơn về tình trạng của bạn. 🌿', 'bot', '2025-07-03 08:33:29'),
(249, 4, NULL, 'symptom_query', 'khong co', 'user', '2025-07-03 08:34:00'),
(250, 4, NULL, 'symptom_query', 'Dựa trên những gì bạn chia sẻ, có thể tình trạng **đau đầu**, **chóng mặt** và **buồn nôn** của bạn liên quan đến một số vấn đề như sau: \n\n📌 **Căng thẳng hoặc lo âu**: Đôi khi, tâm lý căng thẳng có thể dẫn đến những cảm giác như vậy. Bạn hãy thử thư giãn và dành thời gian cho bản thân nhé. \n\n�� **Mất nước hoặc thiếu dinh dưỡng**: Uống đủ nước và ăn uống khoa học cũng rất quan trọng. Bạn hãy cố gắng bổ sung nước và các thực phẩm dinh dưỡng vào khẩu phần ăn hàng ngày. \n\n📌 **Huyết áp thấp**: Nếu có cảm giác chóng mặt, có thể do huyết áp của bạn giảm. Nghỉ ngơi và uống nước có thể giúp cải thiện tình trạng này. \n\nNếu triệu chứng vẫn kéo dài, bạn nên đến gặp bác sĩ để kiểm tra kỹ hơn nhé. Đừng quên rằng đây chỉ là những gợi ý nhẹ nhàng dựa trên thông tin bạn cung cấp.', 'bot', '2025-07-03 08:34:00'),
(251, 4, NULL, 'symptom_query', 'à hình như mình hiểu tại sao mình cảm thấy chống mặt rồi', 'user', '2025-07-03 09:02:03'),
(252, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **đau đầu**, **chóng mặt**, và **buồn nôn**. Các triệu chứng này có thể liên quan đến một số vấn đề như sau: 📌 **Căng thẳng hoặc lo âu**: Có thể do bạn đang đối mặt với áp lực trong cuộc sống, khiến cơ thể phản ứng lại bằng các triệu chứng này. Bạn có thể thử thư giãn hơn và dành thời gian cho những hoạt động bạn thích. 📌 **Mất nước hoặc thiếu dinh dưỡng**: Đôi khi, nếu cơ thể không được cung cấp đủ nước hoặc dinh dưỡng, bạn có thể cảm thấy chóng mặt và buồn nôn. Nhớ uống đủ nước và ăn uống đầy đủ nhé. 📌 **Huyết áp thấp**: Tình trạng này cũng có thể khiến bạn cảm thấy chóng mặt. Nếu bạn nghỉ ngơi và uống nước mà triệu chứng không cải thiện, hãy xem xét việc gặp bác sĩ để kiểm tra kỹ hơn. Nếu bạn muốn chắc chắn, bạn có thể đi khám để kiểm tra kỹ hơn.', 'bot', '2025-07-03 09:02:04'),
(253, 4, NULL, 'symptom_query', 'à hình như mình hiểu tại sao mình cảm thấy chống mặt rồi', 'user', '2025-07-03 09:15:20'),
(254, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **đau đầu**, **chóng mặt**, và **buồn nôn**. Các triệu chứng này có thể liên quan đến một số vấn đề như sau: \n\n📌 **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **đau đầu** và **buồn nôn**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n\n📌 **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.\n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.\n\nNếu bạn muốn chắc chắn, bạn có thể đi khám để kiểm tra kỹ hơn.', 'bot', '2025-07-03 09:15:20'),
(255, 4, NULL, 'symptom_query', 'à hình như mình hiểu tại sao mình cảm thấy chống mặt rồ', 'user', '2025-07-03 09:27:03'),
(256, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **đau đầu**, **chóng mặt**, và **buồn nôn**. Những triệu chứng này có thể liên quan đến một số nguyên nhân như sau: \n\n📌 **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực trong cuộc sống có thể gây ra cảm giác **đau đầu** và **buồn nôn**. Bạn có thể thử thư giãn và dành thời gian cho bản thân.  \n\n📌 **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi. Hãy nhớ uống đủ nước và ăn uống đầy đủ nhé.  \n\n�� **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột. Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.  \n\nNếu bạn muốn chắc chắn, bạn có thể đi khám để kiểm tra kỹ hơn.', 'bot', '2025-07-03 09:27:04'),
(257, 4, NULL, 'symptom_query', 'à hình như mình hiểu tại sao mình cảm thấy chống mặt rồi', 'user', '2025-07-03 09:37:11'),
(258, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **chóng mặt** và có thể là **buồn nôn**. Những triệu chứng này có thể liên quan đến một số nguyên nhân như sau: \n\n�� **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực trong cuộc sống có thể gây ra cảm giác **chóng mặt**. Bạn có thể thử thư giãn và dành thời gian cho bản thân nhé.  \n\n📌 **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước, bạn có thể cảm thấy **chóng mặt**. Nhớ uống đủ nước và ăn uống đầy đủ trong ngày nha.  \n\n�� **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** khi bạn thay đổi tư thế đột ngột. Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.  \n\nNếu bạn vẫn cảm thấy không khỏe hoặc triệu chứng kéo dài, mình khuyên bạn nên đi khám để kiểm tra kỹ hơn nhé.', 'bot', '2025-07-03 09:37:12'),
(259, 4, NULL, 'symptom_query', 'à hình như mình hiểu tại sao mình cảm thấy chống mặt rồi', 'user', '2025-07-03 09:40:44'),
(260, 4, NULL, 'symptom_query', 'Vậy là bạn đang suy nghĩ thêm về tình trạng của mình rồi nè. Nếu cần mình hỗ trợ thêm, cứ nói nha!', 'bot', '2025-07-03 09:40:44'),
(261, 4, NULL, 'health_advice', 'mình mới nhớ là hôm nay mình chưa ăn gì từ sáng, chắc vì thế mà chóng mặt', 'user', '2025-07-03 09:45:41'),
(262, 4, NULL, 'health_advice', 'Ah, có lẽ vậy đó! Khi chưa ăn gì, cơ thể có thể thiếu năng lượng, dẫn đến cảm giác chóng mặt. Thử làm mấy điều này xem nha:\n\n1. **Ăn một bữa nhẹ**: Thử ăn một chút gì đó dễ tiêu như trái cây, bánh mì hoặc ngũ cốc. Những món này sẽ giúp bạn nạp lại năng lượng ngay lập tức 🍏.\n\n2. **Uống đủ nước**: Đôi khi nhà mình quên uống nước có thể khiến cơ thể mệt mỏi hơn. Nhớ uống chút nước nha!\n\n3. **Ngồi nghỉ một chút**: Nếu bạn cảm thấy chóng mặt, ngồi hoặc nằm xuống một chỗ thoải mái một lúc có thể giúp giảm bớt cảm giác này.\n\nBạn hãy chăm sóc bản thân nhé, nếu tình trạng vẫn không cải thiện thì nhớ tìm sự hỗ trợ từ người khác nha! 💪', 'bot', '2025-07-03 09:45:41'),
(263, 4, NULL, 'symptom_query', 'à hình như mình hiểu tại sao mình cảm thấy chống mặt rồi', 'user', '2025-07-03 09:47:17'),
(264, 4, NULL, 'symptom_query', 'Vậy là bạn đang suy nghĩ thêm về tình trạng của mình rồi nè. Nếu cần mình hỗ trợ thêm, cứ nói nha!', 'bot', '2025-07-03 09:47:17'),
(265, 4, NULL, 'health_advice', 'mình mới nhớ là hôm nay mình chưa ăn gì từ sáng, chắc vì thế mà chóng mặt', 'user', '2025-07-03 09:47:30'),
(266, 4, NULL, 'health_advice', 'À, vậy là đúng rồi đó! Khi chưa ăn gì thì cơ thể sẽ yếu và dễ bị chóng mặt. Thử làm mấy điều này xem nha:\n\n1. **Ăn một bữa nhẹ**: Hãy thử ăn một chút đồ ăn nhẹ như trái cây, bánh mì, hoặc một ít ngũ cốc. Những món này vừa dễ tiêu, vừa nạp lại năng lượng cho bạn 🍎.\n\n2. **Uống nước**: Đừng quên uống nước giữa các bữa ăn. Uống đều đặn có thể giúp cơ thể bạn cảm thấy tỉnh táo hơn.\n\n3. **Thư giãn một chút**: Nếu cảm thấy chóng mặt, hãy ngồi nghỉ hoặc nằm xuống một chỗ thoải mái để cơ thể có thời gian hồi phục.\n\nNhớ chăm sóc bản thân nha, và nếu cảm giác không cải thiện thì hãy tìm sự giúp đỡ từ ai đó nhé! 💖', 'bot', '2025-07-03 09:47:30'),
(267, 4, NULL, 'symptom_query', 'à hình như mình hiểu tại sao mình cảm thấy chống mặt rồi', 'user', '2025-07-03 10:00:20'),
(268, 4, NULL, 'symptom_query', 'Vậy là bạn đang suy nghĩ thêm về tình trạng của mình rồi nè. Nếu cần mình hỗ trợ thêm, cứ nói nha!', 'bot', '2025-07-03 10:00:20'),
(269, 4, NULL, 'symptom_query', 'mình mới nhớ là hôm nay mình chưa ăn gì từ sáng, chắc vì thế mà chóng mặt', 'user', '2025-07-03 10:00:48'),
(270, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **chóng mặt** và có thể là do bạn chưa ăn gì từ sáng. Cảm giác này có thể liên quan đến một số tình trạng như sau:\n\n�� **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.\n\n�� **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **chóng mặt**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.\n\nNếu bạn muốn chắc chắn, bạn có thể đi khám để kiểm tra kỹ hơn.', 'bot', '2025-07-03 10:00:48'),
(271, 4, NULL, 'symptom_query', 'mình mới nhớ là hôm nay mình chưa ăn gì từ sáng, chắc vì thế mà chóng mặt', 'user', '2025-07-03 10:37:32'),
(272, 4, NULL, 'symptom_query', 'Mình thấy bạn mô tả rõ hơn rồi, để mình lưu lại thêm nghen.Và với chi tiết mới đó, có thể liên quan đến huyết áp thấp.', 'bot', '2025-07-03 10:37:32'),
(273, 4, NULL, 'symptom_query', 'mình mới nhớ là hôm nay mình chưa ăn gì từ sáng, chắc vì thế mà chóng mặt', 'user', '2025-07-03 10:45:42'),
(274, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **chóng mặt**, và có thể do bạn chưa ăn gì từ sáng. Điều này có thể liên quan đến một số tình trạng như sau:\n\n�� **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.\n\n�� **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **chóng mặt**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.\n\nNếu bạn muốn chắc chắn, bạn có thể đi khám để kiểm tra kỹ hơn.', 'bot', '2025-07-03 10:45:43'),
(275, 4, NULL, 'symptom_query', 'à hình như mình hiểu tại sao mình cảm thấy chống mặt rồi', 'user', '2025-07-03 10:47:52'),
(276, 4, NULL, 'symptom_query', 'Mình thấy bạn mô tả rõ hơn rồi, để mình lưu lại thêm nghen.Và với chi tiết mới đó, có thể liên quan đến huyết áp thấp.', 'bot', '2025-07-03 10:47:52'),
(277, 4, NULL, 'symptom_query', 'à hình như mình hiểu tại sao mình cảm thấy chống mặt rồi', 'user', '2025-07-03 11:02:15'),
(278, 4, NULL, 'symptom_query', 'Mình thấy bạn mô tả rõ hơn rồi, để mình lưu lại thêm nghen.', 'bot', '2025-07-03 11:02:15'),
(279, 4, NULL, 'symptom_query', 'À giờ mình mới nhớ là hôm nay mình chưa ăn gì từ sáng, chắc vì thế mà chóng mặt', 'user', '2025-07-03 11:03:43'),
(280, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **chán ăn** của bạn nè. Bạn có thấy mình không muốn ăn trong suốt cả ngày hay chỉ trong một khoảng thời gian nào đó thôi?', 'bot', '2025-07-03 11:03:43'),
(281, 4, NULL, 'symptom_query', 'À giờ mình mới nhớ là hôm nay mình chưa ăn gì từ sáng, chắc vì thế mà chóng mặt', 'user', '2025-07-03 11:10:00'),
(282, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **chóng mặt** và có thể là do bạn chưa ăn gì từ sáng. Nếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi. Ngoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau:\n\n�� **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.\n\n�� **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **chóng mặt**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha. Nếu cần, mình có thể hỗ trợ bạn đặt lịch khám phù hợp nha.', 'bot', '2025-07-03 11:10:00'),
(283, 4, NULL, 'symptom_query', 'À giờ mình mới nhớ là hôm nay mình chưa ăn gì từ sáng, chắc vì thế mà chóng mặt', 'user', '2025-07-03 11:15:45'),
(284, 4, NULL, 'symptom_query', 'Mình thấy bạn mô tả rõ hơn rồi, để mình lưu lại thêm nghen.Và với chi tiết mới đó, có thể liên quan đến huyết áp thấp.', 'bot', '2025-07-03 11:15:46'),
(285, 4, NULL, 'symptom_query', 'À giờ mình mới nhớ là hôm nay mình chưa ăn gì từ sáng, chắc vì thế mà chóng mặt', 'user', '2025-07-03 11:17:52'),
(286, 4, NULL, 'symptom_query', 'Mình thấy bạn mô tả rõ hơn rồi, để mình lưu lại thêm nghen.Và với chi tiết mới đó, có thể liên quan đến huyết áp thấp.', 'bot', '2025-07-03 11:17:52'),
(287, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-03 11:37:36'),
(288, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **đau đầu** nè. Bạn có thấy tình trạng này thường kéo dài bao lâu mỗi lần bạn gặp vậy?', 'bot', '2025-07-03 11:37:37'),
(289, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-03 11:37:53'),
(290, 4, NULL, 'symptom_query', 'Khi bạn cảm thấy **chóng mặt**, liệu có lúc nào bạn thấy điều đó xảy ra khi đang đứng lên hay ngồi xuống không? Hoặc nó thường kéo dài từ vài phút đến nhiều giờ nhỉ?', 'bot', '2025-07-03 11:37:54'),
(291, 4, NULL, 'symptom_query', 'minh cung khong ro no tu nhien chong mat thoi', 'user', '2025-07-03 11:38:13'),
(292, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **buồn nôn** nè. Bạn có thấy tình trạng này có đi kèm với việc ăn uống hay sau khi làm điều gì đó không? Hay là bạn cảm thấy khó chịu hơn khi ở đâu đó nhất định không?', 'bot', '2025-07-03 11:38:13'),
(293, 4, NULL, 'symptom_query', 'khi minh ngui thay 1 mui gi do kha nang thi co cam giac buon non', 'user', '2025-07-03 11:38:28'),
(294, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có thêm cảm giác nào khác nữa không, như là tim đập nhanh hoặc mệt mỏi chẳng hạn? Điều này có thể giúp mình hiểu rõ hơn về tình trạng của bạn.', 'bot', '2025-07-03 11:38:28'),
(295, 4, NULL, 'symptom_query', 'à hình như mình hiểu tại sao mình cảm thấy chống mặt rồi', 'user', '2025-07-03 11:39:04'),
(296, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **đau đầu**, **chóng mặt**, và **buồn nôn**. Những triệu chứng này có thể liên quan đến một số tình trạng nhẹ nhàng. Ví dụ: \n\n- **Đau đầu** có thể là do bạn thiếu ngủ hoặc căng thẳng. Bạn thử nghỉ ngơi xem sao nha. \n- **Chóng mặt** có thể do thay đổi tư thế đột ngột hoặc thiếu nước nhẹ. Bạn có thể thử uống nước từ từ và ngồi nghỉ. \n- **Buồn nôn** thỉnh thoảng xảy ra khi bạn lo lắng hoặc ăn uống không đều. Bạn hãy chú ý đến chế độ ăn uống của mình nhé. \n\nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau:\n\n�� **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **đau đầu** và **buồn nôn**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.  \n\n�� **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.  \n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha. \n\nNếu bạn muốn chắc chắn, bạn có thể đi khám để kiểm tra kỹ hơn.', 'bot', '2025-07-03 11:39:04'),
(297, 4, NULL, 'symptom_query', 'à hình như mình hiểu tại sao mình cảm thấy chống mặt rồi', 'user', '2025-07-03 11:42:42'),
(298, 4, NULL, 'symptom_query', 'Mình thấy bạn mô tả rõ hơn rồi, để mình lưu lại thêm nghen. Nếu bạn đã nhận ra lý do khiến mình cảm thấy **chóng mặt**, có thể là do một số nguyên nhân nhẹ như thiếu nước, mệt mỏi hoặc áp lực công việc. Nếu cần mình hỗ trợ thêm, cứ nói nha!', 'bot', '2025-07-03 11:42:42'),
(299, 4, NULL, 'symptom_query', 'mình mới nhớ là hôm nay mình chưa ăn gì từ sáng, chắc vì thế mà chóng mặt', 'user', '2025-07-03 11:43:31'),
(300, 4, NULL, 'symptom_query', 'Mình thấy bạn mô tả rõ hơn rồi, để mình lưu lại thêm nghen.Bạn đã nói là chưa ăn gì từ sáng, nên cảm giác **chóng mặt** có thể do thiếu dinh dưỡng hoặc hạ đường huyết tạm thời. Bạn thử ăn một cái gì đó nhẹ nhàng như trái cây hoặc bánh mì và uống nước xem có đỡ hơn không nha.', 'bot', '2025-07-03 11:43:31'),
(301, 4, NULL, 'symptom_query', 'mình mới nhớ là hôm nay mình chưa ăn gì từ sáng, chắc vì thế mà chóng mặt', 'user', '2025-07-03 11:51:01'),
(302, 4, NULL, 'symptom_query', 'Mình thấy bạn mô tả rõ hơn rồi, có thể là do hôm nay bạn chưa ăn gì đó nha. Cảm ơn bạn, mình sẽ ghi chú lại thêm.', 'bot', '2025-07-03 11:51:01'),
(303, 4, NULL, 'symptom_query', 'mình mới nhớ là hôm nay mình chưa ăn gì từ sáng, chắc vì thế mà chóng mặt', 'user', '2025-07-03 11:54:23'),
(304, 4, NULL, 'symptom_query', 'Mình thấy bạn mô tả rõ hơn rồi, có thể là do hôm nay bạn chưa ăn gì đó nha. Cảm ơn bạn, mình sẽ ghi chú lại thêm.', 'bot', '2025-07-03 11:54:23'),
(305, 4, NULL, 'symptom_query', 'Mình thấy chóng mặt từ sáng tới giờ vẫn chưa hết, với lần này cảm giác nó quay nhiều hơn bình thường. Ngồi xuống một lúc mà vẫn thấy lâng lâng luôn á.', 'user', '2025-07-03 12:20:13'),
(306, 4, NULL, 'symptom_query', 'Mình thấy bạn mô tả rõ hơn rồi, có thể là do bạn chưa ăn gì từ sáng nên thấy **chóng mặt**. Nhưng cũng có thể là do bạn thiếu ngủ, cơ thể mệt hoặc thời tiết thay đổi nữa. Mình sẽ ghi chú lại thêm để theo dõi ha.', 'bot', '2025-07-03 12:20:13'),
(307, 4, NULL, 'symptom_query', 'Mình thấy chóng mặt từ sáng tới giờ vẫn chưa hết, với lần này cảm giác nó quay nhiều hơn bình thường. Ngồi xuống một lúc mà vẫn thấy lâng lâng luôn á.', 'user', '2025-07-03 12:27:50');
INSERT INTO `chat_logs` (`chat_id`, `user_id`, `guest_id`, `intent`, `message`, `sender`, `sent_at`) VALUES
(308, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **chóng mặt** kéo dài từ sáng với cảm giác quay nhiều hơn bình thường. Điều này có thể do một số nguyên nhân như: \n- **Chóng mặt** có thể do bạn chưa ăn gì từ sáng, dẫn tới thiếu dinh dưỡng hoặc hạ đường huyết tạm thời. Bạn có thể thử ăn nhẹ để cảm thấy khá hơn. \n- **Mất nước** cũng có thể là nguyên nhân, nếu cơ thể bạn không được cung cấp đủ nước. Hãy nhớ uống đủ nước trong ngày nhé. \nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau: \n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** khi bạn chưa ăn gì.  \n→ Bạn nên nghỉ ngơi và uống nước từ từ.  \n\n📌 **Thiếu năng lượng nhẹ**  \nCơ thể có thể hạ đường huyết tạm thời nếu chưa ăn lâu.  \n→ Bạn có thể ăn nhẹ hoặc uống sữa để lấy lại sức.  \n\n📌 **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **chóng mặt**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và thư giãn hơn. \n\nNếu tình trạng này tiếp tục xảy ra, hãy đi khám để kiểm tra kỹ hơn nhé.', 'bot', '2025-07-03 12:27:50'),
(309, 4, NULL, 'symptom_query', 'Mình thấy chóng mặt từ sáng tới giờ vẫn chưa hết, với lần này cảm giác nó quay nhiều hơn bình thường. Ngồi xuống một lúc mà vẫn thấy lâng lâng luôn á.', 'user', '2025-07-03 13:08:31'),
(310, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác **chóng mặt** kéo dài từ sáng với cảm giác quay nhiều hơn bình thường. Điều này có thể do bạn chưa ăn gì từ sáng, dẫn tới thiếu dinh dưỡng hoặc hạ đường huyết tạm thời. Bạn nên thử ăn nhẹ và uống đủ nước nhé. Ngoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau: \n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** khi bạn chưa ăn gì.  \n→ Bạn nên nghỉ ngơi và uống nước từ từ.  \n\n📌 **Thiếu năng lượng nhẹ**  \nCơ thể có thể hạ đường huyết tạm thời nếu chưa ăn lâu.  \n→ Bạn có thể ăn nhẹ hoặc uống sữa để lấy lại sức.  \n\n📌 **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **chóng mặt**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và thư giãn hơn. Nếu bạn muốn chắc chắn, bạn có thể đi khám để kiểm tra kỹ hơn.', 'bot', '2025-07-03 13:08:31'),
(311, 4, NULL, 'symptom_query', 'Mình thấy chóng mặt từ sáng tới giờ vẫn chưa hết, với lần này cảm giác nó quay nhiều hơn bình thường. Ngồi xuống một lúc mà vẫn thấy lâng lâng luôn á.', 'user', '2025-07-03 13:16:16'),
(312, 4, NULL, 'symptom_query', 'Mình thấy bạn mô tả rõ hơn rồi, cảm giác **chóng mặt** của bạn kéo dài từ sáng và có vẻ như quay nhiều hơn bình thường. Điều này có thể do bạn chưa ăn gì, dẫn tới hạ đường huyết hoặc thiếu dinh dưỡng. Nhưng cũng có thể do bạn đang mệt hoặc căng thẳng. Mình sẽ ghi chú lại thêm để theo dõi sát hơn ha.', 'bot', '2025-07-03 13:16:17'),
(313, 4, NULL, 'symptom_query', 'Mình thấy chóng mặt từ sáng tới giờ vẫn chưa hết, với lần này cảm giác nó quay nhiều hơn bình thường. Ngồi xuống một lúc mà vẫn thấy lâng lâng luôn á.', 'user', '2025-07-03 13:27:22'),
(314, 4, NULL, 'symptom_query', 'Mình thấy bạn mô tả rõ hơn rồi, cảm giác **chóng mặt** kéo dài và quay nhiều hơn bình thường có thể là do bạn chưa ăn gì từ sáng. Tuy nhiên, nếu triệu chứng này vẫn không giảm hoặc tiếp tục làm bạn khó chịu, mình khuyên bạn nên đi khám để kiểm tra kỹ hơn nhé. Nếu cần, mình có thể hỗ trợ bạn đặt lịch khám phù hợp nha.', 'bot', '2025-07-03 13:27:23'),
(315, 4, NULL, 'symptom_query', 'Mình thấy chóng mặt từ sáng tới giờ vẫn chưa hết, với lần này cảm giác nó quay nhiều hơn bình thường. Ngồi xuống một lúc mà vẫn thấy lâng lâng luôn á.', 'user', '2025-07-03 13:36:32'),
(316, 4, NULL, 'symptom_query', 'Mình thấy bạn mô tả rõ hơn rồi, cảm giác **chóng mặt** của bạn kéo dài và quay nhiều hơn bình thường có thể do bạn chưa ăn gì từ sáng.Ngoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau: \n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn chưa ăn gì.  \n→ Bạn nên nghỉ ngơi, uống nước và ăn nhẹ để ổn định lại.  \n\n📌 **Thiếu năng lượng nhẹ**  \nCơ thể có thể hạ đường huyết tạm thời nếu chưa ăn lâu.  \n→ Bạn có thể ăn nhẹ hoặc uống sữa để lấy lại sức.  \n\n📌 **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **chóng mặt**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và thư giãn hơn. Nếu cần, mình có thể hỗ trợ bạn đặt lịch khám phù hợp nha.', 'bot', '2025-07-03 13:36:33'),
(317, 4, NULL, 'patient_summary_request', 'lay benh chan doan duoc cua nguoi dung co ten nguyen van A', 'user', '2025-07-03 13:53:45'),
(318, 4, NULL, 'patient_summary_request', '<p>\n    Bệnh nhân đã báo cáo các triệu chứng như <strong>đau đầu</strong> vào ngày 03/07/2025, kéo dài khoảng 5-10 phút khi nằm xuống. Cũng trong ngày này, bệnh nhân cảm thấy <strong>chóng mặt</strong> từ sáng, có thể do chưa ăn gì, và cơn chóng mặt kéo dài hơn bình thường. Ngoài ra, bệnh nhân còn có cảm giác <strong>buồn nôn</strong> nhẹ khi ngửi thấy một mùi nào đó. Vào ngày 01/07/2025, bệnh nhân đã mô tả các triệu chứng như <strong>tê tay chân</strong>, <strong>khàn giọng</strong> và <strong>yếu cơ</strong>. Trước đó, vào ngày 25/06/2025, bệnh nhân đã gặp phải tình trạng <strong>mệt mỏi</strong>, <strong>chóng mặt</strong>, <strong>tê tay chân</strong> bên trái, cùng với <strong>khàn giọng</strong> và khó khăn trong việc nói chuyện.\n</p>\n\n<div>\n    🔴 <strong>Căng Thẳng Hoặc Lo Âu</strong><br>\n    Cảm giác đau đầu và buồn nôn có thể là dấu hiệu của căng thẳng hoặc lo âu. Gợi ý: Bạn nên thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n</div>\n\n<div>\n    🔴 <strong>Mất Nước Hoặc Thiếu Dinh Dưỡng</strong><br>\n    Thiếu nước hoặc năng lượng có thể dẫn đến chóng mặt và mệt mỏi. Gợi ý: Bạn nên uống đủ nước và ăn uống đầy đủ trong ngày.\n</div>\n\n<div>\n    🟠 <strong>Huyết Áp Thấp</strong><br>\n    Huyết áp thấp có thể gây chóng mặt khi thay đổi tư thế. Gợi ý: Bạn nên ngồi nghỉ và uống nước từ từ.\n</div>\n\n<div>\n    🟠 <strong>Thiếu Năng Lượng Nhẹ</strong><br>\n    Cơ thể có thể bị hạ đường huyết tạm thời nếu nhịn ăn lâu. Gợi ý: Bạn có thể ăn nhẹ hoặc uống sữa để lấy lại sức.\n</div>\n\n<div>\n    🔴 <strong>Đột Quỵ Nhẹ</strong><br>\n    Triệu chứng này có thể là dấu hiệu của đột quỵ nhẹ, đặc biệt khi có tê một bên mặt và khó nói. Gợi ý: Nếu có triệu chứng này, bạn cần kiểm tra ngay với bác sĩ.\n</div>\n\n<div>\n    🟠 <strong>Rối Loạn Thần Kinh</strong><br>\n    Các triệu chứng như khàn giọng và đau đầu có thể liên quan đến rối loạn thần kinh. Gợi ý: Bạn nên nghỉ ngơi và theo dõi thêm các triệu chứng khác.\n</div>\n\n<div>\n    🟠 <strong>Không rõ</strong><br>\n    Mệt mỏi và yếu cơ có thể do thiếu máu hoặc vấn đề dinh dưỡng. Gợi ý: Bạn nên đảm bảo ăn uống đủ chất và nghỉ ngơi.\n</div', 'bot', '2025-07-03 13:53:45'),
(319, 4, NULL, 'symptom_query', 'ngay 7/3', 'user', '2025-07-03 13:54:16'),
(320, 4, NULL, 'symptom_query', 'Mình thấy bạn mô tả rõ hơn rồi, để mình lưu lại thêm nghen.Bạn đã nói là chưa ăn gì từ sáng, nên cảm giác **chóng mặt** có thể do hạ đường huyết hoặc thiếu nước. Ngoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau: \n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn chưa ăn gì.  \n→ Bạn nên nghỉ ngơi, uống nước và ăn nhẹ để ổn định lại.\n\n�� **Thiếu năng lượng nhẹ**  \nCơ thể có thể hạ đường huyết tạm thời nếu chưa ăn lâu.  \n→ Bạn có thể ăn nhẹ hoặc uống sữa để lấy lại sức.\n\n📌 **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **chóng mặt**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và thư giãn hơn. Nếu cần, mình có thể hỗ trợ bạn đặt lịch khám phù hợp nha.', 'bot', '2025-07-03 13:54:16'),
(321, 4, NULL, 'patient_summary_request', 'lay benh chan doan duoc cua nguoi dung co ten nguyen van A', 'user', '2025-07-03 13:59:45'),
(322, 4, NULL, 'patient_summary_request', '<p>Bệnh nhân đã báo cáo một số triệu chứng sức khỏe trong thời gian gần đây. Vào ngày <strong>01/07/2025</strong>, bệnh nhân cảm thấy <strong>tê tay chân</strong>, <strong>khàn giọng</strong>, <strong>yếu cơ</strong> và <strong>đau đầu</strong>. Vào ngày <strong>25/06/2025</strong>, bệnh nhân gặp phải tình trạng <strong>tê tay chân</strong> bên trái, <strong>khàn giọng</strong>, <strong>mệt mỏi</strong> và <strong>chóng mặt</strong>. Đặc biệt, vào ngày <strong>03/07/2025</strong>, bệnh nhân báo cáo <strong>đau đầu</strong> khi nằm xuống, <strong>chóng mặt</strong> kéo dài, <strong>buồn nôn</strong> nhẹ khi ngửi thấy mùi, và <strong>tê tay chân</strong>.</p>\n\n<div>\n    <strong>🔴 Căng Thẳng Hoặc Lo Âu</strong><br>\n    Căng thẳng trong công việc hoặc cuộc sống có thể gây ra cảm giác đau đầu và buồn nôn. Gợi ý: Bạn nên thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n</div>\n\n<div>\n    <strong>🔴 Mất Nước Hoặc Thiếu Dinh Dưỡng</strong><br>\n    Thiếu nước hoặc năng lượng có thể dẫn đến chóng mặt và mệt mỏi. Gợi ý: Bạn nên uống đủ nước và ăn uống đầy đủ trong ngày.\n</div>\n\n<div>\n    <strong>🟠 Huyết Áp Thấp</strong><br>\n    Huyết áp thấp có thể gây chóng mặt khi thay đổi tư thế. Gợi ý: Bạn nên ngồi nghỉ và uống nước từ từ.\n</div>\n\n<div>\n    <strong>🟠 Thiếu Năng Lượng Nhẹ</strong><br>\n    Cơ thể có thể bị hạ đường huyết tạm thời nếu nhịn ăn lâu. Gợi ý: Bạn có thể ăn nhẹ hoặc uống sữa để lấy lại sức.\n</div>\n\n<div>\n    <strong>🔴 Đột Quỵ Nhẹ</strong><br>\n    Triệu chứng này có thể là dấu hiệu của đột quỵ nhẹ, nhất là khi có tê một bên mặt và khó nói. Gợi ý: Nếu có triệu chứng này, bạn cần kiểm tra ngay với bác sĩ.\n</div>\n\n<div>\n    <strong>🟠 Rối Loạn Thần Kinh</strong><br>\n    Các triệu chứng như khàn giọng và đau đầu có thể liên quan đến rối loạn thần kinh. Gợi ý: Bạn nên nghỉ ngơi và theo dõi thêm các triệu chứng khác.\n</div>\n\n<div>\n    <strong>🟠 Không rõ</strong><br>\n    Mệt mỏi và yếu cơ có thể do thiếu máu hoặc vấn đề dinh dưỡng. Gợi ý: Bạn nên đảm bảo ăn uống đủ chất và nghỉ ngơi.\n</div>\n\n<div>\n    <strong>🔴 Migrain</strong><br>\n    Migrain là một dạng', 'bot', '2025-07-03 13:59:45'),
(323, 4, NULL, 'symptom_query', 'ngày 7/3', 'user', '2025-07-03 14:00:18'),
(324, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm về cảm giác **tê tay chân** của bạn nè. Có khi nào bạn thấy tê ở một bên hay cả hai bên không? Hoặc cảm giác này thường xảy ra lúc nào, lúc ngồi hay đứng lên?', 'bot', '2025-07-03 14:00:18'),
(325, 4, NULL, 'patient_summary_request', 'lay benh chan doan duoc cua nguoi dung co ten nguyen van A', 'user', '2025-07-03 14:16:01'),
(326, 4, NULL, 'patient_summary_request', 'Xin bác sĩ cho biết ngày cụ thể mà bác muốn xem thông tin bệnh của người dùng Nguyễn Văn A.', 'bot', '2025-07-03 14:16:02'),
(327, 4, NULL, 'patient_summary_request', 'ngay 7/3', 'user', '2025-07-03 14:16:16'),
(328, 4, NULL, 'patient_summary_request', '<p>Người bệnh không báo cáo triệu chứng nào gần đây.</p>\n\n<div>\n    <strong>🔴 Không có dự đoán bệnh lý</strong><br>\n    Hiện tại, không có dự đoán nào về bệnh lý từ AI. Bạn nên tiếp tục theo dõi sức khỏe của mình và đến gặp bác sĩ nếu có bất kỳ triệu chứng nào xuất hiện trong tương lai. Gợi ý: Hãy duy trì lối sống lành mạnh và thường xuyên kiểm tra sức khỏe định kỳ.\n</div>', 'bot', '2025-07-03 14:16:16'),
(329, 4, NULL, 'patient_summary_request', 'lay benh chan doan duoc cua nguoi dung co ten nguyen van A', 'user', '2025-07-03 14:34:24'),
(330, 4, NULL, 'patient_summary_request', 'Xin bác sĩ cho biết ngày cụ thể mà bác muốn xem thông tin bệnh án của người dùng này.', 'bot', '2025-07-03 14:34:24'),
(331, 4, NULL, 'patient_summary_request', 'ngay hom nay', 'user', '2025-07-03 14:35:10'),
(332, 4, NULL, 'patient_summary_request', 'Xin bác sĩ cho biết ngày cụ thể mà bác muốn xem thông tin bệnh án của người dùng này.', 'bot', '2025-07-03 14:35:10'),
(333, 4, NULL, 'patient_summary_request', 'lay benh chan doan duoc cua nguoi dung co ten nguyen van A', 'user', '2025-07-03 14:42:17'),
(334, 4, NULL, 'patient_summary_request', 'Xin bác sĩ cho biết ngày cụ thể mà bác muốn xem thông tin bệnh án của người dùng này.', 'bot', '2025-07-03 14:42:18'),
(335, 4, NULL, 'patient_summary_request', 'ngay 7/3', 'user', '2025-07-03 14:42:33'),
(336, 4, NULL, 'patient_summary_request', '<p>Không có triệu chứng nào được báo cáo gần đây.</p>\n\n<div>\n    <span>🔴 <strong>Bệnh tim mạch</strong></span><br>\n    Bệnh tim mạch có thể gây ra nhiều vấn đề sức khỏe nghiêm trọng. Bạn nên theo dõi sức khỏe tim mạch của mình và thực hiện các kiểm tra định kỳ để phát hiện sớm bất kỳ vấn đề nào. Gợi ý: Bạn nên duy trì lối sống lành mạnh với chế độ ăn uống hợp lý và tập thể dục thường xuyên.\n</div>\n\n<div>\n    <span>🟠 <strong>Bệnh tiểu đường</strong></span><br>\n    Bệnh tiểu đường là một tình trạng mãn tính ảnh hưởng đến cách cơ thể sử dụng glucose. Việc kiểm soát lượng đường trong máu là rất quan trọng. Gợi ý: Bạn nên kiểm tra đường huyết thường xuyên và tuân thủ chế độ ăn uống mà bác sĩ đề xuất.\n</div>\n\n<div>\n    <span>🟡 <strong>Bệnh hô hấp</strong></span><br>\n    Bệnh hô hấp có thể ảnh hưởng đến khả năng hô hấp và sức khỏe tổng thể. Nếu có bất kỳ triệu chứng nào như khó thở hoặc ho kéo dài, bạn nên đi khám bác sĩ. Gợi ý: Hãy tránh khói thuốc và các tác nhân gây dị ứng để bảo vệ phổi của bạn.\n</div>', 'bot', '2025-07-03 14:42:33'),
(337, 4, NULL, 'general_chat', 'ngay 3/7', 'user', '2025-07-03 14:43:09'),
(338, 4, NULL, 'general_chat', 'Um, mình hiểu rồi nè. Bạn có cần thêm thông tin gì về ngày này không?', 'bot', '2025-07-03 14:43:10'),
(339, 4, NULL, 'patient_summary_request', 'lay benh chan doan duoc cua nguoi dung co ten nguyen van A', 'user', '2025-07-03 14:44:04'),
(340, 4, NULL, 'patient_summary_request', 'Xin bác sĩ cho biết ngày cụ thể mà bác muốn xem thông tin bệnh án của người dùng này.', 'bot', '2025-07-03 14:44:04'),
(341, 4, NULL, 'patient_summary_request', 'ngay 3/7', 'user', '2025-07-03 14:44:20'),
(342, 4, NULL, 'patient_summary_request', '<p>Người dùng đã báo cáo các triệu chứng sau đây vào ngày 03/07/2025: <strong>Đau đầu</strong> khi vừa nằm xuống, cơn đau kéo dài khoảng 5-10 phút; <strong>Chóng mặt</strong> từ sáng tới giờ, có thể do chưa ăn gì, cảm giác chóng mặt quay nhiều hơn bình thường và chưa hết; và <strong>Buồn nôn</strong> nhẹ khi ngửi thấy một mùi gì đó.</p>\n\n<div>\n    <strong>🔴 Căng Thẳng Hoặc Lo Âu</strong><br>\n    Áp lực công việc hoặc cuộc sống có thể gây ra cảm giác đau đầu và buồn nôn. Gợi ý: Bạn nên nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n</div>\n\n<div>\n    <strong>🔴 Mất Nước Hoặc Thiếu Dinh Dưỡng</strong><br>\n    Thiếu nước hoặc năng lượng có thể dẫn đến chóng mặt và mệt mỏi. Gợi ý: Bạn nên uống đủ nước và ăn uống đầy đủ trong ngày.\n</div>\n\n<div>\n    <strong>🟠 Huyết Áp Thấp</strong><br>\n    Huyết áp thấp có thể gây chóng mặt khi thay đổi tư thế. Gợi ý: Bạn nên ngồi nghỉ và uống nước từ từ.\n</div>\n\n<div>\n    <strong>🟠 Thiếu Năng Lượng Nhẹ</strong><br>\n    Cơ thể bị hạ đường huyết tạm thời nếu nhịn ăn lâu. Gợi ý: Bạn có thể ăn nhẹ hoặc uống sữa để lấy lại sức.\n</div>', 'bot', '2025-07-03 14:44:20'),
(343, 4, NULL, 'patient_summary_request', 'lay benh chan doan duoc cua nguoi dung co ten nguyen van A', 'user', '2025-07-03 14:45:21'),
(344, 4, NULL, 'patient_summary_request', 'Xin bác sĩ cho biết ngày cụ thể mà bác muốn xem thông tin bệnh án của người dùng này.', 'bot', '2025-07-03 14:45:21'),
(345, 4, NULL, 'patient_summary_request', 'ngay gan nhat', 'user', '2025-07-03 14:45:44'),
(346, 4, NULL, 'patient_summary_request', '<p>Người bệnh đã báo cáo một số triệu chứng sức khỏe gần đây như sau: <strong>Đau đầu</strong> vào ngày 03/07/2025, cơn đau kéo dài khoảng 5-10 phút khi nằm xuống. <strong>Chóng mặt</strong> cũng xuất hiện vào ngày 03/07/2025, kéo dài từ sáng và có thể liên quan đến việc chưa ăn uống. Ngoài ra, người bệnh còn cảm thấy <strong>buồn nôn</strong> nhẹ khi ngửi thấy một mùi gì đó. Trước đó, vào ngày 01/07/2025, người bệnh đã mô tả các triệu chứng như <strong>tê tay chân</strong>, <strong>khàn giọng</strong>, và <strong>yếu cơ</strong>. Vào ngày 25/06/2025, người bệnh gặp phải tình trạng <strong>tê tay chân</strong> bên trái, <strong>khàn giọng</strong>, và cảm giác <strong>mệt mỏi</strong> cùng với <strong>chóng mặt</strong> kéo dài.</p>\n\n<div>\n    <strong>🔴 Căng Thẳng Hoặc Lo Âu</strong><br>\n    Áp lực công việc hoặc cuộc sống có thể gây ra cảm giác đau đầu và buồn nôn. Gợi ý: Bạn nên thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n</div>\n\n<div>\n    <strong>🔴 Mất Nước Hoặc Thiếu Dinh Dưỡng</strong><br>\n    Thiếu nước hoặc năng lượng có thể dẫn đến chóng mặt và mệt mỏi. Gợi ý: Bạn nên uống đủ nước và ăn uống đầy đủ trong ngày.\n</div>\n\n<div>\n    <strong>🟠 Huyết Áp Thấp</strong><br>\n    Huyết áp thấp có thể gây chóng mặt khi thay đổi tư thế. Gợi ý: Bạn nên ngồi nghỉ và uống nước từ từ.\n</div>\n\n<div>\n    <strong>🟠 Thiếu Năng Lượng Nhẹ</strong><br>\n    Cơ thể có thể bị hạ đường huyết tạm thời nếu nhịn ăn lâu. Gợi ý: Bạn có thể ăn nhẹ hoặc uống sữa để lấy lại sức.\n</div>\n\n<div>\n    <strong>🔴 Đột Quỵ Nhẹ</strong><br>\n    Triệu chứng này có thể là dấu hiệu của đột quỵ nhẹ, đặc biệt khi có tê một bên mặt và khó nói. Gợi ý: Nếu có triệu chứng này, bạn cần kiểm tra ngay với bác sĩ.\n</div>\n\n<div>\n    <strong>🟠 Rối Loạn Thần Kinh</strong><br>\n    Các triệu chứng như khàn giọng và đau đầu có thể liên quan đến rối loạn thần kinh. Gợi ý: Bạn nên nghỉ ngơi và theo dõi thêm các triệu chứng khác.\n</div>\n\n<div>\n    <strong>🟠 Không rõ</strong><br>\n    Mệt mỏi và yếu cơ có thể do thiếu máu hoặc vấn đề dinh dưỡng. Gợi ý: Bạn nên đảm bảo ăn uống đủ chất và nghỉ ngơi.\n</div', 'bot', '2025-07-03 14:45:44'),
(347, 4, NULL, 'patient_summary_request', 'lay benh chan doan duoc cua nguoi dung co ten nguyen van A ngay 3/7', 'user', '2025-07-03 14:52:55'),
(348, 4, NULL, 'patient_summary_request', '<p>\n    Các triệu chứng được báo cáo:<br>\n    <strong>Đau đầu</strong> (03/07/2025): Người dùng bị đau đầu khi vừa nằm xuống, cơn đau kéo dài khoảng 5-10 phút.<br>\n    <strong>Chóng mặt</strong> (03/07/2025): Người dùng cảm thấy chóng mặt từ sáng tới giờ, triệu chứng không rõ nguyên nhân và xảy ra một cách tự nhiên. Hôm nay, người dùng chưa ăn gì từ sáng, có thể là nguyên nhân gây ra triệu chứng. Cảm giác chóng mặt lần này quay nhiều hơn bình thường, và dù đã ngồi xuống một lúc, người dùng vẫn cảm thấy lâng lâng. Triệu chứng vẫn chưa hết.<br>\n    <strong>Buồn nôn</strong> (03/07/2025): Người dùng có cảm giác buồn nôn nhẹ khi ngửi thấy một mùi gì đó.\n</p>\n<div>\n    🔴 <strong>Căng Thẳng Hoặc Lo Âu</strong><br>\n    Áp lực công việc hoặc cuộc sống có thể gây ra cảm giác đau đầu và buồn nôn.<br>\n    Gợi ý: Thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n</div>\n<div>\n    🔴 <strong>Mất Nước Hoặc Thiếu Dinh Dưỡng</strong><br>\n    Thiếu nước hoặc năng lượng có thể dẫn đến chóng mặt và mệt mỏi.<br>\n    Gợi ý: Uống đủ nước và ăn uống đầy đủ trong ngày.\n</div>\n<div>\n    🟠 <strong>Huyết Áp Thấp</strong><br>\n    Huyết áp thấp có thể gây chóng mặt khi thay đổi tư thế.<br>\n    Gợi ý: Ngồi nghỉ và uống nước từ từ.\n</div>\n<div>\n    🟠 <strong>Thiếu Năng Lượng Nhẹ</strong><br>\n    Cơ thể bị hạ đường huyết tạm thời nếu nhịn ăn lâu.<br>\n    Gợi ý: Bạn có thể ăn nhẹ hoặc uống sữa để lấy lại sức.\n</div>', 'bot', '2025-07-03 14:52:55'),
(349, 4, NULL, 'health_advice', 'dao nay da minh co vai cho no bi kho qua khong biet co cach nao cai thien khong ta? no bi bong da lên lun', 'user', '2025-07-07 09:45:45'),
(350, 4, NULL, 'health_advice', 'Nhiều khi da bị khô với bong tróc là do thời tiết hoặc thiếu độ ẩm. Bạn có thể thử vài cách tự nhiên để cải thiện như là uống đủ nước mỗi ngày, giúp da luôn ẩm mượt hơn. Ngoài ra, sau khi tắm, hãy dưỡng ẩm cho da luôn nhé, lúc da còn hơi ẩm sẽ thẩm thấu tốt hơn. \n\nMình cũng thấy dễ chịu hơn khi dùng các loại dầu tự nhiên như dầu dừa hoặc dầu oliu xoa lên da, giúp làm mềm và giữ ẩm cho da. Nếu bạn thích, mình có thể gợi ý thêm vài sản phẩm nhẹ nhàng để hỗ trợ da bạn nha 🌿.', 'bot', '2025-07-07 09:45:45'),
(351, 4, NULL, 'health_advice', 'dao nay da minh co vai cho no bi kho qua khong biet co cach nao cai thien khong ta? no bi bong da lên lun', 'user', '2025-07-07 09:49:47'),
(352, 4, NULL, 'health_advice', 'Nhiều khi da bị khô và bong tróc là do thời tiết thay đổi hoặc thiếu độ ẩm. Bạn có thể thử những cách sau để cải thiện tình hình nha:\n\n– Uống đủ nước mỗi ngày để giữ ẩm cho da, bạn thử xem sao nhé! 💧\n\n– Hãy nhớ dưỡng ẩm cho da sau khi tắm, lúc này da còn ẩm sẽ thẩm thấu tốt hơn.\n\n– Nếu muốn, bạn có thể sử dụng các loại dầu tự nhiên như dầu dừa hoặc dầu oliu để xoa lên da, giúp da mềm mại và giữ ẩm.\n\nCó một vài sản phẩm nhẹ nhàng có thể hỗ trợ đúng với vấn đề bạn đang gặp. Muốn mình giới thiệu thử không? 🌿', 'bot', '2025-07-07 09:49:47'),
(353, 4, NULL, 'product_query', 'u ban giup minh gioi thieu vai san pham di', 'user', '2025-07-07 09:50:24'),
(354, 4, NULL, 'product_query', '🛍️ Dưới đây là vài sản phẩm mà chúng tôi đang cung cấp:\n\n- Các loại thuốc: thuốc giảm đau, thuốc kháng sinh, vitamin.\n- Thiết bị y tế: máy đo huyết áp, máy đo đường huyết.\n- Sản phẩm chăm sóc sức khỏe: thực phẩm chức năng, sản phẩm chăm sóc da.\n\nNếu bạn cần tìm hiểu thêm chi tiết về sản phẩm nào, hãy cho mình biết nha!', 'bot', '2025-07-07 09:50:24'),
(355, 4, NULL, 'general_chat', 'dao nay da minh co vai cho no bi kho qua khong biet co cach nao cai thien khong ta? no bi bong da lên lun', 'user', '2025-07-07 11:04:56'),
(356, 4, NULL, 'general_chat', 'Um, mình hiểu rồi nè. Nếu bạn cảm thấy khó chịu hay bị khô da do thời tiết hoặc những nguyên nhân khác, có thể thử một số cách đơn giản như:\n\n- Sử dụng kem dưỡng ẩm cho da.\n- Uống đủ nước để giữ ẩm cho cơ thể.\n- Giảm thiểu việc tắm nước nóng, vì có thể làm mất độ ẩm tự nhiên của da.\n\nNếu tình trạng không cải thiện, bạn có thể muốn tham khảo ý kiến của bác sĩ. Cảm ơn bạn đã chia sẻ nghen!', 'bot', '2025-07-07 11:04:57'),
(357, 4, NULL, 'general_chat', 'dao nay da minh co vai cho no bi kho qua khong biet co cach nao cai thien khong ta? no bi bong da lên lu', 'user', '2025-07-07 11:07:59'),
(358, 4, NULL, 'general_chat', 'Um, mình hiểu rồi nè. Khi bị khô da, bạn có thể thử giữ ẩm cho da bằng cách sử dụng kem dưỡng ẩm và uống đủ nước. Nếu tình trạng này vẫn tiếp tục hoặc bạn cảm thấy khó chịu, có lẽ nên tham khảo ý kiến bác sĩ để được tư vấn thêm. Bạn có muốn chia sẻ thêm về cảm giác của mình không?', 'bot', '2025-07-07 11:07:59'),
(359, 4, NULL, 'general_chat', 'dao nay da minh co vai cho no bi kho qua khong biet co cach nao cai thien khong ta? no bi bong da lên lu', 'user', '2025-07-07 11:10:46'),
(360, 4, NULL, 'general_chat', 'Um, mình hiểu rồi nè. Việc bị khô da có thể khiến bạn cảm thấy không thoải mái. Bạn có thể thử những cách đơn giản như:\n\n- Sử dụng kem dưỡng ẩm hàng ngày.\n- Uống đủ nước để giữ ẩm cho da.\n- Tránh tắm nước quá nóng hoặc lâu.\n- Sử dụng xà phòng nhẹ, không gây kích ứng.\n\nCảm ơn bạn đã chia sẻ nghen! Nếu bạn muốn biết thêm chi tiết nào khác, hãy cho mình biết nhé!', 'bot', '2025-07-07 11:10:46'),
(361, 4, NULL, 'sql_query', 'lay danh sach san pham', 'user', '2025-07-07 11:33:49'),
(362, 4, NULL, 'sql_query', '{\n  \"natural_text\": \"📦 Đây là danh sách sản phẩm hiện có.\",\n  \"sql_query\": \"SELECT product_id AS \'Mã sản phẩm\', name AS \'Tên sản phẩm\', price AS \'Giá\', stock AS \'Số lượng\' FROM products WHERE is_active = 1\"\n}', 'bot', '2025-07-07 11:33:49'),
(363, 4, NULL, 'sql_query', 'lay danh sach san pham', 'user', '2025-07-07 13:40:29'),
(364, 4, NULL, 'sql_query', '{\n  \"natural_text\": \"📦 Đây là danh sách sản phẩm hiện có.\",\n  \"sql_query\": \"SELECT product_id AS \'Mã sản phẩm\', name AS \'Tên sản phẩm\', price AS \'Giá\', stock AS \'Số lượng tồn kho\' FROM products WHERE is_active = 1\"\n}', 'bot', '2025-07-07 13:40:30'),
(365, 4, NULL, 'sql_query', 'lay danh sach san pham', 'user', '2025-07-07 13:40:30'),
(366, 4, NULL, 'sql_query', '📦 Đây là danh sách sản phẩm hiện có.', 'bot', '2025-07-07 13:40:30'),
(367, 4, NULL, 'sql_query', 'lay danh sach san pham', 'user', '2025-07-07 13:44:09'),
(368, 4, NULL, 'sql_query', '{\n  \"natural_text\": \"📦 Dưới đây là danh sách các sản phẩm hiện có.\",\n  \"sql_query\": \"SELECT name AS \'Tên sản phẩm\', price AS \'Giá\' FROM products WHERE is_active = 1\"\n}', 'bot', '2025-07-07 13:44:09'),
(369, 4, NULL, 'sql_query', 'lay danh sach san pham', 'user', '2025-07-07 13:44:09'),
(370, 4, NULL, 'sql_query', '📦 Dưới đây là danh sách các sản phẩm hiện có.', 'bot', '2025-07-07 13:44:10'),
(371, 4, NULL, 'sql_query', 'lay danh sach san pham', 'user', '2025-07-07 13:50:17'),
(372, 4, NULL, 'sql_query', '{\n  \"natural_text\": \"📦 Dưới đây là danh sách các sản phẩm hiện có.\",\n  \"sql_query\": \"SELECT product_id AS \'Mã sản phẩm\', name AS \'Tên sản phẩm\', price AS \'Giá\', stock AS \'Số lượng tồn kho\' FROM products WHERE is_active = 1\"\n}', 'bot', '2025-07-07 13:50:17'),
(373, 4, NULL, 'sql_query', 'lay danh sach san pham', 'user', '2025-07-07 13:50:17'),
(374, 4, NULL, 'sql_query', '📦 Dưới đây là danh sách các sản phẩm hiện có.', 'bot', '2025-07-07 13:50:17'),
(375, 4, NULL, 'suggest_product', 'Mình cảm thấy hơi sốt nhẹ và đau đầu, có cách nào giúp mình dễ chịu hơn không?', 'user', '2025-07-07 13:58:31'),
(376, 4, NULL, 'suggest_product', 'Mình chưa xác định được sản phẩm phù hợp lúc này.', 'bot', '2025-07-07 13:58:31'),
(377, 4, NULL, 'suggest_product', 'Mình cảm thấy hơi sốt nhẹ và đau đầu, có cách nào giúp mình dễ chịu hơn không?', 'user', '2025-07-07 14:02:14'),
(378, 4, NULL, 'suggest_product', 'Mình chưa xác định được sản phẩm phù hợp lúc này.', 'bot', '2025-07-07 14:02:15'),
(379, 4, NULL, 'suggest_product', 'Mình cảm thấy hơi sốt nhẹ và đau đầu, có cách nào giúp mình dễ chịu hơn không?', 'user', '2025-07-07 14:09:28'),
(380, 4, NULL, 'suggest_product', 'Mình chưa xác định được sản phẩm phù hợp lúc này.', 'bot', '2025-07-07 14:09:28'),
(381, 4, NULL, 'suggest_product', 'Mình cảm thấy hơi sốt nhẹ và đau đầu, có cách nào giúp mình dễ chịu hơn không?', 'user', '2025-07-07 14:13:45'),
(382, 4, NULL, 'suggest_product', 'Mình chưa xác định được sản phẩm phù hợp lúc này.', 'bot', '2025-07-07 14:13:45'),
(383, 4, NULL, 'suggest_product', 'Mình cảm thấy hơi sốt nhẹ và đau đầu, có cách nào giúp mình dễ chịu hơn không?', 'user', '2025-07-07 14:20:56'),
(384, 4, NULL, 'suggest_product', 'Mình chưa xác định được sản phẩm phù hợp lúc này.', 'bot', '2025-07-07 14:20:56'),
(385, 4, NULL, 'suggest_product', 'Mình cảm thấy hơi sốt nhẹ và đau đầu, có cách nào giúp mình dễ chịu hơn không?', 'user', '2025-07-07 14:21:58'),
(386, 4, NULL, 'suggest_product', 'Mình chưa xác định được sản phẩm phù hợp lúc này.', 'bot', '2025-07-07 14:21:58'),
(387, 4, NULL, 'suggest_product', 'Mình cảm thấy hơi sốt nhẹ và đau đầu, có cách nào giúp mình dễ chịu hơn không?', 'user', '2025-07-07 14:22:22'),
(388, 4, NULL, 'suggest_product', 'Mình chưa xác định được sản phẩm phù hợp lúc này.', 'bot', '2025-07-07 14:22:22'),
(389, 4, NULL, 'suggest_product', 'Mình cảm thấy hơi sốt nhẹ và đau đầu, có cách nào giúp mình dễ chịu hơn không?', 'user', '2025-07-07 14:24:00'),
(390, 4, NULL, 'suggest_product', '📦 Đây là vài sản phẩm giúp bạn cảm thấy dễ chịu hơn.', 'bot', '2025-07-07 14:24:00'),
(391, 4, NULL, 'sql_query', 'lay danh sach san pham', 'user', '2025-07-07 14:30:05'),
(392, 4, NULL, 'sql_query', '{\n  \"natural_text\": \"📦 Dưới đây là danh sách các sản phẩm hiện có.\",\n  \"sql_query\": \"SELECT product_id AS \'Mã sản phẩm\', name AS \'Tên sản phẩm\', price AS \'Giá\', stock AS \'Số lượng\' FROM products WHERE is_active = 1\"\n}', 'bot', '2025-07-07 14:30:05'),
(393, 4, NULL, 'sql_query', 'lay danh sach san pham', 'user', '2025-07-07 14:30:05'),
(394, 4, NULL, 'sql_query', '📦 Dưới đây là danh sách các sản phẩm hiện có.', 'bot', '2025-07-07 14:30:05'),
(395, 4, NULL, 'suggest_product', 'Mình cảm thấy hơi sốt nhẹ và đau đầu, có cách nào giúp mình dễ chịu hơn không?', 'user', '2025-07-07 14:31:44'),
(396, 4, NULL, 'suggest_product', '📦 Đây là vài sản phẩm giúp bạn cảm thấy dễ chịu hơn.', 'bot', '2025-07-07 14:31:44'),
(397, 4, NULL, 'suggest_product', 'Mình cảm thấy hơi sốt nhẹ và đau đầu, có cách nào giúp mình dễ chịu hơn không?', 'user', '2025-07-07 14:34:20'),
(398, 4, NULL, 'suggest_product', '📦 Đây là vài sản phẩm giúp bạn cảm thấy dễ chịu hơn.', 'bot', '2025-07-07 14:34:20'),
(399, 4, NULL, 'suggest_product', 'Mình cảm thấy hơi sốt nhẹ và đau đầu, có cách nào giúp mình dễ chịu hơn không?', 'user', '2025-07-07 14:39:00'),
(400, 4, NULL, 'suggest_product', '📦 Đây là vài sản phẩm giúp bạn cảm thấy dễ chịu hơn.', 'bot', '2025-07-07 14:39:01'),
(401, 4, NULL, 'suggest_product', 'Mình cảm thấy hơi sốt nhẹ và đau đầu, có cách nào giúp mình dễ chịu hơn không?', 'user', '2025-07-07 14:41:53'),
(402, 4, NULL, 'suggest_product', '📦 Đây là vài sản phẩm giúp bạn cảm thấy dễ chịu hơn.', 'bot', '2025-07-07 14:41:53'),
(403, 4, NULL, 'suggest_product', 'Mình cảm thấy hơi sốt nhẹ và đau đầu, có cách nào giúp mình dễ chịu hơn không?', 'user', '2025-07-07 14:43:57'),
(404, 4, NULL, 'suggest_product', '📦 Đây là vài sản phẩm giúp bạn cảm thấy dễ chịu hơn.', 'bot', '2025-07-07 14:43:57'),
(405, 4, NULL, 'suggest_product', 'Mình cảm thấy hơi sốt nhẹ và đau đầu, có cách nào giúp mình dễ chịu hơn không?', 'user', '2025-07-07 14:46:02'),
(406, 4, NULL, 'suggest_product', '📦 Đây là vài sản phẩm giúp bạn cảm thấy dễ chịu hơn.', 'bot', '2025-07-07 14:46:02'),
(407, 4, NULL, 'suggest_product', 'Mình cảm thấy hơi sốt nhẹ và đau đầu, có cách nào giúp mình dễ chịu hơn không?', 'user', '2025-07-07 14:53:29'),
(408, 4, NULL, 'suggest_product', '📦 Đây là vài sản phẩm giúp bạn cảm thấy dễ chịu hơn.', 'bot', '2025-07-07 14:53:29'),
(409, 4, NULL, 'suggest_product', 'Mình cảm thấy hơi sốt nhẹ và đau đầu, có cách nào giúp mình dễ chịu hơn không?', 'user', '2025-07-07 14:53:29'),
(410, 4, NULL, 'suggest_product', '📦 Đây là vài sản phẩm giúp bạn cảm thấy dễ chịu hơn.', 'bot', '2025-07-07 14:53:29'),
(411, 4, NULL, 'suggest_product', 'Mình bị viêm họng mấy hôm nay, có thuốc nào kháng sinh nhẹ không?', 'user', '2025-07-07 15:03:25'),
(412, 4, NULL, 'suggest_product', '📦 Đây là vài sản phẩm giúp bạn cảm thấy dễ chịu hơn.', 'bot', '2025-07-07 15:03:26'),
(413, 4, NULL, 'suggest_product', 'Mình bị viêm họng mấy hôm nay, có thuốc nào kháng sinh nhẹ không?', 'user', '2025-07-07 15:03:26'),
(414, 4, NULL, 'suggest_product', '📦 Đây là vài sản phẩm giúp bạn cảm thấy dễ chịu hơn.', 'bot', '2025-07-07 15:03:26'),
(415, 4, NULL, 'suggest_product', 'Mình bị viêm họng mấy hôm nay, có thuốc nào kháng sinh nhẹ không?', 'user', '2025-07-07 15:05:44'),
(416, 4, NULL, 'suggest_product', '📦 Đây là vài sản phẩm kháng sinh nhẹ có thể giúp bạn với tình trạng viêm họng.', 'bot', '2025-07-07 15:05:44'),
(417, 4, NULL, 'suggest_product', 'Mình bị viêm họng mấy hôm nay, có thuốc nào kháng sinh nhẹ không?', 'user', '2025-07-07 15:05:44'),
(418, 4, NULL, 'suggest_product', '📦 Đây là vài sản phẩm kháng sinh nhẹ có thể giúp bạn với tình trạng viêm họng.', 'bot', '2025-07-07 15:05:44'),
(419, 4, NULL, 'suggest_product', 'Dạo gần đây da mình khô quá Có cách nào cải thiện không', 'user', '2025-07-07 15:11:22'),
(420, 4, NULL, 'suggest_product', '📦 Dưới đây là một số sản phẩm kháng sinh nhẹ có thể giúp bạn với tình trạng viêm họng.', 'bot', '2025-07-07 15:11:23'),
(421, 4, NULL, 'health_advice', 'Dạo gần đây da mình khô quá Có cách nào cải thiện không', 'user', '2025-07-07 15:17:35'),
(422, 4, NULL, 'health_advice', 'Nhiều khi da khô có thể do thời tiết hoặc thiếu nước đó bạn ơi. Mình có vài mẹo nhỏ thử xem sao nha:\n\n– Uống đủ nước mỗi ngày, giúp da giữ ẩm hơn. Nước lọc hay nước trái cây đều tốt cả 🍵  \n– Thử dùng kem dưỡng ẩm sau khi tắm, lúc da còn ẩm sẽ thấm nhanh hơn. Mình thấy dễ chịu hơn khi làm như vậy.  \n– Bạn cũng có thể dùng dầu dừa hoặc dầu ô liu thoa lên da, giúp cấp ẩm tự nhiên.  \n\nNếu bạn cần, mình cũng có thể gợi ý vài sản phẩm giúp bạn dễ chịu hơn với tình trạng này nha 🌿', 'bot', '2025-07-07 15:17:35'),
(423, 4, NULL, 'suggest_product', 'u goi y giup minh vai san pham di', 'user', '2025-07-07 15:17:55'),
(424, 4, NULL, 'suggest_product', '📦 Đây là một số sản phẩm kháng sinh nhẹ có thể giúp bạn với tình trạng viêm họng.', 'bot', '2025-07-07 15:17:55'),
(425, 4, NULL, 'health_advice', 'Dạo gần đây da mình khô quá Có cách nào cải thiện không', 'user', '2025-07-07 15:20:07'),
(426, 4, NULL, 'health_advice', '- Nhiều khi da khô là do mình không cung cấp đủ nước. Bạn nhớ uống đủ nước mỗi ngày nhé, khoảng 2 lít là tốt nhất 💧\n\n- Cứ sau khi tắm, mình khuyên bạn thoa kem dưỡng ẩm ngay, vì lúc đó da còn ẩm sẽ dễ thẩm thấu hơn. \n\n- Nếu có thể, thử dùng một số loại dầu tự nhiên như dầu dừa hoặc dầu ô liu, cũng giúp da mềm mại hơn đấy.\n\n- Còn một mẹo hay là thêm một chút độ ẩm vào không khí trong nhà bằng máy tạo độ ẩm hoặc đặt một chậu nước trong phòng nữa nha.\n\nNếu bạn cần, mình cũng có thể gợi ý vài sản phẩm giúp bạn dễ chịu hơn với tình trạng này nha 🌿', 'bot', '2025-07-07 15:20:08'),
(427, 4, NULL, 'suggest_product', 'u goi y giup minh vai san pham di', 'user', '2025-07-07 15:20:22'),
(428, 4, NULL, 'suggest_product', '📦 Đây là một số sản phẩm kháng sinh nhẹ có thể giúp bạn với tình trạng viêm họng.', 'bot', '2025-07-07 15:20:22'),
(429, 4, NULL, 'suggest_product', 'u goi y giup minh vai san pham di', 'user', '2025-07-07 15:20:22'),
(430, 4, NULL, 'suggest_product', '📦 Đây là một số sản phẩm kháng sinh nhẹ có thể giúp bạn với tình trạng viêm họng.', 'bot', '2025-07-07 15:20:22'),
(431, 4, NULL, 'health_advice', 'Dạo gần đây da mình khô quá Có cách nào cải thiện không', 'user', '2025-07-07 15:25:00'),
(432, 4, NULL, 'health_advice', 'Mình hiểu cảm giác da khô có thể khó chịu lắm. Thử xem những cách này có giúp gì cho bạn không nha:\n\n– Uống nước đủ, vì nước giúp da giữ độ ẩm tốt hơn. Cả ngày nhớ uống chút chút chứ đừng chờ tới khi khát nhé 💧 \n\n– Sau khi tắm, bạn có thể thoa kem dưỡng ẩm trong lúc da còn ẩm. Mình thấy da sẽ hút ẩm tốt hơn như vậy á. \n\n– Thử dùng máy tạo độ ẩm trong phòng nếu thời tiết khô, không khí sẽ ẩm hơn và dễ chịu hơn cho da.\n\n– Cuối cùng, có thể ăn thêm trái cây hoặc thực phẩm chứa nhiều omega-3 như cá, nó cũng giúp da mềm mại hơn.\n\nNếu bạn cần, mình có thể gợi ý vài sản phẩm giúp bạn dễ chịu hơn với tình trạng này nha 🌿', 'bot', '2025-07-07 15:25:00'),
(433, 4, NULL, 'suggest_product', 'u goi y giup minh vai san pham di', 'user', '2025-07-07 15:25:12'),
(434, 4, NULL, 'suggest_product', '📦 Đây là một số sản phẩm có thể giúp cải thiện tình trạng da khô của bạn.', 'bot', '2025-07-07 15:25:12'),
(435, 4, NULL, 'suggest_product', 'u goi y giup minh vai san pham di', 'user', '2025-07-07 15:25:12'),
(436, 4, NULL, 'suggest_product', '📦 Đây là một số sản phẩm có thể giúp cải thiện tình trạng da khô của bạn.', 'bot', '2025-07-07 15:25:12'),
(437, 4, NULL, 'suggest_product', 'Dạo gần đây da mình khô quá Có cách nào cải thiện không', 'user', '2025-07-07 15:29:52'),
(438, 4, NULL, 'suggest_product', '📦 Dưới đây là một số sản phẩm giúp cải thiện tình trạng da khô của bạn.', 'bot', '2025-07-07 15:29:52'),
(439, 4, NULL, 'suggest_product', 'Dạo gần đây da mình khô quá Có cách nào cải thiện không', 'user', '2025-07-07 15:29:52'),
(440, 4, NULL, 'suggest_product', '📦 Dưới đây là một số sản phẩm giúp cải thiện tình trạng da khô của bạn.', 'bot', '2025-07-07 15:29:52'),
(441, 4, NULL, 'suggest_product', 'Dạo gần đây da mình khô quá Có cách nào cải thiện không', 'user', '2025-07-07 15:30:06'),
(442, 4, NULL, 'suggest_product', '📦 Dưới đây là một số sản phẩm có thể giúp cải thiện tình trạng da khô của bạn.', 'bot', '2025-07-07 15:30:06'),
(443, 4, NULL, 'suggest_product', 'Dạo gần đây da mình khô quá Có cách nào cải thiện không', 'user', '2025-07-07 15:30:06'),
(444, 4, NULL, 'suggest_product', '📦 Dưới đây là một số sản phẩm có thể giúp cải thiện tình trạng da khô của bạn.', 'bot', '2025-07-07 15:30:06'),
(445, 4, NULL, 'health_advice', 'Dạo gần đây da mình khô quá Có cách nào cải thiện không', 'user', '2025-07-07 15:30:25'),
(446, 4, NULL, 'health_advice', '- Uống đủ nước mỗi ngày nha, nó giúp da mình được cấp ẩm từ bên trong đó 💧\n- Sau khi tắm, bạn thử thoa kem dưỡng ẩm hoặc dầu dừa lên da, lúc da còn ẩm sẽ thấm nhanh hơn.\n- Thỉnh thoảng dùng mặt nạ giấy hoặc mặt nạ thiên nhiên từ trái cây để cấp nước cho da cũng tốt lắm đó.\n\nNhiều khi da khô là do thời tiết nữa, bạn thử chăm sóc nhẹ nhàng xem sao nhé. Nếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp da bạn ẩm mịn hơn nè 🌿\n\n```json\n{\n  \"should_suggest_product\": false,\n  \"suggest_type\": \"wellness\",\n  \"suggest_product_target\": [\n    \"Dưỡng ẩm da\",\n    \"Giữ da mềm mịn\",\n    \"Cấp nước cho da\"\n  ]\n}\n```', 'bot', '2025-07-07 15:30:25'),
(447, 4, NULL, 'health_advice', 'Dạo gần đây da mình khô quá Có cách nào cải thiện không', 'user', '2025-07-07 15:31:24'),
(448, 4, NULL, 'health_advice', '- Uống đủ nước mỗi ngày nha, giúp da mình được cấp ẩm từ bên trong đó 💧  \n- Sau khi tắm, bạn thử thoa kem dưỡng ẩm hoặc dầu dừa lên da, lúc da còn ẩm sẽ thấm nhanh hơn.  \n- Thỉnh thoảng sử dụng mặt nạ giấy hoặc mặt nạ thiên nhiên từ trái cây cũng là cách hay để cấp nước cho da.\n\nNhiều khi da khô là do thời tiết nữa, bạn thử chăm sóc nhẹ nhàng xem sao nhé. Nếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp da bạn ẩm mịn hơn nè 🌿 \n\n```json\n{\n  \"should_suggest_product\": false,\n  \"suggest_type\": \"wellness\",\n  \"suggest_product_target\": [\n    \"Dưỡng ẩm da\",\n    \"Giữ da mềm mịn\",\n    \"Cấp nước cho da\"\n  ]\n}\n```', 'bot', '2025-07-07 15:31:24'),
(449, 4, NULL, 'health_advice', 'Dạo gần đây da mình khô quá Có cách nào cải thiện không', 'user', '2025-07-07 15:34:00'),
(450, 4, NULL, 'health_advice', '- Uống đủ nước mỗi ngày nha, giúp da mình được cấp ẩm từ bên trong đó 💧  \n- Sau khi tắm, bạn thử thoa kem dưỡng ẩm hoặc dầu dừa lên da, lúc da còn ẩm sẽ thấm nhanh hơn.  \n- Thỉnh thoảng sử dụng mặt nạ giấy hoặc mặt nạ thiên nhiên từ trái cây cũng là cách hay để cấp nước cho da.  \n\nNhiều khi da khô là do thời tiết nữa, bạn thử chăm sóc nhẹ nhàng xem sao nhé. Nếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp da bạn ẩm mịn hơn nè 🌿 \n\n```json\n{\n  \"should_suggest_product\": false,\n  \"suggest_type\": \"wellness\",\n  \"suggest_product_target\": [\n    \"Dưỡng ẩm da\",\n    \"Giữ da mềm mịn\",\n    \"Cấp nước cho da\"\n  ]\n}\n```', 'bot', '2025-07-07 15:34:00'),
(451, 4, NULL, 'health_advice', 'Dạo gần đây da mình khô quá Có cách nào cải thiện không', 'user', '2025-07-07 15:34:40'),
(452, 4, NULL, 'health_advice', '- Uống đủ nước mỗi ngày nha, giúp da mình được cấp ẩm từ bên trong đó 💧  \n- Sau khi tắm, thử thoa kem dưỡng ẩm hoặc dầu dừa lên da, lúc da còn ẩm sẽ thấm nhanh hơn.  \n- Nếu có thời gian, hãy sử dụng mặt nạ giấy hoặc mặt nạ thiên nhiên từ trái cây để cấp nước cho da.\n\nNhiều khi da khô là do thời tiết nữa, bạn thử chăm sóc nhẹ nhàng xem sao nhé. Nếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp da bạn ẩm mịn hơn nè 🌿\n\n```json\n{\n  \"should_suggest_product\": false,\n  \"suggest_type\": \"wellness\",\n  \"suggest_product_target\": [\n    \"Dưỡng ẩm da\",\n    \"Giữ da mềm mịn\",\n    \"Cấp nước cho da\"\n  ]\n}\n```', 'bot', '2025-07-07 15:34:40'),
(453, 4, NULL, 'health_advice', 'Dạo gần đây da mình khô quá Có cách nào cải thiện không', 'user', '2025-07-07 15:39:29'),
(454, 4, NULL, 'health_advice', '- Uống đủ nước mỗi ngày nha, giúp da mình được cấp ẩm từ bên trong đó 💧  \n- Sau khi tắm, thử thoa kem dưỡng ẩm hoặc dầu dừa lên da, lúc da còn ẩm sẽ thấm nhanh hơn.  \n- Nếu có thời gian, hãy sử dụng mặt nạ giấy hoặc mặt nạ thiên nhiên từ trái cây để cấp nước cho da.\n\nNhiều khi da khô cũng do thời tiết nữa, bạn thử chăm sóc nhẹ nhàng xem sao nhé. Nếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp da bạn ẩm mịn hơn nè 🌿\n\n```json\n\n```', 'bot', '2025-07-07 15:39:29'),
(455, 4, NULL, 'health_advice', 'Dạo gần đây da mình khô quá Có cách nào cải thiện không', 'user', '2025-07-07 15:42:54'),
(456, 4, NULL, 'health_advice', '- Uống đủ nước mỗi ngày nha, giúp da mình được cấp ẩm từ bên trong đó 💧  \n- Sau khi tắm, thử thoa kem dưỡng ẩm hoặc dầu dừa lên da, lúc da còn ẩm sẽ thấm nhanh hơn.  \n- Nếu có thời gian, hãy sử dụng mặt nạ giấy hoặc mặt nạ thiên nhiên từ trái cây để cấp nước cho da.  \nNhiều khi da khô cũng do thời tiết nữa, bạn thử chăm sóc nhẹ nhàng xem sao nhé. Nếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp da bạn ẩm mịn hơn nè 🌿\n\n```json\n\n```', 'bot', '2025-07-07 15:42:54'),
(457, 4, NULL, 'health_advice', 'Dạo gần đây da mình khô quá Có cách nào cải thiện không', 'user', '2025-07-07 15:55:01'),
(458, 4, NULL, 'health_advice', 'Bạn thử uống đủ nước mỗi ngày nha, giúp da mình được cấp ẩm từ bên trong đó 💧\nSau khi tắm, thoa kem dưỡng ẩm hoặc dầu dừa lên da lúc còn ẩm sẽ giúp da thấm tốt hơn.\nNếu có thời gian, dùng mặt nạ giấy hoặc mặt nạ thiên nhiên từ trái cây cũng rất tốt để cấp nước cho da.\nNhiều khi da khô cũng do thời tiết nữa, bạn thử chăm sóc nhẹ nhàng xem sao nhé. Nếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp da bạn ẩm mịn hơn nè 🌿', 'bot', '2025-07-07 15:55:01'),
(459, 4, NULL, 'suggest_product', 'u ban goi y giup minh di', 'user', '2025-07-07 15:55:33'),
(460, 4, NULL, 'suggest_product', '🌿 Dưới đây là một số sản phẩm giúp bạn cấp ẩm và giữ da mềm mịn.', 'bot', '2025-07-07 15:55:33'),
(461, 4, NULL, 'health_advice', 'Dạo gần đây da mình khô quá Có cách nào cải thiện không', 'user', '2025-07-07 16:01:22'),
(462, 4, NULL, 'health_advice', 'Mình thấy khô da nhiều khi do thời tiết hoặc không đủ nước. Bạn thử uống đủ nước mỗi ngày nha, điều này giúp da mình căng mịn hơn. 🌿 Bạn cũng có thể dùng kem dưỡng ẩm sau khi tắm, lúc da còn ẩm để khóa ẩm lại. Nếu có thể, hãy thử tắm bằng nước ấm thay vì nước nóng quá, sẽ tốt hơn cho da đó. Nhiều khi da cũng cần được thư giãn, bạn có thể dùng mặt nạ tự nhiên để chăm sóc nữa. Thử xem sao nha!', 'bot', '2025-07-07 16:01:22'),
(463, 4, NULL, 'health_advice', 'Dạo gần đây da mình khô quá Có cách nào cải thiện không', 'user', '2025-07-07 16:06:42'),
(464, 4, NULL, 'health_advice', 'Nhiều khi da khô là do thời tiết hoặc thiếu nước. Bạn thử uống đủ nước trong ngày nha, điều này giúp da mình mềm mại hơn. 🌿 Ngoài ra, sau khi tắm, bạn có thể thoa kem dưỡng ẩm khi da còn ẩm, sẽ khóa ẩm tốt hơn. Thử tắm bằng nước ấm thay vì nước nóng quá cũng có thể giúp da đỡ khô hơn. Mình thấy dễ chịu hơn khi dùng mặt nạ tự nhiên để chăm sóc da nữa đó. Thử xem sao nha!', 'bot', '2025-07-07 16:06:42'),
(465, 4, NULL, 'health_advice', '- Keep the message warm and friendly, but short and easy to read', 'user', '2025-07-07 16:09:35'),
(466, 4, NULL, 'health_advice', 'Mình hiểu, đôi khi chỉ cần một chút thay đổi nhỏ cũng giúp nhiều lắm. Bạn có thể thử uống nước đều đặn trong ngày để giữ ẩm cho da. 🌿 Thêm vào đó, hãy thoa kem dưỡng ẩm ngay sau khi tắm khi da còn ẩm để khóa ẩm tốt hơn. Thử tắm bằng nước ấm thay vì nước nóng quá cũng là một ý hay đó. Nhiều khi, một chút mặt nạ tự nhiên cũng giúp da mềm mại hơn. Thử xem sao nha!', 'bot', '2025-07-07 16:09:35'),
(467, 4, NULL, 'health_advice', 'Dạo gần đây da mình khô quá Có cách nào cải thiện không', 'user', '2025-07-07 16:11:58'),
(468, 4, NULL, 'health_advice', 'Da khô nhiều khi do không đủ độ ẩm. Bạn thử uống nhiều nước trong ngày nhé, sẽ giúp da mềm mại hơn. Ngoài ra, sau khi tắm, bạn có thể dưỡng ẩm ngay khi da còn ẩm để khóa lại độ ẩm. Còn một cách nữa là dùng máy phun sương trong phòng nữa, giúp không khí bớt khô đi đó 💧', 'bot', '2025-07-07 16:11:58'),
(469, 4, NULL, 'health_advice', 'Dạo gần đây da mình khô quá Có cách nào cải thiện không', 'user', '2025-07-07 16:16:01'),
(470, 4, NULL, 'health_advice', 'Da khô có thể do nhiều nguyên nhân, nhưng bạn thử một số cách này xem sao nha: \n– Uống đủ nước mỗi ngày để giữ ẩm từ bên trong nhé. \n– Sau khi tắm, bạn nhớ dưỡng ẩm ngay khi da còn ẩm để giữ nước lại. \n– Ngoài ra, bạn có thể thử dùng một chiếc máy phun sương trong phòng để không khí ẩm hơn, giúp da không bị khô nữa 💧', 'bot', '2025-07-07 16:16:01'),
(471, 4, NULL, 'health_advice', 'Dạo gần đây da mình khô quá Có cách nào cải thiện không', 'user', '2025-07-07 16:20:50'),
(472, 4, NULL, 'health_advice', 'Bạn thử làm một số điều sau để cải thiện tình trạng da khô nha: \n– Uống đủ nước mỗi ngày, điều này rất quan trọng để giữ ẩm cho da từ bên trong. \n– Sau khi tắm, nhớ dưỡng ẩm ngay khi da còn ẩm để khóa nước lại nhé. \n– Bạn cũng có thể thử dùng dầu dừa hoặc dầu ô liu để massage lên da, mình thấy dễ chịu hơn khi sử dụng những loại dầu tự nhiên này. \n– Cũng có thể thử đặt một chậu nước trong phòng để không khí ẩm hơn, giúp da không bị khô nữa 💧. Nếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha 🌿', 'bot', '2025-07-07 16:20:50'),
(473, 4, NULL, 'suggest_product', 'set `\"should_suggest_product\"` to true.', 'user', '2025-07-07 16:23:28'),
(474, 4, NULL, 'suggest_product', '📦 Dưới đây là một số sản phẩm giúp bạn giữ ẩm cho da nhé!', 'bot', '2025-07-07 16:23:28'),
(475, 4, NULL, 'suggest_product', 'set `\"should_suggest_product\"` to true.', 'user', '2025-07-07 16:23:28'),
(476, 4, NULL, 'suggest_product', '📦 Dưới đây là một số sản phẩm giúp bạn giữ ẩm cho da nhé!', 'bot', '2025-07-07 16:23:28'),
(477, 4, NULL, 'health_advice', 'Dạo gần đây da mình khô quá Có cách nào cải thiện không', 'user', '2025-07-07 16:23:58'),
(478, 4, NULL, 'health_advice', 'Mình thấy da khô có thể do nhiều yếu tố lắm. Bạn thử uống đủ nước mỗi ngày để giữ ẩm từ bên trong nha 💧\n– Sau khi tắm, bạn nên dưỡng ẩm ngay khi da còn ẩm để khóa nước lại.\n– Nếu có thể, bạn có thể dùng máy tạo độ ẩm trong phòng, đặc biệt khi ngủ, để không khí không quá khô.\n– Hãy thử chọn những sản phẩm chăm sóc da nhẹ nhàng và tự nhiên nha, cho da thư giãn hơn.', 'bot', '2025-07-07 16:23:58'),
(479, 4, NULL, 'suggest_product', 'co the goi y giup minh vai san pham ho tro ko?', 'user', '2025-07-07 16:24:19'),
(480, 4, NULL, 'suggest_product', '📦 Dưới đây là một số sản phẩm giúp giữ ẩm cho da của bạn!', 'bot', '2025-07-07 16:24:19'),
(481, 4, NULL, 'suggest_product', 'co the goi y giup minh vai san pham ho tro ko?', 'user', '2025-07-07 16:24:19'),
(482, 4, NULL, 'suggest_product', '📦 Dưới đây là một số sản phẩm giúp giữ ẩm cho da của bạn!', 'bot', '2025-07-07 16:24:19'),
(483, 4, NULL, 'suggest_product', 'co the goi y giup minh vai san pham ho tro ko?', 'user', '2025-07-07 16:27:22'),
(484, 4, NULL, 'suggest_product', '📦 Dưới đây là một số sản phẩm giúp dưỡng ẩm cho da của bạn!', 'bot', '2025-07-07 16:27:22'),
(485, 4, NULL, 'suggest_product', 'co the goi y giup minh vai san pham ho tro ko?', 'user', '2025-07-07 16:27:23'),
(486, 4, NULL, 'suggest_product', '📦 Dưới đây là một số sản phẩm giúp dưỡng ẩm cho da của bạn!', 'bot', '2025-07-07 16:27:23'),
(487, 4, NULL, 'health_advice', 'Dạo gần đây da mình khô quá Có cách nào cải thiện không', 'user', '2025-07-08 09:01:51'),
(488, 4, NULL, 'health_advice', 'Da khô đôi khi có thể do thời tiết hoặc thiếu nước đấy. Bạn thử uống đủ nước mỗi ngày nha, khoảng 2 lít nhé! 💧\n– Sau khi tắm, bạn có thể thoa kem dưỡng ẩm ngay khi da còn ẩm, điều này giúp giữ ẩm tốt hơn.\n– Nhiều khi cũng do không khí trong nhà quá khô, bạn có thể thử đặt một chậu nước trong phòng để làm ẩm không khí nữa.\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha 🌿', 'bot', '2025-07-08 09:01:52'),
(489, 4, NULL, 'health_advice', 'Dạo gần đây da mình khô quá Có cách nào cải thiện không', 'user', '2025-07-08 09:08:20'),
(490, 4, NULL, 'health_advice', 'Da khô có thể do nhiều nguyên nhân khác nhau, nhưng mình có vài gợi ý giúp bạn cải thiện tình trạng này nè. Bạn thử uống đủ nước mỗi ngày nha, khoảng 2 lít nhé! 💧\n– Sau khi tắm, thoa kem dưỡng ẩm ngay khi da còn ẩm để giúp giữ nước cho da tốt hơn.\n– Nếu không khí trong nhà khô quá, bạn có thể thử đặt một chậu nước trong phòng để làm ẩm không khí nữa.\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha 🌿', 'bot', '2025-07-08 09:08:20'),
(491, 4, NULL, 'suggest_product', 'u goi y giup minh di', 'user', '2025-07-08 09:08:39'),
(492, 4, NULL, 'suggest_product', '', 'bot', '2025-07-08 09:08:39'),
(493, 4, NULL, 'health_advice', 'Dạo gần đây da mình khô quá Có cách nào cải thiện không', 'user', '2025-07-08 09:12:38'),
(494, 4, NULL, 'health_advice', 'Khi da khô, bạn có thể thử một số cách sau để cải thiện nhé:\n– Uống đủ nước trong ngày để giữ cho da luôn đủ ẩm 💧\n– Dưỡng ẩm ngay sau khi tắm, lúc da còn ẩm, như vậy sẽ giúp khóa ẩm tốt hơn.\n– Thử dùng máy tạo độ ẩm trong phòng nếu không khí khô.\nNhiều khi cũng do thời tiết nữa, bạn thử xem sao nha.\n\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha 🌿', 'bot', '2025-07-08 09:12:38'),
(495, 4, NULL, 'health_advice', 'Dạo gần đây da mình khô quá Có cách nào cải thiện không', 'user', '2025-07-08 09:12:51'),
(496, 4, NULL, 'health_advice', 'Khi da khô, bạn có thể thử một vài cách sau để cải thiện nhé: \n– Uống đủ nước mỗi ngày để giữ cho da luôn đủ ẩm 💧\n– Dưỡng ẩm ngay sau khi tắm, lúc da còn ẩm để khóa độ ẩm tốt hơn. \n– Thử dùng một chút dầu dừa hoặc dầu ôliu để massage nhẹ da, mình thấy rất dễ chịu. \nNhiều khi cũng do thời tiết khô hanh nữa, bạn thử xem sao nha.', 'bot', '2025-07-08 09:12:51'),
(497, 4, NULL, 'health_advice', 'u goi y giup minh di', 'user', '2025-07-08 09:13:16'),
(498, 4, NULL, 'health_advice', 'Bạn có thể thử một số cách sau để cải thiện tình trạng da khô nhé: \n– Duy trì thói quen uống nước đều đặn mỗi ngày, điều này giúp da bạn luôn được cấp ẩm 💧\n– Sau khi tắm, bạn nên dùng kem dưỡng ẩm ngay khi da còn ẩm để giữ nước lại cho da.\n– Thỉnh thoảng, bạn có thể massage nhẹ nhàng với dầu dừa hoặc dầu ôliu, mình thấy rất thư giãn và da sẽ mềm hơn. \nNhiều khi, thời tiết cũng ảnh hưởng đến da của mình đó, nên nhớ chăm sóc thật kỹ nha!', 'bot', '2025-07-08 09:13:16'),
(499, 4, NULL, 'health_advice', 'Dạo gần đây da mình khô quá Có cách nào cải thiện không', 'user', '2025-07-08 09:14:26');
INSERT INTO `chat_logs` (`chat_id`, `user_id`, `guest_id`, `intent`, `message`, `sender`, `sent_at`) VALUES
(500, 4, NULL, 'health_advice', 'Da khô có thể do nhiều lý do lắm, bạn thử xem những cách này nha: \n– Uống đủ nước mỗi ngày để giữ ẩm cho da nhé 💧 \n– Dưỡng ẩm thường xuyên, nhất là sau khi tắm, lúc da còn ẩm sẽ hiệu quả hơn. \n– Nếu có thể, bạn hãy dùng máy phun sương trong phòng để tạo độ ẩm cho không khí. \nNhiều khi chỉ cần một chút thay đổi cũng giúp da dễ chịu hơn đó! \nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha 🌿', 'bot', '2025-07-08 09:14:26'),
(501, 4, NULL, 'suggest_product', 'u goi y giup minh vai san pham di', 'user', '2025-07-08 09:14:40'),
(502, 4, NULL, 'suggest_product', '', 'bot', '2025-07-08 09:14:40'),
(503, 4, NULL, 'suggest_product', 'u goi y giup minh vai san pham di', 'user', '2025-07-08 09:15:51'),
(504, 4, NULL, 'suggest_product', '', 'bot', '2025-07-08 09:15:51'),
(505, 4, NULL, 'suggest_product', 'u goi y giup minh vai san pham di', 'user', '2025-07-08 09:20:32'),
(506, 4, NULL, 'suggest_product', '', 'bot', '2025-07-08 09:20:32'),
(507, 4, NULL, 'suggest_product', 'u goi y giup minh vai san pham di', 'user', '2025-07-08 09:23:16'),
(508, 4, NULL, 'suggest_product', '', 'bot', '2025-07-08 09:23:16'),
(509, 4, NULL, 'suggest_product', 'u goi y giup minh vai san pham di', 'user', '2025-07-08 09:23:58'),
(510, 4, NULL, 'suggest_product', '', 'bot', '2025-07-08 09:23:58'),
(511, 4, NULL, 'suggest_product', 'u goi y giup minh vai san pham di', 'user', '2025-07-08 09:27:45'),
(512, 4, NULL, 'suggest_product', '', 'bot', '2025-07-08 09:27:45'),
(513, 4, NULL, 'suggest_product', 'u goi y giup minh vai san pham di', 'user', '2025-07-08 09:28:51'),
(514, 4, NULL, 'suggest_product', '🧴 *Kem dưỡng ẩm da nhạy cảm*\nPhục hồi và giữ ẩm cho da khô, kích ứng....\n👉 [Xem chi tiết tại đây](https://demo.site.vn/products/10)', 'bot', '2025-07-08 09:28:51'),
(515, 4, NULL, 'suggest_product', 'u goi y giup minh vai san pham di', 'user', '2025-07-08 09:42:26'),
(516, 4, NULL, 'suggest_product', '📌 *Sản phẩm 1*\n🧴 *Kem dưỡng ẩm da nhạy cảm*\nPhục hồi và giữ ẩm cho da khô, kích ứng....\n👉 [Xem chi tiết tại đây](https://demo.site.vn/products/10)\n\n', 'bot', '2025-07-08 09:42:26'),
(517, 4, NULL, 'health_advice', 'Dạo gần đây da mình khô quá Có cách nào cải thiện không', 'user', '2025-07-08 10:01:05'),
(518, 4, NULL, 'health_advice', 'Bạn thử uống đủ nước mỗi ngày nha, nhiều khi da khô cũng do cơ thể thiếu nước đó. Ngoài ra, bạn có thể dưỡng ẩm thường xuyên, nhất là sau khi tắm, để giữ ẩm cho da. Một chút dầu dừa hay dầu olive cũng có thể giúp da mềm mại hơn. Nếu có thời gian, bạn hãy thử đắp mặt nạ từ thiên nhiên như mật ong hay nha đam nữa nha 🌿\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha 🌿', 'bot', '2025-07-08 10:01:05'),
(519, 4, NULL, 'suggest_product', 'um goi y cho minh vai san pham di', 'user', '2025-07-08 10:01:19'),
(520, 4, NULL, 'suggest_product', '📌 *Sản phẩm 1*\n🧴 *Kem dưỡng ẩm da nhạy cảm*\nPhục hồi và giữ ẩm cho da khô, kích ứng....\n👉 [Xem chi tiết tại đây](https://demo.site.vn/products/10)\n\n', 'bot', '2025-07-08 10:01:19'),
(521, 4, NULL, 'suggest_product', 'um goi y cho minh vai san pham di', 'user', '2025-07-08 10:06:08'),
(522, 4, NULL, 'suggest_product', '📌 *Sản phẩm 1*\n🧴 *Kem dưỡng ẩm da nhạy cảm*\nPhục hồi và giữ ẩm cho da khô, kích ứng....\n👉 [Xem chi tiết tại đây](https://demo.site.vn/products/10)\n\n', 'bot', '2025-07-08 10:06:08'),
(523, 4, NULL, 'suggest_product', 'um goi y cho minh vai san pham di', 'user', '2025-07-08 10:11:44'),
(524, 4, NULL, 'suggest_product', '📌 *Sản phẩm 1*\n🧴 *Kem dưỡng ẩm da nhạy cảm*\nPhục hồi và giữ ẩm cho da khô, kích ứng....\n👉 [Xem chi tiết tại đây](https://demo.site.vn/products/10)\n\n', 'bot', '2025-07-08 10:11:44'),
(525, 4, NULL, 'suggest_product', 'um goi y cho minh vai san pham di', 'user', '2025-07-08 10:14:37'),
(526, 4, NULL, 'suggest_product', '📌 *Sản phẩm 1*\nDa bạn hay khô phải không? Mình có bí quyết nhỏ nè 💧 Kem dưỡng ẩm da nhạy cảm là một lựa chọn tuyệt vời để bảo vệ và giữ ẩm cho làn da của bạn. Sản phẩm này rất lý tưởng trong việc phục hồi sức sống cho làn da khô và kích ứng, giúp bạn cảm thấy dễ chịu hơn mỗi ngày. Hãy thử sử dụng sau khi rửa mặt để nuôi dưỡng da một cách tốt nhất nhé!\n\n👉 [Xem chi tiết tại đây](https://demo.site.vn/products/10)\n\n', 'bot', '2025-07-08 10:14:37'),
(527, 4, NULL, 'health_advice', 'minh cam thay nhuc dau va chong mat qua', 'user', '2025-07-08 10:16:03'),
(528, 4, NULL, 'health_advice', 'Khi thấy nhức đầu và chóng mặt, bạn có thể thử những cách nhẹ nhàng sau đây: \n– Uống nhiều nước để giữ cơ thể luôn đủ nước nha 💧\n– Nghỉ ngơi một chút ở nơi yên tĩnh, có thể nhắm mắt và hít thở sâu để thư giãn.\n– Nếu có thể, bạn thử chườm lạnh lên trán một chút, nhiều khi thấy dễ chịu hơn đó.\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha 🌿', 'bot', '2025-07-08 10:16:03'),
(529, 4, NULL, 'symptom_query', 'minh cam thay nhuc dau va chong mat qua', 'user', '2025-07-08 10:19:44'),
(530, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **đau đầu** của bạn nè. Cảm giác đó thường kéo dài bao lâu mỗi lần bạn gặp vậy?', 'bot', '2025-07-08 10:19:44'),
(531, 4, NULL, 'symptom_query', 'lut minh vua ngu day thi da co cam giac dau dau roi no keo dai tam 5-10p gi do', 'user', '2025-07-08 10:20:29'),
(532, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có cảm giác **chóng mặt** khi đứng dậy hoặc thay đổi tư thế không nha? Hoặc có khi nào cảm giác này kéo dài lâu hơn một chút không?', 'bot', '2025-07-08 10:20:30'),
(533, 4, NULL, 'symptom_query', 'um minh bi chong mat khi dung day', 'user', '2025-07-08 10:21:07'),
(534, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có cảm giác như mệt mỏi, đổ mồ hôi nhiều hay tim đập nhanh không khi bị **chóng mặt** ha? Điều đó có thể giúp mình hiểu rõ hơn về tình trạng của bạn.', 'bot', '2025-07-08 10:21:07'),
(535, 4, NULL, 'symptom_query', 'co lut do tim minh cung dap nhanh nua', 'user', '2025-07-08 10:21:40'),
(536, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác tim đập nhanh của bạn nè. Bạn có thấy tình trạng này xảy ra khi bạn đang nghỉ ngơi hay lúc hoạt động nhiều, như khi đứng dậy hoặc đi lại không?', 'bot', '2025-07-08 10:21:40'),
(537, 4, NULL, 'health_advice', 'lut hoat dong nhieu', 'user', '2025-07-08 10:23:19'),
(538, 4, NULL, 'health_advice', 'Khi bạn hoạt động nhiều, có thể cơ thể cần thời gian để thích nghi. Bạn thử nghỉ ngơi một chút, hít thở sâu và uống nước thường xuyên nha. Thêm vào đó, nếu thấy thoải mái, bạn có thể thử thư giãn với một chút nhạc nhẹ hoặc tắm nước ấm sau khi hoạt động. Điều này có thể giúp tim bạn trở lại nhịp bình thường hơn đó! \nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha 🌿', 'bot', '2025-07-08 10:23:19'),
(539, 4, NULL, 'symptom_query', 'minh cam thay nhuc dau va chong mat qua', 'user', '2025-07-08 10:26:36'),
(540, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có thấy cảm giác **đau đầu** này thường xuyên không, và nó kéo dài bao lâu mỗi lần bạn gặp phải vậy?', 'bot', '2025-07-08 10:26:37'),
(541, 4, NULL, 'symptom_query', 'khi minh vua ngu day thi da dau dau roi', 'user', '2025-07-08 10:26:58'),
(542, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **chóng mặt** nè. Bạn có thấy tình trạng này xảy ra khi nào, lúc đứng lên hay đang ngồi xuống thì dễ bị hơn không ha?', 'bot', '2025-07-08 10:26:58'),
(543, 4, NULL, 'symptom_query', 'khi dang ngoi lau thi cam thay chong mat', 'user', '2025-07-08 10:27:16'),
(544, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có cảm giác nào khác nữa không khi bị **chóng mặt** như mệt mỏi, đổ mồ hôi hay tim đập nhanh không nha? 😊', 'bot', '2025-07-08 10:27:16'),
(545, 4, NULL, 'symptom_query', 'co do mo hoi va cam giac met moi nua', 'user', '2025-07-08 10:27:37'),
(546, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **mệt mỏi** của bạn nè. Bạn có thấy tình trạng này kéo dài lâu không hay chỉ xảy ra trong một khoảng thời gian ngắn sau khi bạn hoạt động nhiều?', 'bot', '2025-07-08 10:27:37'),
(547, 4, NULL, 'general_chat', 'minh cung ko ro', 'user', '2025-07-08 10:28:16'),
(548, 4, NULL, 'general_chat', 'Um, mình hiểu rồi nè. Nếu bạn cần nói về điều gì khác hoặc có câu hỏi gì, cứ chia sẻ nhé!', 'bot', '2025-07-08 10:28:17'),
(549, 4, NULL, 'symptom_query', 'minh cam thay nhuc dau va chong mat qua', 'user', '2025-07-08 10:29:43'),
(550, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **đau đầu** nè, bạn thường cảm thấy nó kéo dài bao lâu mỗi lần vậy?', 'bot', '2025-07-08 10:29:43'),
(551, 4, NULL, 'symptom_query', 'tam 5-10p gi do khi minh vua ngu day', 'user', '2025-07-08 10:30:08'),
(552, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **chóng mặt** nè, bạn thường gặp tình trạng đó khi nào ha? Có hay xảy ra lúc bạn vừa đứng lên hay lúc đang ngồi không?', 'bot', '2025-07-08 10:30:08'),
(553, 4, NULL, 'symptom_query', 'lut minh ngoi lau thi bi chong mat', 'user', '2025-07-08 10:31:30'),
(554, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có thêm cảm giác nào khác nữa không, như mệt mỏi, tức ngực hay tim đập nhanh không nhỉ? 😊', 'bot', '2025-07-08 10:31:30'),
(555, 4, NULL, 'symptom_query', 'minh co cam giac tim dap nhanh nua', 'user', '2025-07-08 10:31:51'),
(556, 4, NULL, 'symptom_query', 'Cảm giác **tim đập nhanh** của bạn thường xảy ra khi nào? Có khi nào bạn cảm thấy nó đi kèm với sự hồi hộp hay căng thẳng không nhỉ?', 'bot', '2025-07-08 10:31:51'),
(557, 4, NULL, 'health_advice', 'um khi minh cam thay cang thang thi tim dap nhanh', 'user', '2025-07-08 10:32:19'),
(558, 4, NULL, 'health_advice', 'Nhiều khi khi căng thẳng, cơ thể phản ứng lại bằng cách tim đập nhanh đó. Bạn thử hít thở sâu vài lần, mỗi lần hít vào thì đếm đến 4, giữ hơi trong 4 giây rồi thở ra nhé. Ngoài ra, đi dạo một chút ngoài trời hoặc nghe nhạc thư giãn cũng có thể giúp bạn cảm thấy dễ chịu hơn. Nếu có thể, hãy dành chút thời gian cho bản thân để thư giãn trước khi ngủ nữa nha! 😌\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha 🌿', 'bot', '2025-07-08 10:32:19'),
(559, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-08 10:33:15'),
(560, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **đau đầu** của bạn nè. Bạn thấy cảm giác này thường kéo dài bao lâu mỗi lần gặp phải vậy?', 'bot', '2025-07-08 10:33:15'),
(561, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-08 10:33:30'),
(562, 4, NULL, 'symptom_query', 'Khi bạn cảm thấy **chóng mặt**, có khi nào cảm giác này xảy ra ngay khi bạn đứng lên hoặc thay đổi vị trí không nhỉ?', 'bot', '2025-07-08 10:33:30'),
(563, 4, NULL, 'symptom_query', 'minh cung khong ro no tu nhien chong mat thoi', 'user', '2025-07-08 10:33:46'),
(564, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **buồn nôn** của bạn nè. Bạn có thấy cảm giác này thường kéo dài bao lâu mỗi lần gặp phải, hay có khi nào bạn cảm thấy dễ chịu hơn sau khi nghỉ ngơi không ha?', 'bot', '2025-07-08 10:33:46'),
(565, 4, NULL, 'symptom_query', 'khi minh ngui thay 1 mui gi do kha nang thi co cam giac buon non', 'user', '2025-07-08 10:34:00'),
(566, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có thêm cảm giác nào khác nữa không, như là tức ngực hay tim đập nhanh không nhỉ? Những cảm giác đó có thể giúp mình hiểu rõ hơn về tình trạng của bạn.', 'bot', '2025-07-08 10:34:00'),
(567, 4, NULL, 'symptom_query', 'khong co', 'user', '2025-07-08 10:34:23'),
(568, 4, NULL, 'symptom_query', 'Bạn có thể mô tả rõ hơn bạn cảm thấy gì không?', 'bot', '2025-07-08 10:34:23'),
(569, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-08 11:11:41'),
(570, 4, NULL, 'symptom_query', 'Cảm giác **đau đầu** của bạn có thường kéo dài bao lâu mỗi lần bạn gặp phải không ha?', 'bot', '2025-07-08 11:11:41'),
(571, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-08 11:11:56'),
(572, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **chóng mặt** của bạn nè, có khi nào bạn cảm thấy nó xảy ra nhiều hơn khi bạn đứng lên hay thay đổi tư thế không ha?', 'bot', '2025-07-08 11:11:56'),
(573, 4, NULL, 'symptom_query', 'minh cung khong ro no tu nhien chong mat thoi', 'user', '2025-07-08 11:12:24'),
(574, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết cảm giác **buồn nôn** của bạn có thường đi kèm với cảm giác mệt mỏi hay không ha? Hoặc có khi nào bạn cảm thấy nó kéo dài lâu hay chỉ thoáng qua thôi?', 'bot', '2025-07-08 11:12:24'),
(575, 4, NULL, 'symptom_query', 'khi minh ngui thay 1 mui gi do kha nang thi co cam giac buon non', 'user', '2025-07-08 11:16:50'),
(576, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có thêm cảm giác nào khác không như **chán ăn**, **đau bụng**, hay có khi nào bạn thấy **nôn mửa** không nha? Điều đó có thể giúp mình hiểu rõ hơn về tình trạng của bạn.', 'bot', '2025-07-08 11:16:50'),
(577, 4, NULL, 'symptom_query', 'khong co', 'user', '2025-07-08 11:17:12'),
(578, 4, NULL, 'symptom_query', 'Bạn có thể mô tả rõ hơn bạn cảm thấy gì không?', 'bot', '2025-07-08 11:17:12'),
(579, 4, NULL, 'symptom_query', 'khong co', 'user', '2025-07-08 11:25:52'),
(580, 4, NULL, 'symptom_query', 'Bạn có thể mô tả rõ hơn bạn cảm thấy gì không?', 'bot', '2025-07-08 11:25:52'),
(581, 4, NULL, 'health_advice', 'khong co trieu chung gi nua', 'user', '2025-07-08 11:26:33'),
(582, 4, NULL, 'health_advice', 'Nhiều khi chỉ cần thư giãn một chút thôi cũng giúp ích nhiều lắm. Bạn thử dành thời gian đi dạo ngoài trời, hít thở không khí trong lành nha 🌿. Hoặc có thể thử ngồi thiền một vài phút mỗi ngày, để tâm trí được yên bình hơn. Nếu bạn thích, có thể thưởng thức một tách trà ấm để cảm thấy thoải mái hơn nữa.', 'bot', '2025-07-08 11:26:33'),
(583, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-08 11:26:58'),
(584, 4, NULL, 'symptom_query', 'Bạn có thể nói rõ hơn về tình trạng của mình không?', 'bot', '2025-07-08 11:26:58'),
(585, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-08 11:27:12'),
(586, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **đau đầu** nè. Bạn có thấy cơn đau này thường kéo dài bao lâu mỗi lần không? Hoặc có khi nào bạn cảm thấy đỡ hơn sau khi nghỉ ngơi không ha?', 'bot', '2025-07-08 11:27:12'),
(587, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-08 11:27:24'),
(588, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **chóng mặt** nè. Bạn có thấy cảm giác này thường xảy ra khi bạn đang ngồi hay khi vừa đứng lên không? 🌿', 'bot', '2025-07-08 11:27:24'),
(589, 4, NULL, 'health_advice', 'co le la do hôm nay mình chưa ăn gì từ sáng, chắc vì thế mà chóng mặt', 'user', '2025-07-08 11:27:36'),
(590, 4, NULL, 'health_advice', 'Mình thấy đó cũng có thể là lý do đấy. Bạn thử uống một chút nước hoặc ăn nhẹ gì đó nha, như trái cây hoặc bánh mì. Nhiều khi cơ thể cần chút năng lượng để hoạt động tốt hơn. Bạn cũng có thể ngồi nghỉ một chút ở nơi thoáng đãng để cảm thấy dễ chịu hơn. Nếu cần, hãy nhớ nghe theo cơ thể mình nhé! \nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn dễ chịu hơn nè 🌿', 'bot', '2025-07-08 11:27:36'),
(591, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-08 11:31:05'),
(592, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **đau đầu** của bạn nè. Cảm giác đó thường kéo dài bao lâu mỗi lần bạn gặp vậy?', 'bot', '2025-07-08 11:31:06'),
(593, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-08 11:31:18'),
(594, 4, NULL, 'symptom_query', 'Cảm giác **chóng mặt** của bạn có thường xảy ra khi bạn thay đổi tư thế, như lúc đứng lên từ vị trí ngồi không? Hoặc có khi nào cảm giác đó kéo dài hơn một chút không?', 'bot', '2025-07-08 11:31:18'),
(595, 4, NULL, 'symptom_query', 'minh cung khong ro no tu nhien chong mat thoi', 'user', '2025-07-08 11:31:32'),
(596, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **buồn nôn** của bạn nè. Bạn có thấy cảm giác này xuất hiện khi nào? Có phải là sau khi ăn uống hay khi bạn cảm thấy căng thẳng không ha?', 'bot', '2025-07-08 11:31:33'),
(597, 4, NULL, 'symptom_query', 'khi minh ngui thay 1 mui gi do kha nang thi co cam giac buon non', 'user', '2025-07-08 11:31:46'),
(598, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có thêm cảm giác nào khác nữa không, như là mệt mỏi, tức ngực hay đổ mồ hôi không nhỉ? Những triệu chứng này có thể liên quan đến cảm giác **đau đầu**, **chóng mặt**, và **buồn nôn** bạn đang gặp phải.', 'bot', '2025-07-08 11:31:46'),
(599, 4, NULL, 'symptom_query', 'khong co', 'user', '2025-07-08 11:32:04'),
(600, 4, NULL, 'symptom_query', 'Bạn có thể mô tả rõ hơn bạn cảm thấy gì không?', 'bot', '2025-07-08 11:32:04'),
(601, 4, NULL, 'symptom_query', 'khong co', 'user', '2025-07-08 11:32:53'),
(602, 4, NULL, 'symptom_query', 'Bạn có thể mô tả rõ hơn bạn cảm thấy gì không?', 'bot', '2025-07-08 11:32:53'),
(603, 4, NULL, 'symptom_query', 'khong co', 'user', '2025-07-08 11:33:25'),
(604, 4, NULL, 'symptom_query', 'Bạn có thể mô tả rõ hơn bạn cảm thấy gì không?', 'bot', '2025-07-08 11:33:25'),
(605, 4, NULL, 'symptom_query', 'khong co', 'user', '2025-07-08 11:38:50'),
(606, 4, NULL, 'symptom_query', 'Bạn có thể mô tả rõ hơn bạn cảm thấy gì không?', 'bot', '2025-07-08 11:38:50'),
(607, 4, NULL, 'symptom_query', 'khong co', 'user', '2025-07-08 11:40:53'),
(608, 4, NULL, 'symptom_query', 'Bạn có thể mô tả rõ hơn bạn cảm thấy gì không?', 'bot', '2025-07-08 11:40:53'),
(609, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-08 11:42:46'),
(610, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **đau đầu** nè. Bạn thường gặp tình trạng này khi nào ha? Có khi nào bạn thấy đỡ hơn sau khi nghỉ ngơi không?', 'bot', '2025-07-08 11:42:46'),
(611, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-08 11:42:59'),
(612, 4, NULL, 'symptom_query', 'Khi bạn cảm thấy **chóng mặt**, có phải bạn thường gặp phải lúc ngồi dậy hay đứng lên không? Hoặc cảm giác này kéo dài bao lâu mỗi lần bạn gặp vậy?', 'bot', '2025-07-08 11:42:59'),
(613, 4, NULL, 'symptom_query', 'minh cung khong ro no tu nhien chong mat thoi', 'user', '2025-07-08 11:43:35'),
(614, 4, NULL, 'symptom_query', 'Cảm giác **buồn nôn** của bạn có thường xảy ra sau khi ăn uống gì không? Hoặc có khi nào bạn thấy buồn nôn kèm theo cảm giác mệt mỏi không ha?', 'bot', '2025-07-08 11:43:36'),
(615, 4, NULL, 'symptom_query', 'khi minh ngui thay 1 mui gi do kha nang thi co cam giac buon non', 'user', '2025-07-08 11:44:09'),
(616, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có thêm cảm giác nào khác không, như là **đau bụng**, **mệt mỏi** hay **tim đập nhanh** không nhỉ? Những triệu chứng này có thể liên quan đến tình trạng của bạn đấy.', 'bot', '2025-07-08 11:44:09'),
(617, 4, NULL, 'symptom_query', 'khong co', 'user', '2025-07-08 11:44:28'),
(618, 4, NULL, 'symptom_query', 'Bạn có thể mô tả rõ hơn bạn cảm thấy gì không?', 'bot', '2025-07-08 11:44:28'),
(619, 4, NULL, 'symptom_query', 'khong co', 'user', '2025-07-08 11:45:49'),
(620, 4, NULL, 'symptom_query', 'Bạn có thể mô tả rõ hơn bạn cảm thấy gì không?', 'bot', '2025-07-08 11:45:49'),
(621, 4, NULL, 'symptom_query', 'khong co', 'user', '2025-07-08 11:51:41'),
(622, 4, NULL, 'symptom_query', 'Bạn có thể mô tả rõ hơn bạn cảm thấy gì không?', 'bot', '2025-07-08 11:51:41'),
(623, 4, NULL, 'symptom_query', 'khong co', 'user', '2025-07-08 11:55:30'),
(624, 4, NULL, 'symptom_query', 'Bạn có thể mô tả rõ hơn bạn cảm thấy gì không?', 'bot', '2025-07-08 11:55:30'),
(625, 4, NULL, 'symptom_query', 'khong co', 'user', '2025-07-08 12:01:12'),
(626, 4, NULL, 'symptom_query', 'Bạn có thể mô tả rõ hơn bạn cảm thấy gì không?', 'bot', '2025-07-08 12:01:12'),
(627, 4, NULL, 'symptom_query', 'khong co', 'user', '2025-07-08 12:04:39'),
(628, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **đau đầu**, **chóng mặt**, và **buồn nôn**. Những triệu chứng này có thể liên quan đến một số nguyên nhân như sau:\n\n- **Đau đầu** có thể là do bạn thiếu ngủ hoặc căng thẳng. Bạn thử nghỉ ngơi xem sao nha.\n- **Chóng mặt** có thể do thay đổi tư thế đột ngột hoặc thiếu nước nhẹ. Bạn có thể thử uống nước từ từ và ngồi nghỉ.\n- **Buồn nôn** có thể xuất phát từ việc dạ dày không được thoải mái hoặc lo âu. Hãy thử ăn nhẹ và xem có đỡ hơn không nhé.\n\nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau:\n\n📌 **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **đau đầu** và **buồn nôn**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n\n📌 **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.\n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.\n\nNếu bạn muốn chắc chắn, bạn có thể đi khám để kiểm tra kỹ hơn.', 'bot', '2025-07-08 12:04:39'),
(629, 4, NULL, 'suggest_product', 'ban co the gioi thieu giup minh vai san pham de tri may cai trieu chung do ko?', 'user', '2025-07-08 12:59:18'),
(630, 4, NULL, 'suggest_product', '📌 *Sản phẩm 1*\nĐã buồn và khó chịu lắm phải không bạn? Mình có một gợi ý nhỏ cho bạn nè 💕. Paracetamol 500mg là một sự lựa chọn tuyệt vời giúp giảm đau và hạ sốt khi bạn đang cảm thấy không được khỏe. Bạn có thể dùng nó để làm dịu cơn đau hoặc hạ nhiệt khi cần thiết. \n\nHãy nhớ sử dụng theo liều lượng chỉ định và tránh dùng khi bạn đã có những bệnh lý đặc biệt mà bác sĩ không khuyên. Chúc bạn mau khỏe nhé! \n\n👉 [Xem chi tiết tại đây](https://demo.site.vn/products/1)\n\n📌 *Sản phẩm 2*\nĐã lâu bạn không được khỏe phải không? Đừng lo lắng, mình có một gợi ý mà có thể giúp bạn cảm thấy dễ chịu hơn. Bạn có thể thử dùng Ibuprofen 200mg, sản phẩm này rất hữu ích trong việc giảm đau và hạ sốt. Khi bạn cảm thấy khó chịu, chỉ cần dùng theo hướng dẫn là sẽ cảm thấy thoải mái hơn.\n\nHãy nhớ rằng, nếu bạn có bất kỳ triệu chứng nào kéo dài, hãy tham khảo ý kiến bác sĩ để được tư vấn kịp thời nhé. Chúc bạn mau khỏe!\n\n👉 [Xem chi tiết tại đây](https://demo.site.vn/products/6)\n\n📌 *Sản phẩm 3*\nĐã lâu không gặp, bạn có vẻ mệt mỏi và cần một giải pháp thư giãn cho giấc ngủ của mình phải không? Mình có một gợi ý nhẹ nhàng cho bạn: Trà ngủ ngon Hoa Cúc. Trà này nổi tiếng với khả năng giúp cải thiện giấc ngủ và mang lại cảm giác thư thái. Bạn hãy thử uống trước khi đi ngủ, để cơ thể được thư giãn, từ đó dễ dàng chìm vào giấc ngủ hơn nhé. Đừng quên tạo cho mình một không gian yên tĩnh và ấm cúng để giấc ngủ đạt hiệu quả tốt nhất nhé! \n\n👉 [Xem chi tiết tại đây](https://demo.site.vn/products/11)\n\n', 'bot', '2025-07-08 12:59:18'),
(631, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-08 13:28:43'),
(632, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết cơn **đau đầu** này thường kéo dài bao lâu mỗi lần bạn gặp phải không ha? Có khi nào bạn thấy đỡ hơn sau khi nghỉ ngơi không?', 'bot', '2025-07-08 13:28:43'),
(633, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-08 13:28:57'),
(634, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết cảm giác **chóng mặt** của bạn có thường xuất hiện khi bạn đang ngồi hay khi vừa đứng lên không? 🌿', 'bot', '2025-07-08 13:28:58'),
(635, 4, NULL, 'symptom_query', 'minh cung khong ro no tu nhien chong mat thoi', 'user', '2025-07-08 13:29:24'),
(636, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **buồn nôn** của bạn nè, có khi nào bạn thấy nó đi kèm với mệt mỏi hoặc chỉ đơn giản là cảm giác không thoải mái thôi không ha?', 'bot', '2025-07-08 13:29:24'),
(637, 4, NULL, 'symptom_query', 'khi minh ngui thay 1 mui gi do kha nang thi co cam giac buon non', 'user', '2025-07-08 13:29:41'),
(638, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có thấy thêm cảm giác nào khác nữa không, như mệt mỏi, tim đập nhanh hay buồn nôn nhiều hơn không nha? 🌿', 'bot', '2025-07-08 13:29:41'),
(639, 4, NULL, 'symptom_query', 'khong co', 'user', '2025-07-08 13:30:19'),
(640, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **đau đầu**, **chóng mặt**, và **buồn nôn**. Những triệu chứng này có thể liên quan đến vài nguyên nhân khác nhau. \n- **Đau đầu** có thể là do bạn thiếu ngủ hoặc căng thẳng. Bạn thử nghỉ ngơi xem sao nha. \n- **Chóng mặt** có thể do thay đổi tư thế đột ngột hoặc thiếu nước nhẹ. Bạn có thể thử uống nước từ từ và ngồi nghỉ. \n- **Buồn nôn** có thể đi kèm với tình trạng mệt mỏi hoặc lo âu. Hãy đảm bảo bạn ăn uống đầy đủ và nghỉ ngơi nhé. \nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau: \n📌 **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **đau đầu** và **buồn nôn**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.  \n\n📌 **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.  \n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.  \nNếu bạn muốn chắc chắn, bạn có thể đi khám để kiểm tra kỹ hơn.', 'bot', '2025-07-08 13:30:19'),
(641, 4, NULL, 'suggest_product', 'ban co the goi y minh vai san pham co the giam bot nhung trieu chung minh dang gap phai ko?', 'user', '2025-07-08 13:31:15'),
(642, 4, NULL, 'suggest_product', 'Bạn đang cảm thấy khó chịu và cần một chút giúp đỡ để giảm bớt triệu chứng đúng không? Mình có một gợi ý rất hay cho bạn nè, đó là thuốc Paracetamol 500mg. Đây là một loại thuốc hạ sốt và giảm đau rất phổ biến, giúp bạn cảm thấy nhẹ nhõm hơn khi gặp phải cơn đau đầu hay cảm cúm. Chỉ cần dùng theo liều lượng khuyến cáo và bạn sẽ nhanh chóng thấy tình trạng sức khỏe được cải thiện. Hãy thử nhé! 👉 [Xem chi tiết tại đây](https://demo.site.vn/products/1)\n\nNgoài ra, bạn cũng có thể tham khảo Ibuprofen 200mg. Đây là loại thuốc không chỉ giảm đau mà còn có tác dụng kháng viêm và hạ sốt, rất phù hợp cho những ai đang trong tình trạng bị đau nhức hoặc sưng tấy. Hãy sử dụng khi cần thiết và tránh dùng quá liều để đảm bảo an toàn cho sức khỏe của bạn. Mong rằng bạn sớm cảm thấy dễ chịu hơn! 👉 [Xem chi tiết tại đây](https://demo.site.vn/products/6)', 'bot', '2025-07-08 13:31:16'),
(643, 4, NULL, 'suggest_product', 'minh dang bi dau dau khong biet la shop ban co thuoc gi co the ho tro minh khong?', 'user', '2025-07-08 13:34:04'),
(644, 4, NULL, 'suggest_product', 'Da bạn hay khô phải không? Mình có bí quyết nhỏ nè :droplet: Nếu bạn đang gặp phải những cơn đau đầu khó chịu, thì thuốc Paracetamol 500mg có thể là một lựa chọn tốt cho bạn. Đây là loại thuốc hạ sốt và giảm đau rất phổ biến, giúp bạn cảm thấy dễ chịu hơn trong những lúc không thoải mái. Bạn chỉ cần uống từ 1-2 viên mỗi 4-6 giờ nếu cần, nhưng nhớ không uống quá 8 viên mỗi ngày nhé! Hãy cẩn thận nếu bạn có tiền sử về gan hoặc nghiện rượu. 👉 [Xem chi tiết tại đây](https://demo.site.vn/products/1)\n\nNếu cần một sự lựa chọn khác, thuốc Ibuprofen 200mg cũng rất hữu ích trong việc giảm đau và kháng viêm, có thể giúp bạn xoa dịu cơn đau đầu một cách hiệu quả. Bạn nên uống sau bữa ăn, và người lớn có thể uống 1 viên mỗi 6-8 giờ nếu cần thiết, nhưng không nên vượt quá 6 viên trong một ngày. Lưu ý rằng nếu bạn có vấn đề về dạ dày hoặc suy gan/thận, thì nên tránh sử dụng thuốc này. Hy vọng bạn sẽ thấy dễ chịu hơn! 👉 [Xem chi tiết tại đây](https://demo.site.vn/products/6)', 'bot', '2025-07-08 13:34:04'),
(645, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa.', 'user', '2025-07-09 07:06:24'),
(646, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè. Bạn có cảm thấy cơn đau này thường kéo dài bao lâu mỗi lần gặp không, hoặc có khi nào bạn thấy đỡ hơn sau khi nghỉ ngơi không ha?', 'bot', '2025-07-09 07:06:24'),
(647, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-09 07:06:39'),
(648, 4, NULL, 'symptom_query', 'Cảm giác **chóng mặt** của bạn thường xảy ra trong hoàn cảnh nào, ví dụ như khi ngồi hoặc khi vừa đứng lên không nhỉ?', 'bot', '2025-07-09 07:06:39'),
(649, 4, NULL, 'symptom_query', 'minh cung khong ro no tu nhien chong mat thoi', 'user', '2025-07-09 07:06:52'),
(650, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **buồn nôn** của bạn nè. Cảm giác này thường xảy ra sau khi bạn ăn hay không nhỉ?', 'bot', '2025-07-09 07:06:52'),
(651, 4, NULL, 'symptom_query', 'khi minh ngui thay 1 mui gi do kha nang thi co cam giac buon non', 'user', '2025-07-09 07:07:07'),
(652, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có thêm cảm giác nào khác nữa không, ví dụ như có thấy mệt mỏi, hay ra mồ hôi nhiều không nhỉ? 😊', 'bot', '2025-07-09 07:07:07'),
(653, 4, NULL, 'symptom_query', 'khong co', 'user', '2025-07-09 07:07:33'),
(654, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **đau đầu**, **chóng mặt**, và **buồn nôn**. Những triệu chứng này có thể xuất phát từ một số nguyên nhân như sau:\n\n**Đau đầu** có thể là do bạn thiếu ngủ hoặc căng thẳng. Bạn thử nghỉ ngơi xem sao nha.\n\n**Chóng mặt** có thể do thay đổi tư thế đột ngột hoặc thiếu nước nhẹ. Bạn có thể thử uống nước từ từ và ngồi nghỉ.\n\n**Buồn nôn** có thể liên quan đến dạ dày hoặc căng thẳng. Bạn có thể thử ăn nhẹ và nghỉ ngơi một chút.\n\nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau: \n\n�� **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.\n\n�� **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **đau đầu** và **buồn nôn**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.\n\n—\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha 🌿\nNếu bạn muốn chắc chắn, bạn có thể đi khám để kiểm tra kỹ hơn.', 'bot', '2025-07-09 07:07:33'),
(655, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-09 07:13:45'),
(656, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **đau đầu** của bạn nè. Cảm giác đó thường kéo dài bao lâu mỗi lần bạn gặp vậy?', 'bot', '2025-07-09 07:13:45'),
(657, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-09 07:13:59'),
(658, 4, NULL, 'symptom_query', 'Khi bạn cảm thấy **chóng mặt**, có khi nào cảm giác này xảy ra lúc bạn vừa đứng lên hay lúc đang ngồi không? Mình đang nghĩ không biết nó thường kéo dài bao lâu mỗi lần bạn gặp phải nhé.', 'bot', '2025-07-09 07:13:59'),
(659, 4, NULL, 'general_chat', 'chat do sang h minh chua an gi', 'user', '2025-07-09 07:14:42'),
(660, 4, NULL, 'general_chat', 'Um, mình hiểu rồi nè. Có vẻ như bạn cảm thấy không được thoải mái. Nếu bạn cần hỗ trợ hay có câu hỏi gì, cứ chia sẻ nhé!', 'bot', '2025-07-09 07:14:42'),
(661, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-09 07:22:17'),
(662, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **đau đầu** của bạn nè. Cảm giác đó thường kéo dài bao lâu mỗi lần bạn gặp vậy?', 'bot', '2025-07-09 07:22:17'),
(663, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-09 07:22:48'),
(664, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **đau đầu**, **chóng mặt**, và **buồn nôn**... Những triệu chứng này có thể liên quan đến một số nguyên nhân nhẹ nhàng như:\n\n- **Đau đầu** có thể là do bạn thiếu ngủ hoặc căng thẳng. Bạn thử nghỉ ngơi xem sao nha.\n- **Chóng mặt** có thể do thay đổi tư thế đột ngột hoặc thiếu nước nhẹ. Bạn có thể thử uống nước từ từ và ngồi nghỉ.\n- **Buồn nôn** có thể do dạ dày không ổn định hoặc căng thẳng. Nếu có thể, hãy thử ăn một chút gì đó nhẹ nhàng nhé.\n\nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau:\n\n�� **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **đau đầu** và **buồn nôn**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n\n📌 **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.\n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.\n\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha 🌿\n\nNếu cần, mình có thể hỗ trợ bạn đặt lịch khám phù hợp nha.', 'bot', '2025-07-09 07:22:48'),
(665, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-09 07:31:49'),
(666, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **đau đầu**, **chóng mặt**, và **buồn nôn**. \n\n**Đau đầu** có thể là do bạn thiếu ngủ hoặc căng thẳng. Bạn thử nghỉ ngơi xem sao nha.\n**Chóng mặt** có thể do thay đổi tư thế đột ngột hoặc thiếu nước nhẹ. Bạn có thể thử uống nước từ từ và ngồi nghỉ.\n**Buồn nôn** có thể liên quan đến việc ăn uống không đều hoặc lo âu. Hãy thử ăn nhẹ và thư giãn một chút nhé.\n\nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau: \n\n�� **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **đau đầu** và **buồn nôn**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n\n📌 **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.\n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.\n\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha 🌿\n\nNếu cần, mình có thể hỗ trợ bạn đặt lịch khám phù hợp nha.', 'bot', '2025-07-09 07:31:49'),
(667, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-09 07:45:01'),
(668, 4, NULL, 'symptom_query', 'Cảm giác **đau đầu** của bạn có khi nào thấy nặng hơn khi bạn đứng lên hay thay đổi tư thế không? Mình muốn biết thêm để hỗ trợ bạn tốt hơn.', 'bot', '2025-07-09 07:45:01'),
(669, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-09 07:45:13'),
(670, 4, NULL, 'symptom_query', 'Mình đang thắc mắc không biết cảm giác **chóng mặt** của bạn có xảy ra khi đang ngồi hay khi vừa đứng lên không? Hoặc có khi nào bạn thấy cảm giác này kéo dài vài phút hay lâu hơn một chút không ha?', 'bot', '2025-07-09 07:45:13'),
(671, 4, NULL, 'symptom_query', 'chat do sang h minh chua an gi', 'user', '2025-07-09 07:45:26'),
(672, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **buồn nôn** của bạn nè. Có khi nào cảm giác này xảy ra ngay sau khi bạn chưa ăn gì không, hay nó xuất hiện một cách ngẫu nhiên ha?', 'bot', '2025-07-09 07:45:26'),
(673, 4, NULL, 'symptom_query', 'khi minh ngui thay 1 mui gi do kha nang thi co cam giac buon non', 'user', '2025-07-09 07:45:38'),
(674, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có thêm cảm giác nào khác như mệt mỏi, đổ mồ hôi nhiều, hay có cảm giác tức ngực không nhé? Điều này có thể giúp mình hiểu rõ hơn về tình trạng của bạn.', 'bot', '2025-07-09 07:45:38'),
(675, 4, NULL, 'symptom_query', 'khong co', 'user', '2025-07-09 07:46:20'),
(676, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **đau đầu**, **chóng mặt**, và **buồn nôn**. \n\n**Đau đầu** có thể là do bạn thiếu ngủ hoặc căng thẳng. Bạn thử nghỉ ngơi xem sao nha. \n**Chóng mặt** có thể do thay đổi tư thế đột ngột hoặc thiếu nước nhẹ. Bạn có thể thử uống nước từ từ và ngồi nghỉ. \n**Buồn nôn** có thể liên quan đến việc ăn uống không đều hoặc lo âu. Hãy thử ăn nhẹ và thư giãn một chút nhé.\n\nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau: \n\n📌 **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **đau đầu** và **buồn nôn**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân. \n\n📌 **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày. \n\n�� **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.\n\n—\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha ��\nNếu cần, mình có thể hỗ trợ bạn đặt lịch khám phù hợp nha.', 'bot', '2025-07-09 07:46:20'),
(677, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-09 07:48:20'),
(678, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **đau đầu** nè. Bạn thường thấy cảm giác này xuất hiện lúc nào, lúc đang ngồi hay lúc vừa đứng lên?', 'bot', '2025-07-09 07:48:20'),
(679, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-09 07:54:57'),
(680, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cơn **đau đầu** của bạn nè. Cảm giác đó có thường kéo dài bao lâu mỗi lần bạn gặp không?', 'bot', '2025-07-09 07:54:57'),
(681, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-09 08:04:00'),
(682, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có thêm cảm giác nào khác nữa không, như là **mệt mỏi**, **tim đập nhanh**, hay **đổ mồ hôi** không nhé? Những triệu chứng đó có thể giúp mình hiểu rõ hơn về tình trạng của bạn.', 'bot', '2025-07-09 08:04:00'),
(683, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-09 08:04:39'),
(684, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **đau đầu**, **chóng mặt**, và **buồn nôn**. Những triệu chứng này có thể do một số nguyên nhân như sau:\n\n**Đau đầu** có thể là do bạn thiếu ngủ hoặc căng thẳng. Bạn thử nghỉ ngơi xem sao nha.\n**Chóng mặt** có thể do thay đổi tư thế đột ngột hoặc thiếu nước nhẹ. Bạn có thể thử uống nước từ từ và ngồi nghỉ.\n**Buồn nôn** có thể liên quan đến việc ăn không đúng bữa hoặc stress. Hãy thử ăn nhẹ và hít thở sâu để cảm thấy dễ chịu hơn.\n\nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau:\n\n📌 **Căng thẳng hoặc lo âu**  \nÁp lực trong cuộc sống có thể gây ra cảm giác **đau đầu** và **buồn nôn**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n\n📌 **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.\n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.\n\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha 🌿\nNếu cần, mình có thể hỗ trợ bạn đặt lịch khám phù hợp nha.', 'bot', '2025-07-09 08:04:39'),
(685, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-09 08:05:42'),
(686, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **cảm giác và nôn** kéo dài khoảng 5-10 phút khi bạn nằm xuống. Điều này có thể do một số nguyên nhân như sau:\n\n**Căng thẳng hoặc lo âu** có thể gây ra cảm giác **nôn** và khó chịu trong cơ thể. Bạn có thể thử thư giãn hoặc hít thở sâu để cảm thấy dễ chịu hơn.  \n**Thiếu nước hoặc thiếu dinh dưỡng** có thể dẫn đến cảm giác **cảm giác** và mệt mỏi. Hãy đảm bảo bạn đang uống đủ nước và ăn uống đầy đủ.  \n**Huyết áp thấp** cũng có thể là nguyên nhân dẫn đến cảm giác **cảm giác** khi thay đổi tư thế. Bạn nên ngồi nghỉ và uống một chút nước từ từ.\n\nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau:\n\n📌 **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **cảm giác** và **nôn**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n\n📌 **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **cảm giác** hoặc mệt mỏi.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.\n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **cảm giác** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.\n\n—\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha 🌿\nNếu cần, mình có thể hỗ trợ bạn đặt lịch khám phù hợp nha.', 'bot', '2025-07-09 08:05:42'),
(687, 4, NULL, 'symptom_query', 'Từ sáng đến giờ mình bị đau đầu kiểu nhói ở thái dương, kèm theo chóng mặt và buồn nôn nhẹ nữa', 'user', '2025-07-09 08:06:42'),
(688, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **đau đầu** này nha. Bạn có thường thấy khó chịu hơn khi đang làm gì đó, hay nó xuất hiện khi bạn vừa đứng lên hoặc thay đổi tư thế không?', 'bot', '2025-07-09 08:06:42'),
(689, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-09 08:06:56'),
(690, 4, NULL, 'symptom_query', 'Bạn có thấy cảm giác **chóng mặt** này thường xảy ra khi nào không? Ví dụ như lúc đang ngồi hay khi vừa đứng lên? 😊', 'bot', '2025-07-09 08:06:56'),
(691, 4, NULL, 'symptom_query', 'chat do sang h minh chua an gi', 'user', '2025-07-09 08:07:10'),
(692, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **buồn nôn** của bạn. Bạn có thấy nó thường đi kèm với các triệu chứng khác như **đau đầu** hay **chóng mặt** không, hay nó chỉ xảy ra riêng lẻ thôi?', 'bot', '2025-07-09 08:07:10'),
(693, 4, NULL, 'symptom_query', 'khi minh ngui thay 1 mui gi do kha nang thi co cam giac buon non', 'user', '2025-07-09 08:07:42'),
(694, 4, NULL, 'symptom_query', 'Mình muốn hỏi thêm một chút về cảm giác **khó thở** của bạn nè. Bạn có thấy nó thường xảy ra khi bạn đang ngồi hay khi vừa đứng lên không? 😊', 'bot', '2025-07-09 08:07:42'),
(695, 4, NULL, 'symptom_query', 'khong ro', 'user', '2025-07-09 08:08:37'),
(696, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **đau đầu**, **chóng mặt**, và **buồn nôn** kéo dài khoảng 5-10 phút khi bạn nằm xuống. Những triệu chứng này có thể liên quan đến vài nguyên nhân như sau:\n\n**Đau đầu** có thể do bạn thiếu ngủ hoặc căng thẳng. Bạn thử nghỉ ngơi xem sao nha.\n\n**Chóng mặt** có thể do thay đổi tư thế đột ngột hoặc thiếu nước nhẹ. Bạn có thể thử uống nước từ từ và ngồi nghỉ.\n\n**Buồn nôn** có thể xảy ra khi cơ thể không được cung cấp đủ dinh dưỡng hoặc do lo âu. Hãy thử ăn nhẹ và thư giãn một chút nhé.\n\nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau:\n\n�� **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **đau đầu** và **buồn nôn**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n\n📌 **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.\n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.\n\n—\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha 🌿\nNếu cần, mình có thể hỗ trợ bạn đặt lịch khám phù hợp nha.', 'bot', '2025-07-09 08:08:37'),
(697, 4, NULL, 'symptom_query', 'minh cam giac dau dau qua', 'user', '2025-07-09 08:10:19'),
(698, 4, NULL, 'symptom_query', 'Mình thấy bạn mô tả rõ hơn rồi, cảm giác **đau đầu** có thể do bạn chưa ăn gì từ sáng nên cảm thấy chóng mặt và buồn nôn. Nhưng cũng có thể là do bạn thiếu ngủ, cơ thể mệt hoặc thời tiết thay đổi nữa. Mình sẽ ghi chú lại thêm để theo dõi sát hơn ha.\n\n\nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau:\n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn chưa ăn gì.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.\n\n📌 **Thiếu năng lượng nhẹ**  \nCơ thể bị hạ đường huyết tạm thời nếu nhịn ăn lâu.  \n→ Bạn có thể ăn nhẹ hoặc uống sữa để lấy lại sức.\n\n📌 **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **đau đầu** và **buồn nôn**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n\n—\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha 🌿\nNếu cần, mình có thể hỗ trợ bạn đặt lịch khám phù hợp nha.', 'bot', '2025-07-09 08:10:19'),
(699, 4, NULL, 'symptom_query', 'minh cam giac dau dau qua', 'user', '2025-07-09 08:10:44'),
(700, 4, NULL, 'symptom_query', 'Cảm giác **đau đầu** của bạn có thường kéo dài bao lâu mỗi lần gặp không? Nó có hay xảy ra khi bạn căng thẳng hay thiếu ngủ không?', 'bot', '2025-07-09 08:10:44'),
(701, 4, NULL, 'symptom_query', 'khi vua nam xuong thi minh bat dau co cam giac va no keo dai tam 5-10p gi do', 'user', '2025-07-09 08:11:03'),
(702, 4, NULL, 'symptom_query', 'Mình đang nghĩ không biết bạn có thêm cảm giác nào khác nữa không, như **mệt mỏi**, **chóng mặt**, hay **tức ngực** không nhỉ? Những triệu chứng này có thể giúp mình hiểu rõ hơn về tình trạng của bạn.', 'bot', '2025-07-09 08:11:03'),
(703, 4, NULL, 'symptom_query', 'minh cung co cam giac chong mat nua', 'user', '2025-07-09 08:11:24'),
(704, 4, NULL, 'symptom_query', 'Mình đang muốn hỏi thêm chút về cảm giác **chóng mặt** của bạn. Bạn thường cảm thấy chóng mặt khi nào, như lúc vừa đứng dậy hay khi đang ngồi nghỉ ngơi?', 'bot', '2025-07-09 08:11:24'),
(705, 4, NULL, 'symptom_query', 'lut minh vua dung day', 'user', '2025-07-09 08:12:02'),
(706, 4, NULL, 'symptom_query', 'Bạn đã mô tả cảm giác như **đau đầu** và **chóng mặt**. \n\n**Đau đầu** có thể là do bạn thiếu ngủ hoặc căng thẳng. Bạn thử nghỉ ngơi xem sao nha.\n**Chóng mặt** có thể do thay đổi tư thế đột ngột hoặc thiếu nước nhẹ. Bạn có thể thử uống nước từ từ và ngồi nghỉ.\n\nNgoài ra, các triệu chứng bạn vừa chia sẻ cũng có thể liên quan đến vài tình trạng như sau: \n\n📌 **Căng thẳng hoặc lo âu**  \nĐôi khi áp lực công việc hoặc cuộc sống có thể gây ra cảm giác **đau đầu** và **buồn nôn**.  \n→ Bạn có thể thử nghỉ ngơi, hít thở sâu và dành thời gian cho bản thân.\n\n📌 **Mất nước hoặc thiếu dinh dưỡng**  \nNếu cơ thể không được cung cấp đủ nước hoặc năng lượng, bạn có thể cảm thấy **chóng mặt** hoặc mệt mỏi.  \n→ Bạn nên uống đủ nước, ăn uống đầy đủ trong ngày.\n\n📌 **Huyết áp thấp**  \nTình trạng này có thể gây cảm giác **chóng mặt** nhẹ khi bạn thay đổi tư thế đột ngột.  \n→ Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.\n\n—\nNếu bạn muốn, mình có thể gợi ý vài sản phẩm giúp bạn cảm thấy dễ chịu hơn nha 🌿\nNếu cần, mình có thể hỗ trợ bạn đặt lịch khám phù hợp nha.', 'bot', '2025-07-09 08:12:02');

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
(3, 6),
(4, 1),
(4, 2),
(4, 7),
(4, 8),
(5, 1),
(5, 4),
(5, 7);

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
(68, 4, 68, NULL, '2025-07-09 08:11:53', 0.9, '{\"symptoms\": [\"Đau đầu\", \"Chóng mặt\"], \"predicted_diseases\": [\"Căng thẳng hoặc lo âu\", \"Mất nước hoặc thiếu dinh dưỡng\", \"Huyết áp thấp\"]}');

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
(68, 4, '2025-07-09', NULL, NULL, NULL, 'Triệu chứng ghi nhận: Đau đầu, Chóng mặt', '2025-07-09 08:11:53', '2025-07-09 15:11:53');

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
(199, 68, -1, 'Huyết áp thấp', 0.85, 'Tình trạng này có thể gây cảm giác chóng mặt nhẹ khi bạn thay đổi tư thế đột ngột.', 'Thử ngồi nghỉ và uống nước từ từ để cảm thấy ổn hơn nha.');

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
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Ẩn/hiện sản phẩm (TRUE = hiển thị, FALSE = ẩn)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `category_id`, `name`, `description`, `price`, `stock`, `is_medicine`, `image_url`, `created_at`, `updated_at`, `is_active`) VALUES
(1, 1, 'Paracetamol 500mg', 'Thuốc hạ sốt, giảm đau thường dùng.', 15000, 100, 1, 'https://example.com/images/paracetamol.jpg', '2025-05-28 07:02:02', '2025-07-04 20:03:58', 1),
(2, 1, 'Amoxicillin 500mg', 'Kháng sinh phổ rộng nhóm penicillin.', 28000, 60, 1, 'https://example.com/images/amoxicillin.jpg', '2025-05-28 07:02:02', '2025-07-04 20:03:58', 1),
(3, 2, 'Vitamin C 1000mg', 'Hỗ trợ tăng cường đề kháng.', 50000, 200, 1, 'https://example.com/images/vitaminC.jpg', '2025-05-28 07:02:02', '2025-07-04 20:03:58', 1),
(4, 3, 'Máy đo huyết áp điện tử', 'Thiết bị đo huyết áp tại nhà.', 650000, 15, 0, 'https://example.com/images/blood_pressure_monitor.jpg', '2025-05-28 07:02:02', '2025-05-28 14:02:02', 1),
(5, 4, 'Khẩu trang y tế 4 lớp', 'Hộp 50 cái, đạt chuẩn kháng khuẩn.', 40000, 500, 0, 'https://example.com/images/face_mask.jpg', '2025-05-28 07:02:02', '2025-05-28 14:02:02', 1),
(6, 1, 'Ibuprofen 200mg', 'Thuốc giảm đau, kháng viêm, hạ sốt.', 20000, 80, 1, 'https://example.com/images/ibuprofen.jpg', '2025-07-04 12:55:40', '2025-07-04 20:03:58', 1),
(7, 2, 'Kẽm Gluconat 50mg', 'Hỗ trợ miễn dịch, chống viêm nhiễm.', 45000, 150, 1, 'https://example.com/images/zinc.jpg', '2025-07-04 12:55:40', '2025-07-04 20:03:58', 1),
(8, 2, 'Men tiêu hóa Biolactyl', 'Giúp cân bằng hệ vi sinh đường ruột.', 70000, 90, 1, 'https://example.com/images/probiotic.jpg', '2025-07-04 12:55:40', '2025-07-04 20:03:58', 1),
(9, 3, 'Máy xông mũi họng mini', 'Hỗ trợ điều trị viêm mũi, cảm cúm tại nhà.', 350000, 25, 0, 'https://example.com/images/nebulizer.jpg', '2025-07-04 12:55:40', '2025-07-04 19:55:40', 1),
(10, 5, 'Kem dưỡng ẩm da nhạy cảm', 'Phục hồi và giữ ẩm cho da khô, kích ứng.', 120000, 50, 0, 'https://example.com/images/moisturizer.jpg', '2025-07-04 12:55:40', '2025-07-04 19:55:40', 1),
(11, 6, 'Trà ngủ ngon Hoa Cúc', 'Giúp thư giãn, cải thiện giấc ngủ tự nhiên.', 65000, 70, 0, 'https://example.com/images/chamomile_tea.jpg', '2025-07-04 12:55:40', '2025-07-04 19:55:40', 1);

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
(5, 'Chăm sóc da', 'Sản phẩm hỗ trợ điều trị và chăm sóc da.', '2025-07-04 12:55:32', '2025-07-04 19:55:32'),
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
(164, 4, 19, '2025-07-09', 'Người dùng có cảm giác chóng mặt khi vừa đứng dậy.');

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
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=200;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=165;

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
