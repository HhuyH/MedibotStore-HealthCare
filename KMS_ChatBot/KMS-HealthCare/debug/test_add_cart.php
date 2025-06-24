<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo "❌ Please login first";
    exit;
}

$user_id = $_SESSION['user_id'];
echo "<h2>🧪 Test Add to Cart API - User ID: {$user_id}</h2>";

// Test 1: Check if we have products
echo "<h3>1. Available Products</h3>";
$result = $conn->query("SELECT product_id, name, price, stock FROM products WHERE is_active = TRUE LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Product ID</th><th>Name</th><th>Price</th><th>Stock</th><th>Action</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['product_id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>" . number_format($row['price']) . "đ</td>";
        echo "<td>{$row['stock']}</td>";
        echo "<td><button onclick='testAddProduct({$row['product_id']})' class='btn'>Add to Cart</button></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ No products found";
}

// Test 2: Manual API call
echo "<h3>2. Manual API Test</h3>";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_add'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    
    echo "<h4>Testing Add Product ID: {$product_id}, Quantity: {$quantity}</h4>";
    
    // Simulate API call logic
    try {
        // Check product exists
        $stmt = $conn->prepare("
            SELECT 
                p.name, p.price, p.stock,
                COALESCE(AVG(pr.rating), 0) as avg_rating
            FROM products p
            LEFT JOIN product_reviews pr ON p.product_id = pr.product_id
            WHERE p.product_id = ? AND p.is_active = TRUE
            GROUP BY p.product_id
        ");
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        
        if (!$product) {
            echo "❌ Product not found";
        } else {
            echo "✅ Product found: {$product['name']}<br>";
            echo "✅ Price: " . number_format($product['price']) . "đ<br>";
            echo "✅ Stock: {$product['stock']}<br>";
            echo "✅ Rating: {$product['avg_rating']}<br>";
            
            if ($product['stock'] < $quantity) {
                echo "❌ Not enough stock";
            } else {
                // Check for existing cart
                $stmt = $conn->prepare("SELECT order_id FROM orders WHERE user_id = ? AND status = 'cart' LIMIT 1");
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $cart = $stmt->get_result()->fetch_assoc();
                
                if (!$cart) {
                    echo "📝 Creating new cart...<br>";
                    $stmt = $conn->prepare("INSERT INTO orders (user_id, status, total) VALUES (?, 'cart', 0)");
                    $stmt->bind_param('i', $user_id);
                    if ($stmt->execute()) {
                        $cart_id = $conn->insert_id;
                        echo "✅ Cart created with ID: {$cart_id}<br>";
                    } else {
                        echo "❌ Failed to create cart: " . $conn->error . "<br>";
                        exit;
                    }
                } else {
                    $cart_id = $cart['order_id'];
                    echo "✅ Using existing cart ID: {$cart_id}<br>";
                }
                
                // Calculate price with discount
                $discount_percent = $product['avg_rating'] >= 4.5 ? 10 : 0;
                $unit_price = $discount_percent > 0 
                    ? $product['price'] * (1 - $discount_percent/100) 
                    : $product['price'];
                
                echo "💰 Unit price: " . number_format($unit_price) . "đ";
                if ($discount_percent > 0) {
                    echo " (Discount: {$discount_percent}%)";
                }
                echo "<br>";
                
                // Check if product already in cart
                $stmt = $conn->prepare("SELECT item_id, quantity FROM order_items WHERE order_id = ? AND product_id = ?");
                $stmt->bind_param('ii', $cart_id, $product_id);
                $stmt->execute();
                $existing_item = $stmt->get_result()->fetch_assoc();
                
                if ($existing_item) {
                    echo "📦 Product already in cart, updating quantity...<br>";
                    $new_quantity = $existing_item['quantity'] + $quantity;
                    $stmt = $conn->prepare("UPDATE order_items SET quantity = ? WHERE item_id = ?");
                    $stmt->bind_param('ii', $new_quantity, $existing_item['item_id']);
                    if ($stmt->execute()) {
                        echo "✅ Updated quantity to: {$new_quantity}<br>";
                    } else {
                        echo "❌ Failed to update: " . $conn->error . "<br>";
                    }
                } else {
                    echo "📦 Adding new item to cart...<br>";
                    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param('iiid', $cart_id, $product_id, $quantity, $unit_price);
                    if ($stmt->execute()) {
                        echo "✅ Item added successfully!<br>";
                    } else {
                        echo "❌ Failed to add item: " . $conn->error . "<br>";
                    }
                }
                
                // Update cart total
                $stmt = $conn->prepare("
                    UPDATE orders 
                    SET total = (
                        SELECT SUM(quantity * unit_price) 
                        FROM order_items 
                        WHERE order_id = ?
                    ) 
                    WHERE order_id = ?
                ");
                $stmt->bind_param('ii', $cart_id, $cart_id);
                if ($stmt->execute()) {
                    echo "✅ Cart total updated<br>";
                } else {
                    echo "❌ Failed to update total: " . $conn->error . "<br>";
                }
                
                echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0;'>";
                echo "🎉 <strong>SUCCESS! Product added to cart successfully!</strong>";
                echo "</div>";
            }
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage();
    }
}

// Form to test
echo "<form method='POST'>";
echo "Product ID: <input type='number' name='product_id' value='1' min='1' required><br><br>";
echo "Quantity: <input type='number' name='quantity' value='1' min='1' required><br><br>";
echo "<input type='submit' name='test_add' value='Test Add to Cart' class='btn btn-primary'>";
echo "</form>";

?>

<script>
async function testAddProduct(productId) {
    try {
        const response = await fetch('/api/cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: 1
            })
        });
        
        const result = await response.json();
        alert(`API Result: ${result.success ? 'SUCCESS' : 'FAILED'}\nMessage: ${result.message}`);
        
        if (result.success) {
            location.reload();
        }
    } catch (error) {
        alert(`Error: ${error.message}`);
    }
}
</script>

<style>
body { padding: 20px; font-family: Arial, sans-serif; }
table { margin: 10px 0; }
th, td { padding: 8px; border: 1px solid #ddd; }
.btn { padding: 5px 10px; background: #007bff; color: white; border: none; cursor: pointer; margin: 5px; }
.btn:hover { background: #0056b3; }
input { padding: 5px; margin: 5px; }
</style>

<hr>
 