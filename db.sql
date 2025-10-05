-- Create database
CREATE DATABASE IF NOT EXISTS sales_prediction;
USE sales_prediction;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1
);

-- Create sales_data table
CREATE TABLE IF NOT EXISTS sales_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    date DATE NOT NULL,
    item_sales INT DEFAULT 0,
    void INT DEFAULT 0,
    discount_bill INT DEFAULT 0,
    discount_item INT DEFAULT 0,
    amount_redeem INT DEFAULT 0,
    net_sales INT DEFAULT 0,
    gross_sales INT DEFAULT 0,
    pembayaran_dp INT DEFAULT 0,
    omset INT DEFAULT 0,
    average_sales INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX idx_username ON users(username);
CREATE INDEX idx_email ON users(email);
CREATE INDEX idx_date ON sales_data(date);
CREATE INDEX idx_omset ON sales_data(omset);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, email, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'Administrator', 'admin');

-- Sample data for testing sales_data (optional)
INSERT INTO sales_data (date, item_sales, void, discount_bill, discount_item, amount_redeem, net_sales, gross_sales, pembayaran_dp, omset, average_sales) VALUES
('2024-01-01', 150, 5, 10000, 5000, 2000, 280000, 300000, 50000, 320000, 2133),
('2024-01-02', 120, 3, 8000, 4000, 1500, 220000, 240000, 40000, 250000, 2083),
('2024-01-03', 180, 7, 12000, 6000, 2500, 340000, 360000, 60000, 380000, 2111),
('2024-01-04', 200, 8, 15000, 7000, 3000, 380000, 400000, 70000, 420000, 2100),
('2024-01-05', 160, 6, 11000, 5500, 2200, 300000, 320000, 55000, 340000, 2125),
('2024-01-06', 140, 4, 9000, 4500, 1800, 260000, 280000, 45000, 290000, 2071),
('2024-01-07', 190, 9, 13000, 6500, 2800, 360000, 380000, 65000, 400000, 2105),
('2024-01-08', 170, 5, 10500, 5200, 2100, 320000, 340000, 58000, 360000, 2118),
('2024-01-09', 130, 3, 8500, 4200, 1700, 240000, 260000, 42000, 270000, 2077),
('2024-01-10', 210, 10, 14000, 7200, 3200, 400000, 420000, 72000, 440000, 2095);