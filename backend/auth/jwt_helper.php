<?php
/**
 * Funciones para manejar la autenticación con JWT
 */

// Clave secreta para firmar los tokens (en producción, usar una clave más segura y almacenarla en variables de entorno)
define('JWT_SECRET', 'salutia_secret_key_2025');

/**
 * Genera un token JWT
 * 
 * @param array $payload Datos a incluir en el token
 * @param int $expiry Tiempo de expiración en segundos (por defecto 24 horas)
 * @return string Token JWT generado
 */
function generateJWT($payload, $expiry = 86400) {
    // Cabecera del token
    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT'
    ];
    
    // Codificar cabecera
    $header_encoded = base64_encode(json_encode($header));
    
    // Añadir tiempo de expiración al payload
    $payload['exp'] = time() + $expiry;
    $payload['iat'] = time();
    
    // Codificar payload
    $payload_encoded = base64_encode(json_encode($payload));
    
    // Crear firma
    $signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", JWT_SECRET, true);
    $signature_encoded = base64_encode($signature);
    
    // Crear token
    return "$header_encoded.$payload_encoded.$signature_encoded";
}

/**
 * Decodifica y verifica un token JWT
 * 
 * @param string $token Token JWT a verificar
 * @return object|false Payload decodificado o false si el token es inválido
 */
function decodeJWT($token) {
    // Dividir token en sus partes
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }
    
    list($header_encoded, $payload_encoded, $signature_encoded) = $parts;
    
    // Verificar firma
    $signature = base64_decode($signature_encoded);
    $expected_signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", JWT_SECRET, true);
    
    if (!hash_equals($expected_signature, $signature)) {
        return false;
    }
    
    // Decodificar payload
    $payload = json_decode(base64_decode($payload_encoded));
    
    // Verificar expiración
    if (isset($payload->exp) && $payload->exp < time()) {
        return false;
    }
    
    return $payload;
}
