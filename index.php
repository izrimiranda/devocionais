<?php
/**
 * Página Inicial
 * Exibe devocional em destaque e listagem
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';

// Paginação
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Buscar último devocional (destaque)
$stmtFeatured = $pdo->prepare('
    SELECT * FROM devotionals 
    WHERE status = "published" 
    ORDER BY published_at DESC 
    LIMIT 1
');
$stmtFeatured->execute();
$featured = $stmtFeatured->fetch();

// Buscar outros devocionais (excluindo o em destaque)
$featuredId = $featured ? $featured['id'] : 0;
$stmtList = $pdo->prepare('
    SELECT * FROM devotionals 
    WHERE status = "published" AND id != ? 
    ORDER BY published_at DESC 
    LIMIT ? OFFSET ?
');
$stmtList->execute([$featuredId, $perPage, $offset]);
$devotionals = $stmtList->fetchAll();

// Contar total para paginação
$stmtCount = $pdo->prepare('
    SELECT COUNT(*) as total FROM devotionals 
    WHERE status = "published" AND id != ?
');
$stmtCount->execute([$featuredId]);
$totalDevotionals = $stmtCount->fetch()['total'];
$totalPages = ceil($totalDevotionals / $perPage);

// Meta tags
$pageTitle = 'Devocionais - Pr. Luciano Miranda';
$metaDescription = $featured 
    ? truncateText($featured['texto_aureo'] ?: strip_tags($featured['content_html']), 160)
    : 'Devocionais diários do Pr. Luciano Miranda. Mensagens de fé, esperança e amor.';

include __DIR__ . '/templates/header.php';
?>

<!-- Pastor Section -->
<section class="pastor-section">
    <div class="container">
        <div class="pastor-image">
            <img 
                src="https://i.imgur.com/9Fwta7o.png" 
                alt="Pr. Luciano Miranda"
            >
        </div>
        <h2 class="pastor-name">Pr. Luciano Miranda</h2>
        <p class="pastor-subtitle">Devocionais Diários</p>
    </div>
</section>

<?php if ($featured): ?>
<!-- Featured Devotional -->
<section class="container">
    <article class="featured-devotional">
        <div class="featured-badge">Devocional de Hoje</div>
        
        <?php if ($featured['image_path']): ?>
        <div class="featured-image">
            <img 
                src="<?= SITE_URL . '/' . htmlspecialchars($featured['image_path']) ?>" 
                alt="<?= htmlspecialchars($featured['title']) ?>"
            >
        </div>
        <?php endif; ?>
        
        <div class="featured-content">
            <div class="featured-meta">
                <time datetime="<?= date('Y-m-d', strtotime($featured['published_at'])) ?>">
                    <?= formatDatePtBr($featured['published_at']) ?>
                </time>
            </div>
            
            <h2 class="featured-title">
                <?= htmlspecialchars($featured['title']) ?>
            </h2>
            
            <?php if ($featured['texto_aureo']): ?>
            <blockquote class="featured-texto-aureo">
                <?= htmlspecialchars($featured['texto_aureo']) ?>
            </blockquote>
            <?php endif; ?>
            
            <?php if ($featured['audio_path']): ?>
            <div class="devotional-audio">
                <?php 
                $audio = SITE_URL . '/' . $featured['audio_path'];
                include __DIR__ . '/templates/audio-player.php'; 
                ?>
            </div>
            <?php endif; ?>
            
            <div style="margin-top: 1.5rem;">
                <a 
                    href="<?= getDevotionalUrl($featured['slug']) ?>" 
                    class="btn-primary"
                >
                    Ler devocional completo
                </a>
            </div>
        </div>
    </article>
</section>
<?php endif; ?>

<?php if (!empty($devotionals)): ?>
<!-- Devotionals List -->
<section class="devotionals-section">
    <div class="container">
        <h2 class="section-title">Devocionais Anteriores</h2>
        
        <div class="devotionals-grid">
            <?php foreach ($devotionals as $devotional): ?>
                <?php include __DIR__ . '/templates/devotional-card.php'; ?>
            <?php endforeach; ?>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <!-- Pagination -->
        <nav class="pagination" aria-label="Paginação">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>" aria-label="Página anterior">← Anterior</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="current" aria-current="page"><?= $i ?></span>
                <?php else: ?>
                    <a href="?page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>" aria-label="Próxima página">Próxima →</a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
    </div>
</section>
<?php else: ?>
<section class="container">
    <div style="text-align: center; padding: 3rem 0;">
        <p style="font-size: 1.125rem; color: var(--gray-600);">
            <?= $featured ? 'Ainda não há outros devocionais publicados.' : 'Nenhum devocional publicado ainda.' ?>
        </p>
    </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/templates/footer.php'; ?>
