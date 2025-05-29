<?php
// Configuración de la base de datos
require_once '../config/database.php';

try {
    // Conectar a la base de datos
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8");
    
    echo "<h1>Configuración de disponibilidad de médicos</h1>";
    
    // Verificar si la tabla doctor_availability existe
    $tableExistsQuery = "SHOW TABLES LIKE 'doctor_availability'";
    $stmt = $conn->prepare($tableExistsQuery);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // Crear la tabla doctor_availability si no existe
        $createTableQuery = "CREATE TABLE doctor_availability (
            id INT AUTO_INCREMENT PRIMARY KEY,
            doctor_id INT NOT NULL,
            date DATE NOT NULL,
            time TIME NOT NULL,
            is_available BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_availability (doctor_id, date, time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->exec($createTableQuery);
        echo "<p style='color: green;'>✅ Tabla 'doctor_availability' creada correctamente.</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ La tabla 'doctor_availability' ya existe.</p>";
    }
    
    // Obtener todos los médicos
    $getDoctorsQuery = "SELECT id, first_name, last_name FROM users WHERE role = 'doctor'";
    $stmt = $conn->prepare($getDoctorsQuery);
    $stmt->execute();
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($doctors) == 0) {
        echo "<p style='color: orange;'>⚠️ No hay médicos registrados en el sistema.</p>";
        echo "<p>Por favor, ejecuta primero el script <a href='update_users_table.php'>update_users_table.php</a> para crear usuarios de ejemplo.</p>";
        exit;
    }
    
    echo "<p>Se encontraron " . count($doctors) . " médicos en el sistema.</p>";
    
    // Generar disponibilidad para los próximos 30 días
    $startDate = new DateTime();
    $endDate = new DateTime('+30 days');
    
    // Horarios de consulta (de 9:00 a 17:00, cada 30 minutos)
    $timeSlots = [];
    $startTime = new DateTime('09:00');
    $endTime = new DateTime('17:00');
    $interval = new DateInterval('PT30M'); // 30 minutos
    
    $currentTime = clone $startTime;
    while ($currentTime < $endTime) {
        $timeSlots[] = $currentTime->format('H:i:s');
        $currentTime->add($interval);
    }
    
    // Eliminar disponibilidad existente para evitar duplicados
    $deleteQuery = "DELETE FROM doctor_availability";
    $conn->exec($deleteQuery);
    echo "<p>Se ha eliminado la disponibilidad anterior para evitar duplicados.</p>";
    
    // Insertar disponibilidad para cada médico
    $insertCount = 0;
    $currentDate = clone $startDate;
    
    // Preparar la consulta de inserción
    $insertQuery = "INSERT INTO doctor_availability (doctor_id, date, time) VALUES (:doctor_id, :date, :time)";
    $insertStmt = $conn->prepare($insertQuery);
    
    while ($currentDate <= $endDate) {
        $formattedDate = $currentDate->format('Y-m-d');
        
        // Saltar los domingos (día 0)
        if ($currentDate->format('w') != 0) {
            foreach ($doctors as $doctor) {
                foreach ($timeSlots as $timeSlot) {
                    // Para cada médico, hacer que algunos horarios no estén disponibles aleatoriamente
                    if (rand(0, 10) > 3) { // 70% de probabilidad de estar disponible
                        try {
                            $insertStmt->bindParam(':doctor_id', $doctor['id']);
                            $insertStmt->bindParam(':date', $formattedDate);
                            $insertStmt->bindParam(':time', $timeSlot);
                            $insertStmt->execute();
                            $insertCount++;
                        } catch (PDOException $e) {
                            // Ignorar errores de duplicados
                            if ($e->getCode() != 23000) {
                                throw $e;
                            }
                        }
                    }
                }
            }
        }
        
        $currentDate->modify('+1 day');
    }
    
    echo "<p style='color: green;'>✅ Se han generado $insertCount horarios disponibles para los médicos.</p>";
    
    // Mostrar algunos horarios disponibles como ejemplo
    $sampleQuery = "SELECT u.first_name, u.last_name, da.date, da.time 
                   FROM doctor_availability da 
                   JOIN users u ON da.doctor_id = u.id 
                   ORDER BY da.date, da.time 
                   LIMIT 20";
    $stmt = $conn->prepare($sampleQuery);
    $stmt->execute();
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($samples) > 0) {
        echo "<h3>Ejemplos de horarios disponibles:</h3>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Médico</th><th>Fecha</th><th>Hora</th></tr>";
        
        foreach ($samples as $sample) {
            echo "<tr>";
            echo "<td>Dr. " . $sample['first_name'] . " " . $sample['last_name'] . "</td>";
            
            // Formatear fecha como dd/mm/yyyy
            $date = new DateTime($sample['date']);
            echo "<td>" . $date->format('d/m/Y') . "</td>";
            
            // Formatear hora como hh:mm
            $time = new DateTime($sample['time']);
            echo "<td>" . $time->format('H:i') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    echo "<p>La disponibilidad de los médicos ha sido configurada correctamente.</p>";
    echo "<p><a href='../../update_database.html'>Volver a la página de actualización de la base de datos</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
