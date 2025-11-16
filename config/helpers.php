<?php
/**
 * Helper Functions
 * Funções auxiliares: slugs, formatação, manipulação de dados
 */

/**
 * Gerar slug a partir de string
 */
function generateSlug($text) {
    // Converter para minúsculas
    $text = strtolower($text);
    
    // Remover acentos
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    
    // Remover caracteres especiais
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    
    // Substituir espaços e múltiplos hífens por um único hífen
    $text = preg_replace('/[\s-]+/', '-', $text);
    
    // Remover hífens do início e fim
    $text = trim($text, '-');
    
    return $text;
}

/**
 * Verificar se slug é único
 */
function isSlugUnique($slug, $excludeId = null) {
    global $pdo;
    
    $sql = 'SELECT COUNT(*) as count FROM devotionals WHERE slug = ?';
    $params = [$slug];
    
    if ($excludeId !== null) {
        $sql .= ' AND id != ?';
        $params[] = $excludeId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    
    return $result['count'] === 0;
}

/**
 * Gerar slug único (adicionar número se necessário)
 */
function generateUniqueSlug($text, $excludeId = null) {
    $baseSlug = generateSlug($text);
    $slug = $baseSlug;
    $counter = 1;
    
    while (!isSlugUnique($slug, $excludeId)) {
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }
    
    return $slug;
}

/**
 * Formatar data em português
 */
function formatDatePtBr($date, $includeTime = false) {
    if (is_string($date)) {
        $date = new DateTime($date);
    }
    
    $months = [
        1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
        'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'
    ];
    
    $day = $date->format('d');
    $month = $months[(int)$date->format('m')];
    $year = $date->format('Y');
    
    $formatted = "$day de $month de $year";
    
    if ($includeTime) {
        $formatted .= ' às ' . $date->format('H:i');
    }
    
    return $formatted;
}

/**
 * Formatar data relativa (hoje, ontem, etc)
 */
function formatRelativeDate($date) {
    if (is_string($date)) {
        $date = new DateTime($date);
    }
    
    $now = new DateTime();
    $diff = $now->diff($date);
    
    if ($diff->days === 0) {
        return 'Hoje';
    } elseif ($diff->days === 1) {
        return 'Ontem';
    } elseif ($diff->days < 7) {
        return 'Há ' . $diff->days . ' dias';
    } else {
        return formatDatePtBr($date);
    }
}

/**
 * Truncar texto
 */
function truncateText($text, $maxLength = 150, $suffix = '...') {
    $text = strip_tags($text);
    
    if (mb_strlen($text) <= $maxLength) {
        return $text;
    }
    
    $truncated = mb_substr($text, 0, $maxLength);
    
    // Tentar não cortar no meio de uma palavra
    $lastSpace = mb_strrpos($truncated, ' ');
    if ($lastSpace !== false) {
        $truncated = mb_substr($truncated, 0, $lastSpace);
    }
    
    return $truncated . $suffix;
}

/**
 * Obter primeiro parágrafo do texto
 */
function getFirstParagraph($html) {
    // Remover tags exceto <p>
    $text = strip_tags($html, '<p>');
    
    // Pegar primeiro <p>
    if (preg_match('/<p[^>]*>(.*?)<\/p>/is', $text, $matches)) {
        return strip_tags($matches[1]);
    }
    
    // Se não houver <p>, retornar primeiros 200 caracteres
    return truncateText(strip_tags($html), 200);
}

/**
 * Converter quebras de linha em <br>
 */
function nl2br_safe($text) {
    return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
}

/**
 * Formatar tamanho de arquivo
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Gerar excerpt automático se não fornecido
 */
function generateExcerpt($content, $length = 200) {
    $text = strip_tags($content);
    return truncateText($text, $length);
}

/**
 * Redirecionar com mensagem
 */
function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header('Location: ' . $url);
    exit;
}

/**
 * Obter e limpar mensagem flash
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'text' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'info'
        ];
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return $message;
    }
    return null;
}

/**
 * Debug helper (remover em produção)
 */
function dd($var) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
    die();
}

/**
 * Obter URL base
 */
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = dirname($_SERVER['SCRIPT_NAME']);
    
    return $protocol . '://' . $host . rtrim($script, '/');
}

/**
 * Obter URL do devocional
 */
function getDevotionalUrl($slug) {
    return SITE_URL . '/devocionais/' . $slug . '.php';
}
