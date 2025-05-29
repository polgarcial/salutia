<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir archivos necesarios
require_once 'database_class.php';

// Habilitar CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Crear instancia de la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar tabla de usuarios (médicos)
    $stmt = $db->query("SELECT COUNT(*) as doctor_count FROM users WHERE role = 'doctor'");
    $doctorCount = $stmt->fetch(PDO::FETCH_ASSOC)['doctor_count'];
    
    // Verificar tabla de especialidades
    $stmt = $db->query("SELECT COUNT(*) as specialty_count FROM doctor_specialties");
    $specialtyCount = $stmt->fetch(PDO::FETCH_ASSOC)['specialty_count'];
    
    // Verificar tabla de disponibilidad
    $stmt = $db->query("SELECT COUNT(*) as availability_count FROM doctor_availability");
    $availabilityCount = $stmt->fetch(PDO::FETCH_ASSOC)['availability_count'];
    
    // Obtener lista de especialidades
    $stmt = $db->query("SELECT DISTINCT specialty FROM doctor_specialties ORDER BY specialty");
    $specialties = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Obtener lista de médicos con sus especialidades
    $stmt = $db->query("
        SELECT u.id, u.name, u.email, ds.specialty
        FROM users u
        JOIN doctor_specialties ds ON u.id = ds.doctor_id
        WHERE u.role = 'doctor'
        ORDER BY ds.specialty, u.name
    ");
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Verificar estructura de la tabla doctor_availability
    $stmt = $db->query("PRAGMA table_info(doctor_availability)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'name');
    
    // Devolver resultados
    echo json_encode([
        'success' => true,
        'counts' => [
            'doctors' => $doctorCount,
            'specialties' => $specialtyCount,
            'availability' => $availabilityCount
        ],
        'specialties' => $specialties,
        'doctors' => $doctors,
        'doctor_availability_columns' => $columnNames
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
