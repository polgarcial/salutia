<?php
/**
 * API Endpoint: Solicitar cita
 */

// Habilitar todos los errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Permitir solicitudes desde cualquier origen (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Para solicitudes OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir configuración de base de datos
require_once '../config/database.php';

// Registrar la solicitud para depuración
error_log("Solicitud recibida en request_appointment.php");

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

try {
    // Obtener conexión a la base de datos
    $db = getDbConnection();
    
    // Obtener los datos enviados en la solicitud
    $data = json_decode(file_get_contents("php://input"), true);
    error_log("Datos recibidos: " . json_encode($data));
    
    // Validar datos requeridos
    if (!isset($data['doctor_id']) || !isset($data['patient_id']) || 
        !isset($data['appointment_date']) || !isset($data['start_time']) || !isset($data['reason'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Faltan datos requeridos"]);
        exit;
    }
    
    // Extraer datos
    $doctor_id = $data['doctor_id'];
    $patient_id = $data['patient_id'];
    $date = $data['appointment_date'];
    $time = $data['start_time'];
    $reason = $data['reason'];
    $status = 'pending'; // Estado inicial: pendiente
    
    // Validar formato de fecha y hora
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Formato de fecha inválido. Use YYYY-MM-DD"]);
        exit;
    }
    
    // Calcular hora de fin (1 hora después)
    $start_time = $time . ':00';
    $time_parts = explode(':', $time);
    $end_hour = (int)$time_parts[0] + 1;
    $end_time = $end_hour . ':' . ($time_parts[1] ?? '00') . ':00';
    
    // Verificar que el médico y el paciente existan
    $checkUsersSql = "SELECT u1.id as doctor_exists, u2.id as patient_exists 
                     FROM users u1, users u2 
                     WHERE u1.id = ? AND u1.role = 'doctor' 
                     AND u2.id = ? AND u2.role = 'patient'";
    $checkUsersStmt = $db->prepare($checkUsersSql);
    $checkUsersStmt->execute([$doctor_id, $patient_id]);
    
    if ($checkUsersStmt->rowCount() === 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Médico o paciente no encontrado"]);
        exit;
    }
    
    // Verificar si ya existe una cita en ese horario
    $checkAppointmentSql = "SELECT id FROM appointments 
                          WHERE doctor_id = ? 
                          AND appointment_date = ? 
                          AND start_time = ?";
    $checkAppointmentStmt = $db->prepare($checkAppointmentSql);
    $checkAppointmentStmt->execute([$doctor_id, $date, $start_time]);
    
    if ($checkAppointmentStmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Ya existe una cita programada en ese horario"]);
        exit;
    }
    
    // Insertar la cita en la base de datos
    $insertSql = "INSERT INTO appointments (doctor_id, patient_id, appointment_date, start_time, end_time, reason, status, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $db->prepare($insertSql);
    $result = $stmt->execute([$doctor_id, $patient_id, $date, $start_time, $end_time, $reason, 'pending']);
    
    if ($result) {
        $appointment_id = $db->lastInsertId();
        
        // Registrar para depuración
        error_log("Cita creada con éxito - ID: $appointment_id, Paciente: $patient_id, Médico: $doctor_id, Fecha: $date, Hora: $start_time");
        
        // Verificar que la cita se haya guardado correctamente
        $checkSql = "SELECT id, appointment_date, start_time FROM appointments WHERE id = ?";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([$appointment_id]);
        
        if ($checkStmt->rowCount() > 0) {
            $appointmentData = $checkStmt->fetch(PDO::FETCH_ASSOC);
            error_log("Verificación de cita - ID: {$appointmentData['id']}, Fecha: {$appointmentData['appointment_date']}, Hora: {$appointmentData['start_time']}");
        } else {
            error_log("ADVERTENCIA: No se pudo verificar la cita recién creada con ID: $appointment_id");
        }
        
        // Enviar respuesta exitosa
        echo json_encode([
            'success' => true,
            'message' => 'Cita solicitada correctamente',
            'appointment_id' => $appointment_id,
            'appointment_date' => $date,
            'start_time' => $start_time
        ]);
    } else {
        // Error al insertar
        error_log("Error al guardar la cita - Paciente: $patient_id, Médico: $doctor_id, Fecha: $date");
        echo json_encode([
            'success' => false,
            'message' => 'Error al guardar la cita'
        ]);
    }
} catch (Exception $e) {
    error_log("Error en request_appointment.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error al procesar la solicitud: " . $e->getMessage()]);
}
?>
