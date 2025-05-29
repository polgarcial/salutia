<?php
// Configuración de CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Si es una solicitud OPTIONS (preflight), responder con éxito
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir la configuración de la base de datos
require_once '../config/database_class.php';

// Función para enviar respuestas JSON
function sendJsonResponse($statusCode, $data) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Conectar a la base de datos
try {
    $database = new Database();
    $db = $database->getConnection();
} catch (PDOException $e) {
    sendJsonResponse(500, ['error' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
}

// Manejar solicitudes según el método HTTP
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Obtener disponibilidad de un médico
        getDoctorAvailability($db);
        break;
    case 'POST':
        // Actualizar disponibilidad de un médico
        updateDoctorAvailability($db);
        break;
    default:
        sendJsonResponse(405, ['error' => 'Método no permitido']);
}

// Función para obtener la disponibilidad de un médico
function getDoctorAvailability($db) {
    // Verificar parámetros requeridos
    if (!isset($_GET['doctor_id']) || !isset($_GET['date'])) {
        sendJsonResponse(400, ['error' => 'Se requiere doctor_id y date']);
    }
    
    $doctorId = $_GET['doctor_id'];
    $date = $_GET['date'];
    
    try {
        // Verificar si el médico existe
        $checkDoctor = $db->prepare("SELECT id FROM doctors WHERE id = :doctor_id");
        $checkDoctor->bindParam(':doctor_id', $doctorId);
        $checkDoctor->execute();
        
        if ($checkDoctor->rowCount() === 0) {
            sendJsonResponse(404, ['error' => 'Médico no encontrado']);
        }
        
        // Obtener la disponibilidad del médico para la fecha especificada
        $stmt = $db->prepare("
            SELECT time_slot, is_available 
            FROM doctor_availability 
            WHERE doctor_id = :doctor_id 
            AND date = :date
            ORDER BY time_slot ASC
        ");
        
        $stmt->bindParam(':doctor_id', $doctorId);
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        
        $availability = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Si no hay registros de disponibilidad, generar horarios predeterminados
        if (empty($availability)) {
            $availability = generateDefaultAvailability($db, $doctorId, $date);
        }
        
        // Obtener citas existentes para marcar horarios ocupados
        $appointmentsStmt = $db->prepare("
            SELECT time_slot 
            FROM appointments 
            WHERE doctor_id = :doctor_id 
            AND date = :date 
            AND (status = 'pending' OR status = 'confirmed')
        ");
        
        $appointmentsStmt->bindParam(':doctor_id', $doctorId);
        $appointmentsStmt->bindParam(':date', $date);
        $appointmentsStmt->execute();
        
        $bookedSlots = [];
        while ($row = $appointmentsStmt->fetch(PDO::FETCH_ASSOC)) {
            $bookedSlots[] = $row['time_slot'];
        }
        
        // Marcar horarios con citas como no disponibles
        foreach ($availability as &$slot) {
            if (in_array($slot['time_slot'], $bookedSlots)) {
                $slot['is_available'] = 0;
                $slot['is_booked'] = 1;
            } else {
                $slot['is_booked'] = 0;
            }
        }
        
        sendJsonResponse(200, ['availability' => $availability]);
        
    } catch (PDOException $e) {
        sendJsonResponse(500, ['error' => 'Error en la base de datos: ' . $e->getMessage()]);
    }
}

// Función para actualizar la disponibilidad de un médico
function updateDoctorAvailability($db) {
    // Obtener datos del cuerpo de la solicitud
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Verificar datos requeridos
    if (!isset($data['doctor_id']) || !isset($data['date']) || !isset($data['slots'])) {
        sendJsonResponse(400, ['error' => 'Faltan datos requeridos (doctor_id, date, slots)']);
    }
    
    $doctorId = $data['doctor_id'];
    $date = $data['date'];
    $slots = $data['slots'];
    
    try {
        // Verificar si el médico existe
        $checkDoctor = $db->prepare("SELECT id FROM doctors WHERE id = :doctor_id");
        $checkDoctor->bindParam(':doctor_id', $doctorId);
        $checkDoctor->execute();
        
        if ($checkDoctor->rowCount() === 0) {
            sendJsonResponse(404, ['error' => 'Médico no encontrado']);
        }
        
        // Iniciar transacción
        $db->beginTransaction();
        
        // Eliminar registros existentes para esta fecha y médico
        $deleteStmt = $db->prepare("
            DELETE FROM doctor_availability 
            WHERE doctor_id = :doctor_id 
            AND date = :date
        ");
        
        $deleteStmt->bindParam(':doctor_id', $doctorId);
        $deleteStmt->bindParam(':date', $date);
        $deleteStmt->execute();
        
        // Insertar nuevos registros de disponibilidad
        $insertStmt = $db->prepare("
            INSERT INTO doctor_availability (doctor_id, date, time_slot, is_available) 
            VALUES (:doctor_id, :date, :time_slot, :is_available)
        ");
        
        foreach ($slots as $slot) {
            // Verificar si hay una cita existente en este horario
            $checkAppointment = $db->prepare("
                SELECT id FROM appointments 
                WHERE doctor_id = :doctor_id 
                AND date = :date 
                AND time_slot = :time_slot
                AND (status = 'pending' OR status = 'confirmed')
            ");
            
            $checkAppointment->bindParam(':doctor_id', $doctorId);
            $checkAppointment->bindParam(':date', $date);
            $checkAppointment->bindParam(':time_slot', $slot['time_slot']);
            $checkAppointment->execute();
            
            // Si hay una cita, no permitir marcar como no disponible
            if ($checkAppointment->rowCount() > 0 && $slot['is_available'] == 0) {
                $db->rollBack();
                sendJsonResponse(409, [
                    'error' => 'No se puede marcar como no disponible un horario con citas existentes',
                    'time_slot' => $slot['time_slot']
                ]);
            }
            
            // Insertar el registro de disponibilidad
            $insertStmt->bindParam(':doctor_id', $doctorId);
            $insertStmt->bindParam(':date', $date);
            $insertStmt->bindParam(':time_slot', $slot['time_slot']);
            $insertStmt->bindParam(':is_available', $slot['is_available']);
            $insertStmt->execute();
        }
        
        // Confirmar transacción
        $db->commit();
        
        sendJsonResponse(200, [
            'success' => true,
            'message' => 'Disponibilidad actualizada con éxito'
        ]);
        
    } catch (PDOException $e) {
        // Revertir transacción en caso de error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        
        sendJsonResponse(500, ['error' => 'Error en la base de datos: ' . $e->getMessage()]);
    }
}

// Función para generar horarios predeterminados
function generateDefaultAvailability($db, $doctorId, $date) {
    $availability = [];
    
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
            
            $availability[] = [
                'time_slot' => $timeSlot,
                'is_available' => 1
            ];
        }
    }
    
    return $availability;
}
?>
