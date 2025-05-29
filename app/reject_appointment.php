<?php
/**
 * Script para rechazar y eliminar una cita
 * Este script actualiza el estado de una cita a 'rechazada' y luego la elimina de la base de datos
 */

// Asegurarse de que no haya salida antes de los encabezados
ob_start();

// Habilitar CORS para permitir solicitudes desde el frontend
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");

// Si es una solicitud OPTIONS, terminar aquí (pre-flight de CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

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

// Función para registrar errores
function error_log_custom($message, $data = []) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'data' => $data
    ];
    
    $log_message = json_encode($log_entry, JSON_PRETTY_PRINT) . "\n";
    file_put_contents(__DIR__ . '/error_log.txt', $log_message, FILE_APPEND);
}

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

try {
    // Comprobar si los datos vienen como FormData o como JSON
    if (!empty($_POST)) {
        // Los datos vienen como FormData (desde un formulario)
        debug_log('Datos recibidos como FormData', ['post' => $_POST]);
        $data = $_POST;
    } else {
        // Intentar leer los datos como JSON
        $input = file_get_contents('php://input');
        debug_log('Datos recibidos como JSON', ['input' => $input]);
        $data = json_decode($input, true);
        
        // Verificar que los datos JSON sean válidos
        if ($data === null && !empty($input)) {
            debug_log('Error al decodificar JSON', ['error' => json_last_error_msg()]);
            throw new Exception('Error en el formato de datos: ' . json_last_error_msg());
        }
    }
    
    // Si no hay datos ni en POST ni en JSON, usar un array vacío
    if (empty($data)) {
        $data = [];
        debug_log('No se recibieron datos, usando valores predeterminados');
    }
    
    // Verificar que se haya proporcionado el ID de la cita
    if (!isset($data['appointment_id'])) {
        throw new Exception('ID de cita no proporcionado');
    }
    
    $appointment_id = $data['appointment_id'];
    
    // Asegurarse de que el ID de la cita sea un entero
    if (!is_numeric($appointment_id)) {
        throw new Exception('ID de cita no válido: debe ser un número');
    }
    
    $appointment_id = intval($appointment_id);
    debug_log('ID de cita recibido', ['appointment_id' => $appointment_id]);
    
    // Conectar a la base de datos
    $db_host = 'localhost';
    $db_name = 'salutia';
    $db_user = 'root';
    $db_pass = '';
    
    try {
        $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        debug_log('Conexión a la base de datos establecida');
    } catch (PDOException $e) {
        error_log_custom('Error de conexión a la base de datos', ['error' => $e->getMessage()]);
        throw new Exception('Error al conectar con la base de datos: ' . $e->getMessage());
    }
    
    // Iniciar transacción
    $conn->beginTransaction();
    
    try {
        // Primero, actualizar el estado de la cita a 'rechazada'
        $update_sql = "UPDATE appointments SET status = 'rejected' WHERE id = :appointment_id";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bindParam(':appointment_id', $appointment_id, PDO::PARAM_INT);
        $update_stmt->execute();
        
        debug_log('Cita marcada como rechazada', ['appointment_id' => $appointment_id]);
        
        // Luego, eliminar la cita
        $delete_sql = "DELETE FROM appointments WHERE id = :appointment_id";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bindParam(':appointment_id', $appointment_id, PDO::PARAM_INT);
        $delete_stmt->execute();
        
        debug_log('Cita eliminada correctamente', ['appointment_id' => $appointment_id]);
        
        // Confirmar la transacción
        $conn->commit();
        
        // Responder con éxito
        echo json_encode([
            'success' => true,
            'message' => 'Cita rechazada y eliminada correctamente',
            'appointment_id' => $appointment_id
        ]);
        
    } catch (PDOException $e) {
        // Revertir la transacción en caso de error
        $conn->rollBack();
        error_log_custom('Error al rechazar/eliminar la cita', ['error' => $e->getMessage(), 'appointment_id' => $appointment_id]);
        throw new Exception('Error al rechazar la cita: ' . $e->getMessage());
    }
    
} catch (Exception $e) {
    error_log_custom('Error en el proceso de rechazo de cita', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Asegurarse de que cualquier salida en el buffer se envíe
ob_end_flush();
?>
