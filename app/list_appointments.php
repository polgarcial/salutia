<?php
// Incluir configuración de base de datos
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Obtener conexión a la base de datos
    $db = getDbConnection();
    
    // Obtener todas las citas
    $sql = "SELECT * FROM appointments ORDER BY appointment_date DESC LIMIT 10";
    $stmt = $db->query($sql);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'appointments' => $appointments
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
