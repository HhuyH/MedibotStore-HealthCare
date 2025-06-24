<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'kms';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// Function to sanitize input
function sanitize_input($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}

// Function to validate email
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to hash password
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Function to verify password
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// Debug mode
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    echo "Database connected successfully<br>";
    echo "Current character set: " . $conn->character_set_name() . "<br>";
}

// Hàm escape string an toàn
function escape_string($str) {
    global $conn;
    return $conn->real_escape_string($str);
}

// Hàm format tiền tệ
function format_currency($amount) {
    return number_format($amount, 0, ',', '.') . 'đ';
}

// Hàm format ngày giờ
function format_datetime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

// Hàm thực thi câu lệnh SQL an toàn
function query($sql, $params = array()) {
    global $conn;
    if (empty($params)) {
        return $conn->query($sql);
    }
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    if (!empty($params)) {
        $types = '';
        $values = array();
        
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
            $values[] = $param;
        }
        
        $refs = array();
        $refs[] = $types;
        foreach ($values as $key => $value) {
            $refs[] = &$values[$key];
        }
        
        call_user_func_array(array($stmt, 'bind_param'), $refs);
    }
    
    $stmt->execute();
    return $stmt;
}

// Hàm lấy một dòng dữ liệu
function fetch_one($sql, $params = array()) {
    $result = query($sql, $params);
    if ($result instanceof mysqli_stmt) {
        $result = $result->get_result();
    }
    return $result->fetch_assoc();
}

// Hàm lấy nhiều dòng dữ liệu
function fetch_all($sql, $params = array()) {
    $result = query($sql, $params);
    if ($result instanceof mysqli_stmt) {
        $result = $result->get_result();
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}
?> 