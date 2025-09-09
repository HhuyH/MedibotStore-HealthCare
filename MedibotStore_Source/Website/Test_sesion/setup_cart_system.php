<?php
require_once 'includes/db.php';

echo "<h2>Setting up Cart System...</h2>";

try {
    // 1. Run setup_orders.sql
    echo "<p>1. Setting up orders tables...</p>";
    $sql = file_get_contents('database/setup_orders.sql');
    if ($conn->multi_query($sql)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->next_result());
    }
    echo "<p style='color: green;'>✓ Orders tables created successfully</p>";

    // 2. Add sample products
    echo "<p>2. Adding sample products...</p>";
    $sql = file_get_contents('database/sample_products_with_images.sql');
    if ($conn->multi_query($sql)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->next_result());
    }
    echo "<p style='color: green;'>✓ Sample products added successfully</p>";

    // 3. Check if we have products
    $result = $conn->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
    $count = $result->fetch_assoc()['count'];
    echo "<p>Total active products: <strong>$count</strong></p>";

    // 4. Check if we have categories
    $result = $conn->query("SELECT COUNT(*) as count FROM categories");
    $count = $result->fetch_assoc()['count'];
    echo "<p>Total categories: <strong>$count</strong></p>";

    echo "<h3 style='color: green;'>✅ Cart system setup completed successfully!</h3>";
    echo "<p><a href='shop.php'>Go to Shop</a> | <a href='cart.php'>Go to Cart</a></p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
    background: #f8f9fa;
}
h2 {
    color: #2c3e50;
    border-bottom: 2px solid #3498db;
    padding-bottom: 10px;
}
p {
    background: white;
    padding: 10px;
    border-radius: 5px;
    margin: 10px 0;
}
a {
    color: #3498db;
    text-decoration: none;
    font-weight: bold;
}
a:hover {
    text-decoration: underline;
}
</style> 