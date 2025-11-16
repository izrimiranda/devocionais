<?php
/**
 * Admin - Login
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/security.php';

// Se já estiver logado, redirecionar
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!validateCSRFToken($csrfToken)) {
        $error = 'Token de segurança inválido.';
    } else {
        $result = doLogin($username, $password);
        
        if ($result['success']) {
            header('Location: dashboard.php');
            exit;
        } else {
            $error = $result['message'];
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
    <title>Login - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Painel Administrativo</h1>
                <p>Devocionais - Pr. Luciano Miranda</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                
                <div class="form-group">
                    <label for="username">Usuário</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required 
                        autofocus
                        autocomplete="username"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        autocomplete="current-password"
                    >
                </div>
                
                <button type="submit" class="btn-primary btn-block">
                    Entrar
                </button>
            </form>
            
            <div class="login-footer">
                <a href="../">← Voltar ao site</a>
            </div>
        </div>
    </div>
</body>
</html>
