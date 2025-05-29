<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Crear archivo de log
$logFile = __DIR__ . '/reject_debug.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Inicio de reject_appointment.php\n", FILE_APPEND);

// Función para escribir en el log
function debug_log($message) {
    global $logFile;
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

// Incluir archivos necesarios
try {
    debug_log("Intentando cargar database.php");
    require_once __DIR__ . '/../../../../config/database.php';
    debug_log("database.php cargado correctamente");
} catch (Exception $e) {
    debug_log("Error al cargar database.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al cargar dependencias: ' . $e->getMessage()]);
    exit();
}

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
    debug_log("Iniciando proceso de rechazo de cita");
    
    // Obtener datos del cuerpo de la solicitud
    $rawData = file_get_contents('php://input');
    debug_log("Datos recibidos: " . $rawData);
    
    $data = json_decode($rawData, true);
    if ($data === null) {
        debug_log("Error al decodificar JSON: " . json_last_error_msg());
        throw new Exception('Error al procesar los datos JSON: ' . json_last_error_msg());
    }
    
    debug_log("Datos decodificados: " . print_r($data, true));
    
    if (!isset($data['appointment_id']) || !is_numeric($data['appointment_id'])) {
        debug_log("ID de cita no válido: " . (isset($data['appointment_id']) ? $data['appointment_id'] : 'no definido'));
        throw new Exception('ID de cita no válido');
    }
    
    $appointmentId = intval($data['appointment_id']);
    $doctorId = isset($data['doctor_id']) ? intval($data['doctor_id']) : 1; // Por defecto, doctor ID 1
    
    debug_log("ID de cita: " . $appointmentId . ", ID de doctor: " . $doctorId);
    
    // Crear instancia de la base de datos
    debug_log("Conectando a la base de datos");
    $db = getDbConnection();
    debug_log("Conexión a la base de datos establecida");
    
    // Verificar que la cita exista y pertenezca al médico
    $checkSql = "SELECT * FROM appointments WHERE id = :appointment_id";
    debug_log("SQL de verificación: " . $checkSql);
    
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(':appointment_id', $appointmentId, PDO::PARAM_INT);
    $checkStmt->execute();
    
    debug_log("Filas encontradas: " . $checkStmt->rowCount());
    
    if ($checkStmt->rowCount() === 0) {
        debug_log("La cita no existe");
        throw new Exception('La cita no existe');
    }
    
    // Obtener los datos de la cita
    $appointment = $checkStmt->fetch(PDO::FETCH_ASSOC);
    debug_log("Datos de la cita: " . print_r($appointment, true));
    
    // Actualizar el estado de la cita a 'rejected'
    $updateSql = "UPDATE appointments SET status = 'rejected' WHERE id = :appointment_id";
    debug_log("SQL de actualización: " . $updateSql);
    
    $updateStmt = $db->prepare($updateSql);
    $updateStmt->bindParam(':appointment_id', $appointmentId, PDO::PARAM_INT);
    $updateStmt->execute();
    
    debug_log("Filas actualizadas: " . $updateStmt->rowCount());
    
    if ($updateStmt->rowCount() === 0) {
        debug_log("No se pudo actualizar el estado de la cita");
        throw new Exception('No se pudo actualizar el estado de la cita');
    }
    
    debug_log("Cita rechazada correctamente");
    
    // Devolver respuesta
    echo json_encode([
        'success' => true,
        'message' => 'Cita rechazada correctamente',
        'appointment_id' => $appointmentId
    ]);
    
} catch (Exception $e) {
    error_log('Error en reject_appointment.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
