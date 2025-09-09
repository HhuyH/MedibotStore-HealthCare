<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    die("❌ Please login first!");
}

$user_id = $_SESSION['user_id'];

echo "<h2>🔍 Quick Cart Debug - User ID: {$user_id}</h2>";

// 1. Check raw order_items data
echo "<h3>📊 Raw Order Items Data:</h3>";
$sql = "
    SELECT oi.*, o.status 
    FROM order_items oi 
    JOIN orders o ON oi.order_id = o.order_id 
    WHERE o.user_id = ? AND o.status = 'cart'
    ORDER BY oi.item_id DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$raw_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($raw_items)) {
    echo "<p style='color: red;'>❌ No cart items found</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f5f5f5;'><th>Item ID</th><th>Order ID</th><th>Product ID</th><th>Quantity</th><th>Unit Price</th></tr>";
    foreach ($raw_items as $item) {
        echo "<tr>";
        echo "<td>{$item['item_id']}</td>";
        echo "<td>{$item['order_id']}</td>";
        echo "<td><strong>{$item['product_id']}</strong></td>";
        echo "<td>{$item['quantity']}</td>";
        echo "<td>" . number_format($item['unit_price']) . "đ</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Check products for each product_id
    echo "<h3>🔍 Product Details for Each Product ID:</h3>";
    $product_ids = array_unique(array_column($raw_items, 'product_id'));
    
    foreach ($product_ids as $product_id) {
        echo "<h4>Product ID: {$product_id}</h4>";
        
        $product_sql = "SELECT * FROM products WHERE product_id = ?";
        $product_stmt = $conn->prepare($product_sql);
        $product_stmt->bind_param('i', $product_id);
        $product_stmt->execute();
        $product = $product_stmt->get_result()->fetch_assoc();
        
        if ($product) {
            echo "<table border='1' style='border-collapse: collapse; margin: 5px 0; width: 100%;'>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            echo "<tr><td><strong>Name</strong></td><td><strong>" . htmlspecialchars($product['name']) . "</strong></td></tr>";
            echo "<tr><td>Price</td><td>" . number_format($product['price']) . "đ</td></tr>";
            echo "<tr><td>Stock</td><td>{$product['stock']}</td></tr>";
            echo "<tr><td>Image URL</td><td>" . htmlspecialchars($product['image_url']) . "</td></tr>";
            echo "</table>";
        } else {
            echo "<p style='color: red;'>❌ Product not found!</p>";
        }
    }
    
    // 3. Test the exact cart.php query
    echo "<h3>🔧 Exact Cart.php Query Result:</h3>";
    $cart_sql = "
        SELECT 
            oi.item_id, 
            oi.product_id, 
            oi.quantity, 
            oi.unit_price,
            p.name, 
            p.image_url, 
            p.stock, 
            p.price,
            o.order_id,
            o.total,
            COALESCE(
                (SELECT AVG(rating) FROM product_reviews WHERE product_id = oi.product_id), 
                0
            ) as avg_rating
        FROM order_items oi
        INNER JOIN orders o ON oi.order_id = o.order_id
        INNER JOIN products p ON oi.product_id = p.product_id
        WHERE o.user_id = ? AND o.status = 'cart'
        ORDER BY oi.item_id DESC
    ";
    
    $cart_stmt = $conn->prepare($cart_sql);
    $cart_stmt->bind_param('i', $user_id);
    $cart_stmt->execute();
    $cart_items = $cart_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f5f5f5;'>";
    echo "<th>Item ID</th><th>Product ID</th><th>Product Name</th><th>Quantity</th><th>Unit Price</th><th>Product Price</th>";
    echo "</tr>";
    
    foreach ($cart_items as $item) {
        // Highlight if there's a mismatch
        $highlight = '';
        if (count($cart_items) > 1) {
            $first_name = $cart_items[0]['name'];
            if ($item['name'] == $first_name && $item['product_id'] != $cart_items[0]['product_id']) {
                $highlight = 'style="background: #ffcccc;"'; // Red highlight for duplicates
            }
        }
        
        echo "<tr {$highlight}>";
        echo "<td>{$item['item_id']}</td>";
        echo "<td><strong>{$item['product_id']}</strong></td>";
        echo "<td><strong>" . htmlspecialchars($item['name']) . "</strong></td>";
        echo "<td>{$item['quantity']}</td>";
        echo "<td>" . number_format($item['unit_price']) . "đ</td>";
        echo "<td>" . number_format($item['price']) . "đ</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 4. Check for potential data corruption
    echo "<h3>⚠️ Data Integrity Check:</h3>";
    $names = array_column($cart_items, 'name');
    $product_ids_from_query = array_column($cart_items, 'product_id');
    
    if (count(array_unique($names)) < count($names)) {
        echo "<p style='color: red;'>❌ PROBLEM FOUND: Duplicate product names in cart!</p>";
        echo "<p>Product IDs: " . implode(', ', $product_ids_from_query) . "</p>";
        echo "<p>Product Names: " . implode(' | ', $names) . "</p>";
    } else {
        echo "<p style='color: green;'>✅ No duplicate names found</p>";
    }
    
    if (count(array_unique($product_ids_from_query)) < count($product_ids_from_query)) {
        echo "<p style='color: red;'>❌ PROBLEM: Duplicate product IDs in cart!</p>";
    } else {
        echo "<p style='color: green;'>✅ All product IDs are unique</p>";
    }
}

?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; }
table { width: 100%; margin: 10px 0; border-collapse: collapse; }
th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
th { background: #f5f5f5; font-weight: bold; }
h2, h3, h4 { color: #333; }
</style>

<p><a href="cart.php">← Back to Cart</a> | <a href="logs/">View Logs</a></p> 