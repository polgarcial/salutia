<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir archivos necesarios
require_once 'database_class.php';

echo "Configurando médicos por especialidad...\n";

try {
    // Crear instancia de la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Limpiar tablas existentes
    $db->exec("DELETE FROM doctor_specialties");
    $db->exec("DELETE FROM doctor_availability");
    
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
    
    // Obtener todos los médicos
    $stmt = $db->query("SELECT id, name, email FROM users WHERE role = 'doctor'");
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Asignar 2 médicos a cada especialidad
    $doctorsPerSpecialty = [];
    $doctorIndex = 0;
    
    foreach ($specialties as $specialty) {
        $doctorsPerSpecialty[$specialty] = [
            $doctors[$doctorIndex % count($doctors)]['id'],
            $doctors[($doctorIndex + 1) % count($doctors)]['id']
        ];
        $doctorIndex += 2;
    }
    
    // Insertar especialidades
    $stmtInsertSpecialty = $db->prepare("INSERT INTO doctor_specialties (doctor_id, specialty) VALUES (:doctor_id, :specialty)");
    
    foreach ($doctorsPerSpecialty as $specialty => $doctorIds) {
        foreach ($doctorIds as $doctorId) {
            $stmtInsertSpecialty->bindParam(':doctor_id', $doctorId);
            $stmtInsertSpecialty->bindParam(':specialty', $specialty);
            $stmtInsertSpecialty->execute();
            
            // Buscar el nombre del médico para el mensaje
            $doctorName = "";
            foreach ($doctors as $doctor) {
                if ($doctor['id'] == $doctorId) {
                    $doctorName = $doctor['name'];
                    break;
                }
            }
            
            echo "Asignada especialidad '$specialty' al médico $doctorName.\n";
        }
    }
    
    // Configurar disponibilidad para cada médico
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
    $morningStart = '09:00:00';
    $morningEnd = '13:00:00';
    $afternoonStart = '16:00:00';
    $afternoonEnd = '20:00:00';
    
    $stmtInsertAvailability = $db->prepare("
        INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time) 
        VALUES (:doctor_id, :day_of_week, :start_time, :end_time)
    ");
    
    // Para cada médico con especialidad, configurar disponibilidad
    foreach ($doctorsPerSpecialty as $specialty => $doctorIds) {
        foreach ($doctorIds as $doctorId) {
            // Asignar disponibilidad en días aleatorios (3 días por semana)
            $availableDays = array_rand(array_flip($days), 3);
            
            foreach ($availableDays as $day) {
                // Horario de mañana
                $stmtInsertAvailability->bindParam(':doctor_id', $doctorId);
                $stmtInsertAvailability->bindParam(':day_of_week', $day);
                $stmtInsertAvailability->bindParam(':start_time', $morningStart);
                $stmtInsertAvailability->bindParam(':end_time', $morningEnd);
                $stmtInsertAvailability->execute();
                
                // Horario de tarde
                $stmtInsertAvailability->bindParam(':doctor_id', $doctorId);
                $stmtInsertAvailability->bindParam(':day_of_week', $day);
                $stmtInsertAvailability->bindParam(':start_time', $afternoonStart);
                $stmtInsertAvailability->bindParam(':end_time', $afternoonEnd);
                $stmtInsertAvailability->execute();
            }
            
            echo "Configurada disponibilidad para el médico con ID $doctorId.\n";
        }
    }
    
    echo "Configuración de médicos por especialidad completada con éxito.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
