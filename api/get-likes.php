<?php
/**
 * API: Obter Curtidas
 * Endpoint para buscar total de curtidas e verificar se usuário curtiu
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';

$devotionalId = (int) ($_GET['devotional_id'] ?? 0);

if (!$devotionalId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID do devocional não informado']);
    exit;
}

try {
    // Total de curtidas
    $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM devotional_likes WHERE devotional_id = ?');
    $stmt->execute([$devotionalId]);
    $totalLikes = (int) $stmt->fetch()['total'];
    
    // Verificar se usuário atual curtiu
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $pdo->prepare('SELECT id FROM devotional_likes WHERE devotional_id = ? AND ip_address = ? LIMIT 1');
    $stmt->execute([$devotionalId, $ipAddress]);
    $userLiked = (bool) $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'total_likes' => $totalLikes,
        'user_liked' => $userLiked
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar curtidas',
        'error' => $e->getMessage()
    ]);
}
