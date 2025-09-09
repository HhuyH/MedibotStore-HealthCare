<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions/enhanced_logger.php';

// Redirect nếu đã đăng nhập
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$token = $_GET['token'] ?? '';
$error = '';
$success = '';
$tokenValid = false;
$tokenData = null;

// Kiểm tra token
if (!empty($token)) {
    try {
        $stmt = $conn->prepare("
            SELECT prt.*, u.username, u.email, ui.full_name 
            FROM password_reset_tokens prt 
            LEFT JOIN users u ON prt.user_id = u.user_id 
            LEFT JOIN users_info ui ON u.user_id = ui.user_id
            WHERE prt.token = ? AND prt.expires_at > NOW() AND prt.used = FALSE
            LIMIT 1
        ");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $tokenData = $result->fetch_assoc();
        
        if ($tokenData) {
            $tokenValid = true;
        } else {
            $error = 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn!';
        }
    } catch (Exception $e) {
        EnhancedLogger::logError('Token validation error', $e->getMessage());
        $error = 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.';
    }
} else {
    $error = 'Token không hợp lệ!';
}

// Xử lý form đặt lại mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    header('Content-Type: application/json');
    
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password) || empty($confirm_password)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Vui lòng nhập đầy đủ thông tin!'
        ]);
        exit();
    }
    
    if (strlen($new_password) < 6) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Mật khẩu phải có ít nhất 6 ký tự!'
        ]);
        exit();
    }
    
    if ($new_password !== $confirm_password) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Mật khẩu xác nhận không khớp!'
        ]);
        exit();
    }
    
    try {
        // Kiểm tra lại token (double-check)
        $stmt = $conn->prepare("
            SELECT * FROM password_reset_tokens 
            WHERE token = ? AND expires_at > NOW() AND used = FALSE
        ");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $tokenCheck = $result->fetch_assoc();
        
        if (!$tokenCheck) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Token không hợp lệ hoặc đã được sử dụng!'
            ]);
            exit();
        }
        
        // Bắt đầu transaction
        $conn->begin_transaction();
        
        // Hash mật khẩu mới
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Cập nhật mật khẩu
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->bind_param('si', $hashed_password, $tokenCheck['user_id']);
        
        if (!$stmt->execute()) {
            throw new Exception('Không thể cập nhật mật khẩu');
        }
        
        // Đánh dấu token đã sử dụng
        $stmt = $conn->prepare("
            UPDATE password_reset_tokens 
            SET used = TRUE, used_at = NOW() 
            WHERE token = ?
        ");
        $stmt->bind_param('s', $token);
        
        if (!$stmt->execute()) {
            throw new Exception('Không thể cập nhật trạng thái token');
        }
        
        // Xóa tất cả token khác của user này
        $stmt = $conn->prepare("
            DELETE FROM password_reset_tokens 
            WHERE user_id = ? AND token != ?
        ");
        $stmt->bind_param('is', $tokenCheck['user_id'], $token);
        $stmt->execute();
        
        // Xóa tất cả remember token của user này (buộc đăng nhập lại)
        $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
        $stmt->bind_param('i', $tokenCheck['user_id']);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Log successful password reset
        EnhancedLogger::logSecurity('PASSWORD_RESET_SUCCESS', "User successfully reset password", 'MEDIUM', [
            'user_id' => $tokenCheck['user_id'],
            'email' => $tokenCheck['email'],
            'token_id' => $tokenCheck['id'],
            'ip' => $_SERVER['REMOTE_ADDR']
        ]);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Mật khẩu đã được đặt lại thành công! Bạn có thể đăng nhập bằng mật khẩu mới.'
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        EnhancedLogger::logError('Password reset error', $e->getMessage());
        
        echo json_encode([
            'status' => 'error',
            'message' => 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.'
        ]);
    }
    
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu - QickMed</title>
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

        .reset-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            position: relative;
            z-index: 2;
        }

        .reset-container {
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

        .reset-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            text-align: center;
            padding: 30px 20px;
            position: relative;
        }

        .reset-header.error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .reset-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, 
                rgba(16, 185, 129, 0.1) 0%, 
                rgba(5, 150, 105, 0.05) 100%);
        }

        .reset-header.error::before {
            background: linear-gradient(135deg, 
                rgba(239, 68, 68, 0.1) 0%, 
                rgba(220, 38, 38, 0.05) 100%);
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

        .reset-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }

        .reset-header p {
            opacity: 0.9;
            font-size: 1rem;
            font-weight: 400;
            position: relative;
            z-index: 1;
        }

        .reset-body {
            padding: 40px 30px;
            background: white;
        }

        .user-info {
            background: #f0f9ff;
            border: 1px solid #7dd3fc;
            border-left: 4px solid #0ea5e9;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 28px;
        }

        .user-info h6 {
            color: #0369a1;
            font-size: 0.9rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .user-info p {
            color: #0369a1;
            margin: 0;
            font-size: 0.9rem;
            line-height: 1.5;
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
        }

        .form-control:focus {
            outline: none;
            border-color: #10b981;
            background: white;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .form-control::placeholder {
            color: #9ca3af;
            font-weight: 400;
        }

        .form-control.is-invalid {
            border-color: #ef4444;
            background: #fef2f2;
            animation: shake 0.5s ease-in-out;
        }

        .form-control.is-valid {
            border-color: #10b981;
            background: #ecfdf5;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
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
            color: #10b981;
            background: #f3f4f6;
        }

        .password-strength {
            margin-top: 8px;
            font-size: 0.85rem;
        }

        .strength-indicator {
            display: flex;
            gap: 4px;
            margin-top: 5px;
        }

        .strength-bar {
            height: 4px;
            flex: 1;
            background: #e5e7eb;
            border-radius: 2px;
            transition: background-color 0.3s ease;
        }

        .strength-bar.active {
            background: #10b981;
        }

        .strength-bar.medium {
            background: #f59e0b;
        }

        .strength-bar.weak {
            background: #ef4444;
        }

        .btn-reset {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-reset:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-reset:active {
            transform: translateY(0);
        }

        .btn-reset.loading {
            position: relative;
            color: transparent;
            pointer-events: none;
        }

        .btn-reset.loading::after {
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

        .error-container {
            text-align: center;
            padding: 40px 20px;
        }

        .error-icon {
            font-size: 4rem;
            color: #ef4444;
            margin-bottom: 20px;
        }

        .error-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .error-message {
            color: #6b7280;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        .back-link {
            text-align: center;
            padding: 20px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        .back-link:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .back-link p {
            margin: 0;
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .back-link a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
            margin-left: 4px;
            transition: color 0.2s ease;
        }

        .back-link a:hover {
            color: #1e40af;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .reset-wrapper {
                padding: 20px 15px;
            }
            
            .reset-container {
                max-width: 100%;
                border-radius: 20px;
            }
            
            .reset-header {
                padding: 35px 25px;
            }
            
            .reset-body {
                padding: 35px 25px;
            }

            .brand-icon {
                width: 70px;
                height: 70px;
            }

            .brand-icon i {
                font-size: 2.2rem;
            }

            .reset-header h1 {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="reset-wrapper">
        <div class="reset-container">
            <div class="reset-header <?php echo !$tokenValid ? 'error' : ''; ?>">
                <div class="brand-icon">
                    <i class="fas fa-<?php echo $tokenValid ? 'shield-alt' : 'exclamation-triangle'; ?>"></i>
                </div>
                <h1><?php echo $tokenValid ? 'Đặt lại mật khẩu' : 'Lỗi token'; ?></h1>
                <p><?php echo $tokenValid ? 'Tạo mật khẩu mới cho tài khoản' : 'Link không hợp lệ'; ?></p>
            </div>

            <div class="reset-body">
                <?php if (!$tokenValid): ?>
                    <div class="error-container">
                        <div class="error-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="error-title">Link không hợp lệ</div>
                        <div class="error-message">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                        <div class="back-link">
                            <p>
                                <a href="forgot_password.php">
                                    <i class="fas fa-arrow-left me-1"></i>Yêu cầu đặt lại mật khẩu mới
                                </a>
                            </p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="user-info">
                        <h6><i class="fas fa-user me-2"></i>Thông tin tài khoản</h6>
                        <p>
                            <strong>Tên đăng nhập:</strong> <?php echo htmlspecialchars($tokenData['username']); ?><br>
                            <strong>Email:</strong> <?php echo htmlspecialchars($tokenData['email']); ?><br>
                            <strong>Họ tên:</strong> <?php echo htmlspecialchars($tokenData['full_name'] ?: 'Chưa cập nhật'); ?>
                        </p>
                    </div>

                    <form id="resetForm">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <div class="form-group">
                            <label class="form-label">Mật khẩu mới</label>
                            <div class="password-wrapper">
                                <input type="password" name="new_password" id="newPassword" class="form-control" 
                                       placeholder="Nhập mật khẩu mới" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('newPassword', 'toggleIcon1')">
                                    <i class="fas fa-eye" id="toggleIcon1"></i>
                                </button>
                            </div>
                            <div class="password-strength" id="passwordStrength">
                                <div class="strength-indicator">
                                    <div class="strength-bar" id="strength1"></div>
                                    <div class="strength-bar" id="strength2"></div>
                                    <div class="strength-bar" id="strength3"></div>
                                    <div class="strength-bar" id="strength4"></div>
                                </div>
                                <div class="strength-text" id="strengthText"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Xác nhận mật khẩu</label>
                            <div class="password-wrapper">
                                <input type="password" name="confirm_password" id="confirmPassword" class="form-control" 
                                       placeholder="Nhập lại mật khẩu mới" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword', 'toggleIcon2')">
                                    <i class="fas fa-eye" id="toggleIcon2"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-reset" id="resetBtn">
                            <i class="fas fa-check me-2"></i>Đặt lại mật khẩu
                        </button>
                    </form>

                    <div id="resetMessage" class="alert" style="display: none;"></div>

                    <div class="back-link">
                        <p>
                            <a href="login.php">
                                <i class="fas fa-arrow-left me-1"></i>Quay lại đăng nhập
                            </a>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
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

        // Password strength checker
        function checkPasswordStrength(password) {
            const strength = {
                score: 0,
                text: 'Rất yếu',
                color: 'weak'
            };

            if (password.length >= 6) strength.score += 1;
            if (password.length >= 8) strength.score += 1;
            if (/[A-Z]/.test(password)) strength.score += 1;
            if (/[0-9]/.test(password)) strength.score += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength.score += 1;

            switch (strength.score) {
                case 0:
                case 1:
                    strength.text = 'Rất yếu';
                    strength.color = 'weak';
                    break;
                case 2:
                    strength.text = 'Yếu';
                    strength.color = 'weak';
                    break;
                case 3:
                    strength.text = 'Trung bình';
                    strength.color = 'medium';
                    break;
                case 4:
                    strength.text = 'Mạnh';
                    strength.color = 'active';
                    break;
                case 5:
                    strength.text = 'Rất mạnh';
                    strength.color = 'active';
                    break;
            }

            return strength;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const resetForm = document.getElementById('resetForm');
            const resetBtn = document.getElementById('resetBtn');
            const resetMessage = document.getElementById('resetMessage');
            const newPasswordInput = document.getElementById('newPassword');
            const confirmPasswordInput = document.getElementById('confirmPassword');
            const strengthText = document.getElementById('strengthText');

            if (newPasswordInput) {
                // Auto focus new password input
                newPasswordInput.focus();

                // Password strength indicator
                newPasswordInput.addEventListener('input', function() {
                    const password = this.value;
                    const strength = checkPasswordStrength(password);
                    
                    // Update strength bars
                    for (let i = 1; i <= 4; i++) {
                        const bar = document.getElementById(`strength${i}`);
                        bar.classList.remove('active', 'medium', 'weak');
                        
                        if (i <= strength.score) {
                            bar.classList.add(strength.color);
                        }
                    }
                    
                    // Update strength text
                    strengthText.textContent = password ? strength.text : '';
                    strengthText.style.color = strength.color === 'weak' ? '#ef4444' : 
                                             strength.color === 'medium' ? '#f59e0b' : '#10b981';
                });

                // Real-time validation
                newPasswordInput.addEventListener('input', function() {
                    const password = this.value;
                    
                    if (password.length >= 6) {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    } else if (password.length > 0) {
                        this.classList.remove('is-valid');
                        this.classList.add('is-invalid');
                    } else {
                        this.classList.remove('is-valid', 'is-invalid');
                    }
                });

                confirmPasswordInput.addEventListener('input', function() {
                    const password = newPasswordInput.value;
                    const confirmPassword = this.value;
                    
                    if (confirmPassword && password === confirmPassword) {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    } else if (confirmPassword) {
                        this.classList.remove('is-valid');
                        this.classList.add('is-invalid');
                    } else {
                        this.classList.remove('is-valid', 'is-invalid');
                    }
                });

                resetForm.addEventListener('submit', async function(e) {
                    e.preventDefault();

                    const newPassword = newPasswordInput.value;
                    const confirmPassword = confirmPasswordInput.value;
                    
                    // Validation
                    if (!newPassword) {
                        showMessage('error', 'Vui lòng nhập mật khẩu mới!');
                        newPasswordInput.classList.add('is-invalid');
                        return;
                    }

                    if (newPassword.length < 6) {
                        showMessage('error', 'Mật khẩu phải có ít nhất 6 ký tự!');
                        newPasswordInput.classList.add('is-invalid');
                        return;
                    }

                    if (!confirmPassword) {
                        showMessage('error', 'Vui lòng xác nhận mật khẩu!');
                        confirmPasswordInput.classList.add('is-invalid');
                        return;
                    }

                    if (newPassword !== confirmPassword) {
                        showMessage('error', 'Mật khẩu xác nhận không khớp!');
                        confirmPasswordInput.classList.add('is-invalid');
                        return;
                    }

                    // Show loading state
                    resetBtn.classList.add('loading');
                    resetBtn.disabled = true;
                    resetMessage.style.display = 'none';

                    try {
                        const formData = new FormData(resetForm);
                        const response = await fetch('reset_password.php?token=<?php echo $token; ?>', {
                            method: 'POST',
                            body: formData
                        });

                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }

                        const result = await response.json();

                        if (result.status === 'success') {
                            showMessage('success', result.message);
                            newPasswordInput.classList.add('is-valid');
                            confirmPasswordInput.classList.add('is-valid');
                            
                            // Disable form and redirect after success
                            setTimeout(() => {
                                newPasswordInput.disabled = true;
                                confirmPasswordInput.disabled = true;
                                resetBtn.innerHTML = '<i class="fas fa-check me-2"></i>Thành công';
                                resetBtn.disabled = true;
                                resetBtn.classList.remove('loading');
                                
                                // Redirect to login page after 3 seconds
                                setTimeout(() => {
                                    window.location.href = 'login.php';
                                }, 3000);
                            }, 1000);
                        } else {
                            showMessage('error', result.message);
                        }

                    } catch (error) {
                        console.error('Error:', error);
                        showMessage('error', 'Đã xảy ra lỗi kết nối. Vui lòng thử lại sau.');
                    } finally {
                        if (!resetBtn.innerHTML.includes('Thành công')) {
                            resetBtn.classList.remove('loading');
                            resetBtn.disabled = false;
                        }
                    }
                });
            }

            function showMessage(type, message) {
                resetMessage.className = `alert alert-${type}`;
                resetMessage.innerHTML = `
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                    ${message}
                `;
                resetMessage.style.display = 'block';
                
                // Auto scroll to message
                resetMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    </script>
</body>
</html> 