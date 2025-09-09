<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    die("❌ Please login first!");
}

$user_id = $_SESSION['user_id'];

echo "<h2>🧪 Simple Cart Test</h2>";

// Step 1: Get cart order
$order_sql = "SELECT order_id FROM orders WHERE user_id = ? AND status = 'cart' LIMIT 1";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param('i', $user_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result()->fetch_assoc();

if (!$order_result) {
    echo "<p>No cart order found</p>";
    exit;
}

$order_id = $order_result['order_id'];
echo "<p><strong>Cart Order ID:</strong> {$order_id}</p>";

// Step 2: Get order items
$items_sql = "SELECT * FROM order_items WHERE order_id = ? ORDER BY item_id DESC";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param('i', $order_id);
$items_stmt->execute();
$items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo "<h3>📦 Order Items:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Item ID</th><th>Product ID</th><th>Quantity</th><th>Unit Price</th></tr>";
foreach ($items as $item) {
    echo "<tr>";
    echo "<td>{$item['item_id']}</td>";
    echo "<td><strong>{$item['product_id']}</strong></td>";
    echo "<td>{$item['quantity']}</td>";
    echo "<td>" . number_format($item['unit_price']) . "đ</td>";
    echo "</tr>";
}
echo "</table>";

// Step 3: Get product info for each item separately
echo "<h3>🛍️ Products Info:</h3>";
foreach ($items as $item) {
    $product_id = $item['product_id'];
    
    $product_sql = "SELECT * FROM products WHERE product_id = ?";
    $product_stmt = $conn->prepare($product_sql);
    $product_stmt->bind_param('i', $product_id);
    $product_stmt->execute();
    $product = $product_stmt->get_result()->fetch_assoc();
    
    echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px 0;'>";
    echo "<h4>Item ID: {$item['item_id']} | Product ID: {$product_id}</h4>";
    
    if ($product) {
        echo "<p><strong>Name:</strong> " . htmlspecialchars($product['name']) . "</p>";
        echo "<p><strong>Price:</strong> " . number_format($product['price']) . "đ</p>";
        echo "<p><strong>Stock:</strong> {$product['stock']}</p>";
        echo "<p><strong>Image:</strong> " . htmlspecialchars($product['image_url']) . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Product not found!</p>";
    }
    echo "</div>";
}

// Step 4: Manual JOIN test
echo "<h3>🔗 Manual JOIN Test:</h3>";
$join_sql = "
    SELECT 
        oi.item_id,
        oi.product_id,
        oi.quantity,
        oi.unit_price,
        p.name,
        p.price,
        p.image_url
    FROM order_items oi, products p
    WHERE oi.order_id = ? 
    AND oi.product_id = p.product_id
    ORDER BY oi.item_id DESC
";

$join_stmt = $conn->prepare($join_sql);
$join_stmt->bind_param('i', $order_id);
$join_stmt->execute();
$join_results = $join_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Item ID</th><th>Product ID</th><th>Product Name</th><th>Quantity</th><th>Unit Price</th><th>Product Price</th></tr>";
foreach ($join_results as $result) {
    echo "<tr>";
    echo "<td>{$result['item_id']}</td>";
    echo "<td><strong>{$result['product_id']}</strong></td>";
    echo "<td><strong>" . htmlspecialchars($result['name']) . "</strong></td>";
    echo "<td>{$result['quantity']}</td>";
    echo "<td>" . number_format($result['unit_price']) . "đ</td>";
    echo "<td>" . number_format($result['price']) . "đ</td>";
    echo "</tr>";
}
echo "</table>";

?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; }
table { width: 100%; margin: 10px 0; border-collapse: collapse; }
th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
th { background: #f5f5f5; }
</style>

<p><a href="cart.php">← Back to Cart</a></p> 