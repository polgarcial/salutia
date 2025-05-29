<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Función para registrar mensajes de depuración
function debug_log($message, $data = []) {
    $log_file = __DIR__ . '/fix_table_log.txt';
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
    debug_log('Iniciando corrección de la tabla appointments');
    
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
    
    if ($result->num_rows > 0) {
        debug_log('La tabla appointments existe, verificando su estructura');
        
        // Obtener la estructura actual de la tabla
        $structure_query = "DESCRIBE appointments";
        $structure_result = $conn->query($structure_query);
        
        $columns = [];
        while ($row = $structure_result->fetch_assoc()) {
            $columns[$row['Field']] = $row;
        }
        
        debug_log('Estructura actual de la tabla', $columns);
        
        // Verificar y añadir columnas faltantes
        $required_columns = [
            'id' => "ALTER TABLE appointments ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY",
            'patient_id' => "ALTER TABLE appointments ADD COLUMN patient_id VARCHAR(50) NOT NULL",
            'patient_name' => "ALTER TABLE appointments ADD COLUMN patient_name VARCHAR(100) NOT NULL",
            'patient_email' => "ALTER TABLE appointments ADD COLUMN patient_email VARCHAR(100) NOT NULL",
            'doctor_id' => "ALTER TABLE appointments ADD COLUMN doctor_id VARCHAR(50) NOT NULL",
            'doctor_name' => "ALTER TABLE appointments ADD COLUMN doctor_name VARCHAR(100) NOT NULL",
            'reason' => "ALTER TABLE appointments ADD COLUMN reason TEXT NOT NULL",
            'status' => "ALTER TABLE appointments ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending'",
            'created_at' => "ALTER TABLE appointments ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
        ];
        
        // Verificar si existen las columnas de fecha y hora
        $date_columns = [
            'appointment_date' => "ALTER TABLE appointments ADD COLUMN appointment_date VARCHAR(20) NOT NULL",
            'appointment_time' => "ALTER TABLE appointments ADD COLUMN appointment_time VARCHAR(10) NOT NULL",
            'date' => "ALTER TABLE appointments ADD COLUMN date VARCHAR(20) NOT NULL",
            'time' => "ALTER TABLE appointments ADD COLUMN time VARCHAR(10) NOT NULL"
        ];
        
        $has_date_column = isset($columns['date']) || isset($columns['appointment_date']);
        $has_time_column = isset($columns['time']) || isset($columns['appointment_time']);
        
        if (!$has_date_column) {
            $required_columns['date'] = $date_columns['date'];
        }
        
        if (!$has_time_column) {
            $required_columns['time'] = $date_columns['time'];
        }
        
        // Añadir columnas faltantes
        $columns_added = 0;
        foreach ($required_columns as $column => $query) {
            if (!isset($columns[$column])) {
                debug_log("Añadiendo columna faltante: $column");
                
                if ($conn->query($query)) {
                    debug_log("Columna $column añadida correctamente");
                    $columns_added++;
                } else {
                    debug_log("Error al añadir columna $column: " . $conn->error);
                }
            }
        }
        
        if ($columns_added > 0) {
            debug_log("Se añadieron $columns_added columnas a la tabla appointments");
        } else {
            debug_log("No fue necesario añadir columnas a la tabla appointments");
        }
    } else {
        debug_log('La tabla appointments no existe, creándola');
        
        // Crear la tabla appointments
        $create_table_query = "CREATE TABLE appointments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id VARCHAR(50) NOT NULL,
            patient_name VARCHAR(100) NOT NULL,
            patient_email VARCHAR(100) NOT NULL,
            doctor_id VARCHAR(50) NOT NULL,
            doctor_name VARCHAR(100) NOT NULL,
            reason TEXT NOT NULL,
            date VARCHAR(20) NOT NULL,
            time VARCHAR(10) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($create_table_query)) {
            debug_log("Tabla appointments creada correctamente");
        } else {
            throw new Exception("Error al crear la tabla appointments: " . $conn->error);
        }
    }
    
    // Verificar la estructura final de la tabla
    $final_structure_query = "DESCRIBE appointments";
    $final_structure_result = $conn->query($final_structure_query);
    
    $final_columns = [];
    while ($row = $final_structure_result->fetch_assoc()) {
        $final_columns[$row['Field']] = $row;
    }
    
    debug_log('Estructura final de la tabla', $final_columns);
    
    // Cerrar la conexión
    $conn->close();
    
    // Responder con éxito
    echo json_encode([
        'success' => true,
        'message' => 'Corrección de la tabla appointments completada',
        'columns' => $final_columns
    ]);
    
} catch (Exception $e) {
    debug_log('Error al corregir la tabla appointments', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al corregir la tabla appointments: ' . $e->getMessage()
    ]);
}
?>
