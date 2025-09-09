# Admin Tools - Hướng dẫn sử dụng

## Tổng quan

Hệ thống admin tools bao gồm 3 trang chính để quản lý và bảo trì hệ thống:

1. **Settings** - Cài đặt hệ thống
2. **Backup** - Sao lưu & khôi phục dữ liệu
3. **Maintenance** - Bảo trì hệ thống

---

## 1. Settings (settings.php)

### Chức năng chính:

- **Cài đặt chung**: Tên website, thông tin liên hệ, SEO
- **Cài đặt Email**: Cấu hình SMTP để gửi email
- **Mạng xã hội**: Liên kết Facebook, Twitter, Instagram, YouTube
- **Bảo mật**: Đổi mật khẩu admin, quản lý phiên đăng nhập

### Cách sử dụng:

1. Truy cập `http://localhost/admin/settings.php`
2. Chọn tab tương ứng với cài đặt cần thay đổi
3. Nhập thông tin và nhấn "Lưu"

### Lưu ý:

- Cài đặt được lưu vào bảng `settings` trong database
- Đổi mật khẩu yêu cầu nhập mật khẩu hiện tại
- Test email chỉ hoạt động khi cấu hình SMTP đúng

---

## 2. Backup (backup.php)

### Chức năng chính:

- **Tạo backup**: Sao lưu toàn bộ database
- **Khôi phục**: Restore database từ file backup
- **Quản lý backup**: Xem, download, xóa các file backup
- **Upload backup**: Tải lên file backup từ máy tính

### Cách sử dụng:

1. Truy cập `http://localhost/admin/backup.php`
2. **Tạo backup**: Nhấn "Tạo Backup Ngay"
3. **Khôi phục**: Chọn file backup → nhấn nút "Restore"
4. **Download**: Nhấn nút "Download" để tải về máy

### Lưu ý:

- File backup được lưu trong thư mục `/backups/`
- Chỉ admin mới có thể truy cập file backup
- Khôi phục sẽ ghi đè toàn bộ dữ liệu hiện tại
- Backup tự động có thể được thiết lập (đang phát triển)

---

## 3. Maintenance (maintenance.php)

### Chức năng chính:

- **Cleaning Tools**: Xóa cache, logs, temp files
- **Database Tools**: Tối ưu hóa, sửa chữa, cập nhật thống kê
- **System Information**: Thông tin PHP, MySQL, server
- **Database Statistics**: Thống kê số lượng records

### Cách sử dụng:

1. Truy cập `http://localhost/admin/maintenance.php`
2. Xem thông tin tổng quan ở dashboard
3. Sử dụng các công cụ:
   - **Clear Cache**: Xóa file cache tạm thời
   - **Clean Old Logs**: Xóa log cũ hơn 7 ngày
   - **Optimize Database**: Tối ưu hóa hiệu suất database
   - **Repair Tables**: Sửa chữa bảng bị lỗi

### Lưu ý:

- Thực hiện bảo trì trong giờ ít người dùng
- Backup trước khi thực hiện optimize/repair
- Theo dõi disk usage để tránh hết dung lượng

---

## Yêu cầu hệ thống

### PHP Extensions:

- `mysqli` - Kết nối database
- `json` - Xử lý dữ liệu JSON
- `fileinfo` - Kiểm tra loại file

### MySQL Tools:

- `mysqldump` - Tạo backup (cần cài đặt riêng)
- `mysql` - Khôi phục backup

### Permissions:

- Thư mục `backups/` cần quyền write (755)
- Thư mục `temp/` cần quyền write (755)
- Thư mục `logs/` cần quyền write (755)

---

## Bảo mật

### Các biện pháp bảo vệ:

1. **Authentication**: Chỉ admin (role_id = 1) mới truy cập được
2. **File Protection**:
   - `.htaccess` chặn truy cập trực tiếp
   - `index.php` redirect về trang chủ
3. **Input Validation**: Kiểm tra tất cả dữ liệu đầu vào
4. **SQL Injection**: Sử dụng prepared statements

### Khuyến nghị:

- Thay đổi mật khẩu admin định kỳ
- Sao lưu định kỳ và lưu trữ ở nơi an toàn
- Theo dõi log files để phát hiện bất thường
- Cập nhật PHP và MySQL thường xuyên

---

## Troubleshooting

### Lỗi thường gặp:

**1. Không thể tạo backup:**

- Kiểm tra `mysqldump` đã cài đặt chưa
- Kiểm tra quyền write thư mục `backups/`
- Kiểm tra thông tin kết nối database

**2. Khôi phục backup thất bại:**

- Kiểm tra file backup có bị corrupted không
- Kiểm tra dung lượng disk còn đủ không
- Kiểm tra quyền của database user

**3. Lỗi permission:**

```bash
chmod 755 backups/
chmod 755 temp/
chmod 755 logs/
```

**4. Lỗi memory limit:**

```php
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 300);
```

---

## Liên hệ hỗ trợ

Nếu gặp vấn đề khi sử dụng, vui lòng:

1. Kiểm tra error logs: `logs/` directory
2. Kiểm tra PHP error log
3. Liên hệ admin hệ thống

---

_Cập nhật lần cuối: <?php echo date('d/m/Y H:i:s'); ?>_
