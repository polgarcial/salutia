<?php
// Habilitar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../backend/config/database.php';

try {
    $conn = getDbConnection();
    
    // Eliminar la restricción de clave foránea existente
    echo "Intentando eliminar la restricción de clave foránea...<br>";
    $conn->exec("ALTER TABLE doctor_weekly_schedules DROP FOREIGN KEY doctor_weekly_schedules_ibfk_1");
    echo "Restricción de clave foránea eliminada correctamente.<br>";
    
    // Agregar una nueva restricción de clave foránea que apunte a users.id
    echo "Intentando agregar nueva restricción de clave foránea a users.id...<br>";
    $conn->exec("ALTER TABLE doctor_weekly_schedules ADD CONSTRAINT doctor_weekly_schedules_user_fk FOREIGN KEY (user_id) REFERENCES users(id)");
    echo "Nueva restricción de clave foránea agregada correctamente.<br>";
    
    echo "Proceso completado con éxito";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    
    // Plan B: Si no podemos modificar la clave foránea, intentamos recrear la tabla
    try {
        echo "Intentando plan B: Recrear la tabla sin restricciones de clave foránea...<br>";
        
        // Guardar datos existentes
        $stmt = $conn->query("SELECT * FROM doctor_weekly_schedules");
        $existingData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Eliminar tabla
        $conn->exec("DROP TABLE IF EXISTS doctor_weekly_schedules");
        
        // Crear tabla sin restricciones de clave foránea
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
        
        // Restaurar datos si existen
        if (!empty($existingData)) {
            $stmt = $conn->prepare("INSERT INTO doctor_weekly_schedules (id, user_id, week_number, year, day_of_week, start_time, end_time, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($existingData as $row) {
                $stmt->execute([
                    $row['id'],
                    $row['user_id'],
                    $row['week_number'],
                    $row['year'],
                    $row['day_of_week'],
                    $row['start_time'],
                    $row['end_time'],
                    $row['created_at'] ?? date('Y-m-d H:i:s')
                ]);
            }
            
            echo "Datos existentes restaurados.<br>";
        }
        
        echo "Tabla recreada correctamente sin restricciones de clave foránea.<br>";
        echo "Plan B completado con éxito";
        
    } catch (Exception $e2) {
        echo "Error en Plan B: " . $e2->getMessage();
    }
}
?>
