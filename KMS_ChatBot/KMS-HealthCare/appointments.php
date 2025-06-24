<?php
include 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$msg = $err = '';

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

// Lấy danh sách lịch hẹn với phân trang
$sql = "SELECT a.*, 
               ui.full_name as doctor_name, 
               s.name as specialization, 
               ui.profile_picture as doctor_image,
               c.name as clinic_name, 
               c.address as clinic_address, 
               c.phone as clinic_phone
        FROM appointments a
        LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
        LEFT JOIN users u ON d.user_id = u.user_id
        LEFT JOIN users_info ui ON u.user_id = ui.user_id
        LEFT JOIN specialties s ON d.specialty_id = s.specialty_id
        LEFT JOIN clinics c ON a.clinic_id = c.clinic_id
        $where_clause
        ORDER BY a.appointment_time DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$params[] = $limit;
$params[] = $offset;
$types .= "ii";
$stmt->bind_param($types, ...$params);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

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
    
    <!-- CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.3);
            --text-primary: #2d3748;
            --text-secondary: #4a5568;
            --text-muted: #718096;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--primary-gradient);
            background-attachment: fixed;
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            padding-top: 120px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 15% 85%, rgba(102, 126, 234, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 85% 15%, rgba(245, 101, 101, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 50% 50%, rgba(79, 172, 254, 0.08) 0%, transparent 50%),
                linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, transparent 100%);
            pointer-events: none;
            z-index: -1;
        }

        .page-container {
            min-height: calc(100vh - 120px);
            padding: 3rem 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .main-content {
            background: var(--glass-bg);
            backdrop-filter: blur(40px);
            border-radius: 32px;
            padding: 0;
            box-shadow: 
                0 40px 80px rgba(0, 0, 0, 0.08),
                0 0 0 1px var(--glass-border),
                inset 0 1px 0 rgba(255, 255, 255, 0.9);
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .main-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
            border-radius: 32px 32px 0 0;
        }

        .content-inner {
            padding: 3rem;
        }

        .page-header {
            text-align: center;
            padding: 4rem 3rem;
            background: 
                linear-gradient(135deg, rgba(102, 126, 234, 0.03), rgba(118, 75, 162, 0.03)),
                linear-gradient(45deg, rgba(255, 255, 255, 0.8), rgba(248, 250, 252, 0.8));
            border-radius: 32px 32px 0 0;
            margin: 0 0 3rem 0;
            position: relative;
        }

        .page-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 2px;
            background: var(--primary-gradient);
            border-radius: 2px;
        }

        .page-title {
            font-size: 3.5rem;
            font-weight: 900;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
            letter-spacing: -0.03em;
            line-height: 1.2;
        }

        .page-subtitle {
            color: var(--text-muted);
            font-size: 1.3rem;
            font-weight: 500;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
            padding: 0 3rem;
        }

        .stat-card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.95), rgba(248, 250, 252, 0.9));
            border-radius: 24px;
            padding: 2.5rem;
            text-align: center;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 
                0 10px 40px rgba(0, 0, 0, 0.06),
                inset 0 1px 0 rgba(255, 255, 255, 0.9);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--card-gradient);
            border-radius: 24px 24px 0 0;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: -100%;
            left: -100%;
            width: 300%;
            height: 300%;
            background: conic-gradient(from 0deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: all 0.8s ease;
            opacity: 0;
        }

        .stat-card:hover {
            transform: translateY(-12px) scale(1.03);
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(255, 255, 255, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 1);
        }

        .stat-card:hover::after {
            opacity: 1;
            animation: rotate 2s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .stat-card.total { --card-gradient: var(--primary-gradient); }
        .stat-card.pending { --card-gradient: linear-gradient(135deg, #f6ad55, #ed8936); }
        .stat-card.confirmed { --card-gradient: linear-gradient(135deg, #48bb78, #38a169); }
        .stat-card.completed { --card-gradient: var(--success-gradient); }
        .stat-card.canceled { --card-gradient: var(--secondary-gradient); }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            opacity: 0.9;
            background: var(--card-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
            z-index: 2;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
            line-height: 1;
            position: relative;
            z-index: 2;
        }

        .stat-label {
            color: var(--text-muted);
            font-weight: 600;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            z-index: 2;
        }

        /* Filters & Actions */
        .filters-section {
            background: #f8fafc;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn-book {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-book:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        /* Appointment Cards */
        .appointments-grid {
            display: grid;
            gap: 1.5rem;
        }

        .appointment-card {
            background: linear-gradient(135deg, #fff 0%, rgba(248, 250, 252, 0.9) 100%);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.06),
                0 0 0 1px rgba(255, 255, 255, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.8);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(20px);
        }

        .appointment-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.02), rgba(118, 75, 162, 0.02));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .appointment-card:hover {
            transform: translateY(-6px) scale(1.01);
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(255, 255, 255, 0.6),
                inset 0 1px 0 rgba(255, 255, 255, 1);
        }

        .appointment-card:hover::before {
            opacity: 1;
        }

        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .appointment-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending { background: rgba(246, 173, 85, 0.1); color: #d69e2e; }
        .status-confirmed { background: rgba(72, 187, 120, 0.1); color: #2f855a; }
        .status-completed { background: rgba(66, 153, 225, 0.1); color: #2b6cb0; }
        .status-canceled { background: rgba(245, 101, 101, 0.1); color: #c53030; }

        .appointment-body {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .doctor-avatar {
            width: 80px;
            height: 80px;
            border-radius: 16px;
            object-fit: cover;
            border: 3px solid #f7fafc;
        }

        .appointment-details h4 {
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .appointment-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #4a5568;
            font-size: 0.9rem;
        }

        .info-item i {
            color: #667eea;
            width: 16px;
        }

        .appointment-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }

        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-cancel {
            background: rgba(245, 101, 101, 0.1);
            color: #c53030;
        }

        .btn-cancel:hover {
            background: rgba(245, 101, 101, 0.2);
        }

        .btn-reschedule {
            background: rgba(66, 153, 225, 0.1);
            color: #2b6cb0;
        }

        .btn-reschedule:hover {
            background: rgba(66, 153, 225, 0.2);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #718096;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Pagination */
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 3rem;
        }

        .pagination .page-link {
            border-radius: 8px;
            margin: 0 0.2rem;
            border: 1px solid #e2e8f0;
            color: #4a5568;
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-color: #667eea;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .container {
                padding: 0 1.5rem;
            }
            
            .page-header {
                padding: 3rem 2rem;
            }
            
            .content-inner {
                padding: 2rem;
            }
            
            .stats-container {
                padding: 0 2rem;
                grid-template-columns: repeat(3, 1fr);
                gap: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            body {
                padding-top: 100px;
            }
            
            .page-container {
                padding: 2rem 0;
                min-height: calc(100vh - 100px);
            }
            
            .page-title { 
                font-size: 2.5rem; 
            }
            
            .page-subtitle {
                font-size: 1.1rem;
            }
            
            .stats-container { 
                grid-template-columns: repeat(2, 1fr);
                padding: 0 1rem;
            }
            
            .stat-card {
                padding: 2rem;
            }
            
            .stat-icon {
                font-size: 2.5rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .filters-section { 
                flex-direction: column; 
                text-align: center; 
            }
            
            .appointment-body { 
                grid-template-columns: 1fr; 
                text-align: center; 
            }
            
            .appointment-info { 
                grid-template-columns: 1fr; 
            }
            
            .appointment-actions { 
                justify-content: center; 
            }
            
            .main-content {
                border-radius: 24px;
                margin-bottom: 2rem;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 2rem;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                padding: 2rem 1.5rem;
            }
            
            .content-inner {
                padding: 1.5rem;
            }
        }

        /* Alerts */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(72, 187, 120, 0.1), rgba(72, 187, 120, 0.05));
            color: #2f855a;
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(245, 101, 101, 0.1), rgba(245, 101, 101, 0.05));
            color: #c53030;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="page-container">
        <div class="container">
            <div class="main-content">
                <!-- Page Header -->
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-calendar-heart"></i>
                        Lịch hẹn của tôi
                    </h1>
                    <p class="page-subtitle">Quản lý và theo dõi các lịch hẹn khám bệnh một cách dễ dàng và tiện lợi</p>
                </div>

                <div class="content-inner">

                <!-- Alerts -->
                <?php if ($msg): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= htmlspecialchars($msg) ?>
                    </div>
                <?php endif; ?>

                <?php if ($err): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?= htmlspecialchars($err) ?>
                    </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="stats-container">
                    <div class="stat-card total">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check" style="color: #667eea;"></i>
                        </div>
                        <div class="stat-number"><?= $stats['total'] ?></div>
                        <div class="stat-label">Tổng lịch hẹn</div>
                    </div>
                    
                    <div class="stat-card pending">
                        <div class="stat-icon">
                            <i class="fas fa-clock" style="color: #ed8936;"></i>
                        </div>
                        <div class="stat-number"><?= $stats['pending'] ?></div>
                        <div class="stat-label">Đang chờ</div>
                    </div>
                    
                    <div class="stat-card confirmed">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle" style="color: #38a169;"></i>
                        </div>
                        <div class="stat-number"><?= $stats['confirmed'] ?></div>
                        <div class="stat-label">Đã xác nhận</div>
                    </div>
                    
                    <div class="stat-card completed">
                        <div class="stat-icon">
                            <i class="fas fa-user-check" style="color: #3182ce;"></i>
                        </div>
                        <div class="stat-number"><?= $stats['completed'] ?></div>
                        <div class="stat-label">Hoàn thành</div>
                    </div>
                    
                    <div class="stat-card canceled">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle" style="color: #e53e3e;"></i>
                        </div>
                        <div class="stat-number"><?= $stats['canceled'] ?></div>
                        <div class="stat-label">Đã hủy</div>
                    </div>
                </div>

                <!-- Filters & Actions -->
                <div class="filters-section">
                    <div class="filter-group">
                        <label for="statusFilter" class="form-label fw-semibold">Lọc theo trạng thái:</label>
                        <select id="statusFilter" class="form-select" style="width: auto;">
                            <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>Tất cả</option>
                            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Đang chờ</option>
                            <option value="confirmed" <?= $status_filter === 'confirmed' ? 'selected' : '' ?>>Đã xác nhận</option>
                            <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
                            <option value="canceled" <?= $status_filter === 'canceled' ? 'selected' : '' ?>>Đã hủy</option>
                        </select>
                    </div>
                    
                    <a href="book-appointment.php" class="btn-book">
                        <i class="fas fa-plus"></i>
                        Đặt lịch mới
                    </a>
                </div>

                <!-- Appointments List -->
                <div class="appointments-grid">
                    <?php if (empty($appointments)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h4>Chưa có lịch hẹn nào</h4>
                            <p>Bạn chưa có lịch hẹn nào. Hãy đặt lịch khám bệnh ngay!</p>
                            <a href="book-appointment.php" class="btn-book mt-3">
                                <i class="fas fa-plus"></i>
                                Đặt lịch ngay
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($appointments as $appointment): ?>
                            <div class="appointment-card">
                                <div class="appointment-header">
                                    <div class="appointment-id">
                                        <small class="text-muted">#<?= $appointment['appointment_id'] ?></small>
                                    </div>
                                    <span class="appointment-status status-<?= $appointment['status'] ?>">
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

                                <div class="appointment-body">
                                    <img src="<?= $appointment['doctor_image'] ?: '/assets/images/default-doctor.png' ?>" 
                                         alt="Doctor" class="doctor-avatar">
                                    <div class="appointment-details">
                                        <h4><?= htmlspecialchars($appointment['doctor_name']) ?></h4>
                                        <p class="text-muted mb-2"><?= htmlspecialchars($appointment['specialization']) ?></p>
                                        
                                        <div class="appointment-info">
                                            <div class="info-item">
                                                <i class="fas fa-clock"></i>
                                                <span><?= date('d/m/Y H:i', strtotime($appointment['appointment_time'])) ?></span>
                                            </div>
                                            <div class="info-item">
                                                <i class="fas fa-hospital"></i>
                                                <span><?= htmlspecialchars($appointment['clinic_name']) ?></span>
                                            </div>
                                            <div class="info-item">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <span><?= htmlspecialchars($appointment['clinic_address']) ?></span>
                                            </div>
                                            <div class="info-item">
                                                <i class="fas fa-phone"></i>
                                                <span><?= htmlspecialchars($appointment['clinic_phone']) ?></span>
                                            </div>
                                        </div>

                                        <?php if ($appointment['reason']): ?>
                                            <div class="info-item">
                                                <i class="fas fa-notes-medical"></i>
                                                <span><?= htmlspecialchars($appointment['reason']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="appointment-actions">
                                    <?php if (in_array($appointment['status'], ['pending', 'confirmed'])): ?>
                                        <button class="btn-action btn-reschedule" onclick="rescheduleAppointment(<?= $appointment['appointment_id'] ?>)">
                                            <i class="fas fa-edit"></i>
                                            Đổi lịch
                                        </button>
                                        <button class="btn-action btn-cancel" onclick="cancelAppointment(<?= $appointment['appointment_id'] ?>)">
                                            <i class="fas fa-times"></i>
                                            Hủy lịch
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination-container">
                        <nav>
                            <ul class="pagination">
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
                    </div>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Status filter change handler
        document.getElementById('statusFilter').addEventListener('change', function() {
            const status = this.value;
            const url = new URL(window.location);
            url.searchParams.set('status', status);
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        });

        // Cancel appointment function
        function cancelAppointment(appointmentId) {
            Swal.fire({
                title: 'Xác nhận hủy lịch hẹn?',
                text: 'Bạn có chắc chắn muốn hủy lịch hẹn này?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e53e3e',
                cancelButtonColor: '#718096',
                confirmButtonText: 'Đồng ý hủy',
                cancelButtonText: 'Không'
            }).then((result) => {
                if (result.isConfirmed) {
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
        }

        // Reschedule appointment function
        function rescheduleAppointment(appointmentId) {
            Swal.fire({
                title: 'Đổi lịch hẹn',
                text: 'Tính năng đổi lịch sẽ được cập nhật sớm!',
                icon: 'info',
                confirmButtonText: 'Đã hiểu'
            });
        }

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
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html> 