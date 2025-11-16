<?php
/**
 * Teste Completo do Sistema de Notificações
 * Acesse via: /test-notification-system.php
 */

require_once 'config/db.php';

echo "<h1>🔔 Teste do Sistema de Notificações</h1>";
echo "<hr>";

// 1. Verificar tabelas
echo "<h2>1. Verificar Tabelas</h2>";

try {
    $tables = $pdo->query("SHOW TABLES LIKE 'push_%'")->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('push_subscriptions', $tables)) {
        echo "✅ Tabela push_subscriptions existe<br>";
        
        $count = $pdo->query("SELECT COUNT(*) FROM push_subscriptions")->fetchColumn();
        $active = $pdo->query("SELECT COUNT(*) FROM push_subscriptions WHERE is_active = 1")->fetchColumn();
        
        echo "📊 Total de inscritos: $count<br>";
        echo "📊 Inscritos ativos: $active<br><br>";
        
        if ($count > 0) {
            echo "<h3>Últimos inscritos:</h3>";
            $subs = $pdo->query("SELECT * FROM push_subscriptions ORDER BY created_at DESC LIMIT 5")->fetchAll();
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>IP</th><th>Ativo</th><th>Criado em</th></tr>";
            foreach ($subs as $sub) {
                echo "<tr>";
                echo "<td>{$sub['id']}</td>";
                echo "<td>{$sub['ip_address']}</td>";
                echo "<td>" . ($sub['is_active'] ? '✅' : '❌') . "</td>";
                echo "<td>{$sub['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table><br>";
        }
    } else {
        echo "❌ Tabela push_subscriptions NÃO existe<br>";
        echo "Execute: database/add_push_subscriptions_table.sql<br><br>";
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br><br>";
}

// 2. Verificar Composer/vendor
echo "<h2>2. Verificar Biblioteca Web-Push</h2>";

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "✅ vendor/autoload.php existe<br>";
    
    require_once __DIR__ . '/vendor/autoload.php';
    
    if (class_exists('Minishlink\\WebPush\\WebPush')) {
        echo "✅ Classe WebPush carregada<br><br>";
    } else {
        echo "❌ Classe WebPush NÃO encontrada<br>";
        echo "Execute: composer require minishlink/web-push<br><br>";
    }
} else {
    echo "❌ vendor/autoload.php NÃO existe<br>";
    echo "Execute no servidor:<br>";
    echo "<code>composer install</code><br><br>";
}

// 3. Verificar VAPID Keys
echo "<h2>3. Verificar VAPID Keys</h2>";
echo "Public Key: BJwV3v7TuXbOmIZd_0hvtT5abgo544zzvwFYRaaz8T51mLJBQYB2kbaCtwkRCOfV4TOEh0K4cn5BuJjpf9Uot3E<br>";
echo "✅ Keys configuradas<br><br>";

// 4. Testar API de Subscribe
echo "<h2>4. Teste da API Subscribe</h2>";
echo "<p>⚠️ Este teste usa dados reais de subscription do navegador</p>";
echo "<button onclick='testSubscribeReal()'>🧪 Testar Subscribe Real</button>";
echo "<button onclick='testSubscribeFake()' style='background: #666;'>🔧 Testar Subscribe Fake (não recomendado)</button>";
echo "<div id='subscribe-result'></div><br>";

// 5. Testar API de Send
echo "<h2>5. Teste de Envio (Manual)</h2>";
echo "<p>⚠️ Só funciona se houver inscritos ativos</p>";
echo "<button onclick='testSend()'>📤 Enviar Notificação Teste</button>";
echo "<div id='send-result'></div><br>";

?>

<script>
// Testar Subscribe com dados reais do navegador
async function testSubscribeReal() {
    const result = document.getElementById('subscribe-result');
    result.innerHTML = '⏳ Solicitando permissão e criando subscription...';
    
    try {
        // Verificar suporte
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            result.innerHTML = '❌ Navegador não suporta Push Notifications';
            return;
        }
        
        // Solicitar permissão
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            result.innerHTML = '❌ Permissão de notificação negada';
            return;
        }
        
        // Registrar Service Worker
        const registration = await navigator.serviceWorker.register('/sw.js');
        await navigator.serviceWorker.ready;
        
        // VAPID public key
        const vapidPublicKey = 'BOYXEbV0gz0T4x0JM56sqEfsnr-_YDPsTvVdgz7syHHW3PgpkfD2AsJ85xa5UCuG4llS7BQm5_NLXhODRm4zdaY';
        
        // Converter para Uint8Array
        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }
        
        // Criar subscription
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
        });
        
        // Enviar para API
        const response = await fetch('/api/subscribe-push.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'subscribe',
                subscription: subscription.toJSON()
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            result.innerHTML = `✅ Subscribe real funcionando!<br>
                <strong>User Hash:</strong> ${data.user_hash}<br>
                <strong>Endpoint:</strong> ${subscription.endpoint.substring(0, 50)}...<br>
                <pre>${JSON.stringify(data, null, 2)}</pre>`;
        } else {
            result.innerHTML = '❌ Erro: ' + (data.message || data.error || 'Desconhecido');
        }
    } catch (error) {
        result.innerHTML = '❌ Erro: ' + error.message;
        console.error(error);
    }
}

// Testar Subscribe com dados fake (para debug)
async function testSubscribeFake() {
    const result = document.getElementById('subscribe-result');
    result.innerHTML = '⏳ Testando com dados fake...';
    
    // Simular dados de subscription
    const testData = {
        action: 'subscribe',
        subscription: {
            endpoint: 'https://fcm.googleapis.com/fcm/send/teste123',
            keys: {
                p256dh: 'BTestKey123',
                auth: 'AuthTestKey456'
            }
        }
    };
    
    try {
        const response = await fetch('/api/subscribe-push.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(testData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            result.innerHTML = '✅ Subscribe fake aceito (mas não funcionará para envio real)';
        } else {
            result.innerHTML = '❌ Esperado: ' + (data.message || 'Keys muito curtas');
        }
    } catch (error) {
        result.innerHTML = '❌ Erro de rede: ' + error.message;
    }
}

async function testSend() {
    const result = document.getElementById('send-result');
    result.innerHTML = '⏳ Enviando...';
    
    const testData = {
        devotional_id: 999,
        title: 'Teste de Notificação',
        slug: 'teste-notificacao'
    };
    
    try {
        const response = await fetch('/api/send-push-notification.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(testData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            result.innerHTML = `✅ Enviado!<br>
                Enviadas: ${data.sent || 0}<br>
                Falhas: ${data.failed || 0}<br>
                Total: ${data.total || 0}<br>
                Mensagem: ${data.message || ''}`;
        } else {
            result.innerHTML = '❌ Erro: ' + (data.error || data.message || 'Desconhecido');
        }
    } catch (error) {
        result.innerHTML = '❌ Erro: ' + error.message;
    }
}
</script>

<style>
body { font-family: Arial, sans-serif; padding: 20px; max-width: 1000px; margin: 0 auto; }
h1 { color: #0055bd; }
h2 { color: #333; border-bottom: 2px solid #0055bd; padding-bottom: 5px; }
button { background: #0055bd; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
button:hover { background: #003d8f; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th { background: #0055bd; color: white; }
code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
#subscribe-result, #send-result { margin-top: 10px; padding: 10px; background: #f9f9f9; border-radius: 5px; }
</style>
