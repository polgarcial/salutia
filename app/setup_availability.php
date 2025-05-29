<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir archivos necesarios
require_once 'database_class.php';
require_once 'debug_log.php';

echo "Configurando tabla de disponibilidad de médicos...\n";

try {
    // Crear instancia de la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar si la tabla doctor_availability existe
    $tableExists = false;
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'doctor_availability'");
        $tableExists = $stmt->rowCount() > 0;
    } catch (Exception $e) {
        echo "Error al verificar la tabla: " . $e->getMessage() . "\n";
    }
    
    // Crear tabla si no existe
    if (!$tableExists) {
        echo "Creando tabla doctor_availability...\n";
        
        $sql = "CREATE TABLE doctor_availability (
            id INT AUTO_INCREMENT PRIMARY KEY,
            doctor_id INT NOT NULL,
            day_of_week VARCHAR(10) NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        
        $db->exec($sql);
        echo "Tabla doctor_availability creada correctamente.\n";
    } else {
        echo "La tabla doctor_availability ya existe.\n";
    }
    
    echo "Configuración de disponibilidad completada con éxito.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    debug_log('Error en setup_availability.php: ' . $e->getMessage());
}
?>
