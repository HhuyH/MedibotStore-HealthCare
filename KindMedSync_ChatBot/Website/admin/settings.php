<?php
session_start();
include '../includes/db.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../login.php');
    exit;
}

$message = '';
$error = '';

// Xử lý cập nhật cài đặt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_general') {
        $site_name = trim($_POST['site_name']);
        $site_description = trim($_POST['site_description']);
        $site_keywords = trim($_POST['site_keywords']);
        $contact_email = trim($_POST['contact_email']);
        $contact_phone = trim($_POST['contact_phone']);
        $address = trim($_POST['address']);
        
        // Cập nhật cài đặt chung
        $settings = [
            'site_name' => $site_name,
            'site_description' => $site_description,
            'site_keywords' => $site_keywords,
            'contact_email' => $contact_email,
            'contact_phone' => $contact_phone,
            'address' => $address
        ];
        
        foreach ($settings as $key => $value) {
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("sss", $key, $value, $value);
            $stmt->execute();
        }
        
        $message = 'Cập nhật cài đặt chung thành công!';
    } 
    elseif ($action === 'update_email') {
        $smtp_host = trim($_POST['smtp_host']);
        $smtp_port = (int)$_POST['smtp_port'];
        $smtp_username = trim($_POST['smtp_username']);
        $smtp_password = trim($_POST['smtp_password']);
        $smtp_secure = $_POST['smtp_secure'];
        
        $email_settings = [
            'smtp_host' => $smtp_host,
            'smtp_port' => $smtp_port,
            'smtp_username' => $smtp_username,
            'smtp_password' => $smtp_password,
            'smtp_secure' => $smtp_secure
        ];
        
        foreach ($email_settings as $key => $value) {
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("sss", $key, $value, $value);
            $stmt->execute();
        }
        
        $message = 'Cập nhật cài đặt email thành công!';
    }
    elseif ($action === 'update_social') {
        $facebook_url = trim($_POST['facebook_url']);
        $twitter_url = trim($_POST['twitter_url']);
        $instagram_url = trim($_POST['instagram_url']);
        $youtube_url = trim($_POST['youtube_url']);
        
        $social_settings = [
            'facebook_url' => $facebook_url,
            'twitter_url' => $twitter_url,
            'instagram_url' => $instagram_url,
            'youtube_url' => $youtube_url
        ];
        
        foreach ($social_settings as $key => $value) {
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("sss", $key, $value, $value);
            $stmt->execute();
        }
        
        $message = 'Cập nhật cài đặt mạng xã hội thành công!';
    }
}

// Tạo bảng settings nếu chưa có
$conn->query("CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Lấy cài đặt hiện tại
$settings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM settings");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Thiết lập giá trị mặc định
$defaults = [
    'site_name' => 'QuickMed Hospital',
    'site_description' => 'Hệ thống quản lý bệnh viện và tư vấn sức khỏe',
    'site_keywords' => 'bệnh viện, tư vấn sức khỏe, khám bệnh',
    'contact_email' => 'admin@quickmed.com',
    'contact_phone' => '0123456789',
    'address' => '123 Đường ABC, Quận 1, TP.HCM',
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_secure' => 'tls',
    'facebook_url' => '',
    'twitter_url' => '',
    'instagram_url' => '',
    'youtube_url' => ''
];

foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt hệ thống - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link href="assets/css/sidebar.css" rel="stylesheet">
    <link href="assets/css/header.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/headeradmin.php'; ?>
    <?php include 'includes/sidebaradmin.php'; ?>

    <main class="main-content">
        <div class="container-fluid p-4">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">
                                <i class="fas fa-cog"></i> Cài đặt hệ thống
                            </h4>
                        </div>
                        
                        <div class="card-body">
                            <?php if ($message): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($message); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($error); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Tabs -->
                            <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                                        <i class="fas fa-globe"></i> Cài đặt chung
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab">
                                        <i class="fas fa-envelope"></i> Cài đặt Email
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="social-tab" data-bs-toggle="tab" data-bs-target="#social" type="button" role="tab">
                                        <i class="fab fa-facebook"></i> Mạng xã hội
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                                        <i class="fas fa-shield-alt"></i> Bảo mật
                                    </button>
                                </li>
                            </ul>
                            
                            <div class="tab-content" id="settingsTabsContent">
                                <!-- Cài đặt chung -->
                                <div class="tab-pane fade show active" id="general" role="tabpanel">
                                    <form method="POST" class="mt-3">
                                        <input type="hidden" name="action" value="update_general">
                                        
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="site_name" class="form-label">Tên website</label>
                                                <input type="text" class="form-control" id="site_name" name="site_name" 
                                                       value="<?php echo htmlspecialchars($settings['site_name']); ?>">
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label for="contact_email" class="form-label">Email liên hệ</label>
                                                <input type="email" class="form-control" id="contact_email" name="contact_email" 
                                                       value="<?php echo htmlspecialchars($settings['contact_email']); ?>">
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label for="contact_phone" class="form-label">Số điện thoại</label>
                                                <input type="text" class="form-control" id="contact_phone" name="contact_phone" 
                                                       value="<?php echo htmlspecialchars($settings['contact_phone']); ?>">
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label for="address" class="form-label">Địa chỉ</label>
                                                <input type="text" class="form-control" id="address" name="address" 
                                                       value="<?php echo htmlspecialchars($settings['address']); ?>">
                                            </div>
                                            
                                            <div class="col-12">
                                                <label for="site_description" class="form-label">Mô tả website</label>
                                                <textarea class="form-control" id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($settings['site_description']); ?></textarea>
                                            </div>
                                            
                                            <div class="col-12">
                                                <label for="site_keywords" class="form-label">Từ khóa SEO</label>
                                                <input type="text" class="form-control" id="site_keywords" name="site_keywords" 
                                                       value="<?php echo htmlspecialchars($settings['site_keywords']); ?>">
                                                <small class="text-muted">Các từ khóa cách nhau bởi dấu phẩy</small>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Lưu cài đặt chung
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Cài đặt Email -->
                                <div class="tab-pane fade" id="email" role="tabpanel">
                                    <form method="POST" class="mt-3">
                                        <input type="hidden" name="action" value="update_email">
                                        
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="smtp_host" class="form-label">SMTP Host</label>
                                                <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                                       value="<?php echo htmlspecialchars($settings['smtp_host']); ?>">
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label for="smtp_port" class="form-label">SMTP Port</label>
                                                <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                                       value="<?php echo htmlspecialchars($settings['smtp_port']); ?>">
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label for="smtp_username" class="form-label">SMTP Username</label>
                                                <input type="text" class="form-control" id="smtp_username" name="smtp_username" 
                                                       value="<?php echo htmlspecialchars($settings['smtp_username']); ?>">
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label for="smtp_password" class="form-label">SMTP Password</label>
                                                <input type="password" class="form-control" id="smtp_password" name="smtp_password" 
                                                       value="<?php echo htmlspecialchars($settings['smtp_password']); ?>">
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label for="smtp_secure" class="form-label">SMTP Secure</label>
                                                <select class="form-select" id="smtp_secure" name="smtp_secure">
                                                    <option value="tls" <?php echo ($settings['smtp_secure'] === 'tls') ? 'selected' : ''; ?>>TLS</option>
                                                    <option value="ssl" <?php echo ($settings['smtp_secure'] === 'ssl') ? 'selected' : ''; ?>>SSL</option>
                                                    <option value="none" <?php echo ($settings['smtp_secure'] === 'none') ? 'selected' : ''; ?>>None</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Lưu cài đặt Email
                                            </button>
                                            <button type="button" class="btn btn-secondary" onclick="testEmail()">
                                                <i class="fas fa-paper-plane"></i> Test Email
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Mạng xã hội -->
                                <div class="tab-pane fade" id="social" role="tabpanel">
                                    <form method="POST" class="mt-3">
                                        <input type="hidden" name="action" value="update_social">
                                        
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="facebook_url" class="form-label">
                                                    <i class="fab fa-facebook text-primary"></i> Facebook URL
                                                </label>
                                                <input type="url" class="form-control" id="facebook_url" name="facebook_url" 
                                                       value="<?php echo htmlspecialchars($settings['facebook_url']); ?>">
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label for="twitter_url" class="form-label">
                                                    <i class="fab fa-twitter text-info"></i> Twitter URL
                                                </label>
                                                <input type="url" class="form-control" id="twitter_url" name="twitter_url" 
                                                       value="<?php echo htmlspecialchars($settings['twitter_url']); ?>">
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label for="instagram_url" class="form-label">
                                                    <i class="fab fa-instagram text-danger"></i> Instagram URL
                                                </label>
                                                <input type="url" class="form-control" id="instagram_url" name="instagram_url" 
                                                       value="<?php echo htmlspecialchars($settings['instagram_url']); ?>">
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label for="youtube_url" class="form-label">
                                                    <i class="fab fa-youtube text-danger"></i> YouTube URL
                                                </label>
                                                <input type="url" class="form-control" id="youtube_url" name="youtube_url" 
                                                       value="<?php echo htmlspecialchars($settings['youtube_url']); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Lưu cài đặt mạng xã hội
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Bảo mật -->
                                <div class="tab-pane fade" id="security" role="tabpanel">
                                    <div class="mt-3">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="mb-0">Đổi mật khẩu Admin</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <form id="changePasswordForm">
                                                            <div class="mb-3">
                                                                <label for="current_password" class="form-label">Mật khẩu hiện tại</label>
                                                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="new_password" class="form-label">Mật khẩu mới</label>
                                                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="confirm_password" class="form-label">Xác nhận mật khẩu</label>
                                                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                                            </div>
                                                            <button type="submit" class="btn btn-warning">
                                                                <i class="fas fa-key"></i> Đổi mật khẩu
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="mb-0">Thông tin phiên đăng nhập</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <p><strong>User ID:</strong> <?php echo $_SESSION['user_id']; ?></p>
                                                        <p><strong>Username:</strong> <?php echo $_SESSION['username']; ?></p>
                                                        <p><strong>Role:</strong> Administrator</p>
                                                        <p><strong>IP:</strong> <?php echo $_SERVER['REMOTE_ADDR']; ?></p>
                                                        <p><strong>Last Login:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
                                                        
                                                        <button type="button" class="btn btn-danger" onclick="logoutAllSessions()">
                                                            <i class="fas fa-sign-out-alt"></i> Đăng xuất tất cả phiên
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function testEmail() {
            alert('Chức năng test email đang được phát triển.');
        }
        
        function logoutAllSessions() {
            if (confirm('Bạn có chắc muốn đăng xuất tất cả phiên đăng nhập?')) {
                window.location.href = '../logout.php';
            }
        }
        
        // Xử lý đổi mật khẩu
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                alert('Mật khẩu mới và xác nhận mật khẩu không khớp!');
                return;
            }
            
            if (newPassword.length < 6) {
                alert('Mật khẩu mới phải có ít nhất 6 ký tự!');
                return;
            }
            
            // Gửi AJAX request để đổi mật khẩu
            fetch('ajax/change_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    current_password: currentPassword,
                    new_password: newPassword
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Đổi mật khẩu thành công!');
                    document.getElementById('changePasswordForm').reset();
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi đổi mật khẩu!');
            });
        });
    </script>
</body>
</html> 