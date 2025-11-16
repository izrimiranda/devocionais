<?php
/**
 * Template: 404 Error
 * Página não encontrada
 */

$pageTitle = 'Página não encontrada - Pr. Luciano Miranda';
$metaDescription = 'A página que você procura não foi encontrada.';

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

include __DIR__ . '/header.php';
?>

<div class="error-page">
    <div class="container-narrow">
        <div class="error-content">
            <h1 class="error-code">404</h1>
            <h2 class="error-title">Página não encontrada</h2>
            <p class="error-message">
                Desculpe, a página que você está procurando não existe ou foi removida.
            </p>
            
            <div class="error-actions">
                <a href="<?= SITE_URL ?>/" class="btn-primary">
                    Voltar à página inicial
                </a>
                <a href="<?= SITE_URL ?>/search.php" class="btn-secondary">
                    Buscar devocionais
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
