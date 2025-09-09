<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../login.php');
    exit();
}

// Get current date for default filter
$current_date = date('Y-m-d');
$selected_date = $_GET['date'] ?? $current_date;
$selected_type = $_GET['type'] ?? 'all';
$search_query = $_GET['search'] ?? '';

// Function to parse and format log entries
function parseLogEntry($line) {
    if (empty(trim($line))) return null;
    
    // Pattern to match log format: [timestamp] [level] [User: id] [IP: ip] message
    $pattern = '/^\[([^\]]+)\] \[([^\]]+)\] \[([^\]]+)\] \[([^\]]+)\] (.+)$/';
    
    if (preg_match($pattern, $line, $matches)) {
        return [
            'timestamp' => $matches[1],
            'level' => $matches[2],
            'user_info' => $matches[3],
            'ip' => $matches[4],
            'message' => $matches[5],
            'raw' => $line
        ];
    }
    
    return null;
}

// Function to get log type from message
function getLogType($message) {
    if (strpos($message, 'CART_') !== false) return 'cart';
    if (strpos($message, 'API_') !== false) return 'api';
    if (strpos($message, 'LOGIN') !== false) return 'auth';
    if (strpos($message, 'REGISTER') !== false) return 'auth';
    if (strpos($message, 'EMAIL') !== false) return 'email';
    if (strpos($message, 'ORDER') !== false) return 'order';
    if (strpos($message, 'APPOINTMENT') !== false) return 'appointment';
    return 'system';
}

// Function to format message for display
function formatMessage($message) {
    // Remove technical prefixes and make more readable
    $message = preg_replace('/^[A-Z_]+:\s*/', '', $message);
    
    // Format API calls
    if (strpos($message, 'API_CALL:') !== false) {
        $message = str_replace('API_CALL:', 'API Request:', $message);
        $message = str_replace('| Request:', '<br><small class="text-muted">Request:</small>', $message);
        $message = str_replace('| Response:', '<br><small class="text-muted">Response:</small>', $message);
    }
    
    // Format cart actions
    if (strpos($message, 'CART_ACTION:') !== false) {
        $message = str_replace('CART_ACTION:', 'Cart Action:', $message);
        $message = str_replace('| Product:', '<br><small class="text-muted">Product:</small>', $message);
        $message = str_replace('| Quantity:', '<small class="text-muted">Quantity:</small>', $message);
        $message = str_replace('| Details:', '<br><small class="text-muted">Details:</small>', $message);
    }
    
    // Format cart debug
    if (strpos($message, 'CART_DEBUG:') !== false) {
        $message = str_replace('CART_DEBUG:', 'Cart Debug:', $message);
    }
    
    return $message;
}

// Function to get log files for selected date
function getLogFiles($date) {
    $log_dir = '../logs/';
    $files = [];
    
    if (is_dir($log_dir)) {
        $scan = scandir($log_dir);
        foreach ($scan as $file) {
            if ($file !== '.' && $file !== '..' && strpos($file, $date) !== false && pathinfo($file, PATHINFO_EXTENSION) === 'log') {
                $files[] = $log_dir . $file;
            }
        }
    }
    
    return $files;
}

// Get log entries
$log_entries = [];
$log_files = getLogFiles($selected_date);

foreach ($log_files as $file) {
    if (file_exists($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parsed = parseLogEntry($line);
            if ($parsed) {
                $parsed['type'] = getLogType($parsed['message']);
                $parsed['formatted_message'] = formatMessage($parsed['message']);
                
                // Apply filters
                if ($selected_type !== 'all' && $parsed['type'] !== $selected_type) {
                    continue;
                }
                
                if (!empty($search_query) && stripos($parsed['message'], $search_query) === false) {
                    continue;
                }
                
                $log_entries[] = $parsed;
            }
        }
    }
}

// Sort by timestamp (newest first)
usort($log_entries, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

// Pagination
$per_page = 50;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$total_entries = count($log_entries);
$total_pages = ceil($total_entries / $per_page);
$offset = ($page - 1) * $per_page;
$log_entries = array_slice($log_entries, $offset, $per_page);

// Get statistics
$stats = [
    'total' => $total_entries,
    'cart' => 0,
    'api' => 0,
    'auth' => 0,
    'system' => 0,
    'email' => 0,
    'order' => 0,
    'appointment' => 0
];

foreach ($log_entries as $entry) {
    $stats[$entry['type']]++;
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
    <link href="assets/css/sidebar.css" rel="stylesheet">
    <link href="assets/css/header.css" rel="stylesheet">
    <style>
        .log-entry {
            border-left: 4px solid #dee2e6;
            margin-bottom: 10px;
        }
        .log-entry.cart { border-left-color: #28a745; }
        .log-entry.api { border-left-color: #007bff; }
        .log-entry.auth { border-left-color: #ffc107; }
        .log-entry.system { border-left-color: #6c757d; }
        .log-entry.email { border-left-color: #17a2b8; }
        .log-entry.order { border-left-color: #fd7e14; }
        .log-entry.appointment { border-left-color: #6f42c1; }
        
        .log-level {
            font-size: 0.75rem;
            padding: 2px 6px;
            border-radius: 3px;
        }
        .log-level.INFO { background-color: #d4edda; color: #155724; }
        .log-level.ERROR { background-color: #f8d7da; color: #721c24; }
        .log-level.WARNING { background-color: #fff3cd; color: #856404; }
        .log-level.DEBUG { background-color: #e2e3e5; color: #383d41; }
        .log-level.API { background-color: #cce5ff; color: #004085; }
        .log-level.CART { background-color: #d1ecf1; color: #0c5460; }
        
        .stats-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
        }
        
        .filter-form {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .log-timestamp {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .log-user {
            font-size: 0.85rem;
            color: #495057;
        }
        
        .log-message {
            word-wrap: break-word;
        }
        
        .badge-type {
            font-size: 0.7rem;
            padding: 3px 8px;
        }
    </style>
</head>
<body>
<?php include 'includes/headeradmin.php'; ?>
<?php include 'includes/sidebaradmin.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebaradmin.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-chart-line me-2"></i>Activity Logs</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                                <i class="fas fa-refresh"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="stats-card text-center">
                            <i class="fas fa-list-ul fa-2x text-primary mb-2"></i>
                            <h4 class="mb-0"><?php echo number_format($stats['total']); ?></h4>
                            <small class="text-muted">Total Entries</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stats-card text-center">
                            <i class="fas fa-shopping-cart fa-2x text-success mb-2"></i>
                            <h4 class="mb-0"><?php echo number_format($stats['cart']); ?></h4>
                            <small class="text-muted">Cart Actions</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stats-card text-center">
                            <i class="fas fa-code fa-2x text-info mb-2"></i>
                            <h4 class="mb-0"><?php echo number_format($stats['api']); ?></h4>
                            <small class="text-muted">API Calls</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stats-card text-center">
                            <i class="fas fa-user-shield fa-2x text-warning mb-2"></i>
                            <h4 class="mb-0"><?php echo number_format($stats['auth']); ?></h4>
                            <small class="text-muted">Auth Events</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stats-card text-center">
                            <i class="fas fa-envelope fa-2x text-primary mb-2"></i>
                            <h4 class="mb-0"><?php echo number_format($stats['email']); ?></h4>
                            <small class="text-muted">Email Events</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stats-card text-center">
                            <i class="fas fa-calendar-check fa-2x text-purple mb-2"></i>
                            <h4 class="mb-0"><?php echo number_format($stats['appointment']); ?></h4>
                            <small class="text-muted">Appointments</small>
                        </div>
                    </div>
                </div>

                <!-- Filter Form -->
                <div class="filter-form">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="date" class="form-label">Select Date</label>
                            <input type="date" class="form-control" id="date" name="date" value="<?php echo $selected_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="type" class="form-label">Log Type</label>
                            <select class="form-select" id="type" name="type">
                                <option value="all" <?php echo $selected_type === 'all' ? 'selected' : ''; ?>>All Types</option>
                                <option value="cart" <?php echo $selected_type === 'cart' ? 'selected' : ''; ?>>Cart Actions</option>
                                <option value="api" <?php echo $selected_type === 'api' ? 'selected' : ''; ?>>API Calls</option>
                                <option value="auth" <?php echo $selected_type === 'auth' ? 'selected' : ''; ?>>Authentication</option>
                                <option value="email" <?php echo $selected_type === 'email' ? 'selected' : ''; ?>>Email Events</option>
                                <option value="order" <?php echo $selected_type === 'order' ? 'selected' : ''; ?>>Orders</option>
                                <option value="appointment" <?php echo $selected_type === 'appointment' ? 'selected' : ''; ?>>Appointments</option>
                                <option value="system" <?php echo $selected_type === 'system' ? 'selected' : ''; ?>>System</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search in logs...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Log Entries -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-file-alt me-2"></i>Log Entries 
                            <span class="badge bg-secondary"><?php echo number_format($total_entries); ?> entries</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($log_entries)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No log entries found</h5>
                                <p class="text-muted">Try adjusting your filters or select a different date.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($log_entries as $entry): ?>
                                <div class="log-entry <?php echo $entry['type']; ?> p-3 mb-3 bg-light rounded">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <span class="log-level <?php echo $entry['level']; ?>"><?php echo $entry['level']; ?></span>
                                            <span class="badge badge-type bg-<?php echo $entry['type'] === 'cart' ? 'success' : ($entry['type'] === 'api' ? 'primary' : ($entry['type'] === 'auth' ? 'warning' : 'secondary')); ?>">
                                                <?php echo ucfirst($entry['type']); ?>
                                            </span>
                                        </div>
                                        <small class="log-timestamp">
                                            <i class="fas fa-clock me-1"></i><?php echo date('H:i:s', strtotime($entry['timestamp'])); ?>
                                        </small>
                                    </div>
                                    <div class="log-message">
                                        <?php echo $entry['formatted_message']; ?>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <small class="log-user">
                                            <i class="fas fa-user me-1"></i><?php echo $entry['user_info']; ?>
                                            <i class="fas fa-globe ms-2 me-1"></i><?php echo $entry['ip']; ?>
                                        </small>
                                        <small class="text-muted">
                                            <?php echo date('Y-m-d H:i:s', strtotime($entry['timestamp'])); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Log pagination">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&date=<?php echo $selected_date; ?>&type=<?php echo $selected_type; ?>&search=<?php echo urlencode($search_query); ?>">Previous</a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&date=<?php echo $selected_date; ?>&type=<?php echo $selected_type; ?>&search=<?php echo urlencode($search_query); ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&date=<?php echo $selected_date; ?>&type=<?php echo $selected_type; ?>&search=<?php echo urlencode($search_query); ?>">Next</a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 