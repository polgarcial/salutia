<?php
// Habilitar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../backend/config/database.php';

try {
    $conn = getDbConnection();
    
    // Verificar si la tabla existe
    $stmt = $conn->query("SHOW TABLES LIKE 'doctor_weekly_schedules'");
    $tableExists = $stmt->rowCount() > 0;
    
    echo "Tabla doctor_weekly_schedules existe: " . ($tableExists ? 'Sí' : 'No') . "<br>";
    
    if ($tableExists) {
        // Verificar si la tabla tiene la columna doctor_id
        $stmt = $conn->query("SHOW COLUMNS FROM doctor_weekly_schedules LIKE 'doctor_id'");
        $doctorIdExists = $stmt->rowCount() > 0;
        
        echo "Columna doctor_id existe: " . ($doctorIdExists ? 'Sí' : 'No') . "<br>";
        
        if ($doctorIdExists) {
            // Modificar la tabla para cambiar doctor_id a user_id
            $conn->exec("ALTER TABLE doctor_weekly_schedules CHANGE doctor_id user_id INT NOT NULL");
            echo "Columna doctor_id cambiada a user_id correctamente<br>";
        }
    } else {
        // Crear la tabla con user_id
        $conn->exec("CREATE TABLE doctor_weekly_schedules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            week_number INT NOT NULL,
            year INT NOT NULL,
            day_of_week INT NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id, week_number, year)
        )");
        echo "Tabla doctor_weekly_schedules creada correctamente<br>";
    }
    
    echo "Proceso completado con éxito";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
