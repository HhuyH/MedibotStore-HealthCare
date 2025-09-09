-- Thêm cột sku vào bảng products
ALTER TABLE products
ADD COLUMN sku VARCHAR(50) AFTER product_id;

-- Cập nhật SKU cho các sản phẩm hiện có
UPDATE products
SET sku = CONCAT('PRD', LPAD(product_id, 5, '0')); 