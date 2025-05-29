<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir archivos necesarios
require_once 'database_class.php';
require_once 'debug_log.php';

// Habilitar CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar OPTIONS request para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Obtener parámetros
    $specialty = isset($_GET['specialty']) ? trim($_GET['specialty']) : '';
    $name = isset($_GET['name']) ? trim($_GET['name']) : '';
    
    // Crear instancia de la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Construir consulta SQL base
    $sql = "
        SELECT DISTINCT u.id, u.name, u.email
        FROM users u
        JOIN doctor_specialties ds ON u.id = ds.doctor_id
        WHERE u.role = 'doctor'
    ";
    
    // Añadir filtros si se especifican
    $params = [];
    
    if (!empty($specialty)) {
        $sql .= " AND ds.specialty = :specialty";
        $params[':specialty'] = $specialty;
    }
    
    if (!empty($name)) {
        $sql .= " AND u.name LIKE :name";
        $params[':name'] = "%$name%";
    }
    
    // Ordenar por nombre
    $sql .= " ORDER BY u.name ASC";
    
    // Preparar y ejecutar consulta
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    
    // Obtener resultados
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Para cada médico, obtener sus especialidades
    foreach ($doctors as &$doctor) {
        $stmt = $db->prepare("SELECT specialty FROM doctor_specialties WHERE doctor_id = :doctor_id ORDER BY specialty ASC");
        $stmt->bindParam(':doctor_id', $doctor['id'], PDO::PARAM_INT);
        $stmt->execute();
        
        $specialties = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $doctor['specialties'] = $specialties;
        
        // Obtener disponibilidad del médico
        $stmt = $db->prepare("
            SELECT day_of_week, start_time, end_time 
            FROM doctor_availability 
            WHERE doctor_id = :doctor_id 
            ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'), start_time
        ");
        $stmt->bindParam(':doctor_id', $doctor['id'], PDO::PARAM_INT);
        $stmt->execute();
        
        $availability = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $doctor['availability'] = $availability;
        
        // Traducir días de la semana para mostrar
        foreach ($doctor['availability'] as &$slot) {
            switch ($slot['day_of_week']) {
                case 'monday': $slot['day_name'] = 'Lunes'; break;
                case 'tuesday': $slot['day_name'] = 'Martes'; break;
                case 'wednesday': $slot['day_name'] = 'Miércoles'; break;
                case 'thursday': $slot['day_name'] = 'Jueves'; break;
                case 'friday': $slot['day_name'] = 'Viernes'; break;
                case 'saturday': $slot['day_name'] = 'Sábado'; break;
                case 'sunday': $slot['day_name'] = 'Domingo'; break;
            }
        }
        
        // Añadir una valoración aleatoria (en un sistema real, esto vendría de la base de datos)
        $doctor['rating'] = rand(35, 50) / 10; // Valoración entre 3.5 y 5.0
    }
    
    // Devolver respuesta
    echo json_encode([
        'success' => true,
        'doctors' => $doctors
    ]);
    
} catch (Exception $e) {
    debug_log('Error en search_doctors.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
