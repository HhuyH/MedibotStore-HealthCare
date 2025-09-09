# Hướng dẫn cài đặt Email MediSync - NHANH CHÓNG ⚡

## 🚀 Cài đặt siêu nhanh (3 bước)

### Bước 1: Import database

```sql
-- Vào phpMyAdmin, chọn database của bạn
-- Import file: database/medisync_email_config.sql
```

### Bước 2: Test email

```
Vào: http://localhost/demo_email_system.php
Nhập email của bạn
Nhấn "Test Tất cả"
```

### Bước 3: Kiểm tra email

```
Mở email của bạn (có thể ở spam)
Sẽ có 3 email test:
✅ Email chào mừng đăng ký
✅ Email xác nhận lịch hẹn
✅ Email xác nhận đặt hàng
```

## 📧 Thông tin Email đã cấu hình

**Email:** `medisyncnoreplybot@gmail.com`  
**Tên hiển thị:** `MediSyncNoreply`  
**App Password:** `zvgk wleu zgyd ljyr`  
**SMTP:** Gmail (smtp.gmail.com:587 TLS)

## ✅ Email tự động sẽ gửi khi:

1. **Đăng ký tài khoản** → Email chào mừng
2. **Đặt lịch hẹn** → Email xác nhận lịch hẹn
3. **Đặt hàng thành công** → Email xác nhận đơn hàng

## 🔧 Nếu có lỗi:

1. **Kiểm tra cài đặt:** `admin/test-email.php`
2. **Xem log:** Trong trang test email, scroll xuống "Log email gần đây"
3. **Firewall:** Đảm bảo port 587 không bị chặn
4. **Internet:** Đảm bảo server có thể kết nối ra ngoài

## 🎯 Test thực tế:

1. **Test đăng ký:**

   - Vào `register.php`
   - Đăng ký tài khoản mới
   - Kiểm tra email chào mừng

2. **Test đặt lịch:**

   - Đăng nhập → `book-appointment.php`
   - Đặt lịch hẹn với bác sĩ
   - Kiểm tra email xác nhận

3. **Test đặt hàng:**
   - Thêm sản phẩm vào giỏ → `checkout.php`
   - Hoàn tất đặt hàng
   - Kiểm tra email đơn hàng

## 📱 Files quan trọng:

- `includes/email_system.php` - Hệ thống email core
- `database/medisync_email_config.sql` - Cấu hình SMTP
- `demo_email_system.php` - Test toàn bộ hệ thống
- `admin/test-email.php` - Test từng email riêng

---

**⚡ Hoàn tất! Email system đã sẵn sàng hoạt động!**

_Nếu có vấn đề gì, hãy kiểm tra log trong `admin/test-email.php`_
