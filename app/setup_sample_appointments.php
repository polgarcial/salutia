<?php
/**
 * Script para configurar citas de ejemplo en la base de datos
 */

// Incluir configuración de base de datos
require_once '../config/database.php';

// Función para generar una fecha aleatoria en el rango especificado
function randomDate($start_date, $end_date) {
    $min = strtotime($start_date);
    $max = strtotime($end_date);
    $val = rand($min, $max);
    return date('Y-m-d', $val);
}

try {
    // Obtener conexión a la base de datos
    $db = getDbConnection();
    
    // Verificar la estructura de la tabla appointments
    $tableStructure = $db->query("DESCRIBE appointments");
    $columns = $tableStructure->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Estructura de la tabla appointments</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Predeterminado</th><th>Extra</th></tr>";
    
    $columnNames = [];
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
        
        $columnNames[] = $column['Field'];
    }
    
    echo "</table>";
    
    // Obtener lista de pacientes
    $patientsQuery = $db->query("SELECT id FROM users WHERE role = 'patient' LIMIT 10");
    $patients = $patientsQuery->fetchAll(PDO::FETCH_COLUMN);
    
    // Obtener lista de médicos
    $doctorsQuery = $db->query("SELECT id FROM users WHERE role = 'doctor' LIMIT 5");
    $doctors = $doctorsQuery->fetchAll(PDO::FETCH_COLUMN);
    
    // Si no hay pacientes o médicos, crearlos
    if (empty($patients) || empty($doctors)) {
        echo "<p>No hay suficientes usuarios en la base de datos. Por favor, ejecute primero el script de configuración de usuarios.</p>";
        exit;
    }
    
    echo "<p>Pacientes disponibles: " . count($patients) . "</p>";
    echo "<p>Médicos disponibles: " . count($doctors) . "</p>";
    
    // Verificar si ya hay citas en la tabla
    $checkAppointments = $db->query("SELECT COUNT(*) FROM appointments");
    $appointmentCount = $checkAppointments->fetchColumn();
    
    echo "<p>Número de citas existentes: " . $appointmentCount . "</p>";
    
    // Crear citas pasadas (para "Recientes")
    $pastAppointments = [];
    for ($i = 0; $i < 5; $i++) {
        $patient_id = $patients[array_rand($patients)];
        $doctor_id = $doctors[array_rand($doctors)];
        $date = randomDate('2025-04-01', '2025-05-15'); // Citas en el último mes
        $start_time = rand(9, 16) . ":00:00"; // Horas entre 9 AM y 4 PM
        $end_time = (intval(substr($start_time, 0, 2)) + 1) . ":00:00"; // Una hora después
        $reason = "Consulta de rutina #" . ($i + 1);
        $status = rand(0, 1) ? 'completed' : 'cancelled';
        
        $pastAppointments[] = [
            'patient_id' => $patient_id,
            'doctor_id' => $doctor_id,
            'appointment_date' => $date,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'reason' => $reason,
            'status' => $status
        ];
    }
    
    // Crear citas futuras (para "Con cita próxima")
    $futureAppointments = [];
    for ($i = 0; $i < 5; $i++) {
        $patient_id = $patients[array_rand($patients)];
        $doctor_id = $doctors[array_rand($doctors)];
        $date = randomDate('2025-05-23', '2025-06-30'); // Citas en el próximo mes
        $start_time = rand(9, 16) . ":00:00"; // Horas entre 9 AM y 4 PM
        $end_time = (intval(substr($start_time, 0, 2)) + 1) . ":00:00"; // Una hora después
        $reason = "Consulta programada #" . ($i + 1);
        $status = 'pending';
        
        $futureAppointments[] = [
            'patient_id' => $patient_id,
            'doctor_id' => $doctor_id,
            'appointment_date' => $date,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'reason' => $reason,
            'status' => $status
        ];
    }
    
    // Preparar la consulta SQL basada en las columnas existentes
    $fields = [];
    $placeholders = [];
    $fieldCount = 0;
    
    // Campos comunes que esperamos encontrar
    $expectedFields = [
        'patient_id', 'doctor_id', 'appointment_date', 'start_time', 'end_time',
        'reason', 'status', 'created_at'
    ];
    
    foreach ($expectedFields as $field) {
        if (in_array($field, $columnNames)) {
            $fields[] = $field;
            $placeholders[] = '?';
            $fieldCount++;
        }
    }
    
    // Si created_at está en los campos pero queremos usar NOW()
    if (in_array('created_at', $fields)) {
        $index = array_search('created_at', $fields);
        unset($fields[$index]);
        unset($placeholders[$index]);
        $fieldCount--;
        
        $fields[] = 'created_at';
        $placeholders[] = 'NOW()';
    }
    
    $fieldStr = implode(', ', $fields);
    $placeholderStr = implode(', ', $placeholders);
    
    $insertSql = "INSERT INTO appointments ($fieldStr) VALUES ($placeholderStr)";
    echo "<p>SQL Query: $insertSql</p>";
    
    $stmt = $db->prepare($insertSql);
    
    // Insertar citas pasadas
    $pastInserted = 0;
    foreach ($pastAppointments as $appointment) {
        $params = [];
        foreach ($fields as $field) {
            if ($field != 'created_at' || strpos($placeholders[array_search($field, $fields)], 'NOW()') === false) {
                $params[] = $appointment[$field] ?? null;
            }
        }
        
        try {
            $stmt->execute($params);
            $pastInserted++;
        } catch (PDOException $e) {
            echo "<p>Error al insertar cita pasada: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<p>Se han insertado $pastInserted citas pasadas.</p>";
    
    // Insertar citas futuras
    $futureInserted = 0;
    foreach ($futureAppointments as $appointment) {
        $params = [];
        foreach ($fields as $field) {
            if ($field != 'created_at' || strpos($placeholders[array_search($field, $fields)], 'NOW()') === false) {
                $params[] = $appointment[$field] ?? null;
            }
        }
        
        try {
            $stmt->execute($params);
            $futureInserted++;
        } catch (PDOException $e) {
            echo "<p>Error al insertar cita futura: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<p>Se han insertado $futureInserted citas futuras.</p>";
    echo "<p>Configuración de citas de ejemplo completada con éxito.</p>";
    
} catch (PDOException $e) {
    echo "<p>Error al configurar las citas de ejemplo: " . $e->getMessage() . "</p>";
}
?>
