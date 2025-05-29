<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir archivos necesarios
require_once __DIR__ . '/../../../../config/database.php';

// Para depuración
error_log('get_patient_appointments.php iniciado');

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

// Verificar que sea una solicitud GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

try {
    // Obtener el ID del paciente
    $patientId = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : null;
    error_log('ID de paciente recibido: ' . $patientId);
    
    if (!$patientId) {
        throw new Exception('ID de paciente no proporcionado');
    }
    
    // Crear instancia de la base de datos
    $db = getDbConnection();
    if (!$db) {
        error_log('Error al conectar con la base de datos');
        throw new Exception('Error al conectar con la base de datos');
    }
    error_log('Conexión a la base de datos establecida');
    
    // Consultar las citas del paciente - Usar una consulta más simple primero para depuración
    $sql = "SELECT * FROM appointments WHERE patient_id = :patient_id";
    error_log('Consulta SQL: ' . $sql);
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':patient_id', $patientId, PDO::PARAM_INT);
    $result = $stmt->execute();
    
    if (!$result) {
        error_log('Error al ejecutar la consulta: ' . print_r($stmt->errorInfo(), true));
        throw new Exception('Error al ejecutar la consulta');
    }
    error_log('Consulta ejecutada correctamente');
    
    // Obtener resultados
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log('Número de citas encontradas: ' . count($appointments));
    
    // Si hay citas, obtener información de los médicos
    if (count($appointments) > 0) {
        foreach ($appointments as &$appointment) {
            // Obtener información del médico
            $doctorSql = "SELECT name, specialty FROM doctors WHERE id = :doctor_id";
            $doctorStmt = $db->prepare($doctorSql);
            $doctorStmt->bindParam(':doctor_id', $appointment['doctor_id'], PDO::PARAM_INT);
            $doctorStmt->execute();
            $doctorInfo = $doctorStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($doctorInfo) {
                $appointment['doctor_name'] = $doctorInfo['name'];
                $appointment['specialty'] = $doctorInfo['specialty'];
            } else {
                $appointment['doctor_name'] = 'Médico no encontrado';
                $appointment['specialty'] = 'No especificada';
            }
        }
    }
    
    // Devolver respuesta
    echo json_encode([
        'success' => true,
        'message' => 'Citas obtenidas correctamente',
        'appointments' => $appointments
    ]);
    
} catch (PDOException $e) {
    error_log('Error de base de datos en get_patient_appointments.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('Error general en get_patient_appointments.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
