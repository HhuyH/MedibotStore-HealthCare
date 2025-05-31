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
    WHERE u.username = input_login OR u.email = input_login
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
        ui.phone AS `Số điện thoại`,
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

