<?php
/**
 * API para obtener las citas próximas (aceptadas) de un médico
 * Este script devuelve todas las citas aceptadas para un médico específico
 */

// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Configuración de cabeceras
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Incluir archivo de configuración de la base de datos
require_once __DIR__ . '/database_class.php';

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

// Manejar solicitud OPTIONS para CORS
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

// Verificar que se haya proporcionado el ID del médico
if (!isset($_GET['doctor_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID del médico no proporcionado']);
    exit();
}

$doctor_id = $_GET['doctor_id'];
debug_log('ID del médico recibido para citas próximas', ['doctor_id' => $doctor_id]);

// Asegurarse de que el ID del médico sea un entero
if (!is_numeric($doctor_id)) {
    debug_log('ID del médico no es numérico, usando 1 por defecto', ['doctor_id' => $doctor_id]);
    $doctor_id = 1;
}

try {
    // Crear instancia de la base de datos
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception("Error al conectar con la base de datos");
    }
    
    debug_log('Conexión a la base de datos establecida para obtener citas próximas');
    
    // Verificar si la tabla appointments existe
    $check_table_query = "SHOW TABLES LIKE 'appointments'";
    $check_table = $conn->query($check_table_query);
    
    debug_log('Verificando si existe la tabla appointments', ['exists' => ($check_table->rowCount() > 0)]);
    
    if ($check_table->rowCount() == 0) {
        // Si la tabla no existe, devolver un array vacío
        debug_log('La tabla appointments no existe');
        echo json_encode(['success' => true, 'appointments' => []]);
        exit();
    }
    
    // Obtener la estructura de la tabla para depuración
    $table_structure = [];
    $structure_query = "DESCRIBE appointments";
    $structure_result = $conn->query($structure_query);
    $table_structure = $structure_result->fetchAll(PDO::FETCH_ASSOC);
    debug_log('Estructura de la tabla appointments', $table_structure);
    
    // Verificar qué columnas existen en la tabla appointments
    $check_date_column = $conn->query("SHOW COLUMNS FROM appointments LIKE 'appointment_date'");
    $check_old_date_column = $conn->query("SHOW COLUMNS FROM appointments LIKE 'date'");
    
    $use_appointment_date = $check_date_column->rowCount() > 0;
    $use_old_date = $check_old_date_column->rowCount() > 0;
    
    debug_log('Verificando columnas', [
        'appointment_date_exists' => $use_appointment_date,
        'date_exists' => $use_old_date
    ]);
    
    // Verificar si existe la columna notes
    $check_notes_column = $conn->query("SHOW COLUMNS FROM appointments LIKE 'notes'");
    $has_notes_column = $check_notes_column->rowCount() > 0;
    debug_log('Columna notes existe', ['exists' => $has_notes_column]);
    
    // Preparar la consulta según las columnas disponibles
    if ($use_appointment_date) {
        $query = "SELECT *, '' as notes FROM appointments 
                  WHERE doctor_id = :doctor_id 
                  AND status = 'accepted' 
                  ORDER BY appointment_date ASC, appointment_time ASC";
        debug_log('Usando columnas appointment_date y appointment_time');
    } else if ($use_old_date) {
        $query = "SELECT *, 
                  date as appointment_date, 
                  time as appointment_time,
                  '' as notes 
                  FROM appointments 
                  WHERE doctor_id = :doctor_id 
                  AND status = 'accepted' 
                  ORDER BY date ASC, time ASC";
        debug_log('Usando columnas date y time (formato antiguo)');
    } else {
        $query = "SELECT *, '' as notes FROM appointments 
                  WHERE doctor_id = :doctor_id 
                  AND status = 'accepted' 
                  ORDER BY created_at ASC";
        debug_log('No se encontraron columnas de fecha/hora, ordenando por created_at');
    }
    
    // Si la columna notes existe, usar la consulta correcta
    if ($has_notes_column) {
        $query = "SELECT * FROM appointments 
                  WHERE doctor_id = :doctor_id 
                  AND status = 'accepted' 
                  ORDER BY appointment_date ASC, appointment_time ASC";
        debug_log('Usando consulta con columna notes real');
    }
    
    // Preparar y ejecutar la consulta
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta");
    }
    
    $stmt->bindParam(':doctor_id', $doctor_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // Obtener los resultados
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    debug_log('Citas próximas obtenidas', ['count' => count($appointments)]);
    
    // Formatear las fechas para mejor visualización
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
            } else if (strpos($date, '/') !== false) {
                $appointment['formatted_date'] = $date;
            } else {
                $appointment['formatted_date'] = $date;
            }
        } else if (isset($appointment['date'])) {
            $date = $appointment['date'];
            if (strpos($date, '-') !== false) {
                $date_parts = explode('-', $date);
                if (count($date_parts) === 3) {
                    $appointment['formatted_date'] = $date_parts[2] . '/' . $date_parts[1] . '/' . $date_parts[0];
                } else {
                    $appointment['formatted_date'] = $date;
                }
            } else if (strpos($date, '/') !== false) {
                $appointment['formatted_date'] = $date;
            } else {
                $appointment['formatted_date'] = $date;
            }
        } else {
            $appointment['formatted_date'] = 'N/A';
        }
    }
    
    // Devolver las citas en formato JSON
    echo json_encode(['success' => true, 'appointments' => $appointments]);
    
} catch (Exception $e) {
    debug_log('Error al obtener citas próximas', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener citas próximas: ' . $e->getMessage()]);
}
