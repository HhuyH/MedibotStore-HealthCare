<?php
require_once '../includes/db.php';

// Kiểm tra kết nối
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "<h2>Database Connection Test</h2>";
echo "Connected successfully to MySQL<br>";
echo "MySQL version: " . $conn->server_info . "<br>";
echo "Character set: " . $conn->character_set_name() . "<br><br>";

// Kiểm tra bảng products
echo "<h2>Products Table Structure</h2>";
$result = $conn->query("DESCRIBE products");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table><br>";
} else {
    echo "Error getting table structure: " . $conn->error . "<br>";
}

// Kiểm tra dữ liệu trong bảng products
echo "<h2>Sample Products Data</h2>";
$result = $conn->query("SELECT * FROM products LIMIT 5");
if ($result) {
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        // Print headers
        $row = $result->fetch_assoc();
        echo "<tr>";
        foreach ($row as $key => $value) {
            echo "<th>" . htmlspecialchars($key) . "</th>";
        }
        echo "</tr>";
        
        // Print first row
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
        
        // Print remaining rows
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No products found in database<br>";
    }
} else {
    echo "Error querying products: " . $conn->error . "<br>";
}

// Test specific product query
$test_id = 1;
echo "<h2>Test Product Query (ID: $test_id)</h2>";
$stmt = $conn->prepare("SELECT product_id, name, price, discount_price, stock FROM products WHERE product_id = ? AND is_active = 1");
if ($stmt) {
    $stmt->bind_param("i", $test_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        if ($product) {
            echo "Product found:<br>";
            echo "<pre>";
            print_r($product);
            echo "</pre>";
        } else {
            echo "No product found with ID: $test_id<br>";
        }
    } else {
        echo "Error executing query: " . $stmt->error . "<br>";
    }
} else {
    echo "Error preparing statement: " . $conn->error . "<br>";
}

$conn->close();
?>