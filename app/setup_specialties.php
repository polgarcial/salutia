<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir archivos necesarios
require_once 'database_class.php';
require_once 'debug_log.php';

echo "Configurando especialidades médicas...\n";

try {
    // Crear instancia de la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar si la tabla doctor_specialties existe
    $tableExists = false;
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'doctor_specialties'");
        $tableExists = $stmt->rowCount() > 0;
    } catch (Exception $e) {
        echo "Error al verificar la tabla: " . $e->getMessage() . "\n";
    }
    
    // Crear tabla si no existe
    if (!$tableExists) {
        echo "Creando tabla doctor_specialties...\n";
        
        $sql = "CREATE TABLE doctor_specialties (
            id INT AUTO_INCREMENT PRIMARY KEY,
            doctor_id INT NOT NULL,
            specialty VARCHAR(100) NOT NULL,
            FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_doctor_specialty (doctor_id, specialty)
        )";
        
        $db->exec($sql);
        echo "Tabla doctor_specialties creada correctamente.\n";
    } else {
        echo "La tabla doctor_specialties ya existe.\n";
    }
    
    // Lista de especialidades médicas
    $specialties = [
        'Cardiología',
        'Dermatología',
        'Endocrinología',
        'Gastroenterología',
        'Geriatría',
        'Ginecología',
        'Hematología',
        'Infectología',
        'Medicina Familiar',
        'Medicina Interna',
        'Nefrología',
        'Neumología',
        'Neurología',
        'Oftalmología',
        'Oncología',
        'Otorrinolaringología',
        'Pediatría',
        'Psiquiatría',
        'Reumatología',
        'Traumatología',
        'Urología'
    ];
    
    // Obtener todos los médicos
    $stmt = $db->query("SELECT id, name FROM users WHERE role = 'doctor'");
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($doctors) === 0) {
        echo "No hay médicos en la base de datos. Creando médicos de ejemplo...\n";
        
        // Crear médicos de ejemplo
        $exampleDoctors = [
            ['name' => 'Dr. Juan Pérez', 'email' => 'juan.perez@salutia.com', 'password' => password_hash('doctor123', PASSWORD_DEFAULT)],
            ['name' => 'Dra. María García', 'email' => 'maria.garcia@salutia.com', 'password' => password_hash('doctor123', PASSWORD_DEFAULT)],
            ['name' => 'Dr. Carlos Rodríguez', 'email' => 'carlos.rodriguez@salutia.com', 'password' => password_hash('doctor123', PASSWORD_DEFAULT)],
            ['name' => 'Dra. Ana Martínez', 'email' => 'ana.martinez@salutia.com', 'password' => password_hash('doctor123', PASSWORD_DEFAULT)],
            ['name' => 'Dr. Luis Sánchez', 'email' => 'luis.sanchez@salutia.com', 'password' => password_hash('doctor123', PASSWORD_DEFAULT)]
        ];
        
        foreach ($exampleDoctors as $doctor) {
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, 'doctor')");
            $stmt->bindParam(':name', $doctor['name']);
            $stmt->bindParam(':email', $doctor['email']);
            $stmt->bindParam(':password', $doctor['password']);
            $stmt->execute();
            
            echo "Médico creado: " . $doctor['name'] . "\n";
        }
        
        // Obtener los médicos recién creados
        $stmt = $db->query("SELECT id, name FROM users WHERE role = 'doctor'");
        $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo "Asignando especialidades a médicos...\n";
    
    // Limpiar especialidades existentes
    $db->exec("DELETE FROM doctor_specialties");
    
    // Asignar especialidades a cada médico (2-3 especialidades por médico)
    foreach ($doctors as $doctor) {
        // Seleccionar 2-3 especialidades aleatorias para cada médico
        $numSpecialties = rand(2, 3);
        $doctorSpecialties = array_rand(array_flip($specialties), $numSpecialties);
        
        if (!is_array($doctorSpecialties)) {
            $doctorSpecialties = [$doctorSpecialties];
        }
        
        foreach ($doctorSpecialties as $specialty) {
            $stmt = $db->prepare("INSERT INTO doctor_specialties (doctor_id, specialty) VALUES (:doctor_id, :specialty)");
            $stmt->bindParam(':doctor_id', $doctor['id']);
            $stmt->bindParam(':specialty', $specialty);
            $stmt->execute();
            
            echo "Asignada especialidad '{$specialty}' al médico {$doctor['name']}.\n";
        }
    }
    
    // Verificar si la tabla doctor_availability existe
    $tableExists = false;
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'doctor_availability'");
        $tableExists = $stmt->rowCount() > 0;
    } catch (Exception $e) {
        echo "Error al verificar la tabla: " . $e->getMessage() . "\n";
    }
    
    // Crear tabla de disponibilidad si no existe
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
        
        // Añadir disponibilidad para cada médico
        $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        
        foreach ($doctors as $doctor) {
            // Asignar horarios aleatorios para cada médico
            $availableDays = array_rand(array_flip($daysOfWeek), rand(3, 5));
            
            if (!is_array($availableDays)) {
                $availableDays = [$availableDays];
            }
            
            foreach ($availableDays as $day) {
                // Horario de mañana (8:00 - 14:00)
                if (rand(0, 1) == 1) {
                    $stmt = $db->prepare("INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time) VALUES (:doctor_id, :day, '08:00:00', '14:00:00')");
                    $stmt->bindParam(':doctor_id', $doctor['id']);
                    $stmt->bindParam(':day', $day);
                    $stmt->execute();
                    
                    echo "Asignado horario de mañana para {$doctor['name']} el {$day}.\n";
                }
                
                // Horario de tarde (16:00 - 20:00)
                if (rand(0, 1) == 1) {
                    $stmt = $db->prepare("INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time) VALUES (:doctor_id, :day, '16:00:00', '20:00:00')");
                    $stmt->bindParam(':doctor_id', $doctor['id']);
                    $stmt->bindParam(':day', $day);
                    $stmt->execute();
                    
                    echo "Asignado horario de tarde para {$doctor['name']} el {$day}.\n";
                }
            }
        }
    } else {
        echo "La tabla doctor_availability ya existe.\n";
    }
    
    echo "Configuración de especialidades y disponibilidad completada con éxito.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    debug_log('Error en setup_specialties.php: ' . $e->getMessage());
}
?>
