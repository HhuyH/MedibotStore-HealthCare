# 📎 HỆ THỐNG GHI LOG NÂNG CAO - MediSync

## TỔNG QUAN

Hệ thống ghi log nâng cao giúp cải thiện khả năng đọc và sự thân thiện với người dùng trong hệ thống Quản Lý Bệnh Viện MediSync. Nó thay thế hệ thống log cơ bản bằng các file log có biểu tượng emoji sinh động, cấu trúc rõ ràng, dễ hiểu hơn.

## 🔒 TÍNH NĂNG BẢO MẬT

- **Thư mục Log được bảo vệ**: Thư mục `/logs` được đặt `.htaccess` ngăn truy cập trực tiếp.
- **Chỉ Admin truy cập**: Admin mới có quyền xem log qua `/admin/activity-log.php`.
- **Theo dõi IP**: Phát hiện IP để giám sát an ninh.

## 📋 CÁC LOẠI LOG

- **Xác thực**: Đăng nhập/đăng xuất/đăng ký
- **Giỏ hàng**: Thêm/xóa/cập nhật sản phẩm
- **API**: REST API request/response
- **Lịch hẹn**: Đặt, hủy, đổi lịch
- **Đơn hàng**: Mua, thanh toán, giao hàng
- **Email**: Gửi email và trạng thái
- **Hệ thống**: Bảo trì, sao lưu
- **Bảo mật**: Cảnh báo, xâm nhập
- **DB**: Thao tác với DB
- **Lỗi**: Ghi lỗi có bổ sung ngữ cảnh

## ✏️ CÁCH Sử DỤNG

### Thên file logger:

```php
require_once 'includes/functions/enhanced_logger.php';
```

### Ghi log cơ bản:

```php
EnhancedLogger::writeLog("Người dùng vừa thực hiện hành động", 'INFO', 'system');
```

### Ghi log xác thực:

```php
EnhancedLogger::logAuth('login', 'john_doe', true);
```

### Ghi giỏ hàng:

```php
EnhancedLogger::logCart('ADD_TO_CART', 123, 'Paracetamol', 2);
```

### Ghi API:

```php
EnhancedLogger::logAPI('/api/add', 'POST', ['id'=>123], ['success'=>true], 200);
```

### Ghi lịch hẹn:

```php
EnhancedLogger::logAppointment('BOOK_APPOINTMENT', 456, 'Nguyen Van A', 'Dr. Smith');
```

### Ghi đơn hàng:

```php
EnhancedLogger::logOrder('CREATE_ORDER', 789, 150000);
```

### Ghi email:

```php
EnhancedLogger::logEmail('SEND_EMAIL', 'user@example.com', 'Subject', true);
```

### Ghi sự kiện hệ thống:

```php
EnhancedLogger::logSystem('MAINTENANCE_MODE', 'System maintenance');
```

### Ghi lỗi:

```php
EnhancedLogger::logError('DB connect fail', 'MySQL', $ex->getTraceAsString());
```

### Ghi bảo mật:

```php
EnhancedLogger::logSecurity('BRUTE_FORCE', 'Nhiều lần sai pass', 'HIGH');
```

## 📅 ĐỊNH DẠNG LOG

```
[2025-01-15 10:30:45] [AUTH] [User: john_doe (ID: 123)] [IP: 192.168.1.100] [Page: login.php] ✓ john_doe đăng nhập thành công
```

## 📆 TRANG QUẢN LÝ ADMIN

- **URL**: `/admin/activity-log.php`
- **Chỉ admin xem log**
- **Tính năng**:

  - Xem log theo thời gian thật
  - Lọc theo ngày/loại log
  - Tìm kiếm
  - Phân trang
  - Thống kê nhanh

## 🔐 BẢO MẬT

- Thư mục logs bị chặn truy cập
- Chỉ admin được xem log
- IP filtering (tuỳ chọn)
- Ghi log theo ngày, dễ dàng quản lý

## 🛠️ DI CHUYỂN Từa HỆ THỐNG CŨ

- Các hàm log cũ vẫn dùng được: `writeLog()`, `logAPI()`, `logError()`
- Có thể di chuyển dần dần về hệ thống mới

## 🔁 CÁU TRÚC FILE LOG

```
logs/
├── .htaccess
├── authentication_2025-01-15.log
├── cart_actions_2025-01-15.log
├── api_calls_2025-01-15.log
├── appointments_2025-01-15.log
├── orders_2025-01-15.log
├── email_activities_2025-01-15.log
├── system_events_2025-01-15.log
├── security_events_2025-01-15.log
├── database_operations_2025-01-15.log
└── errors_2025-01-15.log
```

## ⚠️ Xử LÝ SỰ CỐ

1. **Không ghi được log**: Kiểm tra quyền ghi file/thư mục
2. **Admin bị từ chối**: Kiểm tra quyền người dùng
3. **.htaccess không hiệu lực**: Kiểm tra cài đặt Apache

> ✅ Hệ thống log nâng cao giúp theo dõi toàn diện, dễ bảo trì, và tăng cường an ninh cho MediSync.
