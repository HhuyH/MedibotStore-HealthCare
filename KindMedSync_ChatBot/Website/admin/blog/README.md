# Blog Admin Management

Hệ thống quản lý blog cho admin với 2 trang chính:

## 🏷️ Quản lý danh mục - `categories.php`

**URL:** `http://localhost/admin/blog/categories.php`

### Tính năng:

- ✅ Xem danh sách tất cả danh mục blog
- ✅ Thêm danh mục mới
- ✅ Chỉnh sửa danh mục
- ✅ Xóa danh mục (nếu không có bài viết nào)
- ✅ Tìm kiếm danh mục
- ✅ Phân trang
- ✅ Hiển thị số lượng bài viết trong từng danh mục

### Cách sử dụng:

1. **Thêm danh mục mới:**

   - Nhấp nút "Thêm danh mục"
   - Điền tên danh mục và mô tả
   - Nhấp "Thêm mới"

2. **Chỉnh sửa danh mục:**

   - Nhấp biểu tượng ✏️ trong cột "Thao tác"
   - Sửa thông tin
   - Nhấp "Cập nhật"

3. **Xóa danh mục:**
   - Nhấp biểu tượng 🗑️ trong cột "Thao tác"
   - Xác nhận xóa
   - **Lưu ý:** Không thể xóa danh mục đang có bài viết

---

## 📝 Quản lý bài viết - `posts.php`

**URL:** `http://localhost/admin/blog/posts.php`

### Tính năng:

- ✅ Xem danh sách tất cả bài viết
- ✅ Thêm bài viết mới với editor TinyMCE
- ✅ Chỉnh sửa bài viết
- ✅ Xóa bài viết (chuyển sang trạng thái archived)
- ✅ Tìm kiếm bài viết
- ✅ Lọc theo: danh mục, tác giả, trạng thái
- ✅ Phân trang
- ✅ Upload ảnh đại diện hoặc dùng URL
- ✅ Đánh dấu bài viết nổi bật
- ✅ Quản lý trạng thái: Draft/Published/Archived

### Cách sử dụng:

#### 1. **Thêm bài viết mới:**

- Nhấp nút "Thêm bài viết"
- Điền các thông tin:
  - **Tiêu đề:** Tiêu đề bài viết
  - **Nội dung:** Sử dụng editor TinyMCE
  - **Mô tả ngắn:** Tóm tắt ngắn gọn
  - **Danh mục:** Chọn từ dropdown
  - **Tác giả:** Chọn Admin hoặc Doctor
  - **Trạng thái:** Draft/Published/Archived
  - **Ảnh đại diện:** Upload file hoặc dùng URL
  - **Nổi bật:** Tick vào checkbox nếu muốn
- Nhấp "Thêm mới"

#### 2. **Chỉnh sửa bài viết:**

- Nhấp biểu tượng ✏️ trong cột "Thao tác"
- Sửa thông tin cần thiết
- Nhấp "Cập nhật"

#### 3. **Xem bài viết:**

- Nhấp biểu tượng 👁️ để xem bài viết trên frontend

#### 4. **Lọc và tìm kiếm:**

- **Tìm kiếm:** Nhập từ khóa vào ô tìm kiếm
- **Lọc theo danh mục:** Chọn danh mục từ dropdown
- **Lọc theo tác giả:** Chọn Admin hoặc Doctor
- **Lọc theo trạng thái:** Chọn trạng thái từ dropdown
- Nhấp "Lọc" để áp dụng

---

## 🛠️ Cài đặt & Thiết lập

### 1. Chạy các file SQL theo thứ tự:

```sql
-- 1. Tạo cấu trúc database
source database/setup_blog.sql;

-- 2. Thêm tác giả cố định (Admin & Doctor)
source database/fixed_blog_authors.sql;

-- 3. Thêm dữ liệu mẫu (tùy chọn)
source database/sample_blog_data.sql;
```

### 2. Tạo thư mục upload:

```bash
mkdir -p assets/images/blog/
chmod 755 assets/images/blog/
```

### 3. Kiểm tra quyền:

- Đảm bảo user đã đăng nhập với `role_id = 1` (admin)
- Kiểm tra file `includes/blog_functions.php` có tồn tại

---

## 📊 Trạng thái bài viết

| Trạng thái    | Mô tả                   | Hiển thị trên frontend |
| ------------- | ----------------------- | ---------------------- |
| **Draft**     | Bản nháp, chưa xuất bản | ❌ Không               |
| **Published** | Đã xuất bản             | ✅ Có                  |
| **Archived**  | Đã xóa/lưu trữ          | ❌ Không               |

---

## 🎨 Tính năng nổi bật

### TinyMCE Editor:

- Rich text editor với đầy đủ tính năng
- Hỗ trợ định dạng văn bản, bảng, liên kết
- Có thể chèn ảnh và media

### Upload ảnh:

- **Upload file:** Chọn file từ máy tính
- **URL ảnh:** Dùng link ảnh từ internet
- **Giữ ảnh hiện tại:** Khi chỉnh sửa

### Tác giả cố định:

- **Admin:** Quản trị viên hệ thống
- **Doctor:** Đội ngũ bác sĩ chuyên nghiệp
- Không thể thêm tác giả mới, chỉ chọn 1 trong 2 loại

### Responsive Design:

- Giao diện responsive, tương thích mobile
- Sử dụng Bootstrap 5
- Icon Font Awesome

---

## 🔗 Liên kết quan trọng

- **Trang quản lý danh mục:** `/admin/blog/categories.php`
- **Trang quản lý bài viết:** `/admin/blog/posts.php`
- **Trang blog frontend:** `/blog.php`
- **Trang chi tiết bài viết:** `/blog-post.php?slug=slug-bai-viet`

---

## 🐛 Troubleshooting

### Lỗi thường gặp:

1. **"Không tìm thấy danh mục/tác giả"**

   - Chạy lại file SQL để tạo dữ liệu mẫu

2. **"Không thể upload ảnh"**

   - Kiểm tra quyền thư mục `assets/images/blog/`
   - Kiểm tra dung lượng file (max 5MB)

3. **"Access denied"**

   - Kiểm tra session và quyền admin (`role_id = 1`)

4. **"TinyMCE không load"**
   - Kiểm tra kết nối internet
   - Có thể download TinyMCE về local nếu cần

---

## 📈 Tính năng tương lai

- [ ] Hệ thống bình luận
- [ ] Tags cho bài viết
- [ ] SEO optimization
- [ ] Scheduled publishing
- [ ] Bulk actions
- [ ] Advanced media management

---

_Tài liệu được cập nhật lần cuối: <?php echo date('d/m/Y H:i'); ?>_
