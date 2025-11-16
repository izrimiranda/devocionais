<?php
/**
 * Limpar Subscriptions Inválidas
 * Remove subscriptions de teste com dados inválidos
 */

require_once __DIR__ . '/config/db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Limpar Subscriptions</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; }
        .success { background: #d4edda; padding: 15px; border-radius: 6px; color: #155724; margin: 10px 0; }
        .error { background: #f8d7da; padding: 15px; border-radius: 6px; color: #721c24; margin: 10px 0; }
        .info { background: #d1ecf1; padding: 15px; border-radius: 6px; color: #0c5460; margin: 10px 0; }
        .btn { background: #0055bd; color: white; padding: 10px 20px; border: none; border-radius: 6px; text-decoration: none; display: inline-block; margin: 10px 5px 10px 0; }
    </style>
</head>
<body>
    <h1>🧹 Limpar Subscriptions Inválidas</h1>
    
    <?php
    try {
        // Contar antes
        $before = $pdo->query("SELECT COUNT(*) FROM push_subscriptions")->fetchColumn();
        
        echo "<div class='info'>📊 Total de subscriptions antes: $before</div>";
        
        if (isset($_GET['confirm'])) {
            // Limpar subscriptions de teste (endpoints de teste ou keys muito curtas)
            $deleted = $pdo->exec("
                DELETE FROM push_subscriptions 
                WHERE endpoint LIKE '%test%' 
                   OR LENGTH(p256dh) < 50 
                   OR LENGTH(auth_key) < 16
            ");
            
            $after = $pdo->query("SELECT COUNT(*) FROM push_subscriptions")->fetchColumn();
            
            echo "<div class='success'>";
            echo "✅ <strong>Limpeza concluída!</strong><br><br>";
            echo "• Removidas: $deleted subscriptions inválidas<br>";
            echo "• Restantes: $after subscriptions válidas<br>";
            echo "</div>";
            
            echo "<a href='test-notification-system.php' class='btn'>🧪 Testar Sistema</a>";
            echo "<a href='admin/check-push-tables.php' class='btn'>📊 Ver Estatísticas</a>";
            
        } else {
            // Mostrar subscriptions que serão removidas
            $invalid = $pdo->query("
                SELECT id, endpoint, LENGTH(p256dh) as p256dh_len, LENGTH(auth_key) as auth_len
                FROM push_subscriptions 
                WHERE endpoint LIKE '%test%' 
                   OR LENGTH(p256dh) < 50 
                   OR LENGTH(auth_key) < 16
            ")->fetchAll();
            
            if (empty($invalid)) {
                echo "<div class='success'>✅ Nenhuma subscription inválida encontrada!</div>";
                echo "<a href='test-notification-system.php' class='btn'>🧪 Testar Sistema</a>";
            } else {
                echo "<div class='info'>";
                echo "<strong>⚠️ Subscriptions que serão removidas:</strong><br><br>";
                echo "<table border='1' cellpadding='8' style='width:100%; border-collapse: collapse;'>";
                echo "<tr><th>ID</th><th>Endpoint</th><th>p256dh</th><th>auth</th></tr>";
                foreach ($invalid as $sub) {
                    echo "<tr>";
                    echo "<td>{$sub['id']}</td>";
                    echo "<td>" . substr($sub['endpoint'], 0, 50) . "...</td>";
                    echo "<td>{$sub['p256dh_len']} chars</td>";
                    echo "<td>{$sub['auth_len']} chars</td>";
                    echo "</tr>";
                }
                echo "</table><br>";
                echo "Total: " . count($invalid) . " subscriptions inválidas";
                echo "</div>";
                
                echo "<a href='?confirm=1' class='btn' style='background: #dc3545;'>🗑️ Confirmar Limpeza</a>";
                echo "<a href='index.php' class='btn'>❌ Cancelar</a>";
            }
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    ?>
    
    <hr style="margin: 30px 0;">
    <p style="color: #666;">
        <strong>ℹ️ Sobre:</strong> Este script remove subscriptions de teste ou com dados inválidos 
        (keys muito curtas, endpoints de teste, etc).
    </p>
</body>
</html>
