<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    die("❌ Please login first!");
}

$user_id = $_SESSION['user_id'];

echo "<h2>🔍 Cart Issue Debug - User ID: {$user_id}</h2>";

// 1. Raw database data
echo "<h3>📊 Raw Database Data</h3>";

// Check orders table
$sql = "SELECT * FROM orders WHERE user_id = ? AND status = 'cart'";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo "<h4>Orders Table:</h4>";
if (empty($orders)) {
    echo "<p style='color: orange;'>No cart orders found</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Order ID</th><th>User ID</th><th>Status</th><th>Total</th><th>Date</th></tr>";
    foreach ($orders as $order) {
        echo "<tr>";
        echo "<td>{$order['order_id']}</td>";
        echo "<td>{$order['user_id']}</td>";
        echo "<td>{$order['status']}</td>";
        echo "<td>" . number_format($order['total']) . "đ</td>";
        echo "<td>{$order['order_date']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check order_items table
if (!empty($orders)) {
    $order_id = $orders[0]['order_id'];
    
    echo "<h4>Order Items Table (Order ID: {$order_id}):</h4>";
    $sql = "SELECT * FROM order_items WHERE order_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (empty($items)) {
        echo "<p style='color: orange;'>No items in cart</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Item ID</th><th>Order ID</th><th>Product ID</th><th>Quantity</th><th>Unit Price</th></tr>";
        foreach ($items as $item) {
            echo "<tr>";
            echo "<td>{$item['item_id']}</td>";
            echo "<td>{$item['order_id']}</td>";
            echo "<td>{$item['product_id']}</td>";
            echo "<td>{$item['quantity']}</td>";
            echo "<td>" . number_format($item['unit_price']) . "đ</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check products table
    echo "<h4>Products Table:</h4>";
    $product_ids = array_column($items, 'product_id');
    if (!empty($product_ids)) {
        $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
        $sql = "SELECT * FROM products WHERE product_id IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(str_repeat('i', count($product_ids)), ...$product_ids);
        $stmt->execute();
        $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Product ID</th><th>Name</th><th>Price</th><th>Stock</th><th>Image URL</th></tr>";
        foreach ($products as $product) {
            echo "<tr>";
            echo "<td>{$product['product_id']}</td>";
            echo "<td>" . htmlspecialchars($product['name']) . "</td>";
            echo "<td>" . number_format($product['price']) . "đ</td>";
            echo "<td>{$product['stock']}</td>";
            echo "<td>" . htmlspecialchars($product['image_url']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

// 2. Test exact cart.php query
echo "<h3>🔧 Exact Cart.php Query Result</h3>";
$sql = "
    SELECT 
        o.order_id, o.total,
        oi.item_id, oi.product_id, oi.quantity, oi.unit_price,
        p.name, p.image_url, p.stock, p.price,
        COALESCE(
            (SELECT AVG(rating) FROM product_reviews WHERE product_id = p.product_id), 
            0
        ) as avg_rating
    FROM orders o 
    JOIN order_items oi ON o.order_id = oi.order_id 
    JOIN products p ON oi.product_id = p.product_id 
    WHERE o.user_id = ? AND o.status = 'cart'
    ORDER BY oi.item_id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($cart_items)) {
    echo "<p style='color: red;'>❌ No cart items from cart.php query</p>";
} else {
    echo "<p style='color: green;'>✅ Found " . count($cart_items) . " cart items</p>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Item ID</th><th>Product ID</th><th>Name</th><th>Quantity</th><th>Unit Price</th><th>Product Price</th><th>Stock</th><th>Image</th></tr>";
    foreach ($cart_items as $item) {
        echo "<tr>";
        echo "<td>{$item['item_id']}</td>";
        echo "<td>{$item['product_id']}</td>";
        echo "<td>" . htmlspecialchars($item['name']) . "</td>";
        echo "<td>{$item['quantity']}</td>";
        echo "<td>" . number_format($item['unit_price']) . "đ</td>";
        echo "<td>" . number_format($item['price']) . "đ</td>";
        echo "<td>{$item['stock']}</td>";
        echo "<td>" . basename($item['image_url'] ?: 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show processed data (after PHP logic)
    echo "<h4>📊 After PHP Processing:</h4>";
    foreach ($cart_items as &$item) {
        $item['discount_percent'] = $item['avg_rating'] >= 4.5 ? 10 : 0;
        $item['discount_price'] = $item['discount_percent'] > 0 
            ? $item['price'] * (1 - $item['discount_percent']/100) 
            : null;
        
        if (empty($item['image_url'])) {
            $item['image_url'] = '/assets/images/product-placeholder.jpg';
        }
    }
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Product</th><th>Original Price</th><th>Unit Price</th><th>Discount %</th><th>Final Price</th><th>Image</th></tr>";
    foreach ($cart_items as $item) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($item['name']) . "</td>";
        echo "<td>" . number_format($item['price']) . "đ</td>";
        echo "<td>" . number_format($item['unit_price']) . "đ</td>";
        echo "<td>{$item['discount_percent']}%</td>";
        echo "<td>" . number_format($item['discount_price'] ?: $item['price']) . "đ</td>";
        echo "<td>" . basename($item['image_url']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 3. Test cart count
echo "<h3>🔢 Cart Count Issue</h3>";
$sql = "
    SELECT COUNT(*) as item_count, SUM(quantity) as total_quantity
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    WHERE o.user_id = ? AND o.status = 'cart'
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$count_result = $stmt->get_result()->fetch_assoc();

echo "<p><strong>Item Count:</strong> {$count_result['item_count']}</p>";
echo "<p><strong>Total Quantity:</strong> {$count_result['total_quantity']}</p>";

// Check what getCartCount function returns
if (function_exists('getCartCount')) {
    $cart_count = getCartCount($user_id);
    echo "<p><strong>getCartCount() result:</strong> {$cart_count}</p>";
} else {
    echo "<p style='color: orange;'>getCartCount() function not available</p>";
}

?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; }
table { width: 100%; margin: 10px 0; border-collapse: collapse; }
th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
th { background: #f5f5f5; }
h2, h3, h4 { color: #333; }
</style>

<p><a href="cart.php">← Back to Cart</a> | <a href="logs/">View Logs</a></p> 