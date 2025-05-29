<?php
/**
 * Script para configurar la base de datos para el apartado de pacientes
 * 
 * Este script ejecuta las consultas SQL necesarias para crear las tablas
 * y añadir datos de ejemplo para el apartado de pacientes.
 */

// Incluir configuración de base de datos
require_once '../config/database.php';

// Obtener conexión a la base de datos
$db = getDbConnection();

// Habilitar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Resultados
$results = [];
$success = true;

try {
    // 1. Crear tabla doctor_patients si no existe
    $sql1 = "CREATE TABLE IF NOT EXISTS doctor_patients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        doctor_id INT NOT NULL,
        patient_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_doctor_patient (doctor_id, patient_id)
    )";
    
    $db->exec($sql1);
    $results[] = "Tabla doctor_patients creada correctamente";
    
    // 2. Modificar tabla users para añadir campos necesarios (verificando si existen primero)
    // Verificar si la columna phone existe
    $checkColumnSql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'users' AND COLUMN_NAME = 'phone'";
    $checkStmt = $db->query($checkColumnSql);
    if ($checkStmt->rowCount() === 0) {
        $db->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL");
        $results[] = "Columna phone añadida a la tabla users";
    }
    
    // Verificar si la columna date_of_birth existe
    $checkColumnSql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'users' AND COLUMN_NAME = 'date_of_birth'";
    $checkStmt = $db->query($checkColumnSql);
    if ($checkStmt->rowCount() === 0) {
        $db->exec("ALTER TABLE users ADD COLUMN date_of_birth DATE NULL");
        $results[] = "Columna date_of_birth añadida a la tabla users";
    }
    
    // Verificar si la columna notes existe
    $checkColumnSql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'users' AND COLUMN_NAME = 'notes'";
    $checkStmt = $db->query($checkColumnSql);
    if ($checkStmt->rowCount() === 0) {
        $db->exec("ALTER TABLE users ADD COLUMN notes TEXT NULL");
        $results[] = "Columna notes añadida a la tabla users";
    }
    
    // Verificar si la columna specialty existe
    $checkColumnSql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'users' AND COLUMN_NAME = 'specialty'";
    $checkStmt = $db->query($checkColumnSql);
    if ($checkStmt->rowCount() === 0) {
        $db->exec("ALTER TABLE users ADD COLUMN specialty VARCHAR(50) NULL");
        $results[] = "Columna specialty añadida a la tabla users";
    }
    
    $results[] = "Verificación de columnas en tabla users completada";
    
    // 3. Insertar pacientes de ejemplo
    $checkSql = "SELECT id FROM users WHERE email = ?";
    $insertSql = "INSERT INTO users (email, password, name, role, phone, date_of_birth, notes) 
                  VALUES (?, ?, ?, 'patient', ?, ?, ?)";
    
    // Paciente 1: Juan Pérez
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute(['juan.perez@example.com']);
    
    if ($checkStmt->rowCount() === 0) {
        $insertStmt = $db->prepare($insertSql);
        $insertStmt->execute([
            'juan.perez@example.com', 
            password_hash('123456', PASSWORD_DEFAULT), 
            'Juan Pérez', 
            '600123456', 
            '1985-05-15', 
            'Alergia a penicilina. Hipertensión controlada.'
        ]);
        $results[] = "Paciente Juan Pérez creado correctamente";
    } else {
        $results[] = "Paciente Juan Pérez ya existe";
    }
    
    // Paciente 2: María García
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute(['maria.garcia@example.com']);
    
    if ($checkStmt->rowCount() === 0) {
        $insertStmt = $db->prepare($insertSql);
        $insertStmt->execute([
            'maria.garcia@example.com', 
            password_hash('123456', PASSWORD_DEFAULT), 
            'María García', 
            '600789012', 
            '1990-10-20', 
            'Asma. Revisión anual en mayo.'
        ]);
        $results[] = "Paciente María García creado correctamente";
    } else {
        $results[] = "Paciente María García ya existe";
    }
    
    // Paciente 3: Carlos Rodríguez
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute(['carlos.rodriguez@example.com']);
    
    if ($checkStmt->rowCount() === 0) {
        $insertStmt = $db->prepare($insertSql);
        $insertStmt->execute([
            'carlos.rodriguez@example.com', 
            password_hash('123456', PASSWORD_DEFAULT), 
            'Carlos Rodríguez', 
            '600345678', 
            '1978-12-03', 
            'Diabetes tipo 2. Control trimestral.'
        ]);
        $results[] = "Paciente Carlos Rodríguez creado correctamente";
    } else {
        $results[] = "Paciente Carlos Rodríguez ya existe";
    }
    
    // 4. Obtener ID del doctor Roberto Fernández
    $doctorSql = "SELECT id FROM users WHERE email = ?";
    $doctorStmt = $db->prepare($doctorSql);
    $doctorStmt->execute(['roberto.fernandez@salutia.com']);
    
    if ($doctorStmt->rowCount() > 0) {
        $doctorRow = $doctorStmt->fetch(PDO::FETCH_ASSOC);
        $doctorId = $doctorRow['id'];
        $results[] = "Doctor Roberto Fernández encontrado con ID: " . $doctorId;
        
        // 5. Obtener IDs de los pacientes
        $patientIds = [];
        $patientEmails = ['juan.perez@example.com', 'maria.garcia@example.com', 'carlos.rodriguez@example.com'];
        
        foreach ($patientEmails as $email) {
            $patientStmt = $db->prepare($doctorSql);
            $patientStmt->execute([$email]);
            
            if ($patientStmt->rowCount() > 0) {
                $patientRow = $patientStmt->fetch(PDO::FETCH_ASSOC);
                $patientIds[$email] = $patientRow['id'];
                $results[] = "Paciente con email $email encontrado con ID: " . $patientRow['id'];
            }
        }
        
        // 6. Asociar pacientes con el doctor
        $associateSql = "INSERT IGNORE INTO doctor_patients (doctor_id, patient_id) VALUES (?, ?)";
        
        foreach ($patientIds as $email => $patientId) {
            $associateStmt = $db->prepare($associateSql);
            $associateStmt->execute([$doctorId, $patientId]);
            
            if ($associateStmt->rowCount() > 0) {
                $results[] = "Paciente con email $email asociado al Dr. Roberto Fernández";
            } else {
                $results[] = "Paciente con email $email ya estaba asociado al Dr. Roberto Fernández";
            }
        }
        
        // 7. Insertar citas de ejemplo
        $checkAppointmentSql = "SELECT id FROM appointments WHERE patient_id = ? AND doctor_id = ? AND appointment_date = ? AND appointment_time = ?";
        $insertAppointmentSql = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status, created_at) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        // Citas para Juan Pérez
        if (isset($patientIds['juan.perez@example.com'])) {
            $patientId = $patientIds['juan.perez@example.com'];
            
            // Cita 1: Revisión general (completada)
            $checkAppointmentStmt = $db->prepare($checkAppointmentSql);
            $checkAppointmentStmt->execute([$patientId, $doctorId, '2025-04-10', '10:00:00']);
            
            if ($checkAppointmentStmt->rowCount() === 0) {
                $insertAppointmentStmt = $db->prepare($insertAppointmentSql);
                $insertAppointmentStmt->execute([$patientId, $doctorId, '2025-04-10', '10:00:00', 'Revisión general', 'completed', '2025-04-01']);
                $results[] = "Cita 1 para Juan Pérez creada correctamente";
            }
            
            // Cita 2: Control hipertensión (pendiente)
            $checkAppointmentStmt = $db->prepare($checkAppointmentSql);
            $checkAppointmentStmt->execute([$patientId, $doctorId, '2025-05-25', '11:00:00']);
            
            if ($checkAppointmentStmt->rowCount() === 0) {
                $insertAppointmentStmt = $db->prepare($insertAppointmentSql);
                $insertAppointmentStmt->execute([$patientId, $doctorId, '2025-05-25', '11:00:00', 'Control hipertensión', 'pending', '2025-05-01']);
                $results[] = "Cita 2 para Juan Pérez creada correctamente";
            }
        }
        
        // Citas para María García
        if (isset($patientIds['maria.garcia@example.com'])) {
            $patientId = $patientIds['maria.garcia@example.com'];
            
            // Cita 1: Control asma (completada)
            $checkAppointmentStmt = $db->prepare($checkAppointmentSql);
            $checkAppointmentStmt->execute([$patientId, $doctorId, '2025-03-15', '15:00:00']);
            
            if ($checkAppointmentStmt->rowCount() === 0) {
                $insertAppointmentStmt = $db->prepare($insertAppointmentSql);
                $insertAppointmentStmt->execute([$patientId, $doctorId, '2025-03-15', '15:00:00', 'Control asma', 'completed', '2025-03-01']);
                $results[] = "Cita 1 para María García creada correctamente";
            }
            
            // Cita 2: Revisión anual (pendiente)
            $checkAppointmentStmt = $db->prepare($checkAppointmentSql);
            $checkAppointmentStmt->execute([$patientId, $doctorId, '2025-05-30', '10:30:00']);
            
            if ($checkAppointmentStmt->rowCount() === 0) {
                $insertAppointmentStmt = $db->prepare($insertAppointmentSql);
                $insertAppointmentStmt->execute([$patientId, $doctorId, '2025-05-30', '10:30:00', 'Revisión anual', 'pending', '2025-05-01']);
                $results[] = "Cita 2 para María García creada correctamente";
            }
        }
    } else {
        $results[] = "Doctor Roberto Fernández no encontrado";
    }
    
} catch (PDOException $e) {
    $success = false;
    $results[] = "Error: " . $e->getMessage();
}

// Devolver resultado como JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => $success,
    'results' => $results
]);
?>
