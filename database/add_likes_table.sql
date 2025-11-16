-- Tabela para curtidas de devocionais
-- Execute este SQL no banco de dados

CREATE TABLE IF NOT EXISTS devotional_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    devotional_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (devotional_id, ip_address),
    FOREIGN KEY (devotional_id) REFERENCES devotionals(id) ON DELETE CASCADE,
    INDEX idx_devotional (devotional_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
