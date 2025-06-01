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
    id INT AUTO_INCREMENT PRIMARY KEY,                    -- Khóa chính
    prescription_id INT NOT NULL,                         -- Liên kết đơn thuốc
    product_id INT NULL,                                  -- Có thể NULL nếu không rõ mã sản phẩm
    quantity INT NOT NULL,                                -- Số lượng
    dosage TEXT,                                           -- Liều dùng
    usage_time TEXT,                                       -- Thời gian sử dụng
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
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


-------------------------------------------------------Xác minh tài khoản--------------------------------------------------------------------------------------------------------------

-- Giải thích
-- chức năng này yêu cầu nhập vào
-- usename or email
-- password đã được hash ở trông backend
-- sâu đó sẽ được chuyển về sql và kiểm tra và sau đó trả về backend với json như sau
-- Nếu đúng pass
{
  "success": true, -- succes sẽ trả về true hoặc 1
  "user_id": 123,
  "role": "admin"
}
-- Nếu sai
{
  "success": false, -- succes sẽ trả về false hoặc 0
  "message": "Thông tin đăng nhập không hợp lệ"
}


DELIMITER $$

CREATE PROCEDURE login_user (
    IN input_username_or_email VARCHAR(100),
    IN input_password_hash VARCHAR(255)
)
BEGIN
    DECLARE user_id_result INT;
    DECLARE role_name_result VARCHAR(50);
    
    -- Truy vấn người dùng có tồn tại không
    SELECT u.user_id, r.role_name
    INTO user_id_result, role_name_result
    FROM users u
    JOIN roles r ON u.role_id = r.role_id
    WHERE (u.username = input_username_or_email OR u.email = input_username_or_email)
      AND u.password_hash = input_password_hash
    LIMIT 1;

    -- Nếu tìm được thì trả kết quả
    IF user_id_result IS NOT NULL THEN
        SELECT TRUE AS success, user_id_result AS user_id, role_name_result AS role;
    ELSE
        SELECT FALSE AS success, NULL AS user_id, NULL AS role;
    END IF;
END$$

DELIMITER ;

-- password 123 được hash
$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC
-- test proc login_user
CALL login_user('admin', '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC');


-------------------------------------------------------Gọi lấy thông tin user--------------------------------------------------------------------------------------------------------------

-- Gọi proc này sẽ chuyền toàn bộ những info cần thiết để vận hành 
-- Nếu thông tin đăng nhập email,username,phone sai thì sẽ ko thể lấy được bất kỳ thông tin gì
-- Nếu đúng thì sẽ gửi những thông tin của tài khoản đó và cả password đã được hash 
-- Sau đó thì backend sẽ kiểm tra pass được gửi từ database với với pass người dùng vừa nhập

DELIMITER $$

CREATE PROCEDURE get_user_info (
    IN input_login VARCHAR(100)
)
BEGIN
    SELECT u.user_id, u.username, u.email, u.password_hash, r.role_name
    FROM users u
    JOIN roles r ON u.role_id = r.role_id
    WHERE u.username = input_login OR u.email = input_login OR u.phone_number = input_login
    LIMIT 1;
END$$

DELIMITER ;


-------------------------------------------------------Kiểm tra triệu chứng bệnh nhân--------------------------------------------------------------------------------------------------------------

DELIMITER $$

CREATE PROCEDURE get_user_symptom_history(IN in_user_id INT)
BEGIN
    SELECT 
        u.full_name AS `Họ tên`,
        h.notes AS `Ghi chú`,
        s.name AS `Triệu chứng`
    FROM user_symptom_history h
    JOIN symptoms s ON h.symptom_id = s.symptom_id
    JOIN users_info u ON u.user_id = h.user_id
    WHERE h.user_id = in_user_id
    ORDER BY h.record_date;
END $$

DELIMITER ;


CALL get_user_symptom_history(4);


-------------------------------------------------------Lấy thông tin chi tiết của 1 người dựa trên user_id--------------------------------------------------------------------------------------------------------------


DELIMITER $$

CREATE PROCEDURE get_user_details(IN in_user_id INT)
BEGIN
    SELECT 
        u.user_id AS `User ID`,
        u.username AS `Username`,
        u.email AS `Email`,
        u.phone_number AS `Số điện thoại`,
        r.role_name AS `Vai trò`,
        ui.full_name AS `Họ tên`,
        ui.gender AS `Giới tính`,
        ui.date_of_birth AS `Ngày sinh`,
        ui.profile_picture AS `Ảnh đại diện`,
        a.address_line AS `Địa chỉ`,
        a.ward AS `Phường/Xã`,
        a.district AS `Quận/Huyện`,
        a.city AS `Thành phố`,
        a.country AS `Quốc gia`,
        a.is_default AS `Là địa chỉ mặc định`
    FROM users u
    LEFT JOIN users_info ui ON u.user_id = ui.user_id
    LEFT JOIN roles r ON u.role_id = r.role_id
    LEFT JOIN user_addresses a ON u.user_id = a.user_id AND a.is_default = TRUE
    WHERE u.user_id = in_user_id;
END $$

DELIMITER ;


CALL get_user_details(2);


-------------------------------------------------------Lấy tất cả địa chỉ của 1 người dựa trên user_id--------------------------------------------------------------------------------------------------------------

DELIMITER $$

CREATE PROCEDURE get_user_addresses(IN in_user_id INT)
BEGIN
    SELECT 
        a.id AS `Địa chỉ ID`,
        a.address_line AS `Địa chỉ`,
        a.ward AS `Phường/Xã`,
        a.district AS `Quận/Huyện`,
        a.city AS `Thành phố`,
        a.postal_code AS `Mã bưu chính`,
        a.country AS `Quốc gia`,
        a.is_default AS `Là mặc định`,
        a.created_at AS `Ngày tạo`,
        a.updated_at AS `Ngày cập nhật`
    FROM user_addresses a
    WHERE a.user_id = in_user_id
    ORDER BY a.is_default DESC, a.updated_at DESC;
END $$
DELIMITER ;


CALL get_user_addresses(2);

-------------------------------------------------------Lấy tất cả người dùng bằng role_id-------------------------------------------------------------------------------------------------------------

-- nếu nhập vào role tương ứng thì sẽ gọi role tương ứng
-- nếu call 
-- role_id = 0 lấy tất cả người dùng
-- role_id = 1 lấy tất cả Admin
-- role_id = 2 lấy tất cả Doctor
-- role_id = 3 lấy tất cả Patient
DELIMITER $$

CREATE PROCEDURE get_all_users_by_role(IN input_role_id INT)
BEGIN
    SELECT 
        u.user_id,
        u.username,
        u.email,
        u.phone_number,
        r.role_name,
        ui.full_name,
        ui.gender,
        ui.date_of_birth,
        ua.address_line,
        ua.ward,
        ua.district,
        ua.city,
        ua.country,
        u.created_at
    FROM users u
    LEFT JOIN users_info ui ON u.user_id = ui.user_id
    LEFT JOIN roles r ON u.role_id = r.role_id
    LEFT JOIN user_addresses ua ON u.user_id = ua.user_id AND ua.is_default = TRUE
    WHERE (input_role_id = 0 OR u.role_id = input_role_id)
    ORDER BY u.user_id DESC;
END $$

DELIMITER ;

----------------------------------------------USERS----------------------------------------------------------------------------------------------------------------
INSERT INTO users (username, email, phone_number, password_hash, role_id, created_at)
VALUES
('admin', 'admin@gmail.com', '0123456789',
 '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC',--id 1
 1, NOW()),

('huy', 'hoanhuy12@gmail.com', '0999999999',
 '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC',--id 2
 1, NOW()),

('dr.hanh', 'docter@example.com', '0888888888',
 '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC',--id 3
 2, NOW());

('nguyenvana', 'vana@example.com', '0901234567',
 '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC',--id 4
 3, NOW());

('linh', 'linh@gmail.com', '0123466789',
 '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC',--id 6
 2, NOW()), 

----------------------------------------------GUEST_USERS----------------------------------------------------------------------------------------------------------------
INSERT INTO guest_users (full_name, phone, email)
VALUES
('Nguyễn Văn A', '0909123456', 'nva@example.com'),
('Trần Thị B', '0911234567', 'ttb@example.com'),
('Lê Văn C', '0922345678', 'lvc@example.com');

----------------------------------------------USERS_info----------------------------------------------------------------------------------------------------------------
INSERT INTO users_info (user_id, full_name, gender, date_of_birth)
VALUES
(1, 'Quản trị viên', 'Nam', '1990-01-01'),
(2, 'Huy', 'Nam', '1985-06-15'),
(3, 'Dr.Hand', 'nữ', '2000-12-01');
(4, 'Nguyễn Văn A', 'Nam', '1995-08-15');
(6, 'Dr.Linh', 'Nữ', '1995-08-15');

----------------------------------------------USERS_ADDRESSES----------------------------------------------------------------------------------------------------------------
INSERT INTO user_addresses (
    user_id, address_line, ward, district, city, postal_code, country, is_default
)
VALUES
-- Quản trị viên (user_id = 1)
(1, '123 Trần Hưng Đạo', 'Nguyễn Cư Trinh', 'Quận 1', 'TP.HCM', '700000', 'Vietnam', TRUE),

-- Hòa Huy (user_id = 2)
(2, '456 Lê Lợi', 'Bến Nghé', 'Quận 1', 'TP.HCM', '700000', 'Vietnam', TRUE),
(2, '111 Đường long', 'Bến Nghé', 'Quận 11', 'TP.HCM', '110000', 'Vietnam', TRUE),

-- John Doe (user_id = 3)
(3, '789 Lý Thường Kiệt', 'Phường 7', 'Quận 10', 'TP.HCM', '700000', 'Vietnam', TRUE);

-- Nguyễn văn A (user_id=4)
(4, '123 Đường Lý Thường Kiệt', 'Phường 7', 'Quận 10', 'TP.HCM', '70000', TRUE);

-------------------------------------------------------medical_categories--------------------------------------------------------------------------------------------------------------
INSERT INTO medical_categories (name, description) VALUES
('Tim mạch', 'Chuyên khoa liên quan đến tim và mạch máu'),
('Hô hấp', 'Chuyên khoa về phổi và hệ hô hấp'),
('Tiêu hóa', 'Chuyên khoa về dạ dày, ruột, gan...'),
('Thần kinh', 'Chuyên khoa về não và hệ thần kinh'),
('Da liễu', 'Chuyên khoa về da, tóc và móng');


-------------------------------------------------------diseases--------------------------------------------------------------------------------------------------------------

INSERT INTO diseases (name, description, treatment_guidelines, category_id) VALUES
('Tăng huyết áp', 'Huyết áp cao mãn tính', 'Theo dõi huyết áp thường xuyên, dùng thuốc hạ áp', 1),
('Đột quỵ', 'Rối loạn tuần hoàn não nghiêm trọng', 'Can thiệp y tế khẩn cấp, phục hồi chức năng', 1),
('Hen suyễn', 'Bệnh mãn tính ảnh hưởng đến đường thở', 'Sử dụng thuốc giãn phế quản và kiểm soát dị ứng', 2),
('Viêm phổi', 'Nhiễm trùng phổi do vi khuẩn hoặc virus', 'Kháng sinh, nghỉ ngơi và điều trị hỗ trợ', 2),
('Viêm dạ dày', 'Viêm lớp niêm mạc dạ dày', 'Tránh thức ăn cay, dùng thuốc kháng acid', 3),
('Xơ gan', 'Tổn thương gan mạn tính', 'Kiểm soát nguyên nhân, chế độ ăn và theo dõi y tế', 3),
('Động kinh', 'Rối loạn thần kinh gây co giật lặp lại', 'Dùng thuốc chống động kinh, theo dõi điện não đồ', 4),
('Trầm cảm', 'Rối loạn tâm trạng kéo dài', 'Liệu pháp tâm lý và thuốc chống trầm cảm', 4),
('Viêm da cơ địa', 'Bệnh da mãn tính gây ngứa và phát ban', 'Dưỡng ẩm, thuốc bôi chống viêm', 5),
('Nấm da', 'Nhiễm trùng da do nấm', 'Thuốc kháng nấm dạng bôi hoặc uống', 5);


-------------------------------------------------------symptoms--------------------------------------------------------------------------------------------------------------
INSERT INTO symptoms (name, description) VALUES
('Đau đầu', 'Cảm giác đau ở vùng đầu hoặc cổ'),
('Khó thở', 'Khó khăn trong việc hít thở bình thường'),
('Buồn nôn', 'Cảm giác muốn nôn mửa'),
('Sốt', 'Nhiệt độ cơ thể cao hơn bình thường'),
('Tức ngực', 'Cảm giác đau hoặc áp lực ở ngực'),
('Mệt mỏi', 'Cảm giác kiệt sức, thiếu năng lượng'),
('Co giật', 'Chuyển động không kiểm soát của cơ'),
('Ngứa da', 'Cảm giác châm chích khiến muốn gãi'),
('Phát ban', 'Vùng da bị nổi mẩn đỏ hoặc sưng'),
('Chán ăn', 'Mất cảm giác thèm ăn');


-------------------------------------------------------liên kết diseases với symptoms--------------------------------------------------------------------------------------------------------------
-- Tăng huyết áp
INSERT INTO disease_symptoms (disease_id, symptom_id) VALUES
(1, 1), -- Đau đầu
(1, 5), -- Tức ngực
(1, 6); -- Mệt mỏi

-- Đột quỵ
INSERT INTO disease_symptoms (disease_id, symptom_id) VALUES
(2, 1),
(2, 6),
(2, 7); -- Co giật

-- Hen suyễn
INSERT INTO disease_symptoms (disease_id, symptom_id) VALUES
(3, 2),
(3, 5),
(3, 6);

-- Viêm phổi
INSERT INTO disease_symptoms (disease_id, symptom_id) VALUES
(4, 2),
(4, 4),
(4, 6);

-- Viêm dạ dày
INSERT INTO disease_symptoms (disease_id, symptom_id) VALUES
(5, 3),
(5, 4),
(5, 10); -- Chán ăn

-- Xơ gan
INSERT INTO disease_symptoms (disease_id, symptom_id) VALUES
(6, 6),
(6, 10);

-- Động kinh
INSERT INTO disease_symptoms (disease_id, symptom_id) VALUES
(7, 1),
(7, 7);

-- Trầm cảm
INSERT INTO disease_symptoms (disease_id, symptom_id) VALUES
(8, 6),
(8, 10);

-- Viêm da cơ địa
INSERT INTO disease_symptoms (disease_id, symptom_id) VALUES
(9, 8),
(9, 9);

-- Nấm da
INSERT INTO disease_symptoms (disease_id, symptom_id) VALUES
(10, 8),
(10, 9);

-------------------------------------------------------Lịch sử chiệu chứng của bênh nhân Nguyễn Văn A user_id = 4--------------------------------------------------------------------------------------------------------------
INSERT INTO user_symptom_history (user_id, symptom_id, record_date, notes) VALUES
(4, 4, '2025-05-18', 'Sốt cao 39 độ, kéo dài 2 ngày'),
(4, 1, '2025-05-18', 'Đau đầu âm ỉ vùng trán và sau gáy'),
(4, 2, '2025-05-19', 'Khó thở nhẹ, đặc biệt khi leo cầu thang'),
(4, 6, '2025-05-20', 'Cảm thấy mệt mỏi suốt cả ngày'),
(4, 5, '2025-05-21', 'Cảm giác tức ngực nhẹ khi hít sâu');

-------------------------------------------------------Phòng khám--------------------------------------------------------------------------------------------------------------
INSERT INTO clinics (name, address, phone, email, description) VALUES
('Phòng khám Đa khoa Hòa Hảo', '254 Hòa Hảo, Quận 10, TP.HCM', '02838553085', 'hoahao@example.com', 'Phòng khám tư nhân uy tín với nhiều chuyên khoa.'),
('Bệnh viện Chợ Rẫy', '201B Nguyễn Chí Thanh, Quận 5, TP.HCM', '02838554137', 'choray@hospital.vn', 'Bệnh viện tuyến trung ương chuyên điều trị các ca nặng.'),
('Phòng khám Quốc tế Victoria Healthcare', '79 Điện Biên Phủ, Quận 1, TP.HCM', '02839101717', 'info@victoriavn.com', 'Dịch vụ khám chữa bệnh theo tiêu chuẩn quốc tế.'),
('Bệnh viện Đại học Y Dược', '215 Hồng Bàng, Quận 5, TP.HCM', '02838552307', 'contact@umc.edu.vn', 'Bệnh viện trực thuộc Đại học Y Dược TP.HCM.'),
('Phòng khám đa khoa Pasteur', '27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '02838232299', 'pasteurclinic@vnmail.com', 'Chuyên nội tổng quát, tim mạch, tiêu hóa.');

---------------------------------------------------------------------------------Khoa--------------------------------------------------------------------------------------------------------------
INSERT INTO specialties (name, description) VALUES
('Nội khoa', 'Chẩn đoán và điều trị không phẫu thuật các bệnh lý nội tạng.'),
('Ngoại khoa', 'Chẩn đoán và điều trị bệnh thông qua phẫu thuật.'),
('Tai - Mũi - Họng', 'Khám và điều trị các bệnh lý về tai, mũi và họng.'),
('Tim mạch', 'Chuyên điều trị bệnh về tim và hệ tuần hoàn.'),
('Nhi khoa', 'Chăm sóc và điều trị cho trẻ em từ sơ sinh đến 15 tuổi.'),
('Da liễu', 'Chẩn đoán và điều trị các bệnh về da, tóc và móng.'),
('Tiêu hóa', 'Chuyên về hệ tiêu hóa như dạ dày, gan, ruột.'),
('Thần kinh', 'Khám và điều trị các bệnh về hệ thần kinh trung ương và ngoại biên.');

---------------------------------------------------------------------------------Bác sĩ---------------------------------------------------------------------------------------------------------------------
-- user_id = 3 là bác sĩ Nội khoa tại Phòng khám Đa khoa Hòa Hảo
-- user_id = 6 là bác sĩ Tim mạch tại Bệnh viện Chợ Rẫy

INSERT INTO doctors (user_id, specialty_id, clinic_id, biography)
VALUES
(3, 1, 1, 'Bác sĩ Nội khoa với hơn 10 năm kinh nghiệm trong điều trị tiểu đường, huyết áp. Tốt nghiệp Đại học Y Dược TP.HCM.'),
(6, 4, 2, 'Bác sĩ Tim mạch từng công tác tại Viện Tim TP.HCM. Có bằng Thạc sĩ Y khoa từ Đại học Paris, Pháp.');

---------------------------------------------------------------------------------Lịch làm việc bác sĩ---------------------------------------------------------------------------------------------------------------------
-- Lịch bác sĩ Nội khoa (doctor_id = 1) tại phòng khám 1
INSERT INTO doctor_schedules (doctor_id, clinic_id, day_of_week, start_time, end_time)
VALUES
(1, 1, 'Monday', '08:00:00', '12:00:00'),
(1, 1, 'Wednesday', '08:00:00', '12:00:00'),
(1, 1, 'Friday', '13:30:00', '17:30:00');

-- Lịch bác sĩ Tim mạch (doctor_id = 2) tại phòng khám 2
INSERT INTO doctor_schedules (doctor_id, clinic_id, day_of_week, start_time, end_time)
VALUES
(2, 2, 'Tuesday', '09:00:00', '12:00:00'),
(2, 2, 'Thursday', '14:00:00', '18:00:00'),
(2, 2, 'Saturday', '08:30:00', '11:30:00');

---------------------------------------------------------------------------------Đặt lịch khám---------------------------------------------------------------------------------------------------------------------

-- user_id = 4 đặt khám bác sĩ Nội khoa (user_id = 3, doctor_id = 1) tại Phòng khám Đa khoa Hòa Hảo
INSERT INTO appointments (user_id, doctor_id, clinic_id, appointment_time, reason, status)
VALUES 
(4, 1, 1, '2025-05-28 09:00:00', 'Khám huyết áp và mệt mỏi kéo dài', 'confirmed'),
(4, 1, 1, '2025-06-01 14:30:00', 'Theo dõi tiểu đường định kỳ', 'pending');

-- guest_id = 1 khám Nội khoa (doctor_id = 1) tại Phòng khám Đa khoa Hòa Hảo
-- guest_id = 2 khám Tim mạch (doctor_id = 2) tại Bệnh viện Chợ Rẫy
-- guest_id = 3 khám Tim mạch (doctor_id = 2) tại Bệnh viện Chợ Rẫy

INSERT INTO appointments (guest_id, doctor_id, clinic_id, appointment_time, reason, status)
VALUES
(1, 1, 1, '2025-05-25 10:00:00', 'Đau đầu và cao huyết áp gần đây', 'confirmed'),
(2, 2, 2, '2025-05-27 08:00:00', 'Khó thở, nghi ngờ bệnh tim', 'pending'),
(3, 2, 2, '2025-05-29 15:00:00', 'Đặt lịch kiểm tra tim định kỳ', 'canceled');

---------------------------------------------------------------------------------Đơn thuốc---------------------------------------------------------------------------------------------------------------------

-- Đơn thuốc cho lịch hẹn của user_id = 4 (appointment_id = 1 và 2)
INSERT INTO prescriptions (appointment_id, prescribed_date, medications, notes)
VALUES
(1, '2025-05-28', '[
  {"name": "Thuốc hạ áp Amlodipine", "dosage": "5mg", "frequency": "1 viên/ngày"},
  {"name": "Paracetamol", "dosage": "500mg", "frequency": "2 viên/ngày khi đau đầu"}
]', 'Uống vào buổi sáng sau ăn. Tránh dùng với rượu bia.'),

(2, '2025-06-01', '[
  {"name": "Metformin", "dosage": "500mg", "frequency": "2 lần/ngày"},
  {"name": "Glimepiride", "dosage": "2mg", "frequency": "1 lần/ngày trước ăn sáng"}
]', 'Kiểm tra đường huyết trước mỗi lần dùng thuốc.');

-- Đơn thuốc cho khách vãng lai guest_id = 1 (appointment_id = 3)
INSERT INTO prescriptions (appointment_id, prescribed_date, medications, notes)
VALUES
(3, '2025-05-25', '[
  {"name": "Losartan", "dosage": "50mg", "frequency": "1 viên mỗi sáng"},
  {"name": "Vitamin B1", "dosage": "100mg", "frequency": "1 viên/ngày"}
]', 'Tái khám sau 1 tuần nếu triệu chứng không giảm.');

---------------------------------------------------------------------------------Ghi chú của bác sĩ---------------------------------------------------------------------------------------------------------------------

-- Ghi chú khám của bác sĩ cho các lịch hẹn của user_id = 4
INSERT INTO medical_records (appointment_id, diagnosis, recommendations)
VALUES
(1, 'Tăng huyết áp giai đoạn 1', 'Cần điều chỉnh chế độ ăn và tập thể dục. Uống thuốc đều đặn.'),
(2, 'Tiểu đường tuýp 2', 'Kiểm tra HbA1c 3 tháng/lần. Hạn chế đường và tinh bột.');

-- Ghi chú khám cho khách guest_id = 1
INSERT INTO medical_records (appointment_id, diagnosis, recommendations)
VALUES
(3, 'Cao huyết áp do căng thẳng', 'Nghỉ ngơi hợp lý, tránh thức khuya. Theo dõi huyết áp hàng ngày.');

----------------------------------------------------------------4. Thương mại điện tử-------------------------------------------------------------------------------

--🗂️ product_categories: Danh mục sản phẩm
INSERT INTO product_categories (name, description) VALUES
('Thuốc điều trị', 'Các loại thuốc dùng để điều trị bệnh lý.'),
('Thực phẩm chức năng', 'Sản phẩm hỗ trợ tăng cường sức khỏe.'),
('Thiết bị y tế', 'Các thiết bị và dụng cụ y tế sử dụng trong chẩn đoán và điều trị.'),
('Vật tư tiêu hao', 'Găng tay, khẩu trang, bông băng,... sử dụng một lần.');

--📦 products: Danh sách sản phẩm
INSERT INTO products (category_id, name, description, price, stock, image_url)
VALUES
(1, 'Paracetamol 500mg', 'Thuốc hạ sốt, giảm đau thường dùng.', 15000, 100, 'https://example.com/images/paracetamol.jpg'),
(1, 'Amoxicillin 500mg', 'Kháng sinh phổ rộng nhóm penicillin.', 28000, 60, 'https://example.com/images/amoxicillin.jpg'),
(2, 'Vitamin C 1000mg', 'Hỗ trợ tăng cường đề kháng.', 50000, 200, 'https://example.com/images/vitaminC.jpg'),
(3, 'Máy đo huyết áp điện tử', 'Thiết bị đo huyết áp tại nhà.', 650000, 15, 'https://example.com/images/blood_pressure_monitor.jpg'),
(4, 'Khẩu trang y tế 4 lớp', 'Hộp 50 cái, đạt chuẩn kháng khuẩn.', 40000, 500, 'https://example.com/images/face_mask.jpg');

------------------------------------------------------------💊 medicines: Thông tin chi tiết thuốc (chỉ áp dụng với sản phẩm là thuốc)------------------------------------------------------------------------------------
INSERT INTO medicines (medicine_id, active_ingredient, dosage_form, unit, usage_instructions)
VALUES
(1, 'Paracetamol', 'Viên nén', 'viên', 'Uống 1–2 viên mỗi 4–6 giờ nếu cần. Không dùng quá 8 viên/ngày.'),
(2, 'Amoxicillin', 'Viên nang', 'viên', 'Uống 1 viên mỗi 8 giờ, duy trì trong 5–7 ngày.');

--------------------------------------------------- prescription_products: Sản phẩm thực tế được kê trong đơn thuốc------------------------------------------------------------------------------------
-- Đơn thuốc 1 (của user_id = 4, appointment_id = 1)
INSERT INTO prescription_products (prescription_id, product_id, quantity, dosage, usage_time)
VALUES
(1, 1, 10, '500mg', '2 viên/ngày khi đau đầu'),    -- Paracetamol
(1, NULL, 7, '5mg', '1 viên/ngày');                -- Amlodipine chưa có trong products, có thể là thuốc ngoài danh mục

-- Đơn thuốc 2 (của user_id = 4, appointment_id = 2)
INSERT INTO prescription_products (prescription_id, product_id, quantity, dosage, usage_time)
VALUES
(2, NULL, 14, '500mg', '2 lần/ngày'),              -- Metformin, không có trong bảng `products`
(2, NULL, 7, '2mg', '1 lần/ngày trước ăn sáng');   -- Glimepiride, cũng không có trong bảng `products`

-- Đơn thuốc 3 (của guest_id = 1, appointment_id = 3)
INSERT INTO prescription_products (prescription_id, product_id, quantity, dosage, usage_time)
VALUES
(3, NULL, 7, '50mg', '1 viên mỗi sáng'),           -- Losartan
(3, NULL, 7, '100mg', '1 viên/ngày');              -- Vitamin B1


-------------------------------------------------------------------------------------- product_reviews------------------------------------------------------------------------------------
-- Huy (user_id = 2) đánh giá Paracetamol (product_id = 1)
INSERT INTO product_reviews (product_id, user_id, rating, comment)
VALUES
(1, 2, 5, 'Thuốc giảm đau hiệu quả, ít tác dụng phụ.'),

-- Huy (user_id = 2) đánh giá Amoxicillin (product_id = 2)
(2, 2, 4, 'Tốt nhưng gây buồn nôn nhẹ.'),

-- Admin (user_id = 1) đánh giá máy đo huyết áp (product_id = 4)
(4, 1, 5, 'Dễ sử dụng và rất chính xác.'),

-- Người dùng "dr.hanh" (user_id = 3) đánh giá Vitamin C (product_id = 3)
(3, 3, 4, 'Khá ổn để tăng sức đề kháng. Đóng gói đẹp.');

