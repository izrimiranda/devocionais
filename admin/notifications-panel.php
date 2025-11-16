<?php
/**
 * Admin: Painel de Notificações Push
 * Gerenciar inscritos e enviar notificações de teste
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

requireLogin();

// Estatísticas
$stats = $pdo->query('SELECT 
    COUNT(*) as total,
    SUM(is_active = 1) as active,
    SUM(is_active = 0) as inactive
FROM push_subscriptions')->fetch();

// Últimos inscritos
$subscribers = $pdo->query('SELECT * FROM push_subscriptions ORDER BY created_at DESC LIMIT 50')->fetchAll();

// Últimos envios
$logs = $pdo->query('
    SELECT pl.*, d.title 
    FROM push_notifications_log pl
    LEFT JOIN devotionals d ON pl.devotional_id = d.id
    ORDER BY pl.sent_at DESC 
    LIMIT 20
')->fetchAll();

$pageTitle = 'Notificações Push';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
</head>
<body class="admin-body">
    <!-- Barra de topo mobile -->
    <div class="mobile-topbar">
        <h1 class="mobile-title">Notificações</h1>
        <button class="sidebar-toggle" aria-label="Menu" aria-expanded="false">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
    
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2>Painel Admin</h2>
                <p class="sidebar-subtitle">Pr. Luciano Miranda</p>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-link">
                    <span class="nav-icon">📊</span>
                    <span>Devocionais</span>
                </a>
                <a href="analytics.php" class="nav-link">
                    <span class="nav-icon">📈</span>
                    <span>Analytics</span>
                </a>
                <a href="notifications-panel.php" class="nav-link active">
                    <span class="nav-icon">🔔</span>
                    <span>Notificações</span>
                </a>
                
                <div class="nav-section">
                    <div class="nav-section-header">
                        <span class="nav-icon">⚙️</span>
                        <span>Desenvolvedor</span>
                        <span class="nav-toggle-icon">▼</span>
                    </div>
                    <div class="nav-section-content">
                        <a href="optimize-images.php" class="nav-link nav-link-sub">
                            <span class="nav-icon">🖼️</span>
                            <span>Otimizar Imagens</span>
                        </a>
                        <a href="regenerate-all.php" class="nav-link nav-link-sub">
                            <span class="nav-icon">🔄</span>
                            <span>Regenerar Arquivos</span>
                        </a>
                    </div>
                </div>
                
                <a href="<?= SITE_URL ?>/" class="nav-link" target="_blank">
                    <span class="nav-icon">🌐</span>
                    <span>Ver Site</span>
                </a>
                <a href="logout.php" class="nav-link logout-link">
                    <span class="nav-icon">🚪</span>
                    <span>Sair</span>
                </a>
            </nav>
        </aside>
        
        <main class="admin-main">
            <header class="admin-header">
                <h1><?= $pageTitle ?></h1>
                <div class="header-actions">
                    <button id="test-notification" class="btn-secondary">📤 Enviar Teste</button>
                </div>
            </header>
            
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['total'] ?></div>
                    <div class="stat-label">Total de Inscritos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['active'] ?></div>
                    <div class="stat-label">Ativos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['inactive'] ?></div>
                    <div class="stat-label">Inativos</div>
                </div>
            </div>
            
            <!-- Tabs -->
            <div class="analytics-tabs">
                <div class="tab-buttons">
                    <button class="tab-btn active" data-tab="subscribers">Inscritos</button>
                    <button class="tab-btn" data-tab="logs">Histórico de Envios</button>
                </div>
                
                <!-- Tab: Inscritos -->
                <div class="tab-content active" id="tab-subscribers">
                    <table class="analytics-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User Hash</th>
                                <th>IP</th>
                                <th>Navegador</th>
                                <th>Status</th>
                                <th>Inscrito em</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subscribers as $sub): ?>
                            <tr>
                                <td><?= $sub['id'] ?></td>
                                <td><code><?= substr($sub['user_hash'], 0, 12) ?>...</code></td>
                                <td><?= $sub['ip_address'] ?></td>
                                <td><?= htmlspecialchars(substr($sub['user_agent'], 0, 50)) ?>...</td>
                                <td>
                                    <span class="badge badge-<?= $sub['is_active'] ? 'success' : 'inactive' ?>">
                                        <?= $sub['is_active'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($sub['created_at'])) ?></td>
                                <td>
                                    <button class="btn-sm" onclick="toggleSubscription(<?= $sub['id'] ?>, <?= $sub['is_active'] ?>)">
                                        <?= $sub['is_active'] ? 'Desativar' : 'Ativar' ?>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Tab: Logs -->
                <div class="tab-content" id="tab-logs">
                    <table class="analytics-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Devocional</th>
                                <th>Enviadas</th>
                                <th>Falhas</th>
                                <th>Taxa de Sucesso</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                            <?php
                                $total = $log['total_sent'] + $log['total_failed'];
                                $successRate = $total > 0 ? round(($log['total_sent'] / $total) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><?= $log['id'] ?></td>
                                <td><?= htmlspecialchars($log['title']) ?></td>
                                <td><?= $log['total_sent'] ?></td>
                                <td><?= $log['total_failed'] ?></td>
                                <td>
                                    <span class="badge badge-<?= $successRate >= 90 ? 'success' : 'warning' ?>">
                                        <?= $successRate ?>%
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($log['sent_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
    <script>
        // Enviar notificação de teste
        document.getElementById('test-notification').addEventListener('click', async () => {
            if (!confirm('Enviar notificação de teste para todos os inscritos?')) return;
            
            try {
                const response = await fetch('<?= SITE_URL ?>/api/send-push-notification.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        devotional_id: 1,
                        title: '🧪 Notificação de Teste',
                        slug: 'teste'
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(`✅ Notificação enviada!\n\nEnviadas: ${result.sent}\nFalhas: ${result.failed}`);
                    location.reload();
                } else {
                    alert('❌ Erro ao enviar: ' + result.error);
                }
            } catch (error) {
                alert('❌ Erro: ' + error.message);
            }
        });
        
        // Toggle status subscription
        async function toggleSubscription(id, currentStatus) {
            const newStatus = currentStatus ? 0 : 1;
            
            try {
                const response = await fetch('<?= SITE_URL ?>/api/toggle-subscription.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, status: newStatus })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    alert('Erro ao atualizar');
                }
            } catch (error) {
                console.error(error);
            }
        }
    </script>
</body>
</html>
