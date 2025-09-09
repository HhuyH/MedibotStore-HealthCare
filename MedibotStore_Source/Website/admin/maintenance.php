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
    
    if ($action === 'clear_cache') {
        try {
            // Xóa cache files
            $cache_dirs = [
                '../cache/',
                '../temp/',
                '../logs/',
                '../assets/cache/'
            ];
            
            $deleted_files = 0;
            foreach ($cache_dirs as $dir) {
                if (is_dir($dir)) {
                    $files = glob($dir . '*');
                    foreach ($files as $file) {
                        if (is_file($file) && basename($file) !== '.gitkeep') {
                            if (unlink($file)) {
                                $deleted_files++;
                            }
                        }
                    }
                }
            }
            
            $message = "Đã xóa {$deleted_files} file cache.";
        } catch (Exception $e) {
            $error = 'Lỗi khi xóa cache: ' . $e->getMessage();
        }
    }
    elseif ($action === 'optimize_database') {
        try {
            // Lấy danh sách bảng
            $tables = [];
            $result = $conn->query("SHOW TABLES");
            while ($row = $result->fetch_row()) {
                $tables[] = $row[0];
            }
            
            // Optimize từng bảng
            $optimized_tables = 0;
            foreach ($tables as $table) {
                $conn->query("OPTIMIZE TABLE $table");
                $optimized_tables++;
            }
            
            $message = "Đã tối ưu hóa {$optimized_tables} bảng database.";
        } catch (Exception $e) {
            $error = 'Lỗi khi tối ưu hóa database: ' . $e->getMessage();
        }
    }
    elseif ($action === 'clean_logs') {
        try {
            $log_files = glob('../logs/*.log');
            $deleted_logs = 0;
            
            foreach ($log_files as $log_file) {
                // Giữ lại log của 7 ngày gần nhất
                if (filemtime($log_file) < strtotime('-7 days')) {
                    if (unlink($log_file)) {
                        $deleted_logs++;
                    }
                }
            }
            
            $message = "Đã xóa {$deleted_logs} file log cũ.";
        } catch (Exception $e) {
            $error = 'Lỗi khi xóa logs: ' . $e->getMessage();
        }
    }
    elseif ($action === 'repair_database') {
        try {
            // Lấy danh sách bảng
            $tables = [];
            $result = $conn->query("SHOW TABLES");
            while ($row = $result->fetch_row()) {
                $tables[] = $row[0];
            }
            
            // Repair từng bảng
            $repaired_tables = 0;
            foreach ($tables as $table) {
                $conn->query("REPAIR TABLE $table");
                $repaired_tables++;
            }
            
            $message = "Đã sửa chữa {$repaired_tables} bảng database.";
        } catch (Exception $e) {
            $error = 'Lỗi khi sửa chữa database: ' . $e->getMessage();
        }
    }
    elseif ($action === 'update_statistics') {
        try {
            // Cập nhật thống kê
            $conn->query("ANALYZE TABLE blog_posts, products, appointments, users");
            $message = "Đã cập nhật thống kê database.";
        } catch (Exception $e) {
            $error = 'Lỗi khi cập nhật thống kê: ' . $e->getMessage();
        }
    }
}

// Lấy thông tin hệ thống
$system_info = [
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'mysql_version' => $conn->server_info
];

// Kiểm tra disk space
$disk_free = disk_free_space('.');
$disk_total = disk_total_space('.');
$disk_used = $disk_total - $disk_free;
$disk_usage_percent = ($disk_used / $disk_total) * 100;

// Thống kê database
$db_stats = [];
$result = $conn->query("SELECT COUNT(*) as total FROM users");
$db_stats['users'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM products");
$db_stats['products'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM appointments");
$db_stats['appointments'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM blog_posts");
$db_stats['blog_posts'] = $result->fetch_assoc()['total'];

// Kiểm tra log files
$log_files = glob('../logs/*.log');
$log_count = count($log_files);
$log_size = 0;
foreach ($log_files as $log_file) {
    $log_size += filesize($log_file);
}

// Format bytes function
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảo trì hệ thống - Admin</title>
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
                                <i class="fas fa-tools"></i> Bảo trì hệ thống
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
                            
                            <!-- System Overview -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <h6 class="card-title">Disk Usage</h6>
                                                    <h4><?php echo number_format($disk_usage_percent, 1); ?>%</h4>
                                                </div>
                                                <div>
                                                    <i class="fas fa-hdd fa-2x"></i>
                                                </div>
                                            </div>
                                            <small><?php echo formatBytes($disk_used); ?> / <?php echo formatBytes($disk_total); ?></small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="card bg-success text-white">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <h6 class="card-title">Database Records</h6>
                                                    <h4><?php echo array_sum($db_stats); ?></h4>
                                                </div>
                                                <div>
                                                    <i class="fas fa-database fa-2x"></i>
                                                </div>
                                            </div>
                                            <small>Total records</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="card bg-warning text-white">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <h6 class="card-title">Log Files</h6>
                                                    <h4><?php echo $log_count; ?></h4>
                                                </div>
                                                <div>
                                                    <i class="fas fa-file-alt fa-2x"></i>
                                                </div>
                                            </div>
                                            <small><?php echo formatBytes($log_size); ?></small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="card bg-info text-white">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <h6 class="card-title">PHP Version</h6>
                                                    <h4><?php echo substr($system_info['php_version'], 0, 3); ?></h4>
                                                </div>
                                                <div>
                                                    <i class="fab fa-php fa-2x"></i>
                                                </div>
                                            </div>
                                            <small>Current version</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Maintenance Actions -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                <i class="fas fa-broom"></i> Cleaning Tools
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-grid gap-2">
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="clear_cache">
                                                    <button type="submit" class="btn btn-outline-primary w-100" onclick="return confirm('Xóa tất cả cache files?')">
                                                        <i class="fas fa-trash-alt"></i> Clear Cache
                                                    </button>
                                                </form>
                                                
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="clean_logs">
                                                    <button type="submit" class="btn btn-outline-warning w-100" onclick="return confirm('Xóa log files cũ hơn 7 ngày?')">
                                                        <i class="fas fa-file-alt"></i> Clean Old Logs
                                                    </button>
                                                </form>
                                                
                                                <button type="button" class="btn btn-outline-danger w-100" onclick="clearTempFiles()">
                                                    <i class="fas fa-folder-open"></i> Clear Temp Files
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                <i class="fas fa-database"></i> Database Tools
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-grid gap-2">
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="optimize_database">
                                                    <button type="submit" class="btn btn-outline-success w-100" onclick="return confirm('Tối ưu hóa database?')">
                                                        <i class="fas fa-rocket"></i> Optimize Database
                                                    </button>
                                                </form>
                                                
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="repair_database">
                                                    <button type="submit" class="btn btn-outline-info w-100" onclick="return confirm('Sửa chữa database tables?')">
                                                        <i class="fas fa-wrench"></i> Repair Tables
                                                    </button>
                                                </form>
                                                
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="update_statistics">
                                                    <button type="submit" class="btn btn-outline-primary w-100">
                                                        <i class="fas fa-chart-bar"></i> Update Statistics
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- System Information -->
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                <i class="fas fa-server"></i> System Information
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-sm">
                                                <tr>
                                                    <td><strong>PHP Version:</strong></td>
                                                    <td><?php echo $system_info['php_version']; ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Server Software:</strong></td>
                                                    <td><?php echo $system_info['server_software']; ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>MySQL Version:</strong></td>
                                                    <td><?php echo $system_info['mysql_version']; ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Memory Limit:</strong></td>
                                                    <td><?php echo $system_info['memory_limit']; ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Max Execution Time:</strong></td>
                                                    <td><?php echo $system_info['max_execution_time']; ?>s</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Upload Max Size:</strong></td>
                                                    <td><?php echo $system_info['upload_max_filesize']; ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Post Max Size:</strong></td>
                                                    <td><?php echo $system_info['post_max_size']; ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                <i class="fas fa-chart-pie"></i> Database Statistics
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-sm">
                                                <tr>
                                                    <td><strong>Users:</strong></td>
                                                    <td><span class="badge bg-primary"><?php echo number_format($db_stats['users']); ?></span></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Products:</strong></td>
                                                    <td><span class="badge bg-success"><?php echo number_format($db_stats['products']); ?></span></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Appointments:</strong></td>
                                                    <td><span class="badge bg-warning"><?php echo number_format($db_stats['appointments']); ?></span></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Blog Posts:</strong></td>
                                                    <td><span class="badge bg-info"><?php echo number_format($db_stats['blog_posts']); ?></span></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Total Records:</strong></td>
                                                    <td><span class="badge bg-dark"><?php echo number_format(array_sum($db_stats)); ?></span></td>
                                                </tr>
                                            </table>
                                            
                                            <div class="mt-3">
                                                <canvas id="dbChart" width="400" height="200"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Task Scheduler -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                <i class="fas fa-clock"></i> Scheduled Tasks
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Task</th>
                                                            <th>Schedule</th>
                                                            <th>Last Run</th>
                                                            <th>Status</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>Database Backup</td>
                                                            <td>Daily at 2:00 AM</td>
                                                            <td>-</td>
                                                            <td><span class="badge bg-secondary">Disabled</span></td>
                                                            <td>
                                                                <button class="btn btn-sm btn-outline-primary">Enable</button>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>Clear Cache</td>
                                                            <td>Weekly</td>
                                                            <td>-</td>
                                                            <td><span class="badge bg-secondary">Disabled</span></td>
                                                            <td>
                                                                <button class="btn btn-sm btn-outline-primary">Enable</button>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>Optimize Database</td>
                                                            <td>Monthly</td>
                                                            <td>-</td>
                                                            <td><span class="badge bg-secondary">Disabled</span></td>
                                                            <td>
                                                                <button class="btn btn-sm btn-outline-primary">Enable</button>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function clearTempFiles() {
            if (confirm('Xóa tất cả file tạm thời?')) {
                // AJAX call to clear temp files
                fetch('ajax/clear_temp.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Đã xóa file tạm thời thành công!');
                        location.reload();
                    } else {
                        alert('Lỗi: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        }
        
        // Database statistics chart
        const ctx = document.getElementById('dbChart').getContext('2d');
        const dbChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Users', 'Products', 'Appointments', 'Blog Posts'],
                datasets: [{
                    data: [<?php echo implode(',', $db_stats); ?>],
                    backgroundColor: [
                        '#007bff',
                        '#28a745',
                        '#ffc107',
                        '#17a2b8'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
        
        // Auto refresh system stats every 30 seconds
        setInterval(function() {
            // Can add AJAX call to refresh stats
        }, 30000);
    </script>
</body>
</html> 