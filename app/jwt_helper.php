<?php
require_once __DIR__ . '/jwt_config.php';

function generateJWT($payload) {
    global $JWT_SECRET_KEY;
    
    // Crear el encabezado
    $header = json_encode([
        'typ' => 'JWT',
        'alg' => 'HS256'
    ]);
    
    // Codificar el encabezado y el payload
    $base64UrlHeader = base64url_encode($header);
    $base64UrlPayload = base64url_encode(json_encode($payload));
    
    // Crear la firma
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $JWT_SECRET_KEY, true);
    $base64UrlSignature = base64url_encode($signature);
    
    // Crear el token
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

function decodeJWT($token) {
    global $JWT_SECRET_KEY;
    
    $tokenParts = explode('.', $token);
    if (count($tokenParts) != 3) {
        return false;
    }

    $header = base64url_decode($tokenParts[0]);
    $payload = base64url_decode($tokenParts[1]);
    $signatureProvided = $tokenParts[2];

    // Recrear la firma
    $base64UrlHeader = base64url_encode($header);
    $base64UrlPayload = base64url_encode($payload);
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $JWT_SECRET_KEY, true);
    $base64UrlSignature = base64url_encode($signature);

    if ($base64UrlSignature !== $signatureProvided) {
        return false;
    }

    $decodedPayload = json_decode($payload);
    
    // Verificar expiración
    if (isset($decodedPayload->exp) && $decodedPayload->exp < time()) {
        return false;
    }

    return $decodedPayload;
}

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
}
?>
