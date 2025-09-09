<?php
include 'includes/db.php';

echo "<h3>Database Connection Test - QickMed</h3>";

// Test connection
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $conn->connect_error . "</p>";
    exit;
} else {
    echo "<p style='color: green;'>✅ Database connected successfully!</p>";
}

// Check if tables exist
$tables = ['users', 'users_info', 'roles'];
echo "<h4>Table Structure Check:</h4>";

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Table '$table' exists</p>";
        
        // Show table structure
        $structure = $conn->query("DESCRIBE $table");
        echo "<details>";
        echo "<summary>Show $table structure</summary>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $structure->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</details>";
    } else {
        echo "<p style='color: red;'>❌ Table '$table' does not exist</p>";
    }
}

// Test a simple query
echo "<h4>Test Query:</h4>";
$test_query = $conn->query("SELECT COUNT(*) as count FROM users");
if ($test_query) {
    $count = $test_query->fetch_assoc();
    echo "<p style='color: green;'>✅ Users table query successful. Current user count: " . $count['count'] . "</p>";
} else {
    echo "<p style='color: red;'>❌ Query failed: " . $conn->error . "</p>";
}

// Test prepared statement
echo "<h4>Test Prepared Statement:</h4>";
$stmt = $conn->prepare("SELECT user_id, username, email FROM users LIMIT 1");
if ($stmt) {
    echo "<p style='color: green;'>✅ Prepared statement created successfully</p>";
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "<p>Sample user: " . htmlspecialchars($user['username']) . " (" . htmlspecialchars($user['email']) . ")</p>";
    } else {
        echo "<p>No users found in database</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Prepared statement failed: " . $conn->error . "</p>";
}

echo "<hr>";
echo "<p><a href='register.php'>Test Registration</a> | <a href='login.php'>Test Login</a></p>";
?> 