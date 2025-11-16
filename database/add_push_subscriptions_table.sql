-- Tabela para armazenar inscrições de notificação push
CREATE TABLE IF NOT EXISTS push_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_hash VARCHAR(64) NOT NULL COMMENT 'Hash único do usuário (IP+UserAgent)',
    endpoint VARCHAR(500) NOT NULL UNIQUE COMMENT 'URL do endpoint de push',
    p256dh VARCHAR(255) NOT NULL COMMENT 'Chave pública de criptografia',
    auth_key VARCHAR(255) NOT NULL COMMENT 'Chave de autenticação',
    user_agent TEXT NULL COMMENT 'User agent do navegador',
    ip_address VARCHAR(45) NULL COMMENT 'IP do usuário',
    is_active TINYINT(1) DEFAULT 1 COMMENT '1 = ativo, 0 = inativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user_hash (user_hash),
    INDEX idx_active (is_active),
    INDEX idx_endpoint (endpoint(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para log de notificações enviadas
CREATE TABLE IF NOT EXISTS push_notifications_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    devotional_id INT NOT NULL,
    total_sent INT DEFAULT 0,
    total_failed INT DEFAULT 0,
    payload TEXT NULL COMMENT 'JSON do payload enviado',
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (devotional_id) REFERENCES devotionals(id) ON DELETE CASCADE,
    INDEX idx_devotional (devotional_id),
    INDEX idx_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
