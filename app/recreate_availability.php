<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir archivos necesarios
require_once 'database_class.php';
require_once 'debug_log.php';

echo "Recreando tabla de disponibilidad de médicos...\n";

try {
    // Crear instancia de la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Eliminar tabla si existe
    $db->exec("DROP TABLE IF EXISTS doctor_availability");
    echo "Tabla doctor_availability eliminada.\n";
    
    // Crear tabla con la estructura correcta
    $sql = "CREATE TABLE doctor_availability (
        id INT AUTO_INCREMENT PRIMARY KEY,
        doctor_id INT NOT NULL,
        day_of_week VARCHAR(10) NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $db->exec($sql);
    echo "Tabla doctor_availability creada correctamente con la estructura adecuada.\n";
    
    echo "Recreación de tabla completada con éxito.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    debug_log('Error en recreate_availability.php: ' . $e->getMessage());
}
?>
