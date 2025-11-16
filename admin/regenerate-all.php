<?php
/**
 * Regenerar todos os arquivos de devocionais
 * Execute este arquivo APENAS UMA VEZ após atualizar o sistema
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
requireLogin();

// Incluir gerador
require_once __DIR__ . '/generate-file.php';

// Buscar todos os devocionais publicados
$stmt = $pdo->query('SELECT slug FROM devotionals WHERE status = "published"');
$devotionals = $stmt->fetchAll();

$success = 0;
$errors = 0;

echo "<!DOCTYPE html>
<html lang=\"pt-BR\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Regenerar Arquivos</title>
    <style>
        body { font-family: monospace; padding: 2rem; background: #f5f5f5; }
        .log { background: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .success { color: #22543d; background: #c6f6d5; padding: 0.5rem; margin: 0.25rem 0; }
        .error { color: #742a2a; background: #fed7d7; padding: 0.5rem; margin: 0.25rem 0; }
        .summary { font-size: 1.25rem; font-weight: bold; margin-top: 2rem; }
        .btn { display: inline-block; margin-top: 1rem; padding: 0.75rem 1.5rem; background: #0055bd; color: white; text-decoration: none; border-radius: 6px; }
    </style>
</head>
<body>
    <h1>Regenerando Arquivos de Devocionais</h1>
    <div class=\"log\">";

foreach ($devotionals as $dev) {
    $slug = $dev['slug'];
    echo "<div>Processando: <strong>{$slug}</strong>... ";
    
    if (generateDevotionalFile($slug)) {
        echo "<span class=\"success\">✓ Sucesso</span>";
        $success++;
    } else {
        echo "<span class=\"error\">✗ Erro</span>";
        $errors++;
    }
    
    echo "</div>";
    flush();
}

echo "</div>
    <div class=\"summary\">
        <p>Total processado: " . count($devotionals) . "</p>
        <p class=\"success\">Sucesso: {$success}</p>
        <p class=\"error\">Erros: {$errors}</p>
    </div>
    <a href=\"dashboard.php\" class=\"btn\">← Voltar ao Dashboard</a>
</body>
</html>";
?>
