<?php
/**
 * Script para corregir problemas con las citas y verificar el funcionamiento del filtro "Con cita próxima"
 */

// Incluir configuración de base de datos
require_once '../config/database.php';

try {
    // Obtener conexión a la base de datos
    $db = getDbConnection();
    
    // Verificar la estructura de la tabla appointments
    echo "<h2>Estructura de la tabla appointments</h2>";
    $tableStructure = $db->query("DESCRIBE appointments");
    $columns = $tableStructure->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
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
    
    // Verificar pacientes y médicos disponibles
    $patientsQuery = $db->query("SELECT id, name, first_name, last_name, email FROM users WHERE role = 'patient' LIMIT 5");
    $patients = $patientsQuery->fetchAll(PDO::FETCH_ASSOC);
    
    $doctorsQuery = $db->query("SELECT id, name, first_name, last_name, email FROM users WHERE role = 'doctor' LIMIT 3");
    $doctors = $doctorsQuery->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($patients) || empty($doctors)) {
        echo "<h2>No hay suficientes usuarios para crear citas de prueba</h2>";
        exit;
    }
    
    echo "<h2>Pacientes disponibles</h2>";
    echo "<ul>";
    foreach ($patients as $patient) {
        $displayName = isset($patient['name']) ? $patient['name'] : ($patient['first_name'] . ' ' . $patient['last_name']);
        echo "<li>ID: " . $patient['id'] . " - Nombre: " . $displayName . " - Email: " . $patient['email'] . "</li>";
    }
    echo "</ul>";
    
    echo "<h2>Médicos disponibles</h2>";
    echo "<ul>";
    foreach ($doctors as $doctor) {
        $displayName = isset($doctor['name']) ? $doctor['name'] : ($doctor['first_name'] . ' ' . $doctor['last_name']);
        echo "<li>ID: " . $doctor['id'] . " - Nombre: " . $displayName . " - Email: " . $doctor['email'] . "</li>";
    }
    echo "</ul>";
    
    // Verificar citas existentes
    $appointmentsQuery = $db->query("SELECT * FROM appointments LIMIT 10");
    $appointments = $appointmentsQuery->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Citas existentes</h2>";
    if (empty($appointments)) {
        echo "<p>No hay citas en la base de datos.</p>";
    } else {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Paciente ID</th><th>Médico ID</th><th>Fecha</th><th>Hora inicio</th><th>Hora fin</th><th>Motivo</th><th>Estado</th></tr>";
        
        foreach ($appointments as $appointment) {
            echo "<tr>";
            echo "<td>" . $appointment['id'] . "</td>";
            echo "<td>" . $appointment['patient_id'] . "</td>";
            echo "<td>" . $appointment['doctor_id'] . "</td>";
            echo "<td>" . $appointment['appointment_date'] . "</td>";
            echo "<td>" . $appointment['start_time'] . "</td>";
            echo "<td>" . $appointment['end_time'] . "</td>";
            echo "<td>" . $appointment['reason'] . "</td>";
            echo "<td>" . $appointment['status'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // Crear una cita futura para un paciente
    $patient_id = $patients[0]['id'];
    $doctor_id = $doctors[0]['id'];
    $date = date('Y-m-d', strtotime('+2 days')); // Pasado mañana
    $start_time = '11:00:00';
    $end_time = '12:00:00';
    $reason = 'Cita futura para probar filtro';
    $status = 'pending';
    
    echo "<h2>Creando cita futura para paciente ID: $patient_id</h2>";
    
    // Verificar si ya existe una cita similar
    $checkSql = "SELECT id FROM appointments WHERE patient_id = ? AND appointment_date = ? AND start_time = ?";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute([$patient_id, $date, $start_time]);
    
    if ($checkStmt->rowCount() > 0) {
        echo "<p>Ya existe una cita similar para este paciente. No se creará una nueva.</p>";
    } else {
        // Insertar la cita
        $insertSql = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, start_time, end_time, reason, status, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $insertStmt = $db->prepare($insertSql);
        
        try {
            $result = $insertStmt->execute([$patient_id, $doctor_id, $date, $start_time, $end_time, $reason, $status]);
            
            if ($result) {
                $appointment_id = $db->lastInsertId();
                echo "<p>Cita futura creada con éxito. ID: $appointment_id</p>";
            } else {
                echo "<p>Error al crear la cita futura.</p>";
            }
        } catch (PDOException $e) {
            echo "<p>Error al insertar cita: " . $e->getMessage() . "</p>";
        }
    }
    
    // Verificar próximas citas para cada paciente
    echo "<h2>Verificando próximas citas para cada paciente</h2>";
    
    $today = date('Y-m-d');
    
    foreach ($patients as $patient) {
        $patientId = $patient['id'];
        $displayName = isset($patient['name']) ? $patient['name'] : ($patient['first_name'] . ' ' . $patient['last_name']);
        
        echo "<h3>Paciente: $displayName (ID: $patientId)</h3>";
        
        // Verificar próxima cita
        $nextAppointmentSql = "SELECT appointment_date, start_time 
                              FROM appointments 
                              WHERE patient_id = ? AND status = 'pending' AND appointment_date >= ? 
                              ORDER BY appointment_date ASC, start_time ASC 
                              LIMIT 1";
        $nextAppointmentStmt = $db->prepare($nextAppointmentSql);
        $nextAppointmentStmt->execute([$patientId, $today]);
        
        if ($nextAppointmentStmt->rowCount() > 0) {
            $nextAppointment = $nextAppointmentStmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>Próxima cita: " . $nextAppointment['appointment_date'] . " " . $nextAppointment['start_time'] . "</p>";
        } else {
            echo "<p>No tiene citas próximas.</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<h2>Error de base de datos</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
