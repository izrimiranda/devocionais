<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
requireLogin();

// Estatísticas
$stats = $pdo->query('SELECT 
    (SELECT COUNT(*) FROM devotionals WHERE status="published") as published,
    (SELECT COUNT(*) FROM devotionals WHERE status="draft") as drafts,
    (SELECT COUNT(*) FROM devotionals) as total
')->fetch();

// Lista de devocionais
$devotionals = $pdo->query('SELECT * FROM devotionals ORDER BY published_at DESC LIMIT 50')->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devocionais - Admin</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="admin-body">
    <!-- Barra de topo mobile -->
    <div class="mobile-topbar">
        <h1 class="mobile-title">Painel Admin</h1>
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
                <a href="dashboard.php" class="nav-link active">
                    <span class="nav-icon">📊</span>
                    <span>Devocionais</span>
                </a>
                <a href="analytics.php" class="nav-link">
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
        
        <main class="admin-main">
            <header class="admin-header">
                <h1>Devocionais</h1>
                <div class="header-actions">
                    <a href="create.php" class="btn-primary">+ Novo Devocional</a>
                </div>
            </header>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['published'] ?></div>
                    <div class="stat-label">Publicados</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['drafts'] ?></div>
                    <div class="stat-label">Rascunhos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['total'] ?></div>
                    <div class="stat-label">Total</div>
                </div>
            </div>
            
            <div class="devotionals-container">
                <div class="devotionals-header">
                    <h2>Devocionais Recentes</h2>
                    <div class="search-box">
                        <input 
                            type="text" 
                            id="search-devotionals" 
                            class="search-input" 
                            placeholder="🔍 Buscar por título, série ou ano..."
                            autocomplete="off">
                        <span class="search-count" id="search-count"></span>
                    </div>
                </div>
                <div class="devotionals-grid" id="devotionals-grid">
                    <?php foreach ($devotionals as $dev): ?>
                    <div class="devotional-card-admin" data-title="<?= strtolower(htmlspecialchars($dev['title'])) ?>" data-serie="<?= strtolower(htmlspecialchars($dev['serie'] ?? '')) ?>" data-ano="<?= $dev['ano'] ?? '' ?>">
                        <div class="card-header-admin">
                            <h3 class="card-title-admin"><?= htmlspecialchars($dev['title']) ?></h3>
                            <span class="badge badge-<?= $dev['status'] ?>"><?= $dev['status'] ?></span>
                        </div>
                        
                        <div class="card-meta-admin">
                            <div class="meta-item">
                                <span class="meta-label">📅 Data:</span>
                                <span class="meta-value"><?= formatDatePtBr($dev['published_at']) ?></span>
                            </div>
                            <?php if ($dev['serie']): ?>
                            <div class="meta-item">
                                <span class="meta-label">📚 Série:</span>
                                <span class="meta-value"><?= htmlspecialchars($dev['serie']) ?> - <?= $dev['ano'] ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-actions-admin">
                            <button onclick="copyWhatsAppMessage('<?= htmlspecialchars($dev['title'], ENT_QUOTES) ?>', '<?= formatDatePtBr($dev['published_at']) ?>', '<?= $dev['slug'] ?>')" class="btn-action btn-whatsapp" title="Copiar mensagem WhatsApp">
                                <span class="icon">💬</span>
                                <span class="label">WhatsApp</span>
                            </button>
                            <button onclick="copyDevotionalUrl('<?= $dev['slug'] ?>')" class="btn-action" title="Copiar URL">
                                <span class="icon">📋</span>
                                <span class="label">Copiar</span>
                            </button>
                            <a href="edit.php?id=<?= $dev['id'] ?>" class="btn-action" title="Editar">
                                <span class="icon">✏️</span>
                                <span class="label">Editar</span>
                            </a>
                            <a href="<?= getDevotionalUrl($dev['slug']) ?>" target="_blank" class="btn-action" title="Visualizar">
                                <span class="icon">👁️</span>
                                <span class="label">Ver</span>
                            </a>
                            <a href="delete.php?id=<?= $dev['id'] ?>" onclick="return confirm('Deletar este devocional?')" class="btn-action btn-action-danger" title="Deletar">
                                <span class="icon">🗑️</span>
                                <span class="label">Deletar</span>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="<?= SITE_URL ?>/assets/js/admin.js?v=<?= time() ?>"></script>
    <script>
    // Garantir que a função esteja disponível
    if (typeof copyWhatsAppMessage === 'undefined') {
        function copyWhatsAppMessage(title, date, slug) {
            const url = window.location.origin + '/devocionais/' + slug + '.php';
            
            const message = `Olá, como vai?

Acabei de postar mais um *devocional*! Acesse agora mesmo e seja edificado com a mensagem.

*Tema do Devocional:* ${title}
*Data*: ${date}
*Acesse*: ${url}

_Cordialmente_,
_Pr. Luciano Miranda_
*Pastor Batista*`;
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(message).then(() => {
                    showToast('✅ Mensagem WhatsApp copiada!', 'success');
                }).catch(err => {
                    fallbackCopy(message);
                });
            } else {
                fallbackCopy(message);
            }
        }
        
        function fallbackCopy(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            showToast('✅ Mensagem WhatsApp copiada!', 'success');
        }
    }
    
    // Busca em tempo real de devocionais
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search-devotionals');
        const devotionalsGrid = document.getElementById('devotionals-grid');
        const searchCount = document.getElementById('search-count');
        const cards = devotionalsGrid.querySelectorAll('.devotional-card-admin');
        const totalCards = cards.length;
        
        // Atualizar contador inicial
        updateCount(totalCards, totalCards);
        
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            let visibleCount = 0;
            
            cards.forEach(card => {
                const title = card.getAttribute('data-title');
                const serie = card.getAttribute('data-serie');
                const ano = card.getAttribute('data-ano');
                
                const matches = 
                    title.includes(searchTerm) || 
                    serie.includes(searchTerm) || 
                    ano.includes(searchTerm);
                
                if (matches || searchTerm === '') {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            updateCount(visibleCount, totalCards);
            
            // Mostrar mensagem se nenhum resultado
            showNoResults(visibleCount);
        });
        
        function updateCount(visible, total) {
            if (searchInput.value.trim() === '') {
                searchCount.textContent = '';
            } else {
                searchCount.textContent = `${visible} de ${total}`;
            }
        }
        
        function showNoResults(count) {
            let noResultsMsg = devotionalsGrid.querySelector('.no-results-message');
            
            if (count === 0 && searchInput.value.trim() !== '') {
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('div');
                    noResultsMsg.className = 'no-results-message';
                    noResultsMsg.innerHTML = `
                        <div style="text-align: center; padding: 3rem; color: #64748b;">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">🔍</div>
                            <h3 style="color: #1a202c; margin-bottom: 0.5rem;">Nenhum resultado encontrado</h3>
                            <p>Tente buscar com outros termos</p>
                        </div>
                    `;
                    devotionalsGrid.appendChild(noResultsMsg);
                }
            } else {
                if (noResultsMsg) {
                    noResultsMsg.remove();
                }
            }
        }
    });
    </script>
</body>
</html>
