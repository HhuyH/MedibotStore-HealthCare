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

// Xử lý các action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_backup') {
        try {
            // Tạo thư mục backup nếu chưa có
            $backup_dir = '../backups/';
            if (!is_dir($backup_dir)) {
                mkdir($backup_dir, 0755, true);
            }
            
            // Tên file backup
            $backup_file = $backup_dir . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            
            // Lệnh mysqldump
            $host = DB_HOST;
            $username = DB_USERNAME;
            $password = DB_PASSWORD;
            $database = DB_NAME;
            
            $command = "mysqldump --host=$host --user=$username --password=$password $database > $backup_file";
            
            // Thực thi lệnh
            exec($command, $output, $return_var);
            
            if ($return_var === 0) {
                $message = 'Tạo backup thành công! File: ' . basename($backup_file);
            } else {
                $error = 'Không thể tạo backup. Vui lòng kiểm tra cài đặt mysqldump.';
            }
            
        } catch (Exception $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
    elseif ($action === 'restore_backup') {
        $backup_file = $_POST['backup_file'] ?? '';
        $backup_path = '../backups/' . basename($backup_file);
        
        if (file_exists($backup_path)) {
            try {
                $host = DB_HOST;
                $username = DB_USERNAME;
                $password = DB_PASSWORD;
                $database = DB_NAME;
                
                $command = "mysql --host=$host --user=$username --password=$password $database < $backup_path";
                exec($command, $output, $return_var);
                
                if ($return_var === 0) {
                    $message = 'Khôi phục backup thành công!';
                } else {
                    $error = 'Không thể khôi phục backup. Vui lòng kiểm tra file.';
                }
            } catch (Exception $e) {
                $error = 'Lỗi: ' . $e->getMessage();
            }
        } else {
            $error = 'File backup không tồn tại!';
        }
    }
    elseif ($action === 'delete_backup') {
        $backup_file = $_POST['backup_file'] ?? '';
        $backup_path = '../backups/' . basename($backup_file);
        
        if (file_exists($backup_path)) {
            if (unlink($backup_path)) {
                $message = 'Xóa backup thành công!';
            } else {
                $error = 'Không thể xóa backup!';
            }
        } else {
            $error = 'File backup không tồn tại!';
        }
    }
}

// Lấy danh sách backup
$backups = [];
$backup_dir = '../backups/';
if (is_dir($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $filepath = $backup_dir . $file;
            $backups[] = [
                'name' => $file,
                'size' => formatBytes(filesize($filepath)),
                'date' => date('d/m/Y H:i:s', filemtime($filepath))
            ];
        }
    }
}

// Hàm format bytes
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Thông tin database
$db_info = [];
$tables = $conn->query("SHOW TABLES");
$db_info['tables'] = $tables->num_rows;

$db_size = $conn->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'size' FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");
$db_info['size'] = $db_size->fetch_assoc()['size'] . ' MB';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup & Restore - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link href="assets/css/sidebar.css" rel="stylesheet">
    <link href="assets/css/header.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/headeradmin.php'; ?>
    <?php include 'includes/sidebaradmin.php'; ?>

    <main class="main-content">
        <div class="container-fluid p-4">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">
                                <i class="fas fa-database"></i> Backup & Restore Database
                            </h4>
                        </div>
                        
                        <div class="card-body">
                            <?php if ($message): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($message); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($error); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <div class="row">
                                <!-- Thông tin Database -->
                                <div class="col-md-4">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                <i class="fas fa-info-circle"></i> Thông tin Database
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="text-center">
                                                        <i class="fas fa-table fa-2x text-primary mb-2"></i>
                                                        <h5><?php echo $db_info['tables']; ?></h5>
                                                        <small class="text-muted">Bảng dữ liệu</small>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="text-center">
                                                        <i class="fas fa-hdd fa-2x text-success mb-2"></i>
                                                        <h5><?php echo $db_info['size']; ?></h5>
                                                        <small class="text-muted">Kích thước</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <hr>
                                            <p class="mb-1"><strong>Database:</strong> <?php echo DB_NAME; ?></p>
                                            <p class="mb-1"><strong>Host:</strong> <?php echo DB_HOST; ?></p>
                                            <p class="mb-0"><strong>Backup files:</strong> <?php echo count($backups); ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Tạo Backup -->
                                <div class="col-md-4">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                <i class="fas fa-plus-circle"></i> Tạo Backup
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <p class="text-muted">Tạo bản sao lưu dữ liệu hiện tại của hệ thống.</p>
                                            
                                            <form method="POST" class="mb-3">
                                                <input type="hidden" name="action" value="create_backup">
                                                <button type="submit" class="btn btn-primary w-100" onclick="return confirm('Bạn có chắc muốn tạo backup?')">
                                                    <i class="fas fa-save"></i> Tạo Backup Ngay
                                                </button>
                                            </form>
                                            
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i>
                                                <strong>Lưu ý:</strong> Quá trình backup có thể mất vài phút tùy thuộc vào kích thước dữ liệu.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Upload Backup -->
                                <div class="col-md-4">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                <i class="fas fa-upload"></i> Upload Backup
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <p class="text-muted">Tải lên file backup từ máy tính.</p>
                                            
                                            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                                                <input type="hidden" name="action" value="upload_backup">
                                                <div class="mb-3">
                                                    <input type="file" class="form-control" name="backup_file" accept=".sql" required>
                                                </div>
                                                <button type="submit" class="btn btn-success w-100">
                                                    <i class="fas fa-upload"></i> Upload Backup
                                                </button>
                                            </form>
                                            
                                            <div class="alert alert-warning mt-3">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                <strong>Cảnh báo:</strong> Chỉ upload file .sql từ nguồn tin cậy.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Danh sách Backup -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                <i class="fas fa-list"></i> Danh sách Backup
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <?php if (empty($backups)): ?>
                                                <div class="text-center text-muted py-4">
                                                    <i class="fas fa-folder-open fa-3x mb-3"></i>
                                                    <p>Chưa có file backup nào.</p>
                                                </div>
                                            <?php else: ?>
                                                <div class="table-responsive">
                                                    <table class="table table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>Tên file</th>
                                                                <th>Kích thước</th>
                                                                <th>Ngày tạo</th>
                                                                <th>Thao tác</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($backups as $backup): ?>
                                                                <tr>
                                                                    <td>
                                                                        <i class="fas fa-file-export text-primary me-2"></i>
                                                                        <?php echo htmlspecialchars($backup['name']); ?>
                                                                    </td>
                                                                    <td><?php echo $backup['size']; ?></td>
                                                                    <td><?php echo $backup['date']; ?></td>
                                                                    <td>
                                                                        <div class="btn-group btn-group-sm">
                                                                            <a href="../backups/<?php echo urlencode($backup['name']); ?>" 
                                                                               class="btn btn-info" title="Download">
                                                                                <i class="fas fa-download"></i>
                                                                            </a>
                                                                            <button type="button" class="btn btn-warning" 
                                                                                    onclick="restoreBackup('<?php echo $backup['name']; ?>')" 
                                                                                    title="Restore">
                                                                                <i class="fas fa-undo"></i>
                                                                            </button>
                                                                            <button type="button" class="btn btn-danger" 
                                                                                    onclick="deleteBackup('<?php echo $backup['name']; ?>')" 
                                                                                    title="Delete">
                                                                                <i class="fas fa-trash"></i>
                                                                            </button>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Backup tự động -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                <i class="fas fa-clock"></i> Backup Tự động
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="autoBackup">
                                                        <label class="form-check-label" for="autoBackup">
                                                            Bật backup tự động
                                                        </label>
                                                    </div>
                                                    <small class="text-muted">Tự động tạo backup hàng ngày lúc 2:00 AM</small>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="keepBackups" class="form-label">Giữ lại backup:</label>
                                                        <select class="form-select" id="keepBackups">
                                                            <option value="7">7 ngày</option>
                                                            <option value="14">14 ngày</option>
                                                            <option value="30" selected>30 ngày</option>
                                                            <option value="90">90 ngày</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Forms ẩn -->
    <form id="restoreForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="restore_backup">
        <input type="hidden" name="backup_file" id="restoreFile">
    </form>
    
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete_backup">
        <input type="hidden" name="backup_file" id="deleteFile">
    </form>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function restoreBackup(filename) {
            if (confirm('CẢNH BÁO: Việc khôi phục backup sẽ ghi đè lên dữ liệu hiện tại. Bạn có chắc chắn muốn tiếp tục?')) {
                if (confirm('Bạn có chắc chắn 100% muốn khôi phục backup này? Hành động này không thể hoàn tác!')) {
                    document.getElementById('restoreFile').value = filename;
                    document.getElementById('restoreForm').submit();
                }
            }
        }
        
        function deleteBackup(filename) {
            if (confirm('Bạn có chắc muốn xóa backup này?')) {
                document.getElementById('deleteFile').value = filename;
                document.getElementById('deleteForm').submit();
            }
        }
        
        // Auto refresh danh sách backup mỗi 30 giây
        setInterval(function() {
            // Có thể thêm AJAX để refresh danh sách backup
        }, 30000);
        
        // Xử lý upload form
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const fileInput = this.querySelector('input[type="file"]');
            const file = fileInput.files[0];
            
            if (file) {
                if (file.size > 100 * 1024 * 1024) { // 100MB
                    alert('File quá lớn! Kích thước tối đa là 100MB.');
                    e.preventDefault();
                    return;
                }
                
                if (!file.name.endsWith('.sql')) {
                    alert('Chỉ chấp nhận file .sql!');
                    e.preventDefault();
                    return;
                }
            }
        });
    </script>
</body>
</html> 