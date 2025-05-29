<?php
// Configuración de la base de datos
require_once '../config/database.php';

try {
    // Conectar a la base de datos
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8");
    
    echo "<h1>Actualización de la tabla usuarios para Salutia</h1>";
    
    // Verificar si la tabla users existe
    $tableExistsQuery = "SHOW TABLES LIKE 'users'";
    $stmt = $conn->prepare($tableExistsQuery);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // Crear la tabla users si no existe
        $createUsersTable = "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('patient', 'doctor', 'admin') NOT NULL DEFAULT 'patient',
            specialty VARCHAR(100) NULL,
            profile_image VARCHAR(255) NULL,
            phone VARCHAR(20) NULL,
            address TEXT NULL,
            date_of_birth DATE NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->exec($createUsersTable);
        echo "<p style='color: green;'>✅ Tabla 'users' creada correctamente.</p>";
    } else {
        // Verificar si la columna specialty existe
        $columnExistsQuery = "SHOW COLUMNS FROM users LIKE 'specialty'";
        $stmt = $conn->prepare($columnExistsQuery);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            // Añadir la columna specialty si no existe
            $addSpecialtyColumn = "ALTER TABLE users ADD COLUMN specialty VARCHAR(100) NULL AFTER role";
            $conn->exec($addSpecialtyColumn);
            echo "<p style='color: green;'>✅ Columna 'specialty' añadida a la tabla 'users'.</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ La columna 'specialty' ya existe en la tabla 'users'.</p>";
        }
    }
    
    // Insertar algunos usuarios de ejemplo si no existen
    $checkUsersQuery = "SELECT COUNT(*) FROM users";
    $stmt = $conn->prepare($checkUsersQuery);
    $stmt->execute();
    $userCount = $stmt->fetchColumn();
    
    if ($userCount == 0) {
        // Insertar pacientes de ejemplo
        $insertPatients = "INSERT INTO users (first_name, last_name, email, password, role) VALUES 
            ('Juan', 'Pérez', 'juan@example.com', :password1, 'patient'),
            ('María', 'García', 'maria@example.com', :password2, 'patient'),
            ('Carlos', 'López', 'carlos@example.com', :password3, 'patient')";
        
        $stmt = $conn->prepare($insertPatients);
        $hashedPassword1 = password_hash('password123', PASSWORD_DEFAULT);
        $hashedPassword2 = password_hash('password123', PASSWORD_DEFAULT);
        $hashedPassword3 = password_hash('password123', PASSWORD_DEFAULT);
        $stmt->bindParam(':password1', $hashedPassword1);
        $stmt->bindParam(':password2', $hashedPassword2);
        $stmt->bindParam(':password3', $hashedPassword3);
        $stmt->execute();
        
        // Insertar médicos de ejemplo
        $insertDoctors = "INSERT INTO users (first_name, last_name, email, password, role, specialty) VALUES 
            ('Ana', 'Martínez', 'ana@example.com', :password4, 'doctor', 'Medicina General'),
            ('Pedro', 'Sánchez', 'pedro@example.com', :password5, 'doctor', 'Cardiología'),
            ('Laura', 'Rodríguez', 'laura@example.com', :password6, 'doctor', 'Pediatría'),
            ('Miguel', 'Fernández', 'miguel@example.com', :password7, 'doctor', 'Dermatología'),
            ('Sofía', 'Gómez', 'sofia@example.com', :password8, 'doctor', 'Ginecología')";
        
        $stmt = $conn->prepare($insertDoctors);
        $hashedPassword4 = password_hash('password123', PASSWORD_DEFAULT);
        $hashedPassword5 = password_hash('password123', PASSWORD_DEFAULT);
        $hashedPassword6 = password_hash('password123', PASSWORD_DEFAULT);
        $hashedPassword7 = password_hash('password123', PASSWORD_DEFAULT);
        $hashedPassword8 = password_hash('password123', PASSWORD_DEFAULT);
        $stmt->bindParam(':password4', $hashedPassword4);
        $stmt->bindParam(':password5', $hashedPassword5);
        $stmt->bindParam(':password6', $hashedPassword6);
        $stmt->bindParam(':password7', $hashedPassword7);
        $stmt->bindParam(':password8', $hashedPassword8);
        $stmt->execute();
        
        echo "<p style='color: green;'>✅ Usuarios de ejemplo insertados correctamente.</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ Ya existen usuarios en la base de datos.</p>";
    }
    
    echo "<p>La tabla 'users' ha sido actualizada correctamente.</p>";
    echo "<p><a href='../test_database.php'>Volver a la página de prueba de la base de datos</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
