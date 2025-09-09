<?php
include 'includes/db.php';

echo "<h2>Debug Registration System</h2>";

// Test 1: Kiểm tra kết nối database
echo "<h3>1. Test Database Connection</h3>";
if ($conn) {
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
} else {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
    exit;
}

// Test 2: Kiểm tra cấu trúc bảng users
echo "<h3>2. Check Users Table Structure</h3>";
$result = $conn->query("DESCRIBE users");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ Cannot describe users table: " . $conn->error . "</p>";
}

// Test 3: Kiểm tra cấu trúc bảng users_info
echo "<h3>3. Check Users_Info Table Structure</h3>";
$result = $conn->query("DESCRIBE users_info");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ Cannot describe users_info table: " . $conn->error . "</p>";
}

// Test 4: Test INSERT query
echo "<h3>4. Test Registration Query</h3>";

$test_data = [
    'username' => 'test_user_' . time(),
    'email' => 'test' . time() . '@example.com',
    'password' => 'test123',
    'full_name' => 'Test User',
    'gender' => 'Nam',
    'phone' => '0123456789'
];

echo "<p>Test data:</p>";
echo "<pre>" . print_r($test_data, true) . "</pre>";

try {
    $conn->begin_transaction();
    
    // Test users table insert
    echo "<p>Testing users table insert...</p>";
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role_id, status, created_at) VALUES (?, ?, ?, 2, 'active', NOW())");
    if ($stmt) {
        $stmt->bind_param('sss', $test_data['username'], $test_data['email'], $test_data['password']);
        if ($stmt->execute()) {
            $user_id = $conn->insert_id;
            echo "<p style='color: green;'>✅ User created with ID: $user_id</p>";
            
            // Test users_info table insert
            echo "<p>Testing users_info table insert...</p>";
            $stmt2 = $conn->prepare("INSERT INTO users_info (user_id, full_name, gender, phone, created_at) VALUES (?, ?, ?, ?, NOW())");
            if ($stmt2) {
                $stmt2->bind_param('isss', $user_id, $test_data['full_name'], $test_data['gender'], $test_data['phone']);
                if ($stmt2->execute()) {
                    echo "<p style='color: green;'>✅ User info created successfully</p>";
                    
                    $conn->commit();
                    echo "<p style='color: green;'>✅ Transaction committed successfully</p>";
                    
                    // Clean up test data
                    $conn->query("DELETE FROM users_info WHERE user_id = $user_id");
                    $conn->query("DELETE FROM users WHERE user_id = $user_id");
                    echo "<p style='color: blue;'>ℹ️ Test data cleaned up</p>";
                    
                } else {
                    throw new Exception('Users_info insert failed: ' . $stmt2->error);
                }
            } else {
                throw new Exception('Users_info prepare failed: ' . $conn->error);
            }
        } else {
            throw new Exception('Users insert failed: ' . $stmt->error);
        }
    } else {
        throw new Exception('Users prepare failed: ' . $conn->error);
    }
    
} catch (Exception $e) {
    $conn->rollback();
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='register.php'>Back to Registration</a> | <a href='login.php'>Login</a></p>";
?> 