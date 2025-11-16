<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/image-optimizer.php';
requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token inválido';
    } else {
        $title = sanitizeString($_POST['title'] ?? '');
        $slug = $_POST['slug'] ?? generateUniqueSlug($title);
        $serie = sanitizeString($_POST['serie'] ?? '');
        $numeroDevocional = sanitizeString($_POST['numero_devocional'] ?? '');
        $ano = !empty($_POST['ano']) ? (int)$_POST['ano'] : date('Y');
        $textoAureo = trim(strip_tags($_POST['texto_aureo'] ?? ''));
        $content = sanitizeHTML($_POST['content'] ?? '');
        $publishedAt = $_POST['published_at'] ?? date('Y-m-d H:i:s');
        $status = $_POST['status'] ?? 'published';
        
        $imagePath = null;
        $audioPath = null;
        
        // Upload imagem
        if (!empty($_FILES['image']['name'])) {
            $validation = validateImageUpload($_FILES['image']);
            if ($validation['valid']) {
                $filename = generateSecureFilename($_FILES['image']['name']);
                $targetPath = __DIR__ . '/../uploads/images/' . $filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    // OTIMIZAÇÃO AUTOMÁTICA
                    $optimizeResult = optimizeUploadedImage($targetPath);
                    
                    if ($optimizeResult['success']) {
                        // Usar novo caminho (pode ter mudado de .png para .jpg)
                        $imagePath = str_replace(__DIR__ . '/../', '', $optimizeResult['new_path']);
                        
                        // Log da otimização
                        if ($optimizeResult['optimization']['optimized']) {
                            error_log(sprintf(
                                "Imagem otimizada no upload: %s | %s KB → %s KB (-%s%%)",
                                $filename,
                                $optimizeResult['optimization']['original_size_kb'],
                                $optimizeResult['optimization']['final_size_kb'],
                                $optimizeResult['optimization']['reduction_percent']
                            ));
                        }
                    } else {
                        // Se otimização falhar, usar imagem original
                        $imagePath = 'uploads/images/' . $filename;
                        error_log("Falha na otimização: " . ($optimizeResult['optimization']['error'] ?? 'Erro desconhecido'));
                    }
                }
            } else {
                $error = implode(', ', $validation['errors']);
            }
        }
        
        // Upload áudio
        if (!empty($_FILES['audio']['name'])) {
            $validation = validateAudioUpload($_FILES['audio']);
            if ($validation['valid']) {
                $filename = generateSecureFilename($_FILES['audio']['name']);
                $targetPath = __DIR__ . '/../uploads/audio/' . $filename;
                if (move_uploaded_file($_FILES['audio']['tmp_name'], $targetPath)) {
                    $audioPath = 'uploads/audio/' . $filename;
                }
            } else {
                $error = implode(', ', $validation['errors']);
            }
        }
        
        if (!$error) {
            $stmt = $pdo->prepare('INSERT INTO devotionals (title, slug, serie, numero_devocional, ano, texto_aureo, content_html, image_path, audio_path, published_at, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            if ($stmt->execute([$title, $slug, $serie, $numeroDevocional, $ano, $textoAureo, $content, $imagePath, $audioPath, $publishedAt, $status, $_SESSION['admin_id']])) {
                $devId = $pdo->lastInsertId();
                
                // Gerar arquivo
                include __DIR__ . '/generate-file.php';
                generateDevotionalFile($slug);
                
                // Enviar notificação push se status for 'published'
                if ($status === 'published') {
                    require_once __DIR__ . '/../api/send-push-notification.php';
                    $notifResult = sendPushNotification($devId, $title, $slug);
                    
                    // Log do resultado
                    if ($notifResult['success']) {
                        error_log("Push notifications enviadas: {$notifResult['sent']} sucesso, {$notifResult['failed']} falhas");
                    }
                }
                
                redirectWithMessage('dashboard.php', 'Devocional criado com sucesso!', 'success');
            } else {
                $error = 'Erro ao salvar no banco de dados.';
            }
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
    <title>Novo Devocional - Admin</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="admin-body">
    <!-- Barra de topo mobile -->
    <div class="mobile-topbar">
        <h1 class="mobile-title">Novo Devocional</h1>
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
                <a href="create.php" class="nav-link active">
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
                <h1>Novo Devocional</h1>
            </header>
            
            <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                
                <div class="form-group">
                    <label for="title">Título *</label>
                    <input type="text" id="title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="slug">Slug (URL amigável)</label>
                    <input type="text" id="slug" name="slug" placeholder="Gerado automaticamente">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="serie">Série</label>
                        <input type="text" id="serie" name="serie" placeholder="Ex: Vida de Jesus" maxlength="100">
                        <small class="form-hint">Nome da série de devocionais</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="numero_devocional">Número</label>
                        <input type="text" id="numero_devocional" name="numero_devocional" placeholder="Ex: 258" maxlength="20">
                        <small class="form-hint">Número do devocional na série</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="ano">Ano</label>
                        <input type="number" id="ano" name="ano" value="<?= date('Y') ?>" min="2020" max="2099">
                        <small class="form-hint">Ano da série</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="texto_aureo">Texto Áureo</label>
                    <textarea id="texto_aureo" name="texto_aureo" rows="3" placeholder="Versículo ou citação bíblica principal"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="content">Conteúdo *</label>
                    <textarea id="content" name="content" rows="15" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="image">Imagem</label>
                    <input type="file" id="image" name="image" accept="image/*">
                </div>
                
                <div class="form-group">
                    <label for="audio">Áudio</label>
                    <input type="file" id="audio" name="audio" accept="audio/*">
                </div>
                
                <div class="form-group">
                    <label for="published_at">Data de Publicação</label>
                    <input type="datetime-local" id="published_at" name="published_at" value="<?= date('Y-m-d\TH:i') ?>">
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="published">Publicado</option>
                        <option value="draft">Rascunho</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Salvar Devocional</button>
                    <a href="dashboard.php" class="btn-secondary">Cancelar</a>
                </div>
            </form>
        </main>
    </div>
    <script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
    <script>
        // Auto-gerar slug
        document.getElementById('title').addEventListener('input', function() {
            if (!document.getElementById('slug').value) {
                const slug = this.value.toLowerCase()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '');
                document.getElementById('slug').value = slug;
            }
        });
    </script>
</body>
</html>
