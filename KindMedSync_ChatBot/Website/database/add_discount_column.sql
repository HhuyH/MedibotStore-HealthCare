-- Thêm cột discount_amount vào bảng products nếu chưa có
-- Chạy file này để cập nhật cấu trúc database

-- Kiểm tra và thêm cột discount_amount
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(16,0) DEFAULT 0 AFTER price;

-- Cập nhật một số sản phẩm có giảm giá để test
UPDATE products SET discount_amount = 10000 WHERE product_id = 1; -- Vitamin C: 320000 - 10000 = 310000
UPDATE products SET discount_amount = 60000 WHERE product_id = 2; -- Omega 3: 580000 - 60000 = 520000  
UPDATE products SET discount_amount = 0 WHERE product_id = 3;     -- Calcium D3: không giảm giá
UPDATE products SET discount_amount = 5000 WHERE product_id = 4;  -- Paracetamol: 25000 - 5000 = 20000
UPDATE products SET discount_amount = 10000 WHERE product_id = 5; -- Amoxicillin: 45000 - 10000 = 35000

-- Kiểm tra kết quả
SELECT 
    product_id,
    name,
    price,
    discount_amount,
    (price - discount_amount) as final_price,
    ROUND(((discount_amount / price) * 100), 0) as discount_percent
FROM products 
WHERE is_active = 1
ORDER BY product_id; 