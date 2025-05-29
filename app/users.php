<?php
// Desactivar la salida de errores HTML para evitar que se mezcle con JSON
ini_set('display_errors', 0);
error_reporting(0);

// Configuración de CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Manejar solicitudes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Función para devolver respuesta JSON
function sendJsonResponse($status, $data) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Incluir archivo de configuración de la base de datos
require_once '../config/database.php';

try {
    // Crear conexión a la base de datos
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8");
    
    // Manejar solicitud GET para obtener usuarios
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Consulta para obtener todos los usuarios
        $query = "SELECT id, first_name, last_name, email, role FROM users";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Devolver respuesta con los usuarios
        sendJsonResponse(200, [
            'success' => true,
            'users' => $users
        ]);
    } 
    // Manejar solicitud POST para crear un nuevo usuario
    else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Obtener datos del cuerpo de la solicitud
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!$data) {
            sendJsonResponse(400, [
                'success' => false,
                'error' => 'Datos inválidos'
            ]);
        }
        
        // Validar datos requeridos
        if (!isset($data['first_name']) || !isset($data['last_name']) || !isset($data['email']) || !isset($data['password']) || !isset($data['role'])) {
            sendJsonResponse(400, [
                'success' => false,
                'error' => 'Faltan datos requeridos'
            ]);
        }
        
        // Verificar si el email ya existe
        $check_query = "SELECT id FROM users WHERE email = :email";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bindParam(':email', $data['email']);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            sendJsonResponse(400, [
                'success' => false,
                'error' => 'El email ya está registrado'
            ]);
        }
        
        // Insertar nuevo usuario
        $insert_query = "INSERT INTO users (first_name, last_name, email, password, role) VALUES (:first_name, :last_name, :email, :password, :role)";
        $insert_stmt = $conn->prepare($insert_query);
        
        // Hash de la contraseña
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $insert_stmt->bindParam(':first_name', $data['first_name']);
        $insert_stmt->bindParam(':last_name', $data['last_name']);
        $insert_stmt->bindParam(':email', $data['email']);
        $insert_stmt->bindParam(':password', $hashed_password);
        $insert_stmt->bindParam(':role', $data['role']);
        
        if ($insert_stmt->execute()) {
            sendJsonResponse(201, [
                'success' => true,
                'message' => 'Usuario creado correctamente',
                'user_id' => $conn->lastInsertId()
            ]);
        } else {
            sendJsonResponse(500, [
                'success' => false,
                'error' => 'Error al crear el usuario'
            ]);
        }
    } else {
        // Método no permitido
        sendJsonResponse(405, [
            'success' => false,
            'error' => 'Método no permitido'
        ]);
    }
} catch (PDOException $e) {
    // Error de base de datos
    sendJsonResponse(500, [
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Otro tipo de error
    sendJsonResponse(500, [
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
