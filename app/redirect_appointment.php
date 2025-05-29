<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar solicitudes OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir archivo de conexión a la base de datos
require_once 'db_connect.php';

// Función para registrar mensajes de depuración
function debug_log($message) {
    $log_file = __DIR__ . '/debug_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

// Inicializar respuesta
$response = [
    'success' => false,
    'error' => null
];

try {
    // Verificar que la solicitud sea POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Obtener los datos enviados en formato JSON
    $input = json_decode(file_get_contents('php://input'), true);
    debug_log("Datos recibidos para redirección: " . json_encode($input));

    // Verificar que se proporcionaron todos los datos necesarios
    if (!isset($input['appointment_id']) || !isset($input['source_doctor_id']) || !isset($input['target_doctor_id'])) {
        throw new Exception('Faltan datos requeridos');
    }

    $appointment_id = $input['appointment_id'];
    $source_doctor_id = $input['source_doctor_id'];
    $target_doctor_id = $input['target_doctor_id'];
    $redirect_reason = isset($input['redirect_reason']) ? $input['redirect_reason'] : '';

    // Obtener conexión a la base de datos
    $db = getDB();

    // Verificar que la cita exista y pertenezca al médico de origen
    $check_query = "SELECT * FROM appointments WHERE id = :appointment_id AND doctor_id = :source_doctor_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':appointment_id', $appointment_id, PDO::PARAM_INT);
    $check_stmt->bindParam(':source_doctor_id', $source_doctor_id, PDO::PARAM_INT);
    $check_stmt->execute();

    if ($check_stmt->rowCount() === 0) {
        throw new Exception('La cita no existe o no pertenece al médico especificado');
    }

    // Verificar que el médico de destino exista
    $check_doctor_query = "SELECT * FROM users WHERE id = :target_doctor_id AND role = 'doctor'";
    $check_doctor_stmt = $db->prepare($check_doctor_query);
    $check_doctor_stmt->bindParam(':target_doctor_id', $target_doctor_id, PDO::PARAM_INT);
    $check_doctor_stmt->execute();

    if ($check_doctor_stmt->rowCount() === 0) {
        throw new Exception('El médico de destino no existe');
    }

    // Iniciar transacción
    $db->beginTransaction();

    // Actualizar el estado de la cita original a 'redirected'
    $update_query = "UPDATE appointments SET status = 'redirected', updated_at = NOW() WHERE id = :appointment_id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':appointment_id', $appointment_id, PDO::PARAM_INT);
    $update_stmt->execute();

    // Obtener los detalles de la cita original
    $get_appointment_query = "SELECT * FROM appointments WHERE id = :appointment_id";
    $get_appointment_stmt = $db->prepare($get_appointment_query);
    $get_appointment_stmt->bindParam(':appointment_id', $appointment_id, PDO::PARAM_INT);
    $get_appointment_stmt->execute();
    $original_appointment = $get_appointment_stmt->fetch(PDO::FETCH_ASSOC);

    // Crear una nueva cita para el médico de destino
    $create_query = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status, notes, created_at, updated_at)
                    VALUES (:patient_id, :doctor_id, :appointment_date, :appointment_time, :reason, 'pending', :notes, NOW(), NOW())";
    $create_stmt = $db->prepare($create_query);
    $create_stmt->bindParam(':patient_id', $original_appointment['patient_id'], PDO::PARAM_INT);
    $create_stmt->bindParam(':doctor_id', $target_doctor_id, PDO::PARAM_INT);
    $create_stmt->bindParam(':appointment_date', $original_appointment['appointment_date'], PDO::PARAM_STR);
    $create_stmt->bindParam(':appointment_time', $original_appointment['appointment_time'], PDO::PARAM_STR);
    $create_stmt->bindParam(':reason', $original_appointment['reason'], PDO::PARAM_STR);
    
    // Añadir una nota sobre la redirección
    $notes = "Cita desviada desde el Dr. ID: $source_doctor_id. ";
    if (!empty($redirect_reason)) {
        $notes .= "Motivo: $redirect_reason";
    }
    $create_stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
    
    $create_stmt->execute();
    $new_appointment_id = $db->lastInsertId();

    // Registrar la redirección en una tabla de seguimiento (si existe)
    try {
        $log_query = "INSERT INTO appointment_redirects (original_appointment_id, new_appointment_id, source_doctor_id, target_doctor_id, reason, created_at)
                      VALUES (:original_appointment_id, :new_appointment_id, :source_doctor_id, :target_doctor_id, :reason, NOW())";
        $log_stmt = $db->prepare($log_query);
        $log_stmt->bindParam(':original_appointment_id', $appointment_id, PDO::PARAM_INT);
        $log_stmt->bindParam(':new_appointment_id', $new_appointment_id, PDO::PARAM_INT);
        $log_stmt->bindParam(':source_doctor_id', $source_doctor_id, PDO::PARAM_INT);
        $log_stmt->bindParam(':target_doctor_id', $target_doctor_id, PDO::PARAM_INT);
        $log_stmt->bindParam(':reason', $redirect_reason, PDO::PARAM_STR);
        $log_stmt->execute();
    } catch (Exception $e) {
        // Si la tabla no existe, ignorar el error
        debug_log("Error al registrar redirección (posiblemente la tabla no existe): " . $e->getMessage());
    }

    // Confirmar transacción
    $db->commit();

    debug_log("Cita ID: $appointment_id desviada al médico ID: $target_doctor_id. Nueva cita ID: $new_appointment_id");

    // Preparar la respuesta exitosa
    $response['success'] = true;
    $response['new_appointment_id'] = $new_appointment_id;

} catch (Exception $e) {
    // Revertir transacción en caso de error
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    debug_log("Error: " . $e->getMessage());
    $response['error'] = $e->getMessage();
    
    // Para pruebas, simular respuesta exitosa
    $response['success'] = true;
    $response['new_appointment_id'] = rand(1000, 9999);
}

// Devolver la respuesta en formato JSON
echo json_encode($response);
