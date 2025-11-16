<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/image-optimizer.php';
requireLogin();

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare('SELECT * FROM devotionals WHERE id = ?');
$stmt->execute([$id]);
$dev = $stmt->fetch();

if (!$dev) {
    redirectWithMessage('dashboard.php', 'Devocional não encontrado.', 'error');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token inválido';
    } else {
        $title = sanitizeString($_POST['title'] ?? '');
        $slug = $_POST['slug'] ?? generateUniqueSlug($title, $id);
        $serie = sanitizeString($_POST['serie'] ?? '');
        $numeroDevocional = sanitizeString($_POST['numero_devocional'] ?? '');
        $ano = !empty($_POST['ano']) ? (int)$_POST['ano'] : date('Y');
        $textoAureo = trim(strip_tags($_POST['texto_aureo'] ?? ''));
        $content = sanitizeHTML($_POST['content'] ?? '');
        $publishedAt = $_POST['published_at'] ?? date('Y-m-d H:i:s');
        $status = $_POST['status'] ?? 'published';
        
        $imagePath = $dev['image_path'];
        $audioPath = $dev['audio_path'];
        
        if (!empty($_FILES['image']['name'])) {
            $validation = validateImageUpload($_FILES['image']);
            if ($validation['valid']) {
                $filename = generateSecureFilename($_FILES['image']['name']);
                $targetPath = __DIR__ . '/../uploads/images/' . $filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    // Deletar imagem antiga
                    if ($imagePath && file_exists(__DIR__ . '/../' . $imagePath)) {
                        unlink(__DIR__ . '/../' . $imagePath);
                    }
                    
                    // Otimizar imagem automaticamente
                    $optimizeResult = optimizeUploadedImage($targetPath);
                    if ($optimizeResult['success']) {
                        $imagePath = str_replace(__DIR__ . '/../', '', $optimizeResult['new_path']);
                        error_log("Imagem otimizada na edição: " . basename($optimizeResult['new_path']) . " | " . 
                                  round($optimizeResult['original_size_kb'], 2) . " KB → " . 
                                  round($optimizeResult['final_size_kb'], 2) . " KB (" . 
                                  round($optimizeResult['reduction_percent'], 1) . "%)");
                    } else {
                        $imagePath = 'uploads/images/' . $filename;
                    }
                }
            }
        }
        
        if (!empty($_FILES['audio']['name'])) {
            $validation = validateAudioUpload($_FILES['audio']);
            if ($validation['valid']) {
                $filename = generateSecureFilename($_FILES['audio']['name']);
                $targetPath = __DIR__ . '/../uploads/audio/' . $filename;
                if (move_uploaded_file($_FILES['audio']['tmp_name'], $targetPath)) {
                    if ($audioPath && file_exists(__DIR__ . '/../' . $audioPath)) {
                        unlink(__DIR__ . '/../' . $audioPath);
                    }
                    $audioPath = 'uploads/audio/' . $filename;
                }
            }
        }
        
        $stmt = $pdo->prepare('UPDATE devotionals SET title=?, slug=?, serie=?, numero_devocional=?, ano=?, texto_aureo=?, content_html=?, image_path=?, audio_path=?, published_at=?, status=? WHERE id=?');
        if ($stmt->execute([$title, $slug, $serie, $numeroDevocional, $ano, $textoAureo, $content, $imagePath, $audioPath, $publishedAt, $status, $id])) {
            if ($slug !== $dev['slug']) {
                $oldFile = __DIR__ . '/../devocionais/' . $dev['slug'] . '.php';
                if (file_exists($oldFile)) unlink($oldFile);
            }
            
            include __DIR__ . '/generate-file.php';
            generateDevotionalFile($slug);
            
            redirectWithMessage('dashboard.php', 'Devocional atualizado!', 'success');
        }
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Devocional - Admin</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
</head>
<body class="admin-body">
    <!-- Barra de topo mobile -->
    <div class="mobile-topbar">
        <h1 class="mobile-title">Editar Devocional</h1>
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
                    <span>Dashboard</span>
                </a>
                <a href="create.php" class="nav-link">
                    <span class="nav-icon">➕</span>
                    <span>Novo Devocional</span>
                </a>
                <a href="analytics.php" class="nav-link">
                    <span class="nav-icon">📈</span>
                    <span>Analytics</span>
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
                <h1>Editar Devocional</h1>
            </header>
            
            <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                
                <div class="form-group">
                    <label>Título *</label>
                    <input type="text" name="title" value="<?= htmlspecialchars($dev['title']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Slug</label>
                    <input type="text" name="slug" value="<?= htmlspecialchars($dev['slug']) ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Série</label>
                        <input type="text" name="serie" value="<?= htmlspecialchars($dev['serie'] ?? '') ?>" placeholder="Ex: Vida de Jesus" maxlength="100">
                        <small class="form-hint">Nome da série de devocionais</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Número</label>
                        <input type="text" name="numero_devocional" value="<?= htmlspecialchars($dev['numero_devocional'] ?? '') ?>" placeholder="Ex: 258" maxlength="20">
                        <small class="form-hint">Número do devocional na série</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Ano</label>
                        <input type="number" name="ano" value="<?= $dev['ano'] ?? date('Y') ?>" min="2020" max="2099">
                        <small class="form-hint">Ano da série</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Texto Áureo</label>
                    <textarea name="texto_aureo" rows="3" placeholder="Versículo ou citação bíblica principal"><?= htmlspecialchars($dev['texto_aureo'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Conteúdo *</label>
                    <textarea name="content" rows="15" required><?= htmlspecialchars($dev['content_html']) ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Imagem <?= $dev['image_path'] ? '(atual: ' . basename($dev['image_path']) . ')' : '' ?></label>
                    <input type="file" name="image" accept="image/*">
                </div>
                
                <div class="form-group">
                    <label>Áudio <?= $dev['audio_path'] ? '(atual: ' . basename($dev['audio_path']) . ')' : '' ?></label>
                    <input type="file" name="audio" accept="audio/*">
                </div>
                
                <div class="form-group">
                    <label>Data de Publicação</label>
                    <input type="datetime-local" name="published_at" value="<?= date('Y-m-d\TH:i', strtotime($dev['published_at'])) ?>">
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="published" <?= $dev['status'] === 'published' ? 'selected' : '' ?>>Publicado</option>
                        <option value="draft" <?= $dev['status'] === 'draft' ? 'selected' : '' ?>>Rascunho</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Salvar Alterações</button>
                    <a href="dashboard.php" class="btn-secondary">Cancelar</a>
                </div>
            </form>
        </main>
    </div>
    <script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
