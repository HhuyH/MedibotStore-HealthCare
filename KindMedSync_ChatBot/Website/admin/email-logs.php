<?php
session_start();
include '../includes/db.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../login.php');
    exit;
}

$message = '';
$error = '';

// Xử lý xóa log
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'clear_logs') {
        $conn->query("DELETE FROM email_logs");
        $message = 'Đã xóa tất cả lịch sử email!';
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Lấy tổng số log
$total_result = $conn->query("SELECT COUNT(*) as total FROM email_logs");
$total_logs = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_logs / $per_page);

// Lấy logs với pagination
$logs_query = "SELECT * FROM email_logs ORDER BY sent_at DESC LIMIT $per_page OFFSET $offset";
$logs_result = $conn->query($logs_query);

// Thống kê
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as sent,
    SUM(CASE WHEN status = 'failed' OR status = 'error' THEN 1 ELSE 0 END) as failed,
    DATE(sent_at) as date
    FROM email_logs 
    GROUP BY DATE(sent_at) 
    ORDER BY date DESC 
    LIMIT 7";
$stats_result = $conn->query($stats_query);

// Tạo bảng email_logs nếu chưa có
$conn->query("CREATE TABLE IF NOT EXISTS email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT,
    status ENUM('success', 'failed', 'error') DEFAULT 'failed',
    error_message TEXT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử Email - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
    <link href="assets/css/sidebar.css" rel="stylesheet">
    <link href="assets/css/header.css" rel="stylesheet">
        <style>
        /* Simple Statistics Cards */
        .stats-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            transition: box-shadow 0.2s ease;
            overflow: hidden;
        }
        
        .stats-card:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .stats-card .card-body {
            padding: 1.5rem;
            text-align: center;
        }
        
        .stats-card i {
            margin-bottom: 0.75rem;
            display: block;
        }
        
        .stats-card .card-title {
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stats-card h3 {
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0;
        }
        
        /* Simple table styling */
        .table th {
            background-color: #f8f9fa;
            font-weight: 500;
            border-top: none;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Simple button */
        .btn-info {
            border-radius: 4px;
            padding: 0.25rem 0.5rem;
        }
    </style>

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
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h1 class="page-title">
                                    <i class="fas fa-envelope me-2"></i>
                                    Lịch sử Email
                                </h1>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                        <li class="breadcrumb-item active">Lịch sử Email</li>
                                    </ol>
                                </nav>
                            </div>
                            <div class="page-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="clear_logs">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc muốn xóa tất cả lịch sử email?')">
                                        <i class="fas fa-trash me-1"></i>Xóa tất cả
                                    </button>
                                </form>
                            </div>
                        </div>
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

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card text-center">
                                <div class="card-body">
                                    <i class="fas fa-envelope fa-2x text-primary"></i>
                                    <h6 class="card-title">TỔNG EMAIL</h6>
                                    <h3 class="text-primary"><?= $total_logs ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card text-center">
                                <div class="card-body">
                                    <i class="fas fa-check-circle fa-2x text-success"></i>
                                    <h6 class="card-title">GỬI THÀNH CÔNG</h6>
                                    <?php 
                                    $success_count = 0;
                                    $success_result = $conn->query("SELECT COUNT(*) as count FROM email_logs WHERE status = 'success'");
                                    if ($success_result) {
                                        $success_count = $success_result->fetch_assoc()['count'];
                                    }
                                    ?>
                                    <h3 class="text-success"><?= $success_count ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card text-center">
                                <div class="card-body">
                                    <i class="fas fa-times-circle fa-2x text-danger"></i>
                                    <h6 class="card-title">GỬI THẤT BẠI</h6>
                                    <h3 class="text-danger"><?= $total_logs - $success_count ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card text-center">
                                <div class="card-body">
                                    <i class="fas fa-percentage fa-2x text-info"></i>
                                    <h6 class="card-title">TỶ LỆ THÀNH CÔNG</h6>
                                    <h3 class="text-info">
                                        <?= $total_logs > 0 ? round(($success_count / $total_logs) * 100, 1) : 0 ?>%
                                    </h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Email Logs Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-history me-2"></i>
                                Lịch sử gửi email
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($logs_result && $logs_result->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Email nhận</th>
                                                <th>Chủ đề</th>
                                                <th>Trạng thái</th>
                                                <th>Thời gian</th>
                                                <th>Hành động</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($log = $logs_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?= $log['id'] ?></td>
                                                    <td><?= htmlspecialchars($log['recipient']) ?></td>
                                                    <td><?= htmlspecialchars(substr($log['subject'], 0, 50)) ?>...</td>
                                                    <td>
                                                        <?php if ($log['status'] === 'success'): ?>
                                                            <span class="badge bg-success">Thành công</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Thất bại</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= date('d/m/Y H:i', strtotime($log['sent_at'])) ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-info" onclick="viewEmailDetails(<?= $log['id'] ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                    <nav>
                                        <ul class="pagination justify-content-center">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $page - 1 ?>">Trước</a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $page + 1 ?>">Sau</a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                <?php endif; ?>
                                                         <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">Chưa có lịch sử email</h5>
                                    <p class="text-muted">Khi hệ thống gửi email, lịch sử sẽ hiển thị tại đây</p>
                                </div>
                             <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Email Details Modal -->
    <div class="modal fade" id="emailDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chi tiết Email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="emailDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
    <script>
        function viewEmailDetails(id) {
            fetch(`ajax/get-email-details.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('emailDetailsContent').innerHTML = `
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Email nhận:</strong><br>
                                    <p>${data.email.recipient}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Trạng thái:</strong><br>
                                    <span class="badge bg-${data.email.status === 'success' ? 'success' : 'danger'}">
                                        ${data.email.status === 'success' ? 'Thành công' : 'Thất bại'}
                                    </span>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <strong>Chủ đề:</strong><br>
                                    <p>${data.email.subject}</p>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <strong>Nội dung:</strong><br>
                                    <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                                        ${data.email.body || 'Không có nội dung'}
                                    </div>
                                </div>
                            </div>
                            ${data.email.error_message ? `
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <strong>Lỗi:</strong><br>
                                        <div class="alert alert-danger">${data.email.error_message}</div>
                                    </div>
                                </div>
                            ` : ''}
                            <div class="row mt-3">
                                <div class="col-12">
                                    <strong>Thời gian:</strong><br>
                                    <p>${new Date(data.email.sent_at).toLocaleString('vi-VN')}</p>
                                </div>
                            </div>
                        `;
                        new bootstrap.Modal(document.getElementById('emailDetailsModal')).show();
                    } else {
                        alert('Không thể tải chi tiết email');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra');
                });
        }
    </script>
</body>
</html> 