<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Función para registrar mensajes de depuración
function debug_log($message, $data = []) {
    $log_file = __DIR__ . '/debug_test.txt';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "$timestamp - $message";
    
    if (!empty($data)) {
        $log_message .= ": " . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    file_put_contents($log_file, $log_message . "\n", FILE_APPEND);
}

// Incluir archivo de configuración de la base de datos
require_once __DIR__ . '/database_class.php';

// Habilitar CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

try {
    debug_log('Iniciando prueba de conexión a la base de datos');
    
    // Crear instancia de la base de datos
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception("Error al conectar con la base de datos");
    }
    
    debug_log('Conexión a la base de datos establecida');
    
    // Verificar si la tabla appointments existe
    $check_table_query = "SHOW TABLES LIKE 'appointments'";
    $result = $conn->query($check_table_query);
    
    if (!$result) {
        throw new Exception("Error al verificar si existe la tabla: " . $conn->error);
    }
    
    $table_exists = ($result->num_rows > 0);
    debug_log('Tabla appointments existe', ['exists' => $table_exists]);
    
    if ($table_exists) {
        // Obtener la estructura de la tabla
        $structure_query = "DESCRIBE appointments";
        $structure_result = $conn->query($structure_query);
        
        if (!$structure_result) {
            throw new Exception("Error al obtener la estructura de la tabla: " . $conn->error);
        }
        
        $columns = [];
        while ($row = $structure_result->fetch_assoc()) {
            $columns[] = $row;
        }
        
        debug_log('Estructura de la tabla appointments', $columns);
        
        // Verificar si existen las columnas necesarias
        $has_appointment_date = false;
        $has_appointment_time = false;
        $has_date = false;
        $has_time = false;
        
        foreach ($columns as $column) {
            if ($column['Field'] === 'appointment_date') {
                $has_appointment_date = true;
            }
            if ($column['Field'] === 'appointment_time') {
                $has_appointment_time = true;
            }
            if ($column['Field'] === 'date') {
                $has_date = true;
            }
            if ($column['Field'] === 'time') {
                $has_time = true;
            }
        }
        
        debug_log('Columnas de fecha/hora', [
            'has_appointment_date' => $has_appointment_date,
            'has_appointment_time' => $has_appointment_time,
            'has_date' => $has_date,
            'has_time' => $has_time
        ]);
        
        // Intentar insertar un registro de prueba
        debug_log('Intentando insertar un registro de prueba');
        
        // Determinar qué columnas usar
        if ($has_appointment_date && $has_appointment_time) {
            $query = "INSERT INTO appointments 
                      (patient_id, patient_name, patient_email, doctor_id, doctor_name, reason, appointment_date, appointment_time, status) 
                      VALUES ('test', 'Test Patient', 'test@example.com', 'test', 'Test Doctor', 'Test Reason', '2025-06-01', '10:00', 'test')";
        } else if ($has_date && $has_time) {
            $query = "INSERT INTO appointments 
                      (patient_id, patient_name, patient_email, doctor_id, doctor_name, reason, date, time, status) 
                      VALUES ('test', 'Test Patient', 'test@example.com', 'test', 'Test Doctor', 'Test Reason', '2025-06-01', '10:00', 'test')";
        } else {
            throw new Exception("No se encontraron las columnas necesarias para la fecha y hora");
        }
        
        $insert_result = $conn->query($query);
        
        if (!$insert_result) {
            throw new Exception("Error al insertar registro de prueba: " . $conn->error);
        }
        
        $insert_id = $conn->insert_id;
        debug_log('Registro de prueba insertado correctamente', ['id' => $insert_id]);
        
        // Eliminar el registro de prueba
        $delete_query = "DELETE FROM appointments WHERE patient_id = 'test' AND doctor_id = 'test' AND status = 'test'";
        $delete_result = $conn->query($delete_query);
        
        if (!$delete_result) {
            debug_log('Error al eliminar registro de prueba', ['error' => $conn->error]);
        } else {
            debug_log('Registro de prueba eliminado correctamente');
        }
    } else {
        // Crear la tabla
        debug_log('Creando tabla appointments');
        
        $create_table_query = "CREATE TABLE appointments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id VARCHAR(50) NOT NULL,
            patient_name VARCHAR(100) NOT NULL,
            patient_email VARCHAR(100) NOT NULL,
            doctor_id VARCHAR(50) NOT NULL,
            doctor_name VARCHAR(100) NOT NULL,
            reason TEXT NOT NULL,
            appointment_date VARCHAR(20) NOT NULL,
            appointment_time VARCHAR(10) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $create_result = $conn->query($create_table_query);
        
        if (!$create_result) {
            throw new Exception("Error al crear la tabla appointments: " . $conn->error);
        }
        
        debug_log('Tabla appointments creada correctamente');
    }
    
    // Responder con éxito
    echo json_encode([
        'success' => true,
        'message' => 'Prueba de base de datos completada con éxito',
        'table_exists' => $table_exists,
        'columns' => $columns ?? []
    ]);
    
} catch (Exception $e) {
    debug_log('Error en la prueba de base de datos', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en la prueba de base de datos: ' . $e->getMessage()
    ]);
}
?>
