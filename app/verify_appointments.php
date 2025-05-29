<?php
/**
 * Script para verificar que las citas se estén guardando correctamente
 */

// Incluir configuración de base de datos
require_once '../config/database.php';

// Configurar encabezados para texto plano
header('Content-Type: text/plain');

try {
    // Obtener conexión a la base de datos
    $db = getDbConnection();
    
    echo "=== VERIFICACIÓN DE CITAS Y FILTRADO ===\n\n";
    
    // 1. Verificar la estructura de la tabla appointments
    echo "1. Estructura de la tabla appointments:\n";
    $tableStructure = $db->query("SHOW COLUMNS FROM appointments");
    while ($column = $tableStructure->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    echo "\n";
    
    // 2. Verificar citas existentes
    echo "2. Citas existentes:\n";
    $appointmentsQuery = $db->query("SELECT * FROM appointments ORDER BY appointment_date DESC LIMIT 5");
    $appointments = $appointmentsQuery->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($appointments) > 0) {
        foreach ($appointments as $appointment) {
            echo "- ID: " . $appointment['id'] . 
                 ", Paciente: " . $appointment['patient_id'] . 
                 ", Médico: " . $appointment['doctor_id'] . 
                 ", Fecha: " . $appointment['appointment_date'] . 
                 ", Hora inicio: " . $appointment['start_time'] .
                 ", Estado: " . $appointment['status'] . "\n";
        }
    } else {
        echo "No hay citas en la base de datos.\n";
    }
    
    echo "\n";
    
    // 3. Verificar pacientes con cita próxima
    echo "3. Pacientes con cita próxima:\n";
    $today = date('Y-m-d');
    $patientsWithAppointmentsQuery = $db->query("
        SELECT DISTINCT u.id, u.name, u.email, a.appointment_date, a.start_time
        FROM users u
        JOIN appointments a ON u.id = a.patient_id
        WHERE u.role = 'patient' 
        AND a.status = 'pending' 
        AND a.appointment_date >= '$today'
        ORDER BY a.appointment_date ASC, a.start_time ASC
    ");
    
    $patientsWithAppointments = $patientsWithAppointmentsQuery->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($patientsWithAppointments) > 0) {
        foreach ($patientsWithAppointments as $patient) {
            echo "- Paciente: " . $patient['name'] . 
                 " (ID: " . $patient['id'] . ")" .
                 ", Próxima cita: " . $patient['appointment_date'] . 
                 " " . $patient['start_time'] . "\n";
        }
    } else {
        echo "No hay pacientes con citas próximas.\n";
    }
    
    echo "\n";
    
    // 4. Verificar la función getNextAppointment
    echo "4. Verificación de la función getNextAppointment:\n";
    
    // Incluir la función si no está disponible
    if (!function_exists('getNextAppointment')) {
        function getNextAppointment($db, $patient_id) {
            try {
                $sql = "SELECT appointment_date, start_time 
                        FROM appointments 
                        WHERE patient_id = ? AND status = 'pending' AND appointment_date >= ? 
                        ORDER BY appointment_date ASC, start_time ASC 
                        LIMIT 1";
                
                $stmt = $db->prepare($sql);
                $today = date('Y-m-d');
                $stmt->execute([$patient_id, $today]);
                
                if ($stmt->rowCount() > 0) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    return $row['appointment_date'];
                } else {
                    return null;
                }
            } catch (PDOException $e) {
                return null;
            }
        }
    }
    
    // Probar la función con algunos pacientes
    if (count($patientsWithAppointments) > 0) {
        foreach ($patientsWithAppointments as $patient) {
            $nextAppointment = getNextAppointment($db, $patient['id']);
            echo "- Paciente: " . $patient['name'] . 
                 " (ID: " . $patient['id'] . ")" .
                 ", getNextAppointment: " . ($nextAppointment ?? 'null') . "\n";
        }
    } else {
        echo "No hay pacientes para probar la función getNextAppointment.\n";
    }
    
    echo "\n";
    
    // 5. Verificar si hay citas sin start_time o end_time
    echo "5. Verificación de citas con campos faltantes:\n";
    $invalidAppointmentsQuery = $db->query("
        SELECT id, patient_id, doctor_id, appointment_date, start_time, end_time, status
        FROM appointments
        WHERE start_time IS NULL OR end_time IS NULL OR start_time = '' OR end_time = ''
    ");
    
    $invalidAppointments = $invalidAppointmentsQuery->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($invalidAppointments) > 0) {
        echo "Se encontraron " . count($invalidAppointments) . " citas con campos faltantes:\n";
        foreach ($invalidAppointments as $appointment) {
            echo "- ID: " . $appointment['id'] . 
                 ", Paciente: " . $appointment['patient_id'] . 
                 ", Médico: " . $appointment['doctor_id'] . 
                 ", Fecha: " . $appointment['appointment_date'] . 
                 ", Hora inicio: " . ($appointment['start_time'] ?? 'NULL') .
                 ", Hora fin: " . ($appointment['end_time'] ?? 'NULL') . "\n";
        }
    } else {
        echo "No se encontraron citas con campos faltantes.\n";
    }
    
} catch (PDOException $e) {
    echo "Error de base de datos: " . $e->getMessage();
}
?>
