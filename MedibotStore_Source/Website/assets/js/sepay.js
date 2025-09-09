function copyToClipboard(text, buttonId) {
    navigator.clipboard.writeText(text).then(() => {
        const button = document.getElementById(buttonId);
        const originalText = button.textContent;
        button.textContent = 'Đã sao chép!';
        setTimeout(() => {
            button.textContent = originalText;
        }, 2000);
    });
}

function generateQRCode(amount, orderId) {
    $.ajax({
        url: 'api/generate_sepay_qr.php',
        method: 'POST',
        data: {
            amount: amount,
            order_id: orderId
        },
        success: function(response) {
            if (response.success) {
                $('#sepayQRCode').attr('src', response.qr_url);
                $('#sepayQRContainer').slideDown();
                startCheckingPayment(orderId);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: 'Không thể tạo mã QR. Vui lòng thử lại sau.'
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                text: 'Có lỗi xảy ra. Vui lòng thử lại sau.'
            });
        }
    });
}

function startCheckingPayment(orderId) {
    const checkInterval = setInterval(() => {
        $.ajax({
            url: 'api/check_payment_status.php',
            method: 'POST',
            data: { order_id: orderId },
            success: function(response) {
                if (response.status === 'completed') {
                    clearInterval(checkInterval);
                    $('#paymentStatus')
                        .removeClass('pending')
                        .addClass('success')
                        .html('<i class="fas fa-check-circle"></i> Thanh toán thành công!')
                        .show();
                    
                    setTimeout(() => {
                        window.location.href = 'order-success.php?order_id=' + orderId;
                    }, 2000);
                } else if (response.status === 'pending') {
                    $('#paymentStatus')
                        .addClass('pending')
                        .removeClass('success')
                        .html('<i class="fas fa-clock"></i> Đang chờ thanh toán...')
                        .show();
                }
            }
        });
    }, 5000); // Kiểm tra mỗi 5 giây
}

