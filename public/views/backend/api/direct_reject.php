<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Habilitar CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar OPTIONS request para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Obtener el ID de la cita (aceptamos tanto GET como POST)
$appointmentId = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $appointmentId = isset($data['appointment_id']) ? intval($data['appointment_id']) : 0;
} else {
    $appointmentId = isset($_GET['id']) ? intval($_GET['id']) : 0;
}

// Verificar que tengamos un ID válido
if ($appointmentId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de cita no válido']);
    exit();
}

try {
    // Conectar a la base de datos directamente
    $host = 'localhost';
    $dbname = 'salutia';
    $username = 'root';
    $password = '';
    
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Actualizar el estado de la cita a 'rejected'
    $sql = "UPDATE appointments SET status = 'rejected' WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id', $appointmentId, PDO::PARAM_INT);
    $stmt->execute();
    
    // Devolver respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Cita rechazada correctamente',
        'appointment_id' => $appointmentId
    ]);
    
} catch (PDOException $e) {
    // Devolver error
    echo json_encode([
        'success' => false,
        'message' => 'Error en la base de datos: ' . $e->getMessage(),
        'appointment_id' => $appointmentId
    ]);
}
?>
