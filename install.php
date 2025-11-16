<?php
/**
 * Script de Instalação
 * Verificar requisitos e criar usuário admin
 */

require_once __DIR__ . '/config/db.php';

$errors = [];
$success = false;
$warnings = [];

// Verificar requisitos
if (version_compare(PHP_VERSION, '7.4.0') < 0) {
    $errors[] = 'PHP 7.4 ou superior é necessário. Você está usando: ' . PHP_VERSION;
}

if (!extension_loaded('pdo')) {
    $errors[] = 'Extensão PDO não encontrada.';
}

if (!extension_loaded('pdo_mysql')) {
    $errors[] = 'Extensão PDO MySQL não encontrada.';
}

// Criar diretórios se não existirem e verificar permissões
$directories = [
    '/uploads/images' => 'Uploads de imagens',
    '/uploads/audio' => 'Uploads de áudio',
    '/devocionais' => 'Devocionais gerados'
];

foreach ($directories as $dir => $description) {
    $fullPath = __DIR__ . $dir;
    
    // Tentar criar diretório se não existir
    if (!is_dir($fullPath)) {
        if (@mkdir($fullPath, 0755, true)) {
            $warnings[] = "Diretório $dir criado automaticamente.";
        } else {
            $errors[] = "Não foi possível criar o diretório $dir. Crie manualmente e defina permissão 755.";
            continue;
        }
    }
    
    // Verificar permissão de escrita
    if (!is_writable($fullPath)) {
        // Tentar corrigir permissão
        if (@chmod($fullPath, 0755)) {
            $warnings[] = "Permissões do diretório $dir ajustadas automaticamente.";
        } else {
            $errors[] = "Diretório $dir não tem permissão de escrita. Execute: chmod 755 $fullPath";
        }
    }
}

// Processar criação de admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $email = $_POST['email'] ?? '';
    
    if ($username && $password && strlen($password) >= 6) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare('INSERT INTO admin_users (username, password_hash, email) VALUES (?, ?, ?)');
            if ($stmt->execute([$username, $hash, $email])) {
                $success = true;
            }
        } catch (PDOException $e) {
            $errors[] = 'Erro ao criar usuário: ' . $e->getMessage();
        }
    } else {
        $errors[] = 'Preencha todos os campos. Senha mínima: 6 caracteres.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - Devocionais</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Instalação do Sistema</h1>
                <p>Devocionais - Pr. Luciano Miranda</p>
            </div>
            
            <?php if (!empty($warnings)): ?>
            <div class="alert" style="background: #feebc8; color: #7c2d12; border-left: 4px solid #ed8936; padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <strong>Avisos:</strong>
                <ul style="margin: 0.5rem 0 0 1rem;">
                    <?php foreach ($warnings as $warning): ?>
                        <li><?= htmlspecialchars($warning) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <strong>Erros encontrados:</strong>
                <ul style="margin: 0.5rem 0 0 1rem;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <p><strong>Instalação concluída!</strong></p>
                <p>Usuário admin criado com sucesso.</p>
                <p style="margin-top: 1rem;">
                    <a href="admin/login.php" class="btn-primary btn-block" style="text-align: center;">
                        Fazer Login
                    </a>
                </p>
                <p style="margin-top: 1rem; font-size: 0.875rem; color: #7c2d12;">
                    <strong>IMPORTANTE:</strong> Delete o arquivo install.php por segurança!
                </p>
            </div>
            <?php else: ?>
            
            <h3 style="margin: 1.5rem 0 1rem; color: #1a202c;">Verificação de Requisitos</h3>
            <ul style="list-style: none; padding: 0;">
                <li style="padding: 0.5rem 0; color: <?= version_compare(PHP_VERSION, '7.4.0') >= 0 ? '#22543d' : '#742a2a' ?>">
                    <?= version_compare(PHP_VERSION, '7.4.0') >= 0 ? '✓' : '✗' ?> PHP 7.4+ (atual: <?= PHP_VERSION ?>)
                </li>
                <li style="padding: 0.5rem 0; color: <?= extension_loaded('pdo') ? '#22543d' : '#742a2a' ?>">
                    <?= extension_loaded('pdo') ? '✓' : '✗' ?> PDO
                </li>
                <li style="padding: 0.5rem 0; color: <?= extension_loaded('pdo_mysql') ? '#22543d' : '#742a2a' ?>">
                    <?= extension_loaded('pdo_mysql') ? '✓' : '✗' ?> PDO MySQL
                </li>
                <?php foreach ($directories as $dir => $description): 
                    $fullPath = __DIR__ . $dir;
                    $exists = is_dir($fullPath);
                    $writable = $exists && is_writable($fullPath);
                    $color = $writable ? '#22543d' : ($exists ? '#7c2d12' : '#742a2a');
                    $icon = $writable ? '✓' : '✗';
                    $status = $writable ? '(escrita OK)' : ($exists ? '(sem permissão)' : '(não existe)');
                ?>
                <li style="padding: 0.5rem 0; color: <?= $color ?>">
                    <?= $icon ?> <?= $dir ?> <?= $status ?>
                </li>
                <?php endforeach; ?>
            </ul>
            
            <?php if (!empty($errors)): ?>
            <div style="background: #feebc8; padding: 1rem; border-radius: 8px; margin-top: 1rem;">
                <strong>💡 Dica:</strong> Se os diretórios não puderem ser criados automaticamente, execute via terminal/SSH:
                <pre style="background: #1a202c; color: #fff; padding: 0.75rem; border-radius: 6px; margin-top: 0.5rem; overflow-x: auto; font-size: 0.875rem;">mkdir -p uploads/images uploads/audio devocionais
chmod -R 755 uploads devocionais</pre>
            </div>
            <?php endif; ?>
            
            <?php if (empty($errors)): ?>
            <h3 style="margin: 1.5rem 0 1rem; color: #1a202c;">Criar Usuário Admin</h3>
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label>Usuário</label>
                    <input type="text" name="username" required autofocus>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Senha (mínimo 6 caracteres)</label>
                    <input type="password" name="password" required minlength="6">
                </div>
                <button type="submit" class="btn-primary btn-block">Criar Admin e Concluir</button>
            </form>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
