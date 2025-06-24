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
    // Bắt đầu output buffering để tránh output trước JSON
    // Json khởi tạo trước nhưng chưa có dữ liệu
    ob_start();
    
    // Set content type để đảm bảo response luôn là JSON
    header('Content-Type: application/json');
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    if (!$username || !$password) {
        ob_clean(); // Clear any output
        echo json_encode([
            'status' => 'error',
            'message' => 'Vui lòng nhập đầy đủ thông tin!'
        ]);
        exit;
    }

    try {
        $query = "
            SELECT u.user_id, u.username, u.email, u.password, u.phone_number, r.role_name, ui.full_name 
            FROM users u
            JOIN roles r ON u.role_id = r.role_id
            LEFT JOIN users_info ui ON u.user_id = ui.user_id
            WHERE u.username = ? OR u.email = ? OR u.phone_number = ?
            LIMIT 1
        ";

        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            ob_clean(); // Clear any output
            echo json_encode([
                'status' => 'error',
                'message' => 'Lỗi hệ thống, vui lòng thử lại sau!'
            ]);
            exit;
        }

        $stmt->bind_param('sss', $username, $username, $username);
        $stmt->execute();

        // Lấy kết quả dưới dạng associative array
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row) {
            // Kiểm tra mật khẩu (có thể dùng password_verify nếu đã hash)
            if ($password === $row['password'] || password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['role_name'] = $row['role_name'];
                $_SESSION['full_name'] = $row['full_name'];

                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (86400 * 30), '/');

                    $stmt_token = $conn->prepare("
                        INSERT INTO remember_tokens (user_id, token, expires_at)
                        VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))
                        ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)
                    ");
                    if ($stmt_token) {
                        $stmt_token->bind_param('is', $row['user_id'], $token);
                        $stmt_token->execute();
                        $stmt_token->close();
                    }
                }   

                // Trả JSON cho JS xử lý
                ob_clean(); // Clear any output
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Đăng nhập thành công!',
                    'user' => [
                        'user_id' => $row['user_id'],
                        'username' => $row['username'],
                        'role' => $row['role_name']
                    ]
                ]);
            } else {
                ob_clean(); // Clear any output
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Mật khẩu không chính xác!'
                ]);
            }
        } else {
            ob_clean(); // Clear any output
            echo json_encode([
                'status' => 'error',
                'message' => 'Tài khoản không tồn tại!'
            ]);
        }

        $stmt->close();
        
    } catch (Exception $e) {
        ob_clean(); // Clear any output
        echo json_encode([
            'status' => 'error',
            'message' => 'Lỗi hệ thống, vui lòng thử lại sau!'
        ]);
        error_log("Login error: " . $e->getMessage());
    }
    exit;
}

// Kiểm tra thông báo từ register
if (isset($_GET['registered'])) {
    $success = 'Chúc mừng! Đăng ký tài khoản thành công. Bây giờ bạn có thể đăng nhập vào hệ thống.';
}
if (isset($_GET['logout'])) {
    $success = 'Đăng xuất thành công! Hẹn gặp lại bạn.';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - QickMed</title>
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
                rgba(15, 23, 42, 0.7) 0%, 
                rgba(30, 41, 59, 0.8) 50%,
                rgba(51, 65, 85, 0.7) 100%);
            z-index: 1;
        }

        .login-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            position: relative;
            z-index: 2;
        }

        .login-container {
            width: 100%;
            max-width: 500px;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 24px;
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.2),
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

        .login-header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            text-align: center;
            padding: 30px 20px;
            position: relative;
        }

        .login-header::before {
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

        .brand-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            position: relative;
            z-index: 1;
        }

        .brand-icon i {
            font-size: 2.5rem;
            color: white;
        }

        .login-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }

        .login-header p {
            opacity: 0.9;
            font-size: 1rem;
            font-weight: 400;
            position: relative;
            z-index: 1;
        }

        .login-body {
            padding: 40px 30px;
            background: white;
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

        .demo-info {
            background: #eff6ff;
            border: 1px solid #dbeafe;
            border-left: 4px solid #3b82f6;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 28px;
        }

        .demo-info h6 {
            color: #1e40af;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .demo-info p {
            color: #1e40af;
            margin: 0;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            color: #374151;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.9rem;
            display: block;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 500;
            background: #f9fafb;
            transition: all 0.3s ease;
            color: #1f2937;
            position: relative;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-control::placeholder {
            color: #9ca3af;
            font-weight: 400;
        }

        /* States for form validation */
        .form-control.error {
            border-color: #ef4444 !important;
            background: #fef2f2 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
        }

        .form-control.success {
            border-color: #10b981 !important;
            background: #ecfdf5 !important;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1) !important;
        }

        .form-control.success + .password-toggle {
            color: #10b981 !important;
        }

        .form-control.error + .password-toggle {
            color: #ef4444 !important;
        }

        .password-wrapper {
            position: relative;
        }

        .password-toggle {
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
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
        }

        .password-toggle:hover {
            color: #3b82f6;
            background: #f3f4f6;
        }

        /* Validation icon styles */
        .validation-icon {
            position: absolute;
            right: 50px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.1rem;
            opacity: 0;
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .validation-icon.show {
            opacity: 1;
        }

        .validation-icon.success {
            color: #10b981;
        }

        .validation-icon.error {
            color: #ef4444;
        }

        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 28px;
            gap: 10px;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            border: 2px solid #d1d5db;
            border-radius: 4px;
            background: white;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .form-check-input:checked {
            background: #3b82f6;
            border-color: #3b82f6;
        }

        .form-check-label {
            color: #4b5563;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
        }

        .btn-login {
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
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .forgot-link {
            display: block;
            text-align: center;
            color: #3b82f6;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 24px;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .forgot-link:hover {
            color: #1e40af;
            background: #f3f4f6;
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

        .register-link {
            text-align: center;
            padding: 20px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        .register-link:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .register-link p {
            margin: 0;
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .register-link a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
            margin-left: 4px;
            transition: color 0.2s ease;
        }

        .register-link a:hover {
            color: #1e40af;
        }

        /* Loading state */
        .btn-login.loading {
            position: relative;
            color: transparent;
            pointer-events: none;
        }

        .btn-login.loading::after {
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

        /* Footer positioning */
        footer {
            position: relative;
            z-index: 2;
            margin-top: auto;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .login-wrapper {
                padding: 20px 15px;
            }
            
            .login-container {
                max-width: 100%;
                border-radius: 20px;
            }
            
            .login-header {
                padding: 35px 25px;
            }
            
            .login-body {
                padding: 35px 25px;
            }

            .brand-icon {
                width: 70px;
                height: 70px;
            }

            .brand-icon i {
                font-size: 2.2rem;
            }

            .login-header h1 {
                font-size: 1.75rem;
            }
        }

        @media (max-width: 480px) {
            .login-wrapper {
                padding: 15px 10px;
            }

            .login-header {
                padding: 30px 20px;
            }
            
            .login-body {
                padding: 30px 20px;
            }

            .brand-icon {
                width: 65px;
                height: 65px;
                margin-bottom: 16px;
            }

            .brand-icon i {
                font-size: 2rem;
            }

            .login-header h1 {
                font-size: 1.6rem;
            }

            .form-control {
                padding: 12px 14px;
            }

            .btn-login {
                padding: 12px;
            }
        }

        /* Enhanced form validation styles */
        .form-control.is-invalid {
            border-color: #ef4444;
            background: #fef2f2;
            animation: shake 0.5s ease-in-out;
        }

        .form-control.is-valid {
            border-color: #10b981;
            background: #ecfdf5;
        }

        /* Shake animation for errors */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* Better error message styling */
        .alert {
            border: none;
            border-radius: 12px;
            margin-bottom: 24px;
            padding: 16px 20px;
            font-weight: 500;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            animation: slideIn 0.4s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert i {
            margin-right: 10px;
            font-size: 1.1rem;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border-left: 4px solid #10b981;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15);
        }

        /* Accessibility improvements */
        .form-control:focus-visible,
        .btn-login:focus-visible,
        .forgot-link:focus-visible {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }

        /* Smooth transitions for better UX */
        * {
            transition: border-color 0.2s ease, background-color 0.2s ease, color 0.2s ease;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-header">
                <div class="brand-icon">
                    <i class="fas fa-stethoscope"></i>
                </div>
                <h1>QickMed</h1>
                <p>Hệ thống quản lý phòng khám</p>
            </div>

            <div class="login-body">
                <?php if($success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle"></i><?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>
                
                <?php if($err): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle"></i><?= htmlspecialchars($err) ?>
                    </div>
                <?php endif; ?>

                <div class="demo-info">
                    <h6><i class="fas fa-info-circle me-2"></i>Thông tin đăng nhập</h6>
                    <p>Bạn có thể đăng nhập bằng: <strong>Tên đăng nhập</strong>, <strong>Email</strong> hoặc <strong>Số điện thoại</strong></p>
                </div>

                <form method="post" class="needs-validation" id="loginForm" novalidate>
                    <div class="form-group">
                        <label class="form-label">Tên đăng nhập, Email hoặc Số điện thoại</label>
                        <div class="password-wrapper">
                            <input type="text" name="username" id="username" class="form-control" 
                                   placeholder="Nhập tên đăng nhập, email hoặc số điện thoại"
                                   value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" 
                                   required>
                            <i class="fas fa-check validation-icon" id="usernameValidIcon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Mật khẩu</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="password" class="form-control" 
                                   placeholder="Nhập mật khẩu" required>
                            <i class="fas fa-check validation-icon" id="passwordValidIcon"></i>
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" name="remember" class="form-check-input" id="remember">
                        <label class="form-check-label" for="remember">
                            Ghi nhớ đăng nhập
                        </label>
                    </div>

                    <button type="submit" class="btn-login" id="loginBtn">
                        Đăng nhập
                    </button>
                </form>

                <div id="loginMessage" class="alert alert-danger" style="display: none; margin-top: 15px;">
                </div>

                <a href="forgot_password.php" class="forgot-link">
                    <i class="fas fa-key me-1"></i>Quên mật khẩu?
                </a>

                <div class="divider">
                    <span>hoặc</span>
                </div>

                <div class="register-link">
                    <p>Chưa có tài khoản? 
                        <a href="register.php">
                            <i class="fas fa-user-plus me-1"></i>Đăng ký ngay
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
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

        // Enhanced form validation and visual feedback
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.needs-validation');
            const loginBtn = document.getElementById('loginBtn');
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            const usernameValidIcon = document.getElementById('usernameValidIcon');
            const passwordValidIcon = document.getElementById('passwordValidIcon');

            // Real-time validation
            usernameInput.addEventListener('input', function() {
                validateField(this, usernameValidIcon);
            });

            passwordInput.addEventListener('input', function() {
                validateField(this, passwordValidIcon);
            });

            function validateField(input, icon) {
                const value = input.value.trim();
                
                if (value.length >= 3) {
                    input.classList.remove('error');
                    input.classList.add('success');
                    icon.classList.remove('error');
                    icon.classList.add('success', 'show');
                } else if (value.length > 0) {
                    input.classList.remove('success');
                    input.classList.add('error');
                    icon.classList.remove('success', 'show');
                    icon.classList.add('error');
                } else {
                    input.classList.remove('success', 'error');
                    icon.classList.remove('success', 'error', 'show');
                }
            }

            function setFieldError(input, icon) {
                input.classList.remove('success');
                input.classList.add('error', 'is-invalid');
                icon.classList.remove('success', 'show');
                icon.classList.add('error');
                
                // Add shake animation
                setTimeout(() => {
                    input.classList.remove('is-invalid');
                }, 500);
            }

            function resetFieldStates() {
                [usernameInput, passwordInput].forEach(input => {
                    input.classList.remove('success', 'error', 'is-invalid');
                });
                [usernameValidIcon, passwordValidIcon].forEach(icon => {
                    icon.classList.remove('success', 'error', 'show');
                });
            }

            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    // Visual feedback for invalid fields
                    if (!usernameInput.value.trim()) {
                        setFieldError(usernameInput, usernameValidIcon);
                    }
                    if (!passwordInput.value.trim()) {
                        setFieldError(passwordInput, passwordValidIcon);
                    }
                } else {
                    // Add loading state
                    loginBtn.classList.add('loading');
                    loginBtn.disabled = true;
                }
                form.classList.add('was-validated');
            }, false);

            // Auto focus first input
            usernameInput.focus();

            // Global function for login response handling
            window.handleLoginResponse = function(success, message) {
                if (success) {
                    usernameInput.classList.add('success');
                    passwordInput.classList.add('success');
                    usernameValidIcon.classList.add('success', 'show');
                    passwordValidIcon.classList.add('success', 'show');
                } else {
                    setFieldError(usernameInput, usernameValidIcon);
                    setFieldError(passwordInput, passwordValidIcon);
                }
            };
        });

        document.getElementById("loginForm").addEventListener("submit", async function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = new URLSearchParams(formData);
            const loginBtn = document.getElementById('loginBtn');
            const loginMessage = document.getElementById('loginMessage');

            // Add loading state
            loginBtn.classList.add('loading');
            loginBtn.disabled = true;
            loginMessage.style.display = 'none';

            try {
                const res = await fetch("login.php", {
                    method: "POST",
                    body: data
                });

                // Kiểm tra response status
                if (!res.ok) {
                    throw new Error(`HTTP ${res.status}: ${res.statusText}`);
                }

                const contentType = res.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Response không phải JSON');
                }

                const result = await res.json();

                if (result.status === "success") {
                    const u = result.user;
                    localStorage.setItem("userInfo", JSON.stringify({
                        user_id: u.user_id,
                        username: u.username,
                        role: u.role
                    }));
                    
                    // Visual feedback cho success
                    window.handleLoginResponse(true, result.message);
                    
                    // Hiển thị thông báo thành công
                    loginMessage.className = 'alert alert-success';
                    loginMessage.innerHTML = '<i class="fas fa-check-circle"></i>' + result.message;
                    loginMessage.style.display = 'block';
                    
                    // Redirect sau 1.5 giây
                    setTimeout(() => {
                        switch(u.role) {
                            case 'admin':
                                window.location.href = "admin/dashboard.php";
                                break;
                            case 'doctor':
                                window.location.href = "doctor/dashboard.php";
                                break;
                            default:
                                window.location.href = "index.php";
                                break;
                        }
                    }, 1500);
                } else {
                    // Visual feedback cho error
                    window.handleLoginResponse(false, result.message);
                    
                    // Hiển thị thông báo lỗi cụ thể từ server
                    loginMessage.className = 'alert alert-danger';
                    loginMessage.innerHTML = '<i class="fas fa-exclamation-triangle"></i>' + (result.message || "Đăng nhập thất bại!");
                    loginMessage.style.display = 'block';
                }

            } catch (err) {
                console.error("Lỗi:", err);
                
                // Visual feedback cho error
                window.handleLoginResponse(false, "Lỗi kết nối");
                
                loginMessage.className = 'alert alert-danger';
                loginMessage.innerHTML = '<i class="fas fa-exclamation-triangle"></i>Lỗi kết nối hoặc server không phản hồi!';
                loginMessage.style.display = 'block';
            } finally {
                // Remove loading state
                loginBtn.classList.remove('loading');
                loginBtn.disabled = false;
            }
        });

    </script>
</body>
</html> 