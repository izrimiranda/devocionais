<?php
/**
 * Security Functions
 * CSRF protection, sanitização e validações
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Gerar token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validar token CSRF
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitizar string (remover tags HTML)
 */
function sanitizeString($str) {
    return htmlspecialchars(strip_tags($str), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitizar HTML (permitir tags básicas)
 * Não converte aspas para preservar formatação do texto
 */
function sanitizeHTML($html) {
    // Permitir apenas tags seguras
    $allowedTags = '<p><br><strong><em><u><h1><h2><h3><h4><ul><ol><li><a><blockquote><code><pre>';
    
    // Remove tags perigosas mas mantém o conteúdo intacto
    $cleaned = strip_tags($html, $allowedTags);
    
    // Remove atributos perigosos das tags permitidas
    $cleaned = preg_replace('/<([a-z]+)[^>]*?(on\w+=["\'][^"\']*["\'])[^>]*?>/i', '<$1>', $cleaned);
    
    return $cleaned;
}

/**
 * Sanitizar entrada de array recursivamente
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return sanitizeString($data);
}

/**
 * Validar email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validar URL
 */
function validateURL($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Validar upload de imagem
 */
function validateImageUpload($file) {
    $errors = [];
    
    // Verificar se houve erro no upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Erro no upload do arquivo.';
        return ['valid' => false, 'errors' => $errors];
    }
    
    // Verificar tamanho
    if ($file['size'] > MAX_IMAGE_SIZE) {
        $errors[] = 'Imagem muito grande. Tamanho máximo: ' . (MAX_IMAGE_SIZE / 1024 / 1024) . 'MB';
    }
    
    // Verificar MIME type real
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mimeType, $allowedMimes)) {
        $errors[] = 'Formato de imagem inválido. Permitidos: JPG, PNG, GIF, WEBP';
    }
    
    // Verificar extensão
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($extension, $allowedExtensions)) {
        $errors[] = 'Extensão de arquivo inválida.';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'mime_type' => $mimeType,
        'extension' => $extension
    ];
}

/**
 * Validar upload de áudio
 */
function validateAudioUpload($file) {
    $errors = [];
    
    // Verificar se houve erro no upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Erro no upload do arquivo.';
        return ['valid' => false, 'errors' => $errors];
    }
    
    // Verificar tamanho
    if ($file['size'] > MAX_AUDIO_SIZE) {
        $errors[] = 'Áudio muito grande. Tamanho máximo: ' . (MAX_AUDIO_SIZE / 1024 / 1024) . 'MB';
    }
    
    // Verificar MIME type real
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowedMimes = [
        'audio/mpeg',           // MP3
        'audio/mp3',            // MP3 (alternativo)
        'audio/mp4',            // M4A/AAC
        'audio/x-m4a',          // M4A (alternativo)
        'audio/aac',            // AAC
        'audio/ogg',            // OGG
        'audio/wav',            // WAV
        'video/mp4',            // MP4 (áudio exportado do WhatsApp)
        'application/mp4',      // MP4 (alternativo)
        'audio/x-mp4',          // MP4 (alternativo)
        'audio/mp4a-latm',      // AAC LATM
        'audio/mpeg4-generic'   // MPEG-4 genérico
    ];
    
    // Verificar extensão do arquivo
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['mp3', 'mp4', 'm4a', 'aac', 'ogg', 'wav'];
    
    // Permitir se MIME type está na lista OU se extensão é válida e arquivo é de áudio
    $validMime = in_array($mimeType, $allowedMimes);
    $validExtension = in_array($extension, $allowedExtensions);
    
    // Para arquivos MP4, aceitar video/mp4 se a extensão for de áudio
    if ($mimeType === 'video/mp4' && in_array($extension, ['mp4', 'm4a'])) {
        $validMime = true;
    }
    
    if (!$validMime && !$validExtension) {
        $errors[] = 'Formato de áudio inválido. Permitidos: MP3, MP4/M4A, AAC, OGG, WAV (Detectado: ' . $mimeType . ')';
    }
    
    // Remover verificação de extensão duplicada (já está incluída acima)
    /*
    // Verificar extensão
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['mp3', 'mp4', 'm4a', 'aac', 'ogg', 'wav'];
    if (!in_array($extension, $allowedExtensions)) {
        $errors[] = 'Extensão de arquivo inválida.';
    }
    */
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'mime_type' => $mimeType,
        'extension' => $extension
    ];
}

/**
 * Gerar nome de arquivo seguro e único
 */
function generateSecureFilename($originalName) {
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $hash = bin2hex(random_bytes(16));
    return $hash . '.' . $extension;
}

/**
 * Prevenir path traversal
 */
function sanitizePath($path) {
    $path = str_replace(['../', '..\\'], '', $path);
    return preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '', $path);
}
