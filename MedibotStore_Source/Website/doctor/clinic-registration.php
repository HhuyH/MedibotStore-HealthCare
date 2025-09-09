<?php
session_start();
require_once '../includes/db.php';

// Kiểm tra đăng nhập và quyền bác sĩ
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [2, 3])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Lấy thông tin bác sĩ hiện tại
$doctor_info = null;
$stmt = $conn->prepare("
    SELECT d.*, s.name as specialty_name, c.name as current_clinic_name, c.address as current_clinic_address
    FROM doctors d 
    LEFT JOIN specialties s ON d.specialty_id = s.specialty_id
    LEFT JOIN clinics c ON d.clinic_id = c.clinic_id
    WHERE d.user_id = ?
");

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $doctor_info = $result->fetch_assoc();
    }
    $stmt->close();
}

// Nếu không tìm thấy thông tin bác sĩ
if (!$doctor_info) {
    $message = 'Không tìm thấy thông tin bác sĩ. Vui lòng liên hệ admin.';
    $message_type = 'danger';
}

// Lấy danh sách tất cả clinic
$clinics = [];
$stmt = $conn->prepare("SELECT clinic_id, name, address, phone, email, description FROM clinics ORDER BY name");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $clinics[] = $row;
    }
    $stmt->close();
}

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_clinic'])) {
    $new_clinic_id = (int)($_POST['clinic_id'] ?? 0);
    
    if ($new_clinic_id === 0) {
        $message = 'Vui lòng chọn một phòng khám.';
        $message_type = 'warning';
    } else {
        // Cập nhật clinic_id cho bác sĩ
        $stmt = $conn->prepare("UPDATE doctors SET clinic_id = ? WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $new_clinic_id, $user_id);
            if ($stmt->execute()) {
                $message = 'Cập nhật nơi làm việc thành công!';
                $message_type = 'success';
                
                // Reload thông tin bác sĩ
                header('Location: clinic-registration.php?success=1');
                exit();
            } else {
                $message = 'Lỗi khi cập nhật: ' . $stmt->error;
                $message_type = 'danger';
            }
            $stmt->close();
        } else {
            $message = 'Lỗi kết nối database: ' . $conn->error;
            $message_type = 'danger';
        }
    }
}

// Hiển thị thông báo thành công từ URL
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $message = 'Cập nhật nơi làm việc thành công!';
    $message_type = 'success';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký Nơi làm việc - Bác sĩ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Modern Background */
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-attachment: fixed;
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        /* Container styling */
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            margin: 20px auto;
            padding: 2rem;
            backdrop-filter: blur(10px);
        }

        /* Doctor Navigation Bar */
        .doctor-nav {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            border-radius: 15px;
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            color: white;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }

        .doctor-nav h2 {
            color: white;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .nav-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .nav-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            color: white;
            transform: translateY(-2px);
        }

        .nav-btn.active {
            background: rgba(255, 255, 255, 0.9);
            color: #4f46e5;
            border-color: white;
        }

        .main-card { 
            background: rgba(255, 255, 255, 0.9); 
            border-radius: 15px; 
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.6);
        }
        
        .clinic-card { 
            border: 2px solid #e9ecef; 
            border-radius: 10px; 
            transition: all 0.3s; 
            cursor: pointer;
            background: rgba(255, 255, 255, 0.8);
        }
        
        .clinic-card:hover { 
            border-color: #4f46e5; 
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.2);
        }
        
        .clinic-card.selected { 
            border-color: #4f46e5; 
            background-color: rgba(79, 70, 229, 0.05);
        }
        
        .clinic-card input[type="radio"] { 
            transform: scale(1.2);
            accent-color: #4f46e5;
        }
        
        .current-clinic { 
            background: linear-gradient(135deg, #4f46e5, #6366f1); 
            color: white; 
            border-radius: 10px; 
        }
        
        .section-title { 
            color: #1f2937; 
            font-weight: 700; 
            border-bottom: 2px solid #4f46e5; 
            padding-bottom: 8px; 
            margin-bottom: 20px; 
        }

        .btn-primary {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(79, 70, 229, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
        }

        .btn-outline-secondary {
            color: #6b7280;
            border-color: #6b7280;
            border-radius: 8px;
            font-weight: 600;
        }

        .btn-outline-secondary:hover {
            background: #6b7280;
            border-color: #6b7280;
            transform: translateY(-1px);
        }

        /* Alert styling */
        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            color: #92400e;
            border-left: 4px solid #f59e0b;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-container {
                margin: 10px;
                padding: 1.5rem;
            }

            .doctor-nav {
                padding: 1.5rem;
            }

            .nav-buttons {
                justify-content: center;
            }

            .nav-btn {
                flex: 1;
                justify-content: center;
                min-width: 140px;
            }

            .main-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container py-3">
        <div class="main-container">
            <!-- Doctor Navigation -->
            <div class="doctor-nav">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div class="mb-3 mb-md-0">
                        <h2 class="mb-1">
                            <i class="fas fa-user-md me-2"></i>
                            Panel Bác sĩ
                        </h2>
                        <p class="mb-0 opacity-75">Quản lý lịch làm việc và lịch hẹn</p>
                    </div>
                    <div class="nav-buttons">
                        <a href="schedule.php" class="nav-btn">
                            <i class="fas fa-calendar-alt"></i>
                            Lịch làm việc
                        </a>
                        <a href="../appointments.php" class="nav-btn">
                            <i class="fas fa-calendar-check"></i>
                            Lịch hẹn của tôi
                        </a>
                        <a href="clinic-registration.php" class="nav-btn active">
                            <i class="fas fa-hospital"></i>
                            Nơi làm việc
                        </a>
                        <a href="appointment-view.php?id=1" class="nav-btn">
                            <i class="fas fa-eye"></i>
                            Xem lịch hẹn
                        </a>
                    </div>
                </div>
            </div>

            <!-- Header -->
            <div class="header-section mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h3 mb-1 text-dark">
                            <i class="fas fa-hospital text-primary me-2"></i>
                            Đăng ký Nơi làm việc
                        </h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                                <li class="breadcrumb-item"><a href="schedule.php">Lịch làm việc</a></li>
                                <li class="breadcrumb-item active">Nơi làm việc</li>
                            </ol>
                        </nav>
                    </div>
                    <div>
                        <a href="schedule.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </div>
            </div>

        <!-- Thông báo -->
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($doctor_info): ?>
        <div class="row">
            <!-- Thông tin hiện tại -->
            <div class="col-lg-4 mb-4">
                <div class="main-card p-4">
                    <h5 class="section-title">Thông tin hiện tại</h5>
                    
                    <div class="mb-3">
                        <strong>Chuyên khoa:</strong><br>
                        <span class="badge bg-info"><?= htmlspecialchars($doctor_info['specialty_name']) ?></span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Nơi làm việc hiện tại:</strong><br>
                        <?php if ($doctor_info['current_clinic_name']): ?>
                        <div class="current-clinic p-3 mt-2">
                            <h6 class="mb-1"><?= htmlspecialchars($doctor_info['current_clinic_name']) ?></h6>
                            <small><i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($doctor_info['current_clinic_address']) ?></small>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Chưa đăng ký nơi làm việc
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($doctor_info['biography']): ?>
                    <div class="mb-3">
                        <strong>Tiểu sử:</strong><br>
                        <small class="text-muted"><?= nl2br(htmlspecialchars($doctor_info['biography'])) ?></small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Form chọn clinic -->
            <div class="col-lg-8">
                <div class="main-card p-4">
                    <h5 class="section-title">Chọn Nơi làm việc</h5>
                    
                    <form method="POST">
                        <div class="row">
                            <?php foreach ($clinics as $clinic): ?>
                            <div class="col-md-6 mb-3">
                                <label class="clinic-card p-3 d-block h-100 <?= $clinic['clinic_id'] == $doctor_info['clinic_id'] ? 'selected' : '' ?>">
                                    <div class="d-flex align-items-start">
                                        <input type="radio" name="clinic_id" value="<?= $clinic['clinic_id'] ?>" 
                                               class="me-3 mt-1" 
                                               <?= $clinic['clinic_id'] == $doctor_info['clinic_id'] ? 'checked' : '' ?>>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?= htmlspecialchars($clinic['name']) ?></h6>
                                            <p class="mb-1 text-muted small">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?= htmlspecialchars($clinic['address']) ?>
                                            </p>
                                            <?php if ($clinic['phone']): ?>
                                            <p class="mb-1 text-muted small">
                                                <i class="fas fa-phone me-1"></i>
                                                <?= htmlspecialchars($clinic['phone']) ?>
                                            </p>
                                            <?php endif; ?>
                                            <?php if ($clinic['email']): ?>
                                            <p class="mb-1 text-muted small">
                                                <i class="fas fa-envelope me-1"></i>
                                                <?= htmlspecialchars($clinic['email']) ?>
                                            </p>
                                            <?php endif; ?>
                                            <?php if ($clinic['description']): ?>
                                            <p class="mb-0 text-muted small">
                                                <?= htmlspecialchars(substr($clinic['description'], 0, 100)) ?>
                                                <?= strlen($clinic['description']) > 100 ? '...' : '' ?>
                                            </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Chọn phòng khám chính nơi bạn làm việc
                                </small>
                            </div>
                            <button type="submit" name="update_clinic" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                Cập nhật Nơi làm việc
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="main-card p-4 text-center">
            <i class="fas fa-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
            <h4 class="mt-3">Không tìm thấy thông tin bác sĩ</h4>
            <p class="text-muted">Vui lòng liên hệ admin để thiết lập tài khoản bác sĩ.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Xử lý click vào clinic card
        document.querySelectorAll('.clinic-card').forEach(card => {
            card.addEventListener('click', function() {
                // Remove selected class from all cards
                document.querySelectorAll('.clinic-card').forEach(c => c.classList.remove('selected'));
                
                // Add selected class to clicked card
                this.classList.add('selected');
                
                // Check the radio button
                this.querySelector('input[type="radio"]').checked = true;
            });
        });
    </script>
</body>
</html> 