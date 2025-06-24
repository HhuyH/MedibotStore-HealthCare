<?php

// Note: format_currency() đã được định nghĩa trong db.php

/**
 * Tính giá giảm dựa trên rating
 * @param float $price Giá gốc
 * @param float $rating Rating trung bình
 * @return array Thông tin giá và giảm giá
 */
if (!function_exists('calculateDiscountPrice')) {
function calculateDiscountPrice($price, $rating) {
    $discount_percent = $rating >= 4.5 ? 10 : 0;
    $discount_price = $discount_percent > 0 
        ? $price * (1 - $discount_percent/100) 
        : null;
    
    return [
        'discount_percent' => $discount_percent,
        'discount_price' => $discount_price,
        'original_price' => $price
    ];
}
}

/**
 * Lấy ảnh sản phẩm với fallback
 * @param string $image_url URL ảnh từ database
 * @return string URL ảnh hoặc placeholder
 */
if (!function_exists('getProductImage')) {
function getProductImage($image_url) {
    return !empty($image_url) ? $image_url : '/assets/images/product-placeholder.jpg';
}
}

/**
 * Format rating với 1 chữ số thập phân
 * @param float $rating Rating
 * @return string Rating đã format
 */
if (!function_exists('formatRating')) {
function formatRating($rating) {
    return number_format($rating, 1);
}
}

/**
 * Tạo HTML stars cho rating
 * @param float $rating Rating từ 0-5
 * @return string HTML của stars
 */
if (!function_exists('generateStars')) {
function generateStars($rating) {
    $html = '';
    $fullStars = floor($rating);
    $hasHalfStar = ($rating - $fullStars) >= 0.5;
    
    // Stars đầy
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<i class="fas fa-star"></i>';
    }
    
    // Half star
    if ($hasHalfStar) {
        $html .= '<i class="fas fa-star-half-alt"></i>';
        $fullStars++;
    }
    
    // Stars rỗng
    for ($i = $fullStars; $i < 5; $i++) {
        $html .= '<i class="far fa-star"></i>';
    }
    
    return $html;
}
}

/**
 * Tính thuế VAT (10%)
 * @param float $amount Số tiền
 * @return float Thuế VAT
 */
if (!function_exists('calculateVAT')) {
function calculateVAT($amount) {
    return $amount * 0.1;
}
}

/**
 * Tính phí ship (miễn phí nếu > 500k)
 * @param float $amount Tổng giá trị đơn hàng
 * @return float Phí ship
 */
if (!function_exists('calculateShipping')) {
function calculateShipping($amount) {
    return $amount >= 500000 ? 0 : 30000;
}
}

/**
 * Format số lượng sản phẩm
 * @param int $quantity Số lượng
 * @return string Chuỗi số lượng với định dạng
 */
if (!function_exists('formatQuantity')) {
function formatQuantity($quantity) {
    return number_format($quantity, 0, ',', '.');
}
}

/**
 * Tạo mã đơn hàng
 * @param int $order_id ID đơn hàng
 * @return string Mã đơn hàng formatted
 */
if (!function_exists('formatOrderCode')) {
function formatOrderCode($order_id) {
    return 'QM' . str_pad($order_id, 6, '0', STR_PAD_LEFT);
}
}

/**
 * Format ngày tháng Việt Nam
 * @param string $date Ngày tháng
 * @param string $format Format mong muốn
 * @return string Ngày đã format
 */
if (!function_exists('formatDateVN')) {
function formatDateVN($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}
}

/**
 * Tính thời gian tương đối (x phút trước, x giờ trước...)
 * @param string $date Ngày tháng
 * @return string Thời gian tương đối
 */
if (!function_exists('timeAgo')) {
function timeAgo($date) {
    $timestamp = strtotime($date);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return 'Vừa xong';
    } elseif ($difference < 3600) {
        $minutes = floor($difference / 60);
        return $minutes . ' phút trước';
    } elseif ($difference < 86400) {
        $hours = floor($difference / 3600);
        return $hours . ' giờ trước';
    } elseif ($difference < 2592000) {
        $days = floor($difference / 86400);
        return $days . ' ngày trước';
    } else {
        return formatDateVN($date);
    }
}
}
?> 