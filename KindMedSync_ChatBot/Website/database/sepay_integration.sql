-- SePay Transaction Table
CREATE TABLE IF NOT EXISTS sepay_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(100) UNIQUE,           -- SePay transaction ID
    order_id INT NOT NULL,                        -- Reference to orders table
    gateway VARCHAR(100) NOT NULL DEFAULT 'sepay', -- Payment gateway identifier
    transaction_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    bank_account_number VARCHAR(100),             -- Sender's bank account
    bank_account_name VARCHAR(255),               -- Sender's bank account name
    bank_code VARCHAR(50),                        -- Bank code (e.g., MBBank)
    amount DECIMAL(16, 2) NOT NULL,               -- Transaction amount
    description TEXT,                             -- Transaction description/note
    reference_code VARCHAR(250),                  -- Reference/order code
    status VARCHAR(50) DEFAULT 'pending',         -- Transaction status
    webhook_data JSON,                            -- Raw webhook data from SePay
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
) ENGINE=InnoDB;

-- SePay Payment Configuration
CREATE TABLE IF NOT EXISTS sepay_payment_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    merchant_id VARCHAR(100) NOT NULL,            -- SePay merchant ID
    api_key VARCHAR(255) NOT NULL,               -- SePay API key
    webhook_secret VARCHAR(255),                 -- Webhook secret for verification
    bank_account_number VARCHAR(100) NOT NULL,    -- Your bank account number
    bank_account_name VARCHAR(255) NOT NULL,      -- Your bank account name
    bank_code VARCHAR(50) NOT NULL,              -- Your bank code
    is_active BOOLEAN DEFAULT true,              -- Whether this config is active
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert default payment configuration
INSERT INTO sepay_payment_config (
    merchant_id,
    api_key,
    bank_account_number,
    bank_account_name,
    bank_code
) VALUES (
    'Medibot',           -- Your merchant name
    'HTEOCSEHG6PL8CKFMTW4RWVVTYDDKOHXQDJIAEGZBM6C2PSPJIGZAZMBIIE2S50N', -- Your SePay API key
    '8280111012003',     -- Your MBBank account number
    'DANG VUONG THAI DANG', -- Your account name
    'MBBank'             -- Your bank code
);

-- Add SePay as a payment method option in the orders table if not exists
ALTER TABLE orders MODIFY COLUMN payment_method 
    VARCHAR(50) CHECK (payment_method IN ('COD', 'Momo', 'VNPay', 'SePay', 'Other'));
