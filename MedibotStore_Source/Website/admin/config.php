<?php
session_start();
include '../includes/db.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../login.php');
    exit;
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Xử lý cập nhật cài đặt
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'update_general') {
        // Cập nhật cài đặt chung
        $site_name = trim($_POST['site_name']);
        $site_description = trim($_POST['site_description']);
        $site_email = trim($_POST['site_email']);
        $site_phone = trim($_POST['site_phone']);
        $site_address = trim($_POST['site_address']);
        $timezone = $_POST['timezone'];
        
        $conn->begin_transaction();
        try {
            // Cập nhật hoặc thêm mới các setting
            $settings = [
                'site_name' => $site_name,
                'site_description' => $site_description,
                'site_email' => $site_email,
                'site_phone' => $site_phone,
                'site_address' => $site_address,
                'timezone' => $timezone
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->bind_param("sss", $key, $value, $value);
                $stmt->execute();
            }
            
            $conn->commit();
            header('Location: config.php?success=general_updated');
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            header('Location: config.php?error=general_failed');
            exit;
        }
    }
    
    if ($action == 'update_booking') {
        // Cập nhật cài đặt đặt lịch
        $booking_enabled = isset($_POST['booking_enabled']) ? 1 : 0;
        $booking_advance_days = (int)$_POST['booking_advance_days'];
        $booking_hours_start = $_POST['booking_hours_start'];
        $booking_hours_end = $_POST['booking_hours_end'];
        $booking_interval = (int)$_POST['booking_interval'];
        $auto_confirm = isset($_POST['auto_confirm']) ? 1 : 0;
        
        $conn->begin_transaction();
        try {
            $settings = [
                'booking_enabled' => $booking_enabled,
                'booking_advance_days' => $booking_advance_days,
                'booking_hours_start' => $booking_hours_start,
                'booking_hours_end' => $booking_hours_end,
                'booking_interval' => $booking_interval,
                'auto_confirm' => $auto_confirm
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->bind_param("sss", $key, $value, $value);
                $stmt->execute();
            }
            
            $conn->commit();
            header('Location: config.php?success=booking_updated');
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            header('Location: config.php?error=booking_failed');
            exit;
        }
    }
    
    if ($action == 'update_email') {
        // Cập nhật cài đặt email
        $smtp_enabled = isset($_POST['smtp_enabled']) ? 1 : 0;
        $smtp_host = trim($_POST['smtp_host']);
        $smtp_port = (int)$_POST['smtp_port'];
        $smtp_username = trim($_POST['smtp_username']);
        $smtp_password = trim($_POST['smtp_password']);
        $smtp_encryption = $_POST['smtp_encryption'];
        $email_from_name = trim($_POST['email_from_name']);
        $email_from_address = trim($_POST['email_from_address']);
        
        $conn->begin_transaction();
        try {
            $settings = [
                'smtp_enabled' => $smtp_enabled,
                'smtp_host' => $smtp_host,
                'smtp_port' => $smtp_port,
                'smtp_username' => $smtp_username,
                'smtp_password' => $smtp_password,
                'smtp_encryption' => $smtp_encryption,
                'email_from_name' => $email_from_name,
                'email_from_address' => $email_from_address
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->bind_param("sss", $key, $value, $value);
                $stmt->execute();
            }
            
            $conn->commit();
            header('Location: config.php?success=email_updated');
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            header('Location: config.php?error=email_failed');
            exit;
        }
    }
}

// Lấy tất cả cài đặt hiện tại
$settings_result = $conn->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Thiết lập giá trị mặc định
$default_settings = [
    'site_name' => 'MediBot Store',
    'site_description' => 'Hệ thống quản lý phòng khám',
    'site_email' => 'info@qickmed.com',
    'site_phone' => '0123456789',
    'site_address' => 'Hà Nội, Việt Nam',
    'timezone' => 'Asia/Ho_Chi_Minh',
    'booking_enabled' => 1,
    'booking_advance_days' => 30,
    'booking_hours_start' => '08:00',
    'booking_hours_end' => '18:00',
    'booking_interval' => 30,
    'auto_confirm' => 0,
    'smtp_enabled' => 0,
    'smtp_host' => '',
    'smtp_port' => 587,
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_encryption' => 'tls',
    'email_from_name' => 'MediBot Store',
    'email_from_address' => 'noreply@qickmed.com'
];

foreach ($default_settings as $key => $value) {
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
    <title>Cài đặt hệ thống - QickMed Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Cài đặt hệ thống</h1>
                    <p class="mb-0 text-muted">Quản lý cấu hình và thiết lập hệ thống</p>
                </div>
            </div>

            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php
                    switch($success) {
                        case 'general_updated': echo 'Cập nhật cài đặt chung thành công!'; break;
                        case 'booking_updated': echo 'Cập nhật cài đặt đặt lịch thành công!'; break;
                        case 'email_updated': echo 'Cập nhật cài đặt email thành công!'; break;
                        default: echo 'Cập nhật thành công!';
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php
                    switch($error) {
                        case 'general_failed': echo 'Cập nhật cài đặt chung thất bại!'; break;
                        case 'booking_failed': echo 'Cập nhật cài đặt đặt lịch thất bại!'; break;
                        case 'email_failed': echo 'Cập nhật cài đặt email thất bại!'; break;
                        default: echo 'Có lỗi xảy ra!';
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Navigation Tabs -->
                <div class="col-lg-3 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-0">
                            <nav class="nav nav-pills flex-column">
                                <a class="nav-link active" data-bs-toggle="pill" href="#general-tab">
                                    <i class="fas fa-cog me-2"></i>Cài đặt chung
                                </a>
                                <a class="nav-link" data-bs-toggle="pill" href="#booking-tab">
                                    <i class="fas fa-calendar-alt me-2"></i>Đặt lịch hẹn
                                </a>
                                <a class="nav-link" data-bs-toggle="pill" href="#email-tab">
                                    <i class="fas fa-envelope me-2"></i>Cài đặt Email
                                </a>
                                <a class="nav-link" data-bs-toggle="pill" href="#backup-tab">
                                    <i class="fas fa-database me-2"></i>Sao lưu & Khôi phục
                                </a>
                                <a class="nav-link" data-bs-toggle="pill" href="#security-tab">
                                    <i class="fas fa-shield-alt me-2"></i>Bảo mật
                                </a>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="col-lg-9">
                    <div class="tab-content">
                        <!-- General Settings -->
                        <div class="tab-pane fade show active" id="general-tab">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Cài đặt chung</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_general">
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Tên website</label>
                                                <input type="text" class="form-control" name="site_name" 
                                                       value="<?= htmlspecialchars($settings['site_name']) ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Email liên hệ</label>
                                                <input type="email" class="form-control" name="site_email" 
                                                       value="<?= htmlspecialchars($settings['site_email']) ?>" required>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Mô tả website</label>
                                            <textarea class="form-control" name="site_description" rows="3"><?= htmlspecialchars($settings['site_description']) ?></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Số điện thoại</label>
                                                <input type="text" class="form-control" name="site_phone" 
                                                       value="<?= htmlspecialchars($settings['site_phone']) ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Múi giờ</label>
                                                <select class="form-select" name="timezone">
                                                    <option value="Asia/Ho_Chi_Minh" <?= $settings['timezone'] == 'Asia/Ho_Chi_Minh' ? 'selected' : '' ?>>Việt Nam (GMT+7)</option>
                                                    <option value="Asia/Bangkok" <?= $settings['timezone'] == 'Asia/Bangkok' ? 'selected' : '' ?>>Bangkok (GMT+7)</option>
                                                    <option value="Asia/Singapore" <?= $settings['timezone'] == 'Asia/Singapore' ? 'selected' : '' ?>>Singapore (GMT+8)</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Địa chỉ</label>
                                            <textarea class="form-control" name="site_address" rows="2"><?= htmlspecialchars($settings['site_address']) ?></textarea>
                                        </div>

                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Lưu cài đặt
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Booking Settings -->
                        <div class="tab-pane fade" id="booking-tab">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Cài đặt đặt lịch hẹn</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_booking">
                                        
                                        <div class="mb-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="booking_enabled" 
                                                       <?= $settings['booking_enabled'] ? 'checked' : '' ?>>
                                                <label class="form-check-label">
                                                    Cho phép đặt lịch hẹn online
                                                </label>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Đặt trước tối đa (ngày)</label>
                                                <input type="number" class="form-control" name="booking_advance_days" 
                                                       value="<?= $settings['booking_advance_days'] ?>" min="1" max="365">
                                                <small class="text-muted">Khách hàng có thể đặt lịch trước bao nhiêu ngày</small>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Khoảng thời gian (phút)</label>
                                                <select class="form-select" name="booking_interval">
                                                    <option value="15" <?= $settings['booking_interval'] == 15 ? 'selected' : '' ?>>15 phút</option>
                                                    <option value="30" <?= $settings['booking_interval'] == 30 ? 'selected' : '' ?>>30 phút</option>
                                                    <option value="60" <?= $settings['booking_interval'] == 60 ? 'selected' : '' ?>>60 phút</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Giờ bắt đầu</label>
                                                <input type="time" class="form-control" name="booking_hours_start" 
                                                       value="<?= $settings['booking_hours_start'] ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Giờ kết thúc</label>
                                                <input type="time" class="form-control" name="booking_hours_end" 
                                                       value="<?= $settings['booking_hours_end'] ?>">
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="auto_confirm" 
                                                       <?= $settings['auto_confirm'] ? 'checked' : '' ?>>
                                                <label class="form-check-label">
                                                    Tự động xác nhận lịch hẹn
                                                </label>
                                                <small class="d-block text-muted">Nếu tắt, admin sẽ phải xác nhận thủ công</small>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Lưu cài đặt
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Email Settings -->
                        <div class="tab-pane fade" id="email-tab">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Cài đặt Email SMTP</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_email">
                                        
                                        <div class="mb-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="smtp_enabled" 
                                                       <?= $settings['smtp_enabled'] ? 'checked' : '' ?>>
                                                <label class="form-check-label">
                                                    Kích hoạt gửi email qua SMTP
                                                </label>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">SMTP Host</label>
                                                <input type="text" class="form-control" name="smtp_host" 
                                                       value="<?= htmlspecialchars($settings['smtp_host']) ?>" 
                                                       placeholder="smtp.gmail.com">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">SMTP Port</label>
                                                <input type="number" class="form-control" name="smtp_port" 
                                                       value="<?= $settings['smtp_port'] ?>">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Username</label>
                                                <input type="text" class="form-control" name="smtp_username" 
                                                       value="<?= htmlspecialchars($settings['smtp_username']) ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Password</label>
                                                <input type="password" class="form-control" name="smtp_password" 
                                                       value="<?= htmlspecialchars($settings['smtp_password']) ?>">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Encryption</label>
                                                <select class="form-select" name="smtp_encryption">
                                                    <option value="tls" <?= $settings['smtp_encryption'] == 'tls' ? 'selected' : '' ?>>TLS</option>
                                                    <option value="ssl" <?= $settings['smtp_encryption'] == 'ssl' ? 'selected' : '' ?>>SSL</option>
                                                    <option value="" <?= empty($settings['smtp_encryption']) ? 'selected' : '' ?>>None</option>
                                                </select>
                                            </div>
                                        </div>

                                        <hr>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Tên người gửi</label>
                                                <input type="text" class="form-control" name="email_from_name" 
                                                       value="<?= htmlspecialchars($settings['email_from_name']) ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Email người gửi</label>
                                                <input type="email" class="form-control" name="email_from_address" 
                                                       value="<?= htmlspecialchars($settings['email_from_address']) ?>">
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary me-2">
                                            <i class="fas fa-save me-2"></i>Lưu cài đặt
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="testEmail()">
                                            <i class="fas fa-paper-plane me-2"></i>Gửi email thử nghiệm
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Backup Settings -->
                        <div class="tab-pane fade" id="backup-tab">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Sao lưu & Khôi phục</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Sao lưu dữ liệu</h6>
                                            <p class="text-muted">Tạo bản sao lưu toàn bộ cơ sở dữ liệu</p>
                                            <button type="button" class="btn btn-primary" onclick="createBackup()">
                                                <i class="fas fa-download me-2"></i>Tạo bản sao lưu
                                            </button>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Khôi phục dữ liệu</h6>
                                            <p class="text-muted">Khôi phục từ file sao lưu</p>
                                            <input type="file" class="form-control mb-2" accept=".sql">
                                            <button type="button" class="btn btn-warning">
                                                <i class="fas fa-upload me-2"></i>Khôi phục
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Security Settings -->
                        <div class="tab-pane fade" id="security-tab">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Cài đặt bảo mật</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Phiên đăng nhập</h6>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" checked>
                                                <label class="form-check-label">Tự động đăng xuất sau 30 phút</label>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" checked>
                                                <label class="form-check-label">Ghi log đăng nhập</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Mật khẩu</h6>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" checked>
                                                <label class="form-check-label">Yêu cầu mật khẩu mạnh</label>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox">
                                                <label class="form-check-label">Xác thực 2 yếu tố (2FA)</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button type="button" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Lưu cài đặt
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
    
    <script>
    function testEmail() {
        // Implement email test functionality
        alert('Chức năng test email sẽ được triển khai');
    }
    
    function createBackup() {
        // Implement backup functionality
        alert('Chức năng sao lưu sẽ được triển khai');
    }
    </script>
</body>
</html> 