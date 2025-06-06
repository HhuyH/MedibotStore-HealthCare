ROLE_PERMISSIONS = {
    "Admin": ["chat", "view_all", "manage_users"],
    "Doctor": ["chat", "view_patients"],
    "User": ["chat"],
    "Guest": []  # Khách chưa đăng nhập
}

def normalize_role(role):
    if role is None:
        return "Guest"
    if not isinstance(role, str) or role.strip() == "":
        return "Guest"
    return role

# Hàm kiểm tra quyền
def has_permission(role: str, permission: str) -> bool:
    return permission in ROLE_PERMISSIONS.get(role, [])

def log_and_validate_user(msg):
    print(f"User {msg.user_id} ({msg.username}) với vai trò {msg.role} gửi: {msg.message}")
    
    if msg.role != "admin":
        # Có thể raise lỗi hoặc return False
        print("⚠️ User không có quyền admin.")
        return False
    return True