<?php
/**
 * Script para eliminar una cita
 * Este script elimina una cita de la base de datos
 */

// Habilitar CORS para permitir solicitudes desde el frontend
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Si es una solicitud OPTIONS, terminar aquí (pre-flight de CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Función para registrar mensajes de depuración
function debug_log($message, $data = []) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'data' => $data
    ];
    
    $log_message = json_encode($log_entry, JSON_PRETTY_PRINT) . "\n";
    file_put_contents(__DIR__ . '/debug_log.txt', $log_message, FILE_APPEND);
}

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Obtener el ID de la cita
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['id']) || !is_numeric($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de cita no válido']);
    exit();
}

$appointment_id = intval($data['id']);
debug_log('Eliminando cita', ['id' => $appointment_id]);

// Conectar a la base de datos
$db_host = 'localhost';
$db_name = 'salutia';
$db_user = 'root';
$db_pass = '';

try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar si la tabla appointments existe
    $check_table = $conn->query("SHOW TABLES LIKE 'appointments'");
    $table_exists = $check_table->rowCount() > 0;
    debug_log('Tabla appointments existe', ['exists' => $table_exists]);
    
    if (!$table_exists) {
        // Crear la tabla si no existe
        $create_table = "CREATE TABLE IF NOT EXISTS appointments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id VARCHAR(50),
            patient_name VARCHAR(100),
            patient_email VARCHAR(100),
            doctor_id VARCHAR(50),
            doctor_name VARCHAR(100),
            reason TEXT,
            date DATE,
            time TIME,
            appointment_date DATE,
            appointment_time TIME,
            start_time TIME,
            end_time TIME,
            status VARCHAR(20),
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->exec($create_table);
        debug_log('Tabla appointments creada');
    }
    
    // Verificar la estructura de la tabla
    $check_columns = $conn->query("SHOW COLUMNS FROM appointments");
    $columns = $check_columns->fetchAll(PDO::FETCH_COLUMN);
    debug_log('Columnas en la tabla appointments', ['columns' => $columns]);
    
    // Verificar si la cita existe antes de eliminarla
    $check_sql = "SELECT * FROM appointments WHERE id = :id";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bindParam(':id', $appointment_id, PDO::PARAM_INT);
    $check_stmt->execute();
    $appointment = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($appointment) {
        debug_log('Cita encontrada', ['appointment' => $appointment]);
        
        // Eliminar la cita
        $sql = "DELETE FROM appointments WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $appointment_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $affected_rows = $stmt->rowCount();
        debug_log('Filas afectadas', ['count' => $affected_rows]);
        
        if ($affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Cita eliminada correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo eliminar la cita']);
        }
    } else {
        debug_log('Cita no encontrada', ['id' => $appointment_id]);
        echo json_encode(['success' => false, 'message' => 'No se encontró la cita con ID: ' . $appointment_id]);
    }
    
} catch (PDOException $e) {
    debug_log('Error al eliminar cita', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al eliminar la cita: ' . $e->getMessage()]);
}
?>
