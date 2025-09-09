# 🤖 AI Health Chat Application

## 📋 Mô tả dự án

Ứng dụng chat AI tư vấn sức khỏe với giao diện glassmorphism hiện đại, được tối ưu hóa cho performance và trải nghiệm người dùng.

## 🏗️ Cấu trúc dự án

### 📁 File chính

```
Chat/
├── health-ai-chat.php     # File chính - Giao diện chat
├── health-ai-chat.css     # File CSS riêng - Styles
└── README.md             # Tài liệu hướng dẫn
```

### 🎨 Cấu trúc giao diện

#### 1. **Header (chat-header)**

```php
<div class="chat-header">
    - Back button: Quay lại trang chủ
    - Chat info: Tiêu đề và mô tả
    - User info: Thông tin người dùng
    - Action buttons: Đóng, Làm mới, Đăng xuất
</div>
```

#### 2. **Chat Main (chat-main)**

```php
<div class="chat-main">
    - Inner header: Tiêu đề AI và trạng thái online
    - Messages area: Khu vực tin nhắn
    - Input area: Ô nhập tin nhắn
</div>
```

#### 3. **Messages Area (messages-area)**

```php
<div class="messages-area">
    - Welcome card: Thẻ chào mừng (có thể ẩn/hiện)
    - Chat messages: Tin nhắn user và bot
    - Typing indicator: Hiệu ứng đang gõ
</div>
```

## ⏱️ CÀI ĐẶT THỜI GIAN PHẢN HỒI BOT

### 🎯 Vị trí cài đặt

**Dòng 1040-1050 trong file `health-ai-chat.php`:**

```javascript
// Simulate bot response delay
showTyping();
setTimeout(() => {
  hideTyping();
  addMessage(responses[Math.floor(Math.random() * responses.length)]);
}, 2000); // ← THAY ĐỔI SỐ NÀY
```

### 🔧 Tùy chỉnh thời gian phản hồi

| Thời gian | Mô tả                         | Cài đặt                 |
| --------- | ----------------------------- | ----------------------- |
| `1000`    | Phản hồi nhanh (1 giây)       | `setTimeout(..., 1000)` |
| `2000`    | Phản hồi bình thường (2 giây) | `setTimeout(..., 2000)` |
| `3000`    | Phản hồi chậm (3 giây)        | `setTimeout(..., 3000)` |
| `500`     | Phản hồi tức thì (0.5 giây)   | `setTimeout(..., 500)`  |

### 🎲 Thời gian ngẫu nhiên

Để tạo thời gian phản hồi ngẫu nhiên:

```javascript
// Thời gian ngẫu nhiên từ 1-3 giây
const randomDelay = Math.random() * 2000 + 1000;
setTimeout(() => {
  hideTyping();
  addMessage(responses[Math.floor(Math.random() * responses.length)]);
}, randomDelay);
```

## 📝 CÀI ĐẶT NỘI DUNG PHẢN HỒI

### 🔍 Vị trí responses array

**Dòng 995-1030 trong file `health-ai-chat.php`:**

```javascript
const responses = [
  `**🩺 Phân tích tình trạng:**...`,
  `**✨ Hướng dẫn chăm sóc sức khỏe:**...`,
  // Thêm phản hồi mới ở đây
];
```

### ➕ Thêm phản hồi mới

```javascript
const responses = [
  // Phản hồi hiện có...

  // Thêm phản hồi mới
  `**🏥 Tư vấn y tế:**
    
Dựa trên triệu chứng bạn mô tả...`,

  `**💊 Hướng dẫn sử dụng thuốc:**
    
Lưu ý quan trọng khi dùng thuốc...`,
];
```

## 🎨 TÙYCHỈNH GIAO DIỆN

### 🎨 Màu sắc (CSS Variables)

**Trong file `health-ai-chat.css` (dòng 10-20):**

```css
:root {
  /* Medical Color Palette */
  --primary: #0891b2; /* Màu chính */
  --primary-dark: #0e7490; /* Màu tối */
  --secondary: #059669; /* Màu phụ */
  --accent: #0ea5e9; /* Màu nhấn */
}
```

### 🌊 Gradient Background

```css
body {
  background: var(--gradient-ocean);
  /* Có thể thay đổi thành:
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    */
}
```

## 🔧 TÍNH NĂNG

### ✅ Đã có

- ✅ Giao diện glassmorphism responsive
- ✅ Chat simulation với typing indicator
- ✅ Multiple response templates
- ✅ User session management
- ✅ Mobile-friendly design
- ✅ Performance optimized

### 🔄 Tính năng có thể mở rộng

- 🔲 Kết nối API AI thật (ChatGPT, Claude)
- 🔲 Lưu lịch sử chat vào database
- 🔲 Upload file/hình ảnh
- 🔲 Voice message
- 🔲 Export chat history

## 🚀 HƯỚNG DẪN SỬ DỤNG

### 1. **Khởi chạy**

```bash
# Đảm bảo XAMPP đang chạy
# Truy cập: http://localhost/Chat/health-ai-chat.php
```

### 2. **Đăng nhập**

- Cần đăng nhập với tài khoản hợp lệ
- Session được kiểm tra tự động

### 3. **Chat**

- Nhập tin nhắn và nhấn Enter hoặc nút gửi
- Bot sẽ phản hồi sau thời gian đã cài đặt
- Có thể chọn suggestion chips để chat nhanh

## 🔧 TROUBLESHOOTING

### ❌ Lỗi thường gặp

**1. Bot không phản hồi:**

- Kiểm tra console browser (F12)
- Đảm bảo JavaScript không bị lỗi

**2. Giao diện bị lỗi:**

- Kiểm tra CSS load đúng
- Clear cache browser

**3. Session lỗi:**

- Kiểm tra file `includes/config.php`
- Đảm bảo database connection

### 🔍 Debug Mode

Thêm vào cuối file để debug:

```javascript
// Debug mode
console.log("Chat initialized");
console.log("Current user:", "<?= $user_name ?>");
```

## 📱 RESPONSIVE DESIGN

### 📏 Breakpoints

- **Desktop:** > 1024px
- **Tablet:** 768px - 1024px
- **Mobile:** < 768px

### 🎯 Mobile Optimizations

- Touch-friendly buttons (48px minimum)
- Simplified animations
- Reduced blur effects
- Optimized font sizes

## 🔒 BẢO MẬT

### 🛡️ Security Features

- Session validation
- HTML escaping: `htmlspecialchars()`
- SQL injection protection
- XSS prevention

### ⚠️ Lưu ý bảo mật

- Không lưu trữ thông tin nhạy cảm trong JavaScript
- Validate input trước khi xử lý
- Sử dụng HTTPS trong production

---

## 🤝 ĐÓNG GÓP

Để đóng góp cho dự án:

1. Fork repository
2. Tạo feature branch
3. Commit changes
4. Push to branch
5. Create Pull Request

## 📄 LICENSE

MIT License - Xem file LICENSE để biết thêm chi tiết.

---

**🩺 Made with ❤️ for Healthcare**

                    `**✨ Hướng dẫn chăm sóc sức khỏe:**

Rất vui được hỗ trợ bạn! Dưới đây là những lời khuyên hữu ích:

**🌟 Chế độ sinh hoạt khoa học:**

- **Giấc ngủ:** 7-8 tiếng mỗi đêm, đi ngủ đúng giờ
- **Dinh dưỡng:** Ăn đủ 3 bữa, tăng rau xanh, trái cây
- **Thể dục:** 30 phút/ngày, ít nhất 5 ngày/tuần
- **Tinh thần:** Thư giãn, thiền, nghe nhạc

**🥗 Dinh dưỡng cân bằng:**

- Protein: Thịt, cá, trứng, đậu
- Carbohydrate: Cơm, bánh mì nguyên cám
- Chất béo tốt: Dầu ô liu, hạt, cá biển
- Vitamin & khoáng chất: Rau củ quả đa dạng

**📊 Theo dõi sức khỏe:**

- Kiểm tra cân nặng, huyết áp định kỳ
- Ghi chép cảm giác hàng ngày
- Khám sức khỏe tổng quát 6 tháng/lần

**💝 Lời khuyên cuối:**
Hãy kiên nhẫn và thực hiện từng bước một cách bền vững!`,

                    `**🔬 Phân tích chuyên sâu:**

Tôi hiểu mối quan tâm của bạn. Hãy cùng tìm hiểu chi tiết:

**🎯 Kế hoạch cải thiện từng bước:**

**Tuần 1-2: Thiết lập nền tảng**

- Điều chỉnh giờ giấc sinh hoạt
- Tăng lượng nước uống hàng ngày
- Bắt đầu tập thể dục nhẹ

**Tuần 3-4: Phát triển thói quen**

- Ổn định chế độ ăn uống
- Tăng cường hoạt động thể chất
- Thực hành kỹ thuật thư giãn

**Tuần 5-8: Duy trì và nâng cao**

- Đánh giá tiến độ cải thiện
- Điều chỉnh kế hoạch phù hợp
- Xây dựng thói quen lâu dài

**📈 Dấu hiệu tích cực:**

- Ngủ ngon hơn, tỉnh táo buổi sáng
- Tinh thần phấn chấn, ít stress
- Thể lực được cải thiện
