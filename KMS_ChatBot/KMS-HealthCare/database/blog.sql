 -- Tạo bảng categories (danh mục bài viết)
CREATE TABLE IF NOT EXISTS blog_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tạo bảng authors (tác giả)
CREATE TABLE IF NOT EXISTS blog_authors (
    author_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    name VARCHAR(100) NOT NULL,
    avatar VARCHAR(255),
    bio TEXT,
    title VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Tạo bảng posts (bài viết)
CREATE TABLE IF NOT EXISTS blog_posts (
    post_id INT PRIMARY KEY AUTO_INCREMENT,
    author_id INT,
    category_id INT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content TEXT NOT NULL,
    excerpt TEXT,
    featured_image VARCHAR(255),
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    is_featured BOOLEAN DEFAULT FALSE,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL,
    FOREIGN KEY (author_id) REFERENCES blog_authors(author_id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES blog_categories(category_id) ON DELETE SET NULL
);

-- Tạo bảng tags (thẻ)
CREATE TABLE IF NOT EXISTS blog_tags (
    tag_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -- Tạo bảng post_tags (quan hệ nhiều-nhiều giữa posts và tags)
-- CREATE TABLE IF NOT EXISTS blog_post_tags (
--     post_id INT,
--     tag_id INT,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     PRIMARY KEY (post_id, tag_id),
--     FOREIGN KEY (post_id) REFERENCES blog_posts(post_id) ON DELETE CASCADE,
--     FOREIGN KEY (tag_id) REFERENCES blog_tags(tag_id) ON DELETE CASCADE
-- );

-- -- Tạo bảng comments (bình luận)
-- CREATE TABLE IF NOT EXISTS blog_comments (
--     comment_id INT PRIMARY KEY AUTO_INCREMENT,
--     post_id INT,
--     user_id INT,
--     parent_id INT,
--     content TEXT NOT NULL,
--     status ENUM('pending', 'approved', 'spam') DEFAULT 'pending',
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
--     FOREIGN KEY (post_id) REFERENCES blog_posts(post_id) ON DELETE CASCADE,
--     FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
--     FOREIGN KEY (parent_id) REFERENCES blog_comments(comment_id) ON DELETE SET NULL
-- );

-- -- Tạo bảng subscribers (người đăng ký nhận tin)
-- CREATE TABLE IF NOT EXISTS blog_subscribers (
--     subscriber_id INT PRIMARY KEY AUTO_INCREMENT,
--     email VARCHAR(255) NOT NULL UNIQUE,
--     status ENUM('active', 'unsubscribed') DEFAULT 'active',
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
-- );

-- Insert một số danh mục mẫu
INSERT INTO blog_categories (name, slug, description) VALUES
('Chăm sóc sức khỏe', 'cham-soc-suc-khoe', 'Các bài viết về chăm sóc sức khỏe tổng quát'),
('Dinh dưỡng', 'dinh-duong', 'Các bài viết về dinh dưỡng và chế độ ăn uống'),
('Thể dục', 'the-duc', 'Các bài viết về thể dục và vận động'),
('Giấc ngủ', 'giac-ngu', 'Các bài viết về giấc ngủ và sức khỏe'),
('Tâm lý', 'tam-ly', 'Các bài viết về sức khỏe tâm lý'),
('Y học', 'y-hoc', 'Các bài viết về y học và điều trị');

-- Insert một số tác giả mẫu
INSERT INTO blog_authors (name, avatar, bio, title) VALUES
('BS. Nguyễn Văn A', '/assets/images/authors/author-1.jpg', 'Bác sĩ chuyên khoa Nội tổng quát với 10 năm kinh nghiệm', 'Bác sĩ chuyên khoa'),
('BS. Trần Thị B', '/assets/images/authors/author-2.jpg', 'Bác sĩ chuyên khoa Dinh dưỡng', 'Bác sĩ dinh dưỡng'),
('BS. Lê Văn C', '/assets/images/authors/author-3.jpg', 'Bác sĩ chuyên khoa Thể thao', 'Bác sĩ thể thao'),
('BS. Phạm Thị D', '/assets/images/authors/author-4.jpg', 'Bác sĩ chuyên khoa Tâm lý', 'Bác sĩ tâm lý'),
('ThS. Hoàng Văn E', '/assets/images/authors/author-5.jpg', 'Thạc sĩ Y học cổ truyền', 'Thạc sĩ y học');

-- Insert một số bài viết mẫu
INSERT INTO blog_posts (author_id, category_id, title, slug, content, excerpt, featured_image, status, is_featured, published_at) VALUES
(1, 1, '10 Cách Tăng Cường Hệ Miễn Dịch Tự Nhiên', '10-cach-tang-cuong-he-mien-dich-tu-nhien', 
'<p>Nội dung chi tiết về cách tăng cường hệ miễn dịch...</p>', 
'Khám phá những phương pháp đơn giản nhưng hiệu quả để tăng cường hệ miễn dịch của bạn thông qua chế độ ăn uống, lối sống và các hoạt động hàng ngày...',
'/assets/images/blog/featured-1.jpg', 'published', 1, NOW()),

(2, 2, 'Chế Độ Ăn Uống Lành Mạnh Cho Tim Mạch', 'che-do-an-uong-lanh-manh-cho-tim-mach',
'<p>Nội dung chi tiết về chế độ ăn uống tốt cho tim mạch...</p>',
'Tìm hiểu về những thực phẩm tốt cho tim mạch và cách xây dựng chế độ ăn uống khoa học...',
'/assets/images/blog/post-1.jpg', 'published', 0, NOW()),

(3, 3, 'Lợi Ích Của Việc Tập Thể Dục Đều Đặn', 'loi-ich-cua-viec-tap-the-duc-deu-dan',
'<p>Nội dung chi tiết về lợi ích của tập thể dục...</p>',
'Khám phá những lợi ích tuyệt vời của việc duy trì thói quen tập luyện thể dục hàng ngày...',
'/assets/images/blog/post-2.jpg', 'published', 0, NOW()),

(4, 4, 'Tầm Quan Trọng Của Giấc Ngủ Chất Lượng', 'tam-quan-trong-cua-giac-ngu-chat-luong',
'<p>Nội dung chi tiết về giấc ngủ...</p>',
'Hiểu rõ về tác động của giấc ngủ đến sức khỏe và cách cải thiện chất lượng giấc ngủ...',
'/assets/images/blog/post-3.jpg', 'published', 0, NOW()),

(5, 5, 'Quản Lý Stress Hiệu Quả Trong Cuộc Sống', 'quan-ly-stress-hieu-qua-trong-cuoc-song',
'<p>Nội dung chi tiết về quản lý stress...</p>',
'Học cách nhận biết và quản lý stress để duy trì sức khỏe tinh thần tốt...',
'/assets/images/blog/post-4.jpg', 'published', 0, NOW());