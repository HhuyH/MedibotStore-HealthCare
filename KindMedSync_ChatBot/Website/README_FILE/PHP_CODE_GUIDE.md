# QickMed - Hướng dẫn Code PHP và Database

## 📚 Cấu trúc Code PHP

### 1. Kết nối Database (`includes/db.php`)

```php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'kms_website';

$conn = new mysqli($host, $username, $password, $database);
$conn->set_charset("utf8");
```

### 2. Các Hàm Tiện ích

#### Xử lý Input & Security

```php
// Sanitize input
function sanitize_input($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}

// Validate email
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Hash password
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}
```

#### Database Operations

```php
// Execute query with params
function query($sql, $params = array()) {
    global $conn;
    if (empty($params)) {
        return $conn->query($sql);
    }

    $stmt = $conn->prepare($sql);
    // ... bind parameters and execute
    return $stmt;
}

// Fetch one row
function fetch_one($sql, $params = array()) {
    $result = query($sql, $params);
    return $result->fetch_assoc();
}

// Fetch all rows
function fetch_all($sql, $params = array()) {
    $result = query($sql, $params);
    return $result->fetch_all(MYSQLI_ASSOC);
}
```

### 3. Ví dụ Queries Phổ biến

#### User Management

```php
// Get user info
$sql = "SELECT u.*, ui.full_name, ui.phone, r.role_name
        FROM users u
        LEFT JOIN users_info ui ON u.user_id = ui.user_id
        LEFT JOIN roles r ON u.role_id = r.role_id
        WHERE u.user_id = ?";

// Update user
$sql = "UPDATE users_info
        SET full_name = ?, phone = ?, date_of_birth = ?
        WHERE user_id = ?";
```

#### Product Management

```php
// Get products with filters
$sql = "SELECT p.*, pc.name as category_name,
               COALESCE(AVG(pr.rating), 0) as avg_rating,
               COUNT(pr.review_id) as review_count
        FROM products p
        LEFT JOIN product_categories pc ON p.category_id = pc.category_id
        LEFT JOIN product_reviews pr ON p.product_id = pr.product_id
        WHERE p.is_active = TRUE
        AND p.price BETWEEN ? AND ?
        GROUP BY p.product_id";

// Get product details
$sql = "SELECT p.*, m.active_ingredient, m.dosage_form
        FROM products p
        LEFT JOIN medicines m ON p.product_id = m.medicine_id
        WHERE p.product_id = ?";
```

#### Appointment Management

```php
// Get appointments
$sql = "SELECT a.*,
               COALESCE(ui_patient.full_name, u_patient.username) as patient_name,
               COALESCE(ui_doctor.full_name, u_doctor.username) as doctor_name,
               c.name as clinic_name
        FROM appointments a
        LEFT JOIN users_info ui_patient ON a.user_id = ui_patient.user_id
        LEFT JOIN users u_patient ON a.user_id = u_patient.user_id
        LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
        LEFT JOIN users u_doctor ON d.user_id = u_doctor.user_id
        LEFT JOIN users_info ui_doctor ON d.user_id = ui_doctor.user_id
        LEFT JOIN clinics c ON a.clinic_id = c.clinic_id
        ORDER BY a.appointment_time DESC";
```

#### Order Management

```php
// Get orders with customer info
$sql = "SELECT o.*, ui.full_name as customer_name
        FROM orders o
        LEFT JOIN users_info ui ON o.user_id = ui.user_id
        WHERE o.status != 'cart'
        ORDER BY o.order_date DESC";

// Get order items
$sql = "SELECT oi.*, p.name as product_name, p.image_url
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = ?";
```

### 4. API Endpoints

#### Cart API

```php
// Get cart
$sql = "SELECT oi.item_id, oi.product_id, oi.quantity,
               p.name, p.image_url, p.price,
               COALESCE(AVG(pr.rating), 0) as avg_rating
        FROM orders o
        JOIN order_items oi ON o.order_id = oi.order_id
        JOIN products p ON oi.product_id = p.product_id
        LEFT JOIN product_reviews pr ON p.product_id = pr.product_id
        WHERE o.user_id = ? AND o.status = 'cart'
        GROUP BY oi.item_id";
```

#### Search API

```php
// Search products
$sql = "SELECT p.id, p.name, p.slug, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.name LIKE ?
           OR p.description LIKE ?
           OR c.name LIKE ?
        LIMIT 5";
```

### 5. Admin Dashboard

#### Statistics Queries

```php
// Get total patients
$sql = "SELECT COUNT(*) as total FROM users WHERE role_id = 2";

// Get today's appointments
$sql = "SELECT COUNT(*) as total
        FROM appointments
        WHERE DATE(appointment_time) = CURDATE()";

// Get monthly revenue
$sql = "SELECT SUM(total) as revenue
        FROM orders
        WHERE DATE_FORMAT(order_date, '%Y-%m') = ?
        AND status = 'completed'";
```

## 🗄️ Database Maintenance

### 1. Backup Database

```php
// Create backup
$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

foreach ($tables as $table) {
    // Get create table syntax
    $result = $conn->query("SHOW CREATE TABLE $table");
    $row = $result->fetch_row();
    $sql .= "\n\n" . $row[1] . ";\n\n";

    // Get table data
    $result = $conn->query("SELECT * FROM $table");
    while ($row = $result->fetch_assoc()) {
        $sql .= "INSERT INTO $table VALUES(...);";
    }
}
```

### 2. Database Maintenance

```php
// Repair tables
foreach ($tables as $table) {
    $conn->query("REPAIR TABLE $table");
}

// Update statistics
$conn->query("ANALYZE TABLE blog_posts, products, appointments, users");
```

## 📊 Pagination Example

```php
// Count total records
$count_sql = "SELECT COUNT(*) as total FROM table WHERE condition";
$total_records = $conn->query($count_sql)->fetch_assoc()['total'];

// Calculate pagination
$limit = 10;
$total_pages = ceil($total_records / $limit);
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// Get paginated data
$sql = "SELECT * FROM table WHERE condition LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $limit, $offset);
```

## 🔒 Security Best Practices

1. **Prepared Statements**

   - Luôn sử dụng prepared statements cho queries có parameters
   - Tránh SQL injection

2. **Input Validation**

   - Sanitize tất cả input từ user
   - Validate dữ liệu trước khi lưu vào database

3. **Password Security**

   - Sử dụng `password_hash()` để hash password
   - Không bao giờ lưu plain text password

4. **Session Security**

   - Validate session
   - Regenerate session ID sau login
   - Cleanup expired sessions

5. **Error Handling**
   - Log errors thay vì hiển thị
   - Không show database errors cho users

## 🔍 Debug Tools

```php
// Debug mode in config
define('DEBUG_MODE', true);

// Log errors
error_log("Error message");

// Debug database queries
if (DEBUG_MODE) {
    echo "SQL: " . $sql . "<br>";
    echo "Params: " . print_r($params, true) . "<br>";
}
```

## 📝 Coding Standards

1. **File Structure**

   - Include required files at top
   - Define constants/functions
   - Main logic
   - HTML output

2. **Naming Conventions**

   - snake_case cho functions và variables
   - PascalCase cho classes
   - UPPERCASE cho constants

3. **Comments**

   - Document complex queries
   - Explain business logic
   - PHPDoc cho functions

4. **Error Handling**
   - Try-catch blocks cho database operations
   - Validate input data
   - Log errors appropriately

---

**Version:** 1.0.0  
**Last Updated:** 2024  
**Author:** Dalziel Development
