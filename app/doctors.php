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
    
    // Manejar solicitud GET para obtener médicos
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Consulta base para obtener médicos
        $query = "SELECT id, first_name, last_name, email, specialty, 
                  CONCAT(first_name, ' ', last_name) as name,
                  CONCAT('https://via.placeholder.com/150?text=', SUBSTRING(first_name, 1, 1), SUBSTRING(last_name, 1, 1)) as image
                  FROM users WHERE role = 'doctor'";
        $params = [];
        
        // Filtrar por especialidad si se proporciona
        if (isset($_GET['specialty']) && !empty($_GET['specialty'])) {
            $query .= " AND specialty = :specialty";
            $params[':specialty'] = $_GET['specialty'];
        }
        
        // Filtrar por ID si se proporciona
        if (isset($_GET['id']) && !empty($_GET['id'])) {
            $query .= " AND id = :id";
            $params[':id'] = $_GET['id'];
        }
        
        // Ordenar por nombre
        $query .= " ORDER BY first_name, last_name";
        
        $stmt = $conn->prepare($query);
        
        // Vincular parámetros si existen
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        
        $stmt->execute();
        $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formatear los datos de los médicos para la interfaz
        $formattedDoctors = [];
        foreach ($doctors as $doctor) {
            $formattedDoctors[] = [
                'id' => $doctor['id'],
                'name' => $doctor['name'] ? $doctor['name'] : $doctor['first_name'] . ' ' . $doctor['last_name'],
                'specialty' => $doctor['specialty'],
                'email' => $doctor['email'],
                'image' => $doctor['image']
            ];
        }
        
        // Devolver respuesta con los médicos
        sendJsonResponse(200, [
            'success' => true,
            'doctors' => $formattedDoctors
        ]);
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
