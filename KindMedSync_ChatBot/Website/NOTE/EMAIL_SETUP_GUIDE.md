# Hướng dẫn cài đặt hệ thống Email - QuickMed Hospital

## 🚀 Tổng quan

Hệ thống email tự động sẽ gửi thông báo qua email khi:

- ✅ **Đăng ký tài khoản thành công**
- ✅ **Tạo lịch hẹn khám bệnh**
- ✅ **Đặt hàng thành công**

## 📋 Cài đặt ban đầu

### 1. Chạy file SQL để tạo bảng

```sql
-- Chạy file này trong phpMyAdmin hoặc MySQL
mysql -u root -p your_database < database/email_settings.sql
```

Hoặc vào phpMyAdmin và import file `database/email_settings.sql`

### 2. Cấu hình SMTP

Truy cập: `http://localhost/admin/settings.php`

**Với Gmail:**

- SMTP Host: `smtp.gmail.com`
- SMTP Port: `587`
- Username: `your-gmail@gmail.com`
- Password: `App Password` (không phải password Gmail)
- Encryption: `TLS`

**Với Yahoo:**

- SMTP Host: `smtp.mail.yahoo.com`
- SMTP Port: `587`
- Username: `your-yahoo@yahoo.com`
- Password: `App Password`
- Encryption: `TLS`

**Với Outlook:**

- SMTP Host: `smtp-mail.outlook.com`
- SMTP Port: `587`
- Username: `your-outlook@outlook.com`
- Password: `App Password`
- Encryption: `TLS`

### 3. Lấy App Password cho Gmail

1. Vào Google Account Settings
2. Bật 2-Factor Authentication
3. Vào Security → App passwords
4. Tạo app password cho "Mail"
5. Sử dụng password này trong cài đặt SMTP

## 🔧 Cách sử dụng

### Test email

1. Vào `http://localhost/admin/test-email.php`
2. Nhập email để test
3. Nhấn "Gửi email test"
4. Kiểm tra email (có thể trong spam)

### Xem log email

- Vào `http://localhost/admin/test-email.php`
- Scroll xuống phần "Log email gần đây"
- Xem trạng thái gửi email: Success/Failed/Error

## 📧 Các loại email tự động

### 1. Email đăng ký tài khoản

- **Kích hoạt**: Khi user đăng ký thành công
- **File**: `register.php`
- **Template**: `sendRegistrationEmail()`
- **Nội dung**: Chào mừng, hướng dẫn sử dụng

### 2. Email đặt lịch hẹn

- **Kích hoạt**: Khi tạo lịch hẹn thành công
- **File**: `api/book-appointment.php`
- **Template**: `sendAppointmentEmail()`
- **Nội dung**: Thông tin lịch hẹn, lưu ý quan trọng

### 3. Email đặt hàng

- **Kích hoạt**: Khi đặt hàng thành công
- **File**: `api/place-order.php`, `checkout.php`
- **Template**: `sendOrderEmail()`
- **Nội dung**: Thông tin đơn hàng, thời gian giao hàng

## 🛠️ Tùy chỉnh template email

### Chỉnh sửa template

File: `includes/email_system.php`

```php
// Sửa function này để tùy chỉnh giao diện email
function getEmailTemplate($title, $content, $user_name = '') {
    // Tùy chỉnh HTML template ở đây
}
```

### Thêm email mới

```php
// Thêm function mới vào includes/email_system.php
function sendCustomEmail($user_email, $user_name, $custom_data) {
    $subject = 'Tiêu đề email';
    $content = '
        <div class="info-box success">
            <h3>Tiêu đề nội dung</h3>
            <p>Nội dung email...</p>
        </div>
    ';

    return sendEmail($user_email, $subject, getEmailTemplate($subject, $content, $user_name));
}
```

### Gọi email trong code

```php
// Thêm vào file xử lý
require_once 'includes/email_system.php';

// Gửi email
try {
    sendCustomEmail($user_email, $user_name, $data);
    error_log("Email sent successfully");
} catch (Exception $e) {
    error_log("Email failed: " . $e->getMessage());
}
```

## 🔍 Troubleshooting

### Email không gửi được

1. **Kiểm tra cài đặt SMTP**

   - Username/Password đúng chưa?
   - Port và Host đúng chưa?
   - Đã bật App Password chưa?

2. **Kiểm tra log**

   - Vào `admin/test-email.php`
   - Xem log email để biết lỗi cụ thể

3. **Kiểm tra firewall**
   - Port 587 có bị chặn không?
   - Máy chủ có thể kết nối internet?

### Email vào spam

1. **Cải thiện sender reputation**

   - Sử dụng domain email chính thức
   - Đăng ký SPF, DKIM records
   - Tránh từ spam trong subject

2. **Cải thiện nội dung**
   - Không viết hoa hết
   - Tránh từ ngữ spam
   - Cân bằng text/HTML

### Email gửi chậm

1. **Tối ưu server**

   - Kiểm tra tốc độ internet
   - Sử dụng SMTP local nếu có

2. **Gửi bất đồng bộ**
   - Sử dụng queue system
   - Gửi email background

## 📊 Monitoring & Analytics

### Theo dõi email

```sql
-- Xem thống kê email
SELECT
    DATE(sent_at) as date,
    status,
    COUNT(*) as count
FROM email_logs
WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(sent_at), status
ORDER BY date DESC;
```

### Email thành công/thất bại

```sql
-- Tỷ lệ thành công
SELECT
    status,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM email_logs), 2) as percentage
FROM email_logs
GROUP BY status;
```

## 🔒 Bảo mật

### Bảo vệ thông tin SMTP

- Không commit password vào Git
- Sử dụng App Password, không dùng password chính
- Định kỳ thay đổi password
- Giới hạn quyền truy cập admin

### Rate limiting

```php
// Thêm vào email_system.php để tránh spam
function checkEmailRateLimit($email) {
    global $conn;

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM email_logs WHERE recipient = ? AND sent_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    return $result['count'] < 10; // Max 10 emails/hour
}
```

## 📈 Nâng cao

### Tích hợp với services khác

- **SendGrid**: Dành cho volume lớn
- **Mailgun**: API mạnh mẽ
- **Amazon SES**: Giá rẻ, reliable

### Template engine

- Sử dụng Twig cho template phức tạp
- Hỗ trợ multi-language
- Dynamic content

### Queue system

- Redis/RabbitMQ cho email queue
- Background processing
- Retry mechanism

---

## 📞 Hỗ trợ

Nếu gặp vấn đề, vui lòng:

1. Kiểm tra log trong `admin/test-email.php`
2. Xem error logs của PHP
3. Test với email khác
4. Kiểm tra cài đặt SMTP

**Phiên bản**: 1.0.0  
**Cập nhật**: 2024  
**Tác giả**: Dalziel Development

---

_Chúc bạn cài đặt thành công! 🎉_
