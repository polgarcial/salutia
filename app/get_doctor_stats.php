<?php
// Asegurarse de que no hay salida antes de los headers
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar solicitud OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database_class.php';
require_once 'auth.php';

// Verificar el método de la solicitud
// Permitimos GET y OPTIONS para evitar errores CORS
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Verificar el token
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

try {
    $token = null;

    if (isset($headers['Authorization'])) {
        $auth_header = $headers['Authorization'];
        if (strpos($auth_header, 'Bearer ') === 0) {
            $token = substr($auth_header, 7);
        }
    }

    if (!$token) {
        throw new Exception('Token no proporcionado');
    }

    $payload = verifyJWT($token);
    if (!$payload || !isset($payload['user_id']) || $payload['role'] !== 'doctor') {
        throw new Exception('Token inválido o usuario no autorizado');
    }
    $doctorId = $payload['user_id'];

    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();

    // Obtener estadísticas
    $today = date('Y-m-d');
    
    // Verificamos si existen las columnas appointment_date o date
    $checkColumns = $db->query("SHOW COLUMNS FROM appointments LIKE 'appointment_date'");
    $useAppointmentDate = $checkColumns->rowCount() > 0;
    
    // Citas de hoy
    if ($useAppointmentDate) {
        $stmt = $db->prepare("SELECT COUNT(*) as citasHoy FROM appointments WHERE doctor_id = :doctor_id AND DATE(appointment_date) = :today");
    } else {
        $stmt = $db->prepare("SELECT COUNT(*) as citasHoy FROM appointments WHERE doctor_id = :doctor_id AND DATE(date) = :today");
    }
    $stmt->bindParam(':doctor_id', $doctorId);
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    $citasHoy = $stmt->fetch(PDO::FETCH_ASSOC)['citasHoy'] ?? 0;

    // Citas pendientes
    if ($useAppointmentDate) {
        $stmt = $db->prepare("SELECT COUNT(*) as citasPendientes FROM appointments WHERE doctor_id = :doctor_id AND appointment_date >= :today");
    } else {
        $stmt = $db->prepare("SELECT COUNT(*) as citasPendientes FROM appointments WHERE doctor_id = :doctor_id AND date >= :today");
    }
    $stmt->bindParam(':doctor_id', $doctorId);
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    $citasPendientes = $stmt->fetch(PDO::FETCH_ASSOC)['citasPendientes'] ?? 0;

    // Total de pacientes únicos
    $stmt = $db->prepare("SELECT COUNT(DISTINCT patient_id) as totalPacientes FROM appointments WHERE doctor_id = :doctor_id");
    $stmt->bindParam(':doctor_id', $doctorId);
    $stmt->execute();
    $totalPacientes = $stmt->fetch(PDO::FETCH_ASSOC)['totalPacientes'] ?? 0;

    // Limpiar cualquier salida anterior
    ob_clean();
    
    echo json_encode([
        'success' => true,
        'citasHoy' => (int)$citasHoy,
        'citasPendientes' => (int)$citasPendientes,
        'totalPacientes' => (int)$totalPacientes
    ]);

} catch (Exception $e) {
    // Limpiar cualquier salida anterior
    ob_clean();
    
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// Asegurarse de que no hay salida después del JSON
ob_end_flush();

