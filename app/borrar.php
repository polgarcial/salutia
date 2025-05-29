<?php
// Script simple para eliminar citas

// Mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Permitir CORS
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Obtener ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID no válido']);
    exit;
}

// Conexión a la base de datos
$mysqli = new mysqli('localhost', 'root', '', 'salutia');

// Verificar conexión
if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión: ' . $mysqli->connect_error]);
    exit;
}

// Eliminar la cita
$query = "DELETE FROM appointments WHERE id = $id";
$result = $mysqli->query($query);

if ($result) {
    echo json_encode([
        'success' => true,
        'message' => 'Cita eliminada correctamente',
        'affected_rows' => $mysqli->affected_rows,
        'id' => $id
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error al eliminar: ' . $mysqli->error,
        'id' => $id
    ]);
}

$mysqli->close();
?>
