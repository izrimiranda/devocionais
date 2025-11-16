-- Consulta de Visitas por Dia
-- Mostra estatísticas dos últimos 30 dias

-- Visitas por dia
SELECT 
    DATE(visited_at) as dia,
    COUNT(*) as total_visitas,
    COUNT(DISTINCT ip_address) as visitantes_unicos,
    COUNT(DISTINCT session_id) as sessoes_unicas,
    SUM(CASE WHEN device_type = 'mobile' THEN 1 ELSE 0 END) as mobile,
    SUM(CASE WHEN device_type = 'desktop' THEN 1 ELSE 0 END) as desktop,
    SUM(CASE WHEN device_type = 'tablet' THEN 1 ELSE 0 END) as tablet
FROM analytics
WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(visited_at)
ORDER BY dia DESC;

-- Resumo geral
SELECT 
    '=== RESUMO GERAL ===' as info,
    COUNT(*) as total_registros,
    COUNT(DISTINCT ip_address) as ips_unicos,
    MIN(visited_at) as primeiro_registro,
    MAX(visited_at) as ultimo_registro
FROM analytics;

-- Tipos de página mais acessados
SELECT 
    page_type,
    COUNT(*) as acessos
FROM analytics
GROUP BY page_type
ORDER BY acessos DESC;
