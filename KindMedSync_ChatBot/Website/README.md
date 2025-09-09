# MediSync - Hệ thống Quản lý Y tế và Chăm sóc Sức khỏe

[Previous content remains the same until the directory structure section]

## 🛠 Cấu trúc chi tiết dự án

### 📁 Admin Module (`/admin`)

```
admin/
├── activity-log.php           # Theo dõi hoạt động hệ thống
├── ai-chat-box-admin.php      # Quản lý chatbot AI
├── ajax/                      # AJAX handlers
│   ├── change_password.php
│   ├── clear_temp.php
│   ├── delete-off-day.php
│   ├── get-email-details.php
│   ├── update_appointment_status.php
│   └── update-schedule-status.php
├── api/
│   └── get-notifications.php  # API thông báo
├── appointments.php           # Quản lý lịch hẹn
├── assets/                    # Tài nguyên admin
│   ├── css/
│   │   ├── admin.css
│   │   ├── header.css
│   │   └── sidebar.css
│   └── js/
│       ├── admin.js
│       └── notifications.js
├── blog/                      # Quản lý blog
│   ├── categories.php
│   ├── create_post.php
│   ├── manage_posts.php
│   └── posts.php
├── dashboard.php             # Trang chủ admin
├── email-settings.php        # Cấu hình email
├── maintenance.php           # Bảo trì hệ thống
├── products.php              # Quản lý sản phẩm
├── settings.php              # Cài đặt hệ thống
└── users.php                 # Quản lý người dùng
```

### 📁 API Module (`/api`)

```
api/
├── book-appointment.php      # API đặt lịch
├── cart/                     # API giỏ hàng
│   ├── add.php
│   ├── count.php
│   ├── get.php
│   └── update.php
├── check-auth.php           # Xác thực API
├── get-doctors.php          # API thông tin bác sĩ
├── get-order-details.php    # Chi tiết đơn hàng
├── get-time-slots.php       # Khung giờ khám
├── product/                 # API sản phẩm
│   └── view.php
└── wishlist/                # API danh sách yêu thích
```

### 📁 Assets (`/assets`)

```
assets/
├── css/                     # Stylesheets
│   ├── about.css
│   ├── blog-post.css
│   ├── bootstrap.min.css
│   ├── cart.css
│   ├── layout.css
│   └── style.css
├── images/                  # Hình ảnh
│   ├── about-hospital.jpg
│   ├── blog/
│   ├── products/
│   └── services/
└── js/                      # JavaScript
    ├── about.js
    ├── cart-new.js
    ├── chat.js
    ├── global-enhancements.js
    └── shop.js
```

### 📁 Chat Module (`/Chat`)

```
Chat/
├── get_history.php          # Lịch sử chat
├── health-ai-chat.css       # Styles cho chat
├── health-ai-chat.php       # Giao diện chat
└── update_history.php       # Cập nhật lịch sử
```

### 📁 Chatbot Backend (`/Chatbot_BackEnd`)

```
Chatbot_BackEnd/
├── config/
│   ├── config.py           # Cấu hình chatbot
│   ├── intents.py         # Định nghĩa intents
│   └── logging_config.py  # Cấu hình logging
├── prompts/               # Prompts cho AI
│   ├── db_schema/        # Schema database
│   └── prompts.py        # Định nghĩa prompts
├── routes/
│   └── chat.py           # Routes xử lý chat
└── utils/                # Tiện ích
    ├── auth_utils.py
    ├── health_advice.py
    ├── openai_utils.py
    └── symptom_utils.py
```

### 📁 Database (`/database`)

```
database/
├── add_discount_column.sql
├── appointments.sql
├── blog.sql
├── create_tables.sql
├── doctor_schedules.sql
├── email_settings.sql
├── medical_services.sql
├── order.sql
├── sample_data.sql
└── schema.sql
```

### 📁 Includes (`/includes`)

```
includes/
├── ajax/
│   └── search_suggestions.php
├── config.php               # Cấu hình chung
├── db.php                   # Kết nối database
├── email_system.php         # Hệ thống email
├── functions/
│   ├── enhanced_logger.php
│   ├── format_helpers.php
│   └── product_functions.php
├── header.php
└── footer.php
```

### 📁 Main Pages

```
/
├── about.php               # Giới thiệu
├── appointments.php        # Đặt lịch
├── blog.php               # Blog
├── cart.php               # Giỏ hàng
├── contact.php            # Liên hệ
├── doctors.php            # Danh sách bác sĩ
├── index.php              # Trang chủ
├── login.php              # Đăng nhập
├── profile.php            # Thông tin cá nhân
├── register.php           # Đăng ký
├── services.php           # Dịch vụ
└── shop.php               # Cửa hàng
```

### 📁 Documentation (`/README_FILE`)

```
README_FILE/
├── CALCULATION_FUNCTIONS.md
├── EMAIL_ADMIN_SYSTEM.md
├── EMAIL_SETUP_GUIDE.md
├── ENHANCED_LOGGING_SYSTEM.md
├── FORGOT_PASSWORD_SYSTEM.md
├── PHP_CODE_GUIDE.md
└── README_SETUP.md
```

### 📁 SQL Documentation (`/SQL`)

```
SQL/
├── ActivityDiagram/        # Sơ đồ hoạt động
├── DFD/                    # Data Flow Diagrams
├── ERD/                    # Entity Relationship Diagrams
├── Function_NOTE.sql       # Ghi chú functions
└── Sample_Data.sql         # Dữ liệu mẫu
```

## 🔄 Quy trình làm việc

1. **Frontend Flow**

   - Xử lý request người dùng
   - Validate input
   - Gọi API xử lý
   - Hiển thị kết quả

2. **Backend Flow**

   - Xác thực request
   - Xử lý business logic
   - Tương tác database
   - Trả về response

3. **Chatbot Flow**

   - Nhận input người dùng
   - Xử lý NLP
   - Tương tác OpenAI
   - Trả về response

4. **Database Flow**
   - CRUD operations
   - Transaction management
   - Backup/Restore
   - Data validation

[Previous content remains the same from here]
