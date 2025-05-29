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
    $data = json_decode(file_get_contents('php://input'), true);
    error_log("Datos recibidos: " . json_encode($data));
    
    if (!isset($data['appointment_id'])) {
        throw new Exception('ID de cita no proporcionado');
    }
    
    $appointmentId = intval($data['appointment_id']);
    
    // Crear instancia de la base de datos
    $db = getDbConnection();
    if (!$db) {
        throw new Exception('Error al conectar con la base de datos');
    }
    
    // Verificar que la cita exista (sin verificar el doctor_id para depuración)
    $checkSql = "SELECT * FROM appointments WHERE id = :appointment_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(':appointment_id', $appointmentId, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        throw new Exception('La cita con ID ' . $appointmentId . ' no existe en la base de datos');
    }
    
    // Obtener los datos de la cita
    $appointment = $checkStmt->fetch(PDO::FETCH_ASSOC);
    error_log("Datos de la cita: " . json_encode($appointment));
    
    // Verificar si la cita ya está confirmada
    if (isset($appointment['status']) && $appointment['status'] === 'confirmed') {
        echo json_encode([
            'success' => true,
            'message' => 'La cita ya estaba confirmada previamente',
            'appointment_id' => $appointmentId
        ]);
        exit();
    }
    
    // Actualizar el estado de la cita a 'confirmed'
    $updateSql = "UPDATE appointments SET status = 'confirmed' WHERE id = :appointment_id";
    $updateStmt = $db->prepare($updateSql);
    $updateStmt->bindParam(':appointment_id', $appointmentId, PDO::PARAM_INT);
    $result = $updateStmt->execute();
    
    error_log("Resultado de la actualización: " . ($result ? 'true' : 'false'));
    error_log("Filas afectadas: " . $updateStmt->rowCount());
    
    // No verificamos rowCount() porque puede ser 0 si el valor no cambió
    if (!$result) {
        throw new Exception('Error al ejecutar la consulta de actualización');
    }
    
    // Devolver respuesta
    echo json_encode([
        'success' => true,
        'message' => 'Cita confirmada correctamente',
        'appointment_id' => $appointmentId,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    error_log('Error de base de datos en accept_appointment.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error de base de datos: ' . $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
} catch (Exception $e) {
    error_log('Error general en accept_appointment.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

error_log("Finalizando accept_appointment.php");
?>
