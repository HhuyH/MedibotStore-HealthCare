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

