<?php
/**
 * API: Enviar Push Notifications
 * Envia notificação para todos os inscritos
 * USO: Chamado automaticamente quando novo devocional é criado
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

// Bibliotecas necessárias: web-push
// Install: composer require minishlink/web-push
require_once __DIR__ . '/../vendor/autoload.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

/**
 * Enviar notificação para todos os inscritos
 */
function sendPushNotification($devotionalId, $title, $slug) {
    global $pdo;
    
    try {
        // Verificar se tabela existe
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'push_subscriptions'")->fetch();
        if (!$tableCheck) {
            throw new Exception('Tabela push_subscriptions não existe');
        }
        
        // VAPID keys (geradas automaticamente)
        $auth = [
            'VAPID' => [
                'subject' => 'mailto:contato@pastorluciano.com.br',
                'publicKey' => 'BOYXEbV0gz0T4x0JM56sqEfsnr-_YDPsTvVdgz7syHHW3PgpkfD2AsJ85xa5UCuG4llS7BQm5_NLXhODRm4zdaY',
                'privateKey' => '9PT5NV4S3bAc-sXfvJ6DRxiq2YCEakTARRSwOv9CO04'
            ]
        ];
        
        // Inicializar WebPush
        $webPush = new WebPush($auth);
        
        // Configurar opções
        $webPush->setAutomaticPadding(true);
        $defaultOptions = [
            'TTL' => 300, // 5 minutos
            'urgency' => 'normal',
            'topic' => 'devocional'
        ];
        
        // Buscar todas as subscriptions ativas
        $stmt = $pdo->query("
            SELECT endpoint, p256dh, auth_key 
            FROM push_subscriptions 
            WHERE is_active = 1
        ");
        
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($subscriptions)) {
            return [
                'success' => true,
                'sent' => 0,
                'failed' => 0,
                'total' => 0,
                'message' => 'Nenhum inscrito ativo encontrado'
            ];
        }
        
        // Preparar payload da notificação
        $payload = json_encode([
            'title' => '📖 Novo Devocional',
            'body' => $title,
            'icon' => SITE_URL . '/assets/images/icon-192.png',
            'badge' => SITE_URL . '/assets/images/badge.png',
            'tag' => 'devocional-' . $devotionalId,
            'url' => SITE_URL . '/devocionais/' . $slug . '.php',
            'data' => [
                'devotional_id' => $devotionalId,
                'timestamp' => time()
            ]
        ]);
        
        // Enviar para cada subscription
        $sent = 0;
        $failed = 0;
        $errors = [];
        
        foreach ($subscriptions as $sub) {
            try {
                // Validar dados da subscription
                if (empty($sub['endpoint']) || empty($sub['p256dh']) || empty($sub['auth_key'])) {
                    $failed++;
                    $errors[] = 'Subscription com dados incompletos';
                    continue;
                }
                
                $subscription = Subscription::create([
                    'endpoint' => $sub['endpoint'],
                    'keys' => [
                        'p256dh' => $sub['p256dh'],
                        'auth' => $sub['auth_key']
                    ]
                ]);
                
                // Debug: log subscription details
                error_log("Queueing notification for endpoint: " . substr($sub['endpoint'], 0, 50));
                
                $webPush->queueNotification($subscription, $payload, $defaultOptions);
                
            } catch (Exception $e) {
                $failed++;
                $errors[] = $e->getMessage();
                error_log("Erro ao processar subscription: " . $e->getMessage());
            }
        }
        
        // Enviar em lote
        $results = $webPush->flush();
        
        // Processar resultados
        foreach ($results as $result) {
            if ($result->isSuccess()) {
                $sent++;
            } else {
                $failed++;
                
                // Log detalhado do erro
                $errorMsg = 'Push failed - ';
                $response = $result->getResponse();
                
                if ($response) {
                    $statusCode = $response->getStatusCode();
                    $errorMsg .= "Status: $statusCode";
                    
                    // Se endpoint expirado, desativar
                    if ($statusCode === 410) {
                        $expiredEndpoint = $result->getRequest()->getUri()->__toString();
                        $pdo->prepare("UPDATE push_subscriptions SET is_active = 0 WHERE endpoint = ?")
                            ->execute([$expiredEndpoint]);
                        $errorMsg .= ' (endpoint expired and deactivated)';
                    }
                    
                    // Tentar pegar corpo da resposta
                    try {
                        $body = $response->getBody()->getContents();
                        $errorMsg .= " - Body: " . substr($body, 0, 200);
                    } catch (Exception $e) {
                        // Ignorar se não conseguir ler corpo
                    }
                } else {
                    $errorMsg .= 'No response object';
                }
                
                $errors[] = $errorMsg;
                error_log("Push notification error: " . $errorMsg);
            }
        }
        
        // Log do envio
        error_log("Push notifications enviadas: $sent sucesso, $failed falhas");
        
        // Salvar log no banco (se tabela existir)
        try {
            $logStmt = $pdo->prepare("
                INSERT INTO push_notifications_log (devotional_id, total_sent, total_failed, payload, sent_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $logStmt->execute([$devotionalId, $sent, $failed, $payload]);
        } catch (PDOException $e) {
            // Ignorar se tabela de log não existir
            error_log("Erro ao salvar log (tabela pode não existir): " . $e->getMessage());
        }
        
        return [
            'success' => true,
            'sent' => $sent,
            'failed' => $failed,
            'total' => count($subscriptions),
            'message' => "Enviadas: $sent, Falhas: $failed",
            'errors' => !empty($errors) ? $errors : null
        ];
        
    } catch (Exception $e) {
        error_log("Erro ao enviar push: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
    }
}

// Se chamado diretamente (para testes)
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    // Handle preflight
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
    
    // Verificar se é POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false, 
            'error' => 'Método não permitido. Use POST'
        ]);
        exit;
    }
    
    // Receber dados
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);
    
    // Debug
    if ($data === null) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON',
            'raw' => substr($rawData, 0, 100)
        ]);
        exit;
    }
    
    // Validar dados
    $devotionalId = isset($data['devotional_id']) ? (int)$data['devotional_id'] : null;
    $title = isset($data['title']) ? trim($data['title']) : 'Novo Devocional';
    $slug = isset($data['slug']) ? trim($data['slug']) : 'teste-notificacao';
    
    // Se não tiver devotional_id, usar valores padrão para teste
    if (!$devotionalId) {
        $devotionalId = 999; // ID de teste
        $title = $title ?: 'Teste de Notificação Push';
        $slug = $slug ?: 'teste-notificacao';
    }
    
    // Garantir que slug não está vazio
    if (empty($slug)) {
        $slug = 'devocional-' . $devotionalId;
    }
    
    try {
        $result = sendPushNotification($devotionalId, $title, $slug);
        
        // Adicionar informações extras para debug
        $result['debug'] = [
            'devotional_id' => $devotionalId,
            'title' => $title,
            'slug' => $slug,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        http_response_code(200);
        echo json_encode($result);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}
?>
