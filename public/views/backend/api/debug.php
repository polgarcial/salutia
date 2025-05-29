<?php
// Archivo para depuración
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Crear archivo de log
$logFile = __DIR__ . '/debug.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Debug iniciado\n", FILE_APPEND);

// Función para escribir en el log
function debug_log($message) {
    global $logFile;
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

// Registrar información de la solicitud
debug_log("Método: " . $_SERVER['REQUEST_METHOD']);
debug_log("Headers: " . print_r(getallheaders(), true));

// Si es una solicitud POST, registrar el cuerpo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawData = file_get_contents('php://input');
    debug_log("Datos recibidos: " . $rawData);
    
    // Intentar decodificar JSON
    $data = json_decode($rawData, true);
    if ($data) {
        debug_log("JSON decodificado: " . print_r($data, true));
    } else {
        debug_log("Error al decodificar JSON: " . json_last_error_msg());
    }
}

// Responder con éxito
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Debug completado']);
?>
