<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Habilitar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Función para registrar mensajes de depuración
function debug_log($message, $data = []) {
    $log_file = __DIR__ . '/debug_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "$timestamp - $message";
    
    if (!empty($data)) {
        $log_message .= ": " . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    file_put_contents($log_file, $log_message . "\n", FILE_APPEND);
}

// Manejar OPTIONS request para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Registrar inicio de la solicitud
debug_log('Inicio de solicitud doctor_auth.php', [
    'method' => $_SERVER['REQUEST_METHOD'], 
    'action' => isset($_GET['action']) ? $_GET['action'] : 'none'
]);

// Obtener acción solicitada
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Obtener datos del cuerpo de la solicitud para POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    debug_log('Datos recibidos', ['input' => $input]);
    $data = json_decode($input, true);
    
    if ($data === null) {
        debug_log('Error al decodificar JSON', ['error' => json_last_error_msg()]);
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Error en el formato de datos: ' . json_last_error_msg()]);
        exit();
    }
    
    debug_log('Datos decodificados', $data);
}

// Datos de médicos de prueba
$test_doctors = [
    [
        'id' => 1,
        'email' => 'doctor@gmail.com',
        'password' => password_hash('123456', PASSWORD_DEFAULT),
        'name' => 'Dr. Juan Pérez',
        'role' => 'doctor',
        'specialty' => 'Cardiología'
    ],
    [
        'id' => 2,
        'email' => 'doctora@gmail.com',
        'password' => password_hash('123456', PASSWORD_DEFAULT),
        'name' => 'Dra. María García',
        'role' => 'doctor',
        'specialty' => 'Pediatría'
    ]
];

// Función para generar un token JWT simple
function generateSimpleToken($payload) {
    $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64_encode(json_encode($payload));
    $signature = base64_encode(hash_hmac('sha256', "$header.$payload", 'salutia_secret_key', true));
    
    return "$header.$payload.$signature";
}

// Función para iniciar sesión de médico
function doctorLogin($data) {
    global $test_doctors;
    
    try {
        debug_log('Iniciando proceso de login de médico', $data);
        
        // Validar datos requeridos
        if (!isset($data['email']) || !isset($data['password'])) {
            debug_log('Faltan datos requeridos', ['email_set' => isset($data['email']), 'password_set' => isset($data['password'])]);
            return ['success' => false, 'message' => 'Faltan datos requeridos'];
        }
        
        // Buscar médico por email
        $found_doctor = null;
        foreach ($test_doctors as $doctor) {
            if ($doctor['email'] === $data['email']) {
                $found_doctor = $doctor;
                break;
            }
        }
        
        if ($found_doctor) {
            debug_log('Médico encontrado', ['id' => $found_doctor['id'], 'name' => $found_doctor['name']]);
            
            // Verificar contraseña
            if (password_verify($data['password'], $found_doctor['password'])) {
                // Generar token
                $tokenPayload = [
                    'user_id' => $found_doctor['id'],
                    'email' => $found_doctor['email'],
                    'role' => 'doctor',
                    'exp' => time() + (24 * 60 * 60) // Token válido por 24 horas
                ];
                
                $token = generateSimpleToken($tokenPayload);
                
                // Preparar respuesta
                $response = [
                    'success' => true,
                    'message' => 'Inicio de sesión exitoso',
                    'user' => [
                        'id' => $found_doctor['id'],
                        'email' => $found_doctor['email'],
                        'name' => $found_doctor['name'],
                        'role' => 'doctor',
                        'specialty' => $found_doctor['specialty']
                    ],
                    'token' => $token
                ];
                
                debug_log('Login exitoso', ['doctor_id' => $found_doctor['id']]);
                return $response;
            } else {
                debug_log('Contraseña incorrecta', ['email' => $data['email']]);
                return ['success' => false, 'message' => 'Contraseña incorrecta'];
            }
        } else {
            debug_log('Médico no encontrado', ['email' => $data['email']]);
            return ['success' => false, 'message' => 'Usuario no encontrado'];
        }
    } catch (Exception $e) {
        debug_log('Error en login', ['error' => $e->getMessage()]);
        return ['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()];
    }
}

// Manejar las acciones
try {
    debug_log('Procesando acción', ['action' => $action]);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $result = null;
        
        switch ($action) {
            case 'login':
                debug_log('Procesando login de médico');
                $result = doctorLogin($data);
                break;
            default:
                debug_log('Acción no válida', ['action' => $action]);
                $result = ['success' => false, 'message' => 'Acción no válida'];
                break;
        }
        
        // Asegurarse de que no hay salida antes del json_encode
        if (ob_get_length()) ob_clean();
        
        // Codificar respuesta y verificar errores
        $json = json_encode($result);
        if ($json === false) {
            debug_log('Error al codificar JSON', ['error' => json_last_error_msg(), 'result' => print_r($result, true)]);
            throw new Exception('Error al codificar JSON: ' . json_last_error_msg());
        }
        
        debug_log('Respuesta enviada', $result);
        echo $json;
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    }
} catch (Exception $e) {
    debug_log('Error en doctor_auth.php', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
