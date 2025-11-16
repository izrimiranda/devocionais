-- ============================================
-- Database Structure for Devocionais System
-- Pr. Luciano Miranda
-- ============================================

-- Criar banco de dados (caso não exista)
CREATE DATABASE IF NOT EXISTS u959347836_db_luciano 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE u959347836_db_luciano;

-- ============================================
-- Tabela: admin_users
-- Armazena usuários administrativos
-- ============================================
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login DATETIME NULL,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabela: devotionals
-- Armazena os devocionais publicados
-- ============================================
CREATE TABLE IF NOT EXISTS devotionals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    serie VARCHAR(100) NULL COMMENT 'Nome da série (ex: Vida de Jesus)',
    numero_devocional VARCHAR(20) NULL COMMENT 'Número do devocional (ex: 258)',
    ano INT NULL COMMENT 'Ano da publicação (ex: 2025)',
    texto_aureo VARCHAR(500) NULL COMMENT 'Texto Áureo/Versículo principal',
    content_html MEDIUMTEXT NOT NULL,
    image_path VARCHAR(255) NULL,
    audio_path VARCHAR(255) NULL,
    published_at DATETIME NOT NULL,
    status ENUM('draft', 'published') DEFAULT 'published',
    views INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NULL,
    INDEX idx_published_at (published_at),
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_status_published (status, published_at),
    INDEX idx_serie_ano (serie, ano),
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabela: login_attempts
-- Controle de rate limiting para login
-- ============================================
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time DATETIME NOT NULL,
    INDEX idx_ip_time (ip_address, attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabela: devotional_views (opcional - analytics)
-- Registra visualizações dos devocionais
-- ============================================
CREATE TABLE IF NOT EXISTS devotional_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    devotional_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NULL,
    viewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_devotional_id (devotional_id),
    INDEX idx_viewed_at (viewed_at),
    FOREIGN KEY (devotional_id) REFERENCES devotionals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Inserir usuário admin padrão
-- Username: admin
-- Password: admin123 (ALTERAR APÓS PRIMEIRO LOGIN!)
-- ============================================
INSERT INTO admin_users (username, password_hash, email) 
VALUES (
    'admin', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- senha: admin123
    'admin@example.com'
) ON DUPLICATE KEY UPDATE username=username;

-- ============================================
-- Devocional de exemplo (opcional)
-- ============================================
INSERT INTO devotionals (
    title, 
    slug, 
    serie,
    numero_devocional,
    ano,
    texto_aureo, 
    content_html, 
    published_at, 
    status
) VALUES (
    'Bem-vindo aos Devocionais',
    'bem-vindo',
    'Vida de Jesus',
    '1',
    2025,
    '"E indagou aos seus discípulos: Por que sois covardes? Ainda não tendes fé?" (Marcos 4:40)',
    '<p>Olá! Este é um devocional de exemplo para demonstrar o sistema.</p><p>Para começar a usar:</p><ul><li>Acesse /admin/login.php</li><li>Use: <strong>admin</strong> / <strong>admin123</strong></li><li>Altere a senha após o primeiro login</li><li>Crie seus devocionais com texto, imagem e áudio</li></ul><p><strong>Que Deus abençoe!</strong></p>',
    NOW(),
    'published'
) ON DUPLICATE KEY UPDATE slug=slug;

-- ============================================
-- Views úteis
-- ============================================

-- View: Devocionais publicados mais recentes
CREATE OR REPLACE VIEW v_recent_devotionals AS
SELECT 
    id,
    title,
    slug,
    serie,
    numero_devocional,
    ano,
    texto_aureo,
    image_path,
    audio_path,
    published_at,
    views
FROM devotionals
WHERE status = 'published'
ORDER BY published_at DESC;

-- View: Estatísticas do admin
CREATE OR REPLACE VIEW v_admin_stats AS
SELECT
    (SELECT COUNT(*) FROM devotionals WHERE status = 'published') as total_published,
    (SELECT COUNT(*) FROM devotionals WHERE status = 'draft') as total_drafts,
    (SELECT COUNT(*) FROM devotionals) as total_devotionals,
    (SELECT SUM(views) FROM devotionals) as total_views,
    (SELECT COUNT(DISTINCT DATE(viewed_at)) FROM devotional_views) as days_with_views;

-- ============================================
-- Triggers
-- ============================================

-- Trigger: Atualizar contador de views
DELIMITER $$
CREATE TRIGGER IF NOT EXISTS update_devotional_views
AFTER INSERT ON devotional_views
FOR EACH ROW
BEGIN
    UPDATE devotionals 
    SET views = views + 1 
    WHERE id = NEW.devotional_id;
END$$
DELIMITER ;

-- ============================================
-- Limpeza automática de login_attempts antigos
-- (Executar via CRON diariamente)
-- ============================================
-- DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 7 DAY);

-- ============================================
-- Fim do script
-- ============================================
