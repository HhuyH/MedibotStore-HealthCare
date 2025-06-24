<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Tools - QickMed</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }
        
        .header h1 {
            font-size: 3rem;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
            margin: 10px 0;
        }
        
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .tool-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 3px solid transparent;
        }
        
        .tool-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 48px rgba(0,0,0,0.2);
            border-color: #667eea;
        }
        
        .tool-icon {
            font-size: 3rem;
            margin-bottom: 16px;
            text-align: center;
        }
        
        .tool-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 12px;
            text-align: center;
        }
        
        .tool-description {
            color: #7f8c8d;
            margin-bottom: 20px;
            line-height: 1.6;
            text-align: center;
        }
        
        .tool-button {
            display: block;
            width: 100%;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .tool-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .status-section {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .status-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 16px;
            text-align: center;
        }
        
        .back-link {
            text-align: center;
            margin-top: 40px;
        }
        
        .back-link a {
            color: white;
            text-decoration: none;
            font-size: 1.1rem;
            opacity: 0.9;
            transition: opacity 0.3s ease;
        }
        
        .back-link a:hover {
            opacity: 1;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Debug Tools</h1>
            <p>Công cụ debug cho hệ thống shopping cart</p>
        </div>
        
        <?php
        session_start();
        if (isset($_SESSION['user_id'])) {
            echo "<div class='status-section'>";
            echo "<div class='status-title'>✅ Logged in as User ID: {$_SESSION['user_id']}</div>";
            echo "</div>";
        } else {
            echo "<div class='status-section'>";
            echo "<div class='status-title'>⚠️ Not logged in - Some tools may not work</div>";
            echo "</div>";
        }
        ?>
        
        <div class="tools-grid">
            <div class="tool-card">
                <div class="tool-icon">🛒</div>
                <div class="tool-title">Cart Log</div>
                <div class="tool-description">
                    Theo dõi real-time quá trình thêm sản phẩm vào giỏ hàng và debug cart issues
                </div>
                <a href="cart_log.php" class="tool-button">Open Cart Log</a>
            </div>
            
            <div class="tool-card">
                <div class="tool-icon">📊</div>
                <div class="tool-title">Cart Data</div>
                <div class="tool-description">
                    Xem chi tiết dữ liệu trong database về orders, order_items và products
                </div>
                <a href="debug_cart_data.php" class="tool-button">View Cart Data</a>
            </div>
            
            <div class="tool-card">
                <div class="tool-icon">🔘</div>
                <div class="tool-title">Button Test</div>
                <div class="tool-description">
                    Test button add to cart hoạt động có đúng không và kiểm tra cart count
                </div>
                <a href="debug_button.php" class="tool-button">Test Buttons</a>
            </div>
            
            <div class="tool-card">
                <div class="tool-icon">🏪</div>
                <div class="tool-title">Shop Environment</div>
                <div class="tool-description">
                    Test buttons trong môi trường giống shop.php với tất cả CSS/JS
                </div>
                <a href="debug_shop.php" class="tool-button">Test Shop Env</a>
            </div>
            
            <div class="tool-card">
                <div class="tool-icon">➕</div>
                <div class="tool-title">Add Cart Test</div>
                <div class="tool-description">
                    Test trực tiếp API add to cart với form input và debug output
                </div>
                <a href="test_add_cart.php" class="tool-button">Test Add Cart</a>
            </div>
            
            <div class="tool-card">
                <div class="tool-icon">🔍</div>
                <div class="tool-title">Simple Debug</div>
                <div class="tool-description">
                    Debug đơn giản với minimal code để test cart functionality
                </div>
                <a href="simple_debug.php" class="tool-button">Simple Debug</a>
            </div>
        </div>
        
        <div class="status-section">
            <div class="status-title">🎯 Current Issue</div>
            <p style="text-align: center; color: #e74c3c; font-weight: 500;">
                Cart hiển thị cùng tên và giá cho tất cả sản phẩm (như hình đã gửi)
            </p>
            <p style="text-align: center; color: #7f8c8d;">
                Sử dụng <strong>Cart Log</strong> và <strong>Cart Data</strong> để debug vấn đề này
            </p>
        </div>
        
        <div class="back-link">
            <a href="../cart.php">← Back to Cart</a> | 
            <a href="../shop.php">← Back to Shop</a>
        </div>
    </div>
</body>
</html> 