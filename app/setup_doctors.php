<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir archivos necesarios
require_once 'database_class.php';
require_once 'debug_log.php';

echo "Configurando médicos por especialidad...\n";

try {
    // Crear instancia de la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Limpiar tablas existentes
    $db->exec("DELETE FROM doctor_specialties");
    
    // Verificar si hay médicos en la base de datos
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'doctor'");
    $doctorCount = $stmt->fetchColumn();
    
    // Si ya hay médicos, eliminarlos para empezar de nuevo
    if ($doctorCount > 0) {
        $db->exec("DELETE FROM users WHERE role = 'doctor'");
        echo "Médicos existentes eliminados.\n";
    }
    
    // Lista de especialidades principales
    $specialties = [
        'Cardiología',
        'Dermatología',
        'Ginecología',
        'Medicina Familiar',
        'Neurología',
        'Oftalmología',
        'Pediatría',
        'Traumatología'
    ];
    
    // Lista de médicos por especialidad (2 por cada una)
    $doctors = [
        // Cardiología
        [
            'name' => 'Dr. Carlos Jiménez',
            'email' => 'carlos.jimenez@salutia.com',
            'password' => password_hash('doctor123', PASSWORD_DEFAULT),
            'specialty' => 'Cardiología'
        ],
        [
            'name' => 'Dra. Laura Martínez',
            'email' => 'laura.martinez@salutia.com',
            'password' => password_hash('doctor123', PASSWORD_DEFAULT),
            'specialty' => 'Cardiología'
        ],
        
        // Dermatología
        [
            'name' => 'Dr. Miguel Sánchez',
            'email' => 'miguel.sanchez@salutia.com',
            'password' => password_hash('doctor123', PASSWORD_DEFAULT),
            'specialty' => 'Dermatología'
        ],
        [
            'name' => 'Dra. Ana López',
            'email' => 'ana.lopez@salutia.com',
            'password' => password_hash('doctor123', PASSWORD_DEFAULT),
            'specialty' => 'Dermatología'
        ],
        
        // Ginecología
        [
            'name' => 'Dra. Carmen Rodríguez',
            'email' => 'carmen.rodriguez@salutia.com',
            'password' => password_hash('doctor123', PASSWORD_DEFAULT),
            'specialty' => 'Ginecología'
        ],
        [
            'name' => 'Dra. Elena Pérez',
            'email' => 'elena.perez@salutia.com',
            'password' => password_hash('doctor123', PASSWORD_DEFAULT),
            'specialty' => 'Ginecología'
        ],
        
        // Medicina Familiar
        [
            'name' => 'Dr. Javier García',
            'email' => 'javier.garcia@salutia.com',
            'password' => password_hash('doctor123', PASSWORD_DEFAULT),
            'specialty' => 'Medicina Familiar'
        ],
        [
            'name' => 'Dra. Marta Fernández',
            'email' => 'marta.fernandez@salutia.com',
            'password' => password_hash('doctor123', PASSWORD_DEFAULT),
            'specialty' => 'Medicina Familiar'
        ],
        
        // Neurología
        [
            'name' => 'Dr. Roberto Navarro',
            'email' => 'roberto.navarro@salutia.com',
            'password' => password_hash('doctor123', PASSWORD_DEFAULT),
            'specialty' => 'Neurología'
        ],
        [
            'name' => 'Dra. Lucía Gómez',
            'email' => 'lucia.gomez@salutia.com',
            'password' => password_hash('doctor123', PASSWORD_DEFAULT),
            'specialty' => 'Neurología'
        ],
        
        // Oftalmología
        [
            'name' => 'Dr. Antonio Ruiz',
            'email' => 'antonio.ruiz@salutia.com',
            'password' => password_hash('doctor123', PASSWORD_DEFAULT),
            'specialty' => 'Oftalmología'
        ],
        [
            'name' => 'Dra. Isabel Torres',
            'email' => 'isabel.torres@salutia.com',
            'password' => password_hash('doctor123', PASSWORD_DEFAULT),
            'specialty' => 'Oftalmología'
        ],
        
        // Pediatría
        [
            'name' => 'Dr. Francisco Moreno',
            'email' => 'francisco.moreno@salutia.com',
            'password' => password_hash('doctor123', PASSWORD_DEFAULT),
            'specialty' => 'Pediatría'
        ],
        [
            'name' => 'Dra. Cristina Díaz',
            'email' => 'cristina.diaz@salutia.com',
            'password' => password_hash('doctor123', PASSWORD_DEFAULT),
            'specialty' => 'Pediatría'
        ],
        
        // Traumatología
        [
            'name' => 'Dr. Alejandro Vega',
            'email' => 'alejandro.vega@salutia.com',
            'password' => password_hash('doctor123', PASSWORD_DEFAULT),
            'specialty' => 'Traumatología'
        ],
        [
            'name' => 'Dra. Patricia Romero',
            'email' => 'patricia.romero@salutia.com',
            'password' => password_hash('doctor123', PASSWORD_DEFAULT),
            'specialty' => 'Traumatología'
        ]
    ];
    
    // Insertar médicos y sus especialidades
    foreach ($doctors as $doctor) {
        // Insertar el médico
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, 'doctor')");
        $stmt->bindParam(':name', $doctor['name']);
        $stmt->bindParam(':email', $doctor['email']);
        $stmt->bindParam(':password', $doctor['password']);
        $stmt->execute();
        
        $doctorId = $db->lastInsertId();
        
        // Asignar especialidad
        $stmt = $db->prepare("INSERT INTO doctor_specialties (doctor_id, specialty) VALUES (:doctor_id, :specialty)");
        $stmt->bindParam(':doctor_id', $doctorId);
        $stmt->bindParam(':specialty', $doctor['specialty']);
        $stmt->execute();
        
        echo "Médico creado: " . $doctor['name'] . " - " . $doctor['specialty'] . "\n";
        
        // Verificar la estructura de la tabla doctor_availability
        try {
            $stmt = $db->query("DESCRIBE doctor_availability");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Verificar si la columna day_of_week existe
            $dayOfWeekExists = in_array('day_of_week', $columns);
            
            if (!$dayOfWeekExists) {
                echo "La tabla doctor_availability no tiene la columna day_of_week. Actualizando estructura...\n";
                
                // Intentar agregar la columna si no existe
                $db->exec("ALTER TABLE doctor_availability ADD COLUMN day_of_week VARCHAR(10) NOT NULL AFTER doctor_id");
                echo "Columna day_of_week agregada correctamente.\n";
            }
            
            // Configurar disponibilidad
            $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            $availableDays = array_rand(array_flip($daysOfWeek), rand(3, 5));
            
            if (!is_array($availableDays)) {
                $availableDays = [$availableDays];
            }
            
            foreach ($availableDays as $day) {
                // Horario de mañana (8:00 - 14:00)
                if (rand(0, 1) == 1) {
                    $stmt = $db->prepare("INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time) VALUES (:doctor_id, :day, '08:00:00', '14:00:00')");
                    $stmt->bindParam(':doctor_id', $doctorId);
                    $stmt->bindParam(':day', $day);
                    $stmt->execute();
                    
                    echo "Asignado horario de mañana para " . $doctor['name'] . " el " . $day . ".\n";
                }
                
                // Horario de tarde (16:00 - 20:00)
                if (rand(0, 1) == 1) {
                    $stmt = $db->prepare("INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time) VALUES (:doctor_id, :day, '16:00:00', '20:00:00')");
                    $stmt->bindParam(':doctor_id', $doctorId);
                    $stmt->bindParam(':day', $day);
                    $stmt->execute();
                    
                    echo "Asignado horario de tarde para " . $doctor['name'] . " el " . $day . ".\n";
                }
            }
        } catch (Exception $e) {
            echo "Error al configurar disponibilidad: " . $e->getMessage() . "\n";
        }
    }
    
    echo "Configuración de médicos completada con éxito.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    debug_log('Error en setup_doctors.php: ' . $e->getMessage());
}
?>
