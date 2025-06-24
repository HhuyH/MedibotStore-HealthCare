<?php
session_start();
require_once 'includes/db.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dịch vụ - Qickmed Medical & Health Care</title>
    <meta name="description" content="Khám phá các dịch vụ y tế chất lượng cao tại Qickmed - từ khám tổng quát đến chuyên khoa, với trang thiết bị hiện đại và bác sĩ chuyên nghiệp.">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/services.css">
</head>
<body>
    <?php include 'includes/header.php';

    // add services
    $sql = "SELECT 
            c.id as category_id,
            c.name as category_name,
            c.icon as category_icon,
            c.description as category_description,
            s.id as service_id,
            s.name as service_name,
            s.short_description,
            s.price_from,
            s.price_to,
            s.is_featured,
            s.is_emergency,
            GROUP_CONCAT(sf.feature_name) as features
            FROM service_categories c
            LEFT JOIN services s ON s.category_id = c.id
            LEFT JOIN service_features sf ON sf.service_id = s.id
            WHERE c.is_active = 1
            GROUP BY s.id
            ORDER BY c.display_order, s.display_order";
    
    $result = $conn->query($sql);
    $services = array();
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $categoryId = $row['category_id'];
            if (!isset($services[$categoryId])) {
                $services[$categoryId] = array(
                    'category' => array(
                        'name' => $row['category_name'],
                        'icon' => $row['category_icon'],
                        'description' => $row['category_description']
                    ),
                    'items' => array()
                );
            }
            if ($row['service_id']) {
                // Format price range
                $priceRange = 'Liên hệ';
                if ($row['price_from'] !== null) {
                    $priceRange = number_format($row['price_from'], 0, ',', '.') . 'đ';
                    if ($row['price_to'] !== null) {
                        $priceRange .= ' - ' . number_format($row['price_to'], 0, ',', '.') . 'đ';
                    }
                }

                // Convert features string to array
                $features = $row['features'] ? explode(',', $row['features']) : [];

                $services[$categoryId]['items'][] = array(
                    'name' => $row['service_name'],
                    'description' => $row['short_description'],
                    'features' => $features,
                    'price_range' => $priceRange,
                    'is_featured' => $row['is_featured'],
                    'is_emergency' => $row['is_emergency']
                );
            }
        }
    }
    ?>

    <main>
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8 mx-auto text-center">
                        <div class="hero-content">
                            <h1 class="hero-title">Chăm Sóc Sức Khỏe <span>Chuyên Nghiệp</span></h1>
                            <p class="hero-subtitle">
                                Với đội ngũ y bác sĩ giàu kinh nghiệm và trang thiết bị hiện đại, 
                                chúng tôi cam kết mang đến dịch vụ y tế chất lượng cao nhất cho bạn và gia đình
                            </p>
                            <div class="hero-buttons">
                                <a href="#services" class="btn btn-primary">
                                    <i class="fas fa-stethoscope me-2"></i>Khám phá dịch vụ
                                </a>
                                <a href="#contact" class="btn btn-outline-primary">
                                    <i class="fas fa-calendar-alt me-2"></i>Đặt lịch ngay
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Grid -->
        <section id="services" class="services-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Dịch Vụ Y Tế Toàn Diện</h2>
                    <p class="section-description">
                        Chúng tôi cung cấp đa dạng các dịch vụ y tế chất lượng cao, 
                        đáp ứng mọi nhu cầu chăm sóc sức khỏe của bạn
                    </p>
                </div>
                <div class="row g-4">
                    <?php foreach ($services as $categoryId => $categoryData): ?>
                        <?php foreach ($categoryData['items'] as $service): ?>
                            <div class="col-lg-4 col-md-6">
                                <div class="service-card">
                                    <div class="service-icon">
                                        <i class="<?php echo htmlspecialchars($categoryData['category']['icon']); ?>"></i>
                                    </div>
                                    <h3 class="service-title"><?php echo htmlspecialchars($service['name']); ?></h3>
                                    <p class="service-description"><?php echo htmlspecialchars($service['description']); ?></p>
                                    <?php if (!empty($service['features'])): ?>
                                        <ul class="service-features">
                                            <?php foreach ($service['features'] as $feature): ?>
                                                <li>
                                                    <span class="feature-check">
                                                        <i class="fas fa-check"></i>
                                                    </span>
                                                    <?php echo htmlspecialchars($feature); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                    <div class="service-price">
                                        <i class="fas fa-tag"></i>
                                        <?php echo htmlspecialchars($service['price_range']); ?>
                                    </div>
                                    <a href="#" class="btn-book">
                                        <i class="fas fa-calendar-check me-2"></i>
                                        Đặt lịch ngay
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Tại Sao Chọn Qickmed?</h2>
                    <p class="section-description">
                        Chúng tôi cam kết mang đến trải nghiệm y tế tốt nhất với những ưu điểm vượt trội
                    </p>
                </div>
                <div class="row g-4">
                    <div class="col-lg-3 col-md-6">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-user-md"></i>
                            </div>
                            <h3 class="feature-title">Đội Ngũ Chuyên Gia</h3>
                            <p class="feature-description">Bác sĩ giàu kinh nghiệm, được đào tạo bài bản tại các bệnh viện hàng đầu</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-microscope"></i>
                            </div>
                            <h3 class="feature-title">Công Nghệ Hiện Đại</h3>
                            <p class="feature-description">Trang thiết bị y tế tiên tiến được nhập khẩu từ các nước phát triển</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h3 class="feature-title">Phục Vụ 24/7</h3>
                            <p class="feature-description">Sẵn sàng phục vụ mọi lúc với dịch vụ cấp cứu và chăm sóc khẩn cấp</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h3 class="feature-title">An Toàn Tuyệt Đối</h3>
                            <p class="feature-description">Tuân thủ nghiêm ngặt các quy trình an toàn y tế theo tiêu chuẩn quốc tế</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Packages Section -->
        <section class="services-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Gói Khám Sức Khỏe</h2>
                    <p class="section-description">
                        Các gói khám sức khỏe toàn diện được thiết kế phù hợp với từng độ tuổi
                    </p>
                </div>

                <div class="row g-4">
                    <?php
                    // Query to get all active packages
                    $sql = "SELECT * FROM service_packages WHERE is_active = 1 ORDER BY display_order, price";
                    $result = $conn->query($sql);

                    while ($package = $result->fetch_assoc()) {
                        // Get features for this package
                        $features_sql = "SELECT * FROM package_features WHERE package_id = ? ORDER BY display_order";
                        $stmt = $conn->prepare($features_sql);
                        $stmt->bind_param("i", $package['id']);
                        $stmt->execute();
                        $features = $stmt->get_result();
                    ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="package-card <?php echo $package['is_featured'] ? 'popular' : ''; ?>">
                                <?php if ($package['is_featured']) : ?>
                                    <div class="popular-badge">Phổ biến</div>
                                <?php endif; ?>
                                
                                <div class="package-header">
                                    <h3 class="package-title"><?php echo htmlspecialchars($package['name']); ?></h3>
                                    <div class="package-price">
                                        <?php echo number_format($package['price'], 0, ',', '.'); ?>đ
                                        <span><?php echo htmlspecialchars($package['duration']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="package-body">
                                    <?php if ($package['description']) : ?>
                                        <p class="package-description"><?php echo htmlspecialchars($package['description']); ?></p>
                                    <?php endif; ?>
                                    
                                    <ul class="package-features">
                                        <?php while ($feature = $features->fetch_assoc()) : ?>
                                            <li>
                                                <i class="fas fa-check-circle"></i>
                                                <?php echo htmlspecialchars($feature['feature_name']); ?>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                    
                                    <a href="booking.php?package=<?php echo $package['slug']; ?>" class="btn-book">
                                        <i class="fas fa-calendar-check"></i>
                                        Đặt lịch ngay
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php
                    }
                    ?>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="container">
                <div class="cta-content">
                    <h2 class="cta-title">Sẵn sàng chăm sóc sức khỏe của bạn</h2>
                    <p class="cta-description">
                        Đặt lịch hẹn ngay hôm nay để được tư vấn và khám bệnh với đội ngũ y bác sĩ chuyên nghiệp
                    </p>
                    <div class="cta-buttons">
                        <a href="tel:0123456789" class="btn btn-primary">
                            <i class="fas fa-phone me-2"></i>Gọi ngay: 0123 456 789
                        </a>
                        <a href="#" class="btn btn-outline-primary">
                            <i class="fas fa-calendar-alt me-2"></i>Đặt lịch online
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php 
    $conn->close(); // Close database connection
    include 'includes/footer.php'; 
    ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS Animation -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <!-- Global Enhancements -->
    <script src="assets/js/global-enhancements.js"></script>
    <!-- Custom JS -->
    <script src="/assets/js/services.js"></script>
</body>
</html> 