<?php
// Desactivar la salida de errores HTML para evitar que se mezcle con JSON
ini_set('display_errors', 0);
error_reporting(0);

// Configuración de CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Manejar solicitudes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Función para devolver respuesta JSON
function sendJsonResponse($status, $data) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Incluir archivo de configuración de la base de datos
require_once '../config/database.php';

try {
    // Crear conexión a la base de datos
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8");
    
    // Manejar solicitud GET para obtener horarios disponibles
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Obtener parámetros
        $doctorId = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
        $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
        $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d', strtotime('+14 days'));
        
        // Validar parámetros
        if ($doctorId <= 0) {
            sendJsonResponse(400, [
                'success' => false,
                'error' => 'ID de médico inválido'
            ]);
        }
        
        // Validar fechas
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            sendJsonResponse(400, [
                'success' => false,
                'error' => 'Formato de fecha inválido. Use YYYY-MM-DD'
            ]);
        }
        
        // Verificar que el médico existe
        $checkDoctorQuery = "SELECT id, first_name, last_name, specialty FROM users WHERE id = :id AND role = 'doctor'";
        $stmt = $conn->prepare($checkDoctorQuery);
        $stmt->bindParam(':id', $doctorId);
        $stmt->execute();
        
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$doctor) {
            sendJsonResponse(404, [
                'success' => false,
                'error' => 'Médico no encontrado'
            ]);
        }
        
        // Obtener horarios disponibles
        $query = "SELECT date, time FROM doctor_availability 
                 WHERE doctor_id = :doctor_id 
                 AND date BETWEEN :start_date AND :end_date 
                 AND is_available = 1
                 ORDER BY date, time";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':doctor_id', $doctorId);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formatear los horarios para la respuesta
        $availableSlots = [];
        foreach ($slots as $slot) {
            // Formatear la hora para mostrar (12h en lugar de 24h)
            $time = new DateTime($slot['time']);
            $formattedTime = $time->format('g:i A'); // Ejemplo: 9:00 AM
            
            $availableSlots[] = [
                'date' => $slot['date'],
                'time' => $slot['time'],
                'formatted_time' => $formattedTime
            ];
        }
        
        // Devolver respuesta con los horarios disponibles
        sendJsonResponse(200, [
            'success' => true,
            'doctor' => [
                'id' => $doctor['id'],
                'name' => $doctor['first_name'] . ' ' . $doctor['last_name'],
                'specialty' => $doctor['specialty']
            ],
            'available_slots' => $availableSlots
        ]);
    } 
    // Manejar solicitud POST para actualizar disponibilidad
    else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Obtener datos del cuerpo de la solicitud
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!$data) {
            sendJsonResponse(400, [
                'success' => false,
                'error' => 'Datos inválidos'
            ]);
        }
        
        // Validar datos requeridos
        if (!isset($data['doctor_id']) || !isset($data['date']) || !isset($data['time'])) {
            sendJsonResponse(400, [
                'success' => false,
                'error' => 'Faltan datos requeridos (doctor_id, date, time)'
            ]);
        }
        
        $doctorId = intval($data['doctor_id']);
        $date = $data['date'];
        $time = $data['time'];
        $isAvailable = isset($data['is_available']) ? (bool)$data['is_available'] : true;
        
        // Verificar que el médico existe
        $checkDoctorQuery = "SELECT id FROM users WHERE id = :id AND role = 'doctor'";
        $stmt = $conn->prepare($checkDoctorQuery);
        $stmt->bindParam(':id', $doctorId);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            sendJsonResponse(404, [
                'success' => false,
                'error' => 'Médico no encontrado'
            ]);
        }
        
        // Verificar si ya existe el registro
        $checkQuery = "SELECT id FROM doctor_availability WHERE doctor_id = :doctor_id AND date = :date AND time = :time";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bindParam(':doctor_id', $doctorId);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':time', $time);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Actualizar disponibilidad existente
            $updateQuery = "UPDATE doctor_availability SET is_available = :is_available WHERE doctor_id = :doctor_id AND date = :date AND time = :time";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bindParam(':doctor_id', $doctorId);
            $stmt->bindParam(':date', $date);
            $stmt->bindParam(':time', $time);
            $stmt->bindParam(':is_available', $isAvailable, PDO::PARAM_BOOL);
            $stmt->execute();
            
            sendJsonResponse(200, [
                'success' => true,
                'message' => 'Disponibilidad actualizada correctamente'
            ]);
        } else {
            // Crear nueva disponibilidad
            $insertQuery = "INSERT INTO doctor_availability (doctor_id, date, time, is_available) VALUES (:doctor_id, :date, :time, :is_available)";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bindParam(':doctor_id', $doctorId);
            $stmt->bindParam(':date', $date);
            $stmt->bindParam(':time', $time);
            $stmt->bindParam(':is_available', $isAvailable, PDO::PARAM_BOOL);
            $stmt->execute();
            
            sendJsonResponse(201, [
                'success' => true,
                'message' => 'Disponibilidad creada correctamente'
            ]);
        }
    } else {
        // Método no permitido
        sendJsonResponse(405, [
            'success' => false,
            'error' => 'Método no permitido'
        ]);
    }
} catch (PDOException $e) {
    // Error de base de datos
    sendJsonResponse(500, [
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Otro tipo de error
    sendJsonResponse(500, [
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
