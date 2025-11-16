-- ============================================
-- Migration: Adicionar campos serie, numero_devocional, ano e renomear excerpt
-- Devocionais - Pr. Luciano Miranda
-- Data: 15/11/2025
-- ============================================

USE u959347836_db_luciano;

-- 1. Renomear coluna excerpt para texto_aureo (se existir)
ALTER TABLE devotionals 
CHANGE COLUMN excerpt texto_aureo VARCHAR(500) NULL COMMENT 'Texto Áureo/Versículo principal';

-- 2. Adicionar novos campos para série
ALTER TABLE devotionals 
ADD COLUMN serie VARCHAR(100) NULL COMMENT 'Nome da série (ex: Vida de Jesus)' AFTER slug,
ADD COLUMN numero_devocional VARCHAR(20) NULL COMMENT 'Número do devocional (ex: 258)' AFTER serie,
ADD COLUMN ano INT NULL COMMENT 'Ano da publicação (ex: 2025)' AFTER numero_devocional;

-- 3. Criar índice para série e ano
ALTER TABLE devotionals 
ADD INDEX idx_serie_ano (serie, ano);

-- 4. Atualizar view para incluir novos campos
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

-- 5. Verificar resultado
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'u959347836_db_luciano'
AND TABLE_NAME = 'devotionals'
AND COLUMN_NAME IN ('texto_aureo', 'serie', 'numero_devocional', 'ano')
ORDER BY ORDINAL_POSITION;

-- ============================================
-- Fim da migração
-- ============================================
