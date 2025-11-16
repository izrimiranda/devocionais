<?php
/**
 * API: Gerenciar Inscrições Push
 * Salva/remove subscriptions de notificação
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Habilitar logs de erro para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Capturar conexão do banco
try {
    $pdo = require __DIR__ . '/../config/db.php';
    
    if (!$pdo || !($pdo instanceof PDO)) {
        throw new Exception('Conexão PDO não estabelecida');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro na conexão com banco de dados',
        'error' => $e->getMessage()
    ]);
    exit;
}

// Receber dados
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

if (!$data || !isset($data['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

$action = trim($data['action']);
$subscription = $data['subscription'] ?? null;

if (!$subscription || !isset($subscription['endpoint'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Subscription inválida']);
    exit;
}

try {
    $endpoint = $subscription['endpoint'];
    $p256dh = $subscription['keys']['p256dh'] ?? '';
    $auth = $subscription['keys']['auth'] ?? '';
    
    // Validar formato base64url das keys (devem ter tamanho mínimo)
    if (strlen($p256dh) < 50 || strlen($auth) < 16) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Keys inválidas. Use uma subscription real do navegador.',
            'debug' => [
                'p256dh_length' => strlen($p256dh),
                'auth_length' => strlen($auth),
                'required' => 'p256dh >= 50 chars, auth >= 16 chars'
            ]
        ]);
        exit;
    }
    
    // Identificar usuário (IP como fallback se não houver login)
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ipAddress = getClientIP();
    $userHash = md5($ipAddress . $userAgent); // Identificador único
    
    // Verificar se a tabela existe
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'push_subscriptions'")->fetch();
    if (!$tableCheck) {
        throw new Exception('Tabela push_subscriptions não existe. Execute database/add_push_subscriptions_table.sql');
    }
    
    if ($action === 'subscribe') {
        // Inserir ou atualizar subscription
        $stmt = $pdo->prepare("
            INSERT INTO push_subscriptions (user_hash, endpoint, p256dh, auth_key, user_agent, ip_address, created_at, updated_at)
            VALUES (:user_hash, :endpoint, :p256dh, :auth, :user_agent, :ip, NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
                p256dh = VALUES(p256dh), 
                auth_key = VALUES(auth_key), 
                updated_at = NOW(),
                is_active = 1
        ");
        
        $result = $stmt->execute([
            ':user_hash' => $userHash,
            ':endpoint' => $endpoint,
            ':p256dh' => $p256dh,
            ':auth' => $auth,
            ':user_agent' => $userAgent,
            ':ip' => $ipAddress
        ]);
        
        if (!$result) {
            throw new Exception('Falha ao salvar inscrição');
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Inscrição salva com sucesso',
            'user_hash' => $userHash
        ]);
        
    } elseif ($action === 'unsubscribe') {
        // Desativar subscription
        $stmt = $pdo->prepare("
            UPDATE push_subscriptions 
            SET is_active = 0, updated_at = NOW()
            WHERE endpoint = :endpoint
        ");
        
        $stmt->execute([':endpoint' => $endpoint]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Inscrição removida'
        ]);
        
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ação inválida']);
    }
    
} catch (PDOException $e) {
    $errorMsg = $e->getMessage();
    error_log("Erro push subscription PDO: " . $errorMsg);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao processar inscrição',
        'error' => $errorMsg,
        'code' => $e->getCode(),
        'type' => 'PDOException'
    ]);
} catch (Exception $e) {
    error_log("Erro geral push subscription: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'type' => 'Exception'
    ]);
}

function getClientIP() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}
?>
