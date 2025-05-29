<?php
/**
 * Script simple para eliminar una cita de la base de datos
 */

// Habilitar CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
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

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Obtener el ID de la cita (ya sea de JSON o de la URL)
$id = null;

// Verificar si viene en la URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    debug_log('ID de cita obtenido de URL', ['id' => $id]);
} else {
    // Intentar obtener del cuerpo JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    debug_log('Datos recibidos', ['data' => $data]);
    
    if (isset($data['id'])) {
        $id = $data['id'];
        debug_log('ID de cita obtenido de JSON', ['id' => $id]);
    }
}

// Verificar que tenemos un ID
if ($id === null) {
    echo json_encode(['success' => false, 'message' => 'ID de cita no proporcionado']);
    exit();
}

debug_log('ID de cita a eliminar', ['id' => $id]);

// Conectar a la base de datos
$db_host = 'localhost';
$db_name = 'salutia';
$db_user = 'root';
$db_pass = '';

try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Ejecutar una consulta SQL directa para eliminar la cita
    $sql = "DELETE FROM appointments WHERE id = " . intval($id);
    debug_log('Ejecutando consulta SQL', ['sql' => $sql]);
    
    $result = $conn->exec($sql);
    debug_log('Resultado de la eliminación', ['filas_afectadas' => $result]);
    
    echo json_encode(['success' => true, 'message' => 'Cita eliminada correctamente', 'rows_affected' => $result]);
    
} catch (PDOException $e) {
    debug_log('Error al eliminar cita', ['error' => $e->getMessage()]);
    echo json_encode(['success' => false, 'message' => 'Error al eliminar la cita: ' . $e->getMessage()]);
}
?>
