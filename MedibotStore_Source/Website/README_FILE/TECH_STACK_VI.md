# Tài Liệu Kỹ Thuật Hệ Thống MediSync

## Tổng Quan

MediSync là một hệ thống quản lý y tế toàn diện kết hợp ứng dụng web PHP với chatbot AI được phát triển bằng Python. Hệ thống cung cấp các tính năng đặt lịch khám, quản lý sản phẩm, dịch vụ y tế và tư vấn sức khỏe bằng AI.

## Công Nghệ Frontend

### Công Nghệ Cốt Lõi

- **PHP**: Ngôn ngữ lập trình phía máy chủ (Backend chính)
- **HTML5/CSS3**: Đánh dấu và tạo kiểu giao diện
- **JavaScript**: Tương tác phía người dùng

### Framework và Thư Viện CSS

- **Bootstrap 5.3.0**: Framework CSS chính cho thiết kế responsive
- **Font Awesome 6.4.0**: Thư viện biểu tượng
- **Custom CSS**: Nhiều module CSS tùy chỉnh cho từng chức năng

### Thư Viện JavaScript

- **jQuery**: Xử lý DOM và AJAX
- **Bootstrap Bundle JS 5.3.0**: Components JavaScript của Bootstrap

## Công Nghệ Backend

### Backend PHP

- **PHP**: Xử lý logic ứng dụng phía máy chủ
- **MySQL**: Hệ quản trị cơ sở dữ liệu chính
- **XAMPP**: Môi trường phát triển cục bộ
- **PHPMailer**: Thư viện gửi email (được tích hợp trong hệ thống email)

### Backend Chatbot (Python)

- **FastAPI 0.115.12**: Framework API hiện đại
- **Uvicorn 0.34.3**: Server ASGI
- **OpenAI 1.86.0**: Tích hợp AI cho chatbot
- **PyMySQL 1.1.1**: Kết nối MySQL cho Python
- **Pydantic 2.11.5**: Xác thực dữ liệu

### Xử Lý Ngôn Ngữ Tự Nhiên và AI

- **OpenAI GPT Models**: Mô hình AI cho phản hồi chat
- **Tiktoken 0.9.0**: Tokenizer của OpenAI
- **RapidFuzz 3.13.0**: So khớp và so sánh chuỗi
- **Unidecode 1.4.0**: Chuẩn hóa văn bản Unicode

### Quản Lý Môi Trường và Cấu Hình

- **Python-dotenv 1.1.0**: Quản lý biến môi trường
- **Requests 2.32.4**: Thư viện HTTP

## Tích Hợp Thanh Toán

- **SEPAY**: Cổng thanh toán điện tử (tích hợp qua API)
- **QR Code**: Tạo và quét mã QR cho thanh toán

## Cấu Trúc Cơ Sở Dữ Liệu

Hệ thống sử dụng MySQL với các module:

- Quản lý người dùng (users, roles)
- Dịch vụ y tế (medical_services)
- Thương mại điện tử (products, orders)
- Hệ thống đặt lịch (appointments)
- Dữ liệu chatbot AI
- Hệ thống email

## Tính Năng Chính

### Quản Lý Người Dùng

- Xác thực và phân quyền
- Phân quyền (Admin, Bác sĩ, Bệnh nhân)
- Quản lý hồ sơ

### Dịch Vụ Y Tế

- Đặt lịch khám
- Lịch làm việc bác sĩ
- Danh mục dịch vụ y tế

### Thương Mại Điện Tử

- Quản lý sản phẩm
- Giỏ hàng
- Xử lý đơn hàng
- Tích hợp thanh toán

### Chatbot AI

- Tư vấn sức khỏe
- Phân tích triệu chứng
- Tư vấn y tế
- Tích hợp lịch sử bệnh nhân

### Hệ Thống Email

- Thông báo lịch hẹn
- Xác nhận đơn hàng
- Đặt lại mật khẩu
- Thông báo hệ thống

## Công Cụ Phát Triển

### Hệ Thống Ghi Log

- Ghi log PHP tùy chỉnh
- Ghi log nâng cao để debug
- Theo dõi hoạt động
- Ghi log lỗi

### Tính Năng Bảo Mật

- Mã hóa mật khẩu
- Quản lý phiên
- Bảo vệ CSRF
- Xác thực đầu vào

### Môi Trường Phát Triển

- XAMPP (Apache, MySQL, PHP)
- Môi trường ảo Python
- Git version control

## Cấu Trúc Thư Mục

```
htdocs/
├── admin/           # Giao diện quản trị
├── api/             # Các endpoint API
├── assets/          # Tài nguyên tĩnh
├── Chat/            # Giao diện chat
├── Chatbot_BackEnd/ # Backend chatbot Python
├── database/        # Scripts cơ sở dữ liệu
├── includes/        # Components PHP dùng chung
└── README_FILE/     # Tài liệu
```

## Hướng Dẫn Cài Đặt

1. Cài đặt XAMPP
2. Thiết lập môi trường Python và cài đặt requirements
3. Cấu hình cơ sở dữ liệu bằng scripts SQL
4. Thiết lập biến môi trường
5. Khởi tạo ứng dụng

## Cấu Hình

- Cấu hình database trong `includes/config.php`
- Cấu hình chatbot trong `Chatbot_BackEnd/config/config.py`
- Biến môi trường trong `.env`

## Lưu Ý Bảo Mật

- API keys phải được lưu trong biến môi trường
- Thông tin đăng nhập database phải được bảo mật
- Cập nhật bảo mật thường xuyên
- Xác thực và làm sạch dữ liệu đầu vào

## Bảo Trì

- Sao lưu cơ sở dữ liệu định kỳ
- Xoay vòng log
- Theo dõi hiệu suất
- Cập nhật bảo mật

---

_Tài liệu này được duy trì như một phần của Hệ thống Quản lý Y tế MediSync._
