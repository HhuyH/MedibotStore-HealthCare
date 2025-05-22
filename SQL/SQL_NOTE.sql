----------------------------------------------------------------1. Người dùng & hệ thống------------------------------------------------------------------------
-- Bảng lưu thông tin tài khoản
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,                   -- Khóa chính, định danh người dùng
    username VARCHAR(50) UNIQUE NOT NULL,                     -- Tên đăng nhập, không được trùng
    email VARCHAR(100) UNIQUE NOT NULL,                       -- Email đăng ký, duy nhất
    phone_number VARCHAR(15) UNIQUE,                          -- Số điện thoại (nếu có), cũng duy nhất
    password_hash VARCHAR(255) NOT NULL,                      -- Mật khẩu đã mã hóa
    role_id INT NOT NULL,                                     -- Liên kết đến bảng roles
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,           -- Thời gian tạo tài khoản
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (role_id) REFERENCES roles(role_id)                -- Ràng buộc vai trò người dùng
);

-- Bảng lưu vai trò
CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,                   -- Khóa chính
    role_name VARCHAR(50) UNIQUE NOT NULL,                    -- Tên vai trò: patient, admin, doctor
	description TEXT										  -- 'Mô tả vai trò nếu cần',
);

-- Cho này nên cho vào trước vài role 
-- khi đăng ký tài khoản bất kỳ tài khoản nào cũng sẽ có role là patient
-- sau đó admin sễ set role cho bác sĩ or admin mới nếu cần
-- role bác sĩ sẽ có khá nhiều loại... hoặc là phân trong chuyên khoa thông tin của bác sĩ
-- nhưng nếu là như vậy thì cách gửi thông báo hiện tại ko ổn

-- Bảng lưu thông tin người dùng
CREATE TABLE users_info (
    id INT AUTO_INCREMENT PRIMARY KEY,                        -- Khóa chính
    user_id INT NOT NULL,                                     -- Khóa ngoại liên kết với bảng users
    full_name VARCHAR(100),                                   -- Họ tên đầy đủ
    gender ENUM('Nam', 'Nữ', 'Khác'),                         -- Giới tính
    date_of_birth DATE,                                       -- Ngày sinh
    profile_picture VARCHAR(255),                             -- URL ảnh đại diện (nếu có)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
-- Thông tin người dùng có thể do chính người dùng nhập sau khi đăng ký
-- hoặc là được AI chatbox thu nhập thông qua việc chat với người dùng lút ban đầu cần
-- ví dụ nếu người dùng được AI yêu câu đi khám bác sĩ và người dùng chấp nhận thì
-- AI sẽ kiểm tra xem người dùng có đầy đủ thông tin chưa nếu chưa thì sẽ hỏi thông tin người dùng
-- hoặc kiêu người dùng tự nhập và sau đó thì hỏi nhưng câu hỏi cần thiết cần để đặt lịch khám
-- như ngày khám bác sĩ mong muốn nếu ko bik thì random phù hợp với bệnh muốn khám

-- sẽ được tạo khi người dùng chưa có tài khoản và có nhu cầu đặt lịch khám thì 
-- AI sẽ hỏi nhưng thông tin này và thực hiện đặt lịch khám khi đầy đủ thông tin cần thiết
-- và xác nhận đặt
CREATE TABLE guest_users (
    guest_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255),
    phone VARCHAR(20),
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
);

-- Bảng lưu địa chỉ người dùng 
CREATE TABLE user_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,                -- Khóa chính, tự động tăng
    user_id INT NOT NULL,                             -- ID người dùng liên kết với bảng users
    address_line VARCHAR(255) NOT NULL,               -- Địa chỉ chi tiết: số nhà, tên đường, căn hộ...
    ward VARCHAR(100),                                -- Phường/xã
    district VARCHAR(100),                            -- Quận/huyện
    city VARCHAR(100),                                -- Thành phố
    postal_code VARCHAR(20),                          -- Mã bưu chính (nếu có)
    country VARCHAR(100) DEFAULT 'Vietnam',           -- Quốc gia, mặc định là Việt Nam
    is_default BOOLEAN DEFAULT FALSE,                 -- Địa chỉ mặc định (chỉ 1 địa chỉ của user là TRUE)
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,    -- Thời gian tạo địa chỉ
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,  -- Thời gian cập nhật địa chỉ
    
    FOREIGN KEY (user_id) REFERENCES users(user_id)        -- Khóa ngoại liên kết với bảng users
);
-- bảng lưu địa chỉ này cũng ko quá cấn thiết nhưng nó dùng cho thương mại điện tử
-- và 1 người cũng có thể có nhiều địa chỉ

CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,           -- Mã thông báo, tự tăng, dùng làm khóa chính
    target_role_id INT,                                       -- ID của vai trò được gửi thông báo nếu chỉ muốn gửi tới 1 nhốm đối tưởng nhất định
    title VARCHAR(255) NOT NULL,                              -- Tiêu đề của thông báo (ngắn gọn)
    message TEXT NOT NULL,                                    -- Nội dung chi tiết của thông báo
    type VARCHAR(50),                                         -- Loại thông báo: ví dụ 'system', 'AI alert', 'reminder'...
    is_global BOOLEAN DEFAULT FALSE,                          -- Nếu là TRUE, thông báo sẽ gửi đến toàn bộ người dùng
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,            -- Thời gian tạo thông báo
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP             -- Thời gian cập nhật thông báo (nếu bị chỉnh sửa)
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (target_role_id) REFERENCES roles(role_id)   -- Ràng buộc tới bảng roles
);

CREATE TABLE user_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,                        -- Khóa chính cho bảng ánh xạ
    notification_id INT NOT NULL,                             -- ID của thông báo (khóa ngoại)
    user_id INT NOT NULL,                                     -- ID của người dùng nhận thông báo
    is_read BOOLEAN DEFAULT FALSE,                            -- Đã đọc hay chưa (FALSE = chưa đọc)
    received_at DATETIME DEFAULT CURRENT_TIMESTAMP,           -- Thời điểm người dùng nhận thông báo

    FOREIGN KEY (notification_id) REFERENCES notifications(notification_id),   -- Ràng buộc khóa ngoại tới bảng thông báo
    FOREIGN KEY (user_id) REFERENCES users(user_id)                            -- Ràng buộc khóa ngoại tới bảng người dùng
);

✅ Logic khi gửi thông báo:
Nếu is_global = TRUE: Lấy tất cả người dùng, insert vào user_notifications.

Nếu target_role IS NOT NULL: Lấy tất cả người dùng có vai trò tương ứng (users.role = target_role), insert vào user_notifications.

Nếu gửi cá nhân: Insert 1 dòng vào user_notifications với user_id cụ thể.

✅ Giao diện Admin Gửi Thông Báo (ví dụ):
Tiêu đề

Nội dung

Hình thức gửi:

🔘 Gửi toàn hệ thống

🔘 Gửi theo vai trò → Chọn vai trò (dropdown)

🔘 Gửi người dùng cụ thể → Chọn user

→ Backend sẽ xử lý tùy theo lựa chọn, insert hợp lý vào user_notifications.

----------------------------------------------------------------2. Chăm sóc sức khỏe------------------------------------------------------------------------

-- Bảng medical_categories: Phân loại bệnh và chuyên khoa
CREATE TABLE medical_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,       -- Khóa chính
    name VARCHAR(255) NOT NULL,                       -- Tên chuyên khoa
    description TEXT,                                 -- Mô tả
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP             -- Thời gian cập nhật thông báo (nếu bị chỉnh sửa)
        ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng diseases: Danh sách các bệnh
CREATE TABLE diseases (
    disease_id INT AUTO_INCREMENT PRIMARY KEY,        -- Khóa chính
    name VARCHAR(255) NOT NULL,                       -- Tên bệnh
    description TEXT,                                 -- Mô tả về bệnh
    treatment_guidelines TEXT,                        -- Hướng dẫn điều trị
    category_id INT,                                  -- Liên kết đến chuyên khoa
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP             -- Thời gian cập nhật thông báo (nếu bị chỉnh sửa)
        ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES medical_categories(category_id)
);

-- Bảng symptoms: Danh sách các triệu chứng
CREATE TABLE symptoms (
    symptom_id INT AUTO_INCREMENT PRIMARY KEY,        -- Khóa chính
    name VARCHAR(255) NOT NULL,                       -- Tên triệu chứng
    description TEXT,                                 -- Mô tả triệu chứng
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP             -- Thời gian cập nhật thông báo (nếu bị chỉnh sửa)
        ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng disease_symptoms: Bảng nối giữa bệnh và triệu chứng
CREATE TABLE disease_symptoms (
    disease_id INT NOT NULL,                          -- ID bệnh
    symptom_id INT NOT NULL,                          -- ID triệu chứng
    PRIMARY KEY (disease_id, symptom_id),             -- Khóa chính kép
    FOREIGN KEY (disease_id) REFERENCES diseases(disease_id),
    FOREIGN KEY (symptom_id) REFERENCES symptoms(symptom_id)
);

-- Bảng lưu tiền sử triệu chứng (bảng này có thể được bác sĩ cập nhập hoặc AI cập nhập thông qua chat_log)
CREATE TABLE user_symptom_history (
    id INT AUTO_INCREMENT PRIMARY KEY,                   -- Khóa chính, tự động tăng
    user_id INT NOT NULL,                                -- Khóa ngoại liên hết tới user
    symptom_id INT NOT NULL,                             -- khóa ngoại liên kết tới triệu chứng
    record_date DATE NOT NULL,                           -- Ngày lưu triệu chứng
    notes TEXT,                                          -- Ghi chủ chi tiết nếu có
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (symptom_id) REFERENCES symptoms(symptom_id)
);

-- record_date o day ko de auto vi neu benh nhan mioeu tra 
-- bệnh trông quá khư thì còn có thể nhập

-- Bảng clinics: Danh sách bệnh viện/phòng khám
CREATE TABLE clinics (
    clinic_id INT AUTO_INCREMENT PRIMARY KEY,           -- Khóa chính
    name VARCHAR(255) NOT NULL,                         -- Tên phòng khám
    address TEXT NOT NULL,                              -- Địa chỉ
    phone VARCHAR(20),                                  -- Số điện thoại liên hệ
    email VARCHAR(255),                                 -- Email (nếu có)
    description TEXT,                                   -- Mô tả chi tiết
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP             -- Thời gian cập nhật thông báo (nếu bị chỉnh sửa)
        ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng specialties: Chuyên ngành y tế
CREATE TABLE specialties (
    specialty_id INT AUTO_INCREMENT PRIMARY KEY,        -- Khóa chính
    name VARCHAR(255) NOT NULL,                         -- Tên chuyên ngành (nội khoa, tim mạch…)
    description TEXT,                                   -- Mô tả chuyên ngành
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP             -- Thời gian cập nhật thông báo (nếu bị chỉnh sửa)
        ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng doctors: Thông tin bác sĩ
CREATE TABLE doctors (
    doctor_id INT AUTO_INCREMENT PRIMARY KEY,           -- Khóa chính
    user_id INT NOT NULL UNIQUE,                        -- Liên kết với bảng users
    specialty_id INT NOT NULL,                          -- Liên kết đến chuyên ngành
    clinic_id INT,                                      -- Liên kết đến phòng khám
    biography TEXT,                                     -- Tiểu sử/bằng cấp
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP             -- Thời gian cập nhật thông báo (nếu bị chỉnh sửa)
        ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (specialty_id) REFERENCES specialties(specialty_id),
    FOREIGN KEY (clinic_id) REFERENCES clinics(clinic_id)
);

-- Bảng doctor_schedules: Lịch làm việc của bác sĩ
CREATE TABLE doctor_schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,         -- Khóa chính
    doctor_id INT NOT NULL,                             -- Liên kết đến bảng doctors
    clinic_id INT,                                      -- Nơi làm việc
    day_of_week VARCHAR(20) NOT NULL,                   -- Thứ trong tuần (Monday, Tuesday...)
    start_time TIME NOT NULL,                           -- Giờ bắt đầu
    end_time TIME NOT NULL,                             -- Giờ kết thúc
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP             -- Thời gian cập nhật thông báo (nếu bị chỉnh sửa)
        ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id),
    FOREIGN KEY (clinic_id) REFERENCES clinics(clinic_id)
);

-- Bảng appointments: Lịch hẹn khám bệnh cho người dùng đã có tài khoản
CREATE TABLE appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,        -- Khóa chính
    user_id INT,                                 -- Liên kết đến bảng users
    guest_id INT,
    doctor_id INT NOT NULL,                               -- Liên kết đến bảng doctors
    clinic_id INT,                                        -- Liên kết đến bảng clinics (phòng khám)
    appointment_time DATETIME NOT NULL,                   -- Thời gian đặt lịch
    reason TEXT,                                          -- Lý do khám bệnh
    status VARCHAR(50) DEFAULT 'pending',                 -- Trạng thái: pending, confirmed, completed, canceled
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP             -- Thời gian cập nhật thông báo (nếu bị chỉnh sửa)
        ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (guest_id) REFERENCES guest_users(guest_id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id),
    FOREIGN KEY (clinic_id) REFERENCES clinics(clinic_id)
);

-- Bảng prescriptions: Đơn thuốc sau khi khám
CREATE TABLE prescriptions (
    prescription_id INT AUTO_INCREMENT PRIMARY KEY,     -- Khóa chính
    appointment_id INT NOT NULL,                        -- Liên kết đến lịch hẹn
    prescribed_date DATE DEFAULT CURRENT_DATE,          -- Ngày kê đơn
    medications TEXT,                                   -- Thuốc (có thể lưu dạng JSON/text)
    notes TEXT,                                         -- Ghi chú dùng thuốc
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP             -- Thời gian cập nhật thông báo (nếu bị chỉnh sửa)
        ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id)
);

-- Bảng medical_records: Ghi chú khám của bác sĩ
CREATE TABLE medical_records (
    med_rec_id INT AUTO_INCREMENT PRIMARY KEY,             -- Khóa chính
    appointment_id INT NOT NULL,                        -- Liên kết đến cuộc hẹn
    note_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,      -- Thời điểm ghi chú
    diagnosis TEXT,                                     -- Chẩn đoán
    recommendations TEXT,                               -- Hướng dẫn/chỉ định
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id)
);

----------------------------------------------------------------3. Chatbot AI-------------------------------------------------------------------------------
-- Bảng lưu dữ liệu sức khỏe định kỳ của người dùng (cân nặng, huyết áp, giấc ngủ, v.v.)
CREATE TABLE health_records (
    record_id INT AUTO_INCREMENT PRIMARY KEY,			 -- Khóa chính, tự động tăng
    user_id INT NOT NULL,								 -- liên kết đến bảng users
    record_date DATE NOT NULL,							 -- ngày ghi nhận dữ liệu
    weight FLOAT,										 -- cân nặng (kg)
    blood_pressure VARCHAR(20),							 -- huyết áp, vd: "120/80"
    sleep_hours FLOAT,									 -- số giờ ngủ
    notes TEXT,											 -- ghi chú thêm nếu có
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP             -- Thời gian cập nhật thông báo (nếu bị chỉnh sửa)
        ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Bảng lưu hội thoại giữa người dùng và chatbot AI
CREATE TABLE chat_logs (
    chat_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,									     -- người dùng chat (có thể null nếu là khách)
    guest_id INT,					                     -- phiên chat của khách (nếu user_id null)
	intent VARCHAR(100),                                 -- ý định
    message TEXT NOT NULL,                               -- nội dung tin nhắn
    sender ENUM('user', 'bot') NOT NULL,                 -- người gửi tin nhắn
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_user_or_guest
        CHECK (
            (user_id IS NOT NULL AND guest_id IS NULL) OR
            (user_id IS NULL AND guest_id IS NOT NULL)
        ),

    FOREIGN KEY (guest_id) REFERENCES guest_users(guest_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Bảng lưu kết quả dự đoán bệnh từ AI cho từng lần dự đoán
CREATE TABLE health_predictions (
    prediction_id INT AUTO_INCREMENT PRIMARY KEY,		 -- Khóa chính, tự động tăng
    user_id INT NOT NULL,								 -- liên kết đến người dùng
	record_id INT NOT NULL,                                       -- liên kết đến dữ liệu sức khỏe cụ thể
	chat_id INT,
    prediction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- thời gian dự đoán
    confidence_score FLOAT,                              -- độ tin cậy dự đoán (0-1)
    details TEXT,                                        -- chi tiết thêm về dự đoán (json hoặc text)
    
    CHECK (confidence_score BETWEEN 0 AND 1),
    
    FOREIGN KEY (user_id) REFERENCES users(user_id),
	FOREIGN KEY (record_id) REFERENCES health_records(record_id),
	FOREIGN KEY (chat_id) REFERENCES chat_logs(chat_id)
);

CREATE TABLE prediction_diseases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prediction_id INT NOT NULL,
    disease_name VARCHAR(255) NOT NULL,
    confidence FLOAT,
    FOREIGN KEY (prediction_id) REFERENCES health_predictions(prediction_id)
);

-- Bảng lưu câu hỏi và câu trả lời để huấn luyện hoặc phục vụ chatbot
CREATE TABLE chatbot_knowledge_base (
    kb_id INT AUTO_INCREMENT PRIMARY KEY,
	intent VARCHAR(100),                                 -- ý định
    question TEXT NOT NULL,                              -- câu hỏi mẫu
    answer TEXT NOT NULL,                                -- câu trả lời tương ứng
    category VARCHAR(100),                               -- phân loại câu hỏi (tùy chọn)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP             -- Thời gian cập nhật thông báo (nếu bị chỉnh sửa)
        ON UPDATE CURRENT_TIMESTAMP
);
----------------------------------------------------------------4. Thương mại điện tử-------------------------------------------------------------------------------
-- Bảng product_categories: Danh mục sản phẩm
CREATE TABLE product_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,          -- Khóa chính
    name VARCHAR(255) NOT NULL,                          -- Tên danh mục
    description TEXT,                                    -- Mô tả danh mục
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP             -- Thời gian cập nhật thông báo (nếu bị chỉnh sửa)
        ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng products: Danh sách sản phẩm
CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,           -- Khóa chính
    category_id INT,                                     -- Liên kết đến danh mục
    name VARCHAR(255) NOT NULL,                          -- Tên sản phẩm
    description TEXT,                                    -- Mô tả sản phẩm
    price DECIMAL(16, 0) NOT NULL,                       -- Giá
    stock INT DEFAULT 0,                                 -- Tồn kho
    image_url TEXT,                                      -- Ảnh sản phẩm (nếu có)
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP             -- Thời gian cập nhật thông báo (nếu bị chỉnh sửa)
        ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES product_categories(category_id)
);

CREATE TABLE medicines (
    medicine_id INT PRIMARY KEY,                         -- Khóa chính, trùng với product_id
    active_ingredient VARCHAR(255),                      -- Hoạt chất chính
    dosage_form VARCHAR(100),                            -- Dạng bào chế (viên, ống, gói, ...)
    unit VARCHAR(50),                                    -- Đơn vị tính: viên, ml, ...
    usage_instructions TEXT,                             -- Hướng dẫn dùng thuốc
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP             -- Thời gian cập nhật thông báo (nếu bị chỉnh sửa)
        ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (medicine_id) REFERENCES products(product_id) ON DELETE CASCADE
);

CREATE TABLE prescription_products (
    prescription_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    dosage TEXT,                                         -- Liều dùng
    usage_time TEXT,                                     -- Uống sáng/tối...
    PRIMARY KEY (prescription_id, product_id),
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(prescription_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);


-- Bảng product_reviews: Người dùng đánh giá sản phẩm
CREATE TABLE product_reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,            -- Khóa chính
    product_id INT NOT NULL,                             -- Liên kết đến sản phẩm
    user_id INT NOT NULL,                                -- Người đánh giá
    rating INT CHECK (rating BETWEEN 1 AND 5),           -- Số sao (1–5)
    comment TEXT,                                        -- Nhận xét
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP             -- Thời gian cập nhật thông báo (nếu bị chỉnh sửa)
        ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Bảng carts: Giỏ hàng tạm thời
CREATE TABLE carts (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,              -- Khóa chính
    user_id INT NOT NULL,                                -- Người dùng
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP             -- Thời gian cập nhật thông báo (nếu bị chỉnh sửa)
        ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Bảng orders: Đơn hàng của người dùng
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,             -- Khóa chính
    user_id INT NOT NULL,                                -- Người đặt hàng
    address_id INT NOT NULL,                             -- Liên kết đến bảng user_addresses
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,      -- Thời gian đặt
    total DECIMAL(16, 0) NOT NULL,                       -- Tổng tiền
    status VARCHAR(50) DEFAULT 'pending',                -- Trạng thái đơn hàng
    shipping_address TEXT NOT NULL,                      -- Địa chỉ giao hàng
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (address_id) REFERENCES user_addresses(id)  -- Liên kết địa chỉ giao hàng
);

-- Bảng order_items: Chi tiết từng sản phẩm trong đơn hàng
CREATE TABLE order_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,              -- Khóa chính
    order_id INT NOT NULL,                               -- Liên kết đến đơn hàng
    product_id INT NOT NULL,                             -- Sản phẩm trong đơn
    quantity INT NOT NULL,                               -- Số lượng mua
    unit_price DECIMAL(16, 0) NOT NULL,                  -- Giá mỗi sản phẩm lúc mua
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- Bảng payments: Thông tin thanh toán đơn hàng
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,             -- Khóa chính
    user_id INT,
    order_id INT NOT NULL,                                 -- Liên kết đến đơn hàng
    payment_method VARCHAR(50) NOT NULL,                   -- Phương thức (VNPay, Momo, COD...)
    payment_status VARCHAR(50) DEFAULT 'pending',          -- pending, completed, failed
    amount DECIMAL(16, 0) NOT NULL,                        -- Số tiền thanh toán
    payment_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,      -- Thời gian thanh toán
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- phan payments nay co le can xem xet theo cach lam cua backend

-- Bảng invoices: Thông tin hóa đơn
CREATE TABLE invoices (
    invoice_id INT AUTO_INCREMENT PRIMARY KEY,             -- Khóa chính
    COLUMN user_id INT
    order_id INT NOT NULL,                                 -- Liên kết đến đơn hàng
    invoice_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,      -- Ngày tạo hóa đơn
    total_amount DECIMAL(16, 0) NOT NULL,                  -- Tổng tiền
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Bảng invoice_details: Chi tiết sản phẩm trong hóa đơn
CREATE TABLE invoice_details (
    detail_id INT AUTO_INCREMENT PRIMARY KEY,              -- Khóa chính
    invoice_id INT NOT NULL,                               -- Liên kết đến hóa đơn
    product_id INT NOT NULL,                               -- Sản phẩm cụ thể
    quantity INT NOT NULL,                                 -- Số lượng
    unit_price DECIMAL(16, 0) NOT NULL,                    -- Giá đơn vị
    FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);
