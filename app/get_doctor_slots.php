<?php
// Configuración de CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Incluir la configuración de la base de datos
require_once '../config/database_class.php';

// Función para enviar respuestas JSON
function sendJsonResponse($statusCode, $data) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Verificar el método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(405, ['error' => 'Método no permitido']);
}

// Verificar parámetros requeridos
if (!isset($_GET['doctor_id']) || !isset($_GET['date'])) {
    sendJsonResponse(400, ['error' => 'Se requiere doctor_id y date']);
}

$doctorId = $_GET['doctor_id'];
$date = $_GET['date'];

try {
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Primero, verificar si el médico existe
    $checkDoctor = $db->prepare("SELECT id FROM doctors WHERE id = :doctor_id AND active = 1");
    $checkDoctor->bindParam(':doctor_id', $doctorId);
    $checkDoctor->execute();
    
    if ($checkDoctor->rowCount() === 0) {
        sendJsonResponse(404, ['error' => 'Médico no encontrado o inactivo']);
    }
    
    // Obtener los horarios disponibles del médico para la fecha especificada
    $stmt = $db->prepare("
        SELECT time_slot 
        FROM doctor_availability 
        WHERE doctor_id = :doctor_id 
        AND date = :date 
        AND is_available = 1
        AND time_slot NOT IN (
            SELECT time_slot 
            FROM appointments 
            WHERE doctor_id = :doctor_id 
            AND date = :date 
            AND (status = 'pending' OR status = 'confirmed')
        )
        ORDER BY time_slot ASC
    ");
    
    $stmt->bindParam(':doctor_id', $doctorId);
    $stmt->bindParam(':date', $date);
    $stmt->execute();
    
    $availableSlots = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $availableSlots[] = $row['time_slot'];
    }
    
    // Si no hay horarios disponibles en la tabla doctor_availability,
    // generar horarios predeterminados (9:00 a 17:00, cada 30 minutos)
    if (empty($availableSlots)) {
        // Verificar si el día es fin de semana (6 = sábado, 0 = domingo)
        $dayOfWeek = date('w', strtotime($date));
        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
            // Fin de semana: horarios reducidos (solo mañana)
            $startHour = 9;
            $endHour = 13;
        } else {
            // Día de semana: horario completo
            $startHour = 9;
            $endHour = 17;
        }
        
        // Generar horarios cada 30 minutos
        for ($hour = $startHour; $hour < $endHour; $hour++) {
            for ($minute = 0; $minute < 60; $minute += 30) {
                $timeSlot = sprintf("%02d:%02d", $hour, $minute);
                
                // Verificar si este horario ya está ocupado por una cita
                $checkAppointment = $db->prepare("
                    SELECT id FROM appointments 
                    WHERE doctor_id = :doctor_id 
                    AND date = :date 
                    AND time_slot = :time_slot
                    AND (status = 'pending' OR status = 'confirmed')
                ");
                $checkAppointment->bindParam(':doctor_id', $doctorId);
                $checkAppointment->bindParam(':date', $date);
                $checkAppointment->bindParam(':time_slot', $timeSlot);
                $checkAppointment->execute();
                
                if ($checkAppointment->rowCount() === 0) {
                    $availableSlots[] = $timeSlot;
                }
            }
        }
    }
    
    // Enviar respuesta
    sendJsonResponse(200, ['available_slots' => $availableSlots]);
    
} catch (PDOException $e) {
    // En caso de error, enviar respuesta de error
    sendJsonResponse(500, ['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>
