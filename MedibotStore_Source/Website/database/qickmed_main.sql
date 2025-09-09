-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th6 23, 2025 lúc 08:20 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `qickmed`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `appointments`
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
-- Đang đổ dữ liệu cho bảng `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `user_id`, `guest_id`, `doctor_id`, `clinic_id`, `appointment_time`, `reason`, `status`, `created_at`, `updated_at`) VALUES
(1, 4, NULL, 5, 2, '2025-06-18 11:30:00', 'aa', 'pending', '2025-06-16 13:25:48', '2025-06-17 12:09:49'),
(2, 1, NULL, 4, 2, '2025-06-18 15:00:00', 'ss', 'pending', '2025-06-17 05:47:12', '2025-06-17 12:47:12'),
(3, 12, NULL, 4, 2, '2025-06-25 12:00:00', 'đau đầu', 'pending', '2025-06-23 17:59:15', '2025-06-24 00:59:15');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `blog_authors`
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
-- Đang đổ dữ liệu cho bảng `blog_authors`
--

INSERT INTO `blog_authors` (`author_id`, `user_id`, `name`, `avatar`, `bio`, `title`, `created_at`, `updated_at`) VALUES
(1, NULL, 'BS. Nguyễn Văn A', 'https://i.pinimg.com/736x/58/18/3f/58183feeff607a80fd0f8c8bad8ab6a2.jpg', 'Bác sĩ chuyên khoa Nội tổng quát với 10 năm kinh nghiệm', 'Bác sĩ chuyên khoa', '2025-06-15 05:02:55', '2025-06-15 05:12:54'),
(2, NULL, 'BS. Trần Thị B', 'https://i.pinimg.com/736x/58/18/3f/58183feeff607a80fd0f8c8bad8ab6a2.jpg', 'Bác sĩ chuyên khoa Dinh dưỡng', 'Bác sĩ dinh dưỡng', '2025-06-15 05:02:55', '2025-06-15 05:12:57'),
(3, NULL, 'BS. Lê Văn C', 'https://i.pinimg.com/736x/58/18/3f/58183feeff607a80fd0f8c8bad8ab6a2.jpg', 'Bác sĩ chuyên khoa Thể thao', 'Bác sĩ thể thao', '2025-06-15 05:02:55', '2025-06-15 05:12:58'),
(4, NULL, 'BS. Phạm Thị D', 'https://i.pinimg.com/736x/58/18/3f/58183feeff607a80fd0f8c8bad8ab6a2.jpg', 'Bác sĩ chuyên khoa Tâm lý', 'Bác sĩ tâm lý', '2025-06-15 05:02:55', '2025-06-15 05:13:00'),
(5, NULL, 'ThS. Hoàng Văn E', 'https://i.pinimg.com/736x/58/18/3f/58183feeff607a80fd0f8c8bad8ab6a2.jpg', 'Thạc sĩ Y học cổ truyền', 'Thạc sĩ y học', '2025-06-15 05:02:55', '2025-06-15 05:13:02');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `blog_categories`
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
-- Đang đổ dữ liệu cho bảng `blog_categories`
--

INSERT INTO `blog_categories` (`category_id`, `name`, `slug`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Chăm sóc sức khỏe', 'cham-soc-suc-khoe', 'Các bài viết về chăm sóc sức khỏe tổng quát', '2025-06-15 04:59:07', '2025-06-15 04:59:07'),
(2, 'Dinh dưỡng', 'dinh-duong', 'Các bài viết về dinh dưỡng và chế độ ăn uống', '2025-06-15 04:59:07', '2025-06-15 04:59:07'),
(3, 'Thể dục', 'the-duc', 'Các bài viết về thể dục và vận động', '2025-06-15 04:59:07', '2025-06-15 04:59:07'),
(4, 'Giấc ngủ', 'giac-ngu', 'Các bài viết về giấc ngủ và sức khỏe', '2025-06-15 04:59:07', '2025-06-15 04:59:07'),
(5, 'Tâm lý', 'tam-ly', 'Các bài viết về sức khỏe tâm lý', '2025-06-15 04:59:07', '2025-06-15 04:59:07'),
(6, 'Y học', 'y-hoc', 'Các bài viết về y học và điều trị', '2025-06-15 04:59:07', '2025-06-15 04:59:07');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `blog_comments`
--

CREATE TABLE `blog_comments` (
  `comment_id` int(11) NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `status` enum('pending','approved','spam') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `blog_posts`
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
-- Đang đổ dữ liệu cho bảng `blog_posts`
--

INSERT INTO `blog_posts` (`post_id`, `author_id`, `category_id`, `title`, `slug`, `content`, `excerpt`, `featured_image`, `status`, `is_featured`, `view_count`, `created_at`, `updated_at`, `published_at`) VALUES
(1, 1, 1, '10 Cách Tăng Cường Hệ Miễn Dịch Tự Nhiên', '10-cach-tang-cuong-he-mien-dich-tu-nhien', '<p>Nội dung chi tiết về cách tăng cường hệ miễn dịch...</p>', 'Khám phá những phương pháp đơn giản nhưng hiệu quả để tăng cường hệ miễn dịch của bạn thông qua chế độ ăn uống, lối sống và các hoạt động hàng ngày...', 'https://i.pinimg.com/736x/1d/c3/b9/1dc3b93ef3244a1ec7838ad6ef5fc430.jpg', 'published', 1, 0, '2025-06-15 05:02:55', '2025-06-15 05:11:51', '2025-06-15 05:02:55'),
(2, 2, 2, 'Chế Độ Ăn Uống Lành Mạnh Cho Tim Mạch', 'che-do-an-uong-lanh-manh-cho-tim-mach', '<p>Nội dung chi tiết về chế độ ăn uống tốt cho tim mạch...</p>', 'Tìm hiểu về những thực phẩm tốt cho tim mạch và cách xây dựng chế độ ăn uống khoa học...', 'https://i.pinimg.com/736x/1d/c3/b9/1dc3b93ef3244a1ec7838ad6ef5fc430.jpg', 'published', 0, 0, '2025-06-15 05:02:55', '2025-06-15 05:11:56', '2025-06-15 05:02:55'),
(3, 3, 3, 'Lợi Ích Của Việc Tập Thể Dục Đều Đặn', 'loi-ich-cua-viec-tap-the-duc-deu-dan', '<p>Nội dung chi tiết về lợi ích của tập thể dục...</p>', 'Khám phá những lợi ích tuyệt vời của việc duy trì thói quen tập luyện thể dục hàng ngày...', 'https://i.pinimg.com/736x/1d/c3/b9/1dc3b93ef3244a1ec7838ad6ef5fc430.jpg', 'published', 0, 0, '2025-06-15 05:02:55', '2025-06-15 05:11:59', '2025-06-15 05:02:55'),
(4, 4, 4, 'Tầm Quan Trọng Của Giấc Ngủ Chất Lượng', 'tam-quan-trong-cua-giac-ngu-chat-luong', '<p>Nội dung chi tiết về giấc ngủ...</p>', 'Hiểu rõ về tác động của giấc ngủ đến sức khỏe và cách cải thiện chất lượng giấc ngủ...', 'https://i.pinimg.com/736x/1d/c3/b9/1dc3b93ef3244a1ec7838ad6ef5fc430.jpg', 'published', 0, 0, '2025-06-15 05:02:55', '2025-06-15 05:12:00', '2025-06-15 05:02:55'),
(5, 5, 5, 'Quản Lý Stress Hiệu Quả Trong Cuộc Sống', 'quan-ly-stress-hieu-qua-trong-cuoc-song', '<p>Nội dung chi tiết về quản lý stress...</p>', 'Học cách nhận biết và quản lý stress để duy trì sức khỏe tinh thần tốt...', 'https://i.pinimg.com/736x/1d/c3/b9/1dc3b93ef3244a1ec7838ad6ef5fc430.jpg', 'published', 0, 0, '2025-06-15 05:02:55', '2025-06-15 05:12:02', '2025-06-15 05:02:55');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `blog_post_tags`
--

CREATE TABLE `blog_post_tags` (
  `post_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `blog_subscribers`
--

CREATE TABLE `blog_subscribers` (
  `subscriber_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `status` enum('active','unsubscribed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `blog_tags`
--

CREATE TABLE `blog_tags` (
  `tag_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `description`, `image_url`, `is_active`, `created_at`) VALUES
(1, 'Thuốc không kê đơn', 'Các loại thuốc có thể mua không cần đơn bác sĩ', '/assets/images/category-otc.jpg', 1, '2025-06-15 17:14:22'),
(2, 'Thực phẩm chức năng', 'Vitamin, khoáng chất và các chất bổ sung', '/assets/images/category-supplement.jpg', 1, '2025-06-15 17:14:22'),
(3, 'Dụng cụ y tế', 'Thiết bị và dụng cụ chăm sóc sức khỏe', '/assets/images/category-medical.jpg', 1, '2025-06-15 17:14:22'),
(4, 'Chăm sóc cá nhân', 'Sản phẩm vệ sinh và chăm sóc cá nhân', '/assets/images/category-personal.jpg', 1, '2025-06-15 17:14:22');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `clinics`
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
-- Đang đổ dữ liệu cho bảng `clinics`
--

INSERT INTO `clinics` (`clinic_id`, `name`, `address`, `phone`, `email`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Phòng khám Đa khoa QickMed', '123 Nguyễn Huệ, Quận 1, TP.HCM', '028-3822-1234', 'contact@qickmed.vn', 'Phòng khám đa khoa hiện đại với đội ngũ bác sĩ giàu kinh nghiệm', '2025-06-16 11:56:18', '2025-06-16 18:56:18'),
(2, 'Trung tâm Y tế QickMed Plus', '456 Lê Lợi, Quận 3, TP.HCM', '028-3933-5678', 'plus@qickmed.vn', 'Trung tâm y tế cao cấp với trang thiết bị hiện đại', '2025-06-16 11:56:18', '2025-06-16 18:56:18'),
(3, 'Phòng khám Chuyên khoa Tim mạch', '789 Hai Bà Trưng, Quận 1, TP.HCM', '028-3844-9012', 'cardio@qickmed.vn', 'Chuyên khoa tim mạch với các bác sĩ đầu ngành', '2025-06-16 11:56:18', '2025-06-16 18:56:18');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `doctors`
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
-- Đang đổ dữ liệu cho bảng `doctors`
--

INSERT INTO `doctors` (`doctor_id`, `user_id`, `specialty_id`, `clinic_id`, `biography`, `created_at`, `updated_at`) VALUES
(1, 7, 1, 1, 'Bác sĩ nội khoa với 15 năm kinh nghiệm, từng công tác tại nhiều bệnh viện lớn', '2025-06-16 11:56:19', '2025-06-16 18:56:19'),
(2, 8, 2, 3, 'Chuyên gia tim mạch hàng đầu, có nhiều công trình nghiên cứu quốc tế', '2025-06-16 11:56:19', '2025-06-16 18:56:19'),
(3, 9, 3, 1, 'Bác sĩ tiêu hóa giàu kinh nghiệm, chuyên điều trị các bệnh lý phức tạp', '2025-06-16 11:56:19', '2025-06-16 18:56:19'),
(4, 10, 6, 2, 'Bác sĩ nhi khoa tận tâm, được nhiều phụ huynh tin tưởng', '2025-06-16 11:56:19', '2025-06-16 18:56:19'),
(5, 11, 4, 2, 'Chuyên gia thần kinh với nhiều năm kinh nghiệm điều trị', '2025-06-16 11:56:19', '2025-06-16 18:56:19');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `doctor_schedules`
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
-- Đang đổ dữ liệu cho bảng `doctor_schedules`
--

INSERT INTO `doctor_schedules` (`schedule_id`, `doctor_id`, `clinic_id`, `day_of_week`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Monday', '08:00:00', '17:00:00', '2025-06-16 11:56:19', '2025-06-16 18:56:19'),
(2, 1, 1, 'Tuesday', '08:00:00', '17:00:00', '2025-06-16 11:56:19', '2025-06-16 18:56:19'),
(3, 1, 1, 'Wednesday', '08:00:00', '17:00:00', '2025-06-16 11:56:19', '2025-06-16 18:56:19'),
(4, 1, 1, 'Thursday', '08:00:00', '17:00:00', '2025-06-16 11:56:19', '2025-06-16 18:56:19'),
(5, 1, 1, 'Friday', '08:00:00', '17:00:00', '2025-06-16 11:56:19', '2025-06-16 18:56:19'),
(6, 2, 3, 'Monday', '09:00:00', '16:00:00', '2025-06-16 11:56:19', '2025-06-16 18:56:19'),
(7, 2, 3, 'Wednesday', '09:00:00', '16:00:00', '2025-06-16 11:56:19', '2025-06-16 18:56:19'),
(8, 2, 3, 'Friday', '09:00:00', '16:00:00', '2025-06-16 11:56:19', '2025-06-16 18:56:19'),
(9, 2, 3, 'Saturday', '08:00:00', '12:00:00', '2025-06-16 11:56:19', '2025-06-16 18:56:19'),
(10, 3, 1, 'Tuesday', '08:30:00', '17:30:00', '2025-06-16 11:56:19', '2025-06-16 18:56:19'),
(11, 3, 1, 'Thursday', '08:30:00', '17:30:00', '2025-06-16 11:56:19', '2025-06-16 18:56:19'),
(12, 3, 1, 'Saturday', '08:00:00', '12:00:00', '2025-06-16 11:56:19', '2025-06-16 18:56:19'),
(13, 4, 2, 'Monday', '08:00:00', '17:00:00', '2025-06-16 11:56:19', '2025-06-16 18:56:19'),
(14, 4, 2, 'Tuesday', '08:00:00', '17:00:00', '2025-06-16 11:56:19', '2025-06-16 18:56:19'),
(15, 4, 2, 'Wednesday', '08:00:00', '17:00:00', '2025-06-16 11:56:19', '2025-06-16 18:56:19'),
(16, 4, 2, 'Thursday', '08:00:00', '17:00:00', '2025-06-16 11:56:19', '2025-06-16 18:56:19'),
(17, 4, 2, 'Friday', '08:00:00', '17:00:00', '2025-06-16 11:56:19', '2025-06-16 18:56:19'),
(18, 5, 2, 'Monday', '09:00:00', '16:00:00', '2025-06-16 11:56:19', '2025-06-16 18:56:19'),
(19, 5, 2, 'Wednesday', '09:00:00', '16:00:00', '2025-06-16 11:56:19', '2025-06-16 18:56:19'),
(20, 5, 2, 'Friday', '09:00:00', '16:00:00', '2025-06-16 11:56:19', '2025-06-16 18:56:19');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `guest_users`
--

CREATE TABLE `guest_users` (
  `guest_id` int(11) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `medicines`
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
-- Đang đổ dữ liệu cho bảng `medicines`
--

INSERT INTO `medicines` (`medicine_id`, `active_ingredient`, `dosage_form`, `unit`, `usage_instructions`, `created_at`, `updated_at`) VALUES
(4, 'Paracetamol', 'Viên nén', 'Viên', 'Uống 1 viên/lần, 3-4 lần/ngày sau ăn', '2025-06-06 05:31:13', '2025-06-06 12:31:13'),
(5, 'Amoxicillin', 'Viên nang', 'Viên', 'Uống 1 viên/lần, 2 lần/ngày sau ăn', '2025-06-06 05:31:13', '2025-06-06 12:31:13');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
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

--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `address_id`, `shipping_address`, `total`, `payment_method`, `payment_status`, `status`, `order_note`, `order_date`, `updated_at`) VALUES
(1, 4, NULL, 'Dalziel\n\n73D Nguyễn Thượng Hiền\nBình Thạnh, Bình Thạnh, Bình Thạnh', 42500, 'cod', 'pending', 'pending', '', '2025-06-23 17:33:53', '2025-06-24 00:33:53'),
(2, 1, NULL, 'Nguyễn Văn Test\n0123456789\n123 Test Street\nPhường Test, Quận Test, TP Test', 542000, 'cod', 'pending', 'pending', 'Test checkout', '2025-06-17 06:46:11', '2025-06-17 13:46:11'),
(3, 7, NULL, NULL, 1434500, NULL, 'pending', 'cart', NULL, '2025-06-16 13:51:59', '2025-06-16 20:52:03'),
(4, 1, NULL, '123 Nguyễn Văn A, Quận 1, TP.HCM', 1800000, 'cod', 'pending', 'pending', 'Giao hàng buổi sáng', '2025-06-17 06:33:15', '2025-06-17 13:33:15'),
(5, 1, NULL, '456 Trần Hưng Đạo, Quận 5, TP.HCM', 1800000, 'vnpay', 'paid', 'shipped', 'Gọi trước khi giao', '2025-06-17 06:33:15', '2025-06-17 13:33:15'),
(6, 1, NULL, '789 Lê Văn Sỹ, Quận 3, TP.HCM', 1800000, 'cod', 'paid', 'completed', '', '2025-06-17 06:33:15', '2025-06-17 13:33:15'),
(7, 1, NULL, 'Nguyễn Văn Test\n0123456789\n123 Test Street\nPhường Bến Nghé, Quận 1, TP Hồ Chí Minh', 1240000, 'cod', 'pending', 'pending', 'Test form submission', '2025-06-17 06:55:03', '2025-06-17 13:55:03'),
(8, 1, NULL, 'Nguyễn Văn Test\n0123456789\n123 Test Street\nPhường Bến Nghé, Quận 1, TP Hồ Chí Minh', 1500000, 'cod', 'pending', 'pending', 'Test form submission', '2025-06-17 06:55:48', '2025-06-17 13:55:48'),
(9, 1, NULL, '73D Nguyễn Thượng Hiền\nPhường 5, Quận Bình Thạnh, Thành phố Hồ Chí Minh', 42500, 'cod', 'pending', 'cancelled', '', '2025-06-17 06:57:51', '2025-06-24 00:08:26'),
(10, 1, NULL, '73D Nguyễn Thượng Hiền\nPhường 5, Quận Bình Thạnh, Thành phố Hồ Chí Minh', 542000, 'cod', 'pending', 'pending', '', '2025-06-23 17:11:54', '2025-06-24 00:11:54'),
(11, 1, NULL, '73D Nguyễn Thượng Hiền\nPhường 5, Quận Bình Thạnh, Thành phố Hồ Chí Minh', 42500, 'cod', 'pending', 'pending', '', '2025-06-23 17:12:19', '2025-06-24 00:12:19'),
(12, 1, NULL, '73D Nguyễn Thượng Hiền\nPhường 5, Quận Bình Thạnh, Thành phố Hồ Chí Minh', 542000, 'cod', 'pending', 'pending', '', '2025-06-23 17:16:54', '2025-06-24 00:16:54'),
(13, 1, NULL, '73D Nguyễn Thượng Hiền\nPhường 5, Quận Bình Thạnh, Thành phố Hồ Chí Minh', 542000, 'cod', 'pending', 'pending', '', '2025-06-23 17:18:40', '2025-06-24 00:18:40'),
(14, 1, NULL, '73D Nguyễn Thượng Hiền\nPhường 5, Quận Bình Thạnh, Thành phố Hồ Chí Minh', 542000, 'cod', 'pending', 'pending', '', '2025-06-23 17:20:05', '2025-06-24 00:20:05'),
(15, 1, NULL, NULL, 522000, NULL, 'pending', 'cart', NULL, '2025-06-23 17:20:23', '2025-06-24 00:20:23'),
(16, 4, NULL, 'Dalziel\n424323232\n73D Nguyễn Thượng Hiềnssss\nPhường Đức Giang, Quận Long Biên, Thành phố Hà Nội', 51500, 'cod', 'pending', 'pending', '', '2025-06-23 17:37:14', '2025-06-24 00:37:14'),
(17, 4, NULL, 'Dalziel\n11111111\n73D Nguyễn Thượng Hiền\nBình Thạnh, Bình Thạnh, Bình Thạnh', 110000, 'cod', 'pending', 'pending', '', '2025-06-23 17:48:00', '2025-06-24 00:48:00');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_items`
--

CREATE TABLE `order_items` (
  `item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(16,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `order_items`
--

INSERT INTO `order_items` (`item_id`, `order_id`, `product_id`, `quantity`, `unit_price`) VALUES
(34, 3, 9, 1, 31500),
(35, 3, 8, 1, 45000),
(36, 3, 7, 1, 108000),
(37, 3, 6, 1, 1250000),
(38, 2, 2, 1, 522000),
(39, 4, 1, 2, 320000),
(40, 4, 2, 2, 580000),
(41, 5, 1, 2, 320000),
(42, 5, 2, 2, 580000),
(43, 6, 1, 2, 320000),
(44, 6, 2, 2, 580000),
(45, 7, 1, 2, 320000),
(46, 7, 2, 1, 580000),
(47, 8, 1, 1, 320000),
(48, 8, 2, 2, 580000),
(49, 9, 4, 1, 22500),
(51, 1, 4, 1, 22500),
(52, 10, 2, 1, 522000),
(53, 11, 4, 1, 22500),
(54, 12, 2, 1, 522000),
(55, 13, 2, 1, 522000),
(56, 14, 2, 1, 522000),
(57, 15, 2, 1, 522000),
(58, 16, 9, 1, 31500),
(60, 17, 4, 4, 22500);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `package_features`
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
-- Đang đổ dữ liệu cho bảng `package_features`
--

INSERT INTO `package_features` (`id`, `package_id`, `feature_name`, `description`, `display_order`, `created_at`) VALUES
(1, 1, 'Khám lâm sàng tổng quát', NULL, 0, '2025-06-04 05:55:25'),
(2, 1, 'Xét nghiệm máu cơ bản', NULL, 0, '2025-06-04 05:55:25'),
(3, 1, 'Xét nghiệm nước tiểu', NULL, 0, '2025-06-04 05:55:25'),
(4, 1, 'X-quang phổi', NULL, 0, '2025-06-04 05:55:25'),
(5, 1, 'Điện tim', NULL, 0, '2025-06-04 05:55:25'),
(6, 1, 'Tư vấn kết quả', NULL, 0, '2025-06-04 05:55:25'),
(7, 2, 'Tất cả gói cơ bản', NULL, 0, '2025-06-04 05:55:25'),
(8, 2, 'Siêu âm bụng tổng quát', NULL, 0, '2025-06-04 05:55:25'),
(9, 2, 'Siêu âm tim', NULL, 0, '2025-06-04 05:55:25');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payments`
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
-- Cấu trúc bảng cho bảng `prescriptions`
--

CREATE TABLE `prescriptions` (
  `prescription_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `prescription_products`
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

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(16,0) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `image_url` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`product_id`, `category_id`, `name`, `description`, `price`, `stock`, `image_url`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Vitamin C 1000mg', 'Bổ sung Vitamin C tăng cường đề kháng', 320000, 96, 'https://i.pinimg.com/736x/9e/49/16/9e4916dcd98161b44c43f895318979a2.jpg', 1, '2025-06-06 05:31:13', '2025-06-17 13:55:48'),
(2, 1, 'Omega 3 Fish Oil', 'Dầu cá omega 3 hỗ trợ tim mạch', 580000, 40, 'https://i.pinimg.com/736x/9e/49/16/9e4916dcd98161b44c43f895318979a2.jpg', 1, '2025-06-06 05:31:13', '2025-06-24 00:20:05'),
(3, 1, 'Calcium D3', 'Bổ sung canxi và vitamin D3', 250000, 80, 'https://i.pinimg.com/736x/9e/49/16/9e4916dcd98161b44c43f895318979a2.jpg', 1, '2025-06-06 05:31:13', '2025-06-09 10:54:53'),
(4, 2, 'Paracetamol 500mg', 'Thuốc hạ sốt, giảm đau', 25000, 193, 'https://i.pinimg.com/736x/9e/49/16/9e4916dcd98161b44c43f895318979a2.jpg', 1, '2025-06-06 05:31:13', '2025-06-24 00:48:00'),
(5, 2, 'Amoxicillin 500mg', 'Kháng sinh điều trị nhiễm khuẩn', 45000, 150, 'https://i.pinimg.com/736x/9e/49/16/9e4916dcd98161b44c43f895318979a2.jpg', 1, '2025-06-06 05:31:13', '2025-06-09 10:54:56'),
(6, 3, 'Máy đo huyết áp Omron', 'Máy đo huyết áp tự động, độ chính xác cao', 1250000, 30, 'https://i.pinimg.com/736x/9e/49/16/9e4916dcd98161b44c43f895318979a2.jpg', 1, '2025-06-06 05:31:13', '2025-06-09 10:54:57'),
(7, 3, 'Nhiệt kế điện tử', 'Nhiệt kế đo nhiệt độ nhanh chóng', 120000, 60, 'https://i.pinimg.com/736x/9e/49/16/9e4916dcd98161b44c43f895318979a2.jpg', 1, '2025-06-06 05:31:13', '2025-06-09 10:54:59'),
(8, 4, 'Dung dịch sát khuẩn', 'Dung dịch sát khuẩn tay nhanh', 45000, 100, 'https://i.pinimg.com/736x/9e/49/16/9e4916dcd98161b44c43f895318979a2.jpg', 1, '2025-06-06 05:31:13', '2025-06-09 10:55:01'),
(9, 4, 'Băng gạc y tế', 'Băng gạc vô trùng cao cấp', 35000, 199, 'https://i.pinimg.com/736x/9e/49/16/9e4916dcd98161b44c43f895318979a2.jpg', 1, '2025-06-06 05:31:13', '2025-06-24 00:37:14');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_categories`
--

CREATE TABLE `product_categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `product_categories`
--

INSERT INTO `product_categories` (`category_id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Thực phẩm chức năng', 'Vitamin, khoáng chất và các loại thực phẩm bổ sung sức khỏe', '2025-06-06 05:31:13', '2025-06-06 12:31:13'),
(2, 'Thuốc', 'Thuốc kê đơn và không kê đơn từ các nhà sản xuất uy tín', '2025-06-06 05:31:13', '2025-06-06 12:31:13'),
(3, 'Thiết bị y tế', 'Máy đo huyết áp, nhiệt kế và các thiết bị y tế gia đình', '2025-06-06 05:31:13', '2025-06-06 12:31:13'),
(4, 'Dược phẩm', 'Các sản phẩm dược phẩm chuyên dụng và thuốc đặc trị', '2025-06-06 05:31:13', '2025-06-06 12:31:13');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_reviews`
--

CREATE TABLE `product_reviews` (
  `review_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `product_reviews`
--

INSERT INTO `product_reviews` (`review_id`, `product_id`, `rating`, `comment`, `created_at`, `updated_at`) VALUES
(1, 1, 5, 'Sản phẩm rất tốt, uống thấy khỏe hơn hẳn', '2025-06-06 05:31:13', '2025-06-06 12:31:13'),
(2, 1, 4, 'Giá hơi cao nhưng chất lượng tốt', '2025-06-06 05:31:13', '2025-06-06 12:31:13'),
(3, 2, 5, 'Dầu cá chất lượng, không tanh', '2025-06-06 05:31:13', '2025-06-06 12:31:13'),
(4, 3, 4, 'Viên nén dễ uống, giá cả hợp lý', '2025-06-06 05:31:13', '2025-06-06 12:31:13'),
(5, 4, 5, 'Thuốc tốt, giảm đau nhanh', '2025-06-06 05:31:13', '2025-06-06 12:31:13'),
(6, 5, 4, 'Hiệu quả trong điều trị', '2025-06-06 05:31:13', '2025-06-06 12:31:13'),
(7, 6, 4, 'Máy đo chính xác, dễ sử dụng', '2025-06-06 05:31:13', '2025-06-06 12:31:13'),
(8, 7, 5, 'Nhiệt kế đo nhanh và chính xác', '2025-06-06 05:31:13', '2025-06-06 12:31:13'),
(9, 8, 4, 'Sát khuẩn tốt, thơm nhẹ', '2025-06-06 05:31:13', '2025-06-06 12:31:13'),
(10, 9, 5, 'Băng gạc mềm, thấm hút tốt', '2025-06-06 05:31:13', '2025-06-06 12:31:13');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `remember_tokens`
--

INSERT INTO `remember_tokens` (`id`, `user_id`, `token`, `expires_at`, `created_at`) VALUES
(1, 1, '218a2b8624b702708e5c1a1e6b80249c6f630f19191f332f4d7034ae9782ed99', '2025-07-17 12:06:39', '2025-06-17 05:06:39');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `description`) VALUES
(1, 'admin', 'Quản trị viên hệ thống'),
(2, 'patient', 'Bệnh nhân'),
(3, 'doctor', 'Bác sĩ');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `services`
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
  `price_from` decimal(12,2) DEFAULT NULL,
  `price_to` decimal(12,2) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_emergency` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `services`
--

INSERT INTO `services` (`id`, `category_id`, `name`, `slug`, `short_description`, `full_description`, `icon`, `image`, `price_from`, `price_to`, `is_featured`, `is_emergency`, `is_active`, `display_order`, `created_at`, `updated_at`) VALUES
(1, 1, 'Khám Tổng Quát', 'kham-tong-quat', 'Khám sức khỏe định kỳ và tầm soát các bệnh lý thường gặp', NULL, NULL, NULL, 200000.00, 500000.00, 0, 0, 1, 0, '2025-06-04 05:55:25', '2025-06-04 05:55:25'),
(2, 2, 'Khám Tim Mạch', 'kham-tim-mach', 'Chẩn đoán và điều trị các bệnh lý tim mạch với trang thiết bị hiện đại', NULL, NULL, NULL, 300000.00, 2000000.00, 1, 0, 1, 0, '2025-06-04 05:55:25', '2025-06-04 05:55:25'),
(3, 3, 'Khám Tiêu Hóa', 'kham-tieu-hoa', 'Chẩn đoán và điều trị các bệnh lý về đường tiêu hóa, gan mật', NULL, NULL, NULL, 250000.00, 1500000.00, 0, 0, 1, 0, '2025-06-04 05:55:25', '2025-06-04 05:55:25'),
(4, 6, 'Dịch Vụ Cấp Cứu', 'dich-vu-cap-cuu', 'Dịch vụ cấp cứu 24/7 với đội ngũ y bác sĩ luôn sẵn sàng', NULL, NULL, NULL, NULL, NULL, 0, 1, 1, 0, '2025-06-04 05:55:25', '2025-06-04 05:55:25');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `service_categories`
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
-- Đang đổ dữ liệu cho bảng `service_categories`
--

INSERT INTO `service_categories` (`id`, `name`, `slug`, `icon`, `description`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Khám Tổng Quát', 'kham-tong-quat', 'fas fa-stethoscope', 'Dịch vụ khám sức khỏe tổng quát và tầm soát bệnh', 0, 1, '2025-06-04 05:55:25', '2025-06-04 05:55:25'),
(2, 'Tim Mạch', 'tim-mach', 'fas fa-heartbeat', 'Chẩn đoán và điều trị các bệnh lý tim mạch', 0, 1, '2025-06-04 05:55:25', '2025-06-04 05:55:25'),
(3, 'Tiêu Hóa', 'tieu-hoa', 'fas fa-prescription-bottle-alt', 'Điều trị các bệnh về đường tiêu hóa', 0, 1, '2025-06-04 05:55:25', '2025-06-04 05:55:25'),
(4, 'Thần Kinh', 'than-kinh', 'fas fa-brain', 'Điều trị các bệnh lý thần kinh', 0, 1, '2025-06-04 05:55:25', '2025-06-04 05:55:25'),
(5, 'Chấn Thương Chỉnh Hình', 'chan-thuong-chinh-hinh', 'fas fa-bone', 'Điều trị chấn thương và bệnh lý xương khớp', 0, 1, '2025-06-04 05:55:25', '2025-06-04 05:55:25'),
(6, 'Cấp Cứu', 'cap-cuu', 'fas fa-ambulance', 'Dịch vụ cấp cứu 24/7', 0, 1, '2025-06-04 05:55:25', '2025-06-04 05:55:25');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `service_features`
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
-- Đang đổ dữ liệu cho bảng `service_features`
--

INSERT INTO `service_features` (`id`, `service_id`, `feature_name`, `description`, `icon`, `display_order`, `created_at`) VALUES
(1, 1, 'Khám lâm sàng toàn diện', NULL, NULL, 0, '2025-06-04 05:55:25'),
(2, 1, 'Xét nghiệm máu cơ bản', NULL, NULL, 0, '2025-06-04 05:55:25'),
(3, 1, 'Đo huyết áp, nhịp tim', NULL, NULL, 0, '2025-06-04 05:55:25'),
(4, 1, 'Tư vấn dinh dưỡng', NULL, NULL, 0, '2025-06-04 05:55:25'),
(5, 2, 'Siêu âm tim', NULL, NULL, 0, '2025-06-04 05:55:25'),
(6, 2, 'Điện tim', NULL, NULL, 0, '2025-06-04 05:55:25'),
(7, 2, 'Holter 24h', NULL, NULL, 0, '2025-06-04 05:55:25'),
(8, 2, 'Thăm dò chức năng tim', NULL, NULL, 0, '2025-06-04 05:55:25');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `service_packages`
--

CREATE TABLE `service_packages` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(12,2) DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `service_packages`
--

INSERT INTO `service_packages` (`id`, `name`, `slug`, `description`, `price`, `duration`, `is_featured`, `is_active`, `display_order`, `created_at`, `updated_at`) VALUES
(1, 'Gói Cơ Bản', 'goi-co-ban', 'Gói khám sức khỏe cơ bản', 1500000.00, '/lần', 0, 1, 0, '2025-06-04 05:55:25', '2025-06-04 05:55:25'),
(2, 'Gói Nâng Cao', 'goi-nang-cao', 'Gói khám sức khỏe nâng cao', 3500000.00, '/lần', 1, 1, 0, '2025-06-04 05:55:25', '2025-06-04 05:55:25'),
(3, 'Gói Cao Cấp', 'goi-cao-cap', 'Gói khám sức khỏe cao cấp', 6500000.00, '/lần', 0, 1, 0, '2025-06-04 05:55:25', '2025-06-04 05:55:25');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `specialties`
--

CREATE TABLE `specialties` (
  `specialty_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `specialties`
--

INSERT INTO `specialties` (`specialty_id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Nội khoa', 'Chuyên khoa điều trị các bệnh lý nội tạng', '2025-06-16 11:56:18', '2025-06-16 18:56:18'),
(2, 'Tim mạch', 'Chuyên khoa tim mạch và mạch máu', '2025-06-16 11:56:18', '2025-06-16 18:56:18'),
(3, 'Tiêu hóa', 'Chuyên khoa tiêu hóa và gan mật', '2025-06-16 11:56:18', '2025-06-16 18:56:18'),
(4, 'Thần kinh', 'Chuyên khoa thần kinh và tâm thần', '2025-06-16 11:56:18', '2025-06-16 18:56:18'),
(5, 'Da liễu', 'Chuyên khoa da liễu và thẩm mỹ', '2025-06-16 11:56:18', '2025-06-16 18:56:18'),
(6, 'Nhi khoa', 'Chuyên khoa nhi - sức khỏe trẻ em', '2025-06-16 11:56:18', '2025-06-16 18:56:18'),
(7, 'Sản phụ khoa', 'Chuyên khoa sản phụ khoa', '2025-06-16 11:56:18', '2025-06-16 18:56:18'),
(8, 'Răng hàm mặt', 'Chuyên khoa răng hàm mặt', '2025-06-16 11:56:18', '2025-06-16 18:56:18'),
(9, 'Mắt', 'Chuyên khoa mắt', '2025-06-16 11:56:18', '2025-06-16 18:56:18'),
(10, 'Tai mũi họng', 'Chuyên khoa tai mũi họng', '2025-06-16 11:56:18', '2025-06-16 18:56:18');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL DEFAULT 2,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','inactive','suspended') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `phone_number`, `password`, `role_id`, `created_at`, `updated_at`, `status`) VALUES
(1, 'admin', 'admin@qickmed.com', '0354095219', 'admin123', 1, '2025-05-28 10:38:18', '2025-06-23 17:26:20', 'active'),
(4, 'dalzielsky', 'dvtdang11201@gmail.com', '11111111', '11012003', 2, '2025-05-30 16:31:12', '2025-06-23 17:36:57', 'active'),
(7, 'dr_nguyenvana', 'nguyenvana@qickmed.vn', NULL, '1', 3, '2025-06-16 11:56:18', '2025-06-16 13:50:09', 'active'),
(8, 'dr_tranthib', 'tranthib@qickmed.vn', NULL, 'password123', 3, '2025-06-16 11:56:18', '2025-06-16 11:56:18', 'active'),
(9, 'dr_levantam', 'levantam@qickmed.vn', NULL, 'password123', 3, '2025-06-16 11:56:18', '2025-06-16 11:56:18', 'active'),
(10, 'dr_hoangthimai', 'hoangthimai@qickmed.vn', NULL, 'password123', 3, '2025-06-16 11:56:18', '2025-06-16 11:56:18', 'active'),
(11, 'dr_phamvanminh', 'phamvanminh@qickmed.vn', NULL, 'password123', 3, '2025-06-16 11:56:18', '2025-06-16 11:56:18', 'active'),
(12, 'admin22', 'admin@themesbrand.com', '09777313131', '11012003', 2, '2025-06-23 17:49:00', '2025-06-23 17:49:00', 'active');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users_info`
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
-- Đang đổ dữ liệu cho bảng `users_info`
--

INSERT INTO `users_info` (`id`, `user_id`, `full_name`, `gender`, `date_of_birth`, `profile_picture`, `created_at`, `updated_at`) VALUES
(1, 1, 'Quản trị viên', 'Khác', '0000-00-00', NULL, '2025-05-28 10:38:18', '2025-06-23 17:26:20'),
(3, 4, 'Dalziel', 'Nam', '2016-05-15', NULL, '2025-05-30 16:31:12', '2025-06-15 16:16:27'),
(6, 7, 'BS. Nguyễn Văn A', 'Nam', '1980-05-15', 'assets\\images\\default-doctor.jpg', '2025-06-16 11:56:19', '2025-06-16 13:49:34'),
(7, 8, 'BS. Trần Thị B', 'Nữ', '1985-08-22', 'assets\\images\\default-doctor.jpg', '2025-06-16 11:56:19', '2025-06-16 13:49:43'),
(8, 9, 'BS. Lê Văn Tâm', 'Nam', '1978-12-10', 'assets\\images\\default-doctor.jpg', '2025-06-16 11:56:19', '2025-06-16 13:49:45'),
(9, 10, 'BS. Hoàng Thị Mai', 'Nữ', '1982-03-18', 'assets\\images\\default-doctor.jpg', '2025-06-16 11:56:19', '2025-06-16 13:49:46'),
(10, 11, 'BS. Phạm Văn Minh', 'Nam', '1975-09-25', 'assets\\images\\default-doctor.jpg', '2025-06-16 11:56:19', '2025-06-16 13:49:48'),
(11, 12, 'Dalziel', 'Nam', NULL, NULL, '2025-06-23 17:49:00', '2025-06-23 17:49:00');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_addresses`
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
-- Đang đổ dữ liệu cho bảng `user_addresses`
--

INSERT INTO `user_addresses` (`id`, `user_id`, `address_line`, `ward`, `district`, `city`, `postal_code`, `country`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 4, '73D Nguyễn Thượng Hiền', 'Bình Thạnh', 'Bình Thạnh', 'Bình Thạnh', '888888', 'Vietnam', 1, '2025-06-15 23:15:26', '2025-06-15 23:15:26'),
(2, 1, '73D Nguyễn Thượng Hiền', 'Phường 5', 'Quận Bình Thạnh', 'Thành phố Hồ Chí Minh', '', 'Vietnam', 1, '2025-06-17 12:27:49', '2025-06-17 12:27:49'),
(3, 12, '73D Nguyễn Thượng Hiền', 'Xã Bình Hòa', 'Huyện Bình Sơn', 'Tỉnh Quảng Ngãi', '', 'Vietnam', 1, '2025-06-24 01:01:56', '2025-06-24 01:01:56');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `guest_id` (`guest_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `clinic_id` (`clinic_id`);

--
-- Chỉ mục cho bảng `blog_authors`
--
ALTER TABLE `blog_authors`
  ADD PRIMARY KEY (`author_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `blog_categories`
--
ALTER TABLE `blog_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Chỉ mục cho bảng `blog_comments`
--
ALTER TABLE `blog_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Chỉ mục cho bảng `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD PRIMARY KEY (`post_id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Chỉ mục cho bảng `blog_post_tags`
--
ALTER TABLE `blog_post_tags`
  ADD PRIMARY KEY (`post_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Chỉ mục cho bảng `blog_subscribers`
--
ALTER TABLE `blog_subscribers`
  ADD PRIMARY KEY (`subscriber_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Chỉ mục cho bảng `blog_tags`
--
ALTER TABLE `blog_tags`
  ADD PRIMARY KEY (`tag_id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Chỉ mục cho bảng `clinics`
--
ALTER TABLE `clinics`
  ADD PRIMARY KEY (`clinic_id`);

--
-- Chỉ mục cho bảng `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`doctor_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `specialty_id` (`specialty_id`),
  ADD KEY `clinic_id` (`clinic_id`);

--
-- Chỉ mục cho bảng `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `clinic_id` (`clinic_id`);

--
-- Chỉ mục cho bảng `guest_users`
--
ALTER TABLE `guest_users`
  ADD PRIMARY KEY (`guest_id`);

--
-- Chỉ mục cho bảng `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`medicine_id`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `address_id` (`address_id`);

--
-- Chỉ mục cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `package_features`
--
ALTER TABLE `package_features`
  ADD PRIMARY KEY (`id`),
  ADD KEY `package_id` (`package_id`);

--
-- Chỉ mục cho bảng `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`prescription_id`);

--
-- Chỉ mục cho bảng `prescription_products`
--
ALTER TABLE `prescription_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prescription_id` (`prescription_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Chỉ mục cho bảng `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Chỉ mục cho bảng `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_token` (`user_id`);

--
-- Chỉ mục cho bảng `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Chỉ mục cho bảng `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `category_id` (`category_id`);

--
-- Chỉ mục cho bảng `service_categories`
--
ALTER TABLE `service_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Chỉ mục cho bảng `service_features`
--
ALTER TABLE `service_features`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`);

--
-- Chỉ mục cho bảng `service_packages`
--
ALTER TABLE `service_packages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Chỉ mục cho bảng `specialties`
--
ALTER TABLE `specialties`
  ADD PRIMARY KEY (`specialty_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone_number` (`phone_number`),
  ADD KEY `role_id` (`role_id`);

--
-- Chỉ mục cho bảng `users_info`
--
ALTER TABLE `users_info`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `blog_authors`
--
ALTER TABLE `blog_authors`
  MODIFY `author_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `blog_categories`
--
ALTER TABLE `blog_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `blog_comments`
--
ALTER TABLE `blog_comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `blog_posts`
--
ALTER TABLE `blog_posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `blog_subscribers`
--
ALTER TABLE `blog_subscribers`
  MODIFY `subscriber_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `blog_tags`
--
ALTER TABLE `blog_tags`
  MODIFY `tag_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `clinics`
--
ALTER TABLE `clinics`
  MODIFY `clinic_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `doctors`
--
ALTER TABLE `doctors`
  MODIFY `doctor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT cho bảng `guest_users`
--
ALTER TABLE `guest_users`
  MODIFY `guest_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT cho bảng `order_items`
--
ALTER TABLE `order_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT cho bảng `package_features`
--
ALTER TABLE `package_features`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `prescription_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `prescription_products`
--
ALTER TABLE `prescription_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `service_categories`
--
ALTER TABLE `service_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `service_features`
--
ALTER TABLE `service_features`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `service_packages`
--
ALTER TABLE `service_packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `specialties`
--
ALTER TABLE `specialties`
  MODIFY `specialty_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `users_info`
--
ALTER TABLE `users_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`guest_id`) REFERENCES `guest_users` (`guest_id`),
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`),
  ADD CONSTRAINT `appointments_ibfk_4` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`);

--
-- Các ràng buộc cho bảng `blog_authors`
--
ALTER TABLE `blog_authors`
  ADD CONSTRAINT `blog_authors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `blog_comments`
--
ALTER TABLE `blog_comments`
  ADD CONSTRAINT `blog_comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `blog_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `blog_comments_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `blog_comments` (`comment_id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD CONSTRAINT `blog_posts_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `blog_authors` (`author_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `blog_posts_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`category_id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `blog_post_tags`
--
ALTER TABLE `blog_post_tags`
  ADD CONSTRAINT `blog_post_tags_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `blog_post_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `blog_tags` (`tag_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `doctors_ibfk_2` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`specialty_id`),
  ADD CONSTRAINT `doctors_ibfk_3` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`);

--
-- Các ràng buộc cho bảng `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD CONSTRAINT `doctor_schedules_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`),
  ADD CONSTRAINT `doctor_schedules_ibfk_2` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`);

--
-- Các ràng buộc cho bảng `medicines`
--
ALTER TABLE `medicines`
  ADD CONSTRAINT `medicines_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`address_id`) REFERENCES `user_addresses` (`id`);

--
-- Các ràng buộc cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Các ràng buộc cho bảng `package_features`
--
ALTER TABLE `package_features`
  ADD CONSTRAINT `package_features_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `service_packages` (`id`);

--
-- Các ràng buộc cho bảng `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Các ràng buộc cho bảng `prescription_products`
--
ALTER TABLE `prescription_products`
  ADD CONSTRAINT `prescription_products_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`prescription_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescription_products_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`category_id`);

--
-- Các ràng buộc cho bảng `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Các ràng buộc cho bảng `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `service_categories` (`id`);

--
-- Các ràng buộc cho bảng `service_features`
--
ALTER TABLE `service_features`
  ADD CONSTRAINT `service_features_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`);

--
-- Các ràng buộc cho bảng `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);

--
-- Các ràng buộc cho bảng `users_info`
--
ALTER TABLE `users_info`
  ADD CONSTRAINT `users_info_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
