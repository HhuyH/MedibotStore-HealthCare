<?php
session_start();
require_once '../includes/db.php';

// Kiểm tra kết nối database
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    die("Vui lòng đăng nhập để test");
}

$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Add to Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .debug-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .test-result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
        }
        .test-result.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
        }
        .test-result.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body class="container py-5">
    <h1>Test Add to Cart</h1>
    
    <div class="debug-info">
        <h3>Session Info:</h3>
        <pre><?php print_r($_SESSION); ?></pre>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Test Form</h5>
                    <form id="testForm" class="mt-3">
                        <div class="mb-3">
                            <label for="productId" class="form-label">Product ID</label>
                            <input type="number" class="form-control" id="productId" value="1">
                        </div>
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity" value="1">
                        </div>
                        <button type="submit" class="btn btn-primary">Test Add to Cart</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Test Results</h5>
                    <div id="testResults"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#testForm').on('submit', function(e) {
                e.preventDefault();
                
                const productId = $('#productId').val();
                const quantity = $('#quantity').val();
                
                // Log request data
                console.log('Sending request with:', {
                    product_id: productId,
                    quantity: quantity
                });

                // Test AJAX call
                $.ajax({
                    url: '/api/cart/add.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        product_id: productId,
                        quantity: quantity
                    }),
                    beforeSend: function(xhr) {
                        // Log request headers
                        console.log('Request headers:', xhr.getAllResponseHeaders());
                    },
                    success: function(response) {
                        console.log('Raw response:', response);
                        
                        let result;
                        try {
                            // Try to parse if string
                            result = typeof response === 'string' ? JSON.parse(response) : response;
                            
                            // Display result
                            $('#testResults').html(`
                                <div class="test-result ${result.success ? 'success' : 'error'}">
                                    <h6>${result.success ? 'Success' : 'Error'}</h6>
                                    <p>${result.message}</p>
                                    <pre>${JSON.stringify(result, null, 2)}</pre>
                                </div>
                            `);
                        } catch (e) {
                            console.error('Parse error:', e);
                            $('#testResults').html(`
                                <div class="test-result error">
                                    <h6>Parse Error</h6>
                                    <p>Could not parse response</p>
                                    <pre>${response}</pre>
                                    <p>Error: ${e.message}</p>
                                </div>
                            `);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Ajax error:', {
                            status: status,
                            error: error,
                            response: xhr.responseText
                        });
                        
                        $('#testResults').html(`
                            <div class="test-result error">
                                <h6>AJAX Error</h6>
                                <p>Status: ${status}</p>
                                <p>Error: ${error}</p>
                                <pre>${xhr.responseText}</pre>
                            </div>
                        `);
                    }
                });
            });
        });
</script>
</body>
</html>