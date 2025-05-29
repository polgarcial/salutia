<?php
/**
 * Script para eliminar directamente una cita de la base de datos MySQL
 * Este script utiliza mysqli en lugar de PDO para mayor compatibilidad
 */

// Habilitar CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

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

// Si es una solicitud OPTIONS, terminar aquí (pre-flight de CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Obtener el ID de la cita (de GET o POST)
$id = null;

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    debug_log('ID obtenido de GET', ['id' => $id]);
} elseif (isset($_POST['id'])) {
    $id = $_POST['id'];
    debug_log('ID obtenido de POST', ['id' => $id]);
} else {
    // Intentar obtener de JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    debug_log('Datos recibidos', ['input' => $input, 'data' => $data]);
    
    if (isset($data['id'])) {
        $id = $data['id'];
        debug_log('ID obtenido de JSON', ['id' => $id]);
    }
}

// Verificar que tenemos un ID
if ($id === null) {
    echo json_encode(['success' => false, 'message' => 'ID de cita no proporcionado']);
    exit();
}

// Asegurarse de que el ID es un número
$id = intval($id);
debug_log('ID de cita a eliminar', ['id' => $id]);

// Conectar a la base de datos usando mysqli
$db_host = 'localhost';
$db_name = 'salutia';
$db_user = 'root';
$db_pass = '';

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Verificar conexión
if ($mysqli->connect_error) {
    debug_log('Error de conexión', ['error' => $mysqli->connect_error]);
    echo json_encode(['success' => false, 'message' => 'Error de conexión: ' . $mysqli->connect_error]);
    exit();
}

debug_log('Conexión establecida');

// Verificar si la tabla existe
$result = $mysqli->query("SHOW TABLES LIKE 'appointments'");
if ($result->num_rows == 0) {
    debug_log('La tabla appointments no existe');
    echo json_encode(['success' => false, 'message' => 'La tabla appointments no existe']);
    exit();
}

debug_log('Tabla appointments encontrada');

// Verificar si la cita existe
$check_query = "SELECT * FROM appointments WHERE id = $id";
$check_result = $mysqli->query($check_query);

if ($check_result && $check_result->num_rows > 0) {
    $appointment = $check_result->fetch_assoc();
    debug_log('Cita encontrada', ['appointment' => $appointment]);
    
    // Eliminar la cita
    $delete_query = "DELETE FROM appointments WHERE id = $id";
    debug_log('Ejecutando consulta', ['query' => $delete_query]);
    
    if ($mysqli->query($delete_query)) {
        debug_log('Cita eliminada correctamente', ['affected_rows' => $mysqli->affected_rows]);
        echo json_encode([
            'success' => true, 
            'message' => 'Cita eliminada correctamente',
            'affected_rows' => $mysqli->affected_rows
        ]);
    } else {
        debug_log('Error al eliminar la cita', ['error' => $mysqli->error]);
        echo json_encode([
            'success' => false, 
            'message' => 'Error al eliminar la cita: ' . $mysqli->error
        ]);
    }
} else {
    debug_log('Cita no encontrada', ['id' => $id]);
    echo json_encode([
        'success' => false, 
        'message' => 'No se encontró la cita con ID: ' . $id
    ]);
}

// Cerrar conexión
$mysqli->close();
?>
