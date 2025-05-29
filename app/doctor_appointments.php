<?php
// API para obtener las citas de un médico
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
        getDoctorAppointments($db);
        break;
    case "PUT":
        updateAppointmentStatus($db);
        break;
    default:
        http_response_code(405);
        echo json_encode(["success" => false, "error" => "Método no permitido"]);
        break;
}

// Función para obtener las citas de un médico
function getDoctorAppointments($db) {
    // Obtener parámetros
    $doctor_id = isset($_GET["doctor_id"]) ? $_GET["doctor_id"] : null;
    $status = isset($_GET["status"]) ? $_GET["status"] : null;
    $date = isset($_GET["date"]) ? $_GET["date"] : null;
    
    // Validar parámetros
    if (!$doctor_id) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Se requiere el ID del médico"]);
        return;
    }
    
    try {
        // Construir la consulta SQL
        $sql = "SELECT a.*, p.name AS patient_name, p.phone AS patient_phone, p.email AS patient_email 
                FROM appointments a
                JOIN patients p ON a.patient_id = p.id
                WHERE a.doctor_id = :doctor_id";
        
        $params = [":doctor_id" => $doctor_id];
        
        // Añadir filtro de estado si se proporciona
        if ($status) {
            $sql .= " AND a.status = :status";
            $params[":status"] = $status;
        }
        
        // Añadir filtro de fecha si se proporciona
        if ($date) {
            $sql .= " AND a.appointment_date = :date";
            $params[":date"] = $date;
        }
        
        // Ordenar por fecha y hora
        $sql .= " ORDER BY a.appointment_date, a.appointment_time";
        
        // Preparar y ejecutar la consulta
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        // Obtener resultados
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Devolver respuesta
        echo json_encode([
            "success" => true,
            "appointments" => $appointments
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "error" => "Error al obtener citas: " . $e->getMessage()
        ]);
    }
}

// Función para actualizar el estado de una cita
function updateAppointmentStatus($db) {
    // Obtener datos del cuerpo de la solicitud
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Validar datos
    if (!isset($data["appointment_id"]) || !isset($data["status"])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Se requieren appointment_id y status"]);
        return;
    }
    
    $appointment_id = $data["appointment_id"];
    $status = $data["status"];
    $notes = isset($data["notes"]) ? $data["notes"] : null;
    
    try {
        // Actualizar el estado de la cita
        $sql = "UPDATE appointments 
                SET status = :status";
        
        // Añadir notas si se proporcionan
        if ($notes !== null) {
            $sql .= ", notes = :notes";
        }
        
        $sql .= " WHERE id = :appointment_id";
        
        // Preparar y ejecutar la consulta
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":status", $status);
        $stmt->bindValue(":appointment_id", $appointment_id);
        
        if ($notes !== null) {
            $stmt->bindValue(":notes", $notes);
        }
        
        $stmt->execute();
        
        // Verificar si se actualizó alguna fila
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(["success" => false, "error" => "No se encontró la cita con ID $appointment_id"]);
            return;
        }
        
        // Devolver respuesta
        echo json_encode([
            "success" => true,
            "message" => "Estado de la cita actualizado correctamente"
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "error" => "Error al actualizar el estado de la cita: " . $e->getMessage()
        ]);
    }
}
?>
