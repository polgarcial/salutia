<?php
require_once __DIR__ . '/../config/database_class.php';

try {
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Datos del doctor
    $email = 'doctor@salutia.com';
    $password = password_hash('password123', PASSWORD_DEFAULT);
    $name = 'Doctor Principal';
    $role = 'doctor';
    
    // Verificar si el email ya existe
    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "El usuario ya existe\n";
    } else {
        // Insertar el nuevo doctor
        $stmt = $db->prepare("INSERT INTO users (email, password, name, role) VALUES (:email, :password, :name, :role)");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':role', $role);
        
        if ($stmt->execute()) {
            $doctorId = $db->lastInsertId();
            echo "Doctor creado correctamente con ID: " . $doctorId . "\n";
        } else {
            echo "Error al crear el doctor\n";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
