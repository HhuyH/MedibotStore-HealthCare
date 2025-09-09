<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo "❌ Please login first";
    exit;
}

$user_id = $_SESSION['user_id'];
echo "<h2>🔧 Debug Add to Cart Button - User ID: {$user_id}</h2>";

// Get some products
$result = $conn->query("SELECT product_id, name, price, stock FROM products WHERE is_active = TRUE LIMIT 3");
$products = $result->fetch_all(MYSQLI_ASSOC);

echo "<h3>Test Products</h3>";
foreach ($products as $product) {
    echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0;'>";
    echo "<h4>{$product['name']}</h4>";
    echo "<p>Price: " . number_format($product['price']) . "đ</p>";
    echo "<p>Stock: {$product['stock']}</p>";
    echo "<button class='add-to-cart btn btn-primary' data-id='{$product['product_id']}'>Add to Cart</button>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>Current Cart Status</h3>";
echo "<div id='cart-status'></div>";

echo "<hr>";
echo "<h3>Network Logs</h3>";
echo "<div id='network-logs'></div>";

?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/cart-new.js"></script>

<script>
// Override console.log to show in page
const originalConsoleLog = console.log;
const originalConsoleError = console.error;

function logToPage(message, type = 'info') {
    const logs = document.getElementById('network-logs');
    const logElement = document.createElement('div');
    logElement.className = `alert alert-${type === 'error' ? 'danger' : 'info'} mt-2`;
    logElement.innerHTML = `<small>${new Date().toLocaleTimeString()}</small><br>${message}`;
    logs.appendChild(logElement);
}

console.log = function(...args) {
    originalConsoleLog.apply(console, args);
    logToPage(args.join(' '));
};

console.error = function(...args) {
    originalConsoleError.apply(console, args);
    logToPage(args.join(' '), 'error');
};

// Monitor fetch requests
const originalFetch = window.fetch;
window.fetch = function(...args) {
    const url = args[0];
    const options = args[1] || {};
    
    logToPage(`🚀 FETCH REQUEST: ${url}`);
    if (options.body) {
        logToPage(`📤 REQUEST BODY: ${options.body}`);
    }
    
    return originalFetch.apply(this, args)
        .then(response => {
            logToPage(`📥 RESPONSE STATUS: ${response.status}`);
            
            // Clone response to read it
            const responseClone = response.clone();
            responseClone.text().then(text => {
                logToPage(`📄 RESPONSE BODY: ${text}`);
            });
            
            return response;
        })
        .catch(error => {
            logToPage(`❌ FETCH ERROR: ${error.message}`, 'error');
            throw error;
        });
};

// Function to check cart status
async function checkCartStatus() {
    try {
        const response = await fetch('/api/cart.php');
        const data = await response.json();
        
        const cartStatus = document.getElementById('cart-status');
        cartStatus.innerHTML = `
            <div class="alert alert-info">
                <strong>Cart Status:</strong><br>
                Success: ${data.success}<br>
                Count: ${data.count || 0}<br>
                Items: ${data.items ? data.items.length : 0}<br>
                Total: ${data.total || 0}đ
            </div>
        `;
        
        if (data.items && data.items.length > 0) {
            cartStatus.innerHTML += '<h4>Cart Items:</h4>';
            data.items.forEach(item => {
                cartStatus.innerHTML += `<p>- ${item.name} x${item.quantity} = ${item.subtotal}đ</p>`;
            });
        }
    } catch (error) {
        document.getElementById('cart-status').innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
    }
}

// Check cart status on page load
checkCartStatus();

// Re-check cart status after each add to cart
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('add-to-cart')) {
        setTimeout(() => {
            logToPage('🔄 Rechecking cart status...');
            checkCartStatus();
        }, 2000);
    }
});

logToPage('🔧 Debug page loaded, CartManager initialized');
</script>

<style>
.btn { padding: 10px 15px; margin: 5px; }
#network-logs { max-height: 400px; overflow-y: auto; }
#cart-status { max-height: 300px; overflow-y: auto; }
</style>

<p><a href="cart.php">← View Cart</a> | <a href="simple_debug.php">Simple Debug →</a></p> 