<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Cabeceras para JSON y CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar OPTIONS request para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir la función que hemos creado
require_once __DIR__ . '/functions/patient_appointments.php';

try {
    // Obtener parámetros de la solicitud
    $patientId = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';
    
    // Validar parámetros
    if ($patientId <= 0) {
        throw new InvalidArgumentException('Se requiere un ID de paciente válido');
    }
    
    // Validar el estado
    $validStatus = ['all', 'upcoming', 'past'];
    if (!in_array($status, $validStatus)) {
        $status = 'all'; // Valor por defecto si no es válido
    }
    
    // Obtener las citas del paciente
    $appointments = getPatientAppointments($patientId, $status);
    
    // Devolver respuesta
    echo json_encode([
        'success' => true,
        'patient_id' => $patientId,
        'status' => $status,
        'count' => count($appointments),
        'appointments' => $appointments
    ]);
    
} catch (Exception $e) {
    // Registrar el error
    error_log('Error en get_patient_appointments_example.php: ' . $e->getMessage());
    
    // Devolver respuesta de error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
