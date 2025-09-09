<?php
require_once '../includes/db.php';

function testTableExists($conn, $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $result->num_rows > 0;
}

function testTableStructure($conn, $tableName) {
    $result = $conn->query("DESCRIBE $tableName");
    if (!$result) {
        return false;
    }
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    return $columns;
}

// Test database connection
echo "<h2>Database Connection Test</h2>";
if ($conn->connect_error) {
    echo "❌ Connection failed: " . $conn->connect_error;
} else {
    echo "✅ Database connection successful<br>";
    echo "Connected to database: " . $database . "<br>";
}

// Test required tables
echo "<h2>Table Existence Test</h2>";
$requiredTables = ['doctors', 'users', 'users_info', 'specialties', 'clinics'];
foreach ($requiredTables as $table) {
    if (testTableExists($conn, $table)) {
        echo "✅ Table '$table' exists<br>";
        $columns = testTableStructure($conn, $table);
        echo "Columns: " . implode(", ", $columns) . "<br><br>";
    } else {
        echo "❌ Table '$table' does not exist<br>";
    }
}

// Test data existence
echo "<h2>Data Existence Test</h2>";
$queries = [
    'users' => "SELECT COUNT(*) as count FROM users WHERE status = 'active'",
    'doctors' => "SELECT COUNT(*) as count FROM doctors",
    'specialties' => "SELECT COUNT(*) as count FROM specialties",
    'clinics' => "SELECT COUNT(*) as count FROM clinics"
];

foreach ($queries as $table => $query) {
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✅ {$table}: {$row['count']} records<br>";
    } else {
        echo "❌ Error checking {$table}: " . $conn->error . "<br>";
    }
}

// Test the actual doctors query
echo "<h2>Doctors Query Test</h2>";
$query = "SELECT 
    d.doctor_id,
    d.biography,
    u.username,
    u.email,
    ui.full_name,
    ui.avatar,
    s.name as specialty_name,
    s.description as specialty_description,
    c.name as clinic_name,
    c.address as clinic_address
FROM doctors d
JOIN users u ON d.user_id = u.user_id
LEFT JOIN users_info ui ON u.user_id = ui.user_id
LEFT JOIN specialties s ON d.specialty_id = s.specialty_id
LEFT JOIN clinics c ON d.clinic_id = c.clinic_id
WHERE u.status = 'active'
LIMIT 1";

$result = $conn->query($query);
if (!$result) {
    echo "❌ Error in doctors query: " . $conn->error . "<br>";
} else {
    $doctor = $result->fetch_assoc();
    if ($doctor) {
        echo "✅ Sample doctor data retrieved successfully:<br>";
        echo "<pre>" . print_r($doctor, true) . "</pre>";
    } else {
        echo "⚠️ No active doctors found in the database<br>";
    }
}
?>