<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/config/sepay_config.php';
require_once 'includes/email_system_simple.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header('Location: index.php');
    exit();
}

// Khởi tạo thời gian đếm ngược nếu chưa có
if (!isset($_SESSION['payment_timer'][$_GET['order_id']])) {
    $_SESSION['payment_timer'][$_GET['order_id']] = time() + (15 * 60); // 15 phút
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("
    SELECT o.*, u.email, ui.full_name 
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    LEFT JOIN users_info ui ON u.user_id = ui.user_id
    WHERE o.order_id = ? AND o.user_id = ?
");

$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: index.php');
    exit();
}

// Nếu là COD, chuyển hướng đến trang thành công
if ($order['payment_method'] === 'cod') {
    header("Location: order-success.php?order_id=" . $order_id);
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán đơn hàng #<?php echo $order_id; ?> - MediBot</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: #f8fafc;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .main-content {
            padding: 2rem 0;
            margin-top: 60px;
        }

        .payment-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }

        .payment-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .payment-header h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
            color: #1f2937;
        }

        .timer {
            color: #6b7280;
            font-size: 0.875rem;
            background: #fef3c7;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
        }

        .payment-body {
            padding: 1.5rem;
        }

        .qr-section, .transfer-section {
            padding: 1rem;
        }

        .qr-section h5, .transfer-section h5 {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 1rem;
            text-align: center;
        }

        .qr-wrapper {
            text-align: center;
            padding: 1rem;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin: 0 auto;
            max-width: 280px;
        }

        .qr-wrapper img {
            width: 100%;
            height: auto;
            margin-bottom: 1rem;
        }

        .qr-logos {
            display: flex;
            justify-content: center;
            gap: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }

        .transfer-info {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }

        .info-row {
            display: flex;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            width: 120px;
            color: #6b7280;
            font-size: 0.875rem;
        }

        .info-value {
            flex: 1;
            color: #1f2937;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-value.amount {
            color: #2563eb;
            font-weight: 600;
            font-size: 1rem;
        }

        .copy-btn {
            background: none;
            border: none;
            color: #2563eb;
            padding: 0;
            font-size: 0.875rem;
            cursor: pointer;
        }

        .copy-btn:hover {
            text-decoration: underline;
        }

        .action-buttons {
            padding: 0 1rem;
        }

        .confirm-btn {
            display: block;
            width: 100%;
            padding: 0.75rem;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            margin-bottom: 0.75rem;
            cursor: pointer;
        }

        .confirm-btn:hover {
            background: #059669;
        }

        .cancel-btn {
            display: block;
            width: 100%;
            padding: 0.75rem;
            background: #f3f4f6;
            color: #4b5563;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
        }

        .cancel-btn:hover {
            background: #e5e7eb;
        }

        .border-end {
            border-right: 1px solid #e5e7eb;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }

            .border-end {
                border-right: none;
                border-bottom: 1px solid #e5e7eb;
                margin-bottom: 1rem;
                padding-bottom: 1rem;
            }

            .qr-section, .transfer-section {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="main-content">
        <div class="container">
            <div class="payment-card">
                <div class="payment-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4>Đơn hàng #<?php echo $order_id; ?></h4>
                        <div class="timer">
                            <i class="fas fa-clock me-1"></i>
                            <span id="countdown">14:11</span>
                        </div>
                    </div>
                </div>

                <div class="payment-body">
                    <div class="row">
                        <!-- Cột trái - QR Code -->
                        <div class="col-md-6 border-end">
                            <div class="qr-section">
                                <h5>Quét mã QR</h5>
                                <div class="qr-wrapper">
                                    <img id="sepayQRCode" src="" alt="QR Code">
                                    <div class="qr-logos">
                                        <!-- <img src="https://vietqr.net/img/napas-40.png" alt="Napas" height="24">
                                        <img src="https://vietqr.net/img/mb-bank-logo.png" alt="MB Bank" height="24"> -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cột phải - Thông tin chuyển khoản -->
                        <div class="col-md-6">
                            <div class="transfer-section">
                                <h5>Hoặc chuyển khoản thủ công</h5>
                                <div class="transfer-info">
                                    <div class="info-row">
                                        <div class="info-label">Số tiền:</div>
                                        <div class="info-value amount"><?php echo number_format($order['total'], 0, ',', '.'); ?>đ</div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Ngân hàng:</div>
                                        <div class="info-value">MB Bank</div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Số tài khoản:</div>
                                        <div class="info-value">
                                            8280111012003
                                            <button onclick="copyToClipboard('8280111012003', 'copyAccount')" class="copy-btn">Sao chép</button>
                                        </div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Chủ tài khoản:</div>
                                        <div class="info-value">DANG VUONG THAI DANG</div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Nội dung CK:</div>
                                        <div class="info-value">
                                            MDBDH<?php echo $order_id; ?>
                                            <button onclick="copyToClipboard('MDBDH<?php echo $order_id; ?>', 'copyContent')" class="copy-btn">Sao chép</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="action-buttons mt-4">
                        <button id="confirmPaymentBtn" class="confirm-btn">
                            <i class="fas fa-check-circle me-2"></i>Tôi đã thanh toán
                        </button>
                        <button onclick="window.location.href='orders.php'" class="cancel-btn">
                            Hủy thanh toán
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/sepay.js"></script>
    
    <script>
        $(document).ready(function() {
            // Generate QR code when page loads
            generateQRCode(<?php echo $order['total']; ?>, <?php echo $order_id; ?>);
            
            // Start checking payment status
            startCheckingPayment(<?php echo $order_id; ?>);
            
            // Countdown timer using server time
            const endTime = <?php echo $_SESSION['payment_timer'][$order_id]; ?> * 1000;
            const countdownEl = document.getElementById('countdown');
            
            function updateTimer() {
                const now = new Date().getTime();
                const timeLeft = Math.max(0, Math.floor((endTime - now) / 1000));
                
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                countdownEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                
                if (timeLeft <= 0) {
                    window.location.href = 'orders.php';
                }
            }
            
            updateTimer();
            const timer = setInterval(updateTimer, 1000);
            
            // Handle "I have paid" button
            $('#confirmPaymentBtn').click(function() {
                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Đang xác nhận...');
                
                setTimeout(() => {
                    window.location.href = 'order-success.php?order_id=<?php echo $order_id; ?>';
                }, 2000);
            });
        });
    </script>
</body>
</html>