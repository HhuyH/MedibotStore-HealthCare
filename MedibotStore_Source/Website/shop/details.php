<?php
// Start session trước khi có bất kỳ output nào
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions/product_functions.php';

// Kiểm tra kết nối database
if (!isset($conn) || !$conn) {
    die('Lỗi kết nối cơ sở dữ liệu');
}

// Lấy ID sản phẩm từ URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Lấy thông tin sản phẩm
$product = getProductDetails($product_id);

// Nếu không tìm thấy sản phẩm, chuyển hướng về trang shop
if (!$product) {
    header('Location: ../shop.php');  
    exit;   
}

// Lấy sản phẩm liên quan
$relatedProducts = getRelatedProducts($product['category_id'], $product_id, 4);

// Lấy đánh giá sản phẩm từ database
$reviews = [];
try {
    $reviews_query = "SELECT pr.review_id, pr.product_id, pr.user_id, pr.rating, pr.comment, pr.created_at, 
                             u.username, u.full_name 
                      FROM product_reviews pr 
                      LEFT JOIN users u ON pr.user_id = u.user_id 
                      WHERE pr.product_id = ? 
                      ORDER BY pr.created_at DESC";
    
    $reviews_stmt = $conn->prepare($reviews_query);
    
    if (!$reviews_stmt) {
        error_log("SQL Error in main query: " . $conn->error);
        // Fallback: lấy reviews không có thông tin user
        $simple_query = "SELECT review_id, product_id, user_id, rating, comment, created_at FROM product_reviews WHERE product_id = ? ORDER BY created_at DESC";
        $reviews_stmt = $conn->prepare($simple_query);
        
        if (!$reviews_stmt) {
            error_log("SQL Error in fallback query: " . $conn->error);
        }
    }
    
    if ($reviews_stmt) {
        $reviews_stmt->bind_param("i", $product_id);
        $reviews_stmt->execute();
        $reviews_result = $reviews_stmt->get_result();
        
        while ($row = $reviews_result->fetch_assoc()) {
            $reviews[] = $row;
        }
        
        // Debug: log số lượng reviews
        error_log("Loaded " . count($reviews) . " reviews for product " . $product_id);
        $reviews_stmt->close();
    }
} catch (Exception $e) {
    error_log("Error loading reviews: " . $e->getMessage());
    $reviews = [];
}

// Tính số sao trung bình và tổng số đánh giá
$avgRating = floatval($product['avg_rating'] ?? 0);
$totalReviews = intval($product['review_count'] ?? 0);

// Tính giá sau giảm giá dựa trên discount_amount
$originalPrice = floatval($product['price']);
$discountAmount = floatval($product['discount_amount'] ?? 0);
$finalPrice = max(0, $originalPrice - $discountAmount);
$discountPercent = $originalPrice > 0 && $discountAmount > 0 ? round(($discountAmount / $originalPrice) * 100) : 0;
$savedAmount = $discountAmount;

// Hàm tính thời gian tương đối
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'Vừa xong';
    if ($time < 3600) return floor($time/60) . ' phút trước';
    if ($time < 86400) return floor($time/3600) . ' giờ trước';
    if ($time < 2592000) return floor($time/86400) . ' ngày trước';
    if ($time < 31104000) return floor($time/2592000) . ' tháng trước';
    return floor($time/31104000) . ' năm trước';
}

// Xử lý submit đánh giá
$review_message = '';
$review_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    // Kiểm tra đăng nhập
    if (!isset($_SESSION['user_id'])) {
        $review_error = 'Vui lòng đăng nhập để đánh giá sản phẩm';
    } else {
        $user_id = $_SESSION['user_id'];
        $rating = (int)$_POST['rating'];
        $comment = trim($_POST['comment']);
        
        // Validation
        if ($rating < 1 || $rating > 5) {
            $review_error = 'Vui lòng chọn số sao từ 1 đến 5';
        } elseif (empty($comment)) {
            $review_error = 'Vui lòng nhập nhận xét';
        } else {
            try {
                // Kiểm tra đã đánh giá chưa
                $check_review = $conn->prepare("SELECT review_id FROM product_reviews WHERE product_id = ? AND user_id = ?");
                $check_review->bind_param("ii", $product_id, $user_id);
                $check_review->execute();
                $existing = $check_review->get_result()->fetch_assoc();
                
                if ($existing) {
                    // Cập nhật đánh giá cũ
                    $update_review = $conn->prepare("UPDATE product_reviews SET rating = ?, comment = ?, updated_at = NOW() WHERE review_id = ?");
                    $update_review->bind_param("isi", $rating, $comment, $existing['review_id']);
                    if ($update_review->execute()) {
                        $review_message = 'Cập nhật đánh giá thành công!';
                        // Reload reviews
                        header("Location: details.php?id=" . $product_id . "&review=updated");
                        exit;
                    } else {
                        $review_error = 'Có lỗi khi cập nhật đánh giá';
                    }
                } else {
                    // Thêm đánh giá mới
                    $insert_review = $conn->prepare("INSERT INTO product_reviews (product_id, user_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $insert_review->bind_param("iiis", $product_id, $user_id, $rating, $comment);
                    if ($insert_review->execute()) {
                        $review_message = 'Đánh giá của bạn đã được gửi thành công!';
                        // Reload reviews
                        header("Location: details.php?id=" . $product_id . "&review=success");
                        exit;
                    } else {
                        $review_error = 'Có lỗi khi gửi đánh giá';
                    }
                }
            } catch (Exception $e) {
                $review_error = 'Có lỗi xảy ra: ' . $e->getMessage();
            }
        }
    }
}

// Xử lý thông báo từ redirect
if (isset($_GET['review'])) {
    if ($_GET['review'] === 'success') {
        $review_message = 'Đánh giá của bạn đã được gửi thành công!';
    } elseif ($_GET['review'] === 'updated') {
        $review_message = 'Cập nhật đánh giá thành công!';
    }
}

// Kiểm tra user đã đánh giá chưa
$user_existing_review = null;
if (isset($_SESSION['user_id'])) {
    $check_user_review = $conn->prepare("SELECT rating, comment FROM product_reviews WHERE product_id = ? AND user_id = ?");
    $check_user_review->bind_param("ii", $product_id, $_SESSION['user_id']);
    $check_user_review->execute();
    $user_existing_review = $check_user_review->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - QickMed</title>
    <meta name="description" content="<?php echo htmlspecialchars($product['description']); ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f8f9fa;
        }
        
        /* Cart notification styles */
        .cart-notification {
            position: fixed !important;
            z-index: 99999999 !important;
            top: 20px !important;
            right: 20px !important;
            min-width: 300px !important;
            max-width: 400px !important;
            background: white !important;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12) !important;
            border-radius: 12px !important;
            backdrop-filter: blur(10px) !important;
            border: 1px solid rgba(255,255,255,0.2) !important;
            font-weight: 500 !important;
            margin: 0 !important;
            padding: 1rem !important;
            display: flex !important;
            align-items: center !important;
            gap: 1rem !important;
            transform: translateY(-100%) !important;
            opacity: 0 !important;
            transition: all 0.3s ease !important;
        }

        .cart-notification.show {
            transform: translateY(0) !important;
            opacity: 1 !important;
        }

        .cart-notification .icon {
            width: 40px !important;
            height: 40px !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 1.2rem !important;
            flex-shrink: 0 !important;
        }

        .cart-notification.success .icon {
            background: #d4edda !important;
            color: #155724 !important;
        }

        .cart-notification.error .icon {
            background: #f8d7da !important;
            color: #721c24 !important;
        }

        .cart-notification .content {
            flex: 1 !important;
        }

        .cart-notification .title {
            font-weight: 600 !important;
            margin-bottom: 0.2rem !important;
            color: #2d3748 !important;
        }

        .cart-notification .message {
            color: #718096 !important;
            font-size: 0.9rem !important;
        }

        .cart-notification .close {
            width: 24px !important;
            height: 24px !important;
            border-radius: 50% !important;
            background: #edf2f7 !important;
            color: #718096 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
            font-size: 0.8rem !important;
            transition: all 0.2s ease !important;
        }

        .cart-notification .close:hover {
            background: #e2e8f0 !important;
            color: #2d3748 !important;
        }

        @media (max-width: 768px) {
            .cart-notification {
                top: 80px !important;
                left: 10px !important;
                right: 10px !important;
                min-width: auto !important;
                max-width: none !important;
            }
        }

        /* Force notification to be on top of everything */
        .cart-notification,
        .cart-notification.position-fixed,
        .cart-notification.alert,
        .cart-notification.alert.position-fixed {
            position: fixed !important;
            z-index: 99999999 !important;
        }
        
        .product-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .breadcrumb {
            background: white;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            font-size: 0.9rem;
        }
        
        .breadcrumb-item a {
            color: #3498db;
            text-decoration: none;
        }
        
        .product-detail-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .product-image-section {
            padding: 2rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            position: relative;
        }
        
        .discount-badge {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
            z-index: 10;
        }
        
        .main-product-image {
            width: 100%;
            max-width: 400px;
            height: 400px;
            object-fit: contain;
            border-radius: 16px;
            background: white;
            padding: 1rem;
        }
        
        .product-info-section {
            padding: 2rem;
        }
        
        .product-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1rem;
            line-height: 1.3;
        }
        
        .product-rating {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 1.5rem;
        }
        
        .rating-stars {
            color: #ffd700;
            font-size: 1.1rem;
        }
        
        .rating-count {
            color: #666;
            font-size: 0.9rem;
        }
        
        .rating-value {
            font-weight: 600;
            color: #2c3e50;
            margin-left: 0.5rem;
        }
        
        /* Compact Price Section */
        .product-price-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1.25rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            color: white;
        }
        
        .price-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .current-price {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }
        
        .price-details {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .original-price {   
            font-size: 1rem;
            opacity: 0.7;
            text-decoration: line-through;
        }
        
        .price-save {
            background: rgba(255,255,255,0.2);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .product-stock {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            padding: 0.8rem 1rem;
            background: #e8f5e8;
            border-radius: 12px;
            color: #2d5a27;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .product-stock.out-of-stock {
            background: #fdeaea;
            color: #c53030;
        }
        
        .quantity-section {
            margin-bottom: 1.5rem;
        }
        
        .quantity-label {
            font-weight: 600;
            margin-bottom: 0.8rem;
            color: #2c3e50;
            font-size: 0.9rem;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.8rem;
        }
        
        .quantity-wrapper {
            display: flex;
            align-items: center;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            overflow: hidden;
            background: white;
        }
        
        .quantity-btn {
            width: 40px;
            height: 40px;
            border: none;
            background: #3498db;
            color: white;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .quantity-btn:hover {
            background: #2980b9;
        }
        
        .quantity-btn:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }
        
        .quantity-input {
            width: 70px;
            height: 40px;
            border: none;
            text-align: center;
            font-size: 1rem;
            font-weight: 600;
            background: #f8f9fa;
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 0.8rem;
            margin-bottom: 1.5rem;
        }
        
        .btn-buy-now {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            border: none;
            padding: 0.9rem 1.5rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-buy-now:hover {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(46, 204, 113, 0.3);
        }
        
        .btn-add-cart {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            padding: 0.9rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-add-cart:hover {
            background: linear-gradient(135deg, #2980b9 0%, #3498db 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
        }
        
        .btn-add-cart.loading {
            position: relative;
            color: transparent !important;
        }
        
        .btn-add-cart.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin-top: -10px;
            margin-left: -10px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.8s infinite linear;
        }
        
        .btn-wishlist {
            background: white;
            color: #e74c3c;
            border: 2px solid #e74c3c;
            padding: 0.9rem;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-wishlist:hover {
            background: #e74c3c;
            color: white;
            transform: translateY(-2px);
        }
        
        .product-description {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
        }
        
        .description-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.8rem;
        }
        
        .description-text {
            color: #5a6c7d;
            line-height: 1.6;
            font-size: 0.95rem;
        }
        
        .related-products {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .related-product-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            height: 100%;
        }
        
        .related-product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
            color: inherit;
            text-decoration: none;
        }
        
        .related-product-image {
            height: 180px;
            overflow: hidden;
            position: relative;
        }
        
        .related-product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .related-product-card:hover .related-product-image img {
            transform: scale(1.1);
        }
        
        .related-product-info {
            padding: 1.2rem;
        }
        
        .related-product-name {
            font-weight: 600;
            margin-bottom: 0.6rem;
            color: #2c3e50;
            line-height: 1.4;
            font-size: 0.95rem;
        }
        
        .related-product-price {
            font-size: 1rem;
            font-weight: 700;
            color: #e74c3c;
            margin-bottom: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .product-container {
                padding: 0 0.5rem;
                margin: 1rem auto;
            }
            
            .product-info-section {
                padding: 1.5rem;
            }
            
            .product-title {
                font-size: 1.5rem;
            }
            
            .current-price {
                font-size: 1.6rem;
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
                gap: 0.8rem;
            }
            
            .main-product-image {
                height: 300px;
            }
            
            .price-details {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
        
        .loading {
            opacity: 0.7;
            pointer-events: none;
            position: relative;
        }
        
        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);    
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
        
        /* Product Reviews Section */
        .product-reviews {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .reviews-header {
            border-bottom: 2px solid #f1f3f4;
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .reviews-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .rating-summary {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .overall-rating {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            color: white;
            min-width: 120px;
            height: 120px;
            width: 120px;
        }
        
        .rating-score {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .rating-stars-large {
            font-size: 0.5rem;
            margin-bottom: 0.3rem;
            color: #ffd700;
        }
        
        .rating-count-text {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .review-item {
            padding: 1.5rem;
            border-bottom: 1px solid #f1f3f4;
            transition: all 0.3s ease;
        }
        
        .review-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .review-item:hover {
            background: rgba(102, 126, 234, 0.02);
            border-radius: 12px;
            margin: 0 -1rem;
            padding-left: 2.5rem;
            padding-right: 2.5rem;
        }
        
        .review-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.8rem;
        }
        
        .reviewer-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .reviewer-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        
        .reviewer-details h5 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .review-stars {
            color: #ffd700;
            font-size: 1rem;
            margin-top: 0.2rem;
        }
        
        .review-date {
            color: #666;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .review-comment {
            color: #5a6c7d;
            line-height: 1.6;
            font-size: 0.95rem;
            margin-left: 3.5rem;
        }
        
        .no-reviews {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .no-reviews i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
                 @media (max-width: 768px) {
             .reviews-summary {
                 flex-direction: column;
                 align-items: center;
                 text-align: center;
             }
             
             .rating-summary {
                 flex-direction: column;
             }
             
             .reviewer-info {
                 flex-direction: column;
                 text-align: center;
                 gap: 0.5rem;
             }
             
             .review-comment {
                 margin-left: 0;
                 margin-top: 1rem;
             }
             
             .review-header {
                 flex-direction: column;
                 align-items: center;
                 gap: 0.8rem;
             }
         }
         
         /* Review Form Styles */
         .review-form-section {
             margin-top: 2rem;
             padding-top: 2rem;
             border-top: 2px solid #f1f3f4;
         }
         
         .review-form {
             background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
             border-radius: 16px;
             padding: 2rem;
             margin-top: 1.5rem;
         }
         
         .form-header {
             text-align: center;
             margin-bottom: 2rem;
         }
         
         .form-title {
             font-size: 1.5rem;
             font-weight: 700;
             color: #2c3e50;
             margin-bottom: 0.5rem;
         }
         
         .form-subtitle {
             color: #666;
             font-size: 0.95rem;
         }
         
         .rating-input-section {
             text-align: center;
             margin-bottom: 2rem;
         }
         
         .rating-label {
             display: block;
             font-weight: 600;
             color: #2c3e50;
             margin-bottom: 1rem;
             font-size: 1.1rem;
         }
         
         .star-rating-input {
             display: flex;
             justify-content: center;
             gap: 0.5rem;
             margin-bottom: 0.8rem;
         }
         
         .star-btn {
             background: none;
             border: none;
             font-size: 2rem;
             color: #ddd;
             cursor: pointer;
             transition: all 0.3s ease;
             padding: 0.2rem;
         }
         
         .star-btn:hover,
         .star-btn.active {
             color: #ffd700;
             transform: scale(1.2);
             text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
         }
         
         .rating-text {
             font-weight: 600;
             color: #495057;
             font-size: 1rem;
             margin-top: 0.5rem;
         }
         
         .comment-section {
             margin-bottom: 2rem;
         }
         
         .comment-label {
             display: block;
             font-weight: 600;
             color: #2c3e50;
             margin-bottom: 0.8rem;
             font-size: 1.1rem;
         }
         
         .comment-textarea {
             width: 100%;
             min-height: 120px;
             padding: 1rem;
             border: 2px solid #e1e5e9;
             border-radius: 12px;
             font-size: 0.95rem;
             line-height: 1.5;
             resize: vertical;
             transition: all 0.3s ease;
             background: white;
         }
         
         .comment-textarea:focus {
             outline: none;
             border-color: #667eea;
             box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
         }
         
         .form-actions {
             text-align: center;
         }
         
         .submit-review-btn {
             background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
             color: white;
             border: none;
             padding: 1rem 2.5rem;
             border-radius: 12px;
             font-size: 1rem;
             font-weight: 600;
             cursor: pointer;
             transition: all 0.3s ease;
             display: inline-flex;
             align-items: center;
             gap: 0.5rem;
         }
         
         .submit-review-btn:hover {
             transform: translateY(-2px);
             box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
         }
         
         .submit-review-btn:disabled {
             opacity: 0.6;
             cursor: not-allowed;
             transform: none;
         }
         
         .login-prompt {
             text-align: center;
             padding: 2rem;
             background: #e3f2fd;
             border-radius: 12px;
             margin-top: 1.5rem;
         }
         
         .login-prompt i {
             font-size: 3rem;
             color: #2196f3;
             margin-bottom: 1rem;
         }
         
         .login-btn {
             background: #2196f3;
             color: white;
             border: none;
             padding: 0.8rem 2rem;
             border-radius: 8px;
             text-decoration: none;
             display: inline-block;
             font-weight: 600;
             transition: all 0.3s ease;
         }
         
         .login-btn:hover {
             background: #1976d2;
             color: white;
             text-decoration: none;
             transform: translateY(-2px);
         }
         
         .alert {
             padding: 1rem 1.5rem;
             border-radius: 8px;
             margin-bottom: 1rem;
             font-weight: 500;
         }
         
         .alert-success {
             background: #d4edda;
             color: #155724;
             border: 1px solid #c3e6cb;
         }
         
         .alert-danger {
             background: #f8d7da;
             color: #721c24;
             border: 1px solid #f5c6cb;
         }
         
         @media (max-width: 768px) {
             .review-form {
                 padding: 1.5rem;
             }
             
             .star-btn {
                 font-size: 1.5rem;
             }
             
             .submit-review-btn {
                 padding: 0.8rem 2rem;
             }
         }
    </style>
</head>
<body>
    
        <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <!-- Appointment Modal -->
    <?php 
    $appointment_modal_path = __DIR__ . '/../includes/appointment-modal.php';
    if (file_exists($appointment_modal_path)) {
        include $appointment_modal_path;
    }
    ?>


    <div class="product-container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php"><i class="fas fa-home"></i> Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="../shop.php">Cửa hàng</a></li>
                <li class="breadcrumb-item">
                    <a href="../shop.php?category=<?php echo $product['category_id']; ?>">
                        <?php echo htmlspecialchars($product['category_name'] ?? 'Thiết bị y tế'); ?>
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?php echo htmlspecialchars($product['name']); ?>
                </li>
            </ol>
        </nav>

        <!-- Product Details -->
        <div class="product-detail-card">
            <div class="row g-0">
                <!-- Product Image -->
                <div class="col-lg-6">
                    <div class="product-image-section">
                        <?php if ($discountPercent > 0): ?>
                        <div class="discount-badge">
                            <!-- them icon neu can thiet fa-fire -->
                            <i class="fas "></i> -<?php echo $discountPercent; ?>%
                        </div>
                        <?php endif; ?>
                        
                        <div class="text-center">
                            <?php 
                            $image_src = $product['image_url'] ?? '';
                            if (empty($image_src)) {
                                $image_src = '../assets/images/default-product.jpg';
                            } elseif (!filter_var($image_src, FILTER_VALIDATE_URL) && substr($image_src, 0, 3) !== '../') {
                                $image_src = '../' . $image_src;
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($image_src); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 class="main-product-image"
                                 onerror="this.src='../assets/images/default-product.jpg'">
                        </div>
                    </div>
                </div>

                <!-- Product Info -->
                <div class="col-lg-6">
                    <div class="product-info-section">
                        <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                        
                        <!-- Rating -->
                        <div class="product-rating">
                            <div class="rating-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= floor($avgRating)): ?>
                                        <i class="fas fa-star"></i>
                                    <?php elseif ($i <= $avgRating): ?>
                                        <i class="fas fa-star-half-alt"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <span class="rating-value"><?php echo number_format($avgRating, 1); ?></span>
                            <span class="rating-count">(<?php echo $totalReviews; ?> đánh giá)</span>
                        </div>

                        <!-- Price Section - Compact -->
                        <div class="product-price-section">
                            <div class="price-header">
                                <span class="current-price"><?php echo number_format($finalPrice, 0, ',', '.'); ?>đ</span>
                                <?php if ($discountPercent > 0): ?>
                                    <span class="price-save">Tiết kiệm <?php echo $discountPercent; ?>%</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($discountPercent > 0): ?>
                                <div class="price-details">
                                    <span class="original-price"><?php echo number_format($originalPrice, 0, ',', '.'); ?>đ</span>
                                    <small>Bạn tiết kiệm được <?php echo number_format($savedAmount, 0, ',', '.'); ?>đ</small>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Stock Status -->
                        <div class="product-stock <?php echo $product['stock'] > 0 ? '' : 'out-of-stock'; ?>">
                            <?php if ($product['stock'] > 0): ?>
                                <i class="fas fa-check-circle"></i>
                                <span>Còn <strong><?php echo $product['stock']; ?></strong> sản phẩm</span>
                            <?php else: ?>
                                <i class="fas fa-times-circle"></i>
                                <span>Tạm hết hàng</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($product['stock'] > 0): ?>
                        <!-- Quantity -->
                        <div class="quantity-section">
                            <div class="quantity-label">Số lượng:</div>
                            <div class="quantity-controls">
                                <div class="quantity-wrapper">
                                    <button class="quantity-btn minus" onclick="changeQuantity(-1)">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" value="1" min="1" max="<?php echo $product['stock']; ?>" 
                                           class="quantity-input" id="quantityInput">
                                    <button class="quantity-btn plus" onclick="changeQuantity(1)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Tối đa <?php echo $product['stock']; ?> sản phẩm</small>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="action-buttons">
                            <button class="btn-buy-now" data-id="<?php echo $product['product_id']; ?>">
                                MUA NGAY
                            </button>
                            
                            <button class="btn-add-cart add-to-cart" data-id="<?php echo $product['product_id']; ?>">
                                <i class="fas fa-cart-plus"></i>
                            </button>
                            
                            <button class="btn-wishlist add-to-wishlist" data-id="<?php echo $product['product_id']; ?>">
                                <i class="far fa-heart"></i>
                            </button>
                        </div>
                        <?php endif; ?>

                        <!-- Description -->
                        <div class="product-description">
                            <div class="description-title">
                                <i class="fas fa-clipboard-list"></i> Mô tả sản phẩm
                            </div>
                            <div class="description-text">
                                <?php 
                                // Hiển thị HTML từ TinyMCE, nhưng loại bỏ các tags nguy hiểm
                                $allowed_tags = '<p><br><br/><strong><b><em><i><u><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><a><img><table><tr><td><th><thead><tbody><tfoot>';
                                echo strip_tags($product['description'], $allowed_tags);
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        <?php if (!empty($relatedProducts)): ?>
        <div class="related-products">
            <h3 class="section-title">Sản phẩm liên quan</h3>
            <div class="row g-4">
                <?php foreach ($relatedProducts as $relatedProduct): ?>
                <div class="col-lg-3 col-md-4 col-6">
                    <a href="details.php?id=<?php echo $relatedProduct['product_id']; ?>" 
                       class="related-product-card">
                        <div class="related-product-image">
                            <?php 
                            $related_image = $relatedProduct['image_url'] ?? '';
                            if (empty($related_image)) {
                                $related_image = '../assets/images/default-product.jpg';
                            } elseif (!filter_var($related_image, FILTER_VALIDATE_URL) && substr($related_image, 0, 3) !== '../') {
                                $related_image = '../' . $related_image;
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($related_image); ?>" 
                                 alt="<?php echo htmlspecialchars($relatedProduct['name']); ?>"
                                 onerror="this.src='../assets/images/default-product.jpg'">
                            
                            <?php if (isset($relatedProduct['discount_percent']) && $relatedProduct['discount_percent'] > 0): ?>
                            <div class="discount-badge">
                                -<?php echo $relatedProduct['discount_percent']; ?>%
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="related-product-info">
                            <h4 class="related-product-name">
                                <?php echo htmlspecialchars($relatedProduct['name']); ?>
                            </h4>
                            
                            <div class="related-product-price">
                                <?php 
                                $relatedPrice = floatval($relatedProduct['price']);
                                $relatedDiscount = floatval($relatedProduct['discount_amount'] ?? 0);
                                $relatedFinal = max(0, $relatedPrice - $relatedDiscount);
                                ?>
                                
                                <?php if ($relatedDiscount > 0): ?>
                                    <?php echo number_format($relatedFinal, 0, ',', '.'); ?>đ
                                    <small style="text-decoration: line-through; color: #999; margin-left: 8px;">
                                        <?php echo number_format($relatedPrice, 0, ',', '.'); ?>đ
                                    </small>
                                <?php else: ?>
                                    <?php echo number_format($relatedPrice, 0, ',', '.'); ?>đ
                                <?php endif; ?>
                            </div>

                            <div class="product-status">
                                <?php if ($relatedProduct['stock'] > 0): ?>
                                <span class="badge bg-success">Còn hàng</span>
                                <?php else: ?>
                                <span class="badge bg-danger">Hết hàng</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Product Reviews Section -->
        <div class="product-reviews">
            <div class="reviews-header">
                <div class="reviews-summary">
                    <div class="rating-summary">
                        <div class="overall-rating">
                            <div class="rating-score"><?php echo number_format($avgRating, 1); ?></div>
                            <div class="rating-stars-large">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= floor($avgRating)): ?>
                                        <i class="fas fa-star"></i>
                                    <?php elseif ($i <= $avgRating): ?>
                                        <i class="fas fa-star-half-alt"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <div class="rating-count-text"><?php echo $totalReviews; ?> đánh giá</div>
                        </div>
                        <div>
                            <h3 class="section-title mb-0">
                                <i class="fas fa-comments me-2"></i>
                                Đánh giá sản phẩm
                            </h3>
                            <p class="text-muted mb-0">
                                <?php if ($totalReviews > 0): ?>
                                    Dựa trên <?php echo $totalReviews; ?> đánh giá từ khách hàng
                                <?php else: ?>
                                    Chưa có đánh giá nào
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reviews List -->
            <div class="reviews-list">
                <?php if (!empty($reviews)): ?>
                    <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <div class="reviewer-info">
                                                                 <div class="reviewer-avatar">
                                     <?php 
                                     $reviewer_name = '';
                                     if (isset($review['full_name']) && !empty($review['full_name'])) {
                                         $reviewer_name = $review['full_name'];
                                     } elseif (isset($review['username']) && !empty($review['username'])) {
                                         $reviewer_name = $review['username'];
                                     } else {
                                         $reviewer_name = 'Khách hàng';
                                     }
                                     echo strtoupper(substr($reviewer_name, 0, 1)); 
                                     ?>
                                 </div>
                                 <div class="reviewer-details">
                                     <h5><?php echo htmlspecialchars($reviewer_name); ?></h5>
                                    <div class="review-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $review['rating']): ?>
                                                <i class="fas fa-star"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="review-date">
                                <i class="fas fa-clock"></i>
                                <?php echo timeAgo($review['created_at']); ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($review['comment'])): ?>
                        <div class="review-comment">
                            "<?php echo htmlspecialchars($review['comment']); ?>"
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-reviews">
                        <i class="fas fa-comments"></i>
                        <h4>Chưa có đánh giá nào</h4>
                        <p>Hãy là người đầu tiên đánh giá sản phẩm này!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Review Form Section -->
            <div class="review-form-section">
                <?php if ($review_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($review_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($review_error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($review_error); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Review Form for Logged In Users -->
                    <div class="review-form">
                        <div class="form-header">
                            <h4 class="form-title">
                                <?php if ($user_existing_review): ?>
                                    <i class="fas fa-edit me-2"></i>Cập nhật đánh giá
                                <?php else: ?>
                                    <i class="fas fa-star me-2"></i>Viết đánh giá
                                <?php endif; ?>
                            </h4>
                            <p class="form-subtitle">
                                <?php if ($user_existing_review): ?>
                                    Chỉnh sửa đánh giá của bạn về sản phẩm này
                                <?php else: ?>
                                    Chia sẻ trải nghiệm của bạn về sản phẩm này
                                <?php endif; ?>
                            </p>
                        </div>

                        <form method="POST" id="reviewForm">
                            <!-- Rating Stars -->
                            <div class="rating-input-section">
                                <label class="rating-label">Chọn số sao đánh giá:</label>
                                <div class="star-rating-input" id="starRatingInput">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <button type="button" class="star-btn" data-rating="<?php echo $i; ?>">
                                            <i class="fas fa-star"></i>
                                        </button>
                                    <?php endfor; ?>
                                </div>
                                <input type="hidden" name="rating" id="ratingInput" 
                                       value="<?php echo $user_existing_review['rating'] ?? 0; ?>" required>
                                <div class="rating-text" id="ratingText">
                                    <?php if ($user_existing_review): ?>
                                        <?php echo $user_existing_review['rating']; ?> sao
                                    <?php else: ?>
                                        Chọn đánh giá của bạn
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Comment -->
                            <div class="comment-section">
                                <label for="comment" class="comment-label">
                                    <i class="fas fa-comment me-2"></i>Nhận xét của bạn:
                                </label>
                                <textarea name="comment" id="comment" class="comment-textarea" 
                                          placeholder="Chia sẻ trải nghiệm, cảm nhận của bạn về sản phẩm này..." 
                                          required><?php echo htmlspecialchars($user_existing_review['comment'] ?? ''); ?></textarea>
                                <small class="text-muted">Tối thiểu 10 ký tự</small>
                            </div>

                            <!-- Submit Button -->
                            <div class="form-actions">
                                <button type="submit" name="submit_review" class="submit-review-btn" id="submitBtn">
                                    <i class="fas fa-paper-plane me-2"></i>
                                    <?php echo $user_existing_review ? 'Cập nhật đánh giá' : 'Gửi đánh giá'; ?>
                                </button>
                            </div>
                        </form>
                    </div>

                <?php else: ?>
                    <!-- Login Prompt -->
                    <div class="login-prompt">
                        <i class="fas fa-user-lock"></i>
                        <h4>Đăng nhập để đánh giá</h4>
                        <p>Vui lòng đăng nhập để có thể viết đánh giá và chia sẻ trải nghiệm về sản phẩm này.</p>
                        <a href="../login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="login-btn">
                            <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập ngay
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/cart-new.js"></script>
    
    <script>
        // Hàm hiển thị thông báo giỏ hàng
        function showCartNotification(type, message) {
            // Xóa thông báo cũ nếu có
            $('.cart-notification').remove();
            
            // Tạo thông báo mới
            const notification = $(`
                <div class="cart-notification ${type}">
                    <div class="icon">
                        <i class="fas ${type === 'success' ? 'fa-check' : 'fa-exclamation-triangle'}"></i>
                    </div>
                    <div class="content">
                        <div class="title">${type === 'success' ? 'Thành công!' : 'Lỗi!'}</div>
                        <div class="message">${message}</div>
                    </div>
                    <div class="close">
                        <i class="fas fa-times"></i>
                    </div>
                </div>
            `);
            
            // Thêm vào body
            $('body').append(notification);
            
            // Hiển thị notification với animation
            setTimeout(() => notification.addClass('show'), 100);
            
            // Xử lý nút đóng
            notification.find('.close').on('click', () => {
                notification.removeClass('show');
                setTimeout(() => notification.remove(), 300);
            });
            
            // Tự động ẩn sau 3 giây
            setTimeout(() => {
                notification.removeClass('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Quantity Controls
        function changeQuantity(delta) {
            const input = document.getElementById('quantityInput');
            const current = parseInt(input.value);
            const max = parseInt(input.getAttribute('max'));
            const min = parseInt(input.getAttribute('min'));
            
            const newValue = current + delta;
            
            if (newValue >= min && newValue <= max) {
                input.value = newValue;
                updateButtons();
            }
        }
        
        function updateButtons() {
            const input = document.getElementById('quantityInput');
            const minusBtn = document.querySelector('.quantity-btn.minus');
            const plusBtn = document.querySelector('.quantity-btn.plus');
            const current = parseInt(input.value);
            const max = parseInt(input.getAttribute('max'));
            const min = parseInt(input.getAttribute('min'));
            
            minusBtn.disabled = current <= min;
            plusBtn.disabled = current >= max;
        }
        
        // Event listeners
        document.getElementById('quantityInput').addEventListener('input', function() {
            const max = parseInt(this.getAttribute('max'));
            const min = parseInt(this.getAttribute('min'));
            let value = parseInt(this.value);
            
            if (value > max) this.value = max;
            if (value < min) this.value = min;
            
            updateButtons();
        });
        
        // Buy Now functionality
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-buy-now')) {
                e.preventDefault();
                const buyBtn = e.target.closest('.btn-buy-now');
                const productId = buyBtn.dataset.id;
                
                if (!productId) return;
                
                // Get quantity
                const quantityInput = document.querySelector('.quantity-input');
                const quantity = quantityInput ? parseInt(quantityInput.value) || 1 : 1;
                
                // Add loading state
                const originalHtml = buyBtn.innerHTML;
                buyBtn.disabled = true;
                buyBtn.classList.add('loading');
                buyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
                
                // Add to cart first, then redirect to checkout
                fetch('../api/cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: parseInt(productId),
                        quantity: quantity
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update cart count if cartManager exists
                        if (window.cartManager) {
                            window.cartManager.updateCartCount(data.cart_count);
                        }
                        
                        // Redirect to checkout
                        window.location.href = '../checkout.php';
                    } else {
                        // Show error notification
                        if (window.cartManager) {
                            window.cartManager.showNotification(data.message || 'Có lỗi xảy ra', 'error');
                        } else {
                            alert(data.message || 'Có lỗi xảy ra');
                        }
                        
                        // Restore button
                        buyBtn.disabled = false;
                        buyBtn.classList.remove('loading');
                        buyBtn.innerHTML = originalHtml;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    // Show error notification
                    if (window.cartManager) {
                        window.cartManager.showNotification('Có lỗi xảy ra khi thêm sản phẩm', 'error');
                    } else {
                        alert('Có lỗi xảy ra');
                    }
                    
                    // Restore button
                    buyBtn.disabled = false;
                    buyBtn.classList.remove('loading');
                    buyBtn.innerHTML = originalHtml;
                });
            }
        });
        
        // Review Form Star Rating
        document.addEventListener('DOMContentLoaded', function() {
            updateButtons();
            initializeReviewForm();
            
            // Xử lý thêm vào giỏ hàng
            $('.btn-add-cart').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $btn = $(this);
                const productId = $btn.data('id');
                const quantity = parseInt($('#quantityInput').val()) || 1;

                // Thêm class loading
                $btn.addClass('loading').prop('disabled', true);
                
                // Gọi API thêm vào giỏ
                $.ajax({
                    url: '/api/cart/add.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        product_id: productId,
                        quantity: quantity
                    }),
                    success: function(response) {
                        if (response.success) {
                            // Hiển thị thông báo thành công
                            showCartNotification('success', 'Đã thêm vào giỏ hàng');
                            
                            // Cập nhật số lượng giỏ hàng
                            updateCartCount();
                        } else {
                            // Hiển thị lỗi
                            showCartNotification('error', response.message || 'Có lỗi xảy ra khi thêm vào giỏ hàng');
                        }
                    },
                    error: function() {
                        showCartNotification('error', 'Không thể kết nối đến server');
                    },
                    complete: function() {
                        // Xóa class loading
                        $btn.removeClass('loading').prop('disabled', false);
                    }
                });
            });
        });
        
        function initializeReviewForm() {
            const starButtons = document.querySelectorAll('.star-btn');
            const ratingInput = document.getElementById('ratingInput');
            const ratingText = document.getElementById('ratingText');
            const submitBtn = document.getElementById('submitBtn');
            const commentTextarea = document.getElementById('comment');
            
            if (!starButtons.length) return;
            
            // Rating text mapping
            const ratingTexts = {
                0: 'Chọn đánh giá của bạn',
                1: '⭐ Rất tệ',
                2: '⭐⭐ Tệ', 
                3: '⭐⭐⭐ Bình thường',
                4: '⭐⭐⭐⭐ Tốt',
                5: '⭐⭐⭐⭐⭐ Xuất sắc'
            };
            
            // Initialize existing rating
            const currentRating = parseInt(ratingInput.value) || 0;
            updateStars(currentRating);
            updateRatingText(currentRating);
            
            // Star click handlers
            starButtons.forEach((btn, index) => {
                const rating = index + 1;
                
                btn.addEventListener('click', function() {
                    ratingInput.value = rating;
                    updateStars(rating);
                    updateRatingText(rating);
                    validateForm();
                });
                
                btn.addEventListener('mouseenter', function() {
                    updateStars(rating);
                });
                
                btn.addEventListener('mouseleave', function() {
                    const currentRating = parseInt(ratingInput.value) || 0;
                    updateStars(currentRating);
                });
            });
            
            // Comment validation
            if (commentTextarea) {
                commentTextarea.addEventListener('input', validateForm);
            }
            
            function updateStars(rating) {
                starButtons.forEach((btn, index) => {
                    if (index < rating) {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                });
            }
            
            function updateRatingText(rating) {
                if (ratingText) {
                    ratingText.textContent = ratingTexts[rating] || ratingTexts[0];
                }
            }
            
            function validateForm() {
                const rating = parseInt(ratingInput.value) || 0;
                const comment = commentTextarea ? commentTextarea.value.trim() : '';
                const isValid = rating > 0 && comment.length >= 10;
                
                if (submitBtn) {
                    submitBtn.disabled = !isValid;
                }
            }
            
            // Form submission
            const reviewForm = document.getElementById('reviewForm');
            if (reviewForm) {
                reviewForm.addEventListener('submit', function(e) {
                    const rating = parseInt(ratingInput.value) || 0;
                    const comment = commentTextarea ? commentTextarea.value.trim() : '';
                    
                    if (rating < 1 || rating > 5) {
                        e.preventDefault();
                        alert('Vui lòng chọn số sao từ 1 đến 5');
                        return;
                    }
                    
                    if (comment.length < 10) {
                        e.preventDefault();
                        alert('Nhận xét phải có ít nhất 10 ký tự');
                        return;
                    }
                    
                    // Show loading
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang gửi...';
                });
            }
            
            // Initial validation
            validateForm();
        }
    </script>
</body>
</html> 