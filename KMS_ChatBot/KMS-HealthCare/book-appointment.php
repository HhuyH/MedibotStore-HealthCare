<?php
include 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$msg = $err = '';

// Xử lý đặt lịch
if (isset($_POST['book_appointment'])) {
    $doctor_id = (int)$_POST['doctor_id'];
    $clinic_id = (int)$_POST['clinic_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $reason = trim($_POST['reason']);
    
    // Combine date and time
    $appointment_datetime = $appointment_date . ' ' . $appointment_time;
    
    if ($doctor_id && $clinic_id && $appointment_date && $appointment_time) {
        // Kiểm tra xem đã có lịch hẹn vào thời gian này chưa
        $check_stmt = $conn->prepare("SELECT appointment_id FROM appointments WHERE doctor_id = ? AND appointment_time = ? AND status IN ('pending', 'confirmed')");
        $check_stmt->bind_param('is', $doctor_id, $appointment_datetime);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $err = 'Thời gian này đã có lịch hẹn khác. Vui lòng chọn thời gian khác!';
        } else {
            // Đặt lịch mới
            $stmt = $conn->prepare("INSERT INTO appointments (user_id, doctor_id, clinic_id, appointment_time, reason, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param('iiiss', $user_id, $doctor_id, $clinic_id, $appointment_datetime, $reason);
            
            if ($stmt->execute()) {
                $msg = 'Đặt lịch thành công! Lịch hẹn của bạn đang chờ xác nhận từ phòng khám.';
                // Reset form
                $_POST = [];
            } else {
                $err = 'Có lỗi xảy ra. Vui lòng thử lại!';
            }
        }
    } else {
        $err = 'Vui lòng điền đầy đủ thông tin!';
    }
}

// Lấy danh sách bác sĩ
$doctors_sql = "SELECT d.doctor_id, ui.full_name, s.name as specialization, ui.profile_picture, 
                       d.clinic_id, c.name as clinic_name, c.address as clinic_address 
                FROM doctors d 
                LEFT JOIN users u ON d.user_id = u.user_id
                LEFT JOIN users_info ui ON u.user_id = ui.user_id
                LEFT JOIN specialties s ON d.specialty_id = s.specialty_id
                LEFT JOIN clinics c ON d.clinic_id = c.clinic_id 
                WHERE u.status = 'active' 
                ORDER BY ui.full_name";
$doctors = $conn->query($doctors_sql)->fetch_all(MYSQLI_ASSOC);

// Lấy danh sách phòng khám
$clinics_sql = "SELECT * FROM clinics ORDER BY name";
$clinics = $conn->query($clinics_sql)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lịch khám - Qickmed</title>
    
    <!-- CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.3);
            --text-primary: #2d3748;
            --text-secondary: #4a5568;
            --text-muted: #718096;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--primary-gradient);
            background-attachment: fixed;
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            padding-top: 120px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 15% 85%, rgba(102, 126, 234, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 85% 15%, rgba(245, 101, 101, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 50% 50%, rgba(79, 172, 254, 0.08) 0%, transparent 50%),
                linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, transparent 100%);
            pointer-events: none;
            z-index: -1;
        }

        .page-container {
            min-height: calc(100vh - 120px);
            padding: 3rem 0;
        }

        .booking-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .booking-card {
            background: var(--glass-bg);
            backdrop-filter: blur(40px);
            border-radius: 32px;
            padding: 0;
            box-shadow: 
                0 40px 80px rgba(0, 0, 0, 0.08),
                0 0 0 1px var(--glass-border),
                inset 0 1px 0 rgba(255, 255, 255, 0.9);
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .booking-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
            border-radius: 32px 32px 0 0;
        }

        .page-header {
            text-align: center;
            padding: 4rem 3rem;
            background: 
                linear-gradient(135deg, rgba(102, 126, 234, 0.03), rgba(118, 75, 162, 0.03)),
                linear-gradient(45deg, rgba(255, 255, 255, 0.8), rgba(248, 250, 252, 0.8));
            border-radius: 32px 32px 0 0;
            margin: 0 0 3rem 0;
            position: relative;
        }

        .page-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 2px;
            background: var(--primary-gradient);
            border-radius: 2px;
        }

        .page-title {
            font-size: 3.5rem;
            font-weight: 900;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
            letter-spacing: -0.03em;
            line-height: 1.2;
        }

        .page-subtitle {
            color: var(--text-muted);
            font-size: 1.3rem;
            font-weight: 500;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .content-inner {
            padding: 3rem;
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-size: 0.95rem;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        /* Doctor Selection - Compact Style */
        .doctor-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .doctor-card {
            border: 2px solid rgba(226, 232, 240, 0.6);
            border-radius: 12px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
            position: relative;
            overflow: hidden;
        }

        .doctor-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.15);
        }

        .doctor-card.selected {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.06);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }

        .doctor-card.selected::after {
            content: '✓';
            position: absolute;
            top: 10px;
            right: 15px;
            width: 20px;
            height: 20px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }

        .doctor-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .doctor-avatar {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            object-fit: cover;
        }

        .doctor-details h5 {
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 0.25rem;
            font-size: 1rem;
        }

        .doctor-specialization {
            color: #667eea;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .doctor-clinic {
            color: #718096;
            font-size: 0.75rem;
            margin-top: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Time Slots - Compact */
        .time-slots {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 0.5rem;
            margin-top: 1rem;
            max-height: 200px;
            overflow-y: auto;
            padding: 0.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
        }

        .time-slot {
            padding: 0.5rem 0.25rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
            font-weight: 500;
            font-size: 0.85rem;
        }

        .time-slot:hover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }

        .time-slot.selected {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }

        .time-slot.disabled {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
            opacity: 0.6;
        }

        /* Buttons */
        .btn-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 3rem;
        }

        .btn {
            padding: 0.75rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
            transform: translateY(-1px);
        }

        /* Select2 Custom Styling */
        .select2-container--default .select2-selection--single {
            height: 45px !important;
            border: 2px solid #e2e8f0 !important;
            border-radius: 12px !important;
            padding: 0.75rem 1rem !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 27px !important;
            color: #4a5568 !important;
        }

        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #667eea !important;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
        }

        /* Alerts */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(72, 187, 120, 0.1), rgba(72, 187, 120, 0.05));
            color: #2f855a;
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(245, 101, 101, 0.1), rgba(245, 101, 101, 0.05));
            color: #c53030;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .booking-container {
                padding: 0 1.5rem;
            }
            
            .page-header {
                padding: 3rem 2rem;
            }
            
            .content-inner {
                padding: 2rem;
            }
            
            .doctor-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            body {
                padding-top: 100px;
            }
            
            .page-container {
                padding: 2rem 0;
                min-height: calc(100vh - 100px);
            }
            
            .booking-card { 
                border-radius: 24px;
                margin-bottom: 2rem;
            }
            
            .page-title { 
                font-size: 2.5rem; 
            }
            
            .page-subtitle {
                font-size: 1.1rem;
            }
            
            .page-header {
                padding: 2.5rem 1.5rem;
            }
            
            .content-inner {
                padding: 1.5rem;
            }
            
            .doctor-grid { 
                grid-template-columns: 1fr; 
            }
            
            .time-slots { 
                grid-template-columns: repeat(4, 1fr);
                max-height: 150px;
            }
            
            .btn-group { 
                flex-direction: column; 
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 2rem;
            }
            
            .page-header {
                padding: 2rem 1rem;
            }
            
            .content-inner {
                padding: 1rem;
            }
            
            .time-slots {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .doctor-card {
                padding: 1.5rem;
            }
        }

        /* Loading Animation */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(102, 126, 234, 0.3);
            border-radius: 50%;
            border-top-color: #667eea;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="page-container">
        <div class="container">
            <div class="booking-container">
                <div class="booking-card">
                    <!-- Page Header -->
                    <div class="page-header">
                        <h1 class="page-title">
                            <i class="fas fa-stethoscope"></i>
                            Đặt lịch khám bệnh
                        </h1>
                        <p class="page-subtitle">Chọn bác sĩ chuyên khoa và thời gian phù hợp để đặt lịch khám một cách dễ dàng</p>
                    </div>

                    <div class="content-inner">

                    <!-- Alerts -->
                    <?php if ($msg): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?= htmlspecialchars($msg) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($err): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?= htmlspecialchars($err) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Booking Form -->
                    <form method="POST" id="bookingForm">
                        <!-- Doctor Selection -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-user-md"></i>
                                Chọn bác sĩ
                            </h3>
                            
                            <!-- Quick Select Dropdown -->
                            <div class="form-group mb-3">
                                <select id="doctorDropdown" class="form-control">
                                    <option value="">-- Chọn nhanh bác sĩ --</option>
                                    <?php foreach ($doctors as $doctor): ?>
                                        <option value="<?= $doctor['doctor_id'] ?>" data-clinic="<?= $doctor['clinic_id'] ?>">
                                            <?= htmlspecialchars($doctor['full_name']) ?> - <?= htmlspecialchars($doctor['specialization']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Doctor Cards Grid -->
                            <div class="doctor-grid">
                                <?php foreach ($doctors as $doctor): ?>
                                    <div class="doctor-card" onclick="selectDoctor(<?= $doctor['doctor_id'] ?>)">
                                        <div class="doctor-info">
                                            <img src="<?= $doctor['profile_picture'] ?: '/assets/images/default-doctor.jpg' ?>" 
                                                 alt="Doctor" class="doctor-avatar">
                                            <div class="doctor-details">
                                                <h5><?= htmlspecialchars($doctor['full_name']) ?></h5>
                                                <div class="doctor-specialization"><?= htmlspecialchars($doctor['specialization']) ?></div>
                                                <div class="doctor-clinic">
                                                    <i class="fas fa-hospital"></i>
                                                    <?= htmlspecialchars($doctor['clinic_name']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <input type="hidden" name="doctor_id" id="selectedDoctor" required>
                            <input type="hidden" name="clinic_id" id="selectedClinic" required>
                        </div>

                        <!-- Date & Time Selection -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-calendar-alt"></i>
                                Chọn ngày và giờ
                            </h3>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-calendar me-1"></i>
                                            Ngày khám *
                                        </label>
                                        <input type="date" name="appointment_date" id="appointmentDate" 
                                               class="form-control" required min="<?= date('Y-m-d') ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-clock me-1"></i>
                                            Giờ khám * <small class="text-muted">(8:00 - 17:00)</small>
                                        </label>
                                        <div class="time-slots" id="timeSlots">
                                            <div class="time-slot disabled">Vui lòng chọn bác sĩ và ngày trước</div>
                                        </div>
                                        <input type="hidden" name="appointment_time" id="selectedTime" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reason -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-notes-medical"></i>
                                Lý do khám
                            </h3>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-edit me-1"></i>
                                    Mô tả triệu chứng hoặc lý do khám bệnh
                                </label>
                                <textarea name="reason" class="form-control" rows="4" 
                                          placeholder="Ví dụ: Đau đầu, sốt, khám sức khỏe định kỳ..."></textarea>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="btn-group">
                            <a href="appointments.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i>
                                Quay lại
                            </a>
                            <button type="submit" name="book_appointment" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-calendar-check"></i>
                                Đặt lịch khám
                            </button>
                        </div>
                    </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        let selectedDoctorId = null;
        let selectedClinicId = null;
        let selectedTime = null;

        // Doctor dropdown change handler
        document.getElementById('doctorDropdown').addEventListener('change', function() {
            const doctorId = this.value;
            if (doctorId) {
                selectDoctor(parseInt(doctorId), true);
            }
        });

        // Doctor selection
        function selectDoctor(doctorId, fromDropdown = false) {
            // Remove previous selection
            document.querySelectorAll('.doctor-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selection to clicked card
            if (!fromDropdown) {
                event.currentTarget.classList.add('selected');
            } else {
                // Find and select the correct card
                const targetCard = document.querySelector(`.doctor-card[onclick="selectDoctor(${doctorId})"]`);
                if (targetCard) {
                    targetCard.classList.add('selected');
                }
                // Update dropdown
                document.getElementById('doctorDropdown').value = doctorId;
            }
            
            // Store selected doctor
            selectedDoctorId = doctorId;
            document.getElementById('selectedDoctor').value = doctorId;
            
            // Find clinic for this doctor
            const doctors = <?= json_encode($doctors) ?>;
            const doctor = doctors.find(d => d.doctor_id == doctorId);
            if (doctor && doctor.clinic_id) {
                selectedClinicId = doctor.clinic_id;
                document.getElementById('selectedClinic').value = doctor.clinic_id;
            }
            
            // Reset time selection
            resetTimeSlots();
        }

        // Date change handler
        document.getElementById('appointmentDate').addEventListener('change', function() {
            if (selectedDoctorId && this.value) {
                loadTimeSlots(this.value);
            } else if (!selectedDoctorId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Chưa chọn bác sĩ',
                    text: 'Vui lòng chọn bác sĩ trước khi chọn ngày khám!'
                });
                this.value = '';
            }
        });

        // Load available time slots
        function loadTimeSlots(date) {
            const timeSlotsContainer = document.getElementById('timeSlots');
            timeSlotsContainer.innerHTML = '<div class="loading-spinner"></div> Đang tải...';
            
            // Generate time slots (8:00 - 17:00, every 30 minutes)
            const timeSlots = [];
            for (let hour = 8; hour <= 17; hour++) {
                for (let minute = 0; minute < 60; minute += 30) {
                    if (hour === 17 && minute > 0) break; // Stop at 17:00
                    const timeStr = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
                    timeSlots.push(timeStr);
                }
            }
            
            // Simulate checking availability (in real app, this would be an API call)
            setTimeout(() => {
                timeSlotsContainer.innerHTML = '';
                timeSlots.forEach(time => {
                    const slot = document.createElement('div');
                    slot.className = 'time-slot';
                    slot.textContent = time;
                    slot.onclick = () => selectTime(time);
                    
                    // Randomly disable some slots to simulate booked times
                    if (Math.random() > 0.8) {
                        slot.classList.add('disabled');
                        slot.onclick = null;
                        slot.title = 'Đã có lịch hẹn';
                    }
                    
                    timeSlotsContainer.appendChild(slot);
                });
            }, 500);
        }

        // Time selection
        function selectTime(time) {
            // Remove previous selection
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.classList.remove('selected');
            });
            
            // Add selection to clicked slot
            event.currentTarget.classList.add('selected');
            
            // Store selected time
            selectedTime = time;
            document.getElementById('selectedTime').value = time;
        }

        // Reset time slots
        function resetTimeSlots() {
            document.getElementById('timeSlots').innerHTML = '<div class="time-slot disabled">Vui lòng chọn ngày trước</div>';
            document.getElementById('selectedTime').value = '';
            document.getElementById('appointmentDate').value = '';
            selectedTime = null;
        }

        // Form validation
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            if (!selectedDoctorId) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Chưa chọn bác sĩ',
                    text: 'Vui lòng chọn bác sĩ!'
                });
                return;
            }
            
            if (!document.getElementById('appointmentDate').value) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Chưa chọn ngày',
                    text: 'Vui lòng chọn ngày khám!'
                });
                return;
            }
            
            if (!selectedTime) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Chưa chọn giờ',
                    text: 'Vui lòng chọn giờ khám!'
                });
                return;
            }
            
            // Show loading
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.innerHTML = '<div class="loading-spinner me-2"></div>Đang đặt lịch...';
            submitBtn.disabled = true;
        });

        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => alert.remove(), 300);
                });
            }, 5000);
        });
    </script>

    <!-- Appointment Modal -->
    <?php include 'includes/appointment-modal.php'; ?>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html> 