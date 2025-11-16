<?php
/**
 * API: Get Analytics Stats
 * Retorna estatísticas de acessos
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin();

header('Content-Type: application/json');

$period = $_GET['period'] ?? '7days'; // 7days, 30days, 90days, all
$type = $_GET['type'] ?? 'overview'; // overview, devotionals, pages, devices

try {
    $stats = [];
    
    // Definir período
    $dateCondition = getDateCondition($period);
    
    switch ($type) {
        case 'overview':
            $stats = getOverviewStats($pdo, $dateCondition);
            break;
            
        case 'devotionals':
            $stats = getDevotionalStats($pdo, $dateCondition);
            break;
            
        case 'pages':
            $stats = getPageStats($pdo, $dateCondition);
            break;
            
        case 'devices':
            $stats = getDeviceStats($pdo, $dateCondition);
            break;
            
        default:
            $stats = ['error' => 'Tipo inválido'];
    }
    
    echo json_encode([
        'success' => true,
        'period' => $period,
        'stats' => $stats
    ]);
    
} catch (PDOException $e) {
    error_log("Erro ao obter analytics: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao obter estatísticas'
    ]);
}

/**
 * Condição de data baseada no período
 */
function getDateCondition($period) {
    switch ($period) {
        case '7days':
            return "visited_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        case '30days':
            return "visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        case '90days':
            return "visited_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
        case 'all':
        default:
            return "1=1";
    }
}

/**
 * Estatísticas gerais
 */
function getOverviewStats($pdo, $dateCondition) {
    // Total de visitas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM analytics WHERE $dateCondition");
    $totalVisits = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Visitantes únicos (IPs únicos)
    $stmt = $pdo->query("SELECT COUNT(DISTINCT ip_address) as total FROM analytics WHERE $dateCondition");
    $uniqueVisitors = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Visitas por dia (últimos 30 dias)
    $stmt = $pdo->query("
        SELECT 
            DATE(visited_at) as date,
            COUNT(*) as visits
        FROM analytics 
        WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(visited_at)
        ORDER BY date ASC
    ");
    $dailyVisits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Visitas por tipo de dispositivo
    $stmt = $pdo->query("
        SELECT 
            device_type,
            COUNT(*) as visits
        FROM analytics 
        WHERE $dateCondition
        GROUP BY device_type
        ORDER BY visits DESC
    ");
    $deviceTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Páginas mais visitadas
    $stmt = $pdo->query("
        SELECT 
            page_type,
            COUNT(*) as visits
        FROM analytics 
        WHERE $dateCondition
        GROUP BY page_type
        ORDER BY visits DESC
    ");
    $pageTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'total_visits' => (int)$totalVisits,
        'unique_visitors' => (int)$uniqueVisitors,
        'daily_visits' => $dailyVisits,
        'device_types' => $deviceTypes,
        'page_types' => $pageTypes
    ];
}

/**
 * Estatísticas de devocionais
 */
function getDevotionalStats($pdo, $dateCondition) {
    $stmt = $pdo->query("
        SELECT 
            d.id,
            d.title,
            d.slug,
            COUNT(a.id) as visits,
            COUNT(DISTINCT a.ip_address) as unique_visitors
        FROM devotionals d
        LEFT JOIN analytics a ON d.id = a.devotional_id AND $dateCondition
        WHERE d.published_at IS NOT NULL
        GROUP BY d.id
        ORDER BY visits DESC
        LIMIT 20
    ");
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Estatísticas de páginas
 */
function getPageStats($pdo, $dateCondition) {
    $stmt = $pdo->query("
        SELECT 
            page_url,
            COUNT(*) as visits,
            COUNT(DISTINCT ip_address) as unique_visitors
        FROM analytics 
        WHERE $dateCondition
        GROUP BY page_url
        ORDER BY visits DESC
        LIMIT 30
    ");
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Estatísticas de dispositivos
 */
function getDeviceStats($pdo, $dateCondition) {
    // Por tipo de dispositivo
    $stmt = $pdo->query("
        SELECT 
            device_type,
            COUNT(*) as visits
        FROM analytics 
        WHERE $dateCondition
        GROUP BY device_type
        ORDER BY visits DESC
    ");
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Por navegador
    $stmt = $pdo->query("
        SELECT 
            browser,
            COUNT(*) as visits
        FROM analytics 
        WHERE $dateCondition
        GROUP BY browser
        ORDER BY visits DESC
        LIMIT 10
    ");
    $browsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Por sistema operacional
    $stmt = $pdo->query("
        SELECT 
            os,
            COUNT(*) as visits
        FROM analytics 
        WHERE $dateCondition
        GROUP BY os
        ORDER BY visits DESC
        LIMIT 10
    ");
    $systems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'devices' => $devices,
        'browsers' => $browsers,
        'systems' => $systems
    ];
}
