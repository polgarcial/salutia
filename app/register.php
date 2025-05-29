<?php
// Desactivar la salida de errores HTML para evitar que se mezcle con JSON
error_reporting(0);
ini_set('display_errors', 0);

// Asegurarse de que no haya salida antes de los headers
ob_start();

// Configuración de CORS para permitir solicitudes desde el frontend
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Manejar solicitudes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Ruta absoluta a la configuración de la base de datos
$databasePath = __DIR__ . '/../config/database.php';

// Verificar si el archivo existe
if (!file_exists($databasePath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Archivo de configuración de base de datos no encontrado', 'path' => $databasePath]);
    exit;
}

// Incluir la configuración de la base de datos
require_once $databasePath;

// Función para devolver respuesta JSON de forma segura
function sendJsonResponse($status, $data) {
    // Limpiar cualquier salida anterior
    if (ob_get_length()) ob_clean();
    
    // Asegurarse de que no haya salida después
    ob_start();
    
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    
    ob_end_flush();
    exit;
}

// Verificar si es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(405, ['error' => 'Método no permitido']);
}

// Obtener los datos enviados
$input = file_get_contents("php://input");

// Registrar los datos recibidos para depuración
error_log("Datos recibidos: " . $input);

// Decodificar JSON
$data = json_decode($input, true);

// Verificar si hubo errores en la decodificación JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    sendJsonResponse(400, [
        'error' => 'Error al decodificar JSON: ' . json_last_error_msg(),
        'input' => $input
    ]);
}

// Verificar que se han recibido todos los campos requeridos
if (
    !isset($data['email']) || 
    !isset($data['password']) || 
    !isset($data['first_name']) || 
    !isset($data['last_name'])
) {
    sendJsonResponse(400, ['error' => 'Faltan campos requeridos']);
}

try {
    // Obtener conexión a la base de datos
    $db = getDbConnection();
    
    // Verificar si el email ya existe
    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->bindParam(':email', $data['email']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        sendJsonResponse(409, ['error' => 'El correo electrónico ya está registrado']);
    }
    
    // Encriptar la contraseña
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // Preparar la consulta SQL para insertar el nuevo usuario
    $stmt = $db->prepare("
        INSERT INTO users (
            email, 
            password, 
            first_name, 
            last_name, 
            date_of_birth, 
            phone, 
            address, 
            role
        ) VALUES (
            :email, 
            :password, 
            :first_name, 
            :last_name, 
            :date_of_birth, 
            :phone, 
            :address, 
            :role
        )
    ");
    
    // Asignar valores a los parámetros
    $role = isset($data['role']) ? $data['role'] : 'patient';
    
    $stmt->bindParam(':email', $data['email']);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':first_name', $data['first_name']);
    $stmt->bindParam(':last_name', $data['last_name']);
    $stmt->bindParam(':date_of_birth', $data['date_of_birth'] ?? null);
    $stmt->bindParam(':phone', $data['phone'] ?? null);
    $stmt->bindParam(':address', $data['address'] ?? null);
    $stmt->bindParam(':role', $role);
    
    // Ejecutar la consulta
    if ($stmt->execute()) {
        // Obtener el ID del usuario recién creado
        $userId = $db->lastInsertId();
        
        // Obtener los datos del usuario para la respuesta
        $stmt = $db->prepare("
            SELECT id, email, first_name, last_name, role 
            FROM users 
            WHERE id = :id
        ");
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Generar un token JWT simple (en producción deberías usar una biblioteca JWT)
        $issuedAt = time();
        $expirationTime = $issuedAt + 3600; // 1 hora
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'data' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ];
        
        // Crear el token (versión simplificada)
        $jwt_secret = 'your_jwt_secret_key_here'; // Deberías usar la constante de config.php
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload = base64_encode(json_encode($payload));
        $signature = base64_encode(hash_hmac('sha256', "$header.$payload", $jwt_secret, true));
        $token = "$header.$payload.$signature";
        
        // Devolver respuesta exitosa
        // Limpiar cualquier salida anterior
        if (ob_get_length()) ob_clean();
        
        sendJsonResponse(201, [
            'success' => true,
            'message' => 'Usuario registrado correctamente',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ]);
    } else {
        sendJsonResponse(500, ['error' => 'No se pudo registrar el usuario']);
    }
} catch (PDOException $e) {
    // Registrar el error para depuración
    error_log('Error en register.php: ' . $e->getMessage());
    
    // Limpiar cualquier salida anterior
    if (ob_get_length()) ob_clean();
    
    sendJsonResponse(500, ['error' => 'Error en el servidor: ' . $e->getMessage()]);
}
