<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/format_helpers.php';

/**
 * Get product image URL with proper fallback
 * @param string $image_url Image URL from database
 * @return string Final image URL to use
 */
function getProductImageUrl($image_url) {
    // Check if image_url is empty or null
    if (empty($image_url)) {
        return '../assets/images/default-product.jpg';
    }
    
    // If it's an external URL (http/https), return as is
    if (strpos($image_url, 'http') === 0) {
        return $image_url;
    }
    
    // If it's a local path and doesn't start with ../
    if (strpos($image_url, '../') !== 0) {
        return '../' . $image_url;
    }
    
    // If already has ../ prefix, return as is
    return $image_url;
}

/**
 * Tính giá giảm và phần trăm giảm giá dựa trên discount_amount
 * @param float $original_price Giá gốc
 * @param float|null $discount_amount Số tiền giảm giá
 * @return array Thông tin giảm giá [discount_price, discount_percent, saved_amount, saved_percent]
 */
function calculateProductDiscount($original_price, $discount_amount) {
    if (empty($discount_amount) || $discount_amount <= 0) {
        return [
            'discount_price' => null,
            'discount_percent' => 0,
            'saved_amount' => 0,
            'saved_percent' => 0
        ];
    }

    $discount_price = max(0, $original_price - $discount_amount);
    $discount_percent = round(($discount_amount / $original_price) * 100);
    
    // Tính % còn lại sau khi giảm giá
    $remaining_percent = round(($discount_price / $original_price) * 100);
    $saved_percent = 100 - $remaining_percent;

    return [
        'discount_price' => $discount_price,
        'discount_percent' => $discount_percent,
        'saved_amount' => $discount_amount,
        'saved_percent' => $saved_percent
    ];
}

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
            LEFT JOIN medicines m ON p.product_id = m.product_id
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
        // Calculate discount based on discount_amount
        $discount_info = calculateProductDiscount($row['price'], $row['discount_amount']);
        $row['discount_percent'] = $discount_info['discount_percent'];
        $row['discount_price'] = $discount_info['discount_price'];
        $row['saved_amount'] = $discount_info['saved_amount'];
        $row['saved_percent'] = $discount_info['saved_percent'];
            
        // Format rating
        $row['avg_rating'] = number_format((float)$row['avg_rating'], 1);
        
        // Handle product image
        $row['display_image'] = getProductImageUrl($row['image_url']);
        
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
            LEFT JOIN medicines m ON p.product_id = m.product_id
            WHERE p.is_active = TRUE AND p.price BETWEEN ? AND ?";
    
    // Add category filter if specified
    if ($category_id) {
        $sql .= " AND p.category_id = ?";
    }
    
    $sql .= " GROUP BY p.product_id";
    
    // Add sorting
    switch ($sort) {
        case 'price_asc':
            $sql .= " ORDER BY CASE WHEN p.discount_amount > 0 THEN p.price - p.discount_amount ELSE p.price END ASC";
            break;
        case 'price_desc':
            $sql .= " ORDER BY CASE WHEN p.discount_amount > 0 THEN p.price - p.discount_amount ELSE p.price END DESC";
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
        $stmt->bind_param("ddiii", $min_price, $max_price, $category_id, $per_page, $offset);
    } else {
        $stmt->bind_param("ddii", $min_price, $max_price, $per_page, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        // Calculate discount based on discount_amount
        $discount_info = calculateProductDiscount($row['price'], $row['discount_amount']);
        $row['discount_percent'] = $discount_info['discount_percent'];
        $row['discount_price'] = $discount_info['discount_price'];
        $row['saved_amount'] = $discount_info['saved_amount'];
        $row['saved_percent'] = $discount_info['saved_percent'];
            
        // Format rating
        $row['avg_rating'] = number_format((float)$row['avg_rating'], 1);
        
        // Handle product image
        $row['display_image'] = getProductImageUrl($row['image_url']);
        
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
    
    $sql = "SELECT 
                MIN(CASE WHEN discount_amount > 0 THEN price - discount_amount ELSE price END) as min_price,
                MAX(price) as max_price 
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
        // Calculate discount based on discount_amount
        $discount_info = calculateProductDiscount($row['price'], $row['discount_amount']);
        $row['discount_percent'] = $discount_info['discount_percent'];
        $row['discount_price'] = $discount_info['discount_price'];
        $row['saved_amount'] = $discount_info['saved_amount'];
        $row['saved_percent'] = $discount_info['saved_percent'];
        
        // Format rating and image
        $row['avg_rating'] = number_format((float)$row['avg_rating'], 1);
        $row['display_image'] = !empty($row['image_url']) ? $row['image_url'] : '/assets/images/default-product.jpg';
        
        $products[] = $row;
    }
    
    return $products;
}

/**
 * Lấy số lượng sản phẩm trong danh mục
 * @param int $category_id ID danh mục
 * @return int Số lượng sản phẩm
 */
function getCategoryProductCount($category_id) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count FROM products WHERE category_id = ? AND is_active = TRUE";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_assoc()['count'];
}

/**
 * Lấy chi tiết sản phẩm
 * @param int $product_id ID sản phẩm
 * @return array|null Thông tin sản phẩm
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
            LEFT JOIN medicines m ON p.product_id = m.product_id
            WHERE p.product_id = ? AND p.is_active = TRUE
            GROUP BY p.product_id";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Calculate discount based on discount_amount
        $discount_info = calculateProductDiscount($row['price'], $row['discount_amount']);
        $row['discount_percent'] = $discount_info['discount_percent'];
        $row['discount_price'] = $discount_info['discount_price'];
        $row['saved_amount'] = $discount_info['saved_amount'];
        $row['saved_percent'] = $discount_info['saved_percent'];
        
        // Format rating and image
        $row['avg_rating'] = number_format((float)$row['avg_rating'], 1);
        $row['display_image'] = !empty($row['image_url']) ? $row['image_url'] : '/assets/images/default-product.jpg';
        
        return $row;
    }
    
    return null;
}

/**
 * Lấy đánh giá sản phẩm
 * @param int $product_id ID sản phẩm
 * @return array Danh sách đánh giá
 */
function getProductReviews($product_id) {
    global $conn;
    
    // Kiểm tra bảng product_reviews có tồn tại không
    $check_table = $conn->query("SHOW TABLES LIKE 'product_reviews'");
    if ($check_table->num_rows == 0) {
        return []; // Trả về mảng rỗng nếu bảng chưa tồn tại
    }
    
    $sql = "SELECT 
                pr.*,
                u.username,
                u.avatar
            FROM product_reviews pr
            LEFT JOIN users u ON pr.user_id = u.user_id
            WHERE pr.product_id = ?
            ORDER BY pr.created_at DESC";
            
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        // Log lỗi nếu cần
        error_log("SQL Error in getProductReviews: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    
    $reviews = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['avatar'] = !empty($row['avatar']) ? $row['avatar'] : '/assets/images/default-avatar.png';
        $reviews[] = $row;
    }
    
    return $reviews;
}

/**
 * Lấy sản phẩm liên quan
 * @param int $category_id ID danh mục
 * @param int $current_product_id ID sản phẩm hiện tại
 * @param int $limit Số lượng sản phẩm
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
    
    $products = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        // Calculate discount based on discount_amount
        $discount_info = calculateProductDiscount($row['price'], $row['discount_amount']);
        $row['discount_percent'] = $discount_info['discount_percent'];
        $row['discount_price'] = $discount_info['discount_price'];
        $row['saved_amount'] = $discount_info['saved_amount'];
        $row['saved_percent'] = $discount_info['saved_percent'];
        
        // Format rating and image
        $row['avg_rating'] = number_format((float)$row['avg_rating'], 1);
        $row['display_image'] = !empty($row['image_url']) ? $row['image_url'] : '/assets/images/default-product.jpg';
        
        $products[] = $row;
    }
    
    return $products;
}

/**
 * Kiểm tra số lượng sản phẩm trong database
 * @param mysqli $conn Kết nối database
 * @return bool True nếu có sản phẩm, false nếu không
 */
function checkProductsCount($conn) {
    $result = $conn->query("SELECT COUNT(*) as count FROM products");
        $row = $result->fetch_assoc();
    return $row['count'] > 0;
}

/**
 * Kiểm tra dữ liệu mẫu
 * @param mysqli $conn Kết nối database
 * @return bool True nếu có dữ liệu mẫu, false nếu không
 */
function checkSampleData($conn) {
    $result = $conn->query("SELECT COUNT(*) as count FROM products WHERE name LIKE '%Sample%'");
        $row = $result->fetch_assoc();
    return $row['count'] > 0;
}
?> 