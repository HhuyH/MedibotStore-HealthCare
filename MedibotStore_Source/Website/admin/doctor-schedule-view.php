<?php
session_start();
require_once '../includes/db.php';

// Kiểm tra đăng nhập và quyền bác sĩ
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy thông tin bác sĩ
try {
    $doctor_query = "SELECT d.*, u.username as doctor_name, s.name as specialty_name, 
                           c.name as clinic_name, c.address as clinic_address,
                           (SELECT COUNT(*) FROM doctor_schedules WHERE doctor_id = d.doctor_id) as total_shifts
                    FROM doctors d
                    JOIN users u ON d.user_id = u.user_id
                    LEFT JOIN specialties s ON d.specialty_id = s.specialty_id
                    LEFT JOIN clinics c ON d.clinic_id = c.clinic_id
                    WHERE d.user_id = ?";
    
    $stmt = $conn->prepare($doctor_query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $doctor_info = $stmt->get_result()->fetch_assoc();
    
    if (!$doctor_info) {
        header('Location: ../login.php');
        exit();
    }

    // Lấy lịch làm việc theo ngày
    $schedules_by_day = array_fill(1, 7, null); // Mảng cho 7 ngày trong tuần
    $schedule_query = "SELECT * FROM doctor_schedules 
                      WHERE doctor_id = ? 
                      ORDER BY day_of_week, start_time";
    $stmt = $conn->prepare($schedule_query);
    $stmt->bind_param('i', $doctor_info['doctor_id']);
    $stmt->execute();
    $schedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($schedules as $schedule) {
        $day = $schedule['day_of_week'];
        if (!isset($schedules_by_day[$day])) {
            $schedules_by_day[$day] = [];
        }
        $schedules_by_day[$day][] = $schedule;
    }

    // Lấy ngày nghỉ
    $off_days_query = "SELECT * FROM doctor_off_days 
                      WHERE doctor_id = ? AND off_date >= CURDATE()
                      ORDER BY off_date";
    $stmt = $conn->prepare($off_days_query);
    $stmt->bind_param('i', $doctor_info['doctor_id']);
    $stmt->execute();
    $off_days = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    header('Location: ../error.php');
    exit();
}

function getDayName($day) {
    $days = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ nhật'];
    return $days[$day - 1];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ca trực - <?= htmlspecialchars($doctor_info['doctor_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
    <link href="assets/css/sidebar.css" rel="stylesheet">
    <link href="assets/css/header.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/headeradmin.php'; ?>
    <?php include 'includes/sidebaradmin.php'; ?>

    <main class="main-content">
        <div class="container-fluid">
            <!-- Thông tin tổng quan -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="avatar-lg me-4 bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="fas fa-user-md text-primary fa-2x"></i>
                                </div>
                                <div>
                                    <h4 class="mb-1"><?= htmlspecialchars($doctor_info['doctor_name']) ?></h4>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-stethoscope me-2"></i><?= htmlspecialchars($doctor_info['specialty_name']) ?>
                                        <span class="mx-2">|</span>
                                        <i class="fas fa-hospital me-2"></i><?= htmlspecialchars($doctor_info['clinic_name']) ?>
                                    </p>
                                </div>
                            </div>
                            <div class="text-end">
                                <h3 class="mb-1"><?= $doctor_info['total_shifts'] ?></h3>
                                <p class="text-muted mb-0">Tổng số ca trực</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lịch làm việc -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Ca trực trong tuần</h5>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addShiftModal">
                                    <i class="fas fa-plus me-2"></i>Thêm ca trực
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Thứ</th>
                                            <th>Ca sáng</th>
                                            <th>Ca chiều</th>
                                            <th>Trạng thái</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php for ($day = 1; $day <= 7; $day++): ?>
                                            <tr>
                                                <td class="fw-medium"><?= getDayName($day) ?></td>
                                                <td>
                                                    <?php
                                                    $morning_shift = array_filter($schedules_by_day[$day] ?? [], function($s) {
                                                        return strtotime($s['start_time']) < strtotime('12:00:00');
                                                    });
                                                    if ($morning_shift) {
                                                        $shift = reset($morning_shift);
                                                        echo date('H:i', strtotime($shift['start_time'])) . ' - ' . 
                                                             date('H:i', strtotime($shift['end_time']));
                                                    } else {
                                                        echo '<span class="text-muted">Không có ca trực</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $afternoon_shift = array_filter($schedules_by_day[$day] ?? [], function($s) {
                                                        return strtotime($s['start_time']) >= strtotime('12:00:00');
                                                    });
                                                    if ($afternoon_shift) {
                                                        $shift = reset($afternoon_shift);
                                                        echo date('H:i', strtotime($shift['start_time'])) . ' - ' . 
                                                             date('H:i', strtotime($shift['end_time']));
                                                    } else {
                                                        echo '<span class="text-muted">Không có ca trực</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php if (isset($schedules_by_day[$day])): ?>
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" 
                                                                   id="day_<?= $day ?>"
                                                                   <?= array_reduce($schedules_by_day[$day], function($carry, $item) {
                                                                       return $carry && $item['is_available'];
                                                                   }, true) ? 'checked' : '' ?>>
                                                            <label class="form-check-label" for="day_<?= $day ?>">
                                                                Đang làm việc
                                                            </label>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Chưa có lịch</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-outline-primary btn-sm me-2" 
                                                            onclick="editShifts(<?= $day ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if (isset($schedules_by_day[$day])): ?>
                                                        <button type="button" class="btn btn-outline-danger btn-sm"
                                                                onclick="deleteShifts(<?= $day ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endfor; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ngày nghỉ -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Ngày nghỉ sắp tới</h5>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addOffDayModal">
                                    <i class="fas fa-plus me-2"></i>Thêm ngày nghỉ
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Ngày</th>
                                            <th>Lý do</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($off_days) > 0): ?>
                                            <?php foreach ($off_days as $off_day): ?>
                                                <tr>
                                                    <td class="fw-medium"><?= date('d/m/Y', strtotime($off_day['off_date'])) ?></td>
                                                    <td><?= htmlspecialchars($off_day['reason']) ?></td>
                                                    <td>
                                                        <button type="button" class="btn btn-outline-danger btn-sm"
                                                                onclick="deleteOffDay(<?= $off_day['off_day_id'] ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center py-3 text-muted">
                                                    <i class="fas fa-calendar-check mb-2 d-block"></i>
                                                    Không có ngày nghỉ nào sắp tới
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal thêm ca trực -->
    <div class="modal fade" id="addShiftModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm ca trực mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addShiftForm">
                        <div class="mb-3">
                            <label class="form-label">Thứ</label>
                            <select class="form-select" name="day_of_week" required>
                                <?php for ($i = 1; $i <= 7; $i++): ?>
                                    <option value="<?= $i ?>"><?= getDayName($i) ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ca trực</label>
                            <select class="form-select" name="shift_type" required>
                                <option value="morning">Ca sáng (8:00 - 12:00)</option>
                                <option value="afternoon">Ca chiều (13:30 - 17:30)</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" onclick="saveNewShift()">Lưu</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal thêm ngày nghỉ -->
    <div class="modal fade" id="addOffDayModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm ngày nghỉ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addOffDayForm">
                        <div class="mb-3">
                            <label class="form-label">Ngày nghỉ</label>
                            <input type="date" class="form-control" name="off_date" required
                                   min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Lý do</label>
                            <textarea class="form-control" name="reason" rows="3" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" onclick="saveOffDay()">Lưu</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <style>
    .main-content {
        margin-left: 250px;
        padding: 20px;
    }
    .avatar-lg {
        width: 80px;
        height: 80px;
    }
    .table th {
        font-weight: 600;
    }
    .form-switch .form-check-input {
        width: 2.5em;
    }
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    .badge {
        font-weight: 500;
        padding: 0.5em 0.7em;
    }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Xử lý thêm ca trực mới
    function saveNewShift() {
        const form = document.getElementById('addShiftForm');
        const formData = new FormData(form);
        
        fetch('ajax/add-shift.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Có lỗi xảy ra: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi thêm ca trực');
        });
    }

    // Xử lý thêm ngày nghỉ
    function saveOffDay() {
        const form = document.getElementById('addOffDayForm');
        const formData = new FormData(form);
        
        fetch('ajax/add-off-day.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Có lỗi xảy ra: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi thêm ngày nghỉ');
        });
    }

    // Xử lý xóa ca trực
    function deleteShifts(day) {
        if (confirm('Bạn có chắc chắn muốn xóa các ca trực của ngày này?')) {
            fetch('ajax/delete-shifts.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    day_of_week: day
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Có lỗi xảy ra khi xóa ca trực');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi xóa ca trực');
            });
        }
    }

    // Xử lý xóa ngày nghỉ
    function deleteOffDay(offDayId) {
        if (confirm('Bạn có chắc chắn muốn xóa ngày nghỉ này?')) {
            fetch('ajax/delete-off-day.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    off_day_id: offDayId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Có lỗi xảy ra khi xóa ngày nghỉ');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi xóa ngày nghỉ');
            });
        }
    }
    </script>
</body>
</html> 