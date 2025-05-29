<?php
// API para gestionar los pacientes de un médico
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Incluir la configuración de la base de datos
require_once "../config/database_class.php";

// Crear conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Obtener el método HTTP
$method = $_SERVER["REQUEST_METHOD"];

// Procesar según el método
switch ($method) {
    case "GET":
        getDoctorPatients($db);
        break;
    default:
        http_response_code(405);
        echo json_encode(["success" => false, "error" => "Método no permitido"]);
        break;
}

// Función para obtener los pacientes de un médico
function getDoctorPatients($db) {
    // Obtener parámetros
    $doctor_id = isset($_GET["doctor_id"]) ? $_GET["doctor_id"] : null;
    
    // Validar parámetros
    if (!$doctor_id) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Se requiere el ID del médico"]);
        return;
    }
    
    try {
        // Construir la consulta SQL para obtener pacientes que han tenido citas con este médico
        $sql = "SELECT DISTINCT p.* 
                FROM patients p
                JOIN appointments a ON p.id = a.patient_id
                WHERE a.doctor_id = :doctor_id
                ORDER BY p.name";
        
        // Preparar y ejecutar la consulta
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":doctor_id", $doctor_id);
        $stmt->execute();
        
        // Obtener resultados
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Devolver respuesta
        echo json_encode([
            "success" => true,
            "patients" => $patients
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "error" => "Error al obtener pacientes: " . $e->getMessage()
        ]);
    }
}
?>
