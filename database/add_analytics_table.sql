-- Tabela de Analytics
-- Rastreia acessos ao site e devocionais

CREATE TABLE IF NOT EXISTS analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_type ENUM('home', 'devotional', 'search', 'other') NOT NULL DEFAULT 'other',
    devotional_id INT NULL,
    page_url VARCHAR(500) NOT NULL,
    referrer VARCHAR(500) NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    device_type ENUM('desktop', 'mobile', 'tablet') NULL,
    browser VARCHAR(100) NULL,
    os VARCHAR(100) NULL,
    country VARCHAR(100) NULL,
    visited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    session_id VARCHAR(64) NULL,
    INDEX idx_page_type (page_type),
    INDEX idx_devotional_id (devotional_id),
    INDEX idx_visited_at (visited_at),
    INDEX idx_session_id (session_id),
    INDEX idx_ip_date (ip_address, visited_at),
    FOREIGN KEY (devotional_id) REFERENCES devotionals(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índice composto para consultas de analytics
CREATE INDEX idx_analytics_stats ON analytics(page_type, visited_at, devotional_id);
