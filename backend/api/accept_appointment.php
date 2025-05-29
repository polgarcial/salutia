<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);


require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

try {

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['appointment_id']) || !is_numeric($data['appointment_id'])) {
        throw new Exception('ID de cita no válido');
    }
    
    $appointmentId = intval($data['appointment_id']);
    $doctorId = isset($data['doctor_id']) ? intval($data['doctor_id']) : 1;
    
    $db = getDbConnection();
    
    $checkSql = "SELECT * FROM appointments WHERE id = :appointment_id AND doctor_id = :doctor_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(':appointment_id', $appointmentId, PDO::PARAM_INT);
    $checkStmt->bindParam(':doctor_id', $doctorId, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        throw new Exception('La cita no existe o no pertenece a este médico');
    }
    
    $appointment = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    $updateSql = "UPDATE appointments SET status = 'accepted' WHERE id = :appointment_id";
    $updateStmt = $db->prepare($updateSql);
    $updateStmt->bindParam(':appointment_id', $appointmentId, PDO::PARAM_INT);
    $updateStmt->execute();
    
    if ($updateStmt->rowCount() === 0) {
        throw new Exception('No se pudo actualizar el estado de la cita');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Cita aceptada correctamente',
        'appointment_id' => $appointmentId
    ]);
    
} catch (Exception $e) {
    error_log('Error en accept_appointment.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
