<?php
/**
 * Template: Single Devotional
 * Exibe devocional completo
 * 
 * Variáveis esperadas:
 * - $dev: array com dados do devocional
 */

if (!isset($dev)) {
    return;
}

$image = $dev['image_path'] ? SITE_URL . '/' . $dev['image_path'] : null;
$audio = $dev['audio_path'] ? SITE_URL . '/' . $dev['audio_path'] : null;
$date = formatDatePtBr($dev['published_at']);
$shareUrl = getDevotionalUrl($dev['slug']);

// Mensagem personalizada para WhatsApp
$whatsappMessage = "Olá, como vai? 😊\n\n";
$whatsappMessage .= "Acabei de ler esse devocional inspirador. Recomendo para você também. Sua fé será fortalecida.\n\n";
$whatsappMessage .= "Acesse: " . $shareUrl;
$whatsappUrl = 'https://wa.me/?text=' . urlencode($whatsappMessage);

// URL para compartilhar no Facebook
$facebookUrl = 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($shareUrl);

// Montar informação da série
$serieInfo = '';
if ($dev['serie']) {
    $serieInfo = $dev['serie'];
}
if ($dev['ano']) {
    $serieInfo .= ' ' . $dev['ano'];
}
?>

<article class="single-devotional">
    <div class="container-narrow">
        <header class="devotional-header">
            <div class="devotional-meta">
                <?php if ($serieInfo): ?>
                <span class="devotional-serie"><?= htmlspecialchars($serieInfo) ?></span>
                <?php endif; ?>
                
                <?php if ($dev['numero_devocional']): ?>
                <span class="devotional-numero">#<?= htmlspecialchars($dev['numero_devocional']) ?></span>
                <?php endif; ?>
                
                <time datetime="<?= date('Y-m-d', strtotime($dev['published_at'])) ?>">
                    <?= htmlspecialchars($date) ?>
                </time>
            </div>
            
            <h1 class="devotional-title"><?= htmlspecialchars($dev['title']) ?></h1>
            
            <?php if ($dev['texto_aureo']): ?>
            <blockquote class="devotional-texto-aureo">
                <?= htmlspecialchars($dev['texto_aureo']) ?>
            </blockquote>
            <?php endif; ?>
        </header>
        
        <?php if ($image): ?>
        <div class="devotional-image">
            <img 
                src="<?= htmlspecialchars($image) ?>" 
                alt="<?= htmlspecialchars($dev['title']) ?>"
            >
        </div>
        <?php endif; ?>
        
        <?php if ($audio): ?>
        <div class="devotional-audio">
            <?php include __DIR__ . '/audio-player.php'; ?>
        </div>
        <?php endif; ?>
        
        <div class="devotional-content">
            <?= $dev['content_html'] ?>
        </div>
        
        <footer class="devotional-footer">
            <!-- Curtidas -->
            <div class="devotional-actions">
                <button 
                    class="btn-like" 
                    data-devotional-id="<?= $dev['id'] ?>"
                    aria-label="Curtir devocional"
                >
                    <span class="heart-icon">♡</span>
                    <span class="like-count">0</span>
                </button>
            </div>
            
            <div class="share-buttons">
                <p class="share-label">Compartilhar:</p>
                <a 
                    href="<?= htmlspecialchars($whatsappUrl) ?>" 
                    target="_blank" 
                    rel="noopener noreferrer"
                    class="btn-share btn-whatsapp"
                >
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    WhatsApp
                </a>
                
                <a 
                    href="<?= htmlspecialchars($facebookUrl) ?>" 
                    target="_blank" 
                    rel="noopener noreferrer"
                    class="btn-share btn-facebook"
                >
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    Facebook
                </a>
                
                <button 
                    class="btn-share btn-copy-link" 
                    onclick="copyToClipboard('<?= htmlspecialchars($shareUrl) ?>')"
                    aria-label="Copiar link"
                >
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Copiar link
                </button>
            </div>
            
            <div class="back-link">
                <a href="<?= SITE_URL ?>/" class="btn-back">← Voltar aos devocionais</a>
            </div>
        </footer>
    </div>
</article>
