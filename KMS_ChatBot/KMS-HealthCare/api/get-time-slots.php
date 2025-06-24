<?php
include '../includes/db.php';
session_start();

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get parameters
$doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
$date = isset($_GET['date']) ? $_GET['date'] : '';

if (!$doctor_id || !$date) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

try {
    // Generate time slots (8:00 AM - 5:00 PM, every 30 minutes)
    $timeSlots = [];
    for ($hour = 8; $hour <= 17; $hour++) {
        for ($minute = 0; $minute < 60; $minute += 30) {
            if ($hour === 17 && $minute > 0) break; // Stop at 17:00
            
            $timeStr = sprintf('%02d:%02d', $hour, $minute);
            $timeSlots[] = $timeStr;
        }
    }
    
    // Check which slots are already booked
    $placeholders = str_repeat('?,', count($timeSlots) - 1) . '?';
    $datetimes = array_map(function($time) use ($date) {
        return $date . ' ' . $time . ':00';
    }, $timeSlots);
    
    $sql = "SELECT TIME(appointment_time) as booked_time 
            FROM appointments 
            WHERE doctor_id = ? 
            AND DATE(appointment_time) = ? 
            AND status IN ('pending', 'confirmed')
            AND appointment_time IN ($placeholders)";
    
    $params = array_merge([$doctor_id, $date], $datetimes);
    $types = 'is' . str_repeat('s', count($datetimes));
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    $bookedTimes = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $bookedTimes[] = substr($row['booked_time'], 0, 5); // HH:MM format
    }
    
    // Build response with availability
    $slots = [];
    foreach ($timeSlots as $time) {
        $slots[] = [
            'time' => $time,
            'booked' => in_array($time, $bookedTimes)
        ];
    }
    
    echo json_encode([
        'success' => true,
        'slots' => $slots
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading time slots: ' . $e->getMessage()
    ]);
}
?> 