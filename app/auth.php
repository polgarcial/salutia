<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'debug_log.php';

debug_log('Inicio de solicitud auth.php', ['method' => $_SERVER['REQUEST_METHOD'], 'action' => isset($_GET['action']) ? $_GET['action'] : 'none']);

require_once 'database_class.php';
require_once 'jwt_helper.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

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

function verifyJWT($token) {
    try {
        $decoded = decodeJWT($token);
        if (!$decoded || !isset($decoded->user_id)) {
            throw new Exception('Token inválido');
        }
        return [
            'user_id' => $decoded->user_id,
            'email' => $decoded->email,
            'role' => $decoded->role
        ];
    } catch (Exception $e) {
        throw new Exception('Token inválido: ' . $e->getMessage());
    }
}

function verifyToken() {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'No autorizado']);
        exit();
    }

    $authHeader = $headers['Authorization'];
    $token = str_replace('Bearer ', '', $authHeader);

    try {
        return verifyJWT($token);
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Token inválido']);
        exit();
    }
}

function register($db, $data) {
    try {

        if (!isset($data['firstName']) || !isset($data['lastName']) || !isset($data['email']) || !isset($data['password'])) {
            return ['success' => false, 'message' => 'Faltan datos requeridos'];
        }

        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->bindParam(':email', $data['email']);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'El correo electrónico ya está registrado'];
        }

        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT INTO users (email, password, name, role) VALUES (:email, :password, :name, :role)");
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':password', $hashedPassword);
        
        $fullName = $data['firstName'] . ' ' . $data['lastName'];
        $stmt->bindParam(':name', $fullName);
        
        $role = isset($data['role']) ? $data['role'] : 'patient';
        $stmt->bindParam(':role', $role);

        if ($stmt->execute()) {
            $userId = $db->lastInsertId();
            return ['success' => true, 'message' => 'Usuario registrado correctamente', 'userId' => $userId];
        } else {
            return ['success' => false, 'message' => 'Error al registrar el usuario'];
        }
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()];
    }
}

function login($db, $data) {
    try {
        debug_log('Iniciando proceso de login', $data);
        
        if (!isset($data['email']) || !isset($data['password'])) {
            debug_log('Faltan datos requeridos', ['email_set' => isset($data['email']), 'password_set' => isset($data['password'])]);
            return ['success' => false, 'message' => 'Faltan datos requeridos'];
        }

        $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Email inválido'];
        }

        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $data['email']);
        $stmt->execute();
        
        debug_log('Búsqueda de usuario', ['email' => $data['email'], 'found' => $stmt->rowCount()]);

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            debug_log('Usuario encontrado', ['id' => $user['id'], 'role' => $user['role']]);
            
            if (password_verify($data['password'], $user['password'])) {
                try {

                    require_once(__DIR__ . '/jwt_helper.php');
                    $tokenPayload = [
                        'user_id' => (int)$user['id'],
                        'email' => $user['email'],
                        'role' => $user['role'],
                        'exp' => time() + (24 * 60 * 60)
                    ];
                    $token = generateJWT($tokenPayload);
                    
                    unset($user['password']);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Inicio de sesión exitoso',
                        'user' => [
                            'id' => (int)$user['id'],
                            'email' => $user['email'],
                            'name' => $user['name'],
                            'role' => $user['role']
                        ],
                        'token' => $token
                    ];
                    
                    $testJson = json_encode($response);
                    if ($testJson === false) {
                        throw new Exception('Error al codificar la respuesta: ' . json_last_error_msg());
                    }
                    
                    return $response;
                    
                } catch (Exception $e) {
                    return ['success' => false, 'message' => 'Error al generar token: ' . $e->getMessage()];
                }
            } else {
                return ['success' => false, 'message' => 'Contraseña incorrecta'];
            }
        } else {
            return ['success' => false, 'message' => 'Usuario no encontrado'];
        }
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()];
    }
}

function logout() {
    return ['success' => true, 'message' => 'Sesión cerrada correctamente'];
}

try {
    debug_log('Procesando acción', ['action' => $action]);
    
    if ($action === 'verify') {

        verifyToken();
        echo json_encode(['success' => true]);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $result = null;
        
        switch ($action) {
            case 'register':
                debug_log('Procesando registro');
                $result = register($db, $data);
                break;
            case 'login':
                debug_log('Procesando login');
                $result = login($db, $data);
                break;
            case 'logout':
                debug_log('Procesando logout');
                $result = logout();
                break;
            default:
                debug_log('Acción no válida', ['action' => $action]);
                $result = ['success' => false, 'message' => 'Acción no válida'];
                break;
        }
        
        if (ob_get_length()) ob_clean();
        
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
    debug_log('Error en auth.php', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>