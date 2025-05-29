<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir archivos necesarios
require_once 'database_class.php';
require_once 'debug_log.php';

// Crear instancia de la base de datos
$database = new Database();

// SQL para crear la tabla appointments si no existe
$createAppointmentsTableSQL = "
CREATE TABLE IF NOT EXISTS `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `reason` text,
  `status` enum('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`),
  CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// SQL para crear la tabla doctor_specialties si no existe
$createDoctorSpecialtiesTableSQL = "
CREATE TABLE IF NOT EXISTS `doctor_specialties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `specialty` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `doctor_id` (`doctor_id`),
  CONSTRAINT `doctor_specialties_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// SQL para crear la tabla doctor_availability si no existe
$createDoctorAvailabilityTableSQL = "
CREATE TABLE IF NOT EXISTS `doctor_availability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `doctor_id` (`doctor_id`),
  CONSTRAINT `doctor_availability_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Crear las tablas si no existen
try {
    $db = $database->getConnection();
    debug_log("Conexión a la base de datos establecida correctamente");
    
    // Crear la tabla appointments
    $result = $database->ensureTableExists('appointments', $createAppointmentsTableSQL);
    if ($result) {
        debug_log("Tabla 'appointments' verificada/creada correctamente");
        echo "<p style='color:green'>✓ Tabla 'appointments' verificada/creada correctamente</p>";
    }
    
    // Crear la tabla doctor_specialties
    $result = $database->ensureTableExists('doctor_specialties', $createDoctorSpecialtiesTableSQL);
    if ($result) {
        debug_log("Tabla 'doctor_specialties' verificada/creada correctamente");
        echo "<p style='color:green'>✓ Tabla 'doctor_specialties' verificada/creada correctamente</p>";
    }
    
    // Crear la tabla doctor_availability
    $result = $database->ensureTableExists('doctor_availability', $createDoctorAvailabilityTableSQL);
    if ($result) {
        debug_log("Tabla 'doctor_availability' verificada/creada correctamente");
        echo "<p style='color:green'>✓ Tabla 'doctor_availability' verificada/creada correctamente</p>";
    }
    
    // Verificar si hay citas en la tabla
    $stmt = $db->query("SELECT COUNT(*) as count FROM appointments");
    $appointmentCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    debug_log("Número de citas en la base de datos: " . $appointmentCount);
    
    // Verificar si hay especialidades para los doctores
    $stmt = $db->query("SELECT COUNT(*) as count FROM doctor_specialties");
    $specialtyCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    debug_log("Número de especialidades en la base de datos: " . $specialtyCount);
    
    // Si no hay especialidades, crear algunas para el doctor de prueba
    if ($specialtyCount == 0) {
        // Obtener el ID del doctor de prueba
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND role = 'doctor'");
        $stmt->execute(['doctor@salutia.com']);
        
        if ($stmt->rowCount() > 0) {
            $doctorId = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
            
            // Insertar especialidades para el doctor
            $specialties = ['Medicina General', 'Cardiología'];
            $stmt = $db->prepare("INSERT INTO doctor_specialties (doctor_id, specialty) VALUES (?, ?)");
            
            foreach ($specialties as $specialty) {
                $stmt->execute([$doctorId, $specialty]);
            }
            
            debug_log("Especialidades creadas para el doctor de prueba");
            echo "<p style='color:green'>✓ Especialidades creadas para el doctor de prueba</p>";
            
            // Insertar disponibilidad para el doctor
            $availability = [
                ['monday', '09:00:00', '13:00:00'],
                ['monday', '15:00:00', '18:00:00'],
                ['wednesday', '09:00:00', '13:00:00'],
                ['wednesday', '15:00:00', '18:00:00'],
                ['friday', '09:00:00', '14:00:00']
            ];
            
            $stmt = $db->prepare("INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
            
            foreach ($availability as $slot) {
                $stmt->execute([$doctorId, $slot[0], $slot[1], $slot[2]]);
            }
            
            debug_log("Disponibilidad creada para el doctor de prueba");
            echo "<p style='color:green'>✓ Disponibilidad creada para el doctor de prueba</p>";
        }
    }
    
    // Si no hay citas, crear algunas de prueba
    if ($appointmentCount == 0) {
        // Obtener el ID del paciente de prueba
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND role = 'patient'");
        $stmt->execute(['poli@gmail.com']);
        
        if ($stmt->rowCount() > 0) {
            $patientId = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
            
            // Obtener el ID del doctor de prueba
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND role = 'doctor'");
            $stmt->execute(['doctor@salutia.com']);
            
            if ($stmt->rowCount() > 0) {
                $doctorId = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
                
                // Crear citas de prueba
                $appointments = [
                    // Cita pasada completada
                    [
                        'patient_id' => $patientId,
                        'doctor_id' => $doctorId,
                        'appointment_date' => date('Y-m-d', strtotime('-7 days')),
                        'start_time' => '10:00:00',
                        'end_time' => '10:30:00',
                        'reason' => 'Consulta de rutina',
                        'status' => 'completed',
                        'notes' => 'Paciente en buen estado general. Se recomienda seguimiento en 6 meses.'
                    ],
                    // Cita futura pendiente
                    [
                        'patient_id' => $patientId,
                        'doctor_id' => $doctorId,
                        'appointment_date' => date('Y-m-d', strtotime('+3 days')),
                        'start_time' => '11:00:00',
                        'end_time' => '11:30:00',
                        'reason' => 'Dolor de cabeza recurrente',
                        'status' => 'pending',
                        'notes' => ''
                    ],
                    // Cita futura confirmada
                    [
                        'patient_id' => $patientId,
                        'doctor_id' => $doctorId,
                        'appointment_date' => date('Y-m-d', strtotime('+10 days')),
                        'start_time' => '16:00:00',
                        'end_time' => '16:30:00',
                        'reason' => 'Seguimiento tratamiento',
                        'status' => 'confirmed',
                        'notes' => ''
                    ]
                ];
                
                $stmt = $db->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, start_time, end_time, reason, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                
                foreach ($appointments as $appointment) {
                    $stmt->execute([
                        $appointment['patient_id'],
                        $appointment['doctor_id'],
                        $appointment['appointment_date'],
                        $appointment['start_time'],
                        $appointment['end_time'],
                        $appointment['reason'],
                        $appointment['status'],
                        $appointment['notes']
                    ]);
                }
                
                debug_log("Citas de prueba creadas");
                echo "<p style='color:green'>✓ Citas de prueba creadas</p>";
            }
        }
    } else {
        echo "<p style='color:green'>✓ Ya existen citas en la base de datos</p>";
    }
    
    echo "<p style='color:green'>✓ Configuración completada correctamente</p>";
    echo "<p><a href='../public/login.html'>Ir a la página de inicio de sesión</a></p>";
    echo "<p><a href='../public/views/patient/patient_dashboard.html'>Ir al dashboard de pacientes</a></p>";
} catch (PDOException $e) {
    debug_log("Error al configurar la base de datos: " . $e->getMessage());
    echo "<p style='color:red'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<p>Por favor, asegúrese de que el servidor MySQL esté en funcionamiento y que las credenciales sean correctas.</p>";
}
?>