<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir archivos necesarios
require_once __DIR__ . '/../../../../config/database.php';

// Habilitar CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar OPTIONS request para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

try {
    // Obtener datos del cuerpo de la solicitud
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);
    
    if (!isset($data['appointment_id']) || !is_numeric($data['appointment_id'])) {
        throw new Exception('ID de cita no válido');
    }
    
    $appointmentId = intval($data['appointment_id']);
    
    // Conectar a la base de datos directamente (sin usar funciones externas)
    $host = 'localhost';
    $dbname = 'salutia';
    $username = 'root';
    $password = '';
    
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Actualizar directamente sin verificaciones complejas
    $updateSql = "UPDATE appointments SET status = 'rejected' WHERE id = :appointment_id";
    $updateStmt = $db->prepare($updateSql);
    $updateStmt->bindParam(':appointment_id', $appointmentId, PDO::PARAM_INT);
    $updateStmt->execute();
    
    // Devolver respuesta
    echo json_encode([
        'success' => true,
        'message' => 'Cita rechazada correctamente',
        'appointment_id' => $appointmentId
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
