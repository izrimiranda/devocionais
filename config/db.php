<?php
/**
 * Database Connection
 * Carrega variáveis de ambiente e estabelece conexão PDO
 */

// Carregar variáveis de ambiente
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        // Remover aspas se existirem
        if (preg_match('/^"(.*)"$/', $value, $matches)) {
            $value = $matches[1];
        }
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

// Carregar .env
loadEnv(__DIR__ . '/../.env');

// Definir constantes do banco
define('DB_HOST', getenv('DB_HOST') ?: 'srv723.hstgr.io');
define('DB_NAME', getenv('DB_NAME') ?: 'u959347836_db_luciano');
define('DB_USER', getenv('DB_USER') ?: 'u959347836_luciano');
define('DB_PASS', getenv('DB_PASS') ?: 'pBZzB:9UB8+');
define('DB_CHARSET', 'utf8mb4');

// Timezone
date_default_timezone_set(getenv('TIMEZONE') ?: 'America/Sao_Paulo');

// Criar conexão PDO
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (PDOException $e) {
    // Em produção, não expor detalhes do erro
    if (getenv('APP_ENV') === 'production') {
        die('Erro ao conectar ao banco de dados. Entre em contato com o administrador.');
    } else {
        die('Erro de conexão: ' . $e->getMessage());
    }
}

// Definir constantes adicionais
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost');
define('SITE_NAME', getenv('SITE_NAME') ?: 'Devocionais - Pr. Luciano Miranda');
define('MAX_IMAGE_SIZE', (int)getenv('MAX_IMAGE_SIZE') ?: 2097152); // 2MB
define('MAX_AUDIO_SIZE', (int)getenv('MAX_AUDIO_SIZE') ?: 15728640); // 15MB

return $pdo;
