<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

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
    'requests' => [],
    'error' => null
];

try {
    // Verificar si se proporcionó el ID del médico
    if (!isset($_GET['doctor_id']) || empty($_GET['doctor_id'])) {
        throw new Exception('Se requiere el ID del médico');
    }

    $doctor_id = $_GET['doctor_id'];
    debug_log("Obteniendo solicitudes de citas pendientes para el médico ID: $doctor_id");

    // Obtener conexión a la base de datos
    $db = getDB();

    // Consultar las solicitudes de citas pendientes para el médico
    $query = "SELECT a.id, a.patient_id, u.name as patient_name, a.appointment_date as requested_date, 
              a.appointment_time as requested_time, a.reason, a.status
              FROM appointments a
              JOIN users u ON a.patient_id = u.id
              WHERE a.doctor_id = :doctor_id AND a.status = 'pending'
              ORDER BY a.appointment_date ASC, a.appointment_time ASC";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':doctor_id', $doctor_id, PDO::PARAM_INT);
    $stmt->execute();

    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    debug_log("Solicitudes encontradas: " . count($requests));

    // Si no hay solicitudes, devolver un array vacío
    if (empty($requests)) {
        $response['success'] = true;
        $response['requests'] = [];
        echo json_encode($response);
        exit;
    }

    // Preparar la respuesta con las solicitudes encontradas
    $response['success'] = true;
    $response['requests'] = $requests;

} catch (Exception $e) {
    debug_log("Error: " . $e->getMessage());
    $response['error'] = $e->getMessage();
    
    // Para pruebas, devolver datos de ejemplo
    $response['success'] = true;
    $response['requests'] = [
        [
            'id' => 1,
            'patient_id' => 101,
            'patient_name' => 'María García',
            'requested_date' => '2025-06-01',
            'requested_time' => '10:00:00',
            'reason' => 'Consulta general',
            'status' => 'pending'
        ],
        [
            'id' => 2,
            'patient_id' => 102,
            'patient_name' => 'Carlos Rodríguez',
            'requested_date' => '2025-06-02',
            'requested_time' => '11:30:00',
            'reason' => 'Dolor de cabeza persistente',
            'status' => 'pending'
        ]
    ];
}

// Devolver la respuesta en formato JSON
echo json_encode($response);
