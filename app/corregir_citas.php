<?php
// Script para corregir la estructura del sistema de citas médicas
header("Content-Type: text/html; charset=UTF-8");

echo "<html><head><title>Corrección del Sistema de Citas - Salutia</title>
<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
    h1, h2 { color: #0066cc; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
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
<h1>Corrección del Sistema de Citas Médicas - Salutia</h1>";

try {
    // Incluir la configuración de la base de datos
    require_once __DIR__ . '/../backend/config/database_class.php';
    
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p class='success'>✓ Conexión a la base de datos establecida correctamente.</p>";
    
    // Verificar si existen las tablas necesarias
    $tables = ['doctors', 'doctor_availability', 'appointments'];
    $missingTables = [];
    
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() === 0) {
            $missingTables[] = $table;
        }
    }
    
    if (!empty($missingTables)) {
        echo "<p class='warning'>⚠ Las siguientes tablas no existen: " . implode(', ', $missingTables) . "</p>";
        echo "<p>Creando tablas necesarias...</p>";
        
        // Crear tabla de médicos si no existe
        if (in_array('doctors', $missingTables)) {
            $createDoctorsTable = "
                CREATE TABLE IF NOT EXISTS doctors (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    specialty VARCHAR(50) NOT NULL,
                    email VARCHAR(100) UNIQUE,
                    phone VARCHAR(20),
                    active BOOLEAN DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";
            $db->exec($createDoctorsTable);
            echo "<p class='success'>✓ Tabla 'doctors' creada correctamente.</p>";
            
            // Insertar médicos de ejemplo
            $insertDoctors = "
                INSERT INTO doctors (name, specialty, email, phone) VALUES
                ('Dr. Joan Metge', 'Medicina General', 'joan.metge@salutia.com', '123456789'),
                ('Dra. Ana Cardióloga', 'Cardiología', 'ana.cardio@salutia.com', '234567890'),
                ('Dr. Pedro Dermatologo', 'Dermatología', 'pedro.derm@salutia.com', '345678901'),
                ('Dra. María Pediatra', 'Pediatría', 'maria.pediatra@salutia.com', '456789012'),
                ('Dra. Laura Ginecóloga', 'Ginecología', 'laura.gineco@salutia.com', '567890123');
            ";
            $db->exec($insertDoctors);
            echo "<p class='success'>✓ Médicos de ejemplo insertados correctamente.</p>";
        }
        
        // Crear tabla de disponibilidad de médicos si no existe
        if (in_array('doctor_availability', $missingTables)) {
            $createDoctorAvailabilityTable = "
                CREATE TABLE IF NOT EXISTS doctor_availability (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    doctor_id INT NOT NULL,
                    date DATE NOT NULL,
                    time_slot VARCHAR(10) NOT NULL,
                    is_available BOOLEAN DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_doctor_slot (doctor_id, date, time_slot)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";
            $db->exec($createDoctorAvailabilityTable);
            echo "<p class='success'>✓ Tabla 'doctor_availability' creada correctamente.</p>";
            
            // Generar disponibilidad para los próximos 30 días
            echo "<p>Generando disponibilidad para los médicos...</p>";
            
            // Obtener todos los médicos
            $stmt = $db->query("SELECT id FROM doctors WHERE active = 1");
            $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Generar horarios para los próximos 30 días
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime('+30 days'));
            $timeSlots = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', 
                          '16:00', '16:30', '17:00', '17:30', '18:00', '18:30'];
            
            $insertAvailability = $db->prepare("
                INSERT INTO doctor_availability (doctor_id, date, time_slot, is_available)
                VALUES (:doctor_id, :date, :time_slot, 1)
                ON DUPLICATE KEY UPDATE is_available = 1
            ");
            
            $currentDate = $startDate;
            $count = 0;
            
            while ($currentDate <= $endDate) {
                $dayOfWeek = date('w', strtotime($currentDate));
                
                // Saltar fines de semana (0 = domingo, 6 = sábado)
                if ($dayOfWeek != 0 && $dayOfWeek != 6) {
                    foreach ($doctors as $doctor) {
                        foreach ($timeSlots as $timeSlot) {
                            $insertAvailability->execute([
                                ':doctor_id' => $doctor['id'],
                                ':date' => $currentDate,
                                ':time_slot' => $timeSlot
                            ]);
                            $count++;
                        }
                    }
                }
                
                $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
            }
            
            echo "<p class='success'>✓ Se generaron $count horarios disponibles para los médicos.</p>";
        }
        
        // Crear tabla de citas si no existe
        if (in_array('appointments', $missingTables)) {
            $createAppointmentsTable = "
                CREATE TABLE IF NOT EXISTS appointments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    patient_id INT NOT NULL,
                    doctor_id INT NOT NULL,
                    appointment_date DATE NOT NULL,
                    appointment_time VARCHAR(10) NOT NULL,
                    notes TEXT,
                    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_appointment (doctor_id, appointment_date, appointment_time)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";
            $db->exec($createAppointmentsTable);
            echo "<p class='success'>✓ Tabla 'appointments' creada correctamente.</p>";
        }
    } else {
        echo "<p class='success'>✓ Todas las tablas necesarias existen.</p>";
        
        // Verificar y corregir la estructura de la tabla appointments
        $stmt = $db->query("DESCRIBE appointments");
        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[$row['Field']] = $row;
        }
        
        $needsUpdate = false;
        
        // Verificar si existen las columnas date y time
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
        
        if (!$needsUpdate) {
            echo "<p class='success'>✓ La estructura de la tabla 'appointments' es correcta.</p>";
        }
    }
    
    // Ahora corregimos el archivo de API para que use los nombres de columna correctos
    $apiFile = 'backend/api/appointments.php';
    $apiContent = file_get_contents($apiFile);
    
    // Reemplazar las referencias a las columnas
    $replacements = [
        'a.date' => 'a.appointment_date',
        'a.time' => 'a.appointment_time',
        'ORDER BY a.date' => 'ORDER BY a.appointment_date',
        'a.user_id' => 'a.patient_id',
        "'date' => " => "'appointment_date' => ",
        "'time' => " => "'appointment_time' => "
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
    
    // Mostrar la estructura actual de las tablas
    echo "<h2>Estructura actual de las tablas</h2>";
    
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<h3>Tabla: $table</h3>";
            
            $stmt = $db->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
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
        }
    }
    
    echo "<p class='success'>✓ El sistema de citas médicas ha sido corregido correctamente.</p>";
    echo "<p>Ahora deberías poder crear y ver citas sin problemas.</p>";
    
    echo "<div style='margin-top: 20px;'>";
    echo "<a href='citas_db.html' class='button'>Ir al Sistema de Citas</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
