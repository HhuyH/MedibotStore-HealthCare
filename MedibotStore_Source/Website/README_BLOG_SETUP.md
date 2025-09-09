# Blog System Setup Guide

## 🎯 Tổng quan

Blog system đã được tích hợp vào website MediSync với đầy đủ tính năng:

- Hiển thị danh sách bài viết với phân trang
- Trang chi tiết bài viết với thiết kế đẹp
- Tìm kiếm và lọc theo danh mục
- Sidebar với bài viết mới nhất và danh mục phổ biến
- Reading progress indicator
- Social sharing
- SEO-friendly

## 📁 Cấu trúc Files

```
/
├── blog.php                           # Trang danh sách bài viết
├── blog-post.php                      # Trang chi tiết bài viết
├── includes/blog_functions.php        # Các hàm xử lý blog
├── assets/css/blog.css                # CSS cho trang blog chính
├── assets/css/blog-post.css           # CSS cho trang chi tiết bài viết
├── assets/js/blog.js                  # JavaScript cho blog
├── assets/js/blog-post.js             # JavaScript cho trang chi tiết
├── database/setup_blog.sql            # Script tạo bảng database
├── database/sample_blog_data.sql      # Dữ liệu mẫu
└── README_BLOG_SETUP.md               # File hướng dẫn này
```

## 🗄️ Database Setup

### Bước 1: Tạo các bảng cần thiết

Chạy file SQL để tạo bảng:

```sql
SOURCE database/setup_blog.sql;
```

Hoặc copy nội dung file và chạy trong phpMyAdmin/MySQL Workbench.

### Bước 2: Thêm dữ liệu mẫu

```sql
SOURCE database/sample_blog_data.sql;
```

### Cấu trúc Database

**Bảng chính:**

- `blog_categories` - Danh mục bài viết
- `blog_authors` - Tác giả
- `blog_posts` - Bài viết
- `blog_tags` - Tags (tùy chọn)
- `blog_subscribers` - Đăng ký newsletter

## 🚀 Cách sử dụng

### Truy cập Blog

1. **Trang chính:** `http://localhost/blog.php`
2. **Chi tiết bài viết:** `http://localhost/blog-post.php?slug=ten-bai-viet`
3. **Lọc theo danh mục:** `http://localhost/blog.php?category=1`
4. **Tìm kiếm:** `http://localhost/blog.php?search=tu-khoa`

### URL Samples với dữ liệu mẫu

- `http://localhost/blog-post.php?slug=10-cach-tang-cuong-he-mien-dich-tu-nhien`
- `http://localhost/blog-post.php?slug=quan-ly-stress-hieu-qua-trong-cuoc-song`
- `http://localhost/blog-post.php?slug=che-do-an-uong-lanh-manh-cho-tim-mach`

## 🎨 Thiết kế Features

### Trang Blog Chính (blog.php)

- ✅ Header với breadcrumb gradient
- ✅ Search functionality
- ✅ Category filters
- ✅ Featured post section
- ✅ Posts grid layout
- ✅ Pagination
- ✅ Sidebar với recent posts và categories

### Trang Chi tiết (blog-post.php)

- ✅ Background trắng sạch sẽ
- ✅ Breadcrumb navigation
- ✅ Post header với category badge
- ✅ Author information
- ✅ Post meta (date, read time, views)
- ✅ Featured image với hover effects
- ✅ Rich content formatting
- ✅ Tags section
- ✅ Social sharing buttons
- ✅ Author bio
- ✅ Related posts section
- ✅ Reading progress indicator

## 🔧 Tùy chỉnh

### Thêm bài viết mới

```php
// Ví dụ thêm bài viết qua code
$data = [
    'author_id' => 1,
    'category_id' => 1,
    'title' => 'Tiêu đề bài viết',
    'slug' => 'tieu-de-bai-viet',
    'content' => '<p>Nội dung HTML...</p>',
    'excerpt' => 'Tóm tắt ngắn',
    'featured_image' => 'path/to/image.jpg',
    'status' => 'published',
    'is_featured' => 0,
    'published_at' => date('Y-m-d H:i:s')
];

create_blog_post($data);
```

### Thay đổi số bài viết trên trang

Trong `blog.php` dòng 8:

```php
$limit = 6; // Thay đổi số này
```

### Thay đổi màu sắc

Trong `assets/css/blog-post.css`:

```css
/* Breadcrumb gradient */
.breadcrumb-section {
  background: linear-gradient(135deg, #YOUR_COLOR1 0%, #YOUR_COLOR2 100%);
}

/* Category badge */
.post-category a {
  background: linear-gradient(135deg, #YOUR_COLOR1 0%, #YOUR_COLOR2 100%);
}
```

## 📱 Responsive Design

- ✅ Mobile-first design
- ✅ Tablet optimization
- ✅ Desktop enhancement
- ✅ Touch-friendly interactions

## ⚡ Performance

- ✅ Image lazy loading
- ✅ Optimized database queries
- ✅ CSS/JS minification ready
- ✅ SEO meta tags
- ✅ Reading progress indicator

## 🔍 SEO Features

- ✅ Dynamic page titles
- ✅ Meta descriptions
- ✅ Open Graph tags
- ✅ Structured URLs
- ✅ Image alt tags
- ✅ Breadcrumb schema

## 🚨 Troubleshooting

### Lỗi thường gặp:

1. **Không hiển thị bài viết:**

   - Kiểm tra database connection
   - Đảm bảo đã import dữ liệu mẫu
   - Kiểm tra status = 'published'

2. **CSS/JS không load:**

   - Kiểm tra đường dẫn files
   - Clear browser cache
   - Kiểm tra file permissions

3. **Lỗi 404 chi tiết bài viết:**
   - Kiểm tra slug trong database
   - Kiểm tra function `get_blog_post()`

## 📞 Support

Nếu gặp vấn đề, hãy kiểm tra:

1. Database connection trong `includes/db.php`
2. Blog functions trong `includes/blog_functions.php`
3. Error logs trong `/logs/`

---

**Tạo bởi:** MediSync Development Team
**Cập nhật:** December 2024
