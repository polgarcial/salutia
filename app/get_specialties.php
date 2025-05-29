<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir archivos necesarios
require_once 'database_class.php';
require_once 'debug_log.php';

// Habilitar CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar OPTIONS request para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Crear instancia de la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener todas las especialidades
    $stmt = $db->query("SELECT DISTINCT specialty FROM doctor_specialties ORDER BY specialty");
    $specialties = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Devolver respuesta
    echo json_encode([
        'success' => true,
        'specialties' => $specialties
    ]);
    
} catch (Exception $e) {
    debug_log('Error en get_specialties.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
