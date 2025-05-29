<?php
/**
 * Script para configurar las tablas necesarias para el sistema de citas de Salutia
 * Este script crea las tablas si no existen y añade datos de ejemplo
 */

// Incluir la configuración de la base de datos
require_once '../config/database_class.php';

// Función para crear las tablas si no existen
function setupTables() {
    try {
        // Conectar a la base de datos
        $database = new Database();
        $db = $database->getConnection();
        
        echo "<h2>Configurando tablas para el sistema de citas de Salutia</h2>";
        
        // Crear tabla de médicos si no existe
        $createDoctorsTable = "
            CREATE TABLE IF NOT EXISTS doctors (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                specialty VARCHAR(50) NOT NULL,
                email VARCHAR(100) UNIQUE,
                phone VARCHAR(20),
                active BOOLEAN DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        $db->exec($createDoctorsTable);
        echo "<p>✅ Tabla 'doctors' creada o verificada correctamente.</p>";
        
        // Crear tabla de disponibilidad de médicos si no existe
        $createDoctorAvailabilityTable = "
            CREATE TABLE IF NOT EXISTS doctor_availability (
                id INT AUTO_INCREMENT PRIMARY KEY,
                doctor_id INT NOT NULL,
                date DATE NOT NULL,
                time_slot VARCHAR(10) NOT NULL,
                is_available BOOLEAN DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
                UNIQUE KEY unique_doctor_slot (doctor_id, date, time_slot)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        $db->exec($createDoctorAvailabilityTable);
        echo "<p>✅ Tabla 'doctor_availability' creada o verificada correctamente.</p>";
        
        // Crear tabla de citas si no existe
        $createAppointmentsTable = "
            CREATE TABLE IF NOT EXISTS appointments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                doctor_id INT NOT NULL,
                date DATE NOT NULL,
                time_slot VARCHAR(10) NOT NULL,
                notes TEXT,
                status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
                UNIQUE KEY unique_appointment (doctor_id, date, time_slot, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        $db->exec($createAppointmentsTable);
        echo "<p>✅ Tabla 'appointments' creada o verificada correctamente.</p>";
        
        // Verificar si hay médicos en la tabla, si no, insertar algunos de ejemplo
        $checkDoctors = $db->query("SELECT COUNT(*) FROM doctors");
        $doctorCount = $checkDoctors->fetchColumn();
        
        if ($doctorCount == 0) {
            // Insertar médicos de ejemplo
            $insertDoctors = "
                INSERT INTO doctors (name, specialty, email, phone) VALUES
                ('Dr. Joan Metge', 'Medicina General', 'joan.metge@salutia.com', '123456789'),
                ('Dra. Ana Cardióloga', 'Cardiología', 'ana.cardio@salutia.com', '234567890'),
                ('Dr. Pedro Dermatologo', 'Dermatología', 'pedro.derm@salutia.com', '345678901'),
                ('Dra. María Pediatra', 'Pediatría', 'maria.pediatra@salutia.com', '456789012'),
                ('Dra. Laura Ginecóloga', 'Ginecología', 'laura.gineco@salutia.com', '567890123'),
                ('Dr. Carlos Traumatólogo', 'Traumatología', 'carlos.trauma@salutia.com', '678901234'),
                ('Dra. Elena Oftalmóloga', 'Oftalmología', 'elena.oftalmo@salutia.com', '789012345'),
                ('Dr. Javier Neurólogo', 'Neurología', 'javier.neuro@salutia.com', '890123456');
            ";
            $db->exec($insertDoctors);
            
            echo "<p>✅ Médicos de ejemplo insertados correctamente.</p>";
        } else {
            echo "<p>ℹ️ Ya existen médicos en la base de datos. No se han insertado médicos de ejemplo.</p>";
        }
        
        echo "<h3>Configuración completada con éxito</h3>";
        echo "<p>El sistema de citas está listo para ser utilizado.</p>";
        echo "<p><a href='../../sistema_citas.html' class='btn btn-primary'>Ir al Sistema de Citas</a></p>";
        
        return true;
        
    } catch (PDOException $e) {
        echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
        echo "<h3>❌ Error al configurar las tablas</h3>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "</div>";
        return false;
    }
}

// Añadir estilos básicos para mejorar la presentación
echo "
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Configuración de Salutia</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        h2 {
            color: #0d6efd;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        p {
            margin-bottom: 10px;
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
            padding: 8px 20px;
            text-decoration: none;
            color: white;
            border-radius: 5px;
            display: inline-block;
            margin-top: 20px;
        }
    </style>
</head>
<body>
";

// Ejecutar la configuración
setupTables();

echo "
</body>
</html>
";
?>
