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
    $sql = "SELECT 
        d.doctor_id,
        COALESCE(ui.full_name, u.username) as full_name,
        COALESCE(s.name, 'Chưa phân chuyên khoa') as specialization,
        COALESCE(ui.profile_picture, '/assets/images/default-doctor.jpg') as profile_picture,
        d.clinic_id,
        COALESCE(c.name, 'Chưa phân phòng khám') as clinic_name,
        COALESCE(c.address, '') as clinic_address
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
            // Ensure all required fields exist
            $row['doctor_id'] = (int)$row['doctor_id'];
            $row['clinic_id'] = (int)$row['clinic_id'];
            $doctors[] = $row;
        }
    }
    
    echo json_encode([
        'success' => true,
        'doctors' => $doctors
    ]);
    
} catch (Exception $e) {
    error_log("Error in get-doctors.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Không thể tải danh sách bác sĩ. Vui lòng thử lại!'
    ]);
}
?> 