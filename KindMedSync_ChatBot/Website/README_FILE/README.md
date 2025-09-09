# QickMed - Hệ thống Quản lý Phòng khám

## 📚 Thư viện và Công nghệ sử dụng

### Frontend Libraries

#### CSS Frameworks & UI

- **Bootstrap 5.3.0** - Framework CSS responsive và component library
  - URL: `https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css`
- **Font Awesome 6.4.0** - Icon library với hơn 7000+ icons
  - URL: `https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css`
- **Select2 4.1.0** - Advanced select boxes với search và multi-select
  - URL: `https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css`
- **Flatpickr** - Modern date/time picker library
  - URL: `https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css`
- **SweetAlert2** - Beautiful, responsive alerts và confirmations
  - URL: `https://cdn.jsdelivr.net/npm/sweetalert2@11`

#### JavaScript Libraries

- **jQuery 3.6.0** - Fast JavaScript library
  <!-- - URL: `https://code.jquery.com/jquery-3.6.0.min.js`  -->
- **Bootstrap Bundle JS 5.3.0** - Bootstrap JavaScript components
  - URL: `https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js`
- **Select2 JS** - JavaScript functionality cho Select2
  - URL: `https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js`
- **Flatpickr JS** - Date picker functionality
  - URL: `https://cdn.jsdelivr.net/npm/flatpickr`
- **AOS (Animate On Scroll)** - Animation library for scroll effects
  - URL: `https://unpkg.com/aos@2.3.1/dist/aos.js`

### Backend Technologies

- **PHP 8.0+** - Server-side scripting language
- **MySQL 8.0+** - Relational database management system
- **Apache/Nginx** - Web server

### Address Integration

- **Vietnam Provinces API** - `provinces.open-api.vn` - Complete Vietnam address data
  - 63 Tỉnh/Thành phố
  - 700+ Quận/Huyện
  - 10,000+ Phường/Xã/Thị trấn
  - API URL: `https://provinces.open-api.vn/api/?depth=3`

### Appointment System Features

- **Real-time booking** - Live availability checking
- **Cascade address selection** - Vietnam location hierarchy
- **Modal integration** - Reusable booking modal component
- **Responsive design** - Mobile-first approach
- **Status management** - Pending → Confirmed → Completed → Canceled

### API Endpoints

- `/api/get-doctors.php` - Fetch available doctors
- `/api/get-time-slots.php` - Get available appointment slots
- `/api/book-appointment.php` - Process appointment booking
- External: `provinces.open-api.vn/api/?depth=3` - Vietnam address data

### File Structure

```
├── appointments.php              # User appointment management
├── book-appointment.php          # Appointment booking page
├── profile.php                   # User profile with address integration
├── includes/
│   └── appointment-modal.php     # Reusable booking modal
├── api/
│   ├── get-doctors.php          # Doctors API
│   ├── get-time-slots.php       # Time slots API
│   └── book-appointment.php     # Booking API
└── assets/
    ├── css/style.css            # Custom styles
    └── images/                  # Static images
```

### Key Features Implemented

#### 🏥 Appointment Management System

- ✅ **User Appointment Dashboard** - View, filter, cancel appointments
- ✅ **Professional Booking Page** - Step-by-step appointment booking
- ✅ **Reusable Modal Component** - Quick booking from any page
- ✅ **Real-time Availability** - Live checking of doctor schedules
- ✅ **Status Tracking** - Pending → Confirmed → Completed → Canceled

#### 🇻🇳 Vietnam Address Integration

- ✅ **Complete Location Data** - All 63 provinces with districts/wards
- ✅ **Cascade Selection** - Province → District → Ward hierarchy
- ✅ **Search Functionality** - Find locations quickly with Select2
- ✅ **Auto-population** - Load existing addresses for editing
- ✅ **Form Integration** - Seamless integration with user profiles

#### 🎨 UI/UX Enhancements

- ✅ **Modern Design** - Professional gradient backgrounds
- ✅ **Header Overlap Fix** - Proper spacing for fixed navigation
- ✅ **Responsive Layout** - Mobile-first responsive design
- ✅ **Interactive Elements** - Smooth animations and transitions
- ✅ **Loading States** - User feedback during async operations

#### 🔄 Workflow Integration

- ✅ **Admin Approval System** - All appointments start as 'pending'
- ✅ **Status Management** - Complete appointment lifecycle
- ✅ **User Notifications** - Success/error messages with auto-hide
- ✅ **Validation** - Client and server-side form validation
- ✅ **Conflict Prevention** - Check for time slot conflicts

### Usage Examples

#### Include Appointment Modal in any page:

```php
<?php include 'includes/appointment-modal.php'; ?>

<!-- Trigger button -->
<button onclick="openAppointmentModal()" class="btn btn-primary">
    Đặt lịch khám
</button>
```

#### Vietnam Address Selection:

```javascript
// Initialize cascade selection
$("#countrySelect").change(function () {
  if ($(this).val() === "Vietnam") {
    loadProvinces(); // Load all 63 provinces
  }
});
```

#### API Usage:

```javascript
// Get available time slots
fetch(`/api/get-time-slots.php?doctor_id=${doctorId}&date=${date}`)
  .then((response) => response.json())
  .then((data) => {
    // Handle available slots
  });
```

### Deployment Notes

- Ensure PHP 8.0+ and MySQL 8.0+ are installed
- Configure database connection in `includes/db.php`
- Set proper file permissions for upload directories
- Enable mod_rewrite for clean URLs
- Configure SSL for production use

### 🚨 Git Issues & Solutions

**Gặp vấn đề khi push code lên Git?** Dữ liệu bị thay đổi không mong muốn?

- 📖 **Đọc hướng dẫn chi tiết**: [GIT_ISSUES_README.md](GIT_ISSUES_README.md)
- 🔧 **Sửa nhanh**: Chạy `fix-git-issues.bat` (Windows)
- ⚙️ **Files đã chuẩn bị**: `.gitattributes` và `.editorconfig`

**Nguyên nhân phổ biến:**

- Line endings khác nhau (Windows CRLF vs Unix LF)
- Encoding issues (UTF-8, UTF-8 BOM, ANSI)
- Git autocrlf settings
- Trailing whitespace

**Giải pháp nhanh:**

```bash
git config --global core.autocrlf true
git config --global core.safecrlf warn
```

### Development Team

- **Frontend**: Bootstrap 5, Modern CSS3, JavaScript ES6+
- **Backend**: PHP 8, MySQL, RESTful APIs
- **Integration**: Vietnam Address API, Real-time validation
- **Design**: Mobile-first, Professional healthcare theme

---

**Version**: 1.0.0  
**Last Updated**: 2025  
**License**: Proprietary  
**Contact**: Dalziel
