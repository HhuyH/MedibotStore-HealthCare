# Các Hàm Tính Toán Trong Hệ Thống QickMed

## 1. Tính Toán Giỏ Hàng (Cart Calculations)

### 1.1. Tính Giá Sản Phẩm (Product Price)

```php
function calculateDiscountPrice($price, $rating) {
    // Kiểm tra cấu hình giảm giá tự động
    if (!defined('ENABLE_AUTO_DISCOUNT') || !ENABLE_AUTO_DISCOUNT) {
        return [
            'discount_percent' => 0,
            'discount_price' => null,
            'original_price' => $price
        ];
    }

    // Tính giảm giá dựa trên rating
    $discount_percent = $rating >= (defined('AUTO_DISCOUNT_MIN_RATING') ? AUTO_DISCOUNT_MIN_RATING : 4.5)
        ? (defined('AUTO_DISCOUNT_PERCENT') ? AUTO_DISCOUNT_PERCENT : 10)
        : 0;
    $discount_price = $discount_percent > 0
        ? $price * (1 - $discount_percent/100)
        : null;

    return [
        'discount_percent' => $discount_percent,
        'discount_price' => $discount_price,
        'original_price' => $price
    ];
}
```

### 1.2. Tính Tổng Giỏ Hàng (Cart Total)

```php
function updateCartTotal($cart_id) {
    global $conn;

    // Tính tổng tiền từ các items trong giỏ hàng
    $stmt = $conn->prepare("
        SELECT SUM(quantity * unit_price) as total
        FROM order_items
        WHERE order_id = ?
    ");
    $stmt->bind_param('i', $cart_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $total = $result['total'] ?: 0;

    // Cập nhật tổng tiền vào bảng orders
    $stmt = $conn->prepare("UPDATE orders SET total = ? WHERE order_id = ?");
    $stmt->bind_param('di', $total, $cart_id);
    $stmt->execute();
}
```

### 1.3. Đếm Số Lượng Sản Phẩm (Cart Count)

```php
function getCartCount($user_id) {
    global $conn;

    $stmt = $conn->prepare("
        SELECT SUM(oi.quantity) as count
        FROM orders o
        JOIN order_items oi ON o.order_id = oi.order_id
        WHERE o.user_id = ? AND o.status = 'cart'
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    return (int)($result['count'] ?: 0);
}
```

## 2. Tính Toán Đơn Hàng (Order Calculations)

### 2.1. Tính Tổng Đơn Hàng (Order Total)

```php
// Tính tổng đơn hàng bao gồm phí giao hàng
$cart_total = $cart_items[0]['total'];
$shipping_fee = 20000; // Phí giao hàng cố định 20,000 VND
$final_total = $cart_total + $shipping_fee;
```

## 3. Thống Kê Admin (Admin Statistics)

### 3.1. Thống Kê Đơn Hàng (Order Statistics)

```sql
SELECT
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
    SUM(CASE WHEN status = 'completed' THEN total ELSE 0 END) as total_revenue
FROM orders
WHERE status != 'cart'
```

### 3.2. Thống Kê Lịch Hẹn (Appointment Statistics)

```sql
SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
    SUM(CASE WHEN DATE(appointment_time) = CURDATE() THEN 1 ELSE 0 END) as today
FROM appointments
```

### 3.3. Thống Kê Người Dùng (User Statistics)

```php
// Thống kê hoạt động người dùng
$stats = [
    'total_appointments' => $conn->query("SELECT COUNT(*) as count FROM appointments WHERE user_id = $user_id")->fetch_assoc()['count'],
    'completed_appointments' => $conn->query("SELECT COUNT(*) as count FROM appointments WHERE user_id = $user_id AND status = 'completed'")->fetch_assoc()['count'],
    'total_orders' => $conn->query("SELECT COUNT(*) as count FROM orders WHERE user_id = $user_id")->fetch_assoc()['count'],
    'total_spent' => $conn->query("SELECT COALESCE(SUM(total), 0) as total FROM orders WHERE user_id = $user_id AND status = 'completed'")->fetch_assoc()['total']
];
```

## 4. Phân Trang (Pagination Calculations)

### 4.1. Tính Số Trang (Page Count)

```php
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10; // Số items mỗi trang
$offset = ($page - 1) * $per_page;
$total_pages = ceil($total_records / $per_page);
```

## 5. Thống Kê Hệ Thống (System Statistics)

### 5.1. Thống Kê Disk Space

```php
// Kiểm tra disk space
$disk_free = disk_free_space('.');
$disk_total = disk_total_space('.');
$disk_used = $disk_total - $disk_free;
$disk_usage_percent = ($disk_used / $disk_total) * 100;
```

### 5.2. Thống Kê Database

```php
// Thống kê số lượng records trong các bảng
$db_stats = [
    'users' => $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'],
    'products' => $conn->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'],
    'appointments' => $conn->query("SELECT COUNT(*) as total FROM appointments")->fetch_assoc()['total'],
    'blog_posts' => $conn->query("SELECT COUNT(*) as total FROM blog_posts")->fetch_assoc()['total']
];
```

## 6. Format Số (Number Formatting)

### 6.1. Format Tiền Tệ (Currency)

```php
// Format số tiền với đơn vị VND
echo number_format($amount, 0, ',', '.') . 'đ';
```

### 6.2. Format Rating

```php
// Format rating với 1 chữ số thập phân
function formatRating($rating) {
    return number_format($rating, 1);
}
```
