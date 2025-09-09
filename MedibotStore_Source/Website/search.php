<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions/product_functions.php';

// Debug mode - set to false to ẩn thông tin debug
define('DEBUG_MODE', false);

// Kiểm tra kết nối database
if (!isset($conn) || $conn->connect_error) {
    die("Lỗi kết nối database: " . ($conn->connect_error ?? "Không có kết nối"));
}

// Xử lý tham số tìm kiếm
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Debug
if (DEBUG_MODE) {
    echo "Từ khóa tìm kiếm: " . htmlspecialchars($search) . "<br>";
}

// Khởi tạo biến
$products = [];
$totalProducts = 0;

if (!empty($search)) {
    // Tạo câu truy vấn tìm kiếm
    $sql = "SELECT p.product_id, p.name, p.description, p.price, p.stock, p.image_url, 
                   pc.name as category_name,
                   COALESCE(AVG(pr.rating), 0) as avg_rating,
                   COUNT(pr.review_id) as review_count
            FROM products p 
            LEFT JOIN product_categories pc ON p.category_id = pc.category_id 
            LEFT JOIN product_reviews pr ON p.product_id = pr.product_id
            WHERE p.name LIKE ? OR p.description LIKE ? OR pc.name LIKE ?
            GROUP BY p.product_id
            ORDER BY p.name ASC 
            LIMIT ? OFFSET ?";
    
    // Debug
    if (DEBUG_MODE) {
        echo "SQL Query: " . $sql . "<br>";
    }
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Lỗi prepare statement: " . $conn->error);
    }
    
    // Tạo tham số tìm kiếm
    $searchParam = "%" . $search . "%";
    $stmt->bind_param("sssii", $searchParam, $searchParam, $searchParam, $perPage, $offset);
    
    // Thực thi truy vấn
    if (!$stmt->execute()) {
        die("Lỗi execute: " . $stmt->error);
    }
    
    // Lấy kết quả
    $result = $stmt->get_result();
    $products = [];
    require_once __DIR__ . '/includes/functions/format_helpers.php';
    while ($row = $result->fetch_assoc()) {
        // Sử dụng hàm calculateDiscountPrice để check config
        $discount_info = calculateDiscountPrice($row['price'], $row['avg_rating']);
        $row['discount_percent'] = $discount_info['discount_percent'];
        $row['discount_price'] = $discount_info['discount_price'];
        
        // Format lại rating
        $row['avg_rating'] = number_format($row['avg_rating'], 1);
        
        // Xử lý ảnh sản phẩm
        $row['display_image'] = $row['image_url'] ?: '/assets/images/product-placeholder.jpg';
        
        $products[] = $row;
    }
    
    // Đếm tổng số sản phẩm
    $countSql = "SELECT COUNT(DISTINCT p.product_id) as total 
                 FROM products p 
                 LEFT JOIN product_categories pc ON p.category_id = pc.category_id 
                 WHERE p.name LIKE ? OR p.description LIKE ? OR pc.name LIKE ?";
    
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalProducts = $countResult->fetch_assoc()['total'];
    
    // Debug
    if (DEBUG_MODE) {
        echo "Tổng số sản phẩm tìm thấy: " . $totalProducts . "<br>";
    }
}

// Tính số trang
$totalPages = ceil($totalProducts / $perPage);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả tìm kiếm - <?php echo htmlspecialchars($search); ?> - MediSync</title>
    <meta name="description" content="Tìm kiếm sản phẩm y tế, thuốc, thiết bị y tế tại MediSync">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        /* Fix body padding for fixed header */
        body {
            font-family: 'Inter', sans-serif;
            padding-top: 140px !important; /* Height of fixed header */
        }
        
        /* Ensure header is below appointment modal */
        .medical-header {
            z-index: 999990 !important;
        }
        
        /* Make sure main content is below header */
        main.search-page {
            position: relative;
            z-index: 1;
        }
        
        .search-page {
            padding: 2rem 0;
            background-color: #f5f5f5;
            min-height: calc(100vh - 200px);
            margin-top: 20px; /* Extra space from header */
        }
        
        /* Extra protection for container */
        .search-page .container {
            position: relative;
            z-index: 1;
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 30px;
        }

        .product-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #eee;
            height: 100%;
            position: relative;
        }

        .product-card:hover {
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transform: translateY(-4px);
        }

        .product-image {
            position: relative;
            width: 100%;
            padding-bottom: 100%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .product-image img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .product-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            z-index: 1;
            background: #ff4757;
            color: white;
        }

        .product-actions {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 10px;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            transform: translateY(100%);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .product-card:hover .product-actions {
            transform: translateY(0);
            opacity: 1;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: relative;
            text-decoration: none;
        }

        .action-btn:hover {
            background: #0d6efd;
            color: white;
            transform: translateY(-2px);
        }

        .action-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .action-btn .tooltip {
            position: absolute;
            top: -40px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
        }

        .action-btn:hover .tooltip {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(-4px);
        }

        .product-content {
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .product-category {
            color: #0d6efd;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .product-title {
            font-size: 16px;
            font-weight: 500;
            margin: 0;
            line-height: 1.4;
            height: 44px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            color: #333;
            text-decoration: none;
        }

        .product-title:hover {
            color: #0d6efd;
            text-decoration: none;
        }

        .product-rating {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .rating-stars {
            color: #ffc107;
            font-size: 14px;
            display: flex;
            gap: 2px;
        }

        .rating-count {
            font-size: 14px;
            color: #666;
        }

        .product-price {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: auto;
        }

        .current-price {
            font-size: 18px;
            font-weight: 700;
            color: #ee0000;
        }

        .original-price {
            font-size: 14px;
            color: #999;
            text-decoration: line-through;
        }

        .product-stock {
            font-size: 14px;
        }

        .in-stock {
            color: #28a745;
        }

        .out-of-stock {
            color: #dc3545;
        }

        /* Filter Bar */
        .filter-bar {
            background: #fff;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            border: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .filter-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .filter-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .filter-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #fff;
            color: #333;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-toggle:hover {
            border-color: #0d6efd;
            color: #0d6efd;
        }

        .sort-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sort-label {
            font-size: 14px;
            color: #666;
            white-space: nowrap;
        }

        .sort-options {
            display: flex;
            gap: 8px;
        }

        .sort-option {
            padding: 6px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: #fff;
            color: #333;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .sort-option:hover,
        .sort-option.active {
            background: #0d6efd;
            border-color: #0d6efd;
            color: #fff;
        }

        .view-mode {
            display: flex;
            gap: 4px;
        }

        .view-mode-btn {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: #fff;
            color: #666;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .view-mode-btn:hover,
        .view-mode-btn.active {
            background: #0d6efd;
            border-color: #0d6efd;
            color: #fff;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .filter-label {
            color: #666;
            font-size: 14px;
            font-weight: 500;
            white-space: nowrap;
        }

        .filter-select {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            color: #333;
            background: #fff;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-select:hover,
        .filter-select:focus {
            border-color: #0d6efd;
            outline: none;
        }

        .search-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            color: #333;
            transition: all 0.2s ease;
        }

        .search-input:hover,
        .search-input:focus {
            border-color: #0d6efd;
            outline: none;
        }

        .sort-group {
            display: flex;
            align-items: center;
            gap: 12px;
            justify-content: flex-end;
        }

        .view-options {
            display: flex;
            gap: 8px;
        }

        .view-option {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #ddd;
            border-radius: 8px;
            color: #666;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .view-option:hover,
        .view-option.active {
            background: #0d6efd;
            border-color: #0d6efd;
            color: white;
        }

        @media (max-width: 991px) {
            .filter-group {
                margin-bottom: 12px;
            }
            
            .sort-group {
                justify-content: flex-start;
            }
        }

        @media (max-width: 767px) {
            .filter-bar {
                padding: 12px;
            }
            
            .view-options {
                display: none;
            }
        }

        /* Search Type */
        .search-type-wrapper {
            margin-bottom: 20px;
            background: #fff;
            padding: 16px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .search-type {
            display: flex;
            gap: 20px;
        }

        .search-type-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 8px 16px;
            border-radius: 20px;
            transition: all 0.2s ease;
            background: #f8f9fa;
        }

        .search-type-option:hover {
            background: #e9ecef;
        }

        .search-type-option input[type="radio"] {
            display: none;
        }

        .search-type-option span {
            position: relative;
            padding-left: 24px;
            color: #333;
            font-size: 14px;
            font-weight: 500;
        }

        .search-type-option span:before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            border: 2px solid #ddd;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .search-type-option.active {
            background: rgba(13, 110, 253, 0.1);
        }

        .search-type-option.active span {
            color: #0d6efd;
        }

        .search-type-option.active span:before {
            border-color: #0d6efd;
        }

        .search-type-option.active span:after {
            content: '';
            position: absolute;
            left: 4px;
            top: 50%;
            transform: translateY(-50%);
            width: 8px;
            height: 8px;
            background: #0d6efd;
            border-radius: 50%;
        }

        /* Product Notice */
        .product-notice {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Pagination */
        .pagination-wrapper {
            margin-top: 30px;
            display: flex;
            justify-content: center;
        }

        .pagination {
            display: flex;
            gap: 8px;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .page-item {
            display: flex;
        }

        .page-link {
            min-width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 12px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            color: #333;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            background: #fff;
        }

        .page-link:hover {
            border-color: #0d6efd;
            color: #0d6efd;
            background: rgba(13, 110, 253, 0.1);
        }

        .page-item.active .page-link {
            background: #0d6efd;
            border-color: #0d6efd;
            color: #fff;
        }

        .page-item.disabled .page-link {
            color: #6c757d;
            pointer-events: none;
            background: #f8f9fa;
            border-color: #dee2e6;
        }

        .page-item:first-child .page-link,
        .page-item:last-child .page-link {
            padding: 0 16px;
        }

        /* No Results */
        .no-results {
            text-align: center;
            padding: 60px 20px;
            background: #fff;
            border-radius: 12px;
            margin: 40px 0;
        }

        .no-results-icon {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .no-results-text {
            font-size: 18px;
            color: #666;
            margin: 0;
            line-height: 1.5;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .product-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 991px) {
            .product-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .filter-bar {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-right {
                width: 100%;
                justify-content: space-between;
            }
        }

        @media (max-width: 767px) {
            .product-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .sort-options {
                flex-wrap: wrap;
            }
            
            .view-mode {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .product-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-bar {
                padding: 12px;
            }
            
            .sort-wrapper {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <!-- Appointment Modal -->
    <?php include 'includes/appointment-modal.php'; ?>
    
    <main class="search-page">
        <div class="container">
            <!-- Search Type -->
            <div class="search-type-wrapper">
                <div class="search-type">
                    <label class="search-type-option active">
                        <input type="radio" name="search_type" value="product" checked>
                        <span>Sản phẩm</span>
                    </label>
                    <label class="search-type-option">
                        <input type="radio" name="search_type" value="article">
                        <span>Bài viết sức khỏe</span>
                    </label>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="filter-left">
                    <button class="filter-toggle">
                        <i class="fas fa-sliders-h"></i>
                        Bộ lọc nâng cao
                    </button>
                </div>
                <div class="filter-right">
                    <div class="sort-wrapper">
                        <span class="sort-label">Sắp xếp theo:</span>
                        <div class="sort-options">
                            <button class="sort-option active">Bán chạy</button>
                            <button class="sort-option">Giá thấp</button>
                            <button class="sort-option">Giá cao</button>
                        </div>
                    </div>
                    <div class="view-mode">
                        <button class="view-mode-btn active" data-mode="grid">
                            <i class="fas fa-th-large"></i>
                        </button>
                        <button class="view-mode-btn" data-mode="list">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Product Notice -->
            <div class="product-notice">
                <i class="fas fa-info-circle"></i>
                Lưu ý: Thuốc kê đơn và một số sản phẩm sẽ cần tư vấn từ dược sĩ
            </div>

            <!-- Product Grid -->
            <?php if (!empty($products)): ?>
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <img src="<?php echo htmlspecialchars($product['display_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                            
                            <?php if ($product['discount_percent'] > 0): ?>
                            <div class="product-badge discount">
                                -<?php echo $product['discount_percent']; ?>%
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-content">
                            <div class="product-category">
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($product['category_name']); ?>
                            </div>
                            
                            <a href="/shop/details.php?id=<?php echo $product['product_id']; ?>" class="product-title">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </a>
                            
                            <div class="product-rating">
                                <div class="rating-stars">
                                    <?php 
                                    $rating = round($product['avg_rating']);
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $rating) {
                                            echo '<i class="fas fa-star"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <span class="rating-count">(<?php echo $product['review_count']; ?>)</span>
                            </div>
                            
                            <div class="product-price">
                                <?php if ($product['discount_percent'] > 0): ?>
                                    <span class="original-price"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</span>
                                    <span class="current-price">
                                        <?php 
                                        $discounted_price = $product['price'] * (100 - $product['discount_percent']) / 100;
                                        echo number_format($discounted_price, 0, ',', '.'); 
                                        ?>đ
                                    </span>
                                <?php else: ?>
                                    <span class="current-price"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</span>
                                <?php endif; ?>
                            </div>

                            <div class="product-stock">
                                <?php if ($product['stock'] > 0): ?>
                                    <span class="in-stock">Còn hàng</span>
                                <?php else: ?>
                                    <span class="out-of-stock">Hết hàng</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination-wrapper">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a href="?q=<?php echo urlencode($search); ?>&page=<?php echo $page-1; ?>" class="page-link">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a href="?q=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>" class="page-link">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a href="?q=<?php echo urlencode($search); ?>&page=<?php echo $page+1; ?>" class="page-link">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-results">
                    <div class="no-results-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <p class="no-results-text">
                        <?php if (!empty($search)): ?>
                            Không tìm thấy sản phẩm nào phù hợp với từ khóa "<?php echo htmlspecialchars($search); ?>"
                        <?php else: ?>
                            Vui lòng nhập từ khóa để tìm kiếm sản phẩm
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- Quick View Modal -->
    <div class="modal fade quick-view-modal" id="quickViewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-body">
                    <!-- Quick view content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <!-- Global Enhancements -->
    <script src="assets/js/global-enhancements.js"></script>
    <script>
        // Khởi tạo AOS
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });

        // Xử lý quick view
        document.querySelectorAll('.quick-view').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.dataset.id;
                const modal = new bootstrap.Modal(document.getElementById('quickViewModal'));
                const modalBody = document.querySelector('#quickViewModal .modal-body');
                
                // Hiển thị loading
                modalBody.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
                modal.show();
                
                // Gọi AJAX để lấy thông tin sản phẩm
                fetch(`shop/get_product_details.php?id=${productId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.text();
                    })
                    .then(html => {
                        modalBody.innerHTML = html;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        modalBody.innerHTML = `
                            <div class="alert alert-danger" role="alert">
                                Có lỗi xảy ra khi tải thông tin sản phẩm. 
                                <a href="shop/details.php?id=${productId}" class="alert-link">Nhấn vào đây</a> để xem chi tiết sản phẩm.
                            </div>
                        `;
                    });
            });
        });

        // Khởi tạo cart và wishlist
        document.addEventListener('DOMContentLoaded', function() {
            // Add to cart
            document.querySelectorAll('.add-to-cart').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.dataset.id;
                    const productName = this.closest('.product-card').querySelector('.product-title').textContent.trim();
                    const productPrice = this.closest('.product-card').querySelector('.product-price').dataset.price;
                    const productImage = this.closest('.product-card').querySelector('img').src;
                    
                    // Get cart from localStorage
                    let cart = JSON.parse(localStorage.getItem('cart') || '[]');
                    
                    // Check if product already in cart
                    const existingItem = cart.find(item => item.id === productId);
                    if (existingItem) {
                        existingItem.quantity += 1;
                    } else {
                        cart.push({
                            id: productId,
                            name: productName,
                            price: productPrice,
                            image: productImage,
                            quantity: 1
                        });
                    }
                    
                    // Save cart
                    localStorage.setItem('cart', JSON.stringify(cart));
                    
                    // Update cart count
                    updateCartCount();
                    
                    // Show notification
                    showNotification('Đã thêm vào giỏ hàng', 'success');
                });
            });

            // Add to wishlist
            document.querySelectorAll('.add-to-wishlist').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.dataset.id;
                    const productName = this.closest('.product-card').querySelector('.product-title').textContent.trim();
                    const productPrice = this.closest('.product-card').querySelector('.product-price').dataset.price;
                    const productImage = this.closest('.product-card').querySelector('img').src;
                    
                    // Get wishlist from localStorage
                    let wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
                    
                    // Check if product already in wishlist
                    const index = wishlist.findIndex(item => item.id === productId);
                    if (index !== -1) {
                        // Remove from wishlist
                        wishlist.splice(index, 1);
                        this.querySelector('i').classList.remove('fas');
                        this.querySelector('i').classList.add('far');
                        showNotification('Đã xóa khỏi danh sách yêu thích', 'info');
                    } else {
                        // Add to wishlist
                        wishlist.push({
                            id: productId,
                            name: productName,
                            price: productPrice,
                            image: productImage
                        });
                        this.querySelector('i').classList.remove('far');
                        this.querySelector('i').classList.add('fas');
                        showNotification('Đã thêm vào danh sách yêu thích', 'success');
                    }
                    
                    // Save wishlist
                    localStorage.setItem('wishlist', JSON.stringify(wishlist));
                });
            });

            // Initialize wishlist icons
            const wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
            document.querySelectorAll('.add-to-wishlist').forEach(button => {
                const productId = button.dataset.id;
                if (wishlist.some(item => item.id === productId)) {
                    button.querySelector('i').classList.remove('far');
                    button.querySelector('i').classList.add('fas');
                }
            });

            // Update initial cart count
            updateCartCount();
        });

        // Show notification
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            
            const icons = {
                success: 'check-circle',
                error: 'exclamation-circle',
                info: 'info-circle',
                warning: 'exclamation-triangle'
            };
            
            notification.innerHTML = `
                <i class="fas fa-${icons[type]} me-2"></i>
                ${message}
            `;
            
            notification.style.cssText = `
                position: fixed;
                top: 100px;
                right: 20px;
                background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#0d6efd'};
                color: white;
                padding: 12px 20px;
                border-radius: 25px;
                z-index: 1000;
                animation: slideInRight 0.3s ease;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideInRight 0.3s ease reverse';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Update cart count
        function updateCartCount() {
            const cart = JSON.parse(localStorage.getItem('cart') || '[]');
            const count = cart.reduce((total, item) => total + item.quantity, 0);
            
            const cartCountElement = document.querySelector('.cart-count');
            if (cartCountElement) {
                cartCountElement.textContent = count;
            }
        }
    </script>
</body>
</html> 