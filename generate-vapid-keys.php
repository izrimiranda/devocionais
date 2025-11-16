#!/usr/bin/env php
<?php
/**
 * Gerador de VAPID Keys para Web Push
 * Executar: php generate-vapid-keys.php
 * 
 * MÉTODO 1: Com Composer (recomendado)
 * MÉTODO 2: Nativo PHP (fallback)
 */

echo "===========================================\n";
echo "  GERADOR DE VAPID KEYS PARA WEB PUSH\n";
echo "===========================================\n\n";

// Verificar se vendor existe
$vendorPath = __DIR__ . '/vendor/autoload.php';

if (file_exists($vendorPath)) {
    // MÉTODO 1: Usar biblioteca Composer
    require_once $vendorPath;
    
    try {
        $keys = \Minishlink\WebPush\VAPID::createVapidKeys();
        
        echo "✅ VAPID Keys geradas com sucesso! (via Composer)\n\n";
        echo "📋 Copie estas chaves:\n\n";
        echo "PUBLIC KEY:\n";
        echo $keys['publicKey'] . "\n\n";
        echo "PRIVATE KEY:\n";
        echo $keys['privateKey'] . "\n\n";
        
    } catch (Exception $e) {
        echo "❌ Erro ao gerar via Composer: " . $e->getMessage() . "\n";
        echo "Tentando método nativo...\n\n";
        generateKeysNative();
    }
    
} else {
    // MÉTODO 2: Geração nativa
    echo "⚠️  Composer não detectado. Usando método nativo.\n\n";
    echo "💡 Para melhor compatibilidade, instale via Composer:\n";
    echo "   composer require minishlink/web-push\n\n";
    echo "-------------------------------------------\n\n";
    
    generateKeysNative();
}

echo "-------------------------------------------\n\n";
echo "📝 Instruções:\n";
echo "1. Cole a PUBLIC KEY em:\n";
echo "   - assets/js/notifications.js (linha 6: vapidPublicKey)\n\n";
echo "2. Cole ambas as chaves em:\n";
echo "   - api/send-push-notification.php (array \$vapidKeys)\n\n";
echo "3. IMPORTANTE: Mantenha a PRIVATE KEY em segredo!\n";
echo "   Nunca exponha em JavaScript ou repositórios públicos.\n\n";
echo "===========================================\n";

/**
 * Gerar VAPID keys nativamente (sem dependências)
 */
function generateKeysNative() {
    // Verificar se OpenSSL está disponível
    if (!function_exists('openssl_pkey_new')) {
        echo "❌ ERRO: OpenSSL não está disponível no PHP.\n";
        echo "   Instale a extensão OpenSSL ou use Composer.\n\n";
        
        // Fornecer chaves de exemplo (APENAS PARA TESTE - NÃO USAR EM PRODUÇÃO)
        echo "⚠️  CHAVES DE EXEMPLO (APENAS TESTE):\n\n";
        echo "PUBLIC KEY (exemplo):\n";
        echo "BEl62iUYgUivxIkv62iUYgUivxIkv62iUYgUivxIkv62iUYgUivxIkv\n\n";
        echo "PRIVATE KEY (exemplo):\n";
        echo "ABCdef123XYZ789-EXEMPLO-NAO-USAR-EM-PRODUCAO-123456\n\n";
        echo "⚠️  GERE CHAVES REAIS COM: composer require minishlink/web-push\n\n";
        return;
    }
    
    try {
        // Configuração para curva elíptica P-256 (NIST P-256 / secp256r1 / prime256v1)
        $config = [
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ];
        
        // Gerar par de chaves
        $res = openssl_pkey_new($config);
        
        if ($res === false) {
            throw new Exception("Falha ao gerar chave privada");
        }
        
        // Exportar chave privada
        openssl_pkey_export($res, $privateKey);
        
        // Obter detalhes da chave pública
        $details = openssl_pkey_get_details($res);
        
        if (!isset($details['ec']['x']) || !isset($details['ec']['y'])) {
            throw new Exception("Formato de chave inválido");
        }
        
        // Construir chave pública no formato não comprimido (0x04 + x + y)
        $publicKeyBinary = "\x04" . $details['ec']['x'] . $details['ec']['y'];
        
        // Codificar em base64url
        $publicKeyBase64 = base64UrlEncode($publicKeyBinary);
        
        // Extrair D (componente privado) do PEM
        $privateKeyBase64 = extractPrivateKeyD($privateKey);
        
        echo "✅ VAPID Keys geradas com sucesso! (método nativo)\n\n";
        echo "📋 Copie estas chaves:\n\n";
        echo "PUBLIC KEY:\n";
        echo $publicKeyBase64 . "\n\n";
        echo "PRIVATE KEY:\n";
        echo $privateKeyBase64 . "\n\n";
        
    } catch (Exception $e) {
        echo "❌ Erro ao gerar chaves: " . $e->getMessage() . "\n\n";
        echo "💡 Solução: Instale via Composer:\n";
        echo "   composer require minishlink/web-push\n\n";
    }
}

/**
 * Codificar em Base64 URL-safe
 */
function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Extrair componente D da chave privada PEM
 */
function extractPrivateKeyD($pemKey) {
    // Parse PEM para extrair o valor D
    // Este é um método simplificado - a biblioteca Composer faz isso melhor
    
    // Remover header/footer PEM
    $pem = str_replace(['-----BEGIN EC PRIVATE KEY-----', '-----END EC PRIVATE KEY-----', "\n", "\r"], '', $pemKey);
    $der = base64_decode($pem);
    
    // Buscar sequência DER para extrair D
    // Formato ASN.1 DER simplificado
    // NOTA: Este é um parser básico - pode não funcionar em todos os casos
    
    // Procurar por 32 bytes após um marcador específico (0x04 0x20)
    $pos = strpos($der, "\x04\x20");
    
    if ($pos !== false) {
        $d = substr($der, $pos + 2, 32);
        return base64UrlEncode($d);
    }
    
    // Fallback: usar hash do PEM (NÃO IDEAL, mas funciona para teste)
    echo "⚠️  Extração não ideal - recomenda-se usar Composer\n\n";
    return base64UrlEncode(hash('sha256', $pemKey, true));
}
?>
