// Hàm cập nhật số lượng giỏ hàng
function updateCartCount() {
    $.ajax({
        url: '/api/cart/count.php',
        type: 'GET',
        success: function(response) {
            try {
                const data = JSON.parse(response);
                // Đếm số loại sản phẩm, không phải tổng số lượng
                const count = data.items ? Object.keys(data.items).length : 0;
                $('.cart-count').text(count);
                
                // Thêm class updating để kích hoạt animation
                $('.cart-count').addClass('updating');
                setTimeout(() => {
                    $('.cart-count').removeClass('updating');
                }, 500);
                
                // Cập nhật tổng tiền nếu có
                if (data.total) {
                    $('.cart-total').text(formatCurrency(data.total));
                }
            } catch (e) {
                console.error('Error parsing cart count:', e);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error updating cart count:', error);
        }
    });
}

// Hàm format tiền tệ
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount).replace('₫', 'đ');
}

// Hàm thêm vào giỏ hàng
function addToCart(productId, quantity = 1) {
    console.log('Sending request to add product:', productId, 'quantity:', quantity); // Debug log

    $.ajax({
        url: '/api/cart/add.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            product_id: productId,
            quantity: quantity
        }),
        beforeSend: function() {
            console.log('Sending request...'); // Debug log
        },
        success: function(response) {
            console.log('Raw response:', response); // Debug log
            try {
                const data = typeof response === 'string' ? JSON.parse(response) : response;
                console.log('Parsed data:', data); // Debug log

                if (data.success) {
                    showCartNotification('success', 'Đã thêm vào giỏ hàng');
                    updateCartCount();
                    
                    // Thêm hiệu ứng cho nút
                    const btn = $(`.add-to-cart[data-id="${productId}"]`);
                    btn.addClass('added');
                    setTimeout(() => btn.removeClass('added'), 1000);
                } else {
                    console.error('Server error:', data.message);
                    showCartNotification('error', data.message || 'Có lỗi xảy ra');
                }
            } catch (e) {
                console.error('Parse error:', e);
                console.error('Original response:', response);
                showCartNotification('error', 'Có lỗi xảy ra khi xử lý phản hồi');
            }
        },
        error: function(xhr, status, error) {
            console.error('Ajax error:', {
                status: status,
                error: error,
                response: xhr.responseText,
                state: xhr.state(),
                readyState: xhr.readyState
            });
            
            let errorMessage = 'Không thể thêm vào giỏ hàng';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (xhr.status === 401) {
                errorMessage = 'Vui lòng đăng nhập để thêm vào giỏ hàng';
            }
            
            showCartNotification('error', errorMessage);
        }
    });
}

// Hàm hiển thị thông báo
function showCartNotification(type, message) {
    // Xóa thông báo cũ nếu có
    $('.cart-notification').remove();
    
    // Tạo thông báo mới
    const notification = $('<div>', {
        class: `cart-notification alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`,
        role: 'alert'
    }).css({
        'position': 'fixed',
        'top': '20px',
        'right': '20px',
        'z-index': '9999',
        'min-width': '300px',
        'background': type === 'success' ? 'rgba(40, 167, 69, 0.95)' : 'rgba(220, 53, 69, 0.95)',
        'color': '#fff',
        'border': 'none',
        'border-radius': '10px',
        'box-shadow': '0 4px 12px rgba(0,0,0,0.15)',
        'backdrop-filter': 'blur(10px)',
        'padding': '1rem 1.5rem'
    });

    // Icon
    const icon = $('<i>', {
        class: `fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2`
    });

    // Nội dung
    const content = $('<span>').text(message);

    // Nút đóng
    const closeButton = $('<button>', {
        type: 'button',
        class: 'btn-close',
        'data-bs-dismiss': 'alert',
        'aria-label': 'Close'
    }).css('color', '#fff');

    // Ghép các phần tử
    notification.append(icon, content, closeButton);

    // Thêm vào body
    $('body').append(notification);

    // Tự động ẩn sau 3 giây
    setTimeout(() => {
        notification.alert('close');
    }, 3000);
}

// Khởi tạo khi trang load xong
$(document).ready(function() {
    // Cập nhật số lượng giỏ hàng ban đầu
    updateCartCount();

    // Xử lý sự kiện click nút thêm vào giỏ
    $(document).on('click', '.add-to-cart', function(e) {
        e.preventDefault();
        const productId = $(this).data('id');
        const quantity = parseInt($(this).data('quantity') || 1);
        addToCart(productId, quantity);
    });
});