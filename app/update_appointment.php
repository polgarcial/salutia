<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir archivos necesarios
require_once 'database_class.php';
require_once 'debug_log.php';
require_once 'jwt_helper.php';

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
    if (!$decoded || !isset($decoded->user_id)) {
        throw new Exception('Token inválido');
    }
    
    // Obtener datos de la solicitud
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['appointment_id']) || !isset($data['status'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
        exit();
    }
    
    $appointmentId = intval($data['appointment_id']);
    $status = $data['status'];
    $notes = isset($data['notes']) ? $data['notes'] : null;
    
    // Validar estado
    $validStatuses = ['pending', 'confirmed', 'cancelled', 'completed'];
    if (!in_array($status, $validStatuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Estado no válido']);
        exit();
    }
    
    // Crear instancia de la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener información de la cita para verificar permisos
    $stmt = $db->prepare("SELECT patient_id, doctor_id FROM appointments WHERE id = :appointment_id");
    $stmt->bindParam(':appointment_id', $appointmentId, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Cita no encontrada']);
        exit();
    }
    
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar permisos según el rol
    $userId = intval($decoded->user_id);
    $userRole = $decoded->role;
    
    // Pacientes solo pueden cancelar sus propias citas
    if ($userRole === 'patient' && $appointment['patient_id'] !== $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para modificar esta cita']);
        exit();
    }
    
    // Pacientes solo pueden cancelar citas, no confirmarlas o marcarlas como completadas
    if ($userRole === 'patient' && $status !== 'cancelled') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Los pacientes solo pueden cancelar citas']);
        exit();
    }
    
    // Médicos solo pueden modificar citas donde son el médico asignado
    if ($userRole === 'doctor' && $appointment['doctor_id'] !== $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para modificar esta cita']);
        exit();
    }
    
    // Actualizar estado de la cita
    $sql = "UPDATE appointments SET status = :status";
    if ($notes !== null) {
        $sql .= ", notes = :notes";
    }
    $sql .= " WHERE id = :appointment_id";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':status', $status);
    if ($notes !== null) {
        $stmt->bindParam(':notes', $notes);
    }
    $stmt->bindParam(':appointment_id', $appointmentId, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Estado de la cita actualizado correctamente'
        ]);
    } else {
        throw new Exception('Error al actualizar el estado de la cita');
    }
    
} catch (Exception $e) {
    debug_log('Error en update_appointment.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
