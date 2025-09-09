<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    die("❌ Please login first!");
}

$user_id = $_SESSION['user_id'];

echo "<h2>🔍 Cart Data Debug for User ID: {$user_id}</h2>";

// 1. Check orders table
echo "<h3>📋 Orders Table</h3>";
$sql = "SELECT * FROM orders WHERE user_id = ? AND status = 'cart'";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($orders)) {
    echo "<div style='color: orange;'>⚠️ No cart orders found</div>";
} else {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Order ID</th><th>Status</th><th>Total</th><th>Created</th></tr>";
    foreach ($orders as $order) {
        echo "<tr>";
        echo "<td>{$order['order_id']}</td>";
        echo "<td>{$order['status']}</td>";
        echo "<td>" . number_format($order['total']) . "đ</td>";
        echo "<td>{$order['order_date']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 2. Check order_items table
echo "<h3>📦 Order Items Table</h3>";
$sql = "
    SELECT oi.*, p.name, p.price as product_price, p.image_url
    FROM order_items oi 
    LEFT JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id IN (SELECT order_id FROM orders WHERE user_id = ? AND status = 'cart')
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($items)) {
    echo "<div style='color: orange;'>⚠️ No cart items found</div>";
} else {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Item ID</th><th>Product ID</th><th>Product Name</th><th>Quantity</th><th>Unit Price</th><th>Product Price</th><th>Total</th></tr>";
    foreach ($items as $item) {
        echo "<tr>";
        echo "<td>{$item['item_id']}</td>";
        echo "<td>{$item['product_id']}</td>";
        echo "<td>{$item['name']}</td>";
        echo "<td>{$item['quantity']}</td>";
        echo "<td>" . number_format($item['unit_price']) . "đ</td>";
        echo "<td>" . number_format($item['product_price']) . "đ</td>";
        echo "<td>" . number_format($item['quantity'] * $item['unit_price']) . "đ</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 3. Test the exact query from cart.php
echo "<h3>🔧 Exact Cart Query Result</h3>";
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
    echo "<div style='color: red;'>❌ No cart items from cart.php query</div>";
} else {
    echo "<div style='color: green;'>✅ Found " . count($cart_items) . " cart items</div>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Item ID</th><th>Product ID</th><th>Name</th><th>Quantity</th><th>Unit Price</th><th>Stock</th><th>Rating</th></tr>";
    foreach ($cart_items as $item) {
        echo "<tr>";
        echo "<td>{$item['item_id']}</td>";
        echo "<td>{$item['product_id']}</td>";
        echo "<td>" . htmlspecialchars($item['name']) . "</td>";
        echo "<td>{$item['quantity']}</td>";
        echo "<td>" . number_format($item['unit_price']) . "đ</td>";
        echo "<td>{$item['stock']}</td>";
        echo "<td>" . round($item['avg_rating'], 1) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show processed data
    echo "<h4>📊 Processed Data (After PHP Logic)</h4>";
    foreach ($cart_items as &$item) {
        $item['discount_percent'] = $item['avg_rating'] >= 4.5 ? 10 : 0;
        $item['discount_price'] = $item['discount_percent'] > 0 
            ? $item['price'] * (1 - $item['discount_percent']/100) 
            : null;
        
        if (empty($item['image_url'])) {
            $item['image_url'] = '/assets/images/product-placeholder.jpg';
        }
    }
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Product</th><th>Original Price</th><th>Unit Price</th><th>Discount</th><th>Final Price</th><th>Image</th></tr>";
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

// 4. Check products table
echo "<h3>🛍️ Products Info</h3>";
if (!empty($items)) {
    $product_ids = array_unique(array_column($items, 'product_id'));
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    
    $sql = "SELECT product_id, name, price, image_url, stock FROM products WHERE product_id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('i', count($product_ids)), ...$product_ids);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Product ID</th><th>Name</th><th>Price</th><th>Stock</th><th>Image</th></tr>";
    foreach ($products as $product) {
        echo "<tr>";
        echo "<td>{$product['product_id']}</td>";
        echo "<td>" . htmlspecialchars($product['name']) . "</td>";
        echo "<td>" . number_format($product['price']) . "đ</td>";
        echo "<td>{$product['stock']}</td>";
        echo "<td>" . basename($product['image_url'] ?: 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; }
table { width: 100%; margin: 10px 0; }
th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
th { background: #f5f5f5; }
h2, h3 { color: #333; }
h4 { color: #666; }
</style> 