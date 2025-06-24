-- MediSync Database Setup
-- Run this SQL to create the required tables

-- Create roles table (if not exists)
CREATE TABLE IF NOT EXISTS `roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default roles
INSERT IGNORE INTO `roles` (`role_id`, `name`, `description`) VALUES
(1, 'Administrator', 'Quản trị viên hệ thống'),
(2, 'Patient', 'Bệnh nhân'),
(3, 'Doctor', 'Bác sĩ');

-- Create products table
CREATE TABLE IF NOT EXISTS `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `category` varchar(100),
  `image` varchar(255),
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create orders table
CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','completed','cancelled') DEFAULT 'pending',
  `shipping_address` text,
  `payment_method` varchar(50),
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create order_items table
CREATE TABLE IF NOT EXISTS `order_items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  PRIMARY KEY (`item_id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create services table (if not exists)
CREATE TABLE IF NOT EXISTS `services` (
  `service_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `duration` int(11) DEFAULT 30,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create users_info table (if not exists)
CREATE TABLE IF NOT EXISTS `users_info` (
  `info_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(255),
  `phone` varchar(20),
  `address` text,
  `date_of_birth` date,
  `gender` enum('male','female','other'),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`info_id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Update appointments table structure (if needed)
ALTER TABLE `appointments` 
ADD COLUMN IF NOT EXISTS `patient_id` int(11) AFTER `user_id`,
ADD COLUMN IF NOT EXISTS `doctor_id` int(11) AFTER `patient_id`,
ADD COLUMN IF NOT EXISTS `service_id` int(11) AFTER `doctor_id`;

-- Insert sample data for testing
INSERT IGNORE INTO `services` (`name`, `description`, `price`) VALUES
('Khám tổng quát', 'Khám sức khỏe tổng quát', 200000),
('Khám chuyên khoa', 'Khám chuyên khoa theo yêu cầu', 300000),
('Xét nghiệm máu', 'Xét nghiệm máu cơ bản', 150000),
('Siêu âm', 'Siêu âm tổng quát', 250000);

INSERT IGNORE INTO `products` (`name`, `description`, `price`, `stock_quantity`, `category`) VALUES
('Paracetamol 500mg', 'Thuốc giảm đau, hạ sốt', 25000, 100, 'Thuốc giảm đau'),
('Vitamin C 1000mg', 'Bổ sung vitamin C', 150000, 50, 'Vitamin'),
('Khẩu trang y tế', 'Khẩu trang y tế 3 lớp', 50000, 200, 'Vật tư y tế'),
('Nhiệt kế điện tử', 'Nhiệt kế đo nhiệt độ cơ thể', 200000, 30, 'Thiết bị y tế');

-- Add foreign key constraints
ALTER TABLE `orders` 
ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `order_items` 
ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

ALTER TABLE `users_info` 
ADD CONSTRAINT `fk_users_info_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE; 