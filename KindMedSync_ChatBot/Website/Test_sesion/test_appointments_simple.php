<?php
include 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

echo "<h1>Test Appointments Debug</h1>";
echo "<p><strong>User ID:</strong> $user_id</p>";

// Basic query to get appointments
$sql = "SELECT * FROM appointments WHERE user_id = ? ORDER BY appointment_time DESC";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo "<p style='color: red;'>SQL Prepare Error: " . $conn->error . "</p>";
} else {
    $stmt->bind_param('i', $user_id);
    
    if (!$stmt->execute()) {
        echo "<p style='color: red;'>SQL Execute Error: " . $stmt->error . "</p>";
    } else {
        $result = $stmt->get_result();
        $appointments = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        
        echo "<p><strong>Appointments found:</strong> " . count($appointments) . "</p>";
        
        if (!empty($appointments)) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Doctor ID</th><th>Clinic ID</th><th>Time</th><th>Status</th><th>Reason</th></tr>";
            
            foreach ($appointments as $apt) {
                echo "<tr>";
                echo "<td>" . $apt['appointment_id'] . "</td>";
                echo "<td>" . $apt['doctor_id'] . "</td>";
                echo "<td>" . $apt['clinic_id'] . "</td>";
                echo "<td>" . $apt['appointment_time'] . "</td>";
                echo "<td>" . $apt['status'] . "</td>";
                echo "<td>" . htmlspecialchars($apt['reason'] ?? '') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>No appointments found for user $user_id</p>";
        }
    }
}

// Check if user exists in users table
$user_check = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$user_check->bind_param('i', $user_id);
$user_check->execute();
$user_data = $user_check->get_result()->fetch_assoc();

echo "<h2>User Data Check:</h2>";
if ($user_data) {
    echo "<p>✅ User exists: " . htmlspecialchars($user_data['username']) . "</p>";
} else {
    echo "<p style='color: red;'>❌ User not found in database!</p>";
}

// Check all appointments table
echo "<h2>All Appointments (any user):</h2>";
$all_appointments = $conn->query("SELECT COUNT(*) as total FROM appointments");
if ($all_appointments) {
    $total = $all_appointments->fetch_assoc()['total'];
    echo "<p>Total appointments in database: $total</p>";
}

// Show some sample appointments
$sample = $conn->query("SELECT * FROM appointments ORDER BY appointment_id DESC LIMIT 5");
if ($sample && $sample->num_rows > 0) {
    echo "<h3>Sample appointments:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Doctor ID</th><th>Time</th><th>Status</th></tr>";
    while ($row = $sample->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['appointment_id'] . "</td>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['doctor_id'] . "</td>";
        echo "<td>" . $row['appointment_time'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?> 