<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    die("❌ Please login first!");
}

$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cart Debug Log</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            padding: 20px; 
            background: #f8f9fa;
        }
        table { 
            width: 100%; 
            margin: 10px 0; 
            background: white;
            border-radius: 8px;
            overflow: hidden;
            border-collapse: collapse;
        }
        th, td { 
            padding: 12px; 
            border: 1px solid #ddd; 
            text-align: left; 
        }
        th { 
            background: #007bff;
            color: white;
            font-weight: 600;
        }
        h2, h3 { 
            color: #333; 
            margin-top: 30px;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            background: #28a745;
            color: white;
        }
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-primary {
            background: #007bff;
        }
        .log-entry {
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        #log-area {
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>

<h2>📝 Cart Debug Log - User ID: <?php echo $user_id; ?></h2>

<?php
// 1. Available Products
echo "<h3>🛍️ Available Products</h3>";
$sql = "SELECT product_id, name, price, stock, image_url FROM products ORDER BY product_id LIMIT 10";
$result = $conn->query($sql);
$products = $result->fetch_all(MYSQLI_ASSOC);

echo "<table>";
echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Stock</th><th>Image</th><th>Action</th></tr>";
foreach ($products as $product) {
    echo "<tr>";
    echo "<td>{$product['product_id']}</td>";
    echo "<td>" . htmlspecialchars($product['name']) . "</td>";
    echo "<td>" . number_format($product['price']) . "đ</td>";
    echo "<td>{$product['stock']}</td>";
    echo "<td>" . basename($product['image_url'] ?: 'N/A') . "</td>";
    echo "<td><button onclick='addToCartAPI({$product['product_id']})' class='btn'>Add</button></td>";
    echo "</tr>";
}
echo "</table>";

// 2. Current Cart
echo "<h3>🛒 Current Cart Status</h3>";
$sql = "
    SELECT 
        o.order_id, o.total,
        oi.item_id, oi.product_id, oi.quantity, oi.unit_price,
        p.name, p.image_url, p.stock, p.price
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
    echo "<div style='color: orange; padding: 10px; background: #fff3cd;'>⚠️ Cart is empty</div>";
} else {
    echo "<div style='color: green; padding: 10px; background: #d4edda;'>✅ Found " . count($cart_items) . " items</div>";
    echo "<table>";
    echo "<tr><th>Item ID</th><th>Product ID</th><th>Name</th><th>Qty</th><th>Unit Price</th><th>Original Price</th><th>Image</th></tr>";
    foreach ($cart_items as $item) {
        echo "<tr>";
        echo "<td>{$item['item_id']}</td>";
        echo "<td>{$item['product_id']}</td>";
        echo "<td>" . htmlspecialchars($item['name']) . "</td>";
        echo "<td>{$item['quantity']}</td>";
        echo "<td>" . number_format($item['unit_price']) . "đ</td>";
        echo "<td>" . number_format($item['price']) . "đ</td>";
        echo "<td>" . basename($item['image_url'] ?: 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>

<h3>📋 Live Log</h3>
<div id='log-area' style='height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f8f9fa;'>
    <div class='log-entry'>🚀 Log started at <?php echo date('Y-m-d H:i:s'); ?></div>
</div>

<h3>🛠️ Actions</h3>
<button onclick='clearCart()' class='btn btn-danger' style='margin-right: 10px;'>Clear Cart</button>
<button onclick='location.reload()' class='btn btn-primary'>Refresh</button>

<script>
function logMessage(message, type = 'info') {
    const logArea = document.getElementById('log-area');
    const timestamp = new Date().toLocaleTimeString();
    const colors = {
        info: '#17a2b8',
        success: '#28a745', 
        error: '#dc3545',
        warning: '#ffc107'
    };
    
    const logEntry = document.createElement('div');
    logEntry.className = 'log-entry';
    logEntry.style.cssText = `
        margin: 5px 0;
        padding: 5px 10px;
        border-left: 4px solid ${colors[type]};
        background: white;
        border-radius: 4px;
    `;
    logEntry.innerHTML = `<strong>[${timestamp}]</strong> ${message}`;
    
    logArea.appendChild(logEntry);
    logArea.scrollTop = logArea.scrollHeight;
}

async function addToCartAPI(productId) {
    logMessage(`🔄 Adding product ${productId} to cart...`, 'info');
    
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
        
        logMessage(`📡 Response status: ${response.status}`, 'info');
        
        const data = await response.json();
        logMessage(`📋 Response: ${JSON.stringify(data)}`, 'info');
        
        if (data.success) {
            logMessage(`✅ SUCCESS: ${data.message}`, 'success');
            setTimeout(() => {
                logMessage('🔄 Reloading...', 'info');
                location.reload();
            }, 2000);
        } else {
            logMessage(`❌ ERROR: ${data.message}`, 'error');
        }
        
    } catch (error) {
        logMessage(`💥 Exception: ${error.message}`, 'error');
    }
}

async function clearCart() {
    if (!confirm('Clear cart?')) return;
    
    logMessage('🗑️ Clearing cart...', 'warning');
    
    try {
        const response = await fetch('/api/cart.php?action=clear', {
            method: 'POST'
        });
        
        const data = await response.json();
        
        if (data.success) {
            logMessage('✅ Cart cleared', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            logMessage(`❌ Failed: ${data.message}`, 'error');
        }
    } catch (error) {
        logMessage(`💥 Exception: ${error.message}`, 'error');
    }
}

logMessage('📄 Page loaded', 'success');
</script>

</body>
</html> 