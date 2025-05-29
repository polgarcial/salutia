<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Reconstrucción de la tabla de citas</h1>";
echo "<pre>";

// Función para registrar mensajes
function log_message($message) {
    echo $message . "\n";
    flush();
}

try {
    // Conectar a la base de datos
    $host = 'localhost';
    $dbname = 'salutia';
    $username = 'root';
    $password = '';
    
    log_message("Conectando a la base de datos...");
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    log_message("Conexión establecida correctamente.");
    
    // Paso 1: Verificar si la tabla appointments existe
    log_message("Verificando si la tabla appointments existe...");
    $check_table = $conn->query("SHOW TABLES LIKE 'appointments'");
    $table_exists = $check_table->rowCount() > 0;
    
    if ($table_exists) {
        log_message("La tabla appointments existe. Obteniendo su estructura...");
        
        // Obtener la estructura actual para preservar los datos
        $columns_query = $conn->query("SHOW COLUMNS FROM appointments");
        $columns = $columns_query->fetchAll(PDO::FETCH_ASSOC);
        
        log_message("Columnas encontradas: " . count($columns));
        foreach ($columns as $col) {
            log_message("- " . $col['Field'] . " (" . $col['Type'] . ")");
        }
        
        // Guardar los datos existentes
        log_message("Guardando datos existentes...");
        $data_query = $conn->query("SELECT * FROM appointments");
        $existing_data = $data_query->fetchAll(PDO::FETCH_ASSOC);
        log_message("Se encontraron " . count($existing_data) . " registros.");
        
        // Renombrar la tabla actual
        log_message("Renombrando la tabla actual a appointments_old...");
        $conn->exec("DROP TABLE IF EXISTS appointments_old");
        $conn->exec("RENAME TABLE appointments TO appointments_old");
        log_message("Tabla renombrada correctamente.");
    } else {
        log_message("La tabla appointments no existe.");
        $existing_data = [];
    }
    
    // Paso 2: Crear la tabla patients si no existe
    log_message("Verificando si la tabla patients existe...");
    $check_patients = $conn->query("SHOW TABLES LIKE 'patients'");
    if ($check_patients->rowCount() == 0) {
        log_message("Creando tabla patients...");
        $create_patients_sql = "CREATE TABLE patients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->exec($create_patients_sql);
        
        // Insertar un paciente de prueba
        log_message("Insertando paciente de prueba...");
        $conn->exec("INSERT INTO patients (id, name, email) VALUES (1, 'Paciente de Prueba', 'paciente@ejemplo.com')");
    } else {
        log_message("La tabla patients ya existe.");
    }
    
    // Paso 3: Crear la tabla doctors si no existe
    log_message("Verificando si la tabla doctors existe...");
    $check_doctors = $conn->query("SHOW TABLES LIKE 'doctors'");
    if ($check_doctors->rowCount() == 0) {
        log_message("Creando tabla doctors...");
        $create_doctors_sql = "CREATE TABLE doctors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            specialty VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->exec($create_doctors_sql);
        
        // Insertar un médico de prueba
        log_message("Insertando médico de prueba...");
        $conn->exec("INSERT INTO doctors (id, name, specialty) VALUES (1, 'Dr. Juan Pérez', 'Cardiología')");
    } else {
        log_message("La tabla doctors ya existe.");
    }
    
    // Paso 4: Crear la nueva tabla appointments SIN restricciones de clave foránea
    log_message("Creando nueva tabla appointments sin restricciones de clave foránea...");
    $create_appointments_sql = "CREATE TABLE appointments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        patient_name VARCHAR(100) NOT NULL,
        patient_email VARCHAR(100) NOT NULL,
        doctor_id INT NOT NULL,
        doctor_name VARCHAR(100) NOT NULL,
        reason TEXT NOT NULL,
        date VARCHAR(20) NOT NULL,
        time VARCHAR(10) NOT NULL,
        appointment_date DATE NOT NULL,
        appointment_time VARCHAR(8),
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        status ENUM('pending', 'confirmed', 'cancelled', 'completed') NOT NULL DEFAULT 'pending',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($create_appointments_sql);
    log_message("Nueva tabla appointments creada correctamente.");
    
    // Paso 5: Restaurar los datos si existían
    if (count($existing_data) > 0) {
        log_message("Restaurando datos existentes...");
        
        foreach ($existing_data as $row) {
            // Preparar los campos para la inserción
            $fields = [];
            $placeholders = [];
            $values = [];
            
            foreach ($row as $key => $value) {
                $fields[] = $key;
                $placeholders[] = "?";
                $values[] = $value;
            }
            
            $sql = "INSERT INTO appointments (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $placeholders) . ")";
            $stmt = $conn->prepare($sql);
            $stmt->execute($values);
        }
        
        log_message("Datos restaurados correctamente.");
    }
    
    // Paso 6: Verificar que todo esté correcto
    log_message("Verificando la nueva tabla appointments...");
    $verify_query = $conn->query("SELECT COUNT(*) as count FROM appointments");
    $count = $verify_query->fetch(PDO::FETCH_ASSOC)['count'];
    log_message("La tabla appointments contiene $count registros.");
    
    log_message("¡Proceso completado con éxito!");
    
    // Eliminar la tabla antigua si todo salió bien
    if ($table_exists) {
        log_message("Eliminando tabla antigua appointments_old...");
        $conn->exec("DROP TABLE IF EXISTS appointments_old");
        log_message("Tabla antigua eliminada.");
    }
    
} catch (PDOException $e) {
    log_message("ERROR: " . $e->getMessage());
}

echo "</pre>";
echo "<p><a href='http://localhost:8000/app/create_appointment_request.php' target='_blank'>Volver a intentar crear una cita</a></p>";
?>
