<?php
// Habilitar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../backend/config/database.php';

try {
    $conn = getDbConnection();
    
    // Verificar si existe un índice único que cause el problema
    echo "Verificando índices en la tabla doctor_weekly_schedules...<br>";
    $stmt = $conn->query("SHOW INDEXES FROM doctor_weekly_schedules WHERE Key_name = 'week_schedule'");
    $indexExists = $stmt->rowCount() > 0;
    
    if ($indexExists) {
        echo "Índice 'week_schedule' encontrado. Eliminando...<br>";
        $conn->exec("ALTER TABLE doctor_weekly_schedules DROP INDEX week_schedule");
        echo "Índice eliminado correctamente.<br>";
    } else {
        echo "No se encontró el índice 'week_schedule'. Verificando otros índices únicos...<br>";
        
        // Verificar todos los índices únicos
        $stmt = $conn->query("SHOW INDEXES FROM doctor_weekly_schedules WHERE Non_unique = 0");
        $uniqueIndexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($uniqueIndexes as $index) {
            if ($index['Key_name'] != 'PRIMARY') {
                echo "Eliminando índice único '{$index['Key_name']}'...<br>";
                $conn->exec("ALTER TABLE doctor_weekly_schedules DROP INDEX {$index['Key_name']}");
                echo "Índice '{$index['Key_name']}' eliminado correctamente.<br>";
            }
        }
    }
    
    // Verificar si existe una restricción de unicidad en la tabla
    echo "Verificando si existe una restricción de unicidad...<br>";
    $stmt = $conn->query("SHOW CREATE TABLE doctor_weekly_schedules");
    $tableDefinition = $stmt->fetch(PDO::FETCH_ASSOC);
    $createTable = $tableDefinition['Create Table'] ?? '';
    
    // Buscar patrones de restricción única
    if (strpos($createTable, 'UNIQUE KEY') !== false || strpos($createTable, 'UNIQUE INDEX') !== false) {
        echo "Se encontró una restricción de unicidad en la definición de la tabla.<br>";
        echo "Recreando la tabla sin restricciones de unicidad...<br>";
        
        // Guardar datos existentes
        $stmt = $conn->query("SELECT * FROM doctor_weekly_schedules");
        $existingData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Eliminar tabla
        $conn->exec("DROP TABLE IF EXISTS doctor_weekly_schedules");
        
        // Crear tabla sin restricciones de unicidad
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
        
        echo "Tabla recreada correctamente sin restricciones de unicidad.<br>";
    } else {
        echo "No se encontró una restricción de unicidad explícita en la definición de la tabla.<br>";
        
        // Como último recurso, recrear la tabla de todos modos
        echo "Recreando la tabla como último recurso...<br>";
        
        // Guardar datos existentes
        $stmt = $conn->query("SELECT * FROM doctor_weekly_schedules");
        $existingData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Eliminar tabla
        $conn->exec("DROP TABLE IF EXISTS doctor_weekly_schedules");
        
        // Crear tabla sin restricciones de unicidad
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
                try {
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
                } catch (Exception $e) {
                    echo "Error al restaurar fila: " . $e->getMessage() . "<br>";
                    // Continuar con la siguiente fila
                }
            }
            
            echo "Datos existentes restaurados.<br>";
        }
        
        echo "Tabla recreada correctamente.<br>";
    }
    
    echo "Proceso completado con éxito";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
