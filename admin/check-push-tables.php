<?php
/**
 * Verificar e Criar Tabelas de Push Notifications
 * Script de diagnóstico e correção automática
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação Push Notifications</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f7fa;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #0055bd;
            border-bottom: 3px solid #0055bd;
            padding-bottom: 10px;
        }
        h2 {
            color: #333;
            margin-top: 30px;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin: 10px 0;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin: 10px 0;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 15px;
            border-radius: 6px;
            margin: 10px 0;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 6px;
            margin: 10px 0;
        }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            border: 1px solid #ddd;
        }
        .btn {
            background: #0055bd;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px 10px 0;
        }
        .btn:hover {
            background: #003d8f;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #0055bd;
            color: white;
        }
        tr:hover {
            background: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔔 Diagnóstico do Sistema de Push Notifications</h1>
        
        <?php
        $errors = [];
        $warnings = [];
        $success = [];
        
        // 1. Verificar conexão com banco
        echo "<h2>1. Verificação de Conexão</h2>";
        try {
            $pdo->query("SELECT 1");
            echo "<div class='success'>✅ Conexão com banco de dados estabelecida</div>";
            $success[] = "Conexão DB OK";
        } catch (Exception $e) {
            echo "<div class='error'>❌ Erro na conexão: " . $e->getMessage() . "</div>";
            $errors[] = "Conexão falhou";
            die();
        }
        
        // 2. Verificar tabela push_subscriptions
        echo "<h2>2. Tabela push_subscriptions</h2>";
        $tableExists = false;
        try {
            $result = $pdo->query("SHOW TABLES LIKE 'push_subscriptions'")->fetch();
            if ($result) {
                $tableExists = true;
                echo "<div class='success'>✅ Tabela push_subscriptions existe</div>";
                
                // Contar registros
                $count = $pdo->query("SELECT COUNT(*) FROM push_subscriptions")->fetchColumn();
                $active = $pdo->query("SELECT COUNT(*) FROM push_subscriptions WHERE is_active = 1")->fetchColumn();
                
                echo "<div class='info'>";
                echo "📊 <strong>Total de inscrições:</strong> $count<br>";
                echo "📊 <strong>Inscrições ativas:</strong> $active<br>";
                echo "📊 <strong>Inscrições inativas:</strong> " . ($count - $active);
                echo "</div>";
                
                // Mostrar últimas inscrições
                if ($count > 0) {
                    echo "<h3>Últimas 10 inscrições:</h3>";
                    $subs = $pdo->query("
                        SELECT id, user_hash, ip_address, is_active, created_at, updated_at
                        FROM push_subscriptions 
                        ORDER BY created_at DESC 
                        LIMIT 10
                    ")->fetchAll();
                    
                    echo "<table>";
                    echo "<tr><th>ID</th><th>Hash</th><th>IP</th><th>Status</th><th>Criado</th><th>Atualizado</th></tr>";
                    foreach ($subs as $sub) {
                        $status = $sub['is_active'] ? '<span style="color: green;">✅ Ativo</span>' : '<span style="color: red;">❌ Inativo</span>';
                        echo "<tr>";
                        echo "<td>{$sub['id']}</td>";
                        echo "<td>" . substr($sub['user_hash'], 0, 8) . "...</td>";
                        echo "<td>{$sub['ip_address']}</td>";
                        echo "<td>$status</td>";
                        echo "<td>" . date('d/m/Y H:i', strtotime($sub['created_at'])) . "</td>";
                        echo "<td>" . date('d/m/Y H:i', strtotime($sub['updated_at'])) . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
                
                $success[] = "Tabela push_subscriptions OK";
            } else {
                $tableExists = false;
                echo "<div class='error'>❌ Tabela push_subscriptions NÃO existe</div>";
                $errors[] = "Tabela push_subscriptions não existe";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Erro ao verificar tabela: " . $e->getMessage() . "</div>";
            $errors[] = $e->getMessage();
        }
        
        // 3. Verificar tabela push_notifications_log
        echo "<h2>3. Tabela push_notifications_log</h2>";
        $logTableExists = false;
        try {
            $result = $pdo->query("SHOW TABLES LIKE 'push_notifications_log'")->fetch();
            if ($result) {
                $logTableExists = true;
                echo "<div class='success'>✅ Tabela push_notifications_log existe</div>";
                
                $count = $pdo->query("SELECT COUNT(*) FROM push_notifications_log")->fetchColumn();
                echo "<div class='info'>📊 <strong>Total de logs:</strong> $count</div>";
                
                if ($count > 0) {
                    echo "<h3>Últimos 5 envios:</h3>";
                    $logs = $pdo->query("
                        SELECT l.*, d.title 
                        FROM push_notifications_log l
                        LEFT JOIN devotionals d ON l.devotional_id = d.id
                        ORDER BY l.sent_at DESC 
                        LIMIT 5
                    ")->fetchAll();
                    
                    echo "<table>";
                    echo "<tr><th>ID</th><th>Devocional</th><th>Enviadas</th><th>Falhas</th><th>Data</th></tr>";
                    foreach ($logs as $log) {
                        echo "<tr>";
                        echo "<td>{$log['id']}</td>";
                        echo "<td>" . ($log['title'] ?: 'ID: ' . $log['devotional_id']) . "</td>";
                        echo "<td style='color: green;'>{$log['total_sent']}</td>";
                        echo "<td style='color: red;'>{$log['total_failed']}</td>";
                        echo "<td>" . date('d/m/Y H:i', strtotime($log['sent_at'])) . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
                
                $success[] = "Tabela push_notifications_log OK";
            } else {
                $logTableExists = false;
                echo "<div class='warning'>⚠️ Tabela push_notifications_log NÃO existe</div>";
                $warnings[] = "Tabela de logs não existe";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Erro ao verificar tabela de logs: " . $e->getMessage() . "</div>";
        }
        
        // 4. Opção de criar tabelas
        if (!$tableExists || !$logTableExists) {
            echo "<h2>4. Correção Automática</h2>";
            
            if (isset($_GET['create_tables'])) {
                echo "<div class='info'>🔧 Criando tabelas...</div>";
                
                try {
                    $sql = file_get_contents(__DIR__ . '/../database/add_push_subscriptions_table.sql');
                    
                    // Executar cada CREATE TABLE separadamente
                    $statements = explode(';', $sql);
                    foreach ($statements as $statement) {
                        $statement = trim($statement);
                        if (!empty($statement)) {
                            $pdo->exec($statement);
                        }
                    }
                    
                    echo "<div class='success'>✅ Tabelas criadas com sucesso!</div>";
                    echo "<a href='check-push-tables.php' class='btn'>🔄 Recarregar Página</a>";
                } catch (Exception $e) {
                    echo "<div class='error'>❌ Erro ao criar tabelas: " . $e->getMessage() . "</div>";
                    echo "<pre>" . $e->getTraceAsString() . "</pre>";
                }
            } else {
                echo "<div class='warning'>";
                echo "⚠️ As tabelas necessárias não estão completas.<br><br>";
                echo "<a href='?create_tables=1' class='btn'>🔧 Criar Tabelas Automaticamente</a>";
                echo "</div>";
            }
        }
        
        // 5. Verificar dependências
        echo "<h2>5. Verificação de Dependências</h2>";
        
        // Web-Push library
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
            
            if (class_exists('Minishlink\\WebPush\\WebPush')) {
                echo "<div class='success'>✅ Biblioteca Web-Push instalada</div>";
                $success[] = "Web-Push library OK";
            } else {
                echo "<div class='error'>❌ Classe WebPush não encontrada</div>";
                echo "<div class='info'>Execute: <code>composer require minishlink/web-push</code></div>";
                $errors[] = "Web-Push class não encontrada";
            }
        } else {
            echo "<div class='error'>❌ Composer vendor não encontrado</div>";
            echo "<div class='info'>Execute: <code>composer install</code></div>";
            $errors[] = "Composer não instalado";
        }
        
        // Service Worker
        if (file_exists(__DIR__ . '/../sw.js')) {
            echo "<div class='success'>✅ Service Worker (sw.js) existe</div>";
            $success[] = "Service Worker OK";
        } else {
            echo "<div class='warning'>⚠️ Service Worker (sw.js) não encontrado</div>";
            $warnings[] = "Service Worker ausente";
        }
        
        // 6. Resumo
        echo "<h2>6. Resumo do Diagnóstico</h2>";
        
        echo "<div class='success'>";
        echo "<strong>✅ Itens OK:</strong> " . count($success) . "<br>";
        foreach ($success as $item) {
            echo "• $item<br>";
        }
        echo "</div>";
        
        if (count($warnings) > 0) {
            echo "<div class='warning'>";
            echo "<strong>⚠️ Avisos:</strong> " . count($warnings) . "<br>";
            foreach ($warnings as $item) {
                echo "• $item<br>";
            }
            echo "</div>";
        }
        
        if (count($errors) > 0) {
            echo "<div class='error'>";
            echo "<strong>❌ Erros:</strong> " . count($errors) . "<br>";
            foreach ($errors as $item) {
                echo "• $item<br>";
            }
            echo "</div>";
        }
        
        if (count($errors) === 0 && $tableExists) {
            echo "<div class='success'>";
            echo "<h3>🎉 Sistema Pronto!</h3>";
            echo "Todas as verificações passaram com sucesso.<br><br>";
            echo "<a href='../test-notification-system.php' class='btn'>🧪 Executar Testes</a>";
            echo "<a href='notifications-panel.php' class='btn'>📊 Painel de Notificações</a>";
            echo "</div>";
        }
        ?>
        
        <hr style="margin: 30px 0;">
        <p style="text-align: center; color: #666;">
            <a href="dashboard.php" class="btn">← Voltar ao Dashboard</a>
        </p>
    </div>
</body>
</html>
