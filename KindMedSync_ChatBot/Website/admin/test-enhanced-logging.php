<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions/enhanced_logger.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../login.php');
    exit();
}

// Test the enhanced logging system
if (isset($_POST['test_logs'])) {
    $test_type = $_POST['test_type'];
    
    switch ($test_type) {
        case 'auth':
            EnhancedLogger::logAuth('login', 'test_user', true);
            EnhancedLogger::logAuth('logout', 'test_user', true);
            EnhancedLogger::logAuth('login', 'invalid_user', false, 'Invalid credentials');
            $message = "Authentication logs created successfully!";
            break;
            
        case 'cart':
            EnhancedLogger::logCart('ADD_TO_CART', 123, 'Paracetamol 500mg', 2, [
                'price' => 25000,
                'category' => 'Medicine'
            ]);
            EnhancedLogger::logCart('UPDATE_QUANTITY', 123, 'Paracetamol 500mg', 3);
            EnhancedLogger::logCart('REMOVE_FROM_CART', 123, 'Paracetamol 500mg', 1);
            $message = "Cart action logs created successfully!";
            break;
            
        case 'api':
            EnhancedLogger::logAPI('/api/cart/add', 'POST', ['product_id' => 123], ['success' => true], 200);
            EnhancedLogger::logAPI('/api/cart/remove', 'DELETE', ['product_id' => 123], ['success' => true], 200);
            EnhancedLogger::logAPI('/api/invalid', 'GET', [], ['error' => 'Not found'], 404);
            $message = "API call logs created successfully!";
            break;
            
        case 'appointment':
            EnhancedLogger::logAppointment('BOOK_APPOINTMENT', 456, 'Nguyen Van A', 'Dr. Smith', [
                'appointment_date' => '2025-01-15',
                'time_slot' => '09:00'
            ]);
            EnhancedLogger::logAppointment('CANCEL_APPOINTMENT', 456, 'Nguyen Van A', 'Dr. Smith', [
                'reason' => 'Patient request'
            ]);
            $message = "Appointment logs created successfully!";
            break;
            
        case 'order':
            EnhancedLogger::logOrder('CREATE_ORDER', 789, 150000, [
                'items_count' => 3,
                'payment_method' => 'credit_card'
            ]);
            EnhancedLogger::logOrder('PAYMENT_SUCCESS', 789, 150000, [
                'transaction_id' => 'TXN123456'
            ]);
            $message = "Order logs created successfully!";
            break;
            
        case 'email':
            EnhancedLogger::logEmail('SEND_EMAIL', 'user@example.com', 'Order Confirmation', true);
            EnhancedLogger::logEmail('SEND_EMAIL', 'invalid@example.com', 'Password Reset', false, 'SMTP error');
            $message = "Email logs created successfully!";
            break;
            
        case 'system':
            EnhancedLogger::logSystem('MAINTENANCE_MODE', 'System under maintenance for updates');
            EnhancedLogger::logSystem('DATABASE_BACKUP', 'Daily backup completed successfully');
            EnhancedLogger::logSystem('CACHE_CLEAR', 'Cache cleared by admin');
            $message = "System event logs created successfully!";
            break;
            
        case 'security':
            EnhancedLogger::logSecurity('BRUTE_FORCE_ATTEMPT', 'Multiple failed login attempts', 'HIGH', [
                'attempts' => 5,
                'blocked_duration' => 300
            ]);
            EnhancedLogger::logSecurity('UNAUTHORIZED_ACCESS', 'Attempt to access admin panel', 'MEDIUM', [
                'attempted_page' => 'admin/users.php'
            ]);
            $message = "Security event logs created successfully!";
            break;
            
        case 'error':
            EnhancedLogger::logError('Database connection failed', 'MySQL connection');
            EnhancedLogger::logError('File not found', 'Image upload', 'Stack trace here...');
            $message = "Error logs created successfully!";
            break;
            
        case 'all':
            // Create sample logs for all categories
            EnhancedLogger::logAuth('login', 'sample_user', true);
            EnhancedLogger::logCart('ADD_TO_CART', 123, 'Sample Product', 1);
            EnhancedLogger::logAPI('/api/test', 'GET', [], ['status' => 'ok'], 200);
            EnhancedLogger::logAppointment('BOOK_APPOINTMENT', 123, 'Test Patient', 'Dr. Test');
            EnhancedLogger::logOrder('CREATE_ORDER', 456, 100000);
            EnhancedLogger::logEmail('SEND_EMAIL', 'test@example.com', 'Test Email', true);
            EnhancedLogger::logSystem('SYSTEM_START', 'System started successfully');
            EnhancedLogger::logSecurity('LOGIN_ATTEMPT', 'User login attempt', 'LOW');
            EnhancedLogger::logError('Test error', 'Test context');
            $message = "All types of logs created successfully!";
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Enhanced Logging System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-vial me-2"></i>Test Enhanced Logging System
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-4">
                            <h5>Test Different Log Types</h5>
                            <p class="text-muted">Select a log type to create sample log entries. You can then view them in the Activity Log page.</p>
                        </div>
                        
                        <form method="POST" action="">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-user-shield fa-3x text-warning mb-3"></i>
                                            <h6>Authentication</h6>
                                            <p class="text-muted small">Login, logout, registration events</p>
                                            <button type="submit" name="test_logs" value="auth" class="btn btn-warning btn-sm">
                                                <input type="hidden" name="test_type" value="auth">
                                                Test Auth Logs
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-shopping-cart fa-3x text-success mb-3"></i>
                                            <h6>Cart Actions</h6>
                                            <p class="text-muted small">Add, remove, update cart items</p>
                                            <button type="submit" name="test_logs" value="cart" class="btn btn-success btn-sm">
                                                <input type="hidden" name="test_type" value="cart">
                                                Test Cart Logs
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-code fa-3x text-info mb-3"></i>
                                            <h6>API Calls</h6>
                                            <p class="text-muted small">REST API requests and responses</p>
                                            <button type="submit" name="test_logs" value="api" class="btn btn-info btn-sm">
                                                <input type="hidden" name="test_type" value="api">
                                                Test API Logs
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-calendar-check fa-3x text-purple mb-3"></i>
                                            <h6>Appointments</h6>
                                            <p class="text-muted small">Booking, cancellation, rescheduling</p>
                                            <button type="submit" name="test_logs" value="appointment" class="btn btn-secondary btn-sm">
                                                <input type="hidden" name="test_type" value="appointment">
                                                Test Appointment Logs
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-shopping-bag fa-3x text-primary mb-3"></i>
                                            <h6>Orders</h6>
                                            <p class="text-muted small">Purchase, payment, shipping events</p>
                                            <button type="submit" name="test_logs" value="order" class="btn btn-primary btn-sm">
                                                <input type="hidden" name="test_type" value="order">
                                                Test Order Logs
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-envelope fa-3x text-info mb-3"></i>
                                            <h6>Email Activities</h6>
                                            <p class="text-muted small">Email sending status and details</p>
                                            <button type="submit" name="test_logs" value="email" class="btn btn-info btn-sm">
                                                <input type="hidden" name="test_type" value="email">
                                                Test Email Logs
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-cogs fa-3x text-secondary mb-3"></i>
                                            <h6>System Events</h6>
                                            <p class="text-muted small">System-wide events and maintenance</p>
                                            <button type="submit" name="test_logs" value="system" class="btn btn-secondary btn-sm">
                                                <input type="hidden" name="test_type" value="system">
                                                Test System Logs
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-shield-alt fa-3x text-danger mb-3"></i>
                                            <h6>Security Events</h6>
                                            <p class="text-muted small">Security alerts and violations</p>
                                            <button type="submit" name="test_logs" value="security" class="btn btn-danger btn-sm">
                                                <input type="hidden" name="test_type" value="security">
                                                Test Security Logs
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                                            <h6>Error Logs</h6>
                                            <p class="text-muted small">Error tracking with context</p>
                                            <button type="submit" name="test_logs" value="error" class="btn btn-warning btn-sm">
                                                <input type="hidden" name="test_type" value="error">
                                                Test Error Logs
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card h-100 border-dark">
                                        <div class="card-body text-center">
                                            <i class="fas fa-list-check fa-3x text-dark mb-3"></i>
                                            <h6>All Types</h6>
                                            <p class="text-muted small">Create samples for all log types</p>
                                            <button type="submit" name="test_logs" value="all" class="btn btn-dark btn-sm">
                                                <input type="hidden" name="test_type" value="all">
                                                Test All Logs
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-grid gap-2">
                                    <a href="activity-log.php" class="btn btn-outline-primary">
                                        <i class="fas fa-chart-line me-2"></i>View Activity Logs
                                    </a>
                                    <a href="dashboard.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-tachometer-alt me-2"></i>Back to Dashboard
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>System Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Logs Directory:</strong> <code>/logs/</code></p>
                                <p><strong>Access Protection:</strong> <span class="badge bg-success">Enabled</span></p>
                                <p><strong>Daily Rotation:</strong> <span class="badge bg-info">Active</span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Current User:</strong> <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></p>
                                <p><strong>User Role:</strong> <span class="badge bg-primary">Admin</span></p>
                                <p><strong>Session ID:</strong> <code><?php echo substr(session_id(), 0, 10); ?>...</code></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 