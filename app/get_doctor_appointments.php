<?php
// API para obtener las citas de un médico

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

// Habilitar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database_class.php';
require_once 'auth.php';

// Verificar el método de la solicitud
// Permitimos GET y OPTIONS para evitar errores CORS
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
    ob_clean(); // Limpiar buffer antes de enviar respuesta
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Verificar el token
$headers = getallheaders();
$token = null;

if (isset($headers['Authorization'])) {
    $auth_header = $headers['Authorization'];
    if (strpos($auth_header, 'Bearer ') === 0) {
        $token = substr($auth_header, 7);
    }
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

try {
    $payload = verifyJWT($token);
    if (!$payload || !isset($payload['user_id']) || $payload['role'] !== 'doctor') {
        throw new Exception('Token inválido o usuario no autorizado');
    }
    $doctor_id = $payload['user_id'];

    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Consulta para obtener las citas del médico
    // Verificamos si existen las columnas appointment_date o date
    $checkColumns = $db->query("SHOW COLUMNS FROM appointments LIKE 'appointment_date'");
    $useAppointmentDate = $checkColumns->rowCount() > 0;
    
    if ($useAppointmentDate) {
        $query = "SELECT 
                    a.*,
                    CONCAT(p.first_name, ' ', p.last_name) as paciente_nombre,
                    p.email as paciente_email
                  FROM appointments a
                  LEFT JOIN patients p ON a.patient_id = p.id
                  WHERE a.doctor_id = :doctor_id
                    AND a.appointment_date >= CURDATE()
                  ORDER BY a.appointment_date ASC, a.appointment_time ASC
                  LIMIT 10";
    } else {
        // Usar las columnas date y time si appointment_date no existe
        $query = "SELECT 
                    a.*,
                    CONCAT(p.first_name, ' ', p.last_name) as paciente_nombre,
                    p.email as paciente_email,
                    a.date as appointment_date,
                    a.time as appointment_time
                  FROM appointments a
                  LEFT JOIN patients p ON a.patient_id = p.id
                  WHERE a.doctor_id = :doctor_id
                    AND a.date >= CURDATE()
                  ORDER BY a.date ASC, a.time ASC
                  LIMIT 10";
    }

    $stmt = $db->prepare($query);
    $stmt->bindParam(':doctor_id', $doctor_id, PDO::PARAM_INT);
    $stmt->execute();

    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear las fechas y horas
    foreach ($appointments as &$appointment) {
        // Asegurarse de que tenemos los campos necesarios
        if (isset($appointment['appointment_date']) && isset($appointment['appointment_time'])) {
            $fecha = $appointment['appointment_date'] . ' ' . $appointment['appointment_time'];
        } elseif (isset($appointment['date']) && isset($appointment['time'])) {
            $fecha = $appointment['date'] . ' ' . $appointment['time'];
            // Añadir los campos con nombres estandarizados
            $appointment['appointment_date'] = $appointment['date'];
            $appointment['appointment_time'] = $appointment['time'];
        } else {
            $fecha = date('Y-m-d H:i:s'); // Valor por defecto
        }
        
        $appointment['fecha'] = $fecha;
        $appointment['motivo'] = $appointment['reason'] ?? ($appointment['motivo'] ?? 'Consulta general');
        $appointment['paciente_nombre'] = $appointment['paciente_nombre'] ?? 'Paciente';
    }

    // Limpiar cualquier salida anterior
    ob_clean();
    
    echo json_encode([
        'success' => true,
        'appointments' => $appointments
    ]);

} catch (Exception $e) {
    // Limpiar cualquier salida anterior
    ob_clean();
    
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Error de autenticación: ' . $e->getMessage()
    ]);
} catch (PDOException $e) {
    // Limpiar cualquier salida anterior
    ob_clean();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener las citas: ' . $e->getMessage()
    ]);
}

// Asegurarse de que no hay salida después del JSON
ob_end_flush();
