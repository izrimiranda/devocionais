<?php
/**
 * Otimizador Automático de Imagens
 * Otimiza imagens automaticamente no upload/edição
 * Requisito: WhatsApp < 600 KB
 */

/**
 * Otimizar imagem automaticamente
 * @param string $filePath Caminho completo da imagem
 * @return array Resultado da otimização
 */
function optimizeImageAuto($filePath) {
    if (!file_exists($filePath)) {
        return [
            'success' => false,
            'error' => 'Arquivo não encontrado'
        ];
    }
    
    $originalSize = filesize($filePath);
    $originalSizeKB = round($originalSize / 1024, 2);
    $targetSize = 600000; // 600 KB para WhatsApp
    
    // Se já está otimizado, retornar sucesso
    if ($originalSize < $targetSize) {
        return [
            'success' => true,
            'optimized' => false,
            'message' => 'Imagem já está otimizada',
            'original_size_kb' => $originalSizeKB,
            'final_size_kb' => $originalSizeKB
        ];
    }
    
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    // Carregar imagem
    try {
        switch ($ext) {
            case 'png':
                $image = @imagecreatefrompng($filePath);
                break;
            case 'jpg':
            case 'jpeg':
                $image = @imagecreatefromjpeg($filePath);
                break;
            case 'gif':
                $image = @imagecreatefromgif($filePath);
                break;
            case 'webp':
                $image = @imagecreatefromwebp($filePath);
                break;
            default:
                return [
                    'success' => false,
                    'error' => 'Formato não suportado: ' . $ext
                ];
        }
        
        if (!$image) {
            return [
                'success' => false,
                'error' => 'Erro ao carregar imagem'
            ];
        }
        
        // Dimensões originais
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);
        
        // Criar backup
        $backupPath = $filePath . '.original';
        @copy($filePath, $backupPath);
        
        $optimized = false;
        $finalSizeKB = $originalSizeKB;
        
        // ESTRATÉGIA 1: Reduzir qualidade (sem redimensionar)
        for ($quality = 85; $quality >= 40; $quality -= 5) {
            if ($ext === 'png') {
                // PNG: converter para JPEG se necessário
                imagejpeg($image, $filePath, $quality);
            } else {
                imagejpeg($image, $filePath, $quality);
            }
            
            $currentSize = filesize($filePath);
            
            if ($currentSize < $targetSize) {
                $optimized = true;
                $finalSizeKB = round($currentSize / 1024, 2);
                break;
            }
        }
        
        // ESTRATÉGIA 2: Redimensionar mantendo aspect ratio (max 1200px)
        if (!$optimized) {
            $maxDimension = 1200;
            
            if ($originalWidth > $maxDimension || $originalHeight > $maxDimension) {
                if ($originalWidth > $originalHeight) {
                    $newWidth = $maxDimension;
                    $newHeight = (int)($originalHeight * ($maxDimension / $originalWidth));
                } else {
                    $newHeight = $maxDimension;
                    $newWidth = (int)($originalWidth * ($maxDimension / $originalHeight));
                }
                
                $resized = imagecreatetruecolor($newWidth, $newHeight);
                
                // Preservar transparência para PNG
                if ($ext === 'png') {
                    imagealphablending($resized, false);
                    imagesavealpha($resized, true);
                }
                
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
                imagedestroy($image);
                $image = $resized;
                
                // Tentar novamente com qualidade reduzida
                for ($quality = 85; $quality >= 40; $quality -= 5) {
                    imagejpeg($image, $filePath, $quality);
                    
                    $currentSize = filesize($filePath);
                    
                    if ($currentSize < $targetSize) {
                        $optimized = true;
                        $finalSizeKB = round($currentSize / 1024, 2);
                        break;
                    }
                }
            }
        }
        
        // ESTRATÉGIA 3: Converter PNG para JPEG (se ainda grande)
        if (!$optimized && $ext === 'png') {
            // Converter para JPEG com fundo branco
            $newImage = imagecreatetruecolor(imagesx($image), imagesy($image));
            $white = imagecolorallocate($newImage, 255, 255, 255);
            imagefill($newImage, 0, 0, $white);
            imagecopy($newImage, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
            
            // Salvar como JPEG
            $newPath = preg_replace('/\.png$/i', '.jpg', $filePath);
            imagejpeg($newImage, $newPath, 75);
            
            imagedestroy($newImage);
            
            $currentSize = filesize($newPath);
            
            if ($currentSize < $targetSize) {
                // Remover PNG original e renomear JPEG
                @unlink($filePath);
                $filePath = $newPath;
                $optimized = true;
                $finalSizeKB = round($currentSize / 1024, 2);
                
                // Atualizar no banco de dados se necessário
                $ext = 'jpg';
            } else {
                @unlink($newPath); // Remover JPEG se não otimizou
            }
        }
        
        imagedestroy($image);
        
        // Se conseguiu otimizar, remover backup
        if ($optimized && file_exists($backupPath)) {
            @unlink($backupPath);
        }
        
        // Log da otimização
        error_log(sprintf(
            "Imagem otimizada: %s | Original: %s KB → Final: %s KB | Redução: %.1f%%",
            basename($filePath),
            $originalSizeKB,
            $finalSizeKB,
            (($originalSizeKB - $finalSizeKB) / $originalSizeKB) * 100
        ));
        
        return [
            'success' => true,
            'optimized' => true,
            'message' => 'Imagem otimizada automaticamente',
            'original_size_kb' => $originalSizeKB,
            'final_size_kb' => $finalSizeKB,
            'reduction_percent' => round((($originalSizeKB - $finalSizeKB) / $originalSizeKB) * 100, 1),
            'new_path' => $filePath,
            'extension_changed' => ($ext !== strtolower(pathinfo($filePath, PATHINFO_EXTENSION)))
        ];
        
    } catch (Exception $e) {
        // Restaurar backup se houver erro
        if (isset($backupPath) && file_exists($backupPath)) {
            @copy($backupPath, $filePath);
            @unlink($backupPath);
        }
        
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Wrapper para otimizar após upload
 * @param string $uploadedFilePath Caminho do arquivo recém-uploaded
 * @return array Resultado com novo caminho (se extensão mudou)
 */
function optimizeUploadedImage($uploadedFilePath) {
    $result = optimizeImageAuto($uploadedFilePath);
    
    // Se a extensão mudou (PNG → JPG), atualizar caminho
    if ($result['success'] && isset($result['extension_changed']) && $result['extension_changed']) {
        return [
            'success' => true,
            'new_path' => $result['new_path'],
            'optimization' => $result
        ];
    }
    
    return [
        'success' => $result['success'],
        'new_path' => $uploadedFilePath,
        'optimization' => $result
    ];
}
?>
