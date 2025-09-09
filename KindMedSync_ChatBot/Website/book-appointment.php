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
    
    if (!$doctor_id || !$clinic_id || !$appointment_date || !$appointment_time) {
        $err = 'Vui lòng điền đầy đủ thông tin!';
    } else {
        try {
            // Kiểm tra thời gian đặt lịch không được trong quá khứ
            $current_datetime = new DateTime();
            $appointment_datetime_obj = new DateTime($appointment_datetime);
            
            if ($appointment_datetime_obj < $current_datetime) {
                $err = 'Không thể đặt lịch trong quá khứ. Vui lòng chọn thời gian khác!';
            } else {
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
                        // Lấy thông tin bác sĩ và phòng khám
                        $doctor_info_sql = "SELECT ui.full_name as doctor_name, s.name as specialization, 
                                               c.name as clinic_name, c.address as clinic_address
                                        FROM doctors d 
                                        LEFT JOIN users u ON d.user_id = u.user_id
                                        LEFT JOIN users_info ui ON u.user_id = ui.user_id
                                        LEFT JOIN specialties s ON d.specialty_id = s.specialty_id
                                        LEFT JOIN clinics c ON d.clinic_id = c.clinic_id
                                        WHERE d.doctor_id = ?";
                        $doctor_stmt = $conn->prepare($doctor_info_sql);
                        $doctor_stmt->bind_param('i', $doctor_id);
                        $doctor_stmt->execute();
                        $doctor_info = $doctor_stmt->get_result()->fetch_assoc();

                        // Lấy thông tin người dùng
                        $user_info_sql = "SELECT u.email, ui.full_name 
                                       FROM users u 
                                       LEFT JOIN users_info ui ON u.user_id = ui.user_id 
                                       WHERE u.user_id = ?";
                        $user_stmt = $conn->prepare($user_info_sql);
                        $user_stmt->bind_param('i', $user_id);
                        $user_stmt->execute();
                        $user_info = $user_stmt->get_result()->fetch_assoc();

                        // Chuẩn bị dữ liệu cho email
                        $appointment_data = array(
                            'doctor_name' => $doctor_info['doctor_name'],
                            'specialization' => $doctor_info['specialization'],
                            'clinic_name' => $doctor_info['clinic_name'],
                            'clinic_address' => $doctor_info['clinic_address'],
                            'appointment_time' => $appointment_datetime,
                            'reason' => $reason
                        );

                        // Gửi email xác nhận
                        require_once 'includes/email_system_real.php';
                        sendAppointmentEmail($user_info['email'], $user_info['full_name'], $appointment_data);

                        $_SESSION['appointment_success'] = 'Đặt lịch thành công! Lịch hẹn của bạn đang chờ xác nhận từ phòng khám. Vui lòng kiểm tra email để xem chi tiết.';
                        header('Location: appointments.php');
                        exit;
                    } else {
                        $err = 'Có lỗi xảy ra. Vui lòng thử lại!';
                    }
                }
            }
        } catch (Exception $e) {
            $err = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
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
    <title>Đặt lịch khám - MediBot Store Medical & Health Care</title>
    
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
            border-radius: 24px;
            padding: 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
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
            border-radius: 24px 24px 0 0;
        }

        .page-header {
            text-align: center;
            padding: 3rem 2rem;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.03), rgba(118, 75, 162, 0.03));
            border-radius: 24px 24px 0 0;
            margin: 0 0 2rem 0;
            position: relative;
        }

        .page-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 2px;
            background: var(--primary-gradient);
            border-radius: 2px;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .page-subtitle {
            color: var(--text-muted);
            font-size: 1.1rem;
            font-weight: 500;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .content-inner {
            padding: 2rem;
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 1rem;
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
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: border-color 0.2s ease;
            background: white;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        /* Doctor Selection */
        .doctor-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .doctor-card {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
            position: relative;
        }

        .doctor-card:hover {
            border-color: #667eea;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
        }

        .doctor-card.selected {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }

        .doctor-card.selected::after {
            content: '✓';
            position: absolute;
            top: 12px;
            right: 15px;
            width: 22px;
            height: 22px;
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
            gap: 1rem;
        }

        .doctor-avatar {
            width: 50px;
            height: 50px;
            border-radius: 8px;
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
            font-size: 0.85rem;
            font-weight: 500;
        }

        .doctor-clinic {
            color: #718096;
            font-size: 0.8rem;
            margin-top: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Time Slots */
        .time-slots {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 0.5rem;
            margin-top: 1rem;
            max-height: 200px;
            overflow-y: auto;
            padding: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
        }

        .time-slot {
            padding: 0.5rem 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
            font-weight: 500;
            font-size: 0.9rem;
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
            opacity: 0.7;
        }

        .time-slot.loading {
            background: #f8fafc;
            color: #94a3b8;
            cursor: default;
        }

        /* Buttons */
        .btn-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        /* Alerts */
        .alert {
            border-radius: 8px;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background: rgba(72, 187, 120, 0.1);
            color: #2f855a;
        }

        .alert-danger {
            background: rgba(245, 101, 101, 0.1);
            color: #c53030;
        }

        /* Loading spinner */
        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-radius: 50%;
            border-top-color: #667eea;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding-top: 100px;
            }
            
            .page-container {
                padding: 2rem 0;
            }
            
            .booking-container {
                padding: 0 1rem;
            }
            
            .page-header {
                padding: 2rem 1.5rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .content-inner {
                padding: 1.5rem;
            }
            
            .doctor-grid {
                grid-template-columns: 1fr;
            }
            
            .time-slots {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .btn-group {
                flex-direction: column;
            }
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
                                </select>
                            </div>
                            
                            <!-- Doctor Cards Grid -->
                            <div class="doctor-grid" id="doctorGrid">
                                <div class="doctor-card loading">
                                    <div class="loading-spinner"></div>
                                    Đang tải danh sách bác sĩ...
                                </div>
                            </div>
                            
                            <input type="hidden" name="doctor_id" id="selectedDoctor" required>
                            <input type="hidden" name="clinic_id" id="selectedClinic" required>
                        </div>

                        <!-- Date & Time Selection -->
                        <div class="form-section">
                            <!-- <h3 class="section-title">
                                <i class="fas fa-calendar-alt"></i>
                                Chọn ngày và giờ
                            </h3> -->
                            
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Cache và state management
        let doctorsCache = null;
        let timeSlotsCache = {};
        let selectedDoctorId = null;
        let selectedClinicId = null;
        let selectedTime = null;

        // Load trang
        document.addEventListener('DOMContentLoaded', function() {
            loadDoctors();
            
            // Event listeners
            document.getElementById('doctorDropdown').addEventListener('change', handleDoctorDropdownChange);
            document.getElementById('appointmentDate').addEventListener('change', handleDateChange);
            document.getElementById('bookingForm').addEventListener('submit', handleFormSubmit);
        });

        // Load danh sách bác sĩ
        function loadDoctors() {
            if (doctorsCache) {
                populateDoctors(doctorsCache);
                return;
            }

            fetch('/api/get-doctors.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        doctorsCache = data.doctors;
                        populateDoctors(data.doctors);
                    } else {
                        showError('Không thể tải danh sách bác sĩ');
                    }
                })
                .catch(error => {
                    console.error('Error loading doctors:', error);
                    showError('Lỗi kết nối. Vui lòng thử lại!');
                });
        }

        // Hiển thị danh sách bác sĩ
        function populateDoctors(doctors) {
            const dropdown = document.getElementById('doctorDropdown');
            const grid = document.getElementById('doctorGrid');
            
            // Cập nhật dropdown
            dropdown.innerHTML = '<option value="">-- Chọn nhanh bác sĩ --</option>';
            doctors.forEach(doctor => {
                const option = document.createElement('option');
                option.value = doctor.doctor_id;
                option.textContent = `${doctor.full_name} - ${doctor.specialization}`;
                option.dataset.clinicId = doctor.clinic_id;
                dropdown.appendChild(option);
            });
            
            // Cập nhật grid
            grid.innerHTML = '';
            doctors.forEach(doctor => {
                const card = document.createElement('div');
                card.className = 'doctor-card';
                card.onclick = () => selectDoctor(doctor.doctor_id);
                card.innerHTML = `
                    <div class="doctor-info">
                        <img src="${doctor.profile_picture || '/assets/images/default-doctor.jpg'}" 
                             alt="Doctor" class="doctor-avatar">
                        <div class="doctor-details">
                            <h5>${doctor.full_name}</h5>
                            <div class="doctor-specialization">${doctor.specialization}</div>
                            <div class="doctor-clinic">
                                <i class="fas fa-hospital"></i>
                                ${doctor.clinic_name}
                            </div>
                        </div>
                    </div>
                `;
                grid.appendChild(card);
            });
        }

        // Xử lý thay đổi dropdown bác sĩ
        function handleDoctorDropdownChange(event) {
            const doctorId = event.target.value;
            if (doctorId) {
                selectDoctor(parseInt(doctorId), true);
            }
        }

        // Chọn bác sĩ
        function selectDoctor(doctorId, fromDropdown = false) {
            if (!doctorId) return;

            selectedDoctorId = doctorId;
            
            // Tìm thông tin bác sĩ từ cache
            const doctor = doctorsCache.find(d => d.doctor_id === doctorId);
            if (!doctor) return;
            
            selectedClinicId = doctor.clinic_id;
            
            // Cập nhật UI
            document.querySelectorAll('.doctor-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Tìm và highlight card được chọn
            const cards = document.querySelectorAll('.doctor-card');
            cards.forEach(card => {
                if (card.querySelector('.doctor-details h5').textContent === doctor.full_name) {
                    card.classList.add('selected');
                }
            });
            
            // Cập nhật dropdown nếu chọn từ card
            if (!fromDropdown) {
                document.getElementById('doctorDropdown').value = doctorId;
            }
            
            // Cập nhật hidden inputs
            document.getElementById('selectedDoctor').value = doctorId;
                document.getElementById('selectedClinic').value = doctor.clinic_id;
            
            // Reset và load lại time slots nếu đã chọn ngày
            const dateInput = document.getElementById('appointmentDate');
            if (dateInput.value) {
                loadTimeSlots(doctorId, dateInput.value);
            } else {
            resetTimeSlots();
            }
        }

        // Xử lý thay đổi ngày
        function handleDateChange(event) {
            const date = event.target.value;
            const selectedDate = new Date(date);
            const today = new Date();
            today.setHours(0, 0, 0, 0); // Reset time to start of day for comparison

            if (!selectedDoctorId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Chưa chọn bác sĩ',
                    text: 'Vui lòng chọn bác sĩ trước khi chọn ngày khám!'
                });
                event.target.value = '';
                return;
            }

            if (selectedDate < today) {
                Swal.fire({
                    icon: 'error',
                    title: 'Ngày không hợp lệ',
                    text: 'Không thể chọn ngày trong quá khứ!'
                });
                event.target.value = '';
                resetTimeSlots();
                return;
            }
            
            if (date) {
                loadTimeSlots(selectedDoctorId, date);
            } else {
                resetTimeSlots();
            }
        }

        // Load time slots từ API
        function loadTimeSlots(doctorId, date) {
            if (!doctorId || !date) {
                console.error('Missing doctorId or date');
                return;
            }

            const container = document.getElementById('timeSlots');
            const cacheKey = `${doctorId}-${date}`;
            
            // Hiển thị loading
            container.innerHTML = '<div class="time-slot loading"><div class="loading-spinner"></div> Đang tải...</div>';
            
            fetch(`/api/get-time-slots.php?doctor_id=${doctorId}&date=${date}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        if (!data.slots || data.slots.length === 0) {
                            container.innerHTML = '<div class="time-slot disabled">Không có lịch trống trong ngày này</div>';
                            document.getElementById('selectedTime').value = '';
                            return;
                        }
                        timeSlotsCache[cacheKey] = data.slots;
                        populateTimeSlots(data.slots);
                    } else {
                        throw new Error(data.message || 'Không thể tải lịch trống');
                    }
                })
                .catch(error => {
                    console.error('Error loading time slots:', error);
                    container.innerHTML = '<div class="time-slot disabled">Lỗi: ' + error.message + '</div>';
                    document.getElementById('selectedTime').value = '';
                });
        }

        // Hiển thị time slots
        function populateTimeSlots(slots) {
            const container = document.getElementById('timeSlots');
            container.innerHTML = '';
            
            if (!slots || slots.length === 0) {
                container.innerHTML = '<div class="time-slot disabled">Không có lịch trống</div>';
                return;
            }

            const now = new Date();
            const selectedDate = new Date(document.getElementById('appointmentDate').value);
            const isToday = selectedDate.toDateString() === now.toDateString();
            
            slots.forEach(slot => {
                const slotElement = document.createElement('div');
                const [hours, minutes] = slot.time.split(':');
                const slotTime = new Date(selectedDate);
                slotTime.setHours(parseInt(hours), parseInt(minutes), 0, 0);

                // Kiểm tra nếu là thời gian trong quá khứ của ngày hôm nay
                const isPastTime = isToday && slotTime < now;
                
                slotElement.className = `time-slot ${(slot.booked || isPastTime) ? 'disabled' : ''}`;
                slotElement.textContent = slot.time;
                
                if (slot.booked) {
                    slotElement.title = 'Đã có lịch hẹn';
                } else {
                    slotElement.onclick = function() {
                        selectTime(slot.time, this);
                    };
                    
                    // Nếu đã chọn trước đó thì highlight
                    if (selectedTime === slot.time) {
                        slotElement.classList.add('selected');
                    }
                }
                
                container.appendChild(slotElement);
            });
        }

        // Chọn giờ
        function selectTime(time, element) {
            // Bỏ chọn slot cũ
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.classList.remove('selected');
            });
            
            // Chọn slot mới
            element.classList.add('selected');
            selectedTime = time;
            document.getElementById('selectedTime').value = time;
        }

        // Reset time slots
        function resetTimeSlots() {
            const container = document.getElementById('timeSlots');
            container.innerHTML = '<div class="time-slot disabled">Vui lòng chọn ngày trước</div>';
            document.getElementById('selectedTime').value = '';
            document.getElementById('appointmentDate').value = '';
            selectedTime = null;
        }

        // Xử lý submit form
        function handleFormSubmit(event) {
            event.preventDefault();
            
            // Validation
            if (!selectedDoctorId) {
                showError('Vui lòng chọn bác sĩ!');
                return;
            }
            
            if (!document.getElementById('appointmentDate').value) {
                showError('Vui lòng chọn ngày khám!');
                return;
            }
            
            if (!selectedTime) {
                showError('Vui lòng chọn giờ khám!');
                return;
            }
            
            // Submit qua API
            const formData = new FormData(event.target);
            submitBooking(formData);
        }

        // Submit booking qua API
        function submitBooking(formData) {
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            
            // Show loading
            submitBtn.innerHTML = '<div class="loading-spinner me-2"></div>Đang đặt lịch...';
            submitBtn.disabled = true;
            
            fetch('/api/book-appointment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Đặt lịch thành công!',
                        text: 'Lịch hẹn của bạn đang chờ xác nhận từ phòng khám.',
                        showConfirmButton: false,
                        timer: 2000
                    }).then(() => {
                        window.location.href = 'appointments.php';
                    });
                } else {
                    showError(data.message || 'Có lỗi xảy ra. Vui lòng thử lại!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Có lỗi xảy ra. Vui lòng thử lại!');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        }

        // Hiển thị lỗi
        function showError(message) {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                text: message
            });
        }

        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    alert.style.transition = 'opacity 0.3s ease';
                    alert.style.opacity = '0';
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