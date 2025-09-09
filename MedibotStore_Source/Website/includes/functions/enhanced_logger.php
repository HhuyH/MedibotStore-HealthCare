<?php

/**
 * Enhanced Logging System for MediSync
 * Provides better readability and user-friendly log messages
 */

class EnhancedLogger {
    private static $logDir = __DIR__ . "/../../logs/";
    
    /**
     * Write a log entry with enhanced formatting
     */
    public static function writeLog($message, $type = 'INFO', $file = 'system', $details = []) {
        $timestamp = date('Y-m-d H:i:s');
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'GUEST';
        $user_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Guest';
        $ip = self::getClientIP();
        $page = basename($_SERVER['PHP_SELF'] ?? 'Unknown');
        
        // Create structured log entry
        $logEntry = [
            'timestamp' => $timestamp,
            'type' => $type,
            'user_id' => $user_id,
            'user_name' => $user_name,
            'ip' => $ip,
            'page' => $page,
            'message' => $message,
            'details' => $details
        ];
        
        // Format for file writing
        $logMessage = self::formatLogMessage($logEntry) . PHP_EOL;
        
        $logFile = self::$logDir . "{$file}_" . date('Y-m-d') . ".log";
        
        // Create logs directory if it doesn't exist
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
        
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Format log message for better readability
     */
    private static function formatLogMessage($entry) {
        $userDisplay = $entry['user_id'] !== 'GUEST' ? 
            "User: {$entry['user_name']} (ID: {$entry['user_id']})" : 
            "Guest User";
            
        return sprintf(
            "[%s] [%s] [%s] [IP: %s] [Page: %s] %s%s",
            $entry['timestamp'],
            $entry['type'],
            $userDisplay,
            $entry['ip'],
            $entry['page'],
            $entry['message'],
            !empty($entry['details']) ? ' | ' . json_encode($entry['details']) : ''
        );
    }
    
    /**
     * Get real client IP address
     */
    private static function getClientIP() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
    
    /**
     * Log user authentication events
     */
    public static function logAuth($action, $username, $success = true, $reason = '') {
        $message = $success ? 
            "✓ User '{$username}' {$action} successfully" : 
            "✗ Failed {$action} for user '{$username}'";
            
        $details = [
            'action' => $action,
            'username' => $username,
            'success' => $success,
            'reason' => $reason
        ];
        
        self::writeLog($message, 'AUTH', 'authentication', $details);
    }
    
    /**
     * Log cart actions with user-friendly messages
     */
    public static function logCart($action, $product_id = null, $product_name = null, $quantity = null, $details = []) {
        $messages = [
            'ADD_TO_CART' => "🛒 Added product to cart",
            'REMOVE_FROM_CART' => "🗑️ Removed product from cart",
            'UPDATE_QUANTITY' => "📝 Updated cart quantity",
            'CLEAR_CART' => "🧹 Cleared entire cart",
            'CHECKOUT_START' => "💳 Started checkout process",
            'CHECKOUT_SUCCESS' => "✅ Checkout completed successfully",
            'CHECKOUT_FAILED' => "❌ Checkout failed"
        ];
        
        $message = $messages[$action] ?? "Cart action: {$action}";
        
        if ($product_name) {
            $message .= " - {$product_name}";
        }
        
        if ($quantity) {
            $message .= " (Qty: {$quantity})";
        }
        
        $logDetails = array_merge([
            'action' => $action,
            'product_id' => $product_id,
            'product_name' => $product_name,
            'quantity' => $quantity
        ], $details);
        
        self::writeLog($message, 'CART', 'cart_actions', $logDetails);
    }
    
    /**
     * Log API calls with better formatting
     */
    public static function logAPI($endpoint, $method, $data = [], $response = [], $status = 200) {
        $statusEmoji = $status >= 200 && $status < 300 ? '✅' : '❌';
        $message = "{$statusEmoji} {$method} {$endpoint} (Status: {$status})";
        
        $details = [
            'endpoint' => $endpoint,
            'method' => $method,
            'status' => $status,
            'request_data' => $data,
            'response_data' => $response
        ];
        
        self::writeLog($message, 'API', 'api_calls', $details);
    }
    
    /**
     * Log appointment actions
     */
    public static function logAppointment($action, $appointment_id = null, $patient_name = null, $doctor_name = null, $details = []) {
        $messages = [
            'BOOK_APPOINTMENT' => "📅 New appointment booked",
            'CANCEL_APPOINTMENT' => "❌ Appointment cancelled",
            'RESCHEDULE_APPOINTMENT' => "📅 Appointment rescheduled",
            'COMPLETE_APPOINTMENT' => "✅ Appointment completed",
            'CONFIRM_APPOINTMENT' => "✅ Appointment confirmed"
        ];
        
        $message = $messages[$action] ?? "Appointment action: {$action}";
        
        if ($patient_name) {
            $message .= " - Patient: {$patient_name}";
        }
        
        if ($doctor_name) {
            $message .= " - Doctor: {$doctor_name}";
        }
        
        $logDetails = array_merge([
            'action' => $action,
            'appointment_id' => $appointment_id,
            'patient_name' => $patient_name,
            'doctor_name' => $doctor_name
        ], $details);
        
        self::writeLog($message, 'APPOINTMENT', 'appointments', $logDetails);
    }
    
    /**
     * Log order actions
     */
    public static function logOrder($action, $order_id = null, $total_amount = null, $details = []) {
        $messages = [
            'CREATE_ORDER' => "🛍️ New order created",
            'PAYMENT_SUCCESS' => "💳 Payment processed successfully",
            'PAYMENT_FAILED' => "❌ Payment failed",
            'ORDER_SHIPPED' => "📦 Order shipped",
            'ORDER_DELIVERED' => "✅ Order delivered",
            'ORDER_CANCELLED' => "❌ Order cancelled"
        ];
        
        $message = $messages[$action] ?? "Order action: {$action}";
        
        if ($order_id) {
            $message .= " - Order #{$order_id}";
        }
        
        if ($total_amount) {
            $message .= " - Amount: " . number_format($total_amount, 0, ',', '.') . " VND";
        }
        
        $logDetails = array_merge([
            'action' => $action,
            'order_id' => $order_id,
            'total_amount' => $total_amount
        ], $details);
        
        self::writeLog($message, 'ORDER', 'orders', $logDetails);
    }
    
    /**
     * Log email activities
     */
    public static function logEmail($action, $recipient, $subject, $success = true, $error = null) {
        $message = $success ? 
            "📧 Email sent successfully to {$recipient}" : 
            "❌ Failed to send email to {$recipient}";
            
        $message .= " - Subject: {$subject}";
        
        $details = [
            'action' => $action,
            'recipient' => $recipient,
            'subject' => $subject,
            'success' => $success,
            'error' => $error
        ];
        
        self::writeLog($message, 'EMAIL', 'email_activities', $details);
    }
    
    /**
     * Log system events
     */
    public static function logSystem($action, $description, $details = []) {
        $messages = [
            'SYSTEM_START' => "🚀 System started",
            'SYSTEM_SHUTDOWN' => "🛑 System shutdown",
            'DATABASE_BACKUP' => "💾 Database backup created",
            'CACHE_CLEAR' => "🧹 Cache cleared",
            'MAINTENANCE_MODE' => "🔧 Maintenance mode activated",
            'SECURITY_ALERT' => "🚨 Security alert"
        ];
        
        $message = $messages[$action] ?? $action;
        
        if ($description) {
            $message .= " - {$description}";
        }
        
        $logDetails = array_merge([
            'action' => $action,
            'description' => $description
        ], $details);
        
        self::writeLog($message, 'SYSTEM', 'system_events', $logDetails);
    }
    
    /**
     * Log errors with enhanced details
     */
    public static function logError($error, $context = '', $trace = null) {
        $message = "❌ Error: {$error}";
        
        if ($context) {
            $message .= " - Context: {$context}";
        }
        
        $details = [
            'error' => $error,
            'context' => $context,
            'trace' => $trace,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];
        
        self::writeLog($message, 'ERROR', 'errors', $details);
    }
    
    /**
     * Log security events
     */
    public static function logSecurity($action, $description, $severity = 'MEDIUM', $details = []) {
        $severityEmoji = [
            'LOW' => '🟡',
            'MEDIUM' => '🟠',
            'HIGH' => '🔴',
            'CRITICAL' => '🚨'
        ];
        
        $emoji = $severityEmoji[$severity] ?? '🔒';
        $message = "{$emoji} Security Event: {$action} - {$description}";
        
        $logDetails = array_merge([
            'action' => $action,
            'description' => $description,
            'severity' => $severity
        ], $details);
        
        self::writeLog($message, 'SECURITY', 'security_events', $logDetails);
    }
    
    /**
     * Log database operations
     */
    public static function logDatabase($operation, $table, $success = true, $affected_rows = 0, $details = []) {
        $message = $success ? 
            "✅ Database: {$operation} on {$table}" : 
            "❌ Database: Failed {$operation} on {$table}";
            
        if ($affected_rows > 0) {
            $message .= " - Affected rows: {$affected_rows}";
        }
        
        $logDetails = array_merge([
            'operation' => $operation,
            'table' => $table,
            'success' => $success,
            'affected_rows' => $affected_rows
        ], $details);
        
        self::writeLog($message, 'DATABASE', 'database_operations', $logDetails);
    }
}

// Backward compatibility functions
function writeLog($message, $type = 'INFO', $file = 'system') {
    EnhancedLogger::writeLog($message, $type, $file);
}

function logCartAction($action, $product_id = null, $quantity = null, $details = '') {
    EnhancedLogger::logCart($action, $product_id, null, $quantity, ['details' => $details]);
}

function logError($error, $context = '') {
    EnhancedLogger::logError($error, $context);
}

function logAPI($endpoint, $method, $data = '', $response = '') {
    EnhancedLogger::logAPI($endpoint, $method, $data, $response);
}

function logDatabase($query, $params = [], $result = '') {
    EnhancedLogger::logDatabase('QUERY', 'multiple', true, 0, [
        'query' => $query,
        'params' => $params,
        'result' => $result
    ]);
}
?> 