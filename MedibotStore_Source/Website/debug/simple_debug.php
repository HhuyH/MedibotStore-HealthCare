<?php
session_start();
require_once 'includes/db.php';

echo "<h2>🔧 Simple Debug - Cart System</h2>";

// Test connection
echo "<h3>1. Database Connection</h3>";
if ($conn->connect_error) {
    echo "❌ Connection failed: " . $conn->connect_error;
    exit;
} else {
    echo "✅ Database connected successfully<br>";
}

// Check user session
echo "<h3>2. User Session</h3>";
if (!isset($_SESSION['user_id'])) {
    echo "❌ No user session found<br>";
    echo "<a href='login.php'>Please login first</a>";
    exit;
} else {
    $user_id = $_SESSION['user_id'];
    echo "✅ User ID: {$user_id}<br>";
}

// Check tables exist
echo "<h3>3. Check Tables</h3>";
$tables = ['orders', 'order_items', 'products'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '{$table}'");
    if ($result->num_rows > 0) {
        echo "✅ Table '{$table}' exists<br>";
    } else {
        echo "❌ Table '{$table}' missing<br>";
    }
}

// Simple query test
echo "<h3>4. Simple Queries</h3>";

// Count orders for user
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE user_id = {$user_id}");
if ($result) {
    $count = $result->fetch_assoc()['count'];
    echo "✅ Total orders for user: {$count}<br>";
} else {
    echo "❌ Error counting orders: " . $conn->error . "<br>";
}

// Count cart orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE user_id = {$user_id} AND status = 'cart'");
if ($result) {
    $count = $result->fetch_assoc()['count'];
    echo "✅ Cart orders for user: {$count}<br>";
} else {
    echo "❌ Error counting cart orders: " . $conn->error . "<br>";
}

// Count order items
$result = $conn->query("SELECT COUNT(*) as count FROM order_items oi JOIN orders o ON oi.order_id = o.order_id WHERE o.user_id = {$user_id}");
if ($result) {
    $count = $result->fetch_assoc()['count'];
    echo "✅ Total order items for user: {$count}<br>";
} else {
    echo "❌ Error counting order items: " . $conn->error . "<br>";
}

// List all orders
echo "<h3>5. All Orders for User</h3>";
$result = $conn->query("SELECT order_id, status, total, order_date FROM orders WHERE user_id = {$user_id} ORDER BY order_date DESC");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Order ID</th><th>Status</th><th>Total</th><th>Order Date</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['order_id']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "<td>" . number_format($row['total']) . "đ</td>";
        echo "<td>{$row['order_date']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ No orders found or error: " . $conn->error . "<br>";
}

// List all order items for user
echo "<h3>6. All Order Items for User</h3>";
$result = $conn->query("
    SELECT oi.item_id, oi.order_id, oi.product_id, oi.quantity, oi.unit_price, o.status 
    FROM order_items oi 
    JOIN orders o ON oi.order_id = o.order_id 
    WHERE o.user_id = {$user_id} 
    ORDER BY oi.item_id DESC
");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Item ID</th><th>Order ID</th><th>Product ID</th><th>Quantity</th><th>Unit Price</th><th>Status</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['item_id']}</td>";
        echo "<td>{$row['order_id']}</td>";
        echo "<td>{$row['product_id']}</td>";
        echo "<td>{$row['quantity']}</td>";
        echo "<td>" . number_format($row['unit_price']) . "đ</td>";
        echo "<td>{$row['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ No order items found or error: " . $conn->error . "<br>";
}

echo "<hr>";
echo "<p><a href='cart.php'>← View Cart</a> | <a href='shop/products.php'>Shop Products →</a></p>";
?> 