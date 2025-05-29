<?php
/**
 * Script para eliminar directamente una cita de la base de datos
 */

// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Configuración de cabeceras
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

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

// Obtener el ID de la cita
$id = null;

// Verificar si viene en GET
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    debug_log("ID obtenido de GET", ["id" => $id]);
} 
// Verificar si viene en POST
else if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    debug_log("ID obtenido de POST", ["id" => $id]);
} 
// Verificar si viene en JSON
else {
    $json = file_get_contents('php://input');
    debug_log("Datos JSON recibidos", ["json" => $json]);
    
    if (!empty($json)) {
        $data = json_decode($json, true);
        if (isset($data['id'])) {
            $id = intval($data['id']);
            debug_log("ID obtenido de JSON", ["id" => $id]);
        }
    }
}

// Verificar que tenemos un ID
if ($id === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de cita no proporcionado']);
    debug_log("Error: ID de cita no proporcionado");
    exit();
}

// Conexión directa a la base de datos
$db_host = 'localhost';
$db_name = 'salutia';
$db_user = 'root';
$db_pass = '';

try {
    // Crear conexión
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    // Verificar conexión
    if ($mysqli->connect_error) {
        throw new Exception("Error de conexión: " . $mysqli->connect_error);
    }
    
    debug_log("Conexión a la base de datos establecida");
    
    // Verificar si la tabla existe
    $result = $mysqli->query("SHOW TABLES LIKE 'appointments'");
    if ($result->num_rows == 0) {
        throw new Exception("La tabla appointments no existe");
    }
    
    debug_log("Tabla appointments encontrada");
    
    // Ejecutar la consulta de eliminación directamente
    $query = "DELETE FROM appointments WHERE id = $id";
    debug_log("Ejecutando consulta", ["query" => $query]);
    
    if ($mysqli->query($query)) {
        $affected_rows = $mysqli->affected_rows;
        debug_log("Cita eliminada correctamente", ["affected_rows" => $affected_rows]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Cita eliminada correctamente',
            'affected_rows' => $affected_rows
        ]);
    } else {
        throw new Exception("Error al eliminar la cita: " . $mysqli->error);
    }
    
    // Cerrar conexión
    $mysqli->close();
    
} catch (Exception $e) {
    debug_log("Error", ["message" => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
