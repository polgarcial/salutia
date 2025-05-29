<?php
/**
 * API Endpoint: Obtener pacientes disponibles
 * 
 * Este endpoint devuelve una lista de pacientes que están en la base de datos
 * pero que aún no están asociados al médico especificado.
 */

// Permitir solicitudes desde cualquier origen (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Para solicitudes OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificar que sea una solicitud GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

// Incluir configuración de base de datos
require_once '../config/database.php';

// Obtener conexión a la base de datos
$db = getDbConnection();

// Obtener el ID del médico de la solicitud
$doctor_id = isset($_GET['doctor_id']) ? $_GET['doctor_id'] : null;

if (!$doctor_id) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Se requiere ID del médico"]);
    exit;
}

try {
    // Consulta para obtener pacientes que no están asociados al médico
    $sql = "SELECT u.id, u.name, u.email, u.phone, u.date_of_birth as dob
            FROM users u
            WHERE u.role = 'patient'
            AND u.id NOT IN (
                SELECT patient_id 
                FROM doctor_patients 
                WHERE doctor_id = ?
            )
            ORDER BY u.name";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$doctor_id]);
    
    $patients = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $patients[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'phone' => $row['phone'] ?? '',
            'dob' => $row['dob'] ?? ''
        ];
    }
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "patients" => $patients
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error al obtener pacientes disponibles: " . $e->getMessage()
    ]);
}
?>
