<?php
require_once __DIR__ . '/../config/database_class.php';

try {
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Datos del doctor
    $email = 'doctor@salutia.com';
    $newPassword = password_hash('123456', PASSWORD_DEFAULT);
    
    // Actualizar la contraseña
    $stmt = $db->prepare("UPDATE users SET password = :password WHERE email = :email");
    $stmt->bindParam(':password', $newPassword);
    $stmt->bindParam(':email', $email);
    
    if ($stmt->execute()) {
        echo "Contraseña actualizada correctamente\n";
    } else {
        echo "Error al actualizar la contraseña\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
