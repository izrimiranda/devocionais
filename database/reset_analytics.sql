-- Reset Analytics Data
-- Este script limpa todos os dados de analytics mantendo a estrutura da tabela

-- Limpar tabela de analytics
TRUNCATE TABLE analytics;

-- Verificar resultado
SELECT 
    'Analytics resetado com sucesso!' as status,
    COUNT(*) as total_registros_restantes 
FROM analytics;

-- Resetar AUTO_INCREMENT (opcional)
ALTER TABLE analytics AUTO_INCREMENT = 1;
