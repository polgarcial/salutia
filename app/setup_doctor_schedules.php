<?php
require_once(__DIR__ . '/../../backend/config/database.php');

function setupDoctorSchedulesTables($conn) {
    // Tabla para las plantillas de horarios
    $sql = "CREATE TABLE IF NOT EXISTS doctor_schedule_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        doctor_id INT NOT NULL,
        template_name VARCHAR(50) NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        FOREIGN KEY (doctor_id) REFERENCES doctors(id)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Tabla doctor_schedule_templates creada correctamente\n";
    } else {
        echo "Error creando tabla doctor_schedule_templates: " . $conn->error . "\n";
    }

    // Tabla para los horarios semanales
    $sql = "CREATE TABLE IF NOT EXISTS doctor_weekly_schedules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        doctor_id INT NOT NULL,
        week_number INT NOT NULL,
        year INT NOT NULL,
        day_of_week INT NOT NULL, -- 1 = Lunes, 2 = Martes, etc.
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (doctor_id) REFERENCES doctors(id),
        UNIQUE KEY week_schedule (doctor_id, week_number, year, day_of_week)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Tabla doctor_weekly_schedules creada correctamente\n";
    } else {
        echo "Error creando tabla doctor_weekly_schedules: " . $conn->error . "\n";
    }
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

setupDoctorSchedulesTables($conn);
$conn->close();

echo "Setup completado.\n";
?>
