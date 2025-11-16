<?php
/**
 * Instalação Rápida de Tabelas Push Notifications
 * Execute uma única vez acessando: /install-push-tables.php
 * APAGUE ESTE ARQUIVO APÓS EXECUTAR
 */

require_once __DIR__ . '/config/db.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Instalação Push Tables</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f7fa;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h1 {
            color: #0055bd;
            border-bottom: 3px solid #0055bd;
            padding-bottom: 10px;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
        }
        .btn {
            background: #0055bd;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px 10px 0;
        }
        .btn:hover {
            background: #003d8f;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Instalação de Tabelas Push Notifications</h1>";

try {
    // Verificar se já existem
    $check1 = $pdo->query("SHOW TABLES LIKE 'push_subscriptions'")->fetch();
    $check2 = $pdo->query("SHOW TABLES LIKE 'push_notifications_log'")->fetch();
    
    if ($check1 && $check2) {
        echo "<div class='info'>
            ℹ️ As tabelas já existem no banco de dados!<br><br>
            <strong>push_subscriptions:</strong> ✅<br>
            <strong>push_notifications_log:</strong> ✅
        </div>";
        
        $count = $pdo->query("SELECT COUNT(*) FROM push_subscriptions")->fetchColumn();
        $active = $pdo->query("SELECT COUNT(*) FROM push_subscriptions WHERE is_active = 1")->fetchColumn();
        
        echo "<div class='success'>
            📊 <strong>Estatísticas:</strong><br>
            Total de inscrições: $count<br>
            Inscrições ativas: $active
        </div>";
        
        echo "<a href='test-notification-system.php' class='btn'>🧪 Testar Sistema</a>";
        echo "<a href='admin/check-push-tables.php' class='btn'>🔍 Diagnóstico Completo</a>";
        
    } else {
        // Ler SQL
        $sqlFile = __DIR__ . '/database/add_push_subscriptions_table.sql';
        
        if (!file_exists($sqlFile)) {
            throw new Exception("Arquivo SQL não encontrado: $sqlFile");
        }
        
        $sql = file_get_contents($sqlFile);
        
        echo "<div class='info'>
            📄 Lendo arquivo SQL...<br>
            Arquivo: database/add_push_subscriptions_table.sql
        </div>";
        
        // Executar SQL
        echo "<div class='info'>⏳ Executando comandos SQL...</div>";
        
        // Dividir por ; e executar cada statement
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && strpos($stmt, '--') !== 0;
            }
        );
        
        $executed = 0;
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
                $executed++;
            }
        }
        
        echo "<div class='success'>
            ✅ <strong>Instalação concluída com sucesso!</strong><br><br>
            • Comandos executados: $executed<br>
            • Tabela push_subscriptions criada ✅<br>
            • Tabela push_notifications_log criada ✅<br>
            • Índices configurados ✅
        </div>";
        
        echo "<div class='info'>
            <strong>⚠️ IMPORTANTE:</strong><br>
            Por segurança, apague este arquivo após a instalação:<br>
            <code>install-push-tables.php</code>
        </div>";
        
        echo "<a href='test-notification-system.php' class='btn'>🧪 Testar Sistema</a>";
        echo "<a href='admin/check-push-tables.php' class='btn'>🔍 Verificar Instalação</a>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>
        ❌ <strong>Erro no banco de dados:</strong><br>
        " . htmlspecialchars($e->getMessage()) . "
    </div>";
    
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    
} catch (Exception $e) {
    echo "<div class='error'>
        ❌ <strong>Erro:</strong><br>
        " . htmlspecialchars($e->getMessage()) . "
    </div>";
}

echo "
        <hr style='margin: 30px 0;'>
        <p style='text-align: center; color: #666;'>
            <a href='index.php' class='btn'>← Voltar ao Site</a>
        </p>
    </div>
</body>
</html>";
?>
