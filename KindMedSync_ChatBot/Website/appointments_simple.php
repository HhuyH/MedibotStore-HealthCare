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
if (!$stmt) {
    $err = "SQL Prepare Error: " . $conn->error;
    $appointments = [];
} else {
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) {
        $err = "SQL Execute Error: " . $stmt->error;
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
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container { 
            margin-top: 20px; 
            position: relative;
            z-index: 1;
        }
        
        /* Header styling */
        h1 {
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            text-align: center;
            margin-bottom: 30px;
            font-weight: 600;
        }
        
        /* Stats cards */
        .stat-card { 
            background: linear-gradient(145deg, rgba(255,255,255,0.95), rgba(248,250,252,0.9));
            border-radius: 15px; 
            padding: 25px 20px; 
            text-align: center; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--card-color);
        }
        
        .stat-card:nth-child(1) { --card-color: linear-gradient(90deg, #4f46e5, #6366f1); }
        .stat-card:nth-child(2) { --card-color: linear-gradient(90deg, #f59e0b, #fbbf24); }
        .stat-card:nth-child(3) { --card-color: linear-gradient(90deg, #10b981, #34d399); }
        .stat-card:nth-child(4) { --card-color: linear-gradient(90deg, #3b82f6, #60a5fa); }
        .stat-card:nth-child(5) { --card-color: linear-gradient(90deg, #ef4444, #f87171); }
        
        .stat-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 12px 35px rgba(0,0,0,0.2);
        }
        
        .stat-card h3 {
            font-size: 2.2rem;
            font-weight: 700;
            margin: 10px 0;
            background: linear-gradient(135deg, #1f2937, #374151);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-card p {
            color: #6b7280;
            font-weight: 500;
            margin: 0;
            font-size: 0.9rem;
        }
        
        /* Alerts */
        .alert {
            border-radius: 12px;
            border: none;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            color: #1e40af;
            border-left: 4px solid #3b82f6;
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
            padding: 40px;
        }
        
        /* Appointment cards */
        .appointment-card { 
            background: rgba(255,255,255,0.95);
            border-radius: 15px; 
            padding: 25px; 
            margin-bottom: 20px; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
            border-left: 5px solid;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .appointment-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(180deg, transparent, rgba(0,0,0,0.1), transparent);
        }
        
        .appointment-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.15);
        }
        
        .appointment-card.status-pending { border-left-color: #f59e0b; }
        .appointment-card.status-confirmed { border-left-color: #10b981; }
        .appointment-card.status-completed { border-left-color: #3b82f6; }
        .appointment-card.status-canceled { border-left-color: #ef4444; }
        
        /* Status badges */
        .status-badge {
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .status-pending { 
            background: linear-gradient(135deg, #fef3c7, #fde68a); 
            color: #92400e; 
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
        }
        .status-confirmed { 
            background: linear-gradient(135deg, #d1fae5, #a7f3d0); 
            color: #065f46; 
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }
        .status-completed { 
            background: linear-gradient(135deg, #dbeafe, #bfdbfe); 
            color: #1e40af; 
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }
        .status-canceled { 
            background: linear-gradient(135deg, #fee2e2, #fecaca); 
            color: #991b1b; 
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }
        
        /* Form elements */
        .form-select {
            border-radius: 10px;
            border: 2px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(10px);
            padding: 12px 16px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .form-select:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            background: white;
        }
        
        /* Buttons */
        .btn {
            border-radius: 10px;
            font-weight: 600;
            padding: 12px 24px;
            transition: all 0.3s ease;
            text-transform: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            border: none;
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
            background: linear-gradient(135deg, #4338ca, #4f46e5);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #f87171);
            border: none;
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
            background: linear-gradient(135deg, #dc2626, #ef4444);
        }
        
        /* Doctor avatar */
        .img-fluid {
            border: 3px solid rgba(255,255,255,0.8);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .img-fluid:hover {
            transform: scale(1.05);
        }
        
        /* Doctor info */
        h6 {
            color: #1f2937;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .text-muted {
            color: #6b7280 !important;
            font-weight: 500;
        }
        
        small {
            color: #4b5563;
            font-weight: 500;
        }
        
        small i {
            color: #6366f1;
            margin-right: 8px;
            width: 16px;
            text-align: center;
        }
        
        /* Pagination */
        .pagination {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }
        
        .page-link {
            background: rgba(255,255,255,0.9);
            border: 1px solid rgba(255,255,255,0.3);
            color: #4f46e5;
            border-radius: 8px;
            margin: 0 3px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .page-link:hover {
            background: #4f46e5;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
        
        .page-item.active .page-link {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            border-color: #4f46e5;
            color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container { margin-top: 10px; padding: 15px; }
            .stat-card { margin-bottom: 15px; }
            .appointment-card { padding: 20px; margin-bottom: 15px; }
            h1 { font-size: 1.8rem; }
        }
        
        /* Subtle animations */
        .appointment-card, .stat-card {
            animation: fadeInUp 0.6s ease-out forwards;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
        .stat-card:nth-child(5) { animation-delay: 0.5s; }
        .stat-card:nth-child(6) { animation-delay: 0.6s; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1><i class="fas fa-calendar-check"></i> Lịch hẹn của tôi</h1>
        
        <!-- Debug Info -->
        <div class="alert alert-info">
            <strong>Debug:</strong> User ID: <?= $user_id ?> | 
            Status Filter: <?= $status_filter ?> | 
            Appointments Count: <?= count($appointments) ?> |
            SQL Error: <?= $err ?: 'None' ?>
        </div>
        
        <!-- Stats -->
        <div class="row">
            <div class="col-md-2">
                <div class="stat-card">
                    <h3><?= $stats['total'] ?></h3>
                    <p>Tổng số</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <h3><?= $stats['pending'] ?></h3>
                    <p>Đang chờ</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <h3><?= $stats['confirmed'] ?></h3>
                    <p>Đã xác nhận</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <h3><?= $stats['completed'] ?></h3>
                    <p>Hoàn thành</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <h3><?= $stats['canceled'] ?></h3>
                    <p>Đã hủy</p>
                </div>
            </div>
            <div class="col-md-2">
                <a href="book-appointment.php" class="btn btn-primary w-100">
                    <i class="fas fa-plus"></i> Đặt lịch mới
                </a>
            </div>
        </div>
        
        <!-- Alerts -->
        <?php if ($msg): ?>
            <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        
        <?php if ($err): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>
        
        <!-- Filter -->
        <div class="row mb-3">
            <div class="col-md-6">
                <select id="statusFilter" class="form-select">
                    <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>Tất cả</option>
                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Đang chờ</option>
                    <option value="confirmed" <?= $status_filter === 'confirmed' ? 'selected' : '' ?>>Đã xác nhận</option>
                    <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
                    <option value="canceled" <?= $status_filter === 'canceled' ? 'selected' : '' ?>>Đã hủy</option>
                </select>
            </div>
        </div>
        
        <!-- Appointments List -->
        <?php if (empty($appointments)): ?>
            <div class="alert alert-warning text-center">
                <i class="fas fa-calendar-times fa-3x mb-3"></i>
                <h5>Chưa có lịch hẹn nào</h5>
                <p>Bạn chưa có lịch hẹn nào.</p>
                <a href="book-appointment.php" class="btn btn-primary">Đặt lịch ngay</a>
            </div>
        <?php else: ?>
            <?php foreach ($appointments as $appointment): ?>
                <div class="appointment-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6>Lịch hẹn #<?= $appointment['appointment_id'] ?></h6>
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
                        <div class="col-md-2">
                            <img src="<?= $appointment['doctor_image'] ?: '/assets/images/default-doctor.jpg' ?>" 
                                 class="img-fluid rounded-circle" style="width: 60px; height: 60px; object-fit: cover;">
                        </div>
                        <div class="col-md-10">
                            <h6><?= htmlspecialchars($appointment['doctor_name'] ?: 'Chưa xác định') ?></h6>
                            <p class="text-muted mb-1"><?= htmlspecialchars($appointment['specialization'] ?: 'Chưa xác định') ?></p>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <small>
                                        <i class="fas fa-clock"></i> 
                                        <?= date('d/m/Y H:i', strtotime($appointment['appointment_time'])) ?>
                                    </small><br>
                                    <small>
                                        <i class="fas fa-hospital"></i> 
                                        <?= htmlspecialchars($appointment['clinic_name'] ?: 'Chưa xác định') ?>
                                    </small>
                                </div>
                                <div class="col-md-6">
                                    <small>
                                        <i class="fas fa-map-marker-alt"></i> 
                                        <?= htmlspecialchars($appointment['clinic_address'] ?: 'Chưa cập nhật') ?>
                                    </small><br>
                                    <small>
                                        <i class="fas fa-phone"></i> 
                                        <?= htmlspecialchars($appointment['clinic_phone'] ?: 'Chưa cập nhật') ?>
                                    </small>
                                </div>
                            </div>
                            
                            <?php if ($appointment['reason']): ?>
                                <div class="mt-2">
                                    <strong>Lý do:</strong> <?= htmlspecialchars($appointment['reason']) ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (in_array($appointment['status'], ['pending', 'confirmed'])): ?>
                                <div class="mt-3">
                                    <button class="btn btn-sm btn-danger" onclick="cancelAppointment(<?= $appointment['appointment_id'] ?>)">
                                        <i class="fas fa-times"></i> Hủy lịch
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&status=<?= $status_filter ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('statusFilter').addEventListener('change', function() {
            const status = this.value;
            const url = new URL(window.location);
            url.searchParams.set('status', status);
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        });

        function cancelAppointment(appointmentId) {
            if (confirm('Bạn có chắc chắn muốn hủy lịch hẹn này?')) {
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
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html> 