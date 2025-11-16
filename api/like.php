<?php
/**
 * API: Curtir/Descurtir Devocional
 * Endpoint para adicionar ou remover curtida
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';

// Apenas POST permitido
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Obter dados
$input = json_decode(file_get_contents('php://input'), true);
$devotionalId = (int) ($input['devotional_id'] ?? 0);
$action = $input['action'] ?? 'toggle'; // toggle, like, unlike

if (!$devotionalId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID do devocional não informado']);
    exit;
}

// Verificar se devocional existe
$stmt = $pdo->prepare('SELECT id FROM devotionals WHERE id = ? LIMIT 1');
$stmt->execute([$devotionalId]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Devocional não encontrado']);
    exit;
}

// Identificar usuário (IP + User-Agent)
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

try {
    // Verificar se já curtiu
    $stmt = $pdo->prepare('SELECT id FROM devotional_likes WHERE devotional_id = ? AND ip_address = ? LIMIT 1');
    $stmt->execute([$devotionalId, $ipAddress]);
    $alreadyLiked = $stmt->fetch();
    
    if ($action === 'toggle') {
        if ($alreadyLiked) {
            // Descurtir
            $stmt = $pdo->prepare('DELETE FROM devotional_likes WHERE devotional_id = ? AND ip_address = ?');
            $stmt->execute([$devotionalId, $ipAddress]);
            $liked = false;
            $message = 'Curtida removida';
        } else {
            // Curtir
            $stmt = $pdo->prepare('INSERT INTO devotional_likes (devotional_id, ip_address, user_agent) VALUES (?, ?, ?)');
            $stmt->execute([$devotionalId, $ipAddress, $userAgent]);
            $liked = true;
            $message = 'Curtida adicionada';
        }
    } elseif ($action === 'like' && !$alreadyLiked) {
        $stmt = $pdo->prepare('INSERT INTO devotional_likes (devotional_id, ip_address, user_agent) VALUES (?, ?, ?)');
        $stmt->execute([$devotionalId, $ipAddress, $userAgent]);
        $liked = true;
        $message = 'Curtida adicionada';
    } elseif ($action === 'unlike' && $alreadyLiked) {
        $stmt = $pdo->prepare('DELETE FROM devotional_likes WHERE devotional_id = ? AND ip_address = ?');
        $stmt->execute([$devotionalId, $ipAddress]);
        $liked = false;
        $message = 'Curtida removida';
    } else {
        $liked = (bool) $alreadyLiked;
        $message = 'Nenhuma ação realizada';
    }
    
    // Obter total de curtidas
    $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM devotional_likes WHERE devotional_id = ?');
    $stmt->execute([$devotionalId]);
    $totalLikes = (int) $stmt->fetch()['total'];
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'liked' => $liked,
        'total_likes' => $totalLikes
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao processar curtida',
        'error' => $e->getMessage()
    ]);
}
