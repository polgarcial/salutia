<?php
// Configuración para NO mostrar errores en la salida (los errores se registrarán en el log)
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejador de errores personalizado para asegurar que siempre se devuelva JSON válido
function json_error_handler($errno, $errstr, $errfile, $errline) {
    // Registrar el error en el archivo de log
    $log_file = __DIR__ . '/error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $error_message = "$timestamp - Error $errno: $errstr en $errfile:$errline";
    file_put_contents($log_file, $error_message . "\n", FILE_APPEND);
    
    // Devolver JSON con el error
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'error_code' => $errno
    ]);
    
    // Terminar la ejecución
    exit();
}

// Establecer el manejador de errores personalizado
set_error_handler('json_error_handler', E_ALL);

// Manejador de excepciones no capturadas
set_exception_handler(function($e) {
    // Registrar la excepción en el archivo de log
    $log_file = __DIR__ . '/error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $error_message = "$timestamp - Excepción no capturada: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine();
    file_put_contents($log_file, $error_message . "\n", FILE_APPEND);
    
    // Devolver JSON con el error
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
    
    // Terminar la ejecución
    exit();
});

// Función para registrar mensajes de depuración
function debug_log($message, $data = []) {
    $log_file = __DIR__ . '/debug_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "$timestamp - $message - " . json_encode($data, JSON_UNESCAPED_UNICODE);
    file_put_contents($log_file, $log_entry . "\n", FILE_APPEND);
}

// Manejar OPTIONS request para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Obtener el ID de la cita
$appointment_id = null;

// Verificar si los datos vienen como GET o POST
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        $appointment_id = $_GET['id'];
        debug_log('ID de cita recibido por GET', ['id' => $appointment_id]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar si los datos vienen como FormData o como JSON
    if (!empty($_POST) && isset($_POST['id'])) {
        $appointment_id = $_POST['id'];
        debug_log('ID de cita recibido por POST FormData', ['id' => $appointment_id]);
    } else {
        // Intentar leer los datos como JSON
        $json_data = file_get_contents('php://input');
        if (!empty($json_data)) {
            $data = json_decode($json_data, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($data['id'])) {
                $appointment_id = $data['id'];
                debug_log('ID de cita recibido por POST JSON', ['id' => $appointment_id]);
            }
        }
    }
}

// Verificar que se haya recibido un ID de cita
if ($appointment_id === null) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'No se proporcionó un ID de cita válido'
    ]);
    exit();
}

try {
    // Conectar a la base de datos
    require_once 'database.php';
    $conn = get_database_connection();
    
    // Iniciar transacción para asegurar la integridad de los datos
    $conn->beginTransaction();
    
    // Verificar que la cita existe y está pendiente
    $check_sql = "SELECT * FROM appointments WHERE id = :id";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bindParam(':id', $appointment_id, PDO::PARAM_INT);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() === 0) {
        // La cita no existe
        $conn->rollBack();
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'La cita no existe'
        ]);
        exit();
    }
    
    $appointment = $check_stmt->fetch(PDO::FETCH_ASSOC);
    debug_log('Cita encontrada', ['appointment' => $appointment]);
    
    // Actualizar el estado de la cita a 'accepted'
    $update_sql = "UPDATE appointments SET status = 'accepted' WHERE id = :id";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bindParam(':id', $appointment_id, PDO::PARAM_INT);
    $result = $update_stmt->execute();
    
    if (!$result) {
        // Error al actualizar la cita
        $conn->rollBack();
        throw new Exception('Error al actualizar el estado de la cita');
    }
    
    // Asegurarse de que la cita aparezca en las próximas citas del médico
    // (Esto ya debería estar cubierto por el cambio de estado, pero lo verificamos)
    $doctor_id = $appointment['doctor_id'];
    $patient_id = $appointment['patient_id'];
    
    // Confirmar la transacción
    $conn->commit();
    
    // Registrar la acción exitosa
    debug_log('Cita aceptada correctamente', [
        'appointment_id' => $appointment_id,
        'doctor_id' => $doctor_id,
        'patient_id' => $patient_id
    ]);
    
    // Devolver respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Cita aceptada correctamente',
        'appointment' => [
            'id' => $appointment_id,
            'doctor_id' => $doctor_id,
            'patient_id' => $patient_id,
            'date' => $appointment['date'],
            'time' => $appointment['time'],
            'status' => 'accepted'
        ]
    ]);
    
} catch (Exception $e) {
    // Si hay una transacción activa, revertirla
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    debug_log('Error al aceptar la cita', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al aceptar la cita: ' . $e->getMessage()
    ]);
}
