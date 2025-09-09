-- Thêm dữ liệu mẫu cho products với hình ảnh

-- Cập nhật sản phẩm có sẵn với hình ảnh
UPDATE products SET 
    image_url = '/assets/images/products/vitamin-c.jpg',
    brand = 'Nature Made',
    description = 'Vitamin C 1000mg giúp tăng cường hệ miễn dịch, chống oxy hóa mạnh mẽ'
WHERE name LIKE '%Vitamin C%' LIMIT 1;

UPDATE products SET 
    image_url = '/assets/images/products/paracetamol.jpg',
    brand = 'Traphaco',
    description = 'Thuốc giảm đau, hạ sốt hiệu quả và an toàn'
WHERE name LIKE '%Paracetamol%' LIMIT 1;

UPDATE products SET 
    image_url = '/assets/images/products/omega-3.jpg',
    brand = 'Blackmores',
    description = 'Omega 3 Fish Oil bổ sung DHA, EPA cho tim mạch và não bộ'
WHERE name LIKE '%Omega%' LIMIT 1;

-- Thêm sản phẩm mới nếu chưa có
INSERT IGNORE INTO products (name, description, price, discount_price, stock, category_id, image_url, brand, sku, is_active) VALUES
('Máy đo huyết áp Omron HEM-7120', 'Máy đo huyết áp tự động, chính xác cao, dễ sử dụng', 1250000, 1100000, 15, 1, '/assets/images/products/blood-pressure-monitor.jpg', 'Omron', 'BP-001', 1),
('Nhiệt kế điện tử Microlife', 'Nhiệt kế điện tử đo nhanh, chính xác trong 60 giây', 150000, NULL, 50, 1, '/assets/images/products/thermometer.jpg', 'Microlife', 'TH-001', 1),
('Khẩu trang y tế 3 lớp', 'Khẩu trang y tế kháng khuẩn, lọc bụi mịn hiệu quả', 45000, 35000, 200, 1, '/assets/images/products/face-mask.jpg', 'QickMed', 'FM-001', 1),
('Glucosamine 1500mg', 'Bổ sung glucosamine cho xương khớp chắc khỏe', 580000, 520000, 30, 2, '/assets/images/products/glucosamine.jpg', 'Schiff', 'GL-001', 1),
('Collagen Beauty Plus', 'Collagen thủy phân giúp đẹp da, chống lão hóa', 750000, 680000, 25, 2, '/assets/images/products/collagen.jpg', 'Youtheory', 'CL-001', 1),
('Máy xông mũi họng Omron', 'Máy xông mũi họng siêu âm, điều trị viêm đường hô hấp', 2200000, 1980000, 8, 1, '/assets/images/products/nebulizer.jpg', 'Omron', 'NB-001', 1),
('Dầu cá Omega 3-6-9', 'Bổ sung omega 3-6-9 toàn diện cho sức khỏe tim mạch', 420000, NULL, 40, 2, '/assets/images/products/fish-oil.jpg', 'Nature Made', 'FO-001', 1);

-- Cập nhật một số sản phẩm để có giá giảm
UPDATE products SET discount_price = price * 0.85 WHERE discount_price IS NULL AND RAND() < 0.3;

-- Cập nhật stock ngẫu nhiên
UPDATE products SET stock = FLOOR(RAND() * 100) + 10 WHERE stock < 5; 