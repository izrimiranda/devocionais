<?php
/**
 * API: Toggle Subscription Status
 * Ativa/desativa uma subscription
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/security.php';

requireLogin();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

$id = (int)$data['id'];
$status = (int)$data['status'];

try {
    $stmt = $pdo->prepare("UPDATE push_subscriptions SET is_active = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Status atualizado'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao atualizar'
    ]);
}
?>
