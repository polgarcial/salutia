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
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar OPTIONS request para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
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
    
    // Obtener ID de la cita
    $appointmentId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if (!$appointmentId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de cita no especificado']);
        exit();
    }
    
    // Crear instancia de la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener detalles de la cita
    $stmt = $db->prepare("
        SELECT a.id, a.patient_id, a.doctor_id, a.appointment_date, a.start_time, a.end_time, 
               a.reason, a.status, a.notes, a.created_at,
               d.name as doctor_name, p.name as patient_name,
               ds.specialty
        FROM appointments a
        LEFT JOIN users d ON a.doctor_id = d.id
        LEFT JOIN users p ON a.patient_id = p.id
        LEFT JOIN doctor_specialties ds ON a.doctor_id = ds.doctor_id
        WHERE a.id = :appointment_id
    ");
    $stmt->bindParam(':appointment_id', $appointmentId, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Cita no encontrada']);
        exit();
    }
    
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar permisos (solo el paciente, el médico o un admin pueden ver los detalles)
    if ($appointment['patient_id'] != $decoded->user_id && 
        $appointment['doctor_id'] != $decoded->user_id && 
        $decoded->role !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para ver esta cita']);
        exit();
    }
    
    // Devolver respuesta
    echo json_encode([
        'success' => true,
        'appointment' => $appointment
    ]);
    
} catch (Exception $e) {
    debug_log('Error en get_appointment_details.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
