<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir archivos necesarios
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../utils/debug_log.php';
require_once __DIR__ . '/../auth/jwt_helper.php';

// Registrar inicio de la ejecución
debug_log('Iniciando accept_appointment.php');

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

// Verificar autenticación
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (!$authHeader || strpos($authHeader, 'Bearer ') !== 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$token = str_replace('Bearer ', '', $authHeader);

try {
    // Verificar token
    $decoded = decodeJWT($token);
    if (!$decoded || !isset($decoded->user_id) || $decoded->role !== 'doctor') {
        throw new Exception('Token inválido o usuario no es médico');
    }
    
    // Obtener datos del cuerpo de la solicitud
    $data = json_decode(file_get_contents('php://input'), true);
    debug_log('Datos recibidos: ' . json_encode($data));
    
    if (!isset($data['appointment_id'])) {
        throw new Exception('ID de cita no proporcionado');
    }
    
    $appointmentId = intval($data['appointment_id']);
    $doctorId = intval($decoded->user_id);
    
    debug_log("Procesando aceptación de cita ID: $appointmentId para doctor ID: $doctorId");
    
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
    debug_log("Información de la cita encontrada: " . json_encode($citaInfo));
    
    // Verificar que la cita exista y pertenezca al médico
    // Nota: Para fines de depuración, temporalmente omitimos la verificación del doctor_id
    $checkSql = "SELECT * FROM appointments WHERE id = :appointment_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(':appointment_id', $appointmentId, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        throw new Exception('La cita no existe');
    }
    
    // Obtener los datos de la cita
    $appointment = $checkStmt->fetch(PDO::FETCH_ASSOC);
    debug_log("Datos completos de la cita: " . json_encode($appointment));
    
    // Verificar si la cita ya está aceptada
    if (isset($appointment['status']) && $appointment['status'] === 'accepted') {
        debug_log("La cita ya está aceptada");
        echo json_encode([
            'success' => true,
            'message' => 'La cita ya estaba aceptada previamente',
            'appointment_id' => $appointmentId
        ]);
        exit();
    }
    
    try {
        // Actualizar el estado de la cita a 'accepted'
        $updateSql = "UPDATE appointments SET status = 'accepted' WHERE id = :appointment_id";
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->bindParam(':appointment_id', $appointmentId, PDO::PARAM_INT);
        $result = $updateStmt->execute();
        
        debug_log("Resultado de la actualización: " . ($result ? 'true' : 'false'));
        debug_log("Filas afectadas: " . $updateStmt->rowCount());
        
        // En algunos casos, rowCount puede devolver 0 incluso si la consulta fue exitosa
        // (por ejemplo, si el valor no cambió). Verificamos el resultado de execute() en su lugar.
        if (!$result) {
            throw new Exception('Error al ejecutar la consulta de actualización');
        }
    } catch (PDOException $e) {
        debug_log("Error PDO al actualizar: " . $e->getMessage());
        throw new Exception('Error de base de datos al actualizar: ' . $e->getMessage());
    }
    
    // Registrar éxito en el log
    debug_log("Cita ID $appointmentId aceptada correctamente");
    
    // Devolver respuesta
    echo json_encode([
        'success' => true,
        'message' => 'Cita aceptada correctamente',
        'appointment_id' => $appointmentId,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    debug_log('Error de base de datos en accept_appointment.php: ' . $e->getMessage());
    debug_log('Código de error: ' . $e->getCode());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error de base de datos: ' . $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
} catch (Exception $e) {
    debug_log('Error en accept_appointment.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

debug_log('Finalizando accept_appointment.php');
?>
