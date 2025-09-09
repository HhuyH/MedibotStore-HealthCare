# Hệ thống Quên Mật Khẩu - QickMed

## Tổng quan

Hệ thống quên mật khẩu cho phép người dùng đặt lại mật khẩu thông qua email với link reset có hiệu lực 24 giờ. Hệ thống này tích hợp với email system và enhanced logging để đảm bảo bảo mật và theo dõi hoạt động.

## Tính năng chính

### 🔒 Bảo mật

- **Token ngẫu nhiên**: Sử dụng token 64 ký tự hex ngẫu nhiên
- **Hết hạn 24 giờ**: Link reset chỉ có hiệu lực 24 giờ
- **Sử dụng 1 lần**: Mỗi token chỉ có thể sử dụng 1 lần duy nhất
- **Rate limiting**: Chỉ cho phép 1 yêu cầu mỗi 5 phút
- **IP tracking**: Theo dõi địa chỉ IP và user agent

### 📧 Email

- **Template đẹp**: Email HTML responsive với thiết kế chuyên nghiệp
- **Thông tin chi tiết**: Bao gồm thời gian, IP, trình duyệt
- **Hướng dẫn rõ ràng**: Cách sử dụng và lưu ý bảo mật

### 📝 Logging

- **Security logs**: Ghi lại tất cả hoạt động bảo mật
- **Failed attempts**: Theo dõi các lần thử không thành công
- **Password changes**: Ghi lại việc đổi mật khẩu thành công

## Cài đặt

### 1. Tạo bảng database

Chạy script tạo bảng:

```
http://localhost/setup_password_reset.php
```

Hoặc chạy SQL trực tiếp:

```sql
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_at TIMESTAMP NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,

    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_email (email),
    INDEX idx_expires (expires_at),
    INDEX idx_used (used)
);
```

### 2. Kiểm tra email system

Đảm bảo email system đã được cấu hình:

- File `includes/email_system_simple.php` hoạt động
- SMTP settings đúng
- Test gửi email thành công

### 3. Cấu hình URL

Trong file `forgot_password.php`, cập nhật URL nếu cần:

```php
$reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token;
```

## Cách sử dụng

### 1. Trang quên mật khẩu

- **URL**: `http://localhost/forgot_password.php`
- **Chức năng**: Nhập email để nhận link reset password
- **Validation**: Kiểm tra email hợp lệ và tồn tại trong hệ thống

### 2. Nhận email

- **Chủ đề**: "Đặt lại mật khẩu - QickMed"
- **Nội dung**: Email HTML với link reset và thông tin chi tiết
- **Hết hạn**: 24 giờ từ lúc gửi

### 3. Trang reset password

- **URL**: `http://localhost/reset_password.php?token={token}`
- **Chức năng**: Nhập mật khẩu mới
- **Validation**: Kiểm tra độ mạnh mật khẩu và xác nhận

## Luồng hoạt động

### 1. Yêu cầu reset password

```
User → forgot_password.php → Nhập email → Validation → Tạo token → Gửi email
```

### 2. Nhận và click link

```
Email → Click link → reset_password.php → Validate token → Form đổi mật khẩu
```

### 3. Đặt lại mật khẩu

```
Form → Validate password → Update database → Mark token used → Redirect login
```

## Bảo mật

### Token Security

- **Độ dài**: 64 ký tự hex (256 bit entropy)
- **Ngẫu nhiên**: Sử dụng `random_bytes()` cryptographically secure
- **Unique**: Constraint UNIQUE trong database
- **Hết hạn**: Tự động expire sau 24 giờ

### Rate Limiting

- **Interval**: 5 phút giữa các yêu cầu
- **Per email**: Mỗi email chỉ được 1 token active
- **Auto cleanup**: Tự động dọn dẹp token cũ

### Logging Security

- **Failed attempts**: Ghi lại email không tồn tại
- **Multiple requests**: Theo dõi các yêu cầu liên tiếp
- **IP tracking**: Lưu IP và user agent
- **Success tracking**: Ghi lại đổi mật khẩu thành công

## Email Template

### Nội dung email

```html
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
  <div style="background-color: white; padding: 30px; border-radius: 10px;">
    <h1 style="color: #2563eb;">🔐 QickMed - Đặt lại mật khẩu</h1>
    <p>Xin chào {user_name},</p>
    <p>Bạn đã yêu cầu đặt lại mật khẩu cho tài khoản {username}.</p>

    <div style="text-align: center; margin: 30px 0;">
      <a
        href="{reset_link}"
        style="background: #3b82f6; color: white; padding: 14px 30px; 
               text-decoration: none; border-radius: 8px; font-weight: 600;"
      >
        🔑 Đặt lại mật khẩu
      </a>
    </div>

    <div style="background: #fef3c7; padding: 15px; border-radius: 8px;">
      <p><strong>⚠️ Lưu ý quan trọng:</strong></p>
      <ul>
        <li>Link này chỉ có hiệu lực trong <strong>24 giờ</strong></li>
        <li>Chỉ sử dụng được <strong>1 lần duy nhất</strong></li>
        <li>
          Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này
        </li>
      </ul>
    </div>

    <div style="border-top: 2px solid #e5e7eb; padding-top: 20px;">
      <p><strong>Thông tin yêu cầu:</strong></p>
      <ul>
        <li>Thời gian: {timestamp}</li>
        <li>IP: {ip_address}</li>
        <li>Trình duyệt: {user_agent}</li>
      </ul>
    </div>
  </div>
</div>
```

## Database Schema

### Bảng password_reset_tokens

```sql
+-------------+--------------+------+-----+---------+----------------+
| Field       | Type         | Null | Key | Default | Extra          |
+-------------+--------------+------+-----+---------+----------------+
| id          | int(11)      | NO   | PRI | NULL    | auto_increment |
| user_id     | int(11)      | NO   | MUL | NULL    |                |
| email       | varchar(255) | NO   | MUL | NULL    |                |
| token       | varchar(255) | NO   | UNI | NULL    |                |
| expires_at  | timestamp    | NO   | MUL | NULL    |                |
| used        | tinyint(1)   | YES  | MUL | 0       |                |
| created_at  | timestamp    | YES  |     | CURRENT_TIMESTAMP |      |
| used_at     | timestamp    | YES  |     | NULL    |                |
| ip_address  | varchar(45)  | YES  |     | NULL    |                |
| user_agent  | text         | YES  |     | NULL    |                |
+-------------+--------------+------+-----+---------+----------------+
```

### Indexes

- `PRIMARY KEY (id)`
- `UNIQUE KEY token (token)`
- `KEY idx_email (email)`
- `KEY idx_expires (expires_at)`
- `KEY idx_used (used)`
- `FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE`

## API Endpoints

### POST /forgot_password.php

**Request:**

```json
{
  "email": "user@example.com"
}
```

**Response Success:**

```json
{
  "status": "success",
  "message": "Chúng tôi đã gửi link đặt lại mật khẩu đến email của bạn..."
}
```

**Response Error:**

```json
{
  "status": "error",
  "message": "Không tìm thấy tài khoản với địa chỉ email này!"
}
```

### POST /reset_password.php

**Request:**

```json
{
  "token": "abc123...",
  "new_password": "newpassword123",
  "confirm_password": "newpassword123"
}
```

**Response Success:**

```json
{
  "status": "success",
  "message": "Mật khẩu đã được đặt lại thành công!"
}
```

**Response Error:**

```json
{
  "status": "error",
  "message": "Mật khẩu xác nhận không khớp!"
}
```

## Error Handling

### Validation Errors

- **Email trống**: "Vui lòng nhập địa chỉ email!"
- **Email không hợp lệ**: "Địa chỉ email không hợp lệ!"
- **Email không tồn tại**: "Không tìm thấy tài khoản với địa chỉ email này!"
- **Rate limit**: "Vui lòng chờ 5 phút trước khi yêu cầu lại."

### Token Errors

- **Token trống**: "Token không hợp lệ!"
- **Token hết hạn**: "Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn!"
- **Token đã sử dụng**: "Token không hợp lệ hoặc đã được sử dụng!"

### Password Errors

- **Mật khẩu trống**: "Vui lòng nhập đầy đủ thông tin!"
- **Mật khẩu quá ngắn**: "Mật khẩu phải có ít nhất 6 ký tự!"
- **Xác nhận không khớp**: "Mật khẩu xác nhận không khớp!"

## Maintenance

### Dọn dẹp tự động

Event scheduler tự động dọn dẹp:

```sql
DELETE FROM password_reset_tokens
WHERE expires_at < NOW() OR (used = TRUE AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY))
```

### Monitoring

- **Admin logs**: Xem tại `/admin/activity-log.php`
- **Security events**: Theo dõi các hoạt động bảo mật
- **Email logs**: Kiểm tra email đã gửi thành công

### Backup

- **Database**: Backup bảng `password_reset_tokens`
- **Logs**: Backup các file log security
- **Config**: Backup cấu hình email

## Testing

### Test Cases

1. **Forgot Password**

   - Nhập email hợp lệ → Thành công
   - Nhập email không tồn tại → Lỗi
   - Nhập email không hợp lệ → Lỗi
   - Yêu cầu liên tiếp → Rate limit

2. **Reset Password**

   - Token hợp lệ → Hiển thị form
   - Token hết hạn → Lỗi
   - Token đã sử dụng → Lỗi
   - Token không tồn tại → Lỗi

3. **Change Password**
   - Mật khẩu hợp lệ → Thành công
   - Mật khẩu quá ngắn → Lỗi
   - Xác nhận không khớp → Lỗi
   - Token hết hạn → Lỗi

### Test URLs

- `http://localhost/forgot_password.php`
- `http://localhost/reset_password.php?token=abc123...`
- `http://localhost/setup_password_reset.php`

## Troubleshooting

### Common Issues

1. **Email không gửi được**

   - Kiểm tra cấu hình SMTP
   - Kiểm tra firewall/antivirus
   - Test email system riêng biệt

2. **Token không hợp lệ**

   - Kiểm tra URL có đúng không
   - Kiểm tra token có trong database không
   - Kiểm tra thời gian expires_at

3. **Database error**
   - Kiểm tra bảng đã tồn tại chưa
   - Kiểm tra foreign key constraints
   - Kiểm tra permissions

### Debug Mode

Bật debug trong cấu hình:

```php
// In config.php
define('DEBUG_MODE', true);
```

## Security Best Practices

1. **Token Security**

   - Sử dụng random_bytes() thay vì rand()
   - Token length >= 32 bytes
   - Set expiration time ngắn (24h)

2. **Rate Limiting**

   - Limit requests per IP
   - Limit requests per email
   - Implement CAPTCHA if needed

3. **Email Security**

   - Không gửi mật khẩu qua email
   - Chỉ gửi link reset
   - Thông báo về hoạt động bảo mật

4. **Database Security**
   - Hash passwords properly
   - Use prepared statements
   - Implement proper indexing

## Conclusion

Hệ thống quên mật khẩu đã được thiết kế với tính bảo mật cao, user experience tốt và khả năng monitoring toàn diện. Hệ thống tích hợp seamlessly với email system và logging system hiện có của QickMed.

**Các tính năng chính:**

- ✅ Bảo mật cao với token ngẫu nhiên
- ✅ Email template đẹp và chuyên nghiệp
- ✅ Rate limiting và IP tracking
- ✅ Enhanced logging và monitoring
- ✅ Auto cleanup expired tokens
- ✅ User-friendly interface
- ✅ Comprehensive error handling

Hệ thống sẵn sàng sử dụng trong production environment.
