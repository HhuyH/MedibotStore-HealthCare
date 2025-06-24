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

try {
    // Get active doctors with clinic info
    $sql = "SELECT d.doctor_id, ui.full_name, s.name as specialization, ui.profile_picture, 
                   d.clinic_id, c.name as clinic_name, c.address as clinic_address
            FROM doctors d 
            LEFT JOIN users u ON d.user_id = u.user_id
            LEFT JOIN users_info ui ON u.user_id = ui.user_id
            LEFT JOIN specialties s ON d.specialty_id = s.specialty_id
            LEFT JOIN clinics c ON d.clinic_id = c.clinic_id 
            WHERE u.status = 'active'
            ORDER BY ui.full_name";
    
    $result = $conn->query($sql);
    $doctors = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $doctors[] = $row;
        }
    }
    
    echo json_encode([
        'success' => true,
        'doctors' => $doctors
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading doctors: ' . $e->getMessage()
    ]);
}
?> 