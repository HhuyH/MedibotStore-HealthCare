<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions/format_helpers.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Kiểm tra order_id
if (!isset($_GET['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing order ID']);
    exit();
}

$order_id = (int)$_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("
    SELECT 
        o.*, 
        os.note as status_note,
        os.created_at as status_time
    FROM orders o
    LEFT JOIN order_status_history os ON o.order_id = os.order_id
    WHERE o.order_id = ? AND o.user_id = ?
    ORDER BY os.created_at DESC
");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

$stmt->bind_param('ii', $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit();
}

// Lấy chi tiết sản phẩm
$stmt = $conn->prepare("
    SELECT 
        oi.*,
        p.name,
        p.image_url as display_image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
");

$stmt->bind_param('i', $order_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Lấy lịch sử trạng thái
$stmt = $conn->prepare("
    SELECT 
        status,
        note,
        created_at
    FROM order_status_history
    WHERE order_id = ?
    ORDER BY created_at DESC
");

$stmt->bind_param('i', $order_id);
$stmt->execute();
$status_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Tạo HTML cho modal
$html = '
<div class="order-detail-content">
    <div class="order-info mb-4">
        <h6 class="text-muted">Thông tin đơn hàng</h6>
        <div class="row">
            <div class="col-md-6">
                <p><strong>Ngày đặt:</strong> ' . date('d/m/Y H:i', strtotime($order['order_date'])) . '</p>
                <p><strong>Trạng thái:</strong> ' . getStatusBadge($order['status'])['text'] . '</p>
                <p><strong>Phương thức thanh toán:</strong> ' . getPaymentBadge($order['payment_method'], $order['payment_status'])['method'] . '</p>
            </div>
            <div class="col-md-6">
                <p><strong>Tổng tiền:</strong> ' . format_currency($order['total']) . '</p>
                <p><strong>Trạng thái thanh toán:</strong> ' . getPaymentBadge($order['payment_method'], $order['payment_status'])['status_text'] . '</p>
            </div>
        </div>
    </div>

    <div class="shipping-info mb-4">
        <h6 class="text-muted">Thông tin giao hàng</h6>
        <p>' . nl2br(htmlspecialchars($order['shipping_address'])) . '</p>
    </div>

    <div class="order-items mb-4">
        <h6 class="text-muted">Sản phẩm</h6>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Số lượng</th>
                        <th>Đơn giá</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody>';

foreach ($items as $item) {
    $html .= '
        <tr>
            <td>
                <div class="d-flex align-items-center">
                    <img src="' . htmlspecialchars($item['display_image']) . '" 
                         alt="' . htmlspecialchars($item['name']) . '" 
                         class="me-2" 
                         style="width: 50px; height: 50px; object-fit: cover;">
                    <span>' . htmlspecialchars($item['name']) . '</span>
                </div>
            </td>
            <td>' . $item['quantity'] . '</td>
            <td>' . format_currency($item['unit_price']) . '</td>
            <td>' . format_currency($item['unit_price'] * $item['quantity']) . '</td>
        </tr>';
}

$html .= '
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Tổng cộng:</strong></td>
                        <td><strong>' . format_currency($order['total']) . '</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>';

if (!empty($status_history)) {
    $html .= '
    <div class="status-history">
        <h6 class="text-muted">Lịch sử trạng thái</h6>
        <div class="timeline">
            <ul class="list-unstyled">';
    
    foreach ($status_history as $history) {
        $status = getStatusBadge($history['status']);
        $html .= '
            <li class="mb-3">
                <div class="d-flex align-items-center">
                    <span class="badge bg-' . $status['class'] . ' me-2">' . $status['text'] . '</span>
                    <small class="text-muted">' . date('d/m/Y H:i', strtotime($history['created_at'])) . '</small>
                </div>
                ' . (!empty($history['note']) ? '<p class="mb-0 mt-1">' . htmlspecialchars($history['note']) . '</p>' : '') . '
            </li>';
    }
    
    $html .= '
            </ul>
        </div>
    </div>';
}

echo json_encode([
    'success' => true,
    'html' => $html
]);