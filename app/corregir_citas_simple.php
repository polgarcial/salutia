<?php
// Script simple para corregir la estructura de la tabla de citas
header("Content-Type: text/html; charset=UTF-8");

echo "<html><head><title>Corrección de Citas - Salutia</title>
<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
    h1, h2 { color: #0066cc; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .button {
        display: inline-block;
        background-color: #0066cc;
        color: white;
        padding: 10px 20px;
        text-decoration: none;
        border-radius: 5px;
        margin: 10px 0;
    }
</style>
</head><body>
<h1>Corrección de la Tabla de Citas Médicas</h1>";

try {
    // Incluir la configuración de la base de datos
    require_once __DIR__ . '/../backend/config/database_class.php';
    
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p class='success'>✓ Conexión a la base de datos establecida correctamente.</p>";
    
    // Verificar si existe la tabla appointments
    $stmt = $db->query("SHOW TABLES LIKE 'appointments'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "<p class='warning'>⚠ La tabla appointments no existe. Creando tabla...</p>";
        
        // Crear la tabla appointments
        $sql = "CREATE TABLE appointments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            doctor_id INT NOT NULL,
            appointment_date DATE NOT NULL,
            appointment_time VARCHAR(10) NOT NULL,
            notes TEXT,
            status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $db->exec($sql);
        echo "<p class='success'>✓ Tabla appointments creada correctamente.</p>";
    } else {
        echo "<p class='success'>✓ La tabla appointments existe.</p>";
        
        // Verificar la estructura de la tabla
        $stmt = $db->query("DESCRIBE appointments");
        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[$row['Field']] = $row;
        }
        
        echo "<h2>Estructura actual de la tabla appointments</h2>";
        echo "<table>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Predeterminado</th><th>Extra</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . $column['Default'] . "</td>";
            echo "<td>" . $column['Extra'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Verificar si existen las columnas date y time
        $needsUpdate = false;
        
        if (isset($columns['date']) && !isset($columns['appointment_date'])) {
            echo "<p class='warning'>⚠ La columna 'date' debe renombrarse a 'appointment_date'</p>";
            $db->exec("ALTER TABLE appointments CHANGE COLUMN `date` `appointment_date` DATE NOT NULL");
            echo "<p class='success'>✓ Columna 'date' renombrada a 'appointment_date'</p>";
            $needsUpdate = true;
        }
        
        if (isset($columns['time']) && !isset($columns['appointment_time'])) {
            echo "<p class='warning'>⚠ La columna 'time' debe renombrarse a 'appointment_time'</p>";
            $db->exec("ALTER TABLE appointments CHANGE COLUMN `time` `appointment_time` VARCHAR(10) NOT NULL");
            echo "<p class='success'>✓ Columna 'time' renombrada a 'appointment_time'</p>";
            $needsUpdate = true;
        }
        
        // Verificar si existe la columna patient_id (en lugar de user_id)
        if (isset($columns['user_id']) && !isset($columns['patient_id'])) {
            echo "<p class='warning'>⚠ La columna 'user_id' debe renombrarse a 'patient_id'</p>";
            $db->exec("ALTER TABLE appointments CHANGE COLUMN `user_id` `patient_id` INT NOT NULL");
            echo "<p class='success'>✓ Columna 'user_id' renombrada a 'patient_id'</p>";
            $needsUpdate = true;
        }
        
        // Añadir columnas que faltan
        $requiredColumns = [
            'patient_id' => 'INT NOT NULL',
            'doctor_id' => 'INT NOT NULL',
            'appointment_date' => 'DATE NOT NULL',
            'appointment_time' => 'VARCHAR(10) NOT NULL',
            'notes' => 'TEXT',
            'status' => "ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending'",
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ];
        
        foreach ($requiredColumns as $column => $type) {
            if (!isset($columns[$column])) {
                echo "<p class='warning'>⚠ La columna '$column' no existe. Añadiendo columna...</p>";
                
                try {
                    $sql = "ALTER TABLE appointments ADD COLUMN $column $type";
                    $db->exec($sql);
                    echo "<p class='success'>✓ Columna $column añadida correctamente.</p>";
                    $needsUpdate = true;
                } catch (PDOException $e) {
                    echo "<p class='error'>✗ Error al añadir columna $column: " . $e->getMessage() . "</p>";
                }
            }
        }
        
        if ($needsUpdate) {
            echo "<h2>Estructura actualizada de la tabla appointments</h2>";
            
            $stmt = $db->query("DESCRIBE appointments");
            $updatedColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table>";
            echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Predeterminado</th><th>Extra</th></tr>";
            
            foreach ($updatedColumns as $column) {
                echo "<tr>";
                echo "<td>" . $column['Field'] . "</td>";
                echo "<td>" . $column['Type'] . "</td>";
                echo "<td>" . $column['Null'] . "</td>";
                echo "<td>" . $column['Key'] . "</td>";
                echo "<td>" . $column['Default'] . "</td>";
                echo "<td>" . $column['Extra'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p class='success'>✓ La estructura de la tabla appointments ya es correcta.</p>";
        }
    }
    
    // Actualizar el archivo de API para usar los nombres de columna correctos
    $apiFiles = [
        'backend/api/appointments.php',
        'backend/api/get_appointments.php',
        'backend/api/create_appointment.php'
    ];
    
    foreach ($apiFiles as $apiFile) {
        if (file_exists($apiFile)) {
            echo "<p>Actualizando archivo API: $apiFile</p>";
            
            $apiContent = file_get_contents($apiFile);
            
            // Reemplazar las referencias a las columnas
            $replacements = [
                'a.date' => 'a.appointment_date',
                'a.time' => 'a.appointment_time',
                'ORDER BY a.date' => 'ORDER BY a.appointment_date',
                'a.user_id' => 'a.patient_id',
                "'date' => " => "'appointment_date' => ",
                "'time' => " => "'appointment_time' => ",
                'date = :date' => 'appointment_date = :date',
                'time = :time' => 'appointment_time = :time'
            ];
            
            $newApiContent = $apiContent;
            $replacementsMade = false;
            
            foreach ($replacements as $search => $replace) {
                if (strpos($apiContent, $search) !== false) {
                    $newApiContent = str_replace($search, $replace, $newApiContent);
                    $replacementsMade = true;
                    echo "<p class='success'>✓ Reemplazado '$search' por '$replace' en el archivo API</p>";
                }
            }
            
            if ($replacementsMade) {
                file_put_contents($apiFile, $newApiContent);
                echo "<p class='success'>✓ Archivo API actualizado correctamente.</p>";
            } else {
                echo "<p class='success'>✓ El archivo API ya está utilizando los nombres de columna correctos.</p>";
            }
        }
    }
    
    echo "<p class='success'>✓ La corrección del sistema de citas médicas ha sido completada.</p>";
    echo "<p>Ahora deberías poder ver y crear citas sin problemas.</p>";
    
    echo "<div style='margin-top: 20px;'>";
    echo "<a href='citas_db.html' class='button'>Ir al Sistema de Citas</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
