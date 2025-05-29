<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir archivos necesarios
require_once __DIR__ . '/../../config/database.php';

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

try {
    // Obtener parámetros
    $doctorId = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 1; // Por defecto, doctor ID 1
    
    // Crear instancia de la base de datos
    $db = getDbConnection();
    
    // Construir consulta SQL para obtener citas próximas aceptadas
    $sql = "
        SELECT a.id, a.patient_id, a.patient_name, a.patient_email, a.doctor_id, a.doctor_name,
               a.reason, a.date, a.time, a.appointment_date, a.appointment_time, a.start_time, a.end_time,
               a.status, a.notes, a.created_at
        FROM appointments a
        WHERE a.doctor_id = :doctor_id
        AND a.status IN ('accepted', 'confirmed')
        AND (a.appointment_date > CURDATE() OR (a.appointment_date = CURDATE() AND a.start_time >= CURTIME()))
        ORDER BY a.appointment_date ASC, a.start_time ASC
    ";
    
    // Preparar y ejecutar consulta
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':doctor_id', $doctorId, PDO::PARAM_INT);
    $stmt->execute();
    
    // Obtener resultados
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear fechas y horas para mejor visualización
    foreach ($appointments as &$appointment) {
        // Convertir fechas al formato español (DD/MM/YYYY)
        if (isset($appointment['appointment_date'])) {
            $date = $appointment['appointment_date'];
            if (strpos($date, '-') !== false) {
                $date_parts = explode('-', $date);
                if (count($date_parts) === 3) {
                    $appointment['formatted_date'] = $date_parts[2] . '/' . $date_parts[1] . '/' . $date_parts[0];
                } else {
                    $appointment['formatted_date'] = $date;
                }
            } else {
                $appointment['formatted_date'] = $date;
            }
        }
        
        // Formatear hora de inicio y fin
        if (isset($appointment['start_time']) && isset($appointment['end_time'])) {
            $appointment['formatted_time'] = substr($appointment['start_time'], 0, 5) . ' - ' . substr($appointment['end_time'], 0, 5);
        }
    }
    
    // Devolver respuesta
    echo json_encode([
        'success' => true,
        'appointments' => $appointments
    ]);
    
} catch (Exception $e) {
    error_log('Error en get_upcoming_appointments.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
