<?php
function writeLog($message, $type = 'INFO', $file = 'cart') {
    $timestamp = date('Y-m-d H:i:s');
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'GUEST';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    $logMessage = "[{$timestamp}] [{$type}] [User: {$user_id}] [IP: {$ip}] {$message}" . PHP_EOL;
    
    $logFile = __DIR__ . "/../../logs/{$file}_" . date('Y-m-d') . ".log";
    
    // Tạo thư mục logs nếu chưa có
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

function logCartAction($action, $product_id = null, $quantity = null, $details = '') {
    $message = "CART_ACTION: {$action}";
    if ($product_id) $message .= " | Product: {$product_id}";
    if ($quantity) $message .= " | Quantity: {$quantity}";
    if ($details) $message .= " | Details: {$details}";
    
    writeLog($message, 'CART', 'cart_actions');
}

function logError($error, $context = '') {
    $message = "ERROR: {$error}";
    if ($context) $message .= " | Context: {$context}";
    
    writeLog($message, 'ERROR', 'errors');
}

function logAPI($endpoint, $method, $data = '', $response = '') {
    $message = "API_CALL: {$method} {$endpoint}";
    if ($data) $message .= " | Request: " . json_encode($data);
    if ($response) $message .= " | Response: " . json_encode($response);
    
    writeLog($message, 'API', 'api_calls');
}

function logDatabase($query, $params = [], $result = '') {
    $message = "DB_QUERY: {$query}";
    if (!empty($params)) $message .= " | Params: " . json_encode($params);
    if ($result) $message .= " | Result: {$result}";
    
    writeLog($message, 'DB', 'database');
}
?> 