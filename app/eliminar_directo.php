<?php
// Script ultra simple para eliminar citas directamente de la base de datos

// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Permitir CORS
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Obtener ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Registrar la solicitud
$log_message = date('Y-m-d H:i:s') . " - Solicitud de eliminación para ID: $id\n";
file_put_contents(__DIR__ . '/eliminar_log.txt', $log_message, FILE_APPEND);

// Verificar ID
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID no válido']);
    exit;
}

// Conexión directa a MySQL
$mysqli = new mysqli('localhost', 'root', '', 'salutia');

// Verificar conexión
if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión: ' . $mysqli->connect_error]);
    exit;
}

// Ejecutar eliminación directa
$query = "DELETE FROM appointments WHERE id = $id";
$result = $mysqli->query($query);
$affected = $mysqli->affected_rows;

// Registrar resultado
$log_result = date('Y-m-d H:i:s') . " - Resultado de eliminación para ID: $id - Filas afectadas: $affected\n";
file_put_contents(__DIR__ . '/eliminar_log.txt', $log_result, FILE_APPEND);

// Devolver resultado
echo json_encode([
    'success' => ($affected > 0),
    'message' => ($affected > 0) ? 'Cita eliminada correctamente' : 'No se encontró la cita o ya fue eliminada',
    'affected_rows' => $affected,
    'id' => $id
]);

// Cerrar conexión
$mysqli->close();
?>
