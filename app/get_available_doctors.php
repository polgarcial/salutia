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
    'doctors' => [],
    'error' => null
];

try {
    // Obtener el ID del médico actual para excluirlo de los resultados
    $current_doctor_id = isset($_GET['exclude_doctor_id']) ? $_GET['exclude_doctor_id'] : null;
    
    // Obtener especialidad para filtrar (opcional)
    $specialty = isset($_GET['specialty']) ? $_GET['specialty'] : null;
    
    debug_log("Obteniendo médicos disponibles. Excluir médico ID: $current_doctor_id, Especialidad: $specialty");

    // Obtener conexión a la base de datos
    $db = getDB();

    // Construir la consulta base
    $query = "SELECT u.id, u.name, ds.specialty 
              FROM users u 
              LEFT JOIN doctor_specialties ds ON u.id = ds.doctor_id 
              WHERE u.role = 'doctor'";
    
    $params = [];
    
    // Excluir al médico actual si se proporcionó su ID
    if ($current_doctor_id) {
        $query .= " AND u.id != :current_doctor_id";
        $params[':current_doctor_id'] = $current_doctor_id;
    }
    
    // Filtrar por especialidad si se proporcionó
    if ($specialty) {
        $query .= " AND ds.specialty = :specialty";
        $params[':specialty'] = $specialty;
    }
    
    // Ordenar por nombre
    $query .= " ORDER BY u.name ASC";
    
    $stmt = $db->prepare($query);
    
    // Vincular parámetros
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    
    $stmt->execute();
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    debug_log("Médicos encontrados: " . count($doctors));

    // Preparar la respuesta
    $response['success'] = true;
    $response['doctors'] = $doctors;

} catch (Exception $e) {
    debug_log("Error: " . $e->getMessage());
    $response['error'] = $e->getMessage();
    
    // Para pruebas, devolver datos de ejemplo
    $response['success'] = true;
    $response['doctors'] = [
        ['id' => 3, 'name' => 'Dr. Ana Martínez', 'specialty' => 'Cardiología'],
        ['id' => 4, 'name' => 'Dr. Luis Sánchez', 'specialty' => 'Pediatría'],
        ['id' => 5, 'name' => 'Dra. Carmen Ruiz', 'specialty' => 'Dermatología']
    ];
}

// Devolver la respuesta en formato JSON
echo json_encode($response);
