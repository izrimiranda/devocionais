<?php
/**
 * Testar VAPID Keys
 * Verifica se as keys estão válidas e gera JWT para debug
 */

require_once __DIR__ . '/vendor/autoload.php';

use Minishlink\WebPush\VAPID;

echo "<h1>🔑 Teste de VAPID Keys</h1>\n\n";

// Keys atuais
$publicKey = 'BJwV3v7TuXbOmIZd_0hvtT5abgo544zzvwFYRaaz8T51mLJBQYB2kbaCtwkRCOfV4TOEh0K4cn5BuJjpf9Uot3E';
$privateKey = 'h1m9fxTl3ZWWc-LJxi_Pz2bwUQe8eQ73dv2JqnG0zmo';

echo "<h2>1. Keys Configuradas</h2>\n";
echo "<strong>Public Key:</strong> $publicKey<br>\n";
echo "<strong>Private Key:</strong> " . substr($privateKey, 0, 10) . "...<br><br>\n";

echo "<h2>2. Teste de Geração de JWT</h2>\n";

try {
    $auth = VAPID::createVapidAuthorizationHeader(
        'https://fcm.googleapis.com',
        'https://pastorluciano.com.br',
        $publicKey,
        $privateKey
    );
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 6px; color: #155724;'>\n";
    echo "✅ <strong>JWT gerado com sucesso!</strong><br><br>\n";
    echo "<strong>Authorization Header:</strong><br>\n";
    echo "<textarea style='width: 100%; height: 100px; font-family: monospace; font-size: 12px;'>$auth</textarea>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 6px; color: #721c24;'>\n";
    echo "❌ <strong>Erro ao gerar JWT:</strong><br>\n";
    echo $e->getMessage() . "<br><br>\n";
    echo "<strong>Possíveis causas:</strong><br>\n";
    echo "• Private key inválida ou corrompida<br>\n";
    echo "• Public key não corresponde à private key<br>\n";
    echo "• Keys não estão no formato base64url correto<br>\n";
    echo "</div>\n";
}

echo "<h2>3. Gerar Novas Keys (se necessário)</h2>\n";
echo "<p>Se as keys atuais estão causando erro 403, gere novas:</p>\n";

try {
    $newKeys = VAPID::createVapidKeys();
    
    echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 6px; color: #0c5460;'>\n";
    echo "<strong>📝 Novas Keys Geradas:</strong><br><br>\n";
    echo "<strong>Public Key:</strong><br>\n";
    echo "<input type='text' value='{$newKeys['publicKey']}' style='width: 100%; font-family: monospace; padding: 8px;' readonly onclick='this.select()'><br><br>\n";
    echo "<strong>Private Key:</strong><br>\n";
    echo "<input type='text' value='{$newKeys['privateKey']}' style='width: 100%; font-family: monospace; padding: 8px;' readonly onclick='this.select()'><br><br>\n";
    echo "<strong>⚠️ Para usar estas novas keys:</strong><br>\n";
    echo "1. Atualize em <code>api/send-push-notification.php</code><br>\n";
    echo "2. Atualize em <code>assets/js/notifications.js</code><br>\n";
    echo "3. Atualize em <code>test-notification-system.php</code><br>\n";
    echo "4. Limpe as subscriptions antigas: <code>clean-subscriptions.php?confirm=1</code><br>\n";
    echo "5. Crie nova subscription com a nova public key<br>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 6px; color: #721c24;'>\n";
    echo "❌ Erro ao gerar novas keys: " . $e->getMessage() . "\n";
    echo "</div>\n";
}

echo "<hr>\n";
echo "<p><a href='test-notification-system.php'>← Voltar aos Testes</a></p>\n";
?>
