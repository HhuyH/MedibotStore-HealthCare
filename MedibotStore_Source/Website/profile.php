<?php
include 'includes/db.php';

session_start();
// kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); exit;
}
$user_id = $_SESSION['user_id'];
$err = $msg = '';

// Lấy thông tin user đầy đủ
$stmt = $conn->prepare("SELECT u.username, u.email, ui.phone, ui.full_name, ui.gender, ui.date_of_birth, ui.profile_picture FROM users u JOIN users_info ui ON u.user_id=ui.user_id WHERE u.user_id=?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Lấy địa chỉ mặc định của user
$stmt = $conn->prepare("SELECT * FROM user_addresses WHERE user_id=? AND is_default=1 LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$address = $stmt->get_result()->fetch_assoc();

// Cập nhật thông tin cá nhân
if (isset($_POST['update_info'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $gender = $_POST['gender'];
    $date_of_birth = $_POST['date_of_birth'];
    
    if ($full_name && $email) {
        // Cập nhật bảng users_info (bao gồm phone)
        $stmt1 = $conn->prepare("UPDATE users_info SET full_name=?, gender=?, date_of_birth=?, phone=? WHERE user_id=?");
        $stmt1->bind_param('ssssi', $full_name, $gender, $date_of_birth, $phone_number, $user_id);
        $stmt1->execute();
        
        // Cập nhật bảng users (chỉ email)
        $stmt2 = $conn->prepare("UPDATE users SET email=? WHERE user_id=?");
        $stmt2->bind_param('si', $email, $user_id);
        $stmt2->execute();
        
        $msg = 'Cập nhật thông tin cá nhân thành công!';
        
        // Update session data
        $_SESSION['full_name'] = $full_name;
    } else {
        $err = 'Vui lòng nhập đầy đủ thông tin bắt buộc!';
    }
}

// Cập nhật địa chỉ
if (isset($_POST['update_address'])) {
    $address_line = trim($_POST['address_line']);
    $ward = trim($_POST['ward']);
    $district = trim($_POST['district']);
    $city = trim($_POST['city']);
    $postal_code = trim($_POST['postal_code']);
    $country = trim($_POST['country']) ?: 'Vietnam';
    
    if ($address_line && $ward && $district && $city) {
        // Kiểm tra xem đã có địa chỉ mặc định chưa
        $stmt = $conn->prepare("SELECT address_id FROM user_addresses WHERE user_id=? AND is_default=1");
if (!$stmt) {
    $err = 'Lỗi truy vấn: ' . $conn->error;
    return;
}
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        
        if ($existing) {
            // Cập nhật địa chỉ hiện tại
            $stmt = $conn->prepare("UPDATE user_addresses SET address_line=?, ward=?, district=?, city=?, postal_code=?, country=? WHERE user_id=? AND is_default=1");
            $stmt->bind_param('ssssssi', $address_line, $ward, $district, $city, $postal_code, $country, $user_id);
        } else {
            // Tạo địa chỉ mới
            $stmt = $conn->prepare("INSERT INTO user_addresses (user_id, address_line, ward, district, city, postal_code, country, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->bind_param('issssss', $user_id, $address_line, $ward, $district, $city, $postal_code, $country);
        }
        $stmt->execute();
        
        $msg = 'Cập nhật địa chỉ thành công!';
    } else {
        $err = 'Vui lòng nhập đầy đủ thông tin địa chỉ!';
    }
}

// Đổi mật khẩu
if (isset($_POST['change_pass'])) {
    $old = $_POST['old_pass'];
    $new = $_POST['new_pass'];
    $confirm = $_POST['confirm_pass'];
    
    if ($new !== $confirm) {
        $err = 'Mật khẩu xác nhận không khớp!';
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id=?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $pw = $stmt->get_result()->fetch_assoc();
        if ($old === $pw['password']) {
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE user_id=?");
            $stmt->bind_param('si', $new, $user_id);
        $stmt->execute();
        $msg = 'Đổi mật khẩu thành công!';
    } else {
        $err = 'Mật khẩu cũ không đúng!';
    }
}
}

// Upload ảnh đại diện
if (isset($_POST['upload_avatar']) && isset($_FILES['avatar']['name']) && $_FILES['avatar']['name']) {
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $file_type = $_FILES['avatar']['type'];
    $file_size = $_FILES['avatar']['size'];
    
    if (!in_array($file_type, $allowed_types)) {
        $err = 'Chỉ cho phép upload file ảnh (JPG, PNG, GIF)!';
    } elseif ($file_size > 5000000) { // 5MB
        $err = 'Kích thước file không được vượt quá 5MB!';
    } else {
        $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $target = 'assets/images/avatar_' . $user_id . '_' . time() . '.' . $file_extension;
        
    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
            // Delete old avatar if exists
            if ($user['profile_picture'] && file_exists($user['profile_picture'])) {
                unlink($user['profile_picture']);
            }
            
        $stmt = $conn->prepare("UPDATE users_info SET profile_picture=? WHERE user_id=?");
        $stmt->bind_param('si', $target, $user_id);
        $stmt->execute();
        $msg = 'Cập nhật ảnh đại diện thành công!';
    } else {
        $err = 'Tải ảnh thất bại!';
    }
}
}

// Reload lại thông tin mới nhất
$stmt = $conn->prepare("SELECT u.username, u.email, ui.phone, ui.full_name, ui.gender, ui.date_of_birth, ui.profile_picture FROM users u JOIN users_info ui ON u.user_id=ui.user_id WHERE u.user_id=?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Reload địa chỉ
$stmt = $conn->prepare("SELECT * FROM user_addresses WHERE user_id=? AND is_default=1 LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$address = $stmt->get_result()->fetch_assoc();

// Get user role
$role_names = [1 => 'Quản trị viên', 2 => 'Bệnh nhân', 3 => 'Bác sĩ'];
$user_role = $role_names[$_SESSION['role_id']] ?? 'Người dùng';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ sơ cá nhân - MediBot Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/profile.css">
    
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="profile-wrapper">
        <div class="profile-container">
            <!-- Sidebar -->
            <div class="profile-sidebar">
                <!-- Alerts -->
                <?php if($err): ?>
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-exclamation-triangle"></i>
                        <?= htmlspecialchars($err) ?>
                    </div>
                <?php endif; ?>
                
                <?php if($msg): ?>
                    <div class="alert alert-success">
                        <i class="fa-solid fa-check-circle"></i>
                        <?= htmlspecialchars($msg) ?>
                    </div>
                <?php endif; ?>

                <div class="profile-avatar-section">
                    <div class="avatar-wrapper">
                        <img src="<?= $user['profile_picture'] ? htmlspecialchars($user['profile_picture']) : '/assets/images/default-avatar.png' ?>" 
                             alt="Avatar" class="profile-avatar" id="currentAvatar">
                        <div class="avatar-edit" onclick="document.getElementById('avatarInput').click()">
                            <i class="fa-solid fa-camera"></i>
                        </div>
                    </div>
                    
                    <div class="profile-name"><?= htmlspecialchars($user['full_name']) ?></div>
                    <div class="profile-role">
                        <i class="fa-solid fa-user-tag me-1"></i>
                        <?= htmlspecialchars($user_role) ?>
                    </div>
                    <div class="profile-username">
                        <i class="fa-solid fa-at me-1"></i>
                        <?= htmlspecialchars($user['username']) ?>
                    </div>
                    
                    <?php if ($user['gender']): ?>
                    <div class="profile-detail">
                        <i class="fa-solid fa-venus-mars me-1"></i>
                        <?= htmlspecialchars($user['gender']) ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($user['date_of_birth']): ?>
                    <div class="profile-detail">
                        <i class="fa-solid fa-birthday-cake me-1"></i>
                        <?= date('d/m/Y', strtotime($user['date_of_birth'])) ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($address): ?>
                    <div class="profile-detail">
                        <i class="fa-solid fa-map-marker-alt me-1"></i>
                        <?= htmlspecialchars($address['city'] . ', ' . $address['country']) ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-number">12</span>
                        <span class="stat-label">Lịch hẹn</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">8</span>
                        <span class="stat-label">Hồ sơ</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">24</span>
                        <span class="stat-label">Tin nhắn</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">5</span>
                        <span class="stat-label">Đánh giá</span>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="profile-content">
                <div class="content-header">
                    <h1 class="content-title">Hồ sơ cá nhân</h1>
                    <p class="content-subtitle">Quản lý thông tin cá nhân và cài đặt tài khoản của bạn</p>
                </div>

                <!-- Custom Tabs -->
                <div class="custom-tabs">
                    <button class="tab-btn active" onclick="showTab('info')">
                        <i class="fa-solid fa-user"></i>
                        Thông tin cá nhân
                    </button>
                    <button class="tab-btn" onclick="showTab('address')">
                        <i class="fa-solid fa-map-marker-alt"></i>
                        Địa chỉ
                    </button>
                    <button class="tab-btn" onclick="showTab('password')">
                        <i class="fa-solid fa-lock"></i>
                        Đổi mật khẩu
                    </button>
                    <button class="tab-btn" onclick="showTab('avatar')">
                        <i class="fa-solid fa-image"></i>
                        Ảnh đại diện
                    </button>
                </div>

                <!-- Tab Contents -->
                <div id="info" class="tab-content active">
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fa-solid fa-user"></i>
                            Cập nhật thông tin cá nhân
                        </h3>
                        
                        <form method="post" id="updateInfoForm">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fa-solid fa-signature me-1"></i>Họ và tên *
                                    </label>
                                    <input type="text" name="full_name" class="form-input" 
                                           value="<?= htmlspecialchars($user['full_name']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fa-solid fa-envelope me-1"></i>Email *
                                    </label>
                                    <input type="email" name="email" class="form-input" 
                                           value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fa-solid fa-phone me-1"></i>Số điện thoại
                                </label>
                                <input type="tel" name="phone_number" class="form-input" 
                                       placeholder="Số điện thoại của bạn"
                                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fa-solid fa-venus-mars me-1"></i>Giới tính
                                    </label>
                                    <select name="gender" class="form-input">
                                        <option value="">Chọn giới tính</option>
                                        <option value="Nam" <?= ($user['gender'] == 'Nam') ? 'selected' : '' ?>>Nam</option>
                                        <option value="Nữ" <?= ($user['gender'] == 'Nữ') ? 'selected' : '' ?>>Nữ</option>
                                        <option value="Khác" <?= ($user['gender'] == 'Khác') ? 'selected' : '' ?>>Khác</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fa-solid fa-birthday-cake me-1"></i>Ngày sinh
                                    </label>
                                    <input type="date" name="date_of_birth" class="form-input" 
                                           value="<?= htmlspecialchars($user['date_of_birth']) ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fa-solid fa-user-circle me-1"></i>Tên đăng nhập
                                </label>
                                <input type="text" class="form-input" 
                                       value="<?= htmlspecialchars($user['username']) ?>" disabled>
                                <small style="color: #a0aec0; font-size: 0.85rem;">Tên đăng nhập không thể thay đổi</small>
                            </div>
                            
                            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                                <button type="submit" name="update_info" class="btn btn-primary">
                                    <i class="fa-solid fa-save"></i>Lưu thay đổi
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fa-solid fa-undo"></i>Hủy bỏ
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="address" class="tab-content">
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fa-solid fa-map-marker-alt"></i>
                            Cập nhật địa chỉ
                        </h3>
                        
                        <form method="post" id="updateAddressForm">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fa-solid fa-home me-1"></i>Địa chỉ cụ thể *
                                </label>
                                <input type="text" name="address_line" class="form-input" 
                                       placeholder="Số nhà, tên đường..."
                                       value="<?= htmlspecialchars($address['address_line'] ?? '') ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fa-solid fa-globe me-1"></i>Quốc gia
                                </label>
                                <select name="country" id="countrySelect" class="form-input">
                                    <option value="Vietnam" <?= ($address['country'] ?? 'Vietnam') == 'Vietnam' ? 'selected' : '' ?>>Việt Nam</option>
                                    <option value="USA" <?= ($address['country'] ?? '') == 'USA' ? 'selected' : '' ?>>Hoa Kỳ</option>
                                    <option value="China" <?= ($address['country'] ?? '') == 'China' ? 'selected' : '' ?>>Trung Quốc</option>
                                    <option value="Japan" <?= ($address['country'] ?? '') == 'Japan' ? 'selected' : '' ?>>Nhật Bản</option>
                                    <option value="Korea" <?= ($address['country'] ?? '') == 'Korea' ? 'selected' : '' ?>>Hàn Quốc</option>
                                    <option value="Thailand" <?= ($address['country'] ?? '') == 'Thailand' ? 'selected' : '' ?>>Thái Lan</option>
                                    <option value="Singapore" <?= ($address['country'] ?? '') == 'Singapore' ? 'selected' : '' ?>>Singapore</option>
                                </select>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fa-solid fa-map me-1"></i>Tỉnh/Thành phố *
                                    </label>
                                    <select name="city" id="citySelect" class="form-input select2" required>
                                        <option value="">-- Chọn Tỉnh/Thành phố --</option>
                                    </select>
                                    <input type="hidden" name="city_text" id="cityText" value="<?= htmlspecialchars($address['city'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fa-solid fa-city me-1"></i>Quận/Huyện *
                                    </label>
                                    <select name="district" id="districtSelect" class="form-input select2" required disabled>
                                        <option value="">-- Chọn Quận/Huyện --</option>
                                    </select>
                                    <input type="hidden" name="district_text" id="districtText" value="<?= htmlspecialchars($address['district'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fa-solid fa-map-pin me-1"></i>Phường/Xã *
                                    </label>
                                    <select name="ward" id="wardSelect" class="form-input select2" required disabled>
                                        <option value="">-- Chọn Phường/Xã --</option>
                                    </select>
                                    <input type="hidden" name="ward_text" id="wardText" value="<?= htmlspecialchars($address['ward'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fa-solid fa-mail-bulk me-1"></i>Mã bưu điện
                                    </label>
                                    <input type="text" name="postal_code" class="form-input" 
                                           placeholder="Mã bưu điện"
                                           value="<?= htmlspecialchars($address['postal_code'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                                <button type="submit" name="update_address" class="btn btn-primary">
                                    <i class="fa-solid fa-save"></i>Lưu địa chỉ
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fa-solid fa-undo"></i>Hủy bỏ
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="password" class="tab-content">
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fa-solid fa-lock"></i>
                            Đổi mật khẩu
                        </h3>
                        
                        <form method="post" id="changePasswordForm">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fa-solid fa-key me-1"></i>Mật khẩu hiện tại
                                </label>
                                <input type="password" name="old_pass" class="form-input" required>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fa-solid fa-lock me-1"></i>Mật khẩu mới
                                    </label>
                                    <input type="password" name="new_pass" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fa-solid fa-check-double me-1"></i>Xác nhận mật khẩu
                                    </label>
                                    <input type="password" name="confirm_pass" class="form-input" required>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                                <button type="submit" name="change_pass" class="btn btn-primary">
                                    <i class="fa-solid fa-key"></i>Đổi mật khẩu
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fa-solid fa-undo"></i>Hủy bỏ
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="avatar" class="tab-content">
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fa-solid fa-image"></i>
                            Cập nhật ảnh đại diện
                        </h3>
                        
                        <form method="post" enctype="multipart/form-data" id="avatarForm">
                            <div class="upload-area" id="uploadArea">
                                <div class="upload-icon">
                                    <i class="fa-solid fa-cloud-upload-alt"></i>
                                </div>
                                <div class="upload-text">
                                    <strong>Kéo thả ảnh vào đây</strong> hoặc <strong>click để chọn file</strong>
                                </div>
                                <div class="upload-hint">
                                    Hỗ trợ: JPG, PNG, GIF (tối đa 5MB)
                                </div>
                                <input type="file" name="avatar" id="avatarInput" accept="image/*" class="hidden-input">
                            </div>
                            
                            <div class="image-preview" id="imagePreview" style="display: none;">
                                <img id="previewImage" class="preview-img">
                                <div style="margin-top: 1.5rem; display: flex; gap: 1rem; justify-content: center;">
                                    <button type="button" class="btn btn-secondary" onclick="clearPreview()">
                                        <i class="fa-solid fa-times"></i>Hủy
                                    </button>
                                    <button type="submit" name="upload_avatar" class="btn btn-primary">
                                        <i class="fa-solid fa-upload"></i>Tải lên
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- AOS Animation -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <!-- Global Enhancements -->
    
    <script>
        // Tab functionality with performance optimization
        function showTab(tabName) {
            // Use more efficient selectors
            const allContents = document.querySelectorAll('.tab-content');
            const allButtons = document.querySelectorAll('.tab-btn');
            const targetContent = document.getElementById(tabName);
            const clickedButton = event.target;
            
            // Remove active classes efficiently
            allContents.forEach(content => content.classList.remove('active'));
            allButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active classes
            targetContent.classList.add('active');
            clickedButton.classList.add('active');
            
            // Load address data only when needed
            if (tabName === 'address' && window.provincesData && window.provincesData.length === 0) {
                if (typeof loadProvinces === 'function') {
                    loadProvinces();
                }
            }
        }

        // File Upload Functionality
        const uploadArea = document.getElementById('uploadArea');
        const avatarInput = document.getElementById('avatarInput');
        const imagePreview = document.getElementById('imagePreview');
        const previewImage = document.getElementById('previewImage');
        const currentAvatar = document.getElementById('currentAvatar');

        uploadArea.addEventListener('click', () => avatarInput.click());

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                avatarInput.files = files;
                handleFileSelect();
            }
        });

        avatarInput.addEventListener('change', handleFileSelect);

        function handleFileSelect() {
            const file = avatarInput.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewImage.src = e.target.result;
                    uploadArea.style.display = 'none';
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        }

        function clearPreview() {
            avatarInput.value = '';
            uploadArea.style.display = 'block';
            imagePreview.style.display = 'none';
        }

        // Form Validation
        document.getElementById('updateInfoForm').addEventListener('submit', function(e) {
            const fullName = this.full_name.value.trim();
            const email = this.email.value.trim();
            const dateOfBirth = this.date_of_birth.value;

            if (fullName.length < 2) {
                e.preventDefault();
                alert('Họ tên phải có ít nhất 2 ký tự!');
                return;
            }

            if (!email.includes('@')) {
                e.preventDefault();
                alert('Email không hợp lệ!');
                return;
            }

            // Validate date of birth
            if (dateOfBirth) {
                const birthDate = new Date(dateOfBirth);
                const today = new Date();
                const age = today.getFullYear() - birthDate.getFullYear();
                
                if (age > 120 || age < 0) {
                    e.preventDefault();
                    alert('Ngày sinh không hợp lệ!');
                    return;
                }
            }
        });

        // Address form validation
        document.getElementById('updateAddressForm').addEventListener('submit', function(e) {
            const addressLine = this.address_line.value.trim();
            const ward = this.ward.value.trim();
            const district = this.district.value.trim();
            const city = this.city.value.trim();

            if (addressLine.length < 5) {
                e.preventDefault();
                alert('Địa chỉ cụ thể phải có ít nhất 5 ký tự!');
                return;
            }

            if (ward.length < 2) {
                e.preventDefault();
                alert('Phường/Xã không hợp lệ!');
                return;
            }

            if (district.length < 2) {
                e.preventDefault();
                alert('Quận/Huyện không hợp lệ!');
                return;
            }

            if (city.length < 2) {
                e.preventDefault();
                alert('Tỉnh/Thành phố không hợp lệ!');
                return;
            }
        });

        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            const oldPass = this.old_pass.value;
            const newPass = this.new_pass.value;
            const confirmPass = this.confirm_pass.value;

            if (newPass.length < 6) {
                e.preventDefault();
                alert('Mật khẩu mới phải có ít nhất 6 ký tự!');
                return;
            }

            if (newPass !== confirmPass) {
                e.preventDefault();
                alert('Mật khẩu xác nhận không khớp!');
                return;
            }
        });

        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => alert.remove(), 300);
                });
            }, 5000);
        });

        // Address API and Cascade Selection
        $(document).ready(function() {
            // Initialize Select2 with minimal config for better performance
            $('.select2').select2({
                placeholder: 'Chọn...',
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: 10
            });

            // Make data globally accessible
            window.provincesData = [];
            let isLoading = false;

            // Load provinces when country is Vietnam
            function loadProvinces() {
                if (isLoading) return;
                
                if ($('#countrySelect').val() === 'Vietnam') {
                    isLoading = true;
                    
                    // Use cached data if available
                    if (window.provincesData.length > 0) {
                        populateProvinces(window.provincesData);
                        restoreSelectedValues();
                        isLoading = false;
                        return;
                    }
                    
                    $.ajax({
                        url: 'https://provinces.open-api.vn/api/?depth=3',
                        method: 'GET',
                        dataType: 'json',
                        timeout: 10000,
                        beforeSend: function() {
                            $('#citySelect').html('<option value="">Đang tải...</option>').prop('disabled', true);
                        },
                        success: function(data) {
                            window.provincesData = data;
                            populateProvinces(data);
                            restoreSelectedValues();
                        },
                        error: function() {
                            $('#citySelect').html('<option value="">Lỗi tải dữ liệu</option>').prop('disabled', true);
                            console.error('Failed to load provinces data');
                        },
                        complete: function() {
                            isLoading = false;
                        }
                    });
                } else {
                    // If not Vietnam, disable all address selects
                    $('#citySelect').html('<option value="">Chỉ hỗ trợ địa chỉ Việt Nam</option>').prop('disabled', true);
                    $('#districtSelect').html('<option value="">-- Chọn Quận/Huyện --</option>').prop('disabled', true);
                    $('#wardSelect').html('<option value="">-- Chọn Phường/Xã --</option>').prop('disabled', true);
                }
            }
            
            function restoreSelectedValues() {
                const currentCity = $('#cityText').val();
                const currentDistrict = $('#districtText').val();
                const currentWard = $('#wardText').val();
                
                if (currentCity && window.provincesData.length > 0) {
                    const province = window.provincesData.find(p => p.name === currentCity);
                    if (province) {
                        $('#citySelect').val(province.code);
                        populateDistricts(province.districts);
                        
                        if (currentDistrict) {
                            const district = province.districts.find(d => d.name === currentDistrict);
                            if (district) {
                                $('#districtSelect').val(district.code);
                                populateWards(district.wards);
                                
                                if (currentWard) {
                                    const ward = district.wards.find(w => w.name === currentWard);
                                    if (ward) {
                                        $('#wardSelect').val(ward.code);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            function populateProvinces(provinces) {
                const $citySelect = $('#citySelect');
                $citySelect.empty().append('<option value="">-- Chọn Tỉnh/Thành phố --</option>');
                
                // Use DocumentFragment for better performance
                const fragment = document.createDocumentFragment();
                provinces.forEach(province => {
                    const option = document.createElement('option');
                    option.value = province.code;
                    option.textContent = province.name;
                    fragment.appendChild(option);
                });
                $citySelect[0].appendChild(fragment);
                $citySelect.prop('disabled', false);
            }

            function populateDistricts(districts) {
                const $districtSelect = $('#districtSelect');
                $districtSelect.empty().append('<option value="">-- Chọn Quận/Huyện --</option>');
                
                const fragment = document.createDocumentFragment();
                districts.forEach(district => {
                    const option = document.createElement('option');
                    option.value = district.code;
                    option.textContent = district.name;
                    fragment.appendChild(option);
                });
                $districtSelect[0].appendChild(fragment);
                $districtSelect.prop('disabled', false);
                
                // Reset ward select
                $('#wardSelect').html('<option value="">-- Chọn Phường/Xã --</option>').prop('disabled', true);
            }

            function populateWards(wards) {
                const $wardSelect = $('#wardSelect');
                $wardSelect.empty().append('<option value="">-- Chọn Phường/Xã --</option>');
                
                const fragment = document.createDocumentFragment();
                wards.forEach(ward => {
                    const option = document.createElement('option');
                    option.value = ward.code;
                    option.textContent = ward.name;
                    fragment.appendChild(option);
                });
                $wardSelect[0].appendChild(fragment);
                $wardSelect.prop('disabled', false);
            }

            // Event handlers with debouncing for better performance
            let changeTimeout;
            
            $('#countrySelect').change(function() {
                clearTimeout(changeTimeout);
                changeTimeout = setTimeout(() => {
                    loadProvinces();
                    $('#districtSelect').html('<option value="">-- Chọn Quận/Huyện --</option>').prop('disabled', true);
                    $('#wardSelect').html('<option value="">-- Chọn Phường/Xã --</option>').prop('disabled', true);
                }, 100);
            });

            $('#citySelect').change(function() {
                const provinceCode = $(this).val();
                clearTimeout(changeTimeout);
                changeTimeout = setTimeout(() => {
                    if (provinceCode && window.provincesData.length > 0) {
                        const province = window.provincesData.find(p => p.code == provinceCode);
                        if (province && province.districts) {
                            populateDistricts(province.districts);
                        }
                    } else {
                        $('#districtSelect').html('<option value="">-- Chọn Quận/Huyện --</option>').prop('disabled', true);
                        $('#wardSelect').html('<option value="">-- Chọn Phường/Xã --</option>').prop('disabled', true);
                    }
                }, 100);
            });

            $('#districtSelect').change(function() {
                const districtCode = $(this).val();
                const provinceCode = $('#citySelect').val();
                
                clearTimeout(changeTimeout);
                changeTimeout = setTimeout(() => {
                    if (districtCode && provinceCode && window.provincesData.length > 0) {
                        const province = window.provincesData.find(p => p.code == provinceCode);
                        if (province && province.districts) {
                            const district = province.districts.find(d => d.code == districtCode);
                            if (district && district.wards) {
                                populateWards(district.wards);
                            }
                        }
                    } else {
                        $('#wardSelect').html('<option value="">-- Chọn Phường/Xã --</option>').prop('disabled', true);
                    }
                }, 100);
            });

            // Update hidden fields with text values before form submission
            $('#updateAddressForm').submit(function() {
                const cityText = $('#citySelect option:selected').text();
                const districtText = $('#districtSelect option:selected').text();
                const wardText = $('#wardSelect option:selected').text();
                
                if (cityText && cityText !== '-- Chọn Tỉnh/Thành phố --') {
                    $('input[name="city"]').remove();
                    $(this).append(`<input type="hidden" name="city" value="${cityText}">`);
                }
                if (districtText && districtText !== '-- Chọn Quận/Huyện --') {
                    $('input[name="district"]').remove();
                    $(this).append(`<input type="hidden" name="district" value="${districtText}">`);
                }
                if (wardText && wardText !== '-- Chọn Phường/Xã --') {
                    $('input[name="ward"]').remove();
                    $(this).append(`<input type="hidden" name="ward" value="${wardText}">`);
                }
            });

            // Initialize on page load only when address tab is active
            if ($('#address').hasClass('active')) {
                loadProvinces();
            }

            // Load provinces when address tab is clicked
            $('button[onclick="showTab(\'address\')"]').click(function() {
                if (window.provincesData.length === 0) {
                    loadProvinces();
                }
            });

            // Simplified entrance animations with CSS transitions
            $('.profile-sidebar, .profile-content').css('opacity', '1');
        });
    </script>

    <!-- Appointment Modal -->
    <?php include 'includes/appointment-modal.php'; ?>
    
    <?php include 'includes/footer.php'; ?>

</body>
</html> 