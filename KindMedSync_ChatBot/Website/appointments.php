<?php
include 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$msg = $err = '';

// Kiểm tra thông báo từ trang đặt lịch
if (isset($_SESSION['appointment_success'])) {
    $msg = $_SESSION['appointment_success'];
    unset($_SESSION['appointment_success']);
}

// Xử lý hủy lịch hẹn
if (isset($_POST['cancel_appointment'])) {
    $appointment_id = (int)$_POST['appointment_id'];
    
    $stmt = $conn->prepare("UPDATE appointments SET status = 'canceled' WHERE appointment_id = ? AND user_id = ? AND status IN ('pending', 'confirmed')");
    $stmt->bind_param('ii', $appointment_id, $user_id);
    
    if ($stmt->execute()) {
        $msg = 'Đã hủy lịch hẹn thành công!';
    } else {
        $err = 'Không thể hủy lịch hẹn. Vui lòng thử lại!';
    }
}

// Lấy danh sách lịch hẹn của user
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$where_clause = "WHERE a.user_id = ?";
$params = [$user_id];
$types = "i";

if ($status_filter && $status_filter !== 'all') {
    $where_clause .= " AND a.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Đếm tổng số lịch hẹn
$count_sql = "SELECT COUNT(*) as total FROM appointments a $where_clause";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_appointments = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_appointments / $limit);

// Lấy danh sách lịch hẹn với phân trang (optimized query)
$sql = "SELECT a.appointment_id, a.appointment_time, a.status, a.reason,
               COALESCE(ui.full_name, 'Chưa xác định') as doctor_name, 
               COALESCE(s.name, 'Chưa xác định') as specialization, 
               ui.profile_picture as doctor_image,
               COALESCE(c.name, 'Chưa xác định') as clinic_name, 
               COALESCE(c.address, 'Chưa cập nhật') as clinic_address, 
               COALESCE(c.phone, 'Chưa cập nhật') as clinic_phone
        FROM appointments a
        LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
        LEFT JOIN users u ON d.user_id = u.user_id
        LEFT JOIN users_info ui ON u.user_id = ui.user_id
        LEFT JOIN specialties s ON d.specialty_id = s.specialty_id
        LEFT JOIN clinics c ON a.clinic_id = c.clinic_id
        $where_clause
        ORDER BY 
            a.appointment_id DESC,
            CASE 
                WHEN a.appointment_time >= NOW() AND a.status IN ('pending', 'confirmed') THEN 1
                WHEN a.appointment_time >= NOW() AND a.status = 'completed' THEN 2
                WHEN a.appointment_time < NOW() AND a.status IN ('pending', 'confirmed', 'completed') THEN 3
                WHEN a.status = 'canceled' THEN 4
            END
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    $err = "Có lỗi xảy ra khi tải dữ liệu lịch hẹn.";
    $appointments = [];
} else {
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) {
        $err = "Có lỗi xảy ra khi tải dữ liệu lịch hẹn.";
        $appointments = [];
    } else {
        $result = $stmt->get_result();
        $appointments = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}

// Thống kê nhanh
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) as canceled
    FROM appointments WHERE user_id = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param('i', $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch hẹn của tôi - Qickmed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-attachment: fixed;
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .main-container { 
            margin-top: 20px; 
            position: relative;
            z-index: 1;
            padding-bottom: 40px;
        }
        
        /* Header styling */
        .page-header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.3);
            text-align: center;
        }
        
        .page-header h1 {
            color: #1f2937;
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-size: 2.2rem;
        }
        
        .page-header .subtitle {
            color: #6b7280;
            font-size: 1.1rem;
            margin: 0;
        }
        
        /* Stats grid */
        .stats-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 1.5rem;
        }
        
        .stat-card { 
            background: rgba(255,255,255,0.9);
            border-radius: 12px; 
            padding: 1.5rem 1rem; 
            text-align: center; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.6);
            transition: transform 0.2s ease;
            position: relative;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--card-color);
            border-radius: 12px 12px 0 0;
        }
        
        .stat-card.total { --card-color: #4f46e5; }
        .stat-card.pending { --card-color: #f59e0b; }
        .stat-card.confirmed { --card-color: #10b981; }
        .stat-card.completed { --card-color: #3b82f6; }
        .stat-card.canceled { --card-color: #ef4444; }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-card .icon {
            font-size: 1.8rem;
            margin-bottom: 0.75rem;
            opacity: 0.8;
        }
        
        .stat-card.total .icon { color: #4f46e5; }
        .stat-card.pending .icon { color: #f59e0b; }
        .stat-card.confirmed .icon { color: #10b981; }
        .stat-card.completed .icon { color: #3b82f6; }
        .stat-card.canceled .icon { color: #ef4444; }
        
        .stat-card h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0.5rem 0;
            color: #1f2937;
        }
        
        .stat-card p {
            color: #6b7280;
            font-weight: 600;
            margin: 0;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Content wrapper */
        .content-wrapper {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        /* Alerts */
        .alert {
            border-radius: 8px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
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
            text-align: center;
            padding: 3rem 2rem;
        }
        
        /* Filter section */
        .filter-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .form-select {
            border-radius: 8px;
            border: 2px solid rgba(79, 70, 229, 0.2);
            background: rgba(255,255,255,0.9);
            padding: 0.75rem 1rem;
            font-weight: 500;
            transition: border-color 0.2s ease;
            min-width: 200px;
        }
        
        .form-select:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            background: white;
        }
        
        .btn-new {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            box-shadow: 0 2px 10px rgba(79, 70, 229, 0.3);
        }
        
        .btn-new:hover {
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
        }
        
        /* Appointment cards */
        .appointment-card { 
            background: rgba(255,255,255,0.9);
            border-radius: 12px; 
            padding: 1.5rem; 
            margin-bottom: 1.5rem; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border-left: 4px solid;
            transition: transform 0.2s ease;
        }
        
        .appointment-card:hover {
            transform: translateY(-2px);
        }
        
        .appointment-card.status-pending { border-left-color: #f59e0b; }
        .appointment-card.status-confirmed { border-left-color: #10b981; }
        .appointment-card.status-completed { border-left-color: #3b82f6; }
        .appointment-card.status-canceled { border-left-color: #ef4444; }
        
        /* Status badges */
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 16px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending { 
            background: #fef3c7; 
            color: #92400e; 
        }
        .status-confirmed { 
            background: #d1fae5; 
            color: #065f46; 
        }
        .status-completed { 
            background: #dbeafe; 
            color: #1e40af; 
        }
        .status-canceled { 
            background: #fee2e2; 
            color: #991b1b; 
        }
        
        /* Doctor info */
        .doctor-avatar {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid rgba(255,255,255,0.8);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .doctor-info h6 {
            color: #1f2937;
            font-weight: 600;
            margin-bottom: 0.25rem;
            font-size: 1.1rem;
        }
        
        .specialization {
            color: #4f46e5;
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .appointment-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #4b5563;
        }
        
        .detail-item i {
            color: #6366f1;
            width: 16px;
            text-align: center;
        }
        
        .appointment-reason {
            background: rgba(79, 70, 229, 0.05);
            border: 1px solid rgba(79, 70, 229, 0.1);
            border-radius: 6px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #374151;
        }
        
        /* Buttons */
        .btn-action {
            border-radius: 6px;
            font-weight: 600;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .btn-danger {
            background: #ef4444;
            border: none;
            color: white;
        }
        
        .btn-danger:hover {
            color: white;
            background: #dc2626;
            transform: translateY(-1px);
        }
        
        /* Pagination */
        .pagination {
            background: rgba(255,255,255,0.6);
            padding: 1rem;
            border-radius: 8px;
        }
        
        .page-link {
            background: rgba(255,255,255,0.9);
            border: 1px solid rgba(79, 70, 229, 0.2);
            color: #4f46e5;
            border-radius: 6px;
            margin: 0 0.1rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .page-link:hover {
            background: #4f46e5;
            color: white;
        }
        
        .page-item.active .page-link {
            background: #4f46e5;
            border-color: #4f46e5;
            color: white;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }
        
        .empty-state h5 {
            color: #374151;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .empty-state p {
            color: #6b7280;
            margin-bottom: 1.5rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-container { 
                margin-top: 10px; 
                padding: 0 15px; 
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
            
            .page-header, .stats-container, .content-wrapper {
                padding: 1.5rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .stat-card {
                padding: 1.25rem 1rem;
            }
            
            .appointment-card {
                padding: 1.25rem;
            }
            
            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }
            
            .form-select {
                min-width: auto;
            }
            
            .appointment-details {
                grid-template-columns: 1fr;
            }
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
        
        /* Performance optimizations */
        * {
            box-sizing: border-box;
        }
        
        .stat-card,
        .appointment-card {
            will-change: transform;
        }
        
        /* Reduce motion for users who prefer it */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="main-container">
        <div class="container">
            <!-- Doctor Navigation (chỉ hiện với bác sĩ) set theo role 2 là bác sĩ -->
            <?php if (isset($_SESSION['role_id']) && in_array($_SESSION['role_id'], [2])): ?> 
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
                        <a href="doctor/schedule.php" class="nav-btn">
                            <i class="fas fa-calendar-alt"></i>
                            Lịch làm việc
                        </a>
                        <a href="appointments.php" class="nav-btn active">
                            <i class="fas fa-calendar-check"></i>
                            Lịch hẹn của tôi
                        </a>
                        <a href="doctor/clinic-registration.php" class="nav-btn">
                            <i class="fas fa-hospital"></i>
                            Nơi làm việc
                        </a>
                        <a href="doctor/appointment-view.php?id=1" class="nav-btn">
                            <i class="fas fa-eye"></i>
                            Xem lịch hẹn
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fas fa-calendar-check me-2"></i>Lịch hẹn của tôi</h1>
                <p class="subtitle">Quản lý và theo dõi các lịch hẹn khám bệnh của bạn</p>
            </div>
            
            <!-- Stats Container -->
            <div class="stats-container">
                <div class="stats-grid">
                    <div class="stat-card total">
                        <div class="icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h3><?= $stats['total'] ?></h3>
                        <p>Tổng số</p>
                    </div>
                    <div class="stat-card pending">
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3><?= $stats['pending'] ?></h3>
                        <p>Đang chờ</p>
                    </div>
                    <div class="stat-card confirmed">
                        <div class="icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3><?= $stats['confirmed'] ?></h3>
                        <p>Đã xác nhận</p>
                    </div>
                    <div class="stat-card completed">
                        <div class="icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <h3><?= $stats['completed'] ?></h3>
                        <p>Hoàn thành</p>
                    </div>
                    <div class="stat-card canceled">
                        <div class="icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <h3><?= $stats['canceled'] ?></h3>
                        <p>Đã hủy</p>
                    </div>
                </div>
            </div>
            
            <!-- Content Wrapper -->
            <div class="content-wrapper">
                <!-- Alerts -->
                <?php if ($msg): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($msg) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($err): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($err) ?>
                    </div>
                <?php endif; ?>
                
                <!-- Filter Section -->
                <div class="filter-section">
                    <div class="d-flex align-items-center gap-3">
                        <label for="statusFilter" class="form-label mb-0 fw-bold">Lọc theo trạng thái:</label>
                        <select id="statusFilter" class="form-select">
                            <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>Tất cả</option>
                            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Đang chờ</option>
                            <option value="confirmed" <?= $status_filter === 'confirmed' ? 'selected' : '' ?>>Đã xác nhận</option>
                            <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
                            <option value="canceled" <?= $status_filter === 'canceled' ? 'selected' : '' ?>>Đã hủy</option>
                        </select>
                    </div>
                    <a href="book-appointment.php" class="btn-new">
                        <i class="fas fa-plus"></i>
                        Đặt lịch mới
                    </a>
                </div>
                
                <!-- Appointments List -->
                <?php if (empty($appointments)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <h5>Chưa có lịch hẹn nào</h5>
                        <p>Bạn chưa có lịch hẹn nào. Hãy đặt lịch khám bệnh ngay!</p>
                        <a href="book-appointment.php" class="btn-new">
                            <i class="fas fa-plus"></i>
                            Đặt lịch ngay
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($appointments as $appointment): ?>
                        <div class="appointment-card status-<?= $appointment['status'] ?>">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h6 class="mb-1">Lịch hẹn #<?= $appointment['appointment_id'] ?></h6>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        <?= date('d/m/Y H:i', strtotime($appointment['appointment_time'])) ?>
                                    </small>
                                </div>
                                <span class="status-badge status-<?= $appointment['status'] ?>">
                                    <?php
                                    $status_labels = [
                                        'pending' => 'Đang chờ',
                                        'confirmed' => 'Đã xác nhận', 
                                        'completed' => 'Hoàn thành',
                                        'canceled' => 'Đã hủy'
                                    ];
                                    echo $status_labels[$appointment['status']] ?? $appointment['status'];
                                    ?>
                                </span>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-2 text-center">
                                    <img src="assets/images/default-doctor.jpg" 
                                         data-src="<?= !empty($appointment['doctor_image']) ? $appointment['doctor_image'] : 'assets/images/default-doctor.jpg' ?>" 
                                         alt="Doctor Avatar" class="doctor-avatar" loading="lazy">
                                </div>
                                <div class="col-md-10">
                                    <div class="doctor-info">
                                        <h6><?= htmlspecialchars($appointment['doctor_name']) ?></h6>
                                        <div class="specialization"><?= htmlspecialchars($appointment['specialization']) ?></div>
                                        
                                        <div class="appointment-details">
                                            <div class="detail-item">
                                                <i class="fas fa-hospital"></i>
                                                <span><?= htmlspecialchars($appointment['clinic_name']) ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <span><?= htmlspecialchars($appointment['clinic_address']) ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <i class="fas fa-phone"></i>
                                                <span><?= htmlspecialchars($appointment['clinic_phone']) ?></span>
                                            </div>
                                        </div>
                                        
                                        <?php if ($appointment['reason']): ?>
                                            <div class="appointment-reason">
                                                <i class="fas fa-notes-medical me-2"></i>
                                                <strong>Lý do khám:</strong> <?= htmlspecialchars($appointment['reason']) ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($appointment['status'], ['pending', 'confirmed'])): ?>
                                            <div class="mt-3">
                                                <button class="btn btn-action btn-danger" onclick="cancelAppointment(<?= $appointment['appointment_id'] ?>)">
                                                    <i class="fas fa-times me-1"></i>
                                                    Hủy lịch hẹn
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= $status_filter ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&status=<?= $status_filter ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= $status_filter ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Load JavaScript asynchronously -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    
    <script>
        // Optimized JavaScript
        (function() {
            'use strict';
            
            // Status filter change handler
            const statusFilter = document.getElementById('statusFilter');
            if (statusFilter) {
                statusFilter.addEventListener('change', function() {
                    const status = this.value;
                    const url = new URL(window.location);
                    url.searchParams.set('status', status);
                    url.searchParams.set('page', '1');
                    window.location.href = url.toString();
                });
            }

            // Cancel appointment function
            window.cancelAppointment = function(appointmentId) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Xác nhận hủy lịch hẹn',
                        text: 'Bạn có chắc chắn muốn hủy lịch hẹn này không?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Đồng ý hủy',
                        cancelButtonText: 'Không hủy',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Show loading
                            Swal.fire({
                                title: 'Đang xử lý...',
                                text: 'Vui lòng chờ trong giây lát',
                                allowOutsideClick: false,
                                showConfirmButton: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            // Submit form
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.innerHTML = `
                                <input type="hidden" name="cancel_appointment" value="1">
                                <input type="hidden" name="appointment_id" value="${appointmentId}">
                            `;
                            document.body.appendChild(form);
                            form.submit();
                        }
                    });
                } else {
                    // Fallback if SweetAlert is not loaded
                    if (confirm('Bạn có chắc chắn muốn hủy lịch hẹn này không?')) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.innerHTML = `
                            <input type="hidden" name="cancel_appointment" value="1">
                            <input type="hidden" name="appointment_id" value="${appointmentId}">
                        `;
                        document.body.appendChild(form);
                        form.submit();
                    }
                }
            };

            // Auto-hide alerts after 5 seconds
            document.addEventListener('DOMContentLoaded', function() {
                const alerts = document.querySelectorAll('.alert');
                if (alerts.length > 0) {
                    setTimeout(() => {
                        alerts.forEach(alert => {
                            alert.style.opacity = '0';
                            alert.style.transition = 'opacity 0.3s ease';
                            setTimeout(() => {
                                if (alert.parentNode) {
                                    alert.parentNode.removeChild(alert);
                                }
                            }, 300);
                        });
                    }, 4000);
                }
            });

            // Lazy load doctor images
            const lazyLoadImages = function() {
                const images = document.querySelectorAll('.doctor-avatar[data-src]');
                if (images.length === 0) return;
                
                if ('IntersectionObserver' in window) {
                    const imageObserver = new IntersectionObserver((entries, observer) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                const img = entry.target;
                                img.src = img.dataset.src;
                                img.removeAttribute('data-src');
                                img.classList.add('loaded');
                                observer.unobserve(img);
                            }
                        });
                    });

                    images.forEach(img => imageObserver.observe(img));
                } else {
                    // Fallback for browsers without IntersectionObserver
                    images.forEach(img => {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        img.classList.add('loaded');
                    });
                }
            };

            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', lazyLoadImages);
            } else {
                lazyLoadImages();
            }
        })();
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html> 