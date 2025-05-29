<?php
/**
 * API Endpoint: Obtener datos de un paciente
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

// Obtener el ID del paciente de la solicitud
$patient_id = isset($_GET['patient_id']) ? $_GET['patient_id'] : null;

// Respuesta fija para pruebas
$response = [
    "success" => true,
    "patient" => [
        'id' => $patient_id,
        'name' => 'Paciente de Prueba',
        'email' => 'paciente@ejemplo.com',
        'phone' => '123456789',
        'dob' => '1990-01-01'
    ]
];

http_response_code(200);
echo json_encode($response);
?>
