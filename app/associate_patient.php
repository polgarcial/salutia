<?php
/**
 * API Endpoint: Asociar paciente existente a un médico
 * 
 * Este endpoint permite asociar un paciente existente a un médico.
 */

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

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

// Incluir configuración de base de datos
require_once '../config/database.php';

// Obtener conexión a la base de datos
$db = getDbConnection();

// Obtener datos del cuerpo de la solicitud
$data = json_decode(file_get_contents("php://input"), true);

// Verificar datos requeridos
if (!isset($data['doctor_id']) || !isset($data['patient_id'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Se requieren IDs del médico y del paciente"]);
    exit;
}

$doctor_id = $data['doctor_id'];
$patient_id = $data['patient_id'];

try {
    // Verificar si el paciente existe y es un paciente
    $checkPatientSql = "SELECT id FROM users WHERE id = ? AND role = 'patient'";
    $checkPatientStmt = $db->prepare($checkPatientSql);
    $checkPatientStmt->execute([$patient_id]);
    
    if ($checkPatientStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Paciente no encontrado o no es un paciente"]);
        exit;
    }
    
    // Verificar si el médico existe y es un médico
    $checkDoctorSql = "SELECT id FROM users WHERE id = ? AND role = 'doctor'";
    $checkDoctorStmt = $db->prepare($checkDoctorSql);
    $checkDoctorStmt->execute([$doctor_id]);
    
    if ($checkDoctorStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Médico no encontrado o no es un médico"]);
        exit;
    }
    
    // Verificar si ya existe la asociación
    $checkAssociationSql = "SELECT id FROM doctor_patients WHERE doctor_id = ? AND patient_id = ?";
    $checkAssociationStmt = $db->prepare($checkAssociationSql);
    $checkAssociationStmt->execute([$doctor_id, $patient_id]);
    
    if ($checkAssociationStmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "El paciente ya está asociado a este médico"]);
        exit;
    }
    
    // Crear la asociación
    $insertSql = "INSERT INTO doctor_patients (doctor_id, patient_id, created_at) VALUES (?, ?, NOW())";
    $insertStmt = $db->prepare($insertSql);
    $insertStmt->execute([$doctor_id, $patient_id]);
    
    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message" => "Paciente asociado correctamente al médico"
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error al asociar paciente: " . $e->getMessage()
    ]);
}
?>
