<?php
/**
 * Template: Devotional Card
 * Card reutilizável para exibir devocional em listagem
 * 
 * Variáveis esperadas:
 * - $devotional: array com dados do devocional
 */

if (!isset($devotional)) {
    return;
}

$url = getDevotionalUrl($devotional['slug']);
$image = $devotional['image_path'] ? SITE_URL . '/' . $devotional['image_path'] : SITE_URL . '/assets/images/default-devotional.jpg';
$date = formatDatePtBr($devotional['published_at']);
$textoAureo = $devotional['texto_aureo'] ?: truncateText(strip_tags($devotional['content_html']), 150);

// Montar informação da série
$serieInfo = '';
if ($devotional['serie']) {
    $serieInfo = $devotional['serie'];
}
if ($devotional['ano']) {
    $serieInfo .= ' ' . $devotional['ano'];
}
?>

<article class="devotional-card">
    <a href="<?= htmlspecialchars($url) ?>" class="card-link">
        <div class="card-image">
            <img 
                src="<?= htmlspecialchars($image) ?>" 
                alt="<?= htmlspecialchars($devotional['title']) ?>"
                loading="lazy"
            >
            <?php if ($devotional['audio_path']): ?>
            <span class="audio-badge" aria-label="Contém áudio">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M9 18V5l12-2v13M9 13l12-2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <?php endif; ?>
        </div>
        
        <div class="card-content">
            <div class="card-meta">
                <?php if ($serieInfo): ?>
                <span class="card-serie"><?= htmlspecialchars($serieInfo) ?></span>
                <?php endif; ?>
                
                <?php if ($devotional['numero_devocional']): ?>
                <span class="card-numero">#<?= htmlspecialchars($devotional['numero_devocional']) ?></span>
                <?php endif; ?>
                
                <time datetime="<?= date('Y-m-d', strtotime($devotional['published_at'])) ?>">
                    <?= htmlspecialchars($date) ?>
                </time>
            </div>
            
            <h3 class="card-title"><?= htmlspecialchars($devotional['title']) ?></h3>
            
            <?php if ($devotional['texto_aureo']): ?>
            <p class="card-texto-aureo"><?= htmlspecialchars($textoAureo) ?></p>
            <?php endif; ?>
            
            <span class="card-read-more">Ler devocional →</span>
        </div>
    </a>
</article>
