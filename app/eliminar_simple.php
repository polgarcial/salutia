<?php
// Script ultra simple para eliminar citas

// Permitir CORS
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Obtener ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Registrar la solicitud
file_put_contents(__DIR__ . '/eliminar_log.txt', date('Y-m-d H:i:s') . " - Solicitud de eliminación para ID: $id\n", FILE_APPEND);

// Conexión a la base de datos
$mysqli = new mysqli('localhost', 'root', '', 'salutia');

// Eliminar la cita directamente
$query = "DELETE FROM appointments WHERE id = $id";
$result = $mysqli->query($query);

echo json_encode([
    'success' => true,
    'message' => 'Cita con ID ' . $id . ' procesada',
    'affected_rows' => $mysqli->affected_rows
]);

$mysqli->close();
?>
