<?php
/**
 * API: Track Analytics
 * Registra visitas ao site
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Permitir CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// Validar dados
$pageType = $input['page_type'] ?? 'other';
$devotionalId = $input['devotional_id'] ?? null;
$pageUrl = $input['page_url'] ?? $_SERVER['REQUEST_URI'] ?? '';
$referrer = $_SERVER['HTTP_REFERER'] ?? null;

// Detectar informações do usuário
$ipAddress = getClientIP();
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$deviceType = detectDeviceType($userAgent);
$browser = detectBrowser($userAgent);
$os = detectOS($userAgent);

// Session ID (cookie ou gerado)
$sessionId = $_COOKIE['analytics_session'] ?? null;
if (!$sessionId) {
    $sessionId = generateSessionId();
    setcookie('analytics_session', $sessionId, time() + (86400 * 30), '/'); // 30 dias
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO analytics (
            page_type, devotional_id, page_url, referrer, 
            ip_address, user_agent, device_type, browser, os, session_id
        ) VALUES (
            :page_type, :devotional_id, :page_url, :referrer,
            :ip_address, :user_agent, :device_type, :browser, :os, :session_id
        )
    ");
    
    $stmt->execute([
        'page_type' => $pageType,
        'devotional_id' => $devotionalId,
        'page_url' => substr($pageUrl, 0, 500),
        'referrer' => $referrer ? substr($referrer, 0, 500) : null,
        'ip_address' => $ipAddress,
        'user_agent' => substr($userAgent, 0, 1000),
        'device_type' => $deviceType,
        'browser' => substr($browser, 0, 100),
        'os' => substr($os, 0, 100),
        'session_id' => $sessionId
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Visita registrada',
        'session_id' => $sessionId
    ]);
    
} catch (PDOException $e) {
    error_log("Erro ao registrar analytics: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao registrar visita'
    ]);
}

/**
 * Obter IP real do cliente
 */
function getClientIP() {
    $ipKeys = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];
    
    foreach ($ipKeys as $key) {
        if (isset($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);
            
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Detectar tipo de dispositivo
 */
function detectDeviceType($userAgent) {
    if (preg_match('/tablet|ipad|playbook|silk/i', $userAgent)) {
        return 'tablet';
    }
    if (preg_match('/mobile|android|iphone|ipod|blackberry|windows phone/i', $userAgent)) {
        return 'mobile';
    }
    return 'desktop';
}

/**
 * Detectar navegador
 */
function detectBrowser($userAgent) {
    $browsers = [
        'Edge' => '/Edge\/([0-9.]+)/',
        'Chrome' => '/Chrome\/([0-9.]+)/',
        'Firefox' => '/Firefox\/([0-9.]+)/',
        'Safari' => '/Safari\/([0-9.]+)/',
        'Opera' => '/Opera\/([0-9.]+)/',
        'IE' => '/MSIE ([0-9.]+)/',
    ];
    
    foreach ($browsers as $name => $pattern) {
        if (preg_match($pattern, $userAgent, $matches)) {
            return $name . ' ' . ($matches[1] ?? '');
        }
    }
    
    return 'Unknown';
}

/**
 * Detectar sistema operacional
 */
function detectOS($userAgent) {
    $osList = [
        'Windows 10' => '/Windows NT 10.0/',
        'Windows 8.1' => '/Windows NT 6.3/',
        'Windows 8' => '/Windows NT 6.2/',
        'Windows 7' => '/Windows NT 6.1/',
        'Mac OS X' => '/Mac OS X ([0-9._]+)/',
        'iPhone' => '/iPhone OS ([0-9._]+)/',
        'iPad' => '/iPad.*OS ([0-9._]+)/',
        'Android' => '/Android ([0-9.]+)/',
        'Linux' => '/Linux/',
    ];
    
    foreach ($osList as $name => $pattern) {
        if (preg_match($pattern, $userAgent, $matches)) {
            $version = isset($matches[1]) ? ' ' . str_replace('_', '.', $matches[1]) : '';
            return $name . $version;
        }
    }
    
    return 'Unknown';
}

/**
 * Gerar Session ID único
 */
function generateSessionId() {
    return hash('sha256', uniqid('', true) . random_bytes(16));
}
