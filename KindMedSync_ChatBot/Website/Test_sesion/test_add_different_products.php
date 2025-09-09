<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions/logger.php';

if (!isset($_SESSION['user_id'])) {
    die("❌ Please login first!");
}

$user_id = $_SESSION['user_id'];

echo "<h2>🧪 Test Adding Different Products</h2>";

// Get some different products
$products_sql = "SELECT product_id, name, price FROM products LIMIT 5";
$products_result = $conn->query($products_sql);
$products = $products_result->fetch_all(MYSQLI_ASSOC);

echo "<h3>Available Products:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Product ID</th><th>Name</th><th>Price</th><th>Action</th></tr>";
foreach ($products as $product) {
    echo "<tr>";
    echo "<td>{$product['product_id']}</td>";
    echo "<td>" . htmlspecialchars($product['name']) . "</td>";
    echo "<td>" . number_format($product['price']) . "đ</td>";
    echo "<td><button onclick='addToCart({$product['product_id']})'>Add to Cart</button></td>";
    echo "</tr>";
}
echo "</table>";

// Handle add to cart
if ($_POST['action'] == 'add_to_cart' && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    
    writeLog("TEST_ADD: Adding product {$product_id} to cart for user {$user_id}");
    
    // Call the cart API
    $api_url = 'http://localhost/api/cart.php';
    $post_data = [
        'action' => 'add_to_cart',
        'product_id' => $product_id,
        'quantity' => 1
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px 0;'>";
    echo "<h4>API Response (HTTP {$http_code}):</h4>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    echo "</div>";
    
    writeLog("TEST_ADD: API response - HTTP {$http_code}: " . substr($response, 0, 200));
}

// Show current cart
echo "<h3>🛒 Current Cart:</h3>";
$cart_sql = "
    SELECT 
        oi.item_id, oi.product_id, oi.quantity, oi.unit_price,
        p.name, p.price
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    JOIN products p ON oi.product_id = p.product_id
    WHERE o.user_id = ? AND o.status = 'cart'
    ORDER BY oi.item_id DESC
";
$cart_stmt = $conn->prepare($cart_sql);
$cart_stmt->bind_param('i', $user_id);
$cart_stmt->execute();
$cart_items = $cart_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($cart_items)) {
    echo "<p>Cart is empty</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Item ID</th><th>Product ID</th><th>Product Name</th><th>Quantity</th><th>Unit Price</th><th>Product Price</th></tr>";
    foreach ($cart_items as $item) {
        echo "<tr>";
        echo "<td>{$item['item_id']}</td>";
        echo "<td>{$item['product_id']}</td>";
        echo "<td>" . htmlspecialchars($item['name']) . "</td>";
        echo "<td>{$item['quantity']}</td>";
        echo "<td>" . number_format($item['unit_price']) . "đ</td>";
        echo "<td>" . number_format($item['price']) . "đ</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Clear cart button
echo "<h3>🗑️ Cart Management:</h3>";
echo "<button onclick='clearCart()' style='background: red; color: white; padding: 10px; border: none; border-radius: 5px;'>Clear Cart</button>";

if ($_POST['action'] == 'clear_cart') {
    $clear_sql = "DELETE FROM order_items WHERE order_id IN (SELECT order_id FROM orders WHERE user_id = ? AND status = 'cart')";
    $clear_stmt = $conn->prepare($clear_sql);
    $clear_stmt->bind_param('i', $user_id);
    $clear_stmt->execute();
    
    writeLog("TEST_CLEAR: Cleared cart for user {$user_id}");
    echo "<p style='color: green;'>✅ Cart cleared!</p>";
    echo "<script>setTimeout(() => location.reload(), 1000);</script>";
}

?>

<script>
function addToCart(productId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="add_to_cart">
        <input type="hidden" name="product_id" value="${productId}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function clearCart() {
    if (confirm('Clear all cart items?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="clear_cart">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<style>
body { font-family: Arial, sans-serif; padding: 20px; }
table { width: 100%; margin: 10px 0; border-collapse: collapse; }
th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
th { background: #f5f5f5; }
button { padding: 5px 10px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; }
button:hover { background: #0056b3; }
</style>

<p><a href="cart.php">← Back to Cart</a> | <a href="logs/">View Logs</a></p> 