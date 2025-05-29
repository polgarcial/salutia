<?php
/**
 * API para gestionar los horarios de los médicos
 * Este script maneja las operaciones CRUD para los horarios y días no disponibles
 */

// Configuración de cabeceras
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Incluir archivo de configuración de la base de datos
require_once __DIR__ . '/../backend/config/database_class.php';

// Crear instancia de la base de datos
$database = new Database();
$conn = $database->getConnection();

// Manejar solicitud OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Obtener el tipo de acción de la solicitud
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Función para registrar mensajes de depuración
function debug_log($message, $data = []) {
    $log_file = __DIR__ . '/debug_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "$timestamp - $message";
    
    if (!empty($data)) {
        $log_message .= ": " . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    file_put_contents($log_file, $log_message . "\n", FILE_APPEND);
}

// Procesar la solicitud según la acción
switch ($action) {
    case 'get_schedules':
        getSchedules($conn);
        break;
    case 'save_schedule':
        saveSchedule($conn);
        break;
    case 'update_schedule':
        updateSchedule($conn);
        break;
    case 'delete_schedule':
        deleteSchedule($conn);
        break;
    case 'get_unavailable_days':
        getUnavailableDays($conn);
        break;
    case 'save_unavailable_day':
        saveUnavailableDay($conn);
        break;
    case 'delete_unavailable_day':
        deleteUnavailableDay($conn);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

/**
 * Obtener los horarios de un médico
 */
function getSchedules($conn) {
    // Verificar que se haya proporcionado el ID del médico
    if (!isset($_GET['doctor_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID del médico no proporcionado']);
        return;
    }
    
    $doctor_id = $_GET['doctor_id'];
    
    try {
        // Consultar los horarios del médico
        $query = "SELECT * FROM doctor_weekly_schedules WHERE doctor_id = ? ORDER BY day_of_week, start_time";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $doctor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $schedules = [];
        while ($row = $result->fetch_assoc()) {
            // Convertir nombres de días de la semana
            $dayNames = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
            
            $schedules[] = [
                'id' => $row['id'],
                'day' => $row['day_of_week'],
                'dayName' => $dayNames[$row['day_of_week']],
                'startTime' => $row['start_time'],
                'endTime' => $row['end_time']
            ];
        }
        
        echo json_encode(['success' => true, 'schedules' => $schedules]);
    } catch (Exception $e) {
        debug_log('Error al obtener horarios', ['error' => $e->getMessage()]);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al obtener horarios: ' . $e->getMessage()]);
    }
}

/**
 * Guardar un nuevo horario
 */
function saveSchedule($conn) {
    // Obtener datos del cuerpo de la solicitud
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Verificar que los datos sean válidos
    if ($data === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Error en el formato de datos']);
        return;
    }
    
    // Verificar que todos los campos requeridos estén presentes
    $required_fields = ['doctor_id', 'day', 'startTime', 'endTime'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Campo requerido faltante: $field"]);
            return;
        }
    }
    
    try {
        // Insertar el nuevo horario
        $query = "INSERT INTO doctor_weekly_schedules (doctor_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiss", $data['doctor_id'], $data['day'], $data['startTime'], $data['endTime']);
        $stmt->execute();
        
        // Verificar si se insertó correctamente
        if ($stmt->affected_rows > 0) {
            $schedule_id = $conn->insert_id;
            echo json_encode(['success' => true, 'message' => 'Horario guardado correctamente', 'id' => $schedule_id]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al guardar el horario']);
        }
    } catch (Exception $e) {
        debug_log('Error al guardar horario', ['error' => $e->getMessage(), 'data' => $data]);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al guardar el horario: ' . $e->getMessage()]);
    }
}

/**
 * Actualizar un horario existente
 */
function updateSchedule($conn) {
    // Obtener datos del cuerpo de la solicitud
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Verificar que los datos sean válidos
    if ($data === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Error en el formato de datos']);
        return;
    }
    
    // Verificar que todos los campos requeridos estén presentes
    $required_fields = ['id', 'doctor_id', 'day', 'startTime', 'endTime'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Campo requerido faltante: $field"]);
            return;
        }
    }
    
    try {
        // Actualizar el horario
        $query = "UPDATE doctor_weekly_schedules SET day_of_week = ?, start_time = ?, end_time = ? WHERE id = ? AND doctor_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issii", $data['day'], $data['startTime'], $data['endTime'], $data['id'], $data['doctor_id']);
        $stmt->execute();
        
        // Verificar si se actualizó correctamente
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Horario actualizado correctamente']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Horario no encontrado o sin cambios']);
        }
    } catch (Exception $e) {
        debug_log('Error al actualizar horario', ['error' => $e->getMessage(), 'data' => $data]);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el horario: ' . $e->getMessage()]);
    }
}

/**
 * Eliminar un horario
 */
function deleteSchedule($conn) {
    // Verificar que se hayan proporcionado los IDs necesarios
    if (!isset($_GET['id']) || !isset($_GET['doctor_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'IDs no proporcionados']);
        return;
    }
    
    $schedule_id = $_GET['id'];
    $doctor_id = $_GET['doctor_id'];
    
    try {
        // Eliminar el horario
        $query = "DELETE FROM doctor_weekly_schedules WHERE id = ? AND doctor_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $schedule_id, $doctor_id);
        $stmt->execute();
        
        // Verificar si se eliminó correctamente
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Horario eliminado correctamente']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Horario no encontrado']);
        }
    } catch (Exception $e) {
        debug_log('Error al eliminar horario', ['error' => $e->getMessage(), 'id' => $schedule_id, 'doctor_id' => $doctor_id]);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al eliminar el horario: ' . $e->getMessage()]);
    }
}

/**
 * Obtener los días no disponibles de un médico
 */
function getUnavailableDays($conn) {
    // Verificar que se haya proporcionado el ID del médico
    if (!isset($_GET['doctor_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID del médico no proporcionado']);
        return;
    }
    
    $doctor_id = $_GET['doctor_id'];
    
    try {
        // Consultar los días no disponibles del médico
        $query = "SELECT * FROM doctor_unavailable_days WHERE doctor_id = ? ORDER BY start_date";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $doctor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $unavailableDays = [];
        while ($row = $result->fetch_assoc()) {
            $unavailableDays[] = [
                'id' => $row['id'],
                'startDate' => $row['start_date'],
                'endDate' => $row['end_date'],
                'reason' => $row['reason']
            ];
        }
        
        echo json_encode(['success' => true, 'unavailableDays' => $unavailableDays]);
    } catch (Exception $e) {
        debug_log('Error al obtener días no disponibles', ['error' => $e->getMessage()]);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al obtener días no disponibles: ' . $e->getMessage()]);
    }
}

/**
 * Guardar un nuevo día no disponible
 */
function saveUnavailableDay($conn) {
    // Obtener datos del cuerpo de la solicitud
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Verificar que los datos sean válidos
    if ($data === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Error en el formato de datos']);
        return;
    }
    
    // Verificar que todos los campos requeridos estén presentes
    $required_fields = ['doctor_id', 'startDate', 'endDate', 'reason'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Campo requerido faltante: $field"]);
            return;
        }
    }
    
    try {
        // Insertar el nuevo día no disponible
        $query = "INSERT INTO doctor_unavailable_days (doctor_id, start_date, end_date, reason) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isss", $data['doctor_id'], $data['startDate'], $data['endDate'], $data['reason']);
        $stmt->execute();
        
        // Verificar si se insertó correctamente
        if ($stmt->affected_rows > 0) {
            $day_id = $conn->insert_id;
            echo json_encode(['success' => true, 'message' => 'Día no disponible guardado correctamente', 'id' => $day_id]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al guardar el día no disponible']);
        }
    } catch (Exception $e) {
        debug_log('Error al guardar día no disponible', ['error' => $e->getMessage(), 'data' => $data]);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al guardar el día no disponible: ' . $e->getMessage()]);
    }
}

/**
 * Eliminar un día no disponible
 */
function deleteUnavailableDay($conn) {
    // Verificar que se hayan proporcionado los IDs necesarios
    if (!isset($_GET['id']) || !isset($_GET['doctor_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'IDs no proporcionados']);
        return;
    }
    
    $day_id = $_GET['id'];
    $doctor_id = $_GET['doctor_id'];
    
    try {
        // Eliminar el día no disponible
        $query = "DELETE FROM doctor_unavailable_days WHERE id = ? AND doctor_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $day_id, $doctor_id);
        $stmt->execute();
        
        // Verificar si se eliminó correctamente
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Día no disponible eliminado correctamente']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Día no disponible no encontrado']);
        }
    } catch (Exception $e) {
        debug_log('Error al eliminar día no disponible', ['error' => $e->getMessage(), 'id' => $day_id, 'doctor_id' => $doctor_id]);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al eliminar el día no disponible: ' . $e->getMessage()]);
    }
}
