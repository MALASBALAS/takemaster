<?php
/**
 * encryption.php
 * 
 * Sistema de encriptación AES-256-GCM para contenido sensible
 * 
 * ⚠️ IMPORTANTE:
 * - La clave maestra se carga desde variables de entorno (.env)
 * - NUNCA guardes la clave en el código
 * - Usar OPENSSL_RAW_DATA para máxima compatibilidad
 */

/**
 * Obtener clave de encriptación desde .env
 * 
 * @return string Clave de 32 bytes (256 bits) en formato hexadecimal
 * @throws Exception Si la clave no está configurada
 */
function get_encryption_key()
{
    $key_hex = getenv('ENCRYPTION_KEY');
    
    if (!$key_hex) {
        // Intenta cargar desde archivo .env si existe
        $env_file = __DIR__ . '/../.env';
        if (file_exists($env_file)) {
            $env_lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($env_lines as $line) {
                if (strpos($line, 'ENCRYPTION_KEY=') === 0) {
                    $key_hex = substr($line, strlen('ENCRYPTION_KEY='));
                    break;
                }
            }
        }
    }
    
    if (!$key_hex) {
        throw new Exception(
            "⚠️ ENCRYPTION_KEY no configurada.\n" .
            "Genera una clave: php -r \"echo bin2hex(random_bytes(32));\" > .env\n" .
            "Crea archivo .env con: ENCRYPTION_KEY=<resultado>"
        );
    }
    
    // Convertir de hex a binario
    $key = hex2bin($key_hex);
    if ($key === false || strlen($key) !== 32) {
        throw new Exception("ENCRYPTION_KEY debe ser 32 bytes en formato hexadecimal (64 caracteres)");
    }
    
    return $key;
}

/**
 * Encriptar contenido usando AES-256-GCM
 * 
 * @param string $plaintext Contenido a encriptar (típicamente JSON)
 * @return string Base64 del resultado: base64(nonce + ciphertext + tag)
 * @throws Exception Si falla la encriptación
 */
function encrypt_content($plaintext)
{
    try {
        $key = get_encryption_key();
        
        // GCM requiere nonce de 12 bytes (recomendado)
        $nonce = random_bytes(12);
        
        // Encriptar con AES-256-GCM
        // openssl_encrypt(data, method, key, options, iv, tag, aad)
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,  // Sin padding ni encoding
            $nonce,
            $tag,               // Tag de autenticación (salida)
            '',                 // AAD (Additional Authenticated Data) - vacío
            16                  // Longitud del tag (máxima seguridad)
        );
        
        if ($ciphertext === false) {
            throw new Exception("openssl_encrypt falló: " . openssl_error_string());
        }
        
        // Combinar: nonce (12) + tag (16) + ciphertext
        $encrypted_data = $nonce . $tag . $ciphertext;
        
        // Codificar en base64 para almacenamiento en BD
        return base64_encode($encrypted_data);
        
    } catch (Exception $e) {
        error_log("[encryption] Error al encriptar: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Desencriptar contenido encriptado con encrypt_content()
 * 
 * @param string $encrypted_base64 Base64 del resultado de encrypt_content()
 * @return string Contenido original desencriptado
 * @throws Exception Si falla la desencriptación o verificación de integridad
 */
function decrypt_content($encrypted_base64)
{
    try {
        $key = get_encryption_key();
        
        // Decodificar de base64
        $encrypted_data = base64_decode($encrypted_base64, true);
        if ($encrypted_data === false) {
            throw new Exception("Base64 inválido");
        }
        
        // Extraer componentes
        $nonce = substr($encrypted_data, 0, 12);
        $tag = substr($encrypted_data, 12, 16);
        $ciphertext = substr($encrypted_data, 28);
        
        if (strlen($nonce) !== 12 || strlen($tag) !== 16) {
            throw new Exception("Formato de encriptación inválido (nonce o tag)");
        }
        
        // Desencriptar con verificación de tag (autenticación)
        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,               // Verificar el tag
            ''                  // AAD debe ser el mismo que en encriptación
        );
        
        if ($plaintext === false) {
            throw new Exception("Desencriptación falló: posible manipulación de datos o clave incorrecta");
        }
        
        return $plaintext;
        
    } catch (Exception $e) {
        error_log("[encryption] Error al desencriptar: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Generar una clave de encriptación segura
 * 
 * Ejecutar: php -r "require 'funciones/encryption.php'; echo generate_encryption_key();"
 * 
 * @return string Clave en formato hexadecimal para archivo .env
 */
function generate_encryption_key()
{
    $key = random_bytes(32); // 256 bits
    return bin2hex($key);
}

/**
 * Verificar si una cadena está encriptada
 * 
 * Las cadenas encriptadas son base64 válido + tienen la estructura correcta
 * 
 * @param string $data Cadena a verificar
 * @return bool True si parece estar encriptada
 */
function is_encrypted($data)
{
    if (!is_string($data) || strlen($data) < 40) {
        return false;
    }
    
    // Intentar decodificar base64
    $decoded = base64_decode($data, true);
    if ($decoded === false) {
        return false;
    }
    
    // Estructura: 12 (nonce) + 16 (tag) + N (ciphertext)
    return strlen($decoded) >= 28;
}

/**
 * Helper para encriptar JSON de forma segura
 * 
 * @param array $data Array de datos
 * @return string JSON encriptado en base64
 */
function encrypt_json($data)
{
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return encrypt_content($json);
}

/**
 * Helper para desencriptar JSON
 * 
 * @param string $encrypted_base64 JSON encriptado en base64
 * @return array Array desencriptado
 */
function decrypt_json($encrypted_base64)
{
    $json = decrypt_content($encrypted_base64);
    return json_decode($json, true);
}
