<?php
// Desactivar errores para evitar problemas con JSON
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Configuración de CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Incluir la configuración de la base de datos
require_once '../config/database_class.php';

// Función para enviar respuestas JSON
function sendJsonResponse($statusCode, $data) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Verificar el método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(405, ['error' => 'Método no permitido']);
}

try {
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener la especialidad si se proporciona
    $specialty = isset($_GET['specialty']) ? $_GET['specialty'] : null;
    
    // Preparar la consulta SQL - Versión corregida que usa JOIN con la tabla users
    $sql = "SELECT d.id, u.name, d.specialty 
           FROM doctors d 
           JOIN users u ON d.user_id = u.id 
           WHERE d.active = 1";
    
    if ($specialty) {
        $sql .= " AND d.specialty = :specialty";
    }
    
    $stmt = $db->prepare($sql);
    
    // Vincular parámetros si es necesario
    if ($specialty) {
        $stmt->bindParam(':specialty', $specialty);
    }
    
    // Ejecutar la consulta
    $stmt->execute();
    
    // Obtener los resultados
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Enviar respuesta
    sendJsonResponse(200, ['doctors' => $doctors]);
    
} catch (PDOException $e) {
    // En caso de error, enviar respuesta de error
    sendJsonResponse(500, ['error' => 'Error al cargar los médicos']);
}
?>
