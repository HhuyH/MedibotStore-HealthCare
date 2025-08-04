<?php
require_once __DIR__ . '/../db.php';

/**
 * Lấy danh sách danh mục sản phẩm
 * @return array Danh sách danh mục
 */
function getCategories() {
    global $conn;
    
    $sql = "SELECT * FROM product_categories ORDER BY name ASC";
    $result = $conn->query($sql);
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    return $categories;
}

/**
 * Lấy danh sách sản phẩm nổi bật
 * @param int $limit Số lượng sản phẩm muốn lấy
 * @return array Danh sách sản phẩm
 */
function getFeaturedProducts($limit = 8) {
    global $conn;
    
    $sql = "SELECT 
                p.*,
                pc.name as category_name,
                pc.description as category_description,
                COALESCE(AVG(pr.rating), 0) as avg_rating,
                COUNT(pr.review_id) as review_count,
                m.active_ingredient,
                m.dosage_form,
                m.unit,
                m.usage_instructions
            FROM products p
            LEFT JOIN product_categories pc ON p.category_id = pc.category_id
            LEFT JOIN product_reviews pr ON p.product_id = pr.product_id
            LEFT JOIN medicines m ON p.product_id = m.medicine_id
            WHERE p.is_active = TRUE
            GROUP BY p.product_id
            ORDER BY avg_rating DESC, review_count DESC
            LIMIT ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        // Tính giá khuyến mãi (giả sử giảm 10% cho sản phẩm có rating cao)
        $row['discount_percent'] = $row['avg_rating'] >= 4.5 ? 10 : 0;
        $row['discount_price'] = $row['discount_percent'] > 0 
            ? $row['price'] * (1 - $row['discount_percent']/100) 
            : null;
            
        // Format lại rating
        $row['avg_rating'] = number_format($row['avg_rating'], 1);
        
        // Xử lý ảnh sản phẩm
        $row['display_image'] = $row['image_url'] ?: '/assets/images/product-placeholder.jpg';
        
        $products[] = $row;
    }
    
    return $products;
}

/**
 * Lấy danh sách sản phẩm với bộ lọc
 * @param float $min_price Giá tối thiểu
 * @param float $max_price Giá tối đa
 * @param string $sort Cách sắp xếp
 * @param int $category_id ID danh mục (tùy chọn)
 * @param int $page Trang hiện tại
 * @param int $per_page Số sản phẩm mỗi trang
 * @return array Danh sách sản phẩm và tổng số trang
 */
function getFilteredProducts($min_price = 0, $max_price = PHP_FLOAT_MAX, $sort = 'default', $category_id = null, $page = 1, $per_page = 9) {
    global $conn;
    
    // Tính offset cho phân trang
    $offset = ($page - 1) * $per_page;
    
    // Base query
    $sql = "SELECT 
                p.*,
                pc.name as category_name,
                pc.description as category_description,
                COALESCE(AVG(pr.rating), 0) as avg_rating,
                COUNT(pr.review_id) as review_count,
                m.active_ingredient,
                m.dosage_form,
                m.unit,
                m.usage_instructions
            FROM products p
            LEFT JOIN product_categories pc ON p.category_id = pc.category_id
            LEFT JOIN product_reviews pr ON p.product_id = pr.product_id
            LEFT JOIN medicines m ON p.product_id = m.medicine_id
            WHERE p.is_active = TRUE AND p.price BETWEEN ? AND ?";
    
    // Add category filter if specified
    if ($category_id) {
        $sql .= " AND p.category_id = ?";
    }
    
    $sql .= " GROUP BY p.product_id";
    
    // Add sorting
    switch ($sort) {
        case 'price_asc':
            $sql .= " ORDER BY p.price ASC";
            break;
        case 'price_desc':
            $sql .= " ORDER BY p.price DESC";
            break;
        case 'name_asc':
            $sql .= " ORDER BY p.name ASC";
            break;
        case 'name_desc':
            $sql .= " ORDER BY p.name DESC";
            break;
        case 'rating':
            $sql .= " ORDER BY avg_rating DESC, review_count DESC";
            break;
        default:
            $sql .= " ORDER BY p.product_id DESC";
    }
    
    // Add pagination
    $sql .= " LIMIT ? OFFSET ?";
    
    // Prepare and execute query
    $stmt = $conn->prepare($sql);
    
    if ($category_id) {
        $stmt->bind_param("ddiiii", $min_price, $max_price, $category_id, $per_page, $offset);
    } else {
        $stmt->bind_param("ddii", $min_price, $max_price, $per_page, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        // Tính giá khuyến mãi
        $row['discount_percent'] = $row['avg_rating'] >= 4.5 ? 10 : 0;
        $row['discount_price'] = $row['discount_percent'] > 0 
            ? $row['price'] * (1 - $row['discount_percent']/100) 
            : null;
            
        // Format lại rating
        $row['avg_rating'] = number_format($row['avg_rating'], 1);
        
        // Xử lý ảnh sản phẩm
        $row['display_image'] = $row['image_url'] ?: '/assets/images/product-placeholder.jpg';
        
        $products[] = $row;
    }
    
    // Get total count for pagination
    $count_sql = "SELECT COUNT(DISTINCT p.product_id) as total 
                  FROM products p 
                  WHERE p.is_active = TRUE 
                  AND p.price BETWEEN ? AND ?";
                  
    if ($category_id) {
        $count_sql .= " AND p.category_id = ?";
    }
    
    $count_stmt = $conn->prepare($count_sql);
    if ($category_id) {
        $count_stmt->bind_param("ddi", $min_price, $max_price, $category_id);
    } else {
        $count_stmt->bind_param("dd", $min_price, $max_price);
    }
    
    $count_stmt->execute();
    $total = $count_stmt->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total / $per_page);
    
    return [
        'products' => $products,
        'total' => $total,
        'total_pages' => $total_pages
    ];
}

/**
 * Lấy khoảng giá sản phẩm
 * @return array Min và max price
 */
function getProductPriceRange() {
    global $conn;
    
    $sql = "SELECT MIN(price) as min_price, MAX(price) as max_price 
            FROM products 
            WHERE is_active = TRUE";
            
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    
    return [
        'min' => (float)$row['min_price'],
        'max' => (float)$row['max_price']
    ];
}

/**
 * Lấy sản phẩm phổ biến
 * @param int $limit Số lượng sản phẩm
 * @return array Danh sách sản phẩm
 */
function getPopularProducts($limit = 3) {
    global $conn;
    
    $sql = "SELECT 
                p.*,
                COALESCE(AVG(pr.rating), 0) as avg_rating,
                COUNT(pr.review_id) as review_count
            FROM products p
            LEFT JOIN product_reviews pr ON p.product_id = pr.product_id
            WHERE p.is_active = TRUE
            GROUP BY p.product_id
            ORDER BY review_count DESC, avg_rating DESC
            LIMIT ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $row['display_image'] = $row['image_url'] ?: '/assets/images/product-placeholder.jpg';
        $row['avg_rating'] = number_format($row['avg_rating'], 1);
        $products[] = $row;
    }
    
    return $products;
}

/**
 * Lấy số lượng sản phẩm trong danh mục
 * @param int $category_id ID của danh mục
 * @return int Số lượng sản phẩm
 */
function getCategoryProductCount($category_id) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count 
            FROM products 
            WHERE category_id = ? AND is_active = TRUE";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'];
}

/**
 * Lấy thông tin chi tiết sản phẩm
 * @param int $product_id ID của sản phẩm
 * @return array|null Thông tin sản phẩm hoặc null nếu không tìm thấy
 */
function getProductDetails($product_id) {
    global $conn;
    
    $sql = "SELECT 
                p.*,
                pc.name as category_name,
                pc.description as category_description,
                COALESCE(AVG(pr.rating), 0) as avg_rating,
                COUNT(pr.review_id) as review_count,
                m.active_ingredient,
                m.dosage_form,
                m.unit,
                m.usage_instructions
            FROM products p
            LEFT JOIN product_categories pc ON p.category_id = pc.category_id
            LEFT JOIN product_reviews pr ON p.product_id = pr.product_id
            LEFT JOIN medicines m ON p.product_id = m.medicine_id
            WHERE p.product_id = ? AND p.is_active = TRUE
            GROUP BY p.product_id";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Tính giá khuyến mãi
        $row['discount_percent'] = $row['avg_rating'] >= 4.5 ? 10 : 0;
        $row['discount_price'] = $row['discount_percent'] > 0 
            ? $row['price'] * (1 - $row['discount_percent']/100) 
            : null;
            
        // Format lại rating
        $row['avg_rating'] = number_format($row['avg_rating'], 1);
        
        // Xử lý ảnh sản phẩm
        $row['display_image'] = $row['image_url'] ?: '/assets/images/product-placeholder.jpg';
        
        return $row;
    }
    
    return null;
}

/**
 * Lấy đánh giá của sản phẩm
 * @param int $product_id ID của sản phẩm
 * @return array Danh sách đánh giá
 */
function getProductReviews($product_id) {
    global $conn;
    
    // Kiểm tra xem bảng product_reviews có tồn tại không
    $check_table = $conn->query("SHOW TABLES LIKE 'product_reviews'");
    if ($check_table->num_rows == 0) {
        return []; // Trả về mảng rỗng nếu bảng chưa tồn tại
    }
    
    $sql = "SELECT 
                pr.*,
                COALESCE(u.name, 'Ẩn danh') as reviewer_name,
                u.avatar
            FROM product_reviews pr
            LEFT JOIN users u ON pr.user_id = u.user_id
            WHERE pr.product_id = ?
            ORDER BY pr.created_at DESC";
            
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return []; // Trả về mảng rỗng nếu có lỗi prepare
    }
    
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        // Xử lý avatar mặc định
        if (empty($row['avatar'])) {
            $row['avatar'] = '/assets/images/default-avatar.png';
        }
        $reviews[] = $row;
    }
    
    return $reviews;
}

/**
 * Lấy sản phẩm liên quan
 * @param int $category_id ID của danh mục
 * @param int $current_product_id ID của sản phẩm hiện tại (để loại trừ)
 * @param int $limit Số lượng sản phẩm muốn lấy
 * @return array Danh sách sản phẩm
 */
function getRelatedProducts($category_id, $current_product_id, $limit = 4) {
    global $conn;
    
    $sql = "SELECT 
                p.*,
                COALESCE(AVG(pr.rating), 0) as avg_rating,
                COUNT(pr.review_id) as review_count
            FROM products p
            LEFT JOIN product_reviews pr ON p.product_id = pr.product_id
            WHERE p.category_id = ? 
            AND p.product_id != ?
            AND p.is_active = TRUE
            GROUP BY p.product_id
            ORDER BY RAND()
            LIMIT ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $category_id, $current_product_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        // Tính giá khuyến mãi
        $row['discount_percent'] = $row['avg_rating'] >= 4.5 ? 10 : 0;
        $row['discount_price'] = $row['discount_percent'] > 0 
            ? $row['price'] * (1 - $row['discount_percent']/100) 
            : null;
            
        // Format lại rating
        $row['avg_rating'] = number_format($row['avg_rating'], 1);
        
        // Xử lý ảnh sản phẩm
        $row['display_image'] = $row['image_url'] ?: '/assets/images/product-placeholder.jpg';
        
        $products[] = $row;
    }
    
    return $products;
}

// Hàm kiểm tra số lượng sản phẩm trong database
function checkProductsCount($conn) {
    $sql = "SELECT COUNT(*) as total FROM products";
    $result = $conn->query($sql);
    if ($result) {
        $row = $result->fetch_assoc();
        return $row['total'];
    }
    return 0;
}

// Hàm kiểm tra dữ liệu mẫu
function checkSampleData($conn) {
    echo "<h3>Thông tin Database:</h3>";
    
    // Kiểm tra bảng products
    $totalProducts = checkProductsCount($conn);
    echo "Tổng số sản phẩm: " . $totalProducts . "<br>";
    
    // Kiểm tra bảng categories
    $sql = "SELECT COUNT(*) as total FROM categories";
    $result = $conn->query($sql);
    $totalCategories = 0;
    if ($result) {
        $row = $result->fetch_assoc();
        $totalCategories = $row['total'];
    }
    echo "Tổng số danh mục: " . $totalCategories . "<br>";
    
    // Hiển thị một số sản phẩm mẫu
    if ($totalProducts > 0) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LIMIT 5";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            echo "<h4>5 sản phẩm mẫu:</h4>";
            while ($row = $result->fetch_assoc()) {
                echo "- " . htmlspecialchars($row['name']) . 
                     " (Danh mục: " . htmlspecialchars($row['category_name']) . ")<br>";
            }
        }
    }
}
?> 