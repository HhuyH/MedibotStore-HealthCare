<?php
include 'includes/db.php';
session_start();

// Redirect nếu đã đăng nhập
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$err = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $gender = $_POST['gender'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $terms = isset($_POST['terms']);
    
    // Validation
    if (!$full_name || !$gender || !$username || !$email || !$phone || !$password || !$confirm_password) {
        $err = 'Vui lòng điền đầy đủ thông tin!';
    } elseif ($password !== $confirm_password) {
        $err = 'Mật khẩu xác nhận không khớp!';
    } elseif (strlen($password) < 6) {
        $err = 'Mật khẩu phải có ít nhất 6 ký tự!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Email không hợp lệ!';
    } elseif (!$terms) {
        $err = 'Vui lòng đồng ý với điều khoản sử dụng!';
    } else {
        // Kiểm tra username, email và phone đã tồn tại chưa
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ? OR phone_number = ?");
        if ($stmt) {
            $stmt->bind_param('sss', $username, $email, $phone);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $err = 'Tên đăng nhập, email hoặc số điện thoại đã tồn tại!';
            } else {
                // Bắt đầu transaction
                $conn->begin_transaction();
                
                try {
                    // Thêm user mới (role_id = 2 cho patient)
                    $stmt = $conn->prepare("INSERT INTO users (username, email, phone_number, password, role_id, status, created_at) VALUES (?, ?, ?, ?, 2, 'active', NOW())");
                    if ($stmt) {
                        $stmt->bind_param('ssss', $username, $email, $phone, $password);
                        if ($stmt->execute()) {
                            $user_id = $conn->insert_id;
                            
                            // Thêm thông tin chi tiết
                            $stmt2 = $conn->prepare("INSERT INTO users_info (user_id, full_name, gender, created_at) VALUES (?, ?, ?, NOW())");
                            if ($stmt2) {
                                $stmt2->bind_param('iss', $user_id, $full_name, $gender);
                                if ($stmt2->execute()) {
                                    $conn->commit();
                                    
                                    // Set success message
                                    $success = 'Đăng ký tài khoản thành công! Bạn sẽ được chuyển đến trang đăng nhập sau 5 giây...';
                                    
                                    // Clear form data
                                    $_POST = array();
                                } else {
                                    throw new Exception('Lỗi tạo thông tin người dùng: ' . $stmt2->error);
                                }
                            } else {
                                throw new Exception('Lỗi chuẩn bị câu lệnh thông tin người dùng: ' . $conn->error);
                            }
                        } else {
                            throw new Exception('Lỗi thực thi câu lệnh tạo user: ' . $stmt->error);
                        }
                    } else {
                        throw new Exception('Lỗi chuẩn bị câu lệnh tạo user: ' . $conn->error);
                    }
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $err = 'Có lỗi xảy ra khi đăng ký. Vui lòng thử lại! Chi tiết: ' . $e->getMessage();
                }
            }
        } else {
            $err = 'Lỗi kết nối cơ sở dữ liệu!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - QickMed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: url('assets/images/bgk_login_reg.jpg') center/cover no-repeat fixed;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, 
                rgba(15, 23, 42, 0.8) 0%, 
                rgba(30, 41, 59, 0.85) 50%,
                rgba(51, 65, 85, 0.8) 100%);
            z-index: 1;
        }

        .register-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            position: relative;
            z-index: 2;
        }

        .register-container {
            width: 100%;
            max-width: 900px;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 24px;
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.2),
                0 8px 32px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.9);
            overflow: hidden;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .register-left {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            min-height: 600px;
        }

        .register-left::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, 
                rgba(59, 130, 246, 0.1) 0%, 
                rgba(147, 197, 253, 0.05) 100%);
        }

        .register-left-content {
            position: relative;
            z-index: 1;
        }

        .brand-icon {
            width: 90px;
            height: 90px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            transition: transform 0.3s ease;
        }

        .brand-icon:hover {
            transform: scale(1.05);
        }

        .brand-icon i {
            font-size: 2.8rem;
            color: white;
        }

        .register-left h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .register-left p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .feature-icons {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 30px;
        }

        .feature-icons .text-center {
            transition: transform 0.3s ease;
            padding: 15px;
            border-radius: 12px;
        }

        .feature-icons .text-center:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-5px);
        }

        .feature-icons i {
            margin-bottom: 10px;
        }

        .feature-icons p {
            font-size: 0.9rem;
            margin: 0;
        }

        .register-right {
            padding: 50px 40px;
            background: white;
            max-height: 90vh;
            overflow-y: auto;
        }

        .register-right::-webkit-scrollbar {
            width: 6px;
        }
        
        .register-right::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .register-right::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #3b82f6, #1e40af);
            border-radius: 10px;
        }

        .register-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .register-header h3 {
            color: #1f2937;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .register-header p {
            color: #6b7280;
            font-size: 1.1rem;
        }

        .form-group {
            margin-bottom: 24px;
            animation: fadeInUp 0.6s ease-out;
        }

        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }
        .form-group:nth-child(4) { animation-delay: 0.4s; }
        .form-group:nth-child(5) { animation-delay: 0.5s; }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .row.g-3 {
            margin-bottom: 0;
        }

        .row.g-3 .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            color: #374151;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.9rem;
            display: block;
        }

        .form-control,
        .form-select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 500;
            background: #f9fafb;
            transition: all 0.3s ease;
            color: #1f2937;
        }

        .form-control:focus,
        .form-select:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-control::placeholder {
            color: #9ca3af;
            font-weight: 400;
        }

        .input-group {
            position: relative;
        }

        .input-group-text {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            transition: all 0.2s ease;
            z-index: 10;
        }

        .input-group-text:hover {
            color: #3b82f6;
            background: #f3f4f6;
        }

        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .strength-weak { 
            background: #ef4444; 
        }
        .strength-medium { 
            background: #f59e0b; 
        }
        .strength-strong { 
            background: #10b981; 
        }

        .form-check {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 30px 0;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            border: 2px solid #d1d5db;
            border-radius: 4px;
            background: white;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 2px;
        }

        .form-check-input:checked {
            background: #3b82f6;
            border-color: #3b82f6;
        }

        .form-check-label {
            color: #4b5563;
            font-size: 0.9rem;
            font-weight: 500;
            line-height: 1.5;
            cursor: pointer;
            flex: 1;
        }

        .form-check-label a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .form-check-label a:hover {
            color: #1e40af;
            text-decoration: underline;
        }

        .btn-register {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 30px 0 20px;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-register:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }

        .alert {
            border: none;
            border-radius: 12px;
            margin-bottom: 24px;
            padding: 16px 20px;
            font-weight: 500;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }

        .alert i {
            margin-right: 10px;
            font-size: 1.1rem;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .divider {
            text-align: center;
            margin: 24px 0;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e5e7eb;
        }

        .divider span {
            background: white;
            padding: 0 16px;
            color: #6b7280;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .login-link {
            text-align: center;
            padding: 20px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        .login-link:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .login-link p {
            margin: 0;
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .login-link a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
            margin-left: 4px;
            transition: color 0.2s ease;
        }

        .login-link a:hover {
            color: #1e40af;
        }

        /* Modal styles */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 24px 30px;
        }

        .modal-title {
            font-weight: 700;
            font-size: 1.2rem;
        }

        .modal-body {
            padding: 30px;
            line-height: 1.6;
        }

        .modal-body h6 {
            color: #1f2937;
            font-weight: 700;
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 1rem;
        }

        .modal-body h6:first-child {
            margin-top: 0;
        }

        .modal-body p {
            color: #4b5563;
            margin-bottom: 15px;
        }

        .modal-footer {
            padding: 20px 30px;
            background: #f8fafc;
            border-radius: 0 0 20px 20px;
        }

        /* Footer positioning */
        footer {
            position: relative;
            z-index: 2;
            margin-top: auto;
        }

        /* Responsive design */
        @media (max-width: 992px) {
            .register-container {
                max-width: 600px;
            }
            
            .register-left {
                display: none;
            }
            
            .register-right {
                padding: 40px 30px;
                max-height: none;
            }
            
            .register-wrapper {
                padding: 30px 15px;
            }

            .feature-icons {
                flex-direction: column;
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .register-wrapper {
                padding: 20px 10px;
            }
            
            .register-container {
                border-radius: 20px;
                max-width: 100%;
            }
            
            .register-right {
                padding: 30px 20px;
            }

            .register-header h3 {
                font-size: 1.75rem;
            }

            .form-control,
            .form-select {
                padding: 12px 14px;
            }

            .btn-register {
                padding: 12px;
            }
        }

        @media (max-width: 576px) {
            .register-right {
                padding: 25px 15px;
            }
            
            .register-header h3 {
                font-size: 1.5rem;
            }

            .register-header p {
                font-size: 1rem;
            }

            .form-group {
                margin-bottom: 20px;
            }

            .row.g-3 .col-md-6 {
                margin-bottom: 15px;
            }

            .modal-body {
                padding: 20px;
            }

            .modal-header {
                padding: 20px;
            }
        }

        /* Form validation styles */
        .form-control.is-invalid,
        .form-select.is-invalid {
            border-color: #ef4444;
            background: #fef2f2;
        }

        .form-control.is-valid,
        .form-select.is-valid {
            border-color: #10b981;
            background: #ecfdf5;
        }

        /* Accessibility improvements */
        .form-control:focus-visible,
        .form-select:focus-visible,
        .btn-register:focus-visible {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }

        /* Loading state */
        .btn-register.loading {
            position: relative;
            color: transparent;
            pointer-events: none;
        }

        .btn-register.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        /* Smooth transitions */
        * {
            transition: border-color 0.2s ease, background-color 0.2s ease, color 0.2s ease;
        }

        /* Improved spacing for better visual hierarchy */
        .register-header {
            margin-bottom: 35px;
        }

        .btn-register {
            margin-top: 35px;
            margin-bottom: 25px;
        }

        .form-check {
            margin: 32px 0;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        /* Enhanced visual separation between sections */
        .form-group:nth-child(2)::after {
            content: '';
            display: block;
            width: 100%;
            height: 1px;
            background: linear-gradient(90deg, transparent, #e5e7eb, transparent);
            margin: 30px 0 10px 0;
        }

        .form-group:nth-child(5)::after {
            content: '';
            display: block;
            width: 100%;
            height: 1px;
            background: linear-gradient(90deg, transparent, #e5e7eb, transparent);
            margin: 30px 0 10px 0;
        }

        .form-select {
            width: 100%;
            padding: 14px 45px 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 500;
            background: #f9fafb url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e") no-repeat right 12px center/16px;
            transition: all 0.3s ease;
            color: #1f2937;
            appearance: none;
            cursor: pointer;
        }

        .form-select:focus {
            outline: none;
            border-color: #3b82f6;
            background: white url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%233b82f6' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e") no-repeat right 12px center/16px;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-select option {
            padding: 10px;
            color: #1f2937;
        }

        /* Add gender icon styling */
        .form-group:has(select[name="gender"]) .form-label::before {
            content: '👤';
            margin-right: 8px;
            font-size: 1rem;
        }

        /* Add icons to other form labels */
        .form-group:has(input[name="full_name"]) .form-label::before {
            content: '📝';
            margin-right: 8px;
            font-size: 1rem;
        }

        .form-group:has(input[name="username"]) .form-label::before {
            content: '🏷️';
            margin-right: 8px;
            font-size: 1rem;
        }

        .form-group:has(input[name="phone"]) .form-label::before {
            content: '📱';
            margin-right: 8px;
            font-size: 1rem;
        }

        .form-group:has(input[name="email"]) .form-label::before {
            content: '📧';
            margin-right: 8px;
            font-size: 1rem;
        }

        .form-group:has(input[name="password"]) .form-label::before {
            content: '🔒';
            margin-right: 8px;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <div class="register-wrapper">
        <div class="register-container">
            <div class="row g-0">
                <!-- Left Side -->
                <div class="col-lg-5">
                    <div class="register-left h-100">
                        <div class="register-left-content">
                            <div class="brand-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h2>Tham gia QickMed</h2>
                            <p>Đăng ký tài khoản để trải nghiệm dịch vụ chăm sóc sức khỏe hiện đại và toàn diện.</p>
                            <div class="feature-icons">
                                <div class="text-center">
                                    <i class="fas fa-calendar-check fa-2x mb-2"></i>
                                    <p class="small mb-0">Đặt lịch online</p>
                                </div>
                                <div class="text-center">
                                    <i class="fas fa-file-medical fa-2x mb-2"></i>
                                    <p class="small mb-0">Hồ sơ y tế</p>
                                </div>
                                <div class="text-center">
                                    <i class="fas fa-headset fa-2x mb-2"></i>
                                    <p class="small mb-0">Hỗ trợ 24/7</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side -->
                <div class="col-lg-7">
                    <div class="register-right">
                        <div class="register-header">
                            <h3>Đăng ký tài khoản</h3>
                            <p>Tạo tài khoản mới để bắt đầu sử dụng QickMed</p>
                        </div>

                        <?php if($success): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle"></i><?= htmlspecialchars($success) ?>
                                <div class="mt-2">
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-success" role="progressbar" id="redirectProgress" style="width: 0%"></div>
                                    </div>
                                    <small class="text-muted mt-1 d-block">Chuyển hướng trong <span id="countdown">5</span> giây...</small>
                                </div>
                            </div>
                            <script>
                                // Countdown and redirect script
                                let countdown = 5;
                                const countdownElement = document.getElementById('countdown');
                                const progressBar = document.getElementById('redirectProgress');
                                
                                const timer = setInterval(() => {
                                    countdown--;
                                    countdownElement.textContent = countdown;
                                    progressBar.style.width = ((5 - countdown) * 20) + '%';
                                    
                                    if (countdown <= 0) {
                                        clearInterval(timer);
                                        window.location.href = 'login.php?registered=1';
                                    }
                                }, 1000);
                                
                                // Also show immediate redirect option
                                setTimeout(() => {
                                    const alertDiv = document.querySelector('.alert-success');
                                    if (alertDiv) {
                                        alertDiv.innerHTML += '<div class="mt-2"><a href="login.php?registered=1" class="btn btn-success btn-sm"><i class="fas fa-sign-in-alt me-1"></i>Đăng nhập ngay</a></div>';
                                    }
                                }, 1000);
                            </script>
                        <?php endif; ?>
                        
                        <?php if($err): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle"></i><?= htmlspecialchars($err) ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" class="needs-validation" novalidate>
                            <!-- Personal Information -->
                            <div class="form-group">
                                <label class="form-label">Họ và tên đầy đủ</label>
                                <input type="text" name="full_name" class="form-control" 
                                       placeholder="Nhập họ và tên đầy đủ"
                                       value="<?= isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : '' ?>" 
                                       required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Giới tính</label>
                                <select name="gender" class="form-select" required>
                                    <option value="">Chọn giới tính</option>
                                    <option value="Nam" <?= (isset($_POST['gender']) && $_POST['gender'] === 'Nam') ? 'selected' : '' ?>>Nam</option>
                                    <option value="Nữ" <?= (isset($_POST['gender']) && $_POST['gender'] === 'Nữ') ? 'selected' : '' ?>>Nữ</option>
                                    <option value="Khác" <?= (isset($_POST['gender']) && $_POST['gender'] === 'Khác') ? 'selected' : '' ?>>Khác</option>
                                </select>
                            </div>

                            <!-- Account Information -->
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Tên đăng nhập</label>
                                        <input type="text" name="username" class="form-control" 
                                               placeholder="Ít nhất 3 ký tự"
                                               value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" 
                                               required minlength="3">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Số điện thoại</label>
                                        <input type="tel" name="phone" class="form-control" 
                                               placeholder="10-11 số"
                                               value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>" 
                                               required pattern="[0-9]{10,11}">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Địa chỉ email</label>
                                <input type="email" name="email" class="form-control" 
                                       placeholder="example@email.com"
                                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" 
                                       required>
                            </div>

                            <!-- Password Fields -->
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Mật khẩu</label>
                                        <div class="input-group">
                                            <input type="password" name="password" id="password" class="form-control" 
                                                   placeholder="Ít nhất 6 ký tự" required minlength="6">
                                            <span class="input-group-text" onclick="togglePassword('password', 'toggleIcon1')">
                                                <i class="fas fa-eye" id="toggleIcon1"></i>
                                            </span>
                                        </div>
                                        <div class="password-strength">
                                            <div class="strength-bar" id="strengthBar"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Xác nhận mật khẩu</label>
                                        <div class="input-group">
                                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" 
                                                   placeholder="Nhập lại mật khẩu" required>
                                            <span class="input-group-text" onclick="togglePassword('confirm_password', 'toggleIcon2')">
                                                <i class="fas fa-eye" id="toggleIcon2"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Terms and Conditions -->
                            <div class="form-check">
                                <input type="checkbox" name="terms" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    Tôi đồng ý với 
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Điều khoản sử dụng</a> và 
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Chính sách bảo mật</a>
                                </label>
                            </div>

                            <button type="submit" class="btn-register">
                                <i class="fas fa-user-plus me-2"></i>Đăng ký tài khoản
                            </button>
                        </form>

                        <div class="divider">
                            <span>hoặc</span>
                        </div>

                        <div class="login-link">
                            <p>Đã có tài khoản? 
                                <a href="login.php">
                                    <i class="fas fa-sign-in-alt me-1"></i>Đăng nhập ngay
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">
                        <i class="fas fa-file-contract me-2"></i>Điều khoản sử dụng
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Chấp nhận điều khoản</h6>
                    <p>Bằng việc sử dụng dịch vụ QickMed, bạn đồng ý tuân thủ các điều khoản này.</p>
                    
                    <h6>2. Sử dụng dịch vụ</h6>
                    <p>Bạn cam kết sử dụng dịch vụ một cách hợp pháp và không vi phạm quyền của bên thứ ba.</p>
                    
                    <h6>3. Bảo mật thông tin</h6>
                    <p>Chúng tôi cam kết bảo vệ thông tin cá nhân của bạn theo chính sách bảo mật.</p>
                    
                    <h6>4. Trách nhiệm</h6>
                    <p>QickMed không chịu trách nhiệm cho bất kỳ thiệt hại nào phát sinh từ việc sử dụng dịch vụ.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Privacy Modal -->
    <div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="privacyModalLabel">
                        <i class="fas fa-shield-alt me-2"></i>Chính sách bảo mật
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Thu thập thông tin</h6>
                    <p>Chúng tôi chỉ thu thập thông tin cần thiết để cung cấp dịch vụ y tế tốt nhất.</p>
                    
                    <h6>2. Sử dụng thông tin</h6>
                    <p>Thông tin của bạn được sử dụng để cung cấp dịch vụ, hỗ trợ khách hàng và cải thiện chất lượng.</p>
                    
                    <h6>3. Chia sẻ thông tin</h6>
                    <p>Chúng tôi không chia sẻ thông tin cá nhân với bên thứ ba khi chưa có sự đồng ý của bạn.</p>
                    
                    <h6>4. Bảo mật dữ liệu</h6>
                    <p>Chúng tôi áp dụng các biện pháp bảo mật tiên tiến để bảo vệ dữ liệu của bạn.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePassword(fieldId, iconId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleIcon = document.getElementById(iconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Password strength meter
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strengthBar');
            let strength = 0;
            
            // Check password criteria
            if (password.length >= 6) strength += 25;
            if (password.match(/[a-z]/)) strength += 25;
            if (password.match(/[A-Z]/)) strength += 25;
            if (password.match(/[0-9]/)) strength += 25;
            
            strengthBar.style.width = strength + '%';
            
            if (strength < 50) {
                strengthBar.className = 'strength-bar strength-weak';
            } else if (strength < 75) {
                strengthBar.className = 'strength-bar strength-medium';
            } else {
                strengthBar.className = 'strength-bar strength-strong';
            }
        });

        // Confirm password validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Mật khẩu xác nhận không khớp');
            } else {
                this.setCustomValidity('');
            }
        });

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        });

        // Format phone number
        document.querySelector('input[name="phone"]').addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 11) {
                value = value.substring(0, 11);
            }
            this.value = value;
        });

        // Username validation
        document.querySelector('input[name="username"]').addEventListener('input', function() {
            const username = this.value.toLowerCase().replace(/[^a-z0-9_]/g, '');
            this.value = username;
        });

        // Auto focus first input
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('input[name="full_name"]').focus();
        });
    </script>
</body>
</html> 