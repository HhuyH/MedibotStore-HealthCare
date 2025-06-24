<?php
require_once 'includes/db.php';

echo "<h2>Fix Appointments Data</h2>";

// 1. Kiểm tra và tạo users cho doctors nếu chưa có
echo "<h3>Step 1: Creating Users for Doctors</h3>";

// Lấy danh sách doctors chưa có user
$doctors_without_users = $conn->query("
    SELECT d.doctor_id, d.user_id, d.specialty_id, d.clinic_id
    FROM doctors d
    LEFT JOIN users u ON d.user_id = u.user_id
    WHERE u.user_id IS NULL
");

if ($doctors_without_users && $doctors_without_users->num_rows > 0) {
    echo "Found " . $doctors_without_users->num_rows . " doctors without users. Creating users...<br>";
    
    while ($doctor = $doctors_without_users->fetch_assoc()) {
        // Tạo user mới cho doctor
        $username = "doctor" . $doctor['doctor_id'];
        $email = "doctor" . $doctor['doctor_id'] . "@clinic.com";
        $password = password_hash("123456", PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role_id, status) VALUES (?, ?, ?, 3, 'active')");
        $stmt->bind_param("sss", $username, $email, $password);
        
        if ($stmt->execute()) {
            $new_user_id = $conn->insert_id;
            
            // Cập nhật doctor với user_id mới
            $update_stmt = $conn->prepare("UPDATE doctors SET user_id = ? WHERE doctor_id = ?");
            $update_stmt->bind_param("ii", $new_user_id, $doctor['doctor_id']);
            $update_stmt->execute();
            
            // Tạo users_info cho doctor
            $doctor_names = ["Dr. Nguyễn Văn A", "Dr. Trần Thị B", "Dr. Lê Văn C", "Dr. Phạm Thị D", "Dr. Hoàng Văn E"];
            $full_name = $doctor_names[($doctor['doctor_id'] - 1) % count($doctor_names)];
            
            $info_stmt = $conn->prepare("INSERT INTO users_info (user_id, full_name, gender) VALUES (?, ?, 'Nam')");
            $info_stmt->bind_param("is", $new_user_id, $full_name);
            $info_stmt->execute();
            
            echo "✅ Created user for Doctor ID {$doctor['doctor_id']}: $username ($full_name)<br>";
        } else {
            echo "❌ Failed to create user for Doctor ID {$doctor['doctor_id']}<br>";
        }
    }
} else {
    echo "✅ All doctors already have users<br>";
}

// 2. Kiểm tra specialties table
echo "<h3>Step 2: Checking Specialties</h3>";
try {
    $specialties_count = $conn->query("SELECT COUNT(*) as count FROM specialties")->fetch_assoc()['count'];
    if ($specialties_count == 0) {
        echo "Creating sample specialties...<br>";
        $specialties = [
            "Nội khoa",
            "Ngoại khoa", 
            "Sản phụ khoa",
            "Nhi khoa",
            "Mắt",
            "Tai mũi họng",
            "Da liễu",
            "Tim mạch"
        ];
        
        foreach ($specialties as $specialty) {
            $stmt = $conn->prepare("INSERT INTO specialties (name, description) VALUES (?, ?)");
            $desc = "Chuyên khoa " . $specialty;
            $stmt->bind_param("ss", $specialty, $desc);
            $stmt->execute();
        }
        echo "✅ Created " . count($specialties) . " specialties<br>";
    } else {
        echo "✅ Specialties table has $specialties_count records<br>";
    }
} catch (Exception $e) {
    echo "❌ Error with specialties: " . $e->getMessage() . "<br>";
}

// 3. Cập nhật specialty_id cho doctors nếu cần
echo "<h3>Step 3: Updating Doctor Specialties</h3>";
$doctors_no_specialty = $conn->query("SELECT doctor_id FROM doctors WHERE specialty_id IS NULL OR specialty_id = 0");
if ($doctors_no_specialty && $doctors_no_specialty->num_rows > 0) {
    $specialties = $conn->query("SELECT specialty_id FROM specialties ORDER BY specialty_id")->fetch_all(MYSQLI_ASSOC);
    
    while ($doctor = $doctors_no_specialty->fetch_assoc()) {
        $random_specialty = $specialties[array_rand($specialties)]['specialty_id'];
        $stmt = $conn->prepare("UPDATE doctors SET specialty_id = ? WHERE doctor_id = ?");
        $stmt->bind_param("ii", $random_specialty, $doctor['doctor_id']);
        $stmt->execute();
        echo "✅ Updated specialty for Doctor ID {$doctor['doctor_id']}<br>";
    }
} else {
    echo "✅ All doctors have specialties<br>";
}

// 4. Test query sau khi sửa
echo "<h3>Step 4: Testing Fixed Query</h3>";
try {
    $sql = "SELECT a.*, 
               COALESCE(ui_patient.full_name, u_patient.username, gu.full_name) as patient_name,
               COALESCE(u_patient.phone_number, gu.phone) as patient_phone,
               COALESCE(ui_doctor.full_name, u_doctor.username, u_doctor.email) as doctor_name,
               c.name as clinic_name
        FROM appointments a
        LEFT JOIN users u_patient ON a.user_id = u_patient.user_id
        LEFT JOIN users_info ui_patient ON a.user_id = ui_patient.user_id
        LEFT JOIN guest_users gu ON a.guest_id = gu.guest_id
        LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
        LEFT JOIN users u_doctor ON d.user_id = u_doctor.user_id
        LEFT JOIN users_info ui_doctor ON d.user_id = ui_doctor.user_id
        LEFT JOIN clinics c ON a.clinic_id = c.clinic_id
        ORDER BY a.appointment_time DESC";
        
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        echo "✅ Query successful! Found " . $result->num_rows . " appointments<br>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Patient Name</th><th>Doctor Name</th><th>Clinic</th><th>Status</th><th>Time</th>";
        echo "</tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['appointment_id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['patient_name'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['doctor_name'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['clinic_name'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
            echo "<td>" . $row['appointment_time'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ Query returned no results<br>";
        echo "SQL Error: " . $conn->error . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Query error: " . $e->getMessage() . "<br>";
}

echo "<br><div style='margin-top: 20px; padding: 15px; background: #e8f5e8; border-radius: 5px;'>";
echo "<strong>✅ Fix completed!</strong><br>";
echo "• Created users for doctors<br>";
echo "• Added specialties<br>";
echo "• Updated doctor specialties<br>";
echo "• Tested appointments query<br>";
echo "</div>";

echo "<br><a href='admin/appointments.php' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>← Go to Appointments</a>";
?> 