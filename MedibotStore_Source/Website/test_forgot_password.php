<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/email_system_simple.php';

// Redirect nếu đã đăng nhập
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$results = [];

function testResult($test_name, $success, $message) {
    global $results;
    $results[] = [
        'test' => $test_name,
        'success' => $success,
        'message' => $message
    ];
}

// Test 1: Kiểm tra bảng password_reset_tokens
try {
    $sql = "SHOW TABLES LIKE 'password_reset_tokens'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        testResult('Database Table', true, 'Bảng password_reset_tokens đã tồn tại');
    } else {
        testResult('Database Table', false, 'Bảng password_reset_tokens chưa được tạo');
    }
} catch (Exception $e) {
    testResult('Database Table', false, 'Lỗi kiểm tra bảng: ' . $e->getMessage());
}

// Test 2: Kiểm tra cấu trúc bảng
try {
    $sql = "DESCRIBE password_reset_tokens";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $fields = [];
        while ($row = $result->fetch_assoc()) {
            $fields[] = $row['Field'];
        }
        $required_fields = ['id', 'user_id', 'email', 'token', 'expires_at', 'used', 'created_at'];
        $missing_fields = array_diff($required_fields, $fields);
        
        if (empty($missing_fields)) {
            testResult('Table Structure', true, 'Cấu trúc bảng đầy đủ');
        } else {
            testResult('Table Structure', false, 'Thiếu các trường: ' . implode(', ', $missing_fields));
        }
    } else {
        testResult('Table Structure', false, 'Không thể kiểm tra cấu trúc bảng');
    }
} catch (Exception $e) {
    testResult('Table Structure', false, 'Lỗi kiểm tra cấu trúc: ' . $e->getMessage());
}

// Test 3: Kiểm tra user table
try {
    $sql = "SELECT COUNT(*) as count FROM users";
    $result = $conn->query($sql);
    if ($result) {
        $row = $result->fetch_assoc();
        if ($row['count'] > 0) {
            testResult('User Data', true, 'Có ' . $row['count'] . ' user trong hệ thống');
        } else {
            testResult('User Data', false, 'Không có user nào trong hệ thống');
        }
    } else {
        testResult('User Data', false, 'Lỗi truy vấn user table');
    }
} catch (Exception $e) {
    testResult('User Data', false, 'Lỗi kiểm tra user: ' . $e->getMessage());
}

// Test 4: Kiểm tra email system
$email_test = false;
if (function_exists('sendEmail')) {
    testResult('Email Function', true, 'Hàm sendEmail đã được load');
    $email_test = true;
} else {
    testResult('Email Function', false, 'Hàm sendEmail không tồn tại');
}

// Test 5: Kiểm tra enhanced logger
if (class_exists('EnhancedLogger')) {
    testResult('Enhanced Logger', true, 'Enhanced Logger đã được load');
} else {
    testResult('Enhanced Logger', false, 'Enhanced Logger không tồn tại');
}

// Test 6: Lấy một user để test
$test_user = null;
try {
    $sql = "SELECT u.user_id, u.username, u.email, ui.full_name 
            FROM users u 
            LEFT JOIN users_info ui ON u.user_id = ui.user_id 
            WHERE u.email IS NOT NULL AND u.email != '' 
            LIMIT 1";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $test_user = $result->fetch_assoc();
        testResult('Test User', true, 'Tìm thấy user test: ' . $test_user['email']);
    } else {
        testResult('Test User', false, 'Không tìm thấy user nào có email');
    }
} catch (Exception $e) {
    testResult('Test User', false, 'Lỗi tìm user: ' . $e->getMessage());
}

// Test 7: Kiểm tra thư mục logs
if (is_dir('logs')) {
    if (is_writable('logs')) {
        testResult('Logs Directory', true, 'Thư mục logs có thể ghi');
    } else {
        testResult('Logs Directory', false, 'Thư mục logs không thể ghi');
    }
} else {
    testResult('Logs Directory', false, 'Thư mục logs không tồn tại');
}

// Test 8: Kiểm tra files tồn tại
$required_files = [
    'forgot_password.php',
    'reset_password.php',
    'includes/email_system_simple.php',
    'includes/functions/enhanced_logger.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        testResult('File: ' . $file, true, 'File tồn tại');
    } else {
        testResult('File: ' . $file, false, 'File không tồn tại');
    }
}

// Test 9: Kiểm tra token generation
try {
    $token = bin2hex(random_bytes(32));
    if (strlen($token) === 64) {
        testResult('Token Generation', true, 'Token generation hoạt động đúng');
    } else {
        testResult('Token Generation', false, 'Token length không đúng: ' . strlen($token));
    }
} catch (Exception $e) {
    testResult('Token Generation', false, 'Lỗi tạo token: ' . $e->getMessage());
}

// Test 10: Kiểm tra timezone
$timezone = date_default_timezone_get();
testResult('Timezone', true, 'Timezone: ' . $timezone);

// Test 11: Kiểm tra session
if (session_status() === PHP_SESSION_ACTIVE) {
    testResult('Session', true, 'Session đang hoạt động');
} else {
    testResult('Session', false, 'Session không hoạt động');
}

// Tính toán kết quả
$total_tests = count($results);
$passed_tests = count(array_filter($results, function($result) { return $result['success']; }));
$failed_tests = $total_tests - $passed_tests;
$success_rate = ($passed_tests / $total_tests) * 100;

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Forgot Password System - QickMed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container {
            padding: 40px 20px;
        }
        
        .test-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .test-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .test-item {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
        }
        
        .test-item:last-child {
            border-bottom: none;
        }
        
        .test-icon {
            width: 30px;
            text-align: center;
            margin-right: 15px;
        }
        
        .test-name {
            font-weight: 600;
            color: #333;
            min-width: 150px;
        }
        
        .test-message {
            color: #666;
            margin-left: auto;
        }
        
        .success {
            color: #28a745;
        }
        
        .failed {
            color: #dc3545;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .progress-custom {
            height: 10px;
            border-radius: 5px;
            background: #e9ecef;
            overflow: hidden;
        }
        
        .progress-bar-custom {
            height: 100%;
            background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
            transition: width 0.5s ease;
        }
        
        .action-buttons {
            margin-top: 30px;
        }
        
        .btn-custom {
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            margin: 5px;
            display: inline-block;
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-success-custom {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
        }
        
        .btn-success-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-warning-custom {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: white;
            border: none;
        }
        
        .btn-warning-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .recommendation {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 20px;
            margin-top: 20px;
            border-radius: 0 10px 10px 0;
        }
        
        .recommendation h5 {
            color: #007bff;
            margin-bottom: 10px;
        }
        
        .test-time {
            color: #666;
            font-size: 0.9rem;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="test-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">
                        <i class="fas fa-vial text-primary me-3"></i>
                        Test Forgot Password System
                    </h1>
                    <p class="text-muted mt-2 mb-0">Kiểm tra tính năng quên mật khẩu của hệ thống QickMed</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="stats-card">
                        <h3 class="mb-0"><?php echo number_format($success_rate, 1); ?>%</h3>
                        <small>Tỷ lệ thành công</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="test-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="mb-0">
                            <i class="fas fa-clipboard-check me-2"></i>
                            Kết quả test
                        </h4>
                        <div class="text-end">
                            <span class="badge bg-success me-2"><?php echo $passed_tests; ?> Passed</span>
                            <span class="badge bg-danger"><?php echo $failed_tests; ?> Failed</span>
                        </div>
                    </div>

                    <div class="progress-custom mb-4">
                        <div class="progress-bar-custom" style="width: <?php echo $success_rate; ?>%"></div>
                    </div>

                    <div class="test-results">
                        <?php foreach ($results as $result): ?>
                            <div class="test-item">
                                <div class="test-icon">
                                    <i class="fas fa-<?php echo $result['success'] ? 'check-circle success' : 'times-circle failed'; ?>"></i>
                                </div>
                                <div class="test-name"><?php echo htmlspecialchars($result['test']); ?></div>
                                <div class="test-message"><?php echo htmlspecialchars($result['message']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="test-time">
                        <i class="fas fa-clock me-1"></i>
                        Thời gian test: <?php echo date('d/m/Y H:i:s'); ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="test-card">
                    <h5 class="mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        Thông tin hệ thống
                    </h5>
                    
                    <div class="mb-3">
                        <strong>PHP Version:</strong><br>
                        <span class="text-muted"><?php echo phpversion(); ?></span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>MySQL Version:</strong><br>
                        <span class="text-muted"><?php echo $conn->server_info; ?></span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Server:</strong><br>
                        <span class="text-muted"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Test User:</strong><br>
                        <span class="text-muted"><?php echo $test_user ? $test_user['email'] : 'Không có'; ?></span>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="setup_password_reset.php" class="btn btn-warning-custom">
                            <i class="fas fa-tools me-2"></i>Setup Database
                        </a>
                        
                        <a href="forgot_password.php" class="btn btn-primary-custom">
                            <i class="fas fa-key me-2"></i>Test Forgot Password
                        </a>
                        
                        <a href="admin/activity-log.php" class="btn btn-success-custom">
                            <i class="fas fa-chart-line me-2"></i>View Logs
                        </a>
                        
                        <a href="index.php" class="btn btn-success-custom">
                            <i class="fas fa-home me-2"></i>Về trang chủ
                        </a>
                    </div>
                </div>

                <?php if ($failed_tests > 0): ?>
                    <div class="recommendation">
                        <h5>
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Khuyến nghị
                        </h5>
                        
                        <?php if (!file_exists('forgot_password.php')): ?>
                            <p>• Tạo file forgot_password.php</p>
                        <?php endif; ?>
                        
                        <?php if (!file_exists('reset_password.php')): ?>
                            <p>• Tạo file reset_password.php</p>
                        <?php endif; ?>
                        
                        <?php 
                        $table_exists = false;
                        foreach ($results as $result) {
                            if ($result['test'] === 'Database Table' && $result['success']) {
                                $table_exists = true;
                                break;
                            }
                        }
                        if (!$table_exists): ?>
                            <p>• Chạy setup_password_reset.php để tạo bảng database</p>
                        <?php endif; ?>
                        
                        <?php if (!function_exists('sendEmail')): ?>
                            <p>• Cấu hình email system</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 