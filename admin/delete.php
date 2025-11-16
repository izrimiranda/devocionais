<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
requireLogin();

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare('SELECT * FROM devotionals WHERE id = ?');
$stmt->execute([$id]);
$dev = $stmt->fetch();

if (!$dev) {
    redirectWithMessage('dashboard.php', 'Devocional não encontrado.', 'error');
}

// Deletar do banco
$stmt = $pdo->prepare('DELETE FROM devotionals WHERE id = ?');
if ($stmt->execute([$id])) {
    // Deletar arquivo físico
    $filePath = __DIR__ . '/../devocionais/' . $dev['slug'] . '.php';
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    // Deletar uploads
    if ($dev['image_path'] && file_exists(__DIR__ . '/../' . $dev['image_path'])) {
        unlink(__DIR__ . '/../' . $dev['image_path']);
    }
    if ($dev['audio_path'] && file_exists(__DIR__ . '/../' . $dev['audio_path'])) {
        unlink(__DIR__ . '/../' . $dev['audio_path']);
    }
    
    redirectWithMessage('dashboard.php', 'Devocional deletado com sucesso!', 'success');
} else {
    redirectWithMessage('dashboard.php', 'Erro ao deletar.', 'error');
}
