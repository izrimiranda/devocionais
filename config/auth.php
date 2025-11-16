<?php
/**
 * Authentication Functions
 * Funções de autenticação, login, logout e proteção de rotas
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

/**
 * Verifica se o usuário está autenticado
 */
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Requer autenticação (redireciona para login se não autenticado)
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
}

/**
 * Verifica credenciais de login
 */
function checkCredentials($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admin_users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        return $user;
    }
    
    return false;
}

/**
 * Realizar login
 */
function doLogin($username, $password) {
    // Verificar rate limiting
    if (!checkRateLimit($_SERVER['REMOTE_ADDR'])) {
        return [
            'success' => false,
            'message' => 'Muitas tentativas de login. Aguarde alguns minutos.'
        ];
    }
    
    $user = checkCredentials($username, $password);
    
    if ($user) {
        // Regenerar session ID para prevenir session fixation
        session_regenerate_id(true);
        
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['login_time'] = time();
        
        // Limpar tentativas de login
        clearLoginAttempts($_SERVER['REMOTE_ADDR']);
        
        return [
            'success' => true,
            'message' => 'Login realizado com sucesso!'
        ];
    } else {
        // Registrar tentativa falha
        registerLoginAttempt($_SERVER['REMOTE_ADDR']);
        
        return [
            'success' => false,
            'message' => 'Usuário ou senha incorretos.'
        ];
    }
}

/**
 * Realizar logout
 */
function doLogout() {
    $_SESSION = [];
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
}

/**
 * Verificar rate limiting de tentativas de login
 */
function checkRateLimit($ip) {
    global $pdo;
    
    $maxAttempts = (int)getenv('MAX_LOGIN_ATTEMPTS') ?: 5;
    $lockoutTime = (int)getenv('LOGIN_LOCKOUT_TIME') ?: 900; // 15 minutos
    
    // Criar tabela se não existir (melhor fazer isso no database.sql)
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            attempt_time DATETIME NOT NULL,
            INDEX idx_ip_time (ip_address, attempt_time)
        ) ENGINE=InnoDB");
    } catch (PDOException $e) {
        // Tabela já existe
    }
    
    // Limpar tentativas antigas
    $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL ? SECOND)');
    $stmt->execute([$lockoutTime]);
    
    // Contar tentativas recentes
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM login_attempts WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)');
    $stmt->execute([$ip, $lockoutTime]);
    $result = $stmt->fetch();
    
    return $result['count'] < $maxAttempts;
}

/**
 * Registrar tentativa de login
 */
function registerLoginAttempt($ip) {
    global $pdo;
    
    $stmt = $pdo->prepare('INSERT INTO login_attempts (ip_address, attempt_time) VALUES (?, NOW())');
    $stmt->execute([$ip]);
}

/**
 * Limpar tentativas de login após sucesso
 */
function clearLoginAttempts($ip) {
    global $pdo;
    
    $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE ip_address = ?');
    $stmt->execute([$ip]);
}

/**
 * Criar hash de senha
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}
