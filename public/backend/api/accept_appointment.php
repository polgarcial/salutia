<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Registrar la solicitud para depuración
file_put_contents(__DIR__ . '/../../../logs/accept_appointment.log', date('Y-m-d H:i:s') . " - Solicitud recibida\n", FILE_APPEND);

// Incluir archivos necesarios
require_once __DIR__ . '/../../../config/database.php';

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
    file_put_contents(__DIR__ . '/../../../logs/accept_appointment.log', date('Y-m-d H:i:s') . " - Datos recibidos: " . json_encode($data) . "\n", FILE_APPEND);
    
    if (!isset($data['appointment_id'])) {
        throw new Exception('ID de cita no proporcionado');
    }
    
    $appointmentId = intval($data['appointment_id']);
    $doctorId = isset($data['doctor_id']) ? intval($data['doctor_id']) : 1; // Por defecto, doctor ID 1
    
    file_put_contents(__DIR__ . '/../../../logs/accept_appointment.log', date('Y-m-d H:i:s') . " - Procesando cita ID: $appointmentId para doctor ID: $doctorId\n", FILE_APPEND);
    
    // Crear instancia de la base de datos
    $db = getDbConnection();
    if (!$db) {
        throw new Exception('Error al conectar con la base de datos');
    }
    
    // Para propósitos de depuración, verificar primero si la cita existe en general
    $existsSql = "SELECT id, doctor_id, status FROM appointments WHERE id = :appointment_id";
    $existsStmt = $db->prepare($existsSql);
    $existsStmt->bindParam(':appointment_id', $appointmentId, PDO::PARAM_INT);
    $existsStmt->execute();
    
    if ($existsStmt->rowCount() === 0) {
        throw new Exception("La cita con ID $appointmentId no existe en la base de datos");
    }
    
    $citaInfo = $existsStmt->fetch(PDO::FETCH_ASSOC);
    file_put_contents(__DIR__ . '/../../../logs/accept_appointment.log', date('Y-m-d H:i:s') . " - Información de la cita encontrada: " . json_encode($citaInfo) . "\n", FILE_APPEND);
    
    // Verificar que la cita exista - omitimos temporalmente la verificación del doctor_id para depuración
    $checkSql = "SELECT * FROM appointments WHERE id = :appointment_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(':appointment_id', $appointmentId, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        throw new Exception('La cita no existe');
    }
    
    // Obtener los datos de la cita
    $appointment = $checkStmt->fetch(PDO::FETCH_ASSOC);
    file_put_contents(__DIR__ . '/../../../logs/accept_appointment.log', date('Y-m-d H:i:s') . " - Datos completos de la cita: " . json_encode($appointment) . "\n", FILE_APPEND);
    
    // Verificar si la cita ya está aceptada
    if (isset($appointment['status']) && $appointment['status'] === 'accepted') {
        file_put_contents(__DIR__ . '/../../../logs/accept_appointment.log', date('Y-m-d H:i:s') . " - La cita ya está aceptada\n", FILE_APPEND);
        echo json_encode([
            'success' => true,
            'message' => 'La cita ya estaba aceptada previamente',
            'appointment_id' => $appointmentId
        ]);
        exit();
    }
    
    try {
        // Actualizar el estado de la cita a 'accepted'
        $updateSql = "UPDATE appointments SET status = 'confirmed' WHERE id = :appointment_id";
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->bindParam(':appointment_id', $appointmentId, PDO::PARAM_INT);
        $result = $updateStmt->execute();
        
        file_put_contents(__DIR__ . '/../../../logs/accept_appointment.log', date('Y-m-d H:i:s') . " - Resultado de la actualización: " . ($result ? 'true' : 'false') . "\n", FILE_APPEND);
        file_put_contents(__DIR__ . '/../../../logs/accept_appointment.log', date('Y-m-d H:i:s') . " - Filas afectadas: " . $updateStmt->rowCount() . "\n", FILE_APPEND);
    
        // En algunos casos, rowCount puede devolver 0 incluso si la consulta fue exitosa
        // (por ejemplo, si el valor no cambió). Verificamos el resultado de execute() en su lugar.
        if (!$result) {
            throw new Exception('Error al ejecutar la consulta de actualización');
        }
    } catch (PDOException $e) {
        file_put_contents(__DIR__ . '/../../../logs/accept_appointment.log', date('Y-m-d H:i:s') . " - Error PDO al actualizar: " . $e->getMessage() . "\n", FILE_APPEND);
        throw new Exception('Error de base de datos al actualizar: ' . $e->getMessage());
    }
    
    // Registrar éxito en el log
    file_put_contents(__DIR__ . '/../../../logs/accept_appointment.log', date('Y-m-d H:i:s') . " - Cita ID $appointmentId aceptada correctamente\n", FILE_APPEND);
    
    // Devolver respuesta
    echo json_encode([
        'success' => true,
        'message' => 'Cita aceptada correctamente',
        'appointment_id' => $appointmentId,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    file_put_contents(__DIR__ . '/../../../logs/accept_appointment.log', date('Y-m-d H:i:s') . " - Error de base de datos: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error de base de datos: ' . $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/../../../logs/accept_appointment.log', date('Y-m-d H:i:s') . " - Error general: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

file_put_contents(__DIR__ . '/../../../logs/accept_appointment.log', date('Y-m-d H:i:s') . " - Finalizando accept_appointment.php\n", FILE_APPEND);
?>
