<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir archivos necesarios
require_once 'database_class.php';
require_once 'debug_log.php';

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
    $doctorId = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
    $date = isset($_GET['date']) ? $_GET['date'] : '';
    
    if (!$doctorId || !$date) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Faltan parámetros requeridos']);
        exit();
    }
    
    // Validar formato de fecha
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido']);
        exit();
    }
    
    // Obtener día de la semana (1 = lunes, 7 = domingo)
    $dayOfWeek = $dateObj->format('N');
    $dayNames = ['', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    $dayName = $dayNames[$dayOfWeek];
    
    // Crear instancia de la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener disponibilidad del médico para ese día de la semana
    $stmt = $db->prepare("
        SELECT start_time, end_time
        FROM doctor_availability
        WHERE doctor_id = :doctor_id AND day_of_week = :day_of_week
        ORDER BY start_time
    ");
    $stmt->bindParam(':doctor_id', $doctorId, PDO::PARAM_INT);
    $stmt->bindParam(':day_of_week', $dayName);
    $stmt->execute();
    
    $availability = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($availability)) {
        // No hay disponibilidad para este día
        echo json_encode([
            'success' => true,
            'slots' => []
        ]);
        exit();
    }
    
    // Obtener citas existentes para ese día y médico
    $stmt = $db->prepare("
        SELECT start_time, end_time
        FROM appointments
        WHERE doctor_id = :doctor_id AND appointment_date = :date
        AND status IN ('pending', 'confirmed')
        ORDER BY start_time
    ");
    $stmt->bindParam(':doctor_id', $doctorId, PDO::PARAM_INT);
    $stmt->bindParam(':date', $date);
    $stmt->execute();
    
    $bookedSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generar slots disponibles (intervalos de 30 minutos)
    $availableSlots = [];
    
    foreach ($availability as $slot) {
        $startTime = new DateTime($slot['start_time']);
        $endTime = new DateTime($slot['end_time']);
        
        // Generar slots de 30 minutos
        while ($startTime < $endTime) {
            $slotStart = $startTime->format('H:i:s');
            $startTime->add(new DateInterval('PT30M'));
            $slotEnd = $startTime->format('H:i:s');
            
            // Verificar si este slot está disponible (no hay citas que se superpongan)
            $isAvailable = true;
            foreach ($bookedSlots as $bookedSlot) {
                $bookedStart = new DateTime($bookedSlot['start_time']);
                $bookedEnd = new DateTime($bookedSlot['end_time']);
                
                // Si hay superposición, el slot no está disponible
                if (($startTime > $bookedStart && $startTime < $bookedEnd) ||
                    ($startTime <= $bookedStart && $endTime >= $bookedEnd)) {
                    $isAvailable = false;
                    break;
                }
            }
            
            if ($isAvailable) {
                $availableSlots[] = [
                    'start_time' => $slotStart,
                    'end_time' => $slotEnd
                ];
            }
        }
    }
    
    // Devolver respuesta
    echo json_encode([
        'success' => true,
        'slots' => $availableSlots
    ]);
    
} catch (Exception $e) {
    debug_log('Error en get_doctor_availability.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
