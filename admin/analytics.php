<?php
/**
 * Admin: Analytics Dashboard
 * Painel de estatísticas de acesso
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

requireLogin();

$pageTitle = 'Analytics - Estatísticas de Acesso';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="admin-body">
    <!-- Barra de topo mobile -->
    <div class="mobile-topbar">
        <h1 class="mobile-title">Analytics</h1>
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
                <a href="analytics.php" class="nav-link active">
                    <span class="nav-icon">📈</span>
                    <span>Analytics</span>
                </a>
                <a href="notifications-panel.php" class="nav-link">
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
        
        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <h1><?= $pageTitle ?></h1>
                <div class="header-actions">
                    <select id="period-selector" class="period-selector">
                        <option value="7days">Últimos 7 dias</option>
                        <option value="30days" selected>Últimos 30 dias</option>
                        <option value="90days">Últimos 90 dias</option>
                        <option value="all">Todo o período</option>
                    </select>
                </div>
            </header>
            
            <!-- Cards de Resumo -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👁️</div>
                    <div class="stat-content">
                        <div class="stat-label">Total de Visitas</div>
                        <div class="stat-value" id="total-visits">-</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <div class="stat-label">Visitantes Únicos</div>
                        <div class="stat-value" id="unique-visitors">-</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📱</div>
                    <div class="stat-content">
                        <div class="stat-label">Visitas Mobile</div>
                        <div class="stat-value" id="mobile-visits">-</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">💻</div>
                    <div class="stat-content">
                        <div class="stat-label">Visitas Desktop</div>
                        <div class="stat-value" id="desktop-visits">-</div>
                    </div>
                </div>
            </div>
            
            <!-- Gráfico de Visitas por Dia -->
            <div class="chart-container">
                <h2>Visitas por Dia</h2>
                <canvas id="daily-visits-chart"></canvas>
            </div>
            
            <!-- Tabs de Conteúdo -->
            <div class="analytics-tabs">
                <div class="tab-buttons">
                    <button class="tab-btn active" data-tab="devotionals">Devocionais Mais Visitados</button>
                    <button class="tab-btn" data-tab="devices">Dispositivos</button>
                    <button class="tab-btn" data-tab="pages">Páginas</button>
                </div>
                
                <!-- Tab: Devocionais -->
                <div class="tab-content active" id="tab-devotionals">
                    <div class="table-responsive">
                        <table class="analytics-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Título</th>
                                    <th>Visitas</th>
                                    <th>Únicos</th>
                                </tr>
                            </thead>
                            <tbody id="devotionals-tbody">
                                <tr>
                                    <td colspan="4" class="loading">Carregando...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Tab: Dispositivos -->
                <div class="tab-content" id="tab-devices">
                    <div class="device-stats-grid">
                        <div class="device-chart-container">
                            <h3>Tipos de Dispositivo</h3>
                            <canvas id="device-types-chart"></canvas>
                        </div>
                        
                        <div class="device-chart-container">
                            <h3>Navegadores</h3>
                            <canvas id="browsers-chart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Tab: Páginas -->
                <div class="tab-content" id="tab-pages">
                    <div class="table-responsive">
                        <table class="analytics-table">
                            <thead>
                                <tr>
                                    <th>URL</th>
                                    <th>Visitas</th>
                                    <th>Visitantes Únicos</th>
                                </tr>
                            </thead>
                            <tbody id="pages-tbody">
                                <tr>
                                    <td colspan="3" class="loading">Carregando...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
    <script>
        // Configuração global
        const SITE_URL = '<?= SITE_URL ?>';
        let currentPeriod = '30days';
        let charts = {};
        
        // Inicializar ao carregar
        document.addEventListener('DOMContentLoaded', function() {
            loadAnalytics();
            setupEventListeners();
        });
        
        // Event listeners
        function setupEventListeners() {
            // Seletor de período
            document.getElementById('period-selector').addEventListener('change', function(e) {
                currentPeriod = e.target.value;
                loadAnalytics();
            });
            
            // Tabs
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const tabName = this.dataset.tab;
                    switchTab(tabName);
                });
            });
        }
        
        // Carregar analytics
        async function loadAnalytics() {
            try {
                // Overview
                const overviewData = await fetch(`${SITE_URL}/api/get-analytics.php?type=overview&period=${currentPeriod}`)
                    .then(r => r.json());
                
                if (overviewData.success) {
                    updateOverview(overviewData.stats);
                }
                
                // Devocionais
                const devotionalsData = await fetch(`${SITE_URL}/api/get-analytics.php?type=devotionals&period=${currentPeriod}`)
                    .then(r => r.json());
                
                if (devotionalsData.success) {
                    updateDevotionals(devotionalsData.stats);
                }
                
                // Dispositivos
                const devicesData = await fetch(`${SITE_URL}/api/get-analytics.php?type=devices&period=${currentPeriod}`)
                    .then(r => r.json());
                
                if (devicesData.success) {
                    updateDevices(devicesData.stats);
                }
                
                // Páginas
                const pagesData = await fetch(`${SITE_URL}/api/get-analytics.php?type=pages&period=${currentPeriod}`)
                    .then(r => r.json());
                
                if (pagesData.success) {
                    updatePages(pagesData.stats);
                }
                
            } catch (error) {
                console.error('Erro ao carregar analytics:', error);
            }
        }
        
        // Atualizar cards de overview
        function updateOverview(stats) {
            document.getElementById('total-visits').textContent = stats.total_visits.toLocaleString('pt-BR');
            document.getElementById('unique-visitors').textContent = stats.unique_visitors.toLocaleString('pt-BR');
            
            // Calcular mobile e desktop
            const mobileVisits = stats.device_types?.find(d => d.device_type === 'mobile')?.visits || 0;
            const desktopVisits = stats.device_types?.find(d => d.device_type === 'desktop')?.visits || 0;
            
            document.getElementById('mobile-visits').textContent = mobileVisits.toLocaleString('pt-BR');
            document.getElementById('desktop-visits').textContent = desktopVisits.toLocaleString('pt-BR');
            
            // Gráfico de visitas diárias
            updateDailyChart(stats.daily_visits);
        }
        
        // Atualizar gráfico de visitas diárias
        function updateDailyChart(dailyVisits) {
            const ctx = document.getElementById('daily-visits-chart').getContext('2d');
            
            if (charts.dailyVisits) {
                charts.dailyVisits.destroy();
            }
            
            charts.dailyVisits = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dailyVisits.map(d => new Date(d.date).toLocaleDateString('pt-BR')),
                    datasets: [{
                        label: 'Visitas',
                        data: dailyVisits.map(d => d.visits),
                        borderColor: '#0055bd',
                        backgroundColor: 'rgba(0, 85, 189, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
        
        // Atualizar tabela de devocionais
        function updateDevotionals(devotionals) {
            const tbody = document.getElementById('devotionals-tbody');
            tbody.innerHTML = '';
            
            if (devotionals.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="no-data">Nenhum dado disponível</td></tr>';
                return;
            }
            
            devotionals.forEach(dev => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><strong>${dev.title || 'Sem título'}</strong></td>
                    <td>${dev.visits}</td>
                    <td>${dev.unique_visitors}</td>
                    <td>
                        <a href="${SITE_URL}/devocionais/${dev.slug}" target="_blank" class="btn-sm">Ver</a>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }
        
        // Atualizar estatísticas de dispositivos
        function updateDevices(stats) {
            // Gráfico de tipos de dispositivo
            const deviceCtx = document.getElementById('device-types-chart').getContext('2d');
            
            if (charts.devices) {
                charts.devices.destroy();
            }
            
            charts.devices = new Chart(deviceCtx, {
                type: 'doughnut',
                data: {
                    labels: stats.devices.map(d => d.device_type || 'Desconhecido'),
                    datasets: [{
                        data: stats.devices.map(d => d.visits),
                        backgroundColor: ['#0055bd', '#3d8bff', '#a6c8ff']
                    }]
                }
            });
            
            // Gráfico de navegadores
            const browserCtx = document.getElementById('browsers-chart').getContext('2d');
            
            if (charts.browsers) {
                charts.browsers.destroy();
            }
            
            charts.browsers = new Chart(browserCtx, {
                type: 'bar',
                data: {
                    labels: stats.browsers.map(b => b.browser || 'Desconhecido'),
                    datasets: [{
                        label: 'Visitas',
                        data: stats.browsers.map(b => b.visits),
                        backgroundColor: '#0055bd'
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
        
        // Atualizar tabela de páginas
        function updatePages(pages) {
            const tbody = document.getElementById('pages-tbody');
            tbody.innerHTML = '';
            
            if (pages.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="no-data">Nenhum dado disponível</td></tr>';
                return;
            }
            
            pages.forEach(page => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><code>${page.page_url}</code></td>
                    <td>${page.visits}</td>
                    <td>${page.unique_visitors}</td>
                `;
                tbody.appendChild(row);
            });
        }
        
        // Trocar tab
        function switchTab(tabName) {
            // Atualizar botões
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.tab === tabName);
            });
            
            // Atualizar conteúdo
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.toggle('active', content.id === `tab-${tabName}`);
            });
        }
    </script>
</body>
</html>
