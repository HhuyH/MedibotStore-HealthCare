<?php
session_start();
include '../includes/db.php';
require_once '../includes/email_system_simple.php';

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
    
    if ($action === 'update_smtp') {
        $smtp_host = trim($_POST['smtp_host']);
        $smtp_port = (int)$_POST['smtp_port'];
        $smtp_username = trim($_POST['smtp_username']);
        $smtp_password = trim($_POST['smtp_password']);
        $smtp_secure = $_POST['smtp_secure'];
        $email_from_name = trim($_POST['email_from_name']);
        $email_from_address = trim($_POST['email_from_address']);
        
        // Validate
        if (!$smtp_host || !$smtp_port || !$smtp_username || !$smtp_password) {
            $error = 'Vui lòng điền đầy đủ thông tin SMTP!';
        } elseif (!filter_var($email_from_address, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email gửi không hợp lệ!';
        } else {
            $email_settings = [
                'smtp_host' => $smtp_host,
                'smtp_port' => $smtp_port,
                'smtp_username' => $smtp_username,
                'smtp_password' => $smtp_password,
                'smtp_secure' => $smtp_secure,
                'email_from_name' => $email_from_name,
                'email_from_address' => $email_from_address
            ];
            
            $conn->begin_transaction();
            try {
                foreach ($email_settings as $key => $value) {
                    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                    $stmt->bind_param("sss", $key, $value, $value);
                    $stmt->execute();
                }
                $conn->commit();
                $message = 'Cập nhật cài đặt email thành công!';
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Lỗi khi cập nhật cài đặt: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'test_email') {
        $test_email = trim($_POST['test_email']);
        
        if (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email test không hợp lệ!';
        } else {
            $test_subject = 'Test Email từ MediSync Hospital';
            $test_body = 'Đây là email test để kiểm tra cài đặt SMTP của hệ thống.';
            
            $result = sendEmailWithFallback($test_email, $test_subject, $test_body);
            
            if ($result) {
                $message = 'Email test đã được gửi thành công!';
            } else {
                $error = 'Gửi email test thất bại! Vui lòng kiểm tra lại cài đặt SMTP.';
            }
        }
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
$result = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'smtp_%' OR setting_key LIKE 'email_%'");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Giá trị mặc định
$defaults = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'medisyncnoreplybot@gmail.com',
    'smtp_password' => 'zvgk wleu zgyd ljyr',
    'smtp_secure' => 'tls',
    'email_from_name' => 'MediSync Hospital',
    'email_from_address' => 'medisyncnoreplybot@gmail.com'
];

$settings = array_merge($defaults, $settings);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt Email - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
    <link href="assets/css/sidebar.css" rel="stylesheet">
    <link href="assets/css/header.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/headeradmin.php'; ?>
<?php include 'includes/sidebaradmin.php'; ?>
    <div class="admin-wrapper">
        <?php include 'includes/headeradmin.php'; ?>
        
        <div class="admin-content">
            <?php include 'includes/sidebaradmin.php'; ?>
            
            <main class="main-content">
                <div class="container-fluid">
                    <!-- Page Header -->
                    <div class="page-header">
                        <div class="d-flex align-items-center">
                            <h1 class="page-title">
                                <i class="fas fa-cog me-2"></i>
                                Cài đặt Email
                            </h1>
                        </div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Cài đặt Email</li>
                            </ol>
                        </nav>
                    </div>

                    <!-- Messages -->
                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- SMTP Settings -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-server me-2"></i>
                                        Cài đặt SMTP
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_smtp">
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-8">
                                                <label class="form-label">SMTP Host:</label>
                                                <input type="text" name="smtp_host" class="form-control" 
                                                       value="<?= htmlspecialchars($settings['smtp_host']) ?>" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Port:</label>
                                                <input type="number" name="smtp_port" class="form-control" 
                                                       value="<?= htmlspecialchars($settings['smtp_port']) ?>" required>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Username:</label>
                                                <input type="text" name="smtp_username" class="form-control" 
                                                       value="<?= htmlspecialchars($settings['smtp_username']) ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Password:</label>
                                                <input type="password" name="smtp_password" class="form-control" 
                                                       value="<?= htmlspecialchars($settings['smtp_password']) ?>" required>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Bảo mật:</label>
                                                <select name="smtp_secure" class="form-select">
                                                    <option value="tls" <?= $settings['smtp_secure'] === 'tls' ? 'selected' : '' ?>>TLS</option>
                                                    <option value="ssl" <?= $settings['smtp_secure'] === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Tên người gửi:</label>
                                                <input type="text" name="email_from_name" class="form-control" 
                                                       value="<?= htmlspecialchars($settings['email_from_name']) ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Email người gửi:</label>
                                                <input type="email" name="email_from_address" class="form-control" 
                                                       value="<?= htmlspecialchars($settings['email_from_address']) ?>" required>
                                            </div>
                                        </div>

                                        <div class="text-end">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-1"></i>Lưu cài đặt
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Test Email -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-paper-plane me-2"></i>
                                        Test Email
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="test_email">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Email nhận:</label>
                                            <input type="email" name="test_email" class="form-control" 
                                                   placeholder="test@example.com" required>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-info">
                                                <i class="fas fa-paper-plane me-1"></i>Gửi test
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Email Stats -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-bar me-2"></i>
                                        Thống kê Email
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Lấy thống kê email
                                    $total_emails = 0;
                                    $sent_emails = 0;
                                    $failed_emails = 0;
                                    
                                    $total_result = $conn->query("SELECT COUNT(*) as total FROM email_logs");
                                    if ($total_result) {
                                        $total_emails = $total_result->fetch_assoc()['total'];
                                    }
                                    
                                    $sent_result = $conn->query("SELECT COUNT(*) as sent FROM email_logs WHERE status = 'success'");
                                    if ($sent_result) {
                                        $sent_emails = $sent_result->fetch_assoc()['sent'];
                                    }
                                    
                                    $failed_emails = $total_emails - $sent_emails;
                                    $success_rate = $total_emails > 0 ? round(($sent_emails / $total_emails) * 100, 1) : 0;
                                    ?>
                                    
                                    <div class="mb-3">
                                        <small class="text-muted">Tổng email gửi:</small>
                                        <div class="h5 mb-0"><?= $total_emails ?></div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <small class="text-muted">Thành công:</small>
                                        <div class="h5 mb-0 text-success"><?= $sent_emails ?></div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <small class="text-muted">Thất bại:</small>
                                        <div class="h5 mb-0 text-danger"><?= $failed_emails ?></div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <small class="text-muted">Tỷ lệ thành công:</small>
                                        <div class="h5 mb-0 text-info"><?= $success_rate ?>%</div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <a href="email-logs.php" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i>Xem chi tiết
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Email Templates Preview -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-file-alt me-2"></i>
                                Template Email
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <h6>Email Đăng ký</h6>
                                    <div class="border p-3 bg-light">
                                        <strong>Chủ đề:</strong> Chào mừng bạn đến với MediSync Hospital!<br>
                                        <strong>Nội dung:</strong> Email chào mừng với thông tin tài khoản...
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <h6>Email Lịch hẹn</h6>
                                    <div class="border p-3 bg-light">
                                        <strong>Chủ đề:</strong> Xác nhận lịch hẹn - MediSync Hospital<br>
                                        <strong>Nội dung:</strong> Thông tin lịch hẹn với bác sĩ...
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <h6>Email Đơn hàng</h6>
                                    <div class="border p-3 bg-light">
                                        <strong>Chủ đề:</strong> Xác nhận đơn hàng - MediSync Hospital<br>
                                        <strong>Nội dung:</strong> Chi tiết đơn hàng và thông tin giao hàng...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
</body>
</html> 