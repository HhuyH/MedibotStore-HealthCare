<?php
session_start();
require_once 'includes/db.php';

try {
    // Lấy danh sách bác sĩ với thông tin chi tiết
    $query = "SELECT 
        d.doctor_id,
        d.biography,
        u.username,
        u.email,
        ui.full_name,
        ui.profile_picture as avatar,
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
    ORDER BY ui.full_name ASC";

    $result = $conn->query($query);
    if (!$result) {
        throw new Exception("Lỗi truy vấn: " . $conn->error);
    }
    $doctors = $result->fetch_all(MYSQLI_ASSOC);

    // Lấy danh sách chuyên khoa
    $specialty_query = "SELECT specialty_id, name FROM specialties ORDER BY name ASC";
    $specialty_result = $conn->query($specialty_query);
    $specialties = $specialty_result->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Error in doctors.php: " . $e->getMessage());
    $error_message = "Có lỗi xảy ra khi tải danh sách bác sĩ";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đội ngũ bác sĩ - MediBot Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .doctors-header-client {
            background: linear-gradient(rgba(138, 138, 138, 0.36), rgba(150, 151, 153, 0.45)), 
                        url('assets/images/thumbnail-chuyen-gia-scaled.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 90px 0;
            position: relative;
            margin-bottom: 30px;
        }
        .doctors-header-client h1 {
            font-size: 40px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .doctors-header-client p {
            font-size: 18px;
            line-height: 1.6;
            opacity: 0.95;
            max-width: 600px;
        }
        .search-container-client {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .search-input-client {
            width: 100%;
            padding: 12px 20px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 15px;
        }
        .search-input-client:focus {
            outline: none;
            border-color: #3b5998;
            box-shadow: 0 0 0 2px rgba(59, 89, 152, 0.1);
        }
        .filter-row-client {
            display: flex;
            gap: 15px;
        }
        .filter-select-client {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
            color: #4a5568;
        }
        .doctor-card-client {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .doctor-card-client:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .doctor-image-client {
            position: relative;
            width: 100%;
            padding-top: 100%;
            background: #f7fafc;
        }
        .doctor-image-client img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .doctor-info-overlay-client {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to bottom, rgba(51, 97, 198, 0.8), rgba(59, 89, 152, 0.95));
            color: white;
            padding: 20px;
            
        }
        .doctor-name-client {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 5px;
            color: white;
        }
        .doctor-specialty-client {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 0;
        }
        .doctor-content-client {
            padding: 20px;
        }
        .doctor-title-client {
            font-size: 16px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
        }
        .doctor-description-client {
            color: #4a5568;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .doctor-buttons-client {
            display: flex;
            gap: 10px;
        }
        .btn-outline-primary-client {
            flex: 1;
            padding: 10px;
            border: 1px solid #3b5998;
            background: transparent;
            color:linear-gradient(135deg, #1976d2, #1ec0f7);
            border-radius: 8px;
            font-weight: 500;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s ease;
            /* background: linear-gradient(135deg, #1976d2, #1ec0f7); */
        }
        .btn-primary-client {
            flex: 1;
            padding: 10px;
            background: #3b5998;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #1976d2, #1ec0f7);

        }
        .btn-outline-primary-client:hover {
            background: #3b5998;
            color: white;
        }
        .btn-primary-client:hover {
            background: #2d4373;
            color: white;
        }
        .schedule-modal-client .modal-content {
            border-radius: 10px;
            overflow: hidden;
        }
        .schedule-modal-client .modal-header {
            background: #3b5998;
            color: white;
            border: none;
            padding: 20px;
        }
        .schedule-modal-client .modal-title {
            font-size: 18px;
            font-weight: 600;
        }
        .schedule-modal-client .modal-body {
            padding: 20px;
        }
        .schedule-table-client {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 15px;
        }
        .schedule-table-client th,
        .schedule-table-client td {
            padding: 12px 15px;
            border: 1px solid #e5e7eb;
        }
        .schedule-table-client th {
            background: #f8fafc;
            font-weight: 600;
            color: #2d3748;
        }
        .schedule-table-client td {
            color: #4a5568;
        }
        .schedule-status-available {
            color: #059669;
            font-weight: 500;
        }
        .schedule-status-unavailable {
            color: #6b7280;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="doctors-header-client">
            <div class="container">
            <h1>Đội ngũ bác sĩ</h1>
            <p>Đội ngũ bác sĩ chuyên môn cao, tận tâm với nghề và luôn sẵn sàng chăm sóc sức khỏe cho bạn.</p>
                </div>
            </div>

            <div class="container">
        <div class="search-container-client">
            <input type="text" 
                   class="search-input-client" 
                   id="searchDoctor-client" 
                                   placeholder="Tìm kiếm theo tên bác sĩ hoặc chuyên khoa...">
            <div class="filter-row-client">
                <select class="filter-select-client" id="specialtyFilter-client">
                            <option value="">Tất cả chuyên khoa</option>
                            <?php foreach ($specialties as $specialty): ?>
                                <option value="<?= htmlspecialchars($specialty['name']) ?>">
                                    <?= htmlspecialchars($specialty['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                <select class="filter-select-client" id="sortDoctor-client">
                            <option value="name">Sắp xếp theo tên</option>
                            <option value="specialty">Sắp xếp theo chuyên khoa</option>
                        </select>
                    </div>
                </div>

        <div class="row" id="doctorsList-client">
                        <?php foreach ($doctors as $doctor): ?>
                <div class="col-lg-3 col-md-6 doctor-item-client" 
                                 data-name="<?= htmlspecialchars($doctor['full_name'] ?? $doctor['username']) ?>"
                                 data-specialty="<?= htmlspecialchars($doctor['specialty_name']) ?>">
                    <div class="doctor-card-client">
                        <div class="doctor-image-client">
                                                <?php if ($doctor['avatar']): ?>
                                                    <img src="<?= htmlspecialchars($doctor['avatar']) ?>" 
                                     alt="<?= htmlspecialchars($doctor['full_name'] ?? $doctor['username']) ?>">
                                                <?php else: ?>
                                <img src="assets/images/default-doctor.jpg" 
                                     alt="Default doctor image">
                                                <?php endif; ?>
                            <div class="doctor-info-overlay-client">
                                <h3 class="doctor-name-client">
                                                    <?= htmlspecialchars($doctor['full_name'] ?? $doctor['username']) ?>
                                </h3>
                                <p class="doctor-specialty-client">
                                                    <?= htmlspecialchars($doctor['specialty_name']) ?>
                                                </p>
                                            </div>
                                        </div>
                        <div class="doctor-content-client">
                            <h4 class="doctor-title-client">Chuyên Khoa: <?= htmlspecialchars($doctor['specialty_name']) ?></h4>
                            <p class="doctor-description-client">
                                <?= htmlspecialchars($doctor['biography']) ?>
                            </p>
                            <div class="doctor-buttons-client">
                                <button class="btn-outline-primary-client" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#scheduleModal-client-<?= $doctor['doctor_id'] ?>">
                                    Xem lịch khám
                                </button>
                                            <a href="book-appointment.php?doctor_id=<?= $doctor['doctor_id'] ?>" 
                                   class="btn-primary-client">
                                    Đặt lịch khám
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                <!-- Schedule Modal -->
                <div class="modal fade schedule-modal-client" 
                     id="scheduleModal-client-<?= $doctor['doctor_id'] ?>" 
                     tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    Lịch khám bệnh - <?= htmlspecialchars($doctor['full_name'] ?? $doctor['username']) ?>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <?php
                                $scheduleQuery = "SELECT 
                                    day_of_week,
                                    DATE_FORMAT(start_time, '%H:%i') as start_time,
                                    DATE_FORMAT(end_time, '%H:%i') as end_time,
                                    is_available
                                FROM doctor_schedules 
                                WHERE doctor_id = {$doctor['doctor_id']}
                                ORDER BY day_of_week ASC";
                                
                                $scheduleResult = $conn->query($scheduleQuery);
                                if ($scheduleResult && $scheduleResult->num_rows > 0): ?>
                                    <table class="schedule-table-client">
                                        <thead>
                                            <tr>
                                                <th>Thứ</th>
                                                <th>Giờ khám</th>
                                                <th>Trạng thái</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $days = [
                                                1 => 'Thứ 2',
                                                2 => 'Thứ 3',
                                                3 => 'Thứ 4',
                                                4 => 'Thứ 5',
                                                5 => 'Thứ 6',
                                                6 => 'Thứ 7',
                                                7 => 'Chủ nhật'
                                            ];
                                            while ($schedule = $scheduleResult->fetch_assoc()): 
                                            ?>
                                                <tr>
                                                    <td><?= $days[$schedule['day_of_week']] ?></td>
                                                    <td><?= $schedule['start_time'] ?> - <?= $schedule['end_time'] ?></td>
                                                    <td>
                                                        <?php if ($schedule['is_available']): ?>
                                                            <span class="schedule-status-available">Có lịch khám</span>
                                                        <?php else: ?>
                                                            <span class="schedule-status-unavailable">Không có lịch</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p class="text-center text-muted">Chưa có lịch khám</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
            </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchDoctor-client');
            const specialtyFilter = document.getElementById('specialtyFilter-client');
            const sortSelect = document.getElementById('sortDoctor-client');
            const doctorsList = document.getElementById('doctorsList-client');
            const doctorItems = document.querySelectorAll('.doctor-item-client');

        function filterDoctors() {
            const searchTerm = searchInput.value.toLowerCase();
            const specialty = specialtyFilter.value.toLowerCase();

            doctorItems.forEach(item => {
                const doctorName = item.dataset.name.toLowerCase();
                const doctorSpecialty = item.dataset.specialty.toLowerCase();
                    
                const matchesSearch = doctorName.includes(searchTerm) || 
                                    doctorSpecialty.includes(searchTerm);
                const matchesSpecialty = !specialty || doctorSpecialty === specialty;
                    
                    item.style.display = (matchesSearch && matchesSpecialty) ? '' : 'none';
                });
        }

        function sortDoctors() {
                const items = Array.from(doctorItems);
            const sortBy = sortSelect.value;
            
            items.sort((a, b) => {
                const aValue = a.dataset[sortBy].toLowerCase();
                const bValue = b.dataset[sortBy].toLowerCase();
                return aValue.localeCompare(bValue);
            });

            items.forEach(item => doctorsList.appendChild(item));
        }

        searchInput.addEventListener('input', filterDoctors);
        specialtyFilter.addEventListener('change', filterDoctors);
        sortSelect.addEventListener('change', sortDoctors);
    });
    </script>
</body>
</html> 