<?php
session_start();
require_once '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../login.php');
    exit();
}

$success_messages = [];
$error_messages = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Read SQL file
    $sql_file = 'setup_database_real.sql';
    if (file_exists($sql_file)) {
        $sql_content = file_get_contents($sql_file);
        
        // Split SQL statements by semicolon and filter out comments
        $statements = array_filter(array_map('trim', explode(';', $sql_content)));
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement) && !preg_match('/^\/\*/', $statement)) {
                try {
                    $conn->query($statement);
                    if (preg_match('/CREATE TABLE.*`?(\w+)`?/', $statement, $matches)) {
                        $success_messages[] = "Tạo bảng {$matches[1]} thành công";
                    } elseif (preg_match('/INSERT.*INTO.*`?(\w+)`?/', $statement, $matches)) {
                        $success_messages[] = "Thêm dữ liệu mẫu vào bảng {$matches[1]} thành công";
                    } elseif (preg_match('/ALTER TABLE.*`?(\w+)`?/', $statement, $matches)) {
                        $success_messages[] = "Cập nhật cấu trúc bảng {$matches[1]} thành công";
                    }
                } catch (Exception $e) {
                    // Bỏ qua lỗi duplicate table/data
                    if (!strpos($e->getMessage(), 'already exists') && !strpos($e->getMessage(), 'Duplicate entry')) {
                        $error_messages[] = "Lỗi: " . $e->getMessage();
                    }
                }
            }
        }
        
        if (empty($error_messages)) {
            $success_messages[] = "Thiết lập database hoàn tất!";
        }
    } else {
        $error_messages[] = "Không tìm thấy file setup_database_real.sql";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thiết lập Database - MediSync Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-database me-2"></i>
                            Thiết lập Database MediSync
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($success_messages)): ?>
                            <div class="alert alert-success">
                                <h6><i class="fas fa-check-circle me-2"></i>Thành công:</h6>
                                <ul class="mb-0">
                                    <?php foreach ($success_messages as $message): ?>
                                        <li><?= htmlspecialchars($message) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($error_messages)): ?>
                            <div class="alert alert-danger">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>Lỗi:</h6>
                                <ul class="mb-0">
                                    <?php foreach ($error_messages as $message): ?>
                                        <li><?= htmlspecialchars($message) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Các bảng chính sẽ được tạo:</h5>
                                <ul class="list-group">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-users me-2 text-primary"></i>users</span>
                                        <span class="badge bg-primary rounded-pill">Người dùng</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-user-circle me-2 text-info"></i>users_info</span>
                                        <span class="badge bg-info rounded-pill">Thông tin người dùng</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-user-tag me-2 text-secondary"></i>roles</span>
                                        <span class="badge bg-secondary rounded-pill">Vai trò</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-calendar-check me-2 text-warning"></i>appointments</span>
                                        <span class="badge bg-warning rounded-pill">Lịch hẹn</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-user-md me-2 text-success"></i>doctors</span>
                                        <span class="badge bg-success rounded-pill">Bác sĩ</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-hospital me-2 text-danger"></i>clinics</span>
                                        <span class="badge bg-danger rounded-pill">Phòng khám</span>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5>Bảng thương mại điện tử:</h5>
                                <ul class="list-group">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-pills me-2 text-primary"></i>products</span>
                                        <span class="badge bg-primary rounded-pill">Sản phẩm</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-shopping-cart me-2 text-success"></i>orders</span>
                                        <span class="badge bg-success rounded-pill">Đơn hàng</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-list me-2 text-info"></i>order_items</span>
                                        <span class="badge bg-info rounded-pill">Chi tiết đơn hàng</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-tags me-2 text-warning"></i>product_categories</span>
                                        <span class="badge bg-warning rounded-pill">Danh mục sản phẩm</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-capsules me-2 text-danger"></i>medicines</span>
                                        <span class="badge bg-danger rounded-pill">Thuốc</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-credit-card me-2 text-secondary"></i>payments</span>
                                        <span class="badge bg-secondary rounded-pill">Thanh toán</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Lưu ý:</strong> Quá trình này sẽ tạo toàn bộ cấu trúc database cho hệ thống MediSync bao gồm:
                            <ul class="mb-0 mt-2">
                                <li>Hệ thống quản lý người dùng và phân quyền</li>
                                <li>Quản lý lịch hẹn khám bệnh</li>
                                <li>Hệ thống thương mại điện tử (sản phẩm, đơn hàng)</li>
                                <li>Quản lý bác sĩ và phòng khám</li>
                                <li>Dữ liệu mẫu để test hệ thống</li>
                            </ul>
                        </div>

                        <form method="POST" class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg me-3">
                                <i class="fas fa-play me-2"></i>
                                Bắt đầu thiết lập
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary btn-lg">
                                <i class="fas fa-arrow-left me-2"></i>
                                Quay lại Dashboard
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 