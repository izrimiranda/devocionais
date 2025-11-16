<?php
/**
 * Página de Busca
 * Pesquisa por título e conteúdo
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/security.php';

$query = isset($_GET['q']) ? sanitizeString($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

$results = [];
$totalResults = 0;

if ($query && strlen($query) >= 3) {
    $searchTerm = '%' . $query . '%';
    
    // Buscar devocionais
    $stmtSearch = $pdo->prepare('
        SELECT * FROM devotionals 
        WHERE status = "published" 
        AND (title LIKE ? OR content_html LIKE ? OR excerpt LIKE ?)
        ORDER BY published_at DESC 
        LIMIT ? OFFSET ?
    ');
    $stmtSearch->execute([$searchTerm, $searchTerm, $searchTerm, $perPage, $offset]);
    $results = $stmtSearch->fetchAll();
    
    // Contar total
    $stmtCount = $pdo->prepare('
        SELECT COUNT(*) as total FROM devotionals 
        WHERE status = "published" 
        AND (title LIKE ? OR content_html LIKE ? OR excerpt LIKE ?)
    ');
    $stmtCount->execute([$searchTerm, $searchTerm, $searchTerm]);
    $totalResults = $stmtCount->fetch()['total'];
}

$totalPages = $totalResults > 0 ? ceil($totalResults / $perPage) : 0;

// Meta tags
$pageTitle = $query 
    ? 'Busca: ' . htmlspecialchars($query) . ' - Pr. Luciano Miranda'
    : 'Buscar Devocionais - Pr. Luciano Miranda';
$metaDescription = 'Busque devocionais do Pr. Luciano Miranda por título ou conteúdo.';

include __DIR__ . '/templates/header.php';
?>

<section class="search-section">
    <div class="container">
        <h1 class="section-title">Buscar Devocionais</h1>
        
        <form method="GET" action="search.php" class="search-form">
            <input 
                type="text" 
                name="q" 
                class="search-input" 
                placeholder="Digite palavras-chave..."
                value="<?= htmlspecialchars($query) ?>"
                required
                minlength="3"
                autocomplete="off"
            >
            <button type="submit" class="search-button">
                Buscar
            </button>
        </form>
        
        <?php if ($query): ?>
            <div class="search-results-info">
                <?php if ($totalResults > 0): ?>
                    Encontrados <strong><?= $totalResults ?></strong> 
                    resultado<?= $totalResults != 1 ? 's' : '' ?> 
                    para "<strong><?= htmlspecialchars($query) ?></strong>"
                <?php else: ?>
                    Nenhum resultado encontrado para "<strong><?= htmlspecialchars($query) ?></strong>"
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($results)): ?>
<section class="devotionals-section">
    <div class="container">
        <div class="devotionals-grid">
            <?php foreach ($results as $devotional): ?>
                <?php include __DIR__ . '/templates/devotional-card.php'; ?>
            <?php endforeach; ?>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Paginação">
            <?php if ($page > 1): ?>
                <a href="?q=<?= urlencode($query) ?>&page=<?= $page - 1 ?>">← Anterior</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="?q=<?= urlencode($query) ?>&page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="?q=<?= urlencode($query) ?>&page=<?= $page + 1 ?>">Próxima →</a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
    </div>
</section>
<?php elseif ($query && strlen($query) >= 3): ?>
<section class="container">
    <div style="text-align: center; padding: 3rem 0;">
        <p style="font-size: 1.125rem; color: var(--gray-600); margin-bottom: 1rem;">
            Não encontramos devocionais com esse termo.
        </p>
        <p style="color: var(--gray-500);">
            Tente usar outras palavras-chave ou 
            <a href="<?= SITE_URL ?>/" style="color: var(--primary);">
                volte à página inicial
            </a>
        </p>
    </div>
</section>
<?php elseif ($query): ?>
<section class="container">
    <div style="text-align: center; padding: 3rem 0;">
        <p style="font-size: 1.125rem; color: var(--warning);">
            Por favor, digite pelo menos 3 caracteres para buscar.
        </p>
    </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/templates/footer.php'; ?>
