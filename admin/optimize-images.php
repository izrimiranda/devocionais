<?php
/**
 * Script para otimizar imagens para WhatsApp (< 600 KB)
 * Requisito: WhatsApp exige imagens menores que 600 KB
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

// Verificar autenticação
requireLogin();

$uploadsDir = __DIR__ . '/../uploads/images/';
$optimizedCount = 0;
$errors = [];
$results = [];

// Processar otimização
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['optimize'])) {
    $files = glob($uploadsDir . '*.{png,jpg,jpeg}', GLOB_BRACE);
    
    foreach ($files as $file) {
        $fileSize = filesize($file);
        $fileSizeKB = round($fileSize / 1024, 2);
        $fileName = basename($file);
        
        // Pular se já está dentro do limite
        if ($fileSize < 600000) { // 600 KB
            $results[] = [
                'file' => $fileName,
                'status' => 'ok',
                'original' => $fileSizeKB,
                'optimized' => $fileSizeKB
            ];
            continue;
        }
        
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        
        try {
            // Criar imagem a partir do arquivo
            if ($ext === 'png') {
                $image = imagecreatefrompng($file);
            } elseif ($ext === 'jpg' || $ext === 'jpeg') {
                $image = imagecreatefromjpeg($file);
            } else {
                continue;
            }
            
            if (!$image) {
                $errors[] = "Erro ao carregar: $fileName";
                continue;
            }
            
            // Obter dimensões originais
            $width = imagesx($image);
            $height = imagesy($image);
            
            // Backup do arquivo original
            $backupFile = $file . '.backup';
            copy($file, $backupFile);
            
            // Estratégia de otimização em 3 etapas
            $optimized = false;
            
            // ETAPA 1: Tentar com qualidade reduzida (sem redimensionar)
            for ($quality = 85; $quality >= 40; $quality -= 5) {
                if ($ext === 'png') {
                    $compression = (int) ((100 - $quality) / 10);
                    imagepng($image, $file, min(9, $compression));
                } else {
                    imagejpeg($image, $file, $quality);
                }
                
                if (filesize($file) < 600000) {
                    $optimized = true;
                    break;
                }
            }
            
            // ETAPA 2: Se não otimizou, redimensionar para 1200px (WhatsApp recomenda)
            if (!$optimized && $width > 1200) {
                $newWidth = 1200;
                $newHeight = (int) ($height * ($newWidth / $width));
                
                $resized = imagecreatetruecolor($newWidth, $newHeight);
                
                if ($ext === 'png') {
                    imagealphablending($resized, false);
                    imagesavealpha($resized, true);
                    $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
                    imagefill($resized, 0, 0, $transparent);
                }
                
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($image);
                $image = $resized;
                $width = $newWidth;
                $height = $newHeight;
                
                // Tentar novamente com imagem redimensionada
                for ($quality = 85; $quality >= 40; $quality -= 5) {
                    if ($ext === 'png') {
                        $compression = (int) ((100 - $quality) / 10);
                        imagepng($image, $file, min(9, $compression));
                    } else {
                        imagejpeg($image, $file, $quality);
                    }
                    
                    if (filesize($file) < 600000) {
                        $optimized = true;
                        break;
                    }
                }
            }
            
            // ETAPA 3: Se ainda não otimizou, converter PNG para JPEG (muito mais leve)
            if (!$optimized && $ext === 'png') {
                $newFileName = str_replace('.png', '.jpg', $file);
                
                // Criar imagem JPEG com fundo branco
                $jpegImage = imagecreatetruecolor($width, $height);
                $white = imagecolorallocate($jpegImage, 255, 255, 255);
                imagefill($jpegImage, 0, 0, $white);
                imagecopy($jpegImage, $image, 0, 0, 0, 0, $width, $height);
                
                for ($quality = 85; $quality >= 50; $quality -= 5) {
                    imagejpeg($jpegImage, $newFileName, $quality);
                    
                    if (filesize($newFileName) < 600000) {
                        // Sucesso! Remover PNG e renomear JPEG
                        unlink($file);
                        $file = $newFileName;
                        $fileName = basename($file);
                        
                        // Atualizar banco de dados
                        $oldPath = 'uploads/images/' . basename($backupFile, '.backup');
                        $newPath = 'uploads/images/' . basename($newFileName);
                        
                        $updateStmt = $pdo->prepare('UPDATE devotionals SET image_path = ? WHERE image_path = ?');
                        $updateStmt->execute([$newPath, $oldPath]);
                        
                        $optimized = true;
                        $results[] = [
                            'file' => $fileName,
                            'status' => 'converted',
                            'original' => $fileSizeKB,
                            'optimized' => round(filesize($file) / 1024, 2),
                            'quality' => $quality,
                            'note' => 'Convertido de PNG para JPEG'
                        ];
                        $optimizedCount++;
                        break;
                    }
                }
                
                imagedestroy($jpegImage);
            }
            
            if ($optimized && !isset($results[count($results) - 1])) {
                // Sucesso na otimização normal
                unlink($backupFile);
                $results[] = [
                    'file' => $fileName,
                    'status' => 'optimized',
                    'original' => $fileSizeKB,
                    'optimized' => round(filesize($file) / 1024, 2),
                    'quality' => $quality
                ];
                $optimizedCount++;
            } elseif (!$optimized) {
                // Falhou, restaurar backup
                copy($backupFile, $file);
                unlink($backupFile);
                $errors[] = "Não foi possível otimizar: $fileName (mesmo após redimensionar e converter)";
            } else {
                // Otimizado via conversão, já adicionado aos resultados
                unlink($backupFile);
            }
            
            imagedestroy($image);
            
        } catch (Exception $e) {
            $errors[] = "Erro ao processar $fileName: " . $e->getMessage();
        }
    }
}

// Escanear imagens atuais
$images = [];
$totalSize = 0;
$oversizedCount = 0;

$files = glob($uploadsDir . '*.{png,jpg,jpeg}', GLOB_BRACE);
foreach ($files as $file) {
    $fileSize = filesize($file);
    $totalSize += $fileSize;
    $fileSizeKB = round($fileSize / 1024, 2);
    
    $isOversized = $fileSize > 600000;
    if ($isOversized) $oversizedCount++;
    
    $images[] = [
        'name' => basename($file),
        'size' => $fileSizeKB,
        'oversized' => $isOversized
    ];
}

usort($images, function($a, $b) {
    return $b['size'] <=> $a['size'];
});
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Otimizar Imagens - WhatsApp</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        .stat-label {
            font-size: 0.875rem;
            color: #64748b;
            text-transform: uppercase;
        }
        .images-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .images-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .images-table th {
            background: #f8fafc;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #e2e8f0;
        }
        .images-table td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .oversized {
            background-color: #fee;
        }
        .badge-danger {
            background: #ef4444;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-success {
            background: #10b981;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }
        .result-item {
            padding: 0.5rem;
            border-bottom: 1px solid #e2e8f0;
        }
    </style>
</head>
<body class="admin-body">
    <!-- Barra de topo mobile -->
    <div class="mobile-topbar">
        <h1 class="mobile-title">Otimizar Imagens</h1>
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
                <a href="optimize-images.php" class="nav-link active">
                    <span class="nav-icon">🖼️</span>
                    <span>Otimizar Imagens</span>
                </a>
                <a href="regenerate-all.php" class="nav-link">
                    <span class="nav-icon">🔄</span>
                    <span>Regenerar Arquivos</span>
                </a>
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
            <div class="admin-header">
                <h1>📊 Otimização de Imagens para WhatsApp</h1>
                <p>WhatsApp exige imagens menores que <strong>600 KB</strong> e com no mínimo <strong>300px de largura</strong></p>
            </div>

        <?php if (!empty($results)): ?>
        <div class="alert alert-success">
            <strong>✅ Otimização concluída!</strong><br>
            <?= $optimizedCount ?> imagem(ns) otimizada(s) com sucesso.
            <details style="margin-top: 1rem;">
                <summary style="cursor: pointer; font-weight: 600;">Ver detalhes</summary>
                <div style="margin-top: 0.5rem;">
                    <?php foreach ($results as $result): ?>
                        <div class="result-item">
                            <strong><?= htmlspecialchars($result['file']) ?></strong>:
                            <?php if ($result['status'] === 'ok'): ?>
                                ✅ Já estava otimizado (<?= $result['original'] ?> KB)
                            <?php elseif ($result['status'] === 'converted'): ?>
                                🔄 <?= $result['original'] ?> KB → <?= $result['optimized'] ?> KB
                                <span style="color: #2563eb;">(<?= $result['note'] ?>)</span>
                            <?php else: ?>
                                📉 <?= $result['original'] ?> KB → <?= $result['optimized'] ?> KB (qualidade <?= $result['quality'] ?>%)
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </details>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>❌ Erros encontrados:</strong><br>
            <ul style="margin: 0.5rem 0 0 1.5rem;">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= count($images) ?></div>
                <div class="stat-label">Total de Imagens</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #ef4444;"><?= $oversizedCount ?></div>
                <div class="stat-label">Acima de 600 KB</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= round($totalSize / 1024 / 1024, 2) ?> MB</div>
                <div class="stat-label">Tamanho Total</div>
            </div>
        </div>

        <?php if ($oversizedCount > 0): ?>
        <form method="POST" style="margin-bottom: 2rem;">
            <button type="submit" name="optimize" class="btn btn-primary" style="font-size: 1.125rem; padding: 1rem 2rem;">
                🚀 Otimizar Todas as Imagens (<?= $oversizedCount ?> acima do limite)
            </button>
            <p style="margin-top: 0.5rem; color: #64748b; font-size: 0.875rem;">
                ⚠️ Backup automático será criado. O processo pode levar alguns minutos.
            </p>
        </form>
        <?php endif; ?>

        <div class="images-table">
            <table>
                <thead>
                    <tr>
                        <th>Arquivo</th>
                        <th>Tamanho</th>
                        <th>Status WhatsApp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($images as $img): ?>
                    <tr class="<?= $img['oversized'] ? 'oversized' : '' ?>">
                        <td>
                            <code><?= htmlspecialchars($img['name']) ?></code>
                        </td>
                        <td>
                            <strong><?= $img['size'] ?> KB</strong>
                        </td>
                        <td>
                            <?php if ($img['oversized']): ?>
                                <span class="badge-danger">❌ Muito grande</span>
                            <?php else: ?>
                                <span class="badge-success">✅ OK</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 2rem; padding: 1.5rem; background: #f8fafc; border-radius: 12px;">
            <h3>📖 Requisitos do WhatsApp</h3>
            <ul style="margin-left: 1.5rem;">
                <li><strong>Tamanho:</strong> Imagem deve ter menos de 600 KB</li>
                <li><strong>Dimensões:</strong> Largura mínima de 300px</li>
                <li><strong>Proporção:</strong> Máximo 4:1 (largura/altura)</li>
                <li><strong>Formato:</strong> PNG, JPEG, WebP ou GIF</li>
            </ul>
            <p style="margin-top: 1rem; color: #64748b;">
                💡 Após otimizar, limpe o cache do WhatsApp usando o 
                <a href="https://developers.facebook.com/tools/debug/" target="_blank" style="color: var(--primary-color);">Facebook Sharing Debugger</a>
            </p>
        </div>
        </main>
    </div>
    <script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
