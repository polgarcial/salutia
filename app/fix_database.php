<?php
// Script para corregir problemas en la base de datos
header("Content-Type: text/html; charset=utf-8");
echo "<h1>Corrigiendo problemas en la base de datos</h1>";

require_once __DIR__ . "/../backend/config/database_class.php";

try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<p>Conexión a la base de datos establecida.</p>";
    
    // Verificar y corregir columnas en appointments
    $stmt = $db->query("SHOW TABLES LIKE 'appointments'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color:red'>La tabla appointments no existe. No se pueden hacer correcciones.</p>";
        exit;
    }
    
    $stmt = $db->query("SHOW COLUMNS FROM appointments LIKE 'appointment_date'");
    $hasAppointmentDate = $stmt->rowCount() > 0;
    
    $stmt = $db->query("SHOW COLUMNS FROM appointments LIKE 'date'");
    $hasDate = $stmt->rowCount() > 0;
    
    if ($hasDate && !$hasAppointmentDate) {
        // Crear appointment_date y copiar datos
        $db->exec("ALTER TABLE appointments ADD COLUMN appointment_date DATE");
        $db->exec("UPDATE appointments SET appointment_date = date");
        echo "<p style='color:green'>✓ Columna appointment_date creada y datos copiados.</p>";
    } elseif ($hasAppointmentDate) {
        echo "<p>La columna appointment_date ya existe.</p>";
    }
    
    $stmt = $db->query("SHOW COLUMNS FROM appointments LIKE 'appointment_time'");
    $hasAppointmentTime = $stmt->rowCount() > 0;
    
    $stmt = $db->query("SHOW COLUMNS FROM appointments LIKE 'time'");
    $hasTime = $stmt->rowCount() > 0;
    
    if ($hasTime && !$hasAppointmentTime) {
        // Crear appointment_time y copiar datos
        $db->exec("ALTER TABLE appointments ADD COLUMN appointment_time TIME");
        $db->exec("UPDATE appointments SET appointment_time = time");
        echo "<p style='color:green'>✓ Columna appointment_time creada y datos copiados.</p>";
    } elseif ($hasAppointmentTime) {
        echo "<p>La columna appointment_time ya existe.</p>";
    }
    
    // Verificar doctor_id en appointments
    $stmt = $db->query("SHOW COLUMNS FROM appointments LIKE 'doctor_id'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color:red'>La columna doctor_id no existe en appointments. Esto puede causar problemas.</p>";
    } else {
        echo "<p style='color:green'>✓ La columna doctor_id existe en appointments.</p>";
    }
    
    echo "<p style='color:green; font-weight:bold'>Correcciones completadas.</p>";
    echo "<p><a href='doctor_dashboard.html' style='display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>Volver al dashboard</a></p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>