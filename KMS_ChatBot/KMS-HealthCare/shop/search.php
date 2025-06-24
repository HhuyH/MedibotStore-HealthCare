<?php
require_once '../includes/db.php';
require_once '../includes/functions/product_functions.php';

// Debug: Kiểm tra kết nối database
if (!isset($conn) || $conn->connect_error) {
    die("Lỗi kết nối database: " . ($conn->connect_error ?? "Không có kết nối"));
}

// Debug: In ra thông tin tìm kiếm
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Kiểm tra dữ liệu trong database
define('DEBUG_MODE', true);
checkSampleData($conn);

// Xử lý tham số tìm kiếm
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
echo "Từ khóa tìm kiếm: " . htmlspecialchars($search) . "<br>";

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Khởi tạo biến
$products = [];
$totalProducts = 0;
$totalPages = 0;

if (!empty($search)) {
    // Tách từ khóa tìm kiếm thành các từ riêng lẻ
    $keywords = explode(' ', $search);
    $searchConditions = [];
    $params = [];
    $types = '';
    
    // Debug: In ra các từ khóa
    echo "Các từ khóa: ";
    print_r($keywords);
    echo "<br>";
    
    // Tạo điều kiện tìm kiếm cho từng từ khóa
    foreach ($keywords as $keyword) {
        $keyword = trim($keyword);
        if (!empty($keyword)) {
            $searchConditions[] = "(
                p.name LIKE ? OR 
                p.description LIKE ? OR 
                c.name LIKE ?
            )";
            $paramValue = "%$keyword%";
            array_push($params, $paramValue, $paramValue, $paramValue);
            $types .= 'sss';
        }
    }
    
    if (!empty($searchConditions)) {
        // Đếm tổng số sản phẩm
        $countSql = "SELECT COUNT(DISTINCT p.id) as total 
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.id
                     WHERE " . implode(' OR ', $searchConditions);
        
        // Debug: In ra câu SQL
        echo "SQL Query: " . $countSql . "<br>";
        echo "Params: ";
        print_r($params);
        echo "<br>";
        
        $stmt = $conn->prepare($countSql);
        if ($stmt === false) {
            die("Lỗi prepare statement: " . $conn->error);
        }
        
        if (!$stmt->bind_param($types, ...$params)) {
            die("Lỗi bind params: " . $stmt->error);
        }
        
        if (!$stmt->execute()) {
            die("Lỗi execute: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $totalProducts = $row['total'];
        
        // Debug: In ra số lượng sản phẩm tìm thấy
        echo "Tổng số sản phẩm: " . $totalProducts . "<br>";
        
        // Lấy danh sách sản phẩm
        if ($totalProducts > 0) {
            $sql = "SELECT DISTINCT p.*, c.name as category_name 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE " . implode(' OR ', $searchConditions) . "
                    ORDER BY 
                        CASE 
                            WHEN p.name LIKE ? THEN 1
                            WHEN p.name LIKE ? THEN 2
                            ELSE 3
                        END,
                        p.name ASC,
                        p.created_at DESC
                    LIMIT ? OFFSET ?";
            
            // Thêm params cho ORDER BY
            array_push($params, "$search%", "%$search%");
            $types .= 'ss';
            
            // Thêm params cho LIMIT và OFFSET
            array_push($params, $perPage, $offset);
            $types .= 'ii';
            
            // Debug: In ra câu SQL
            echo "SQL Products: " . $sql . "<br>";
            
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                die("Lỗi prepare statement products: " . $conn->error);
            }
            
            if (!$stmt->bind_param($types, ...$params)) {
                die("Lỗi bind params products: " . $stmt->error);
            }
            
            if (!$stmt->execute()) {
                die("Lỗi execute products: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $products = $result->fetch_all(MYSQLI_ASSOC);
            $totalPages = ceil($totalProducts / $perPage);
            
            // Debug: In ra sản phẩm tìm thấy
            echo "Sản phẩm tìm thấy: ";
            print_r($products);
            echo "<br>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả tìm kiếm: <?php echo htmlspecialchars($search); ?> - Qickmed Medical & Health Care</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/shop.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="search-results-page">
        <div class="container py-5">
            <div class="search-header mb-4">
                <h1 class="search-title">
                    <?php if (!empty($search)): ?>
                        Kết quả tìm kiếm cho "<?php echo htmlspecialchars($search); ?>"
                    <?php else: ?>
                        Tất cả sản phẩm
                    <?php endif; ?>
                </h1>
                <p class="search-stats">
                    Tìm thấy <?php echo number_format($totalProducts); ?> sản phẩm
                </p>
            </div>

            <?php if (!empty($products)): ?>
                <div class="row">
                    <?php foreach ($products as $product): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <?php if (!empty($product['image'])): ?>
                                    <img src="/uploads/products/<?php echo htmlspecialchars($product['image']); ?>" 
                                         class="card-img-top" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php endif; ?>
            <?php if (empty($products)): ?>
            <div class="no-results text-center py-5">
                <i class="fas fa-search fa-3x mb-3 text-muted"></i>
                <h2>Không tìm thấy sản phẩm</h2>
                <p class="text-muted">Vui lòng thử lại với từ khóa khác</p>
                <a href="/shop" class="btn btn-primary mt-3">
                    <i class="fas fa-arrow-left me-2"></i>
                    Quay lại cửa hàng
                </a>
            </div>
            <?php else: ?>
            <div class="row g-4">
                <?php foreach ($products as $product): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="product-card">
                        <a href="/shop/product.php?slug=<?php echo $product['slug']; ?>" class="product-link">
                            <div class="product-image">
                                <img src="<?php echo $product['image_url']; ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     class="img-fluid">
                            </div>
                            <div class="product-info">
                                <h3 class="product-title">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </h3>
                                <div class="product-category">
                                    <?php echo htmlspecialchars($product['category_name']); ?>
                                </div>
                                <div class="product-price">
                                    <?php echo number_format($product['price'], 0, ',', '.'); ?>đ
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
            <nav class="pagination-wrapper mt-5">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?q=<?php echo urlencode($search); ?>&page=<?php echo ($page - 1); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?q=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?q=<?php echo urlencode($search); ?>&page=<?php echo ($page + 1); ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 