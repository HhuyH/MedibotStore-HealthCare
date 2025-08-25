<!-- Appointment Booking Modal -->
<div class="modal fade" id="appointmentModal" tabindex="-1" aria-labelledby="appointmentModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content modal-glass">
            <div class="modal-header">
                <h5 class="modal-title" id="appointmentModalLabel">
                    <i class="fas fa-calendar-plus me-2"></i>
                    Đặt lịch khám bệnh
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body">
                <form id="modalBookingForm">
                    <!-- Doctor Selection -->
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-user-md me-2"></i>
                            Chọn bác sĩ
                        </h6>
                        <select id="modalDoctorSelect" class="form-select" required>
                            <option value="">-- Chọn bác sĩ --</option>
                        </select>
                    </div>

                    <!-- Date Selection -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">
                                <i class="fas fa-calendar me-2"></i>
                                Ngày khám
                            </h6>
                            <input type="date" id="modalAppointmentDate" class="form-control" required min="">
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">
                                <i class="fas fa-clock me-2"></i>
                                Giờ khám
                            </h6>
                            <select id="modalTimeSelect" class="form-select" required disabled>
                                <option value="">Chọn ngày trước</option>
                            </select>
                        </div>
                    </div>

                    <!-- Reason -->
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-notes-medical me-2"></i>
                            Lý do khám (không bắt buộc)
                        </h6>
                        <textarea id="modalReason" class="form-control" rows="3" 
                                  placeholder="Mô tả triệu chứng hoặc lý do khám bệnh..."></textarea>
                    </div>

                    <!-- Alert area -->
                    <div id="modalAlert" class="alert d-none" role="alert"></div>
                </form>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    Hủy
                </button>
                <button type="button" class="btn btn-primary" id="modalSubmitBtn" onclick="submitModalBooking()">
                    <i class="fas fa-calendar-check me-1"></i>
                    Đặt lịch
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Fixed Modal Backdrop - No blur for performance */
.modal-backdrop {
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 999998 !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
}

/* Modal Container */
.modal {
    z-index: 999999 !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
}

/* Ensure modal centers properly */
.modal-dialog {
    margin: auto;
    display: flex;
    align-items: center;
    min-height: calc(100vh - 2rem);
    position: relative;
    z-index: 1000000 !important;
    max-width: 550px;
    width: 90%;
}

/* Mobile responsiveness */
@media (max-width: 576px) {
    .modal-dialog {
        width: 95% !important;
        min-height: calc(100vh - 1rem) !important;
        margin: 0.5rem auto !important;
    }
    
    .modal-glass {
        max-height: 95vh !important;
        border-radius: 16px !important;
    }
    
    .modal-header {
        padding: 1.5rem 1.5rem !important;
    }
    
    .modal-body {
        padding: 1.5rem !important;
    }
    
    .modal-footer {
        padding: 1rem 1.5rem !important;
    }
    
    .modal-title {
        font-size: 1.25rem !important;
    }
}

/* Tablet responsiveness */
@media (max-width: 768px) {
    #appointmentModal .modal-dialog {
        max-width: 90% !important;
        margin: 1rem auto !important;
    }
}

/* Modal Specific Styles */
.modal-glass {
    border-radius: 24px;
    border: none;
    box-shadow: 
        0 32px 64px rgba(0, 0, 0, 0.3),
        0 0 0 1px rgba(255, 255, 255, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
    background: rgba(255, 255, 255, 0.98);
    overflow: hidden;
    position: relative;
    max-height: 90vh;
    width: 100%;
    margin: 0;
    z-index: 1000001 !important;
}

.modal-glass::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2);
    z-index: 1;
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 24px 24px 0 0;
    border-bottom: none;
    padding: 2rem 2.5rem;
    position: relative;
    z-index: 2;
}

.modal-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), transparent);
    pointer-events: none;
}

.modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.modal-header .btn-close {
    filter: invert(1);
    opacity: 0.8;
    transition: all 0.3s ease;
    border-radius: 8px;
    padding: 0.5rem;
}

.modal-header .btn-close:hover {
    opacity: 1;
    background: rgba(255, 255, 255, 0.1);
}

.modal-body {
    padding: 2.5rem;
    max-height: calc(90vh - 200px);
    overflow-y: auto;
}

.modal-footer {
    padding: 1.5rem 2.5rem;
    border-top: 1px solid rgba(226, 232, 240, 0.3);
    border-radius: 0 0 24px 24px;
    background: linear-gradient(135deg, rgba(248, 250, 252, 0.8), rgba(255, 255, 255, 0.8));
}

.form-select, .form-control {
    border: 2px solid rgba(226, 232, 240, 0.6);
    border-radius: 12px;
    padding: 0.875rem 1rem;
    transition: border-color 0.2s ease;
    background: white;
    font-size: 0.95rem;
}

.form-select:focus, .form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    background: white;
    outline: none;
}

.form-label {
    font-weight: 600;
    color: #4a5568;
    margin-bottom: 0.75rem;
    font-size: 0.95rem;
}

.btn {
    border-radius: 12px;
    padding: 0.875rem 1.75rem;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-size: 0.95rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border: none;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: rgba(226, 232, 240, 0.8);
    border: none;
    color: #4a5568;
}

.btn-secondary:hover {
    background: rgba(203, 213, 224, 0.9);
    transform: translateY(-1px);
}

.alert {
    border-radius: 10px;
    border: none;
    margin-bottom: 0;
}

.alert-success {
    background: linear-gradient(135deg, rgba(72, 187, 120, 0.1), rgba(72, 187, 120, 0.05));
    color: #2f855a;
}

.alert-danger {
    background: linear-gradient(135deg, rgba(245, 101, 101, 0.1), rgba(245, 101, 101, 0.05));
    color: #c53030;
}

/* Loading animation */
.modal-loading {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: white;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Modal Animation - Ultra Fast */
.modal.fade .modal-dialog {
    transform: translateY(-20px);
    transition: transform 0.15s ease-out, opacity 0.15s ease-out;
    opacity: 0;
}

.modal.show .modal-dialog {
    transform: translateY(0);
    opacity: 1;
}

/* Performance optimizations */
.modal-glass {
    will-change: transform, opacity;
}

.modal-backdrop {
    will-change: opacity;
}

/* Override any conflicting styles from other pages */
#appointmentModal {
    z-index: 999999 !important;
    position: fixed !important;
    display: none !important;
}

#appointmentModal.show {
    display: block !important;
}

#appointmentModal .modal-dialog {
    position: relative !important;
    width: auto !important;
    margin: 1.75rem auto !important;
    pointer-events: none !important;
    z-index: 1000000 !important;
}

#appointmentModal .modal-content {
    position: relative !important;
    display: flex !important;
    flex-direction: column !important;
    width: 100% !important;
    pointer-events: auto !important;
    background-clip: padding-box !important;
    outline: 0 !important;
    z-index: 1000001 !important;
}

/* Ensure modal is above all headers/navbars */
#appointmentModal,
#appointmentModal .modal-backdrop {
    z-index: 999999 !important;
}

/* Force modal to be on top of everything */
body.modal-open {
    overflow: hidden !important;
}

/* Prevent any other z-index conflicts */
.modal-backdrop.show {
    opacity: 0.5 !important;
    z-index: 999998 !important;
}

/* Button styling for header appointment button */
.auth-btn.appointment-btn {
    background: linear-gradient(135deg, #667eea, #764ba2) !important;
    color: white !important;
    border: none !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

.auth-btn.appointment-btn:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4) !important;
    color: white !important;
}

/* Ultra high z-index to override everything */
#appointmentModal .modal-backdrop.show {
    z-index: 999998 !important;
}

#appointmentModal.show {
    z-index: 999999 !important;
}

#appointmentModal.show .modal-dialog {
    z-index: 1000000 !important;
}

#appointmentModal.show .modal-content {
    z-index: 1000001 !important;
}

/* Force header to be below modal when modal is open */
body.modal-open .medical-header {
    z-index: 999989 !important;
}

body.modal-open .medical-header .user-account {
    z-index: 999989 !important;
}

body.modal-open .medical-header * {
    z-index: 999989 !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set minimum date to today
    document.getElementById('modalAppointmentDate').min = new Date().toISOString().split('T')[0];
    
    // Date change handler
    document.getElementById('modalAppointmentDate').addEventListener('change', function() {
        const doctorId = document.getElementById('modalDoctorSelect').value;
        if (doctorId && this.value) {
            loadModalTimeSlots(doctorId, this.value);
        }
    });
    
    // Doctor change handler
    document.getElementById('modalDoctorSelect').addEventListener('change', function() {
        const date = document.getElementById('modalAppointmentDate').value;
        if (this.value && date) {
            loadModalTimeSlots(this.value, date);
        } else {
            const timeSelect = document.getElementById('modalTimeSelect');
            timeSelect.innerHTML = '<option value="">Chọn ngày trước</option>';
            timeSelect.disabled = true;
        }
    });
});

// Cache for doctors data
let doctorsCache = null;

// Load doctors list with caching
function loadModalDoctors() {
    // Return cached data if available
    if (doctorsCache) {
        populateDoctorSelect(doctorsCache);
        return;
    }
    
    // Show loading state
    const select = document.getElementById('modalDoctorSelect');
    select.innerHTML = '<option value="">Đang tải...</option>';
    
    fetch('/api/get-doctors.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                doctorsCache = data.doctors; // Cache the data
                populateDoctorSelect(data.doctors);
            } else {
                select.innerHTML = '<option value="">Lỗi tải dữ liệu</option>';
            }
        })
        .catch(error => {
            console.error('Error loading doctors:', error);
            select.innerHTML = '<option value="">Lỗi tải dữ liệu</option>';
        });
}

// Populate doctor select dropdown
function populateDoctorSelect(doctors) {
    const select = document.getElementById('modalDoctorSelect');
    select.innerHTML = '<option value="">-- Chọn bác sĩ --</option>';
    
    doctors.forEach(doctor => {
        const option = document.createElement('option');
        option.value = doctor.doctor_id;
        option.textContent = `${doctor.full_name} - ${doctor.specialization}`;
        option.dataset.clinicId = doctor.clinic_id;
        select.appendChild(option);
    });
}

// Cache for time slots
let timeSlotsCache = {};

// Load available time slots with caching
function loadModalTimeSlots(doctorId, date) {
    const timeSelect = document.getElementById('modalTimeSelect');
    const cacheKey = `${doctorId}-${date}`;
    
    // Check cache first
    if (timeSlotsCache[cacheKey]) {
        populateTimeSlots(timeSlotsCache[cacheKey], timeSelect);
        return;
    }
    
    timeSelect.innerHTML = '<option value="">Đang tải...</option>';
    timeSelect.disabled = true;
    
    fetch(`/api/get-time-slots.php?doctor_id=${doctorId}&date=${date}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.slots.length > 0) {
                timeSlotsCache[cacheKey] = data.slots; // Cache the data
                populateTimeSlots(data.slots, timeSelect);
            } else {
                timeSelect.innerHTML = '<option value="">Không có lịch trống</option>';
                timeSelect.disabled = true;
            }
        })
        .catch(error => {
            console.error('Error loading time slots:', error);
            timeSelect.innerHTML = '<option value="">Lỗi tải dữ liệu</option>';
            timeSelect.disabled = true;
        });
}

// Populate time slots dropdown
function populateTimeSlots(slots, selectElement) {
    selectElement.innerHTML = '<option value="">-- Chọn giờ --</option>';
    
    slots.forEach(slot => {
        const option = document.createElement('option');
        option.value = slot.time;
        option.textContent = slot.time;
        option.disabled = slot.booked;
        if (slot.booked) {
            option.textContent += ' (Đã đặt)';
        }
        selectElement.appendChild(option);
    });
    
    selectElement.disabled = false;
}

// Submit booking
function submitModalBooking() {
    const form = document.getElementById('modalBookingForm');
    const doctorSelect = document.getElementById('modalDoctorSelect');
    const doctorId = doctorSelect.value;
    const clinicId = doctorSelect.selectedOptions[0]?.dataset.clinicId;
    const date = document.getElementById('modalAppointmentDate').value;
    const time = document.getElementById('modalTimeSelect').value;
    const reason = document.getElementById('modalReason').value;
    
    // Validation
    if (!doctorId) {
        showModalAlert('Vui lòng chọn bác sĩ', 'danger');
        return;
    }
    
    if (!date) {
        showModalAlert('Vui lòng chọn ngày khám', 'danger');
        return;
    }
    
    if (!time) {
        showModalAlert('Vui lòng chọn giờ khám', 'danger');
        return;
    }
    
    // Show loading
    const submitBtn = document.getElementById('modalSubmitBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="modal-loading me-2"></span>Đang đặt lịch...';
    submitBtn.disabled = true;
    
    // Submit data
    const formData = new FormData();
    formData.append('book_appointment', '1');
    formData.append('doctor_id', doctorId);
    formData.append('clinic_id', clinicId);
    formData.append('appointment_date', date);
    formData.append('appointment_time', time);
    formData.append('reason', reason);
    
    fetch('/api/book-appointment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showModalAlert('Đặt lịch thành công! Lịch hẹn đang chờ xác nhận.', 'success');
            form.reset();
            
            // Auto-close modal after 2 seconds
            setTimeout(() => {
                bootstrap.Modal.getInstance(document.getElementById('appointmentModal')).hide();
                
                // Redirect to appointments page if available
                if (typeof window !== 'undefined' && window.location.pathname !== '/appointments.php') {
                    setTimeout(() => {
                        window.location.href = '/appointments.php';
                    }, 1000);
                }
            }, 2000);
        } else {
            showModalAlert(data.message || 'Có lỗi xảy ra. Vui lòng thử lại!', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showModalAlert('Có lỗi xảy ra. Vui lòng thử lại!', 'danger');
    })
    .finally(() => {
        // Restore button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Show alert in modal
function showModalAlert(message, type) {
    const alert = document.getElementById('modalAlert');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>${message}`;
    alert.classList.remove('d-none');
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        alert.classList.add('d-none');
    }, 5000);
}

// Function to open modal (can be called from other pages)
function openAppointmentModal() {
    // Load doctors only if not cached
    if (!doctorsCache) {
        loadModalDoctors();
    }
    
    const modalElement = document.getElementById('appointmentModal');
    if (modalElement) {
        // Ensure modal is properly positioned and styled
        modalElement.style.zIndex = '999999';
        modalElement.style.position = 'fixed';
        
        const modal = new bootstrap.Modal(modalElement, {
            backdrop: 'static',
            keyboard: true
        });
        
        // Add event listener to ensure proper styling when modal is shown
        modalElement.addEventListener('shown.bs.modal', function() {
            // Force the backdrop to have correct z-index
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.style.zIndex = '999998';
            }
            
            // Force modal dialog to have higher z-index
            const modalDialog = modalElement.querySelector('.modal-dialog');
            if (modalDialog) {
                modalDialog.style.zIndex = '1000000';
            }
            
            // Force modal content to be on top
            const modalContent = modalElement.querySelector('.modal-content');
            if (modalContent) {
                modalContent.style.zIndex = '1000001';
            }
            
            // Ensure body has modal-open class for proper overflow handling
            document.body.classList.add('modal-open');
        });
        
        // Clean up when modal is hidden
        modalElement.addEventListener('hidden.bs.modal', function() {
            document.body.classList.remove('modal-open');
        });
        
        modal.show();
    }
}
</script> 