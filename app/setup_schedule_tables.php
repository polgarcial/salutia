<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

try {
    $conn = getDbConnection();

    // Crear tabla de horarios semanales del doctor
    $sql = "CREATE TABLE IF NOT EXISTS doctor_weekly_schedules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        doctor_id INT NOT NULL,
        week_number INT NOT NULL,
        year INT NOT NULL,
        day_of_week INT NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (doctor_id) REFERENCES users(id),
        UNIQUE KEY unique_schedule (doctor_id, week_number, year, day_of_week)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    if ($conn->exec($sql) !== false) {
        echo "Tabla doctor_weekly_schedules creada o actualizada correctamente\n";
    }

    // Crear índices para mejorar el rendimiento
    $conn->exec("ALTER TABLE doctor_weekly_schedules ADD INDEX idx_doctor_schedule (doctor_id, week_number, year);");
    echo "Índices creados correctamente\n";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
