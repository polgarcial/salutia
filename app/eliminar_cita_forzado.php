<?php
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
    $log_file = __DIR__ . '/delete_log.txt';
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
    $id = $_GET['id'];
    debug_log("ID obtenido de GET", ["id" => $id]);
} 
// Verificar si viene en POST
else if (isset($_POST['id'])) {
    $id = $_POST['id'];
    debug_log("ID obtenido de POST", ["id" => $id]);
} 
// Verificar si viene en JSON
else {
    $json = file_get_contents('php://input');
    debug_log("Datos JSON recibidos", ["json" => $json]);
    
    if (!empty($json)) {
        $data = json_decode($json, true);
        if (isset($data['id'])) {
            $id = $data['id'];
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

// Asegurarse de que el ID es un número
$id = intval($id);
debug_log("ID de cita a eliminar (convertido a entero)", ["id" => $id]);

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
    
    // Verificar si la cita existe
    $check_query = "SELECT * FROM appointments WHERE id = $id";
    $check_result = $mysqli->query($check_query);
    
    if ($check_result && $check_result->num_rows > 0) {
        $appointment = $check_result->fetch_assoc();
        debug_log("Cita encontrada", ["appointment" => $appointment]);
        
        // Ejecutar la consulta de eliminación directamente
        $query = "DELETE FROM appointments WHERE id = $id";
        debug_log("Ejecutando consulta", ["query" => $query]);
        
        if ($mysqli->query($query)) {
            $affected_rows = $mysqli->affected_rows;
            debug_log("Cita eliminada correctamente", ["affected_rows" => $affected_rows]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Cita eliminada correctamente',
                'affected_rows' => $affected_rows,
                'id' => $id
            ]);
        } else {
            throw new Exception("Error al eliminar la cita: " . $mysqli->error);
        }
    } else {
        debug_log("Cita no encontrada", ["id" => $id]);
        
        // Intentar eliminar de todos modos
        $query = "DELETE FROM appointments WHERE id = $id";
        $mysqli->query($query);
        
        echo json_encode([
            'success' => false,
            'message' => 'No se encontró la cita con ID: ' . $id,
            'id' => $id
        ]);
    }
    
    // Cerrar conexión
    $mysqli->close();
    
} catch (Exception $e) {
    debug_log("Error", ["message" => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'id' => $id]);
}
?>
