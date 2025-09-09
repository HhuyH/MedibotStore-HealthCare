<?php
require_once 'db.php';

// Hàm lấy danh sách bài viết
function get_blog_posts($limit = 10, $offset = 0, $category_id = null, $search = null) {
    global $conn;
    
    $sql = "SELECT p.*, c.name as category_name, a.name as author_name, a.avatar as author_avatar 
            FROM blog_posts p 
            LEFT JOIN blog_categories c ON p.category_id = c.category_id 
            LEFT JOIN blog_authors a ON p.author_id = a.author_id 
            WHERE p.status = 'published'";
    
    $params = [];
    $types = "";
    
    if ($category_id) {
        $sql .= " AND p.category_id = ?";
        $params[] = $category_id;
        $types .= "i";
    }
    
    if ($search) {
        $sql .= " AND (p.title LIKE ? OR p.content LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types .= "ss";
    }
    
    $sql .= " ORDER BY p.published_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Hàm lấy bài viết theo slug
function get_blog_post($slug) {
    global $conn;
    
    $sql = "SELECT p.*, c.name as category_name, a.name as author_name, a.avatar as author_avatar 
            FROM blog_posts p 
            LEFT JOIN blog_categories c ON p.category_id = c.category_id 
            LEFT JOIN blog_authors a ON p.author_id = a.author_id 
            WHERE p.slug = ? AND p.status = 'published'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Hàm lấy danh mục
function get_blog_categories() {
    global $conn;
    
    $sql = "SELECT c.*, COUNT(p.post_id) as post_count 
            FROM blog_categories c 
            LEFT JOIN blog_posts p ON c.category_id = p.category_id 
            GROUP BY c.category_id";
    
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Hàm lấy bài viết mới nhất
function get_recent_posts($limit = 3) {
    global $conn;
    
    $sql = "SELECT p.*, c.name as category_name 
            FROM blog_posts p 
            LEFT JOIN blog_categories c ON p.category_id = c.category_id 
            WHERE p.status = 'published' 
            ORDER BY p.published_at DESC 
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Hàm tạo bài viết mới
function create_blog_post($data) {
    global $conn;
    
    $sql = "INSERT INTO blog_posts (author_id, category_id, title, slug, content, excerpt, 
            featured_image, status, is_featured, published_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissssssss", 
        $data['author_id'],
        $data['category_id'],
        $data['title'],
        $data['slug'],
        $data['content'],
        $data['excerpt'],
        $data['featured_image'],
        $data['status'],
        $data['is_featured'],
        $data['published_at']
    );
    
    return $stmt->execute();
}

// Hàm cập nhật bài viết
function update_blog_post($post_id, $data) {
    global $conn;
    
    $sql = "UPDATE blog_posts SET 
            category_id = ?,
            title = ?,
            slug = ?,
            content = ?,
            excerpt = ?,
            featured_image = ?,
            status = ?,
            is_featured = ?,
            published_at = ?
            WHERE post_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssssssi", 
        $data['category_id'],
        $data['title'],
        $data['slug'],
        $data['content'],
        $data['excerpt'],
        $data['featured_image'],
        $data['status'],
        $data['is_featured'],
        $data['published_at'],
        $post_id
    );
    
    return $stmt->execute();
}

// Hàm xóa bài viết
function delete_blog_post($post_id) {
    global $conn;
    
    $sql = "DELETE FROM blog_posts WHERE post_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $post_id);
    return $stmt->execute();
}

// Hàm tạo slug từ tiêu đề
function create_slug($title) {
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

// Hàm tăng lượt xem
function increment_post_views($post_id) {
    global $conn;
    
    $sql = "UPDATE blog_posts SET view_count = view_count + 1 WHERE post_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $post_id);
    return $stmt->execute();
}

// Hàm lấy bài viết liên quan
function get_related_posts($post_id, $category_id, $limit = 3) {
    global $conn;
    
    $sql = "SELECT p.*, c.name as category_name 
            FROM blog_posts p 
            LEFT JOIN blog_categories c ON p.category_id = c.category_id 
            WHERE p.category_id = ? AND p.post_id != ? AND p.status = 'published' 
            ORDER BY p.published_at DESC 
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $category_id, $post_id, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Hàm đăng ký nhận tin
function subscribe_newsletter($email) {
    global $conn;
    
    $sql = "INSERT INTO blog_subscribers (email) VALUES (?) 
            ON DUPLICATE KEY UPDATE status = 'active'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    return $stmt->execute();
}

// Hàm hủy đăng ký nhận tin
function unsubscribe_newsletter($email) {
    global $conn;
    
    $sql = "UPDATE blog_subscribers SET status = 'unsubscribed' WHERE email = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    return $stmt->execute();
}

/**
 * Get total number of blog posts
 * @param int|null $category_id Category ID to filter by
 * @param string $search Search term to filter by
 * @return int Total number of posts
 */
function get_blog_posts_count($category_id = null, $search = '') {
    $sql = "SELECT COUNT(*) as total FROM blog_posts WHERE status = 'published'";
    $params = array();
    
    if ($category_id) {
        $sql .= " AND category_id = ?";
        $params[] = $category_id;
    }
    
    if ($search) {
        $sql .= " AND (title LIKE ? OR content LIKE ? OR excerpt LIKE ?)";
        $search_term = "%{$search}%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $result = fetch_one($sql, $params);
    return $result['total'];
}

/**
 * Get the most recent featured blog post
 * @return array|null Featured post data or null if no featured post found
 */
function get_featured_post() {
    global $conn;
    
    $sql = "SELECT p.*, 
            c.name as category_name,
            a.name as author_name,
            a.avatar as author_avatar
            FROM blog_posts p
            LEFT JOIN blog_categories c ON p.category_id = c.category_id
            LEFT JOIN blog_authors a ON p.author_id = a.author_id
            WHERE p.status = 'published' 
            AND p.is_featured = 1
            ORDER BY p.published_at DESC
            LIMIT 1";
            
    $result = $conn->query($sql);
    if ($result === false) {
        error_log("Error in get_featured_post: " . $conn->error);
        return null;
    }
    
    return $result->fetch_assoc();
} 