<?php
// Incluir la configuración de la base de datos
require_once '../config/database_class.php';

// Función para crear las tablas si no existen
function setupTables() {
    try {
        // Conectar a la base de datos
        $database = new Database();
        $db = $database->getConnection();
        
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
                ('Dra. Laura Ginecóloga', 'Ginecología', 'laura.gineco@salutia.com', '567890123');
            ";
            $db->exec($insertDoctors);
            
            echo "Médicos de ejemplo insertados correctamente.<br>";
        }
        
        echo "Tablas creadas o verificadas correctamente.<br>";
        return true;
        
    } catch (PDOException $e) {
        echo "Error al configurar las tablas: " . $e->getMessage() . "<br>";
        return false;
    }
}

// Ejecutar la configuración
setupTables();

echo "Proceso de configuración completado.";
?>
