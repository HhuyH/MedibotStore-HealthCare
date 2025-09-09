<?php
require_once '../includes/db.php';
require_once '../includes/functions/product_functions.php';

// Lấy ID sản phẩm từ URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Debug
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    echo "Product ID: " . $product_id . "<br>";
}

// Kiểm tra ID hợp lệ
if ($product_id <= 0) {
    header("Location: /shop/index.php");
    exit;
}

// Lấy thông tin sản phẩm
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = ?";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Lỗi prepare statement: " . $conn->error);
}

$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

// Nếu không tìm thấy sản phẩm
if (!$product) {
    header("Location: /shop/index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Chi tiết sản phẩm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/shop/index.php">Trang chủ</a></li>
                <li class="breadcrumb-item">
                    <a href="/shop/category.php?id=<?php echo $product['category_id']; ?>">
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?php echo htmlspecialchars($product['name']); ?>
                </li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-md-6">
                <?php if (!empty($product['image'])): ?>
                    <img src="/uploads/products/<?php echo htmlspecialchars($product['image']); ?>" 
                         class="img-fluid rounded" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                <?php else: ?>
                    <div class="bg-light p-5 text-center">
                        <p class="mb-0">Không có hình ảnh</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <h1 class="mb-4"><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="text-muted mb-4">
                    Danh mục: <?php echo htmlspecialchars($product['category_name']); ?>
                </p>
                <div class="mb-4">
                    <h2 class="h4">Giá: <?php echo number_format($product['price'], 0, ',', '.'); ?> VNĐ</h2>
                </div>
                <div class="mb-4">
                    <h3 class="h5">Mô tả sản phẩm:</h3>
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>
                <div class="d-grid gap-2">
                    <button class="btn btn-primary btn-lg">Thêm vào giỏ hàng</button>
                    <a href="/shop/index.php" class="btn btn-outline-secondary">
                        Tiếp tục mua sắm
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 