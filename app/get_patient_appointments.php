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
    
    // Obtener parámetros
    $patientId = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    
    // Verificar que el ID del paciente coincida con el del token
    if ($patientId !== intval($decoded->user_id) && $decoded->role !== 'admin' && $decoded->role !== 'doctor') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para ver estas citas']);
        exit();
    }
    
    // Crear instancia de la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Construir consulta SQL
    $sql = "
        SELECT a.id, a.appointment_date, a.start_time, a.end_time, a.reason, a.status, a.notes,
               d.name as doctor_name, ds.specialty
        FROM appointments a
        LEFT JOIN users d ON a.doctor_id = d.id
        LEFT JOIN doctor_specialties ds ON a.doctor_id = ds.doctor_id
        WHERE a.patient_id = :patient_id
    ";
    
    // Filtrar por estado si se especifica
    if ($status === 'upcoming') {
        $sql .= " AND (a.appointment_date > CURDATE() OR (a.appointment_date = CURDATE() AND a.start_time >= CURTIME()))
                  AND a.status IN ('pending', 'confirmed')
                  ORDER BY a.appointment_date ASC, a.start_time ASC";
    } elseif ($status === 'past') {
        $sql .= " AND (a.appointment_date < CURDATE() OR (a.appointment_date = CURDATE() AND a.start_time < CURTIME()))
                  OR a.status IN ('completed', 'cancelled')
                  ORDER BY a.appointment_date DESC, a.start_time DESC";
    } else {
        $sql .= " ORDER BY a.appointment_date DESC, a.start_time DESC";
    }
    
    // Preparar y ejecutar consulta
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':patient_id', $patientId, PDO::PARAM_INT);
    $stmt->execute();
    
    // Obtener resultados
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Devolver respuesta
    echo json_encode([
        'success' => true,
        'appointments' => $appointments
    ]);
    
} catch (Exception $e) {
    debug_log('Error en get_patient_appointments.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
