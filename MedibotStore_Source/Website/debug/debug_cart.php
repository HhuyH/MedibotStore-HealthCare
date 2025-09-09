<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo "❌ Chưa đăng nhập!";
    exit;
}

$user_id = $_SESSION['user_id'];

echo "<h2>🔍 Debug Cart System - User ID: {$user_id}</h2>";

// 1. Kiểm tra có orders với status='cart' không
echo "<h3>1. Kiểm tra Orders Table</h3>";
$sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo "❌ SQL Error: " . $conn->error . "<br>";
    exit;
}
$stmt->bind_param('i', $user_id);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($orders)) {
    echo "❌ Không có order nào cho user này<br>";
} else {
    echo "✅ Tìm thấy " . count($orders) . " orders:<br>";
    foreach ($orders as $order) {
        echo "- Order ID: {$order['order_id']}, Status: {$order['status']}, Total: {$order['total']}, Order Date: {$order['order_date']}<br>";
    }
}

// 2. Kiểm tra order_items
echo "<h3>2. Kiểm tra Order Items Table</h3>";
$sql = "SELECT oi.*, o.status as order_status FROM order_items oi 
        JOIN orders o ON oi.order_id = o.order_id 
        WHERE o.user_id = ? ORDER BY oi.item_id DESC";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo "❌ SQL Error: " . $conn->error . "<br>";
} else {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

if (isset($items)) {
    if (empty($items)) {
        echo "❌ Không có items nào trong giỏ hàng<br>";
    } else {
        echo "✅ Tìm thấy " . count($items) . " items:<br>";
        foreach ($items as $item) {
            echo "- Item ID: {$item['item_id']}, Order ID: {$item['order_id']}, Product ID: {$item['product_id']}, Quantity: {$item['quantity']}, Order Status: {$item['order_status']}<br>";
        }
    }
}

// 3. Kiểm tra query giống trong cart.php
echo "<h3>3. Kiểm tra Query giống Cart.php</h3>";
$sql = "
    SELECT 
        o.order_id, o.total,
        oi.item_id, oi.product_id, oi.quantity, oi.unit_price,
        p.name, p.image_url, p.stock, p.price,
        COALESCE(AVG(pr.rating), 0) as avg_rating
    FROM orders o 
    JOIN order_items oi ON o.order_id = oi.order_id 
    JOIN products p ON oi.product_id = p.product_id 
    LEFT JOIN product_reviews pr ON p.product_id = pr.product_id
    WHERE o.user_id = ? AND o.status = 'cart'
    GROUP BY oi.item_id, oi.product_id, oi.quantity, oi.unit_price, o.order_id, o.total, p.name, p.image_url, p.stock, p.price
    ORDER BY oi.item_id DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo "❌ SQL Error: " . $conn->error . "<br>";
    $cart_items = [];
} else {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

if (empty($cart_items)) {
    echo "❌ Query không trả về kết quả nào<br>";
    echo "Lý do có thể:<br>";
    echo "- Không có order nào với status='cart'<br>";
    echo "- Không có order_items nào trong cart<br>";
    echo "- Sản phẩm không tồn tại hoặc bị xóa<br>";
} else {
    echo "✅ Query trả về " . count($cart_items) . " sản phẩm:<br>";
    foreach ($cart_items as $item) {
        echo "- {$item['name']} x{$item['quantity']} = " . number_format($item['unit_price'] * $item['quantity']) . "đ<br>";
    }
}

// 4. Kiểm tra có products không
echo "<h3>4. Kiểm tra Products Table</h3>";
$sql = "SELECT COUNT(*) as total FROM products WHERE is_active = TRUE";
$result = $conn->query($sql);
$total_products = $result->fetch_assoc()['total'];
echo "✅ Tổng số sản phẩm active: {$total_products}<br>";

// 5. Test add sản phẩm
echo "<h3>5. Test Add Product</h3>";
echo "<button onclick='testAddProduct()' class='btn btn-primary'>Test Add Product ID 1</button>";
echo "<div id='test-result'></div>";

?>

<script>
async function testAddProduct() {
    try {
        const response = await fetch('/api/cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: 1,
                quantity: 1
            })
        });
        
        const result = await response.json();
        document.getElementById('test-result').innerHTML = `
            <div class="alert alert-${result.success ? 'success' : 'danger'} mt-3">
                ${result.message}
            </div>
        `;
        
        if (result.success) {
            setTimeout(() => location.reload(), 1000);
        }
    } catch (error) {
        document.getElementById('test-result').innerHTML = `
            <div class="alert alert-danger mt-3">
                Lỗi: ${error.message}
            </div>
        `;
    }
}
</script>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { padding: 20px; font-family: Arial, sans-serif; }
    h2, h3 { color: #333; }
    .btn { margin: 10px 0; }
</style>

<hr>
<p><a href="cart.php">← Xem Cart</a> | <a href="shop/products.php">Xem sản phẩm →</a></p> 