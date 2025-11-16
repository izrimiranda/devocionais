<?php
/**
 * Template: Header
 * Cabeçalho padrão do site
 */
if (!isset($pageTitle)) {
    $pageTitle = 'Devocionais - Pr. Luciano Miranda';
}
if (!isset($metaDescription)) {
    $metaDescription = 'Devocionais diários do Pr. Luciano Miranda. Mensagens de fé, esperança e amor para fortalecer sua jornada espiritual.';
}
if (!isset($metaImage)) {
    $metaImage = 'https://i.imgur.com/Jpaf0oW.png';
}
if (!isset($canonical)) {
    $canonical = SITE_URL . $_SERVER['REQUEST_URI'];
}

// WhatsApp requer og:title sem marca - criar versão limpa
$ogTitle = isset($dev) 
    ? $dev['title'] 
    : preg_replace('/ - Pr\. Luciano Miranda$/', '', $pageTitle);

// WhatsApp limita description a 80 caracteres
$ogDescription = mb_substr($metaDescription, 0, 80);

// Favicon dinâmico - usar imagem do devocional se disponível
$faviconUrl = isset($dev) && !empty($dev['image_path']) 
    ? SITE_URL . '/' . $dev['image_path'] 
    : $metaImage;

// Detectar tipo MIME da imagem para Open Graph
$imageType = 'image/jpeg'; // padrão
$imageWidth = 1200; // padrão
$imageHeight = 630; // padrão

if (isset($metaImage)) {
    $ext = strtolower(pathinfo($metaImage, PATHINFO_EXTENSION));
    if ($ext === 'png') {
        $imageType = 'image/png';
    } elseif ($ext === 'jpg' || $ext === 'jpeg') {
        $imageType = 'image/jpeg';
    } elseif ($ext === 'webp') {
        $imageType = 'image/webp';
    } elseif ($ext === 'gif') {
        $imageType = 'image/gif';
    }
    
    // Detectar dimensões reais da imagem
    if (isset($dev) && !empty($dev['image_path'])) {
        $imagePath = __DIR__ . '/../' . $dev['image_path'];
        if (file_exists($imagePath)) {
            $imageInfo = getimagesize($imagePath);
            if ($imageInfo !== false) {
                $imageWidth = $imageInfo[0];
                $imageHeight = $imageInfo[1];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    
    <!-- Analytics -->
    <?php if (isset($dev) && isset($dev['id'])): ?>
    <meta name="devotional-id" content="<?= $dev['id'] ?>">
    <?php endif; ?>
    
    <!-- Open Graph / Facebook / WhatsApp -->
    <meta property="og:type" content="<?= isset($ogType) ? $ogType : 'website' ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($ogTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($ogDescription) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($metaImage) ?>">
    <meta property="og:image:secure_url" content="<?= htmlspecialchars($metaImage) ?>">
    <meta property="og:image:type" content="<?= htmlspecialchars($imageType) ?>">
    <meta property="og:image:width" content="<?= $imageWidth ?>">
    <meta property="og:image:height" content="<?= $imageHeight ?>">
    <meta property="og:site_name" content="Pr. Luciano Miranda - Devocionais">
    <meta property="og:locale" content="pt_BR">
    <?php if (isset($dev) && isset($dev['created_at'])): ?>
    <meta property="article:published_time" content="<?= date('c', strtotime($dev['created_at'])) ?>">
    <meta property="article:author" content="Pr. Luciano Miranda">
    <?php endif; ?>
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= htmlspecialchars($canonical) ?>">
    <meta property="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="twitter:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="twitter:image" content="<?= htmlspecialchars($metaImage) ?>">
    
    <!-- Canonical -->
    <link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($faviconUrl) ?>">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($faviconUrl) ?>">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="<?= SITE_URL ?>/manifest.json">
    <meta name="theme-color" content="#0055bd">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/main.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- JavaScript -->
    <script>
        const SITE_URL = '<?= SITE_URL ?>';
    </script>
    <script src="<?= SITE_URL ?>/assets/js/analytics.js" defer></script>
    <script src="<?= SITE_URL ?>/assets/js/devotionals.js" defer></script>
    <script src="<?= SITE_URL ?>/assets/js/notifications.js" defer></script>
    <script src="<?= SITE_URL ?>/assets/js/install-app.js" defer></script>
    
    <?php if (isset($additionalCSS)): ?>
        <?= $additionalCSS ?>
    <?php endif; ?>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="header-content">
                <div class="logo-wrapper">
                    <img src="https://i.imgur.com/Jpaf0oW.png" alt="Logo" class="site-logo">
                    <div class="site-title-wrapper">
                        <h1 class="site-title">
                            <a href="<?= SITE_URL ?>/">Devocionais</a>
                        </h1>
                        <p class="site-subtitle">Pr. Luciano Miranda</p>
                    </div>
                </div>
                <nav class="main-nav">
                    <button class="nav-toggle" aria-label="Menu" aria-expanded="false">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                                        <ul class="nav-menu">
                        <li><a href="<?= SITE_URL ?>/" class="nav-link"><span>🏠</span> Início</a></li>
                        <li><a href="<?= SITE_URL ?>/search.php" class="nav-link"><span>🔍</span> Buscar</a></li>
                        <li id="install-container">
                            <button id="install-app-btn" class="nav-link install-app-btn">
                                <span>📱</span> <span>Instalar App</span>
                            </button>
                        </li>
                        <li><a href="<?= SITE_URL ?>/admin/dashboard.php" class="nav-link"><span>🔐</span> Painel Admin</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>
    
    <main class="main-content">
        <?php
        // Exibir mensagem flash se existir
        $flashMessage = getFlashMessage();
        if ($flashMessage):
        ?>
        <div class="flash-message flash-<?= $flashMessage['type'] ?>">
            <div class="container">
                <?= htmlspecialchars($flashMessage['text']) ?>
            </div>
        </div>
        <?php endif; ?>
