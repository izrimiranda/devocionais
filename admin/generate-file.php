<?php
/**
 * Gerador de arquivo devocional
 * Cria arquivo /devocionais/slug.php
 */

function generateDevotionalFile($slug) {
    $filePath = __DIR__ . '/../devocionais/' . $slug . '.php';
    
    $template = <<<'PHP'
<?php
/**
 * Devocional: %SLUG%
 * Gerado automaticamente
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

$slug = '%SLUG%';
$stmt = $pdo->prepare('SELECT * FROM devotionals WHERE slug = ? AND status = "published" LIMIT 1');
$stmt->execute([$slug]);
$dev = $stmt->fetch();

if (!$dev) {
    http_response_code(404);
    include __DIR__ . '/../templates/404.php';
    exit;
}

// Rastrear visualização (opcional)
if (function_exists('trackView')) {
    trackView($dev['id']);
}

// Meta tags
$pageTitle = $dev['title'] . ' - Pr. Luciano Miranda';
$metaDescription = $dev['texto_aureo'] ?: truncateText(strip_tags($dev['content_html']), 160);
$metaImage = $dev['image_path'] ? SITE_URL . '/' . $dev['image_path'] : SITE_URL . '/assets/images/pastor-luciano.png';
$canonical = getDevotionalUrl($dev['slug']);
$ogType = 'article';

include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/single-devotional.php';
include __DIR__ . '/../templates/footer.php';
PHP;
    
    $content = str_replace('%SLUG%', $slug, $template);
    
    if (file_put_contents($filePath, $content)) {
        chmod($filePath, 0644);
        return true;
    }
    
    return false;
}

// Se chamado diretamente
if (isset($slug)) {
    return generateDevotionalFile($slug);
}
