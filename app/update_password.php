<?php
/**
 * Script para actualizar la contraseña de un usuario
 */

// Incluir configuración de base de datos
require_once '../config/database.php';

// Parámetros
$userId = isset($_GET['user_id']) ? $_GET['user_id'] : 4; // Por defecto, María López
$newPassword = isset($_GET['password']) ? $_GET['password'] : '123456';

try {
    // Obtener conexión a la base de datos
    $db = getDbConnection();
    
    // Obtener información del usuario antes de actualizar
    $userQuery = $db->prepare("SELECT id, name, first_name, last_name, email, role FROM users WHERE id = ?");
    $userQuery->execute([$userId]);
    $user = $userQuery->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "Error: No se encontró ningún usuario con ID $userId";
        exit;
    }
    
    // Mostrar información del usuario
    $displayName = isset($user['name']) ? $user['name'] : ($user['first_name'] . ' ' . $user['last_name']);
    echo "Actualizando contraseña para:\n";
    echo "ID: " . $user['id'] . "\n";
    echo "Nombre: " . $displayName . "\n";
    echo "Email: " . $user['email'] . "\n";
    echo "Rol: " . $user['role'] . "\n\n";
    
    // Hashear la nueva contraseña
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Actualizar la contraseña
    $updateQuery = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    $result = $updateQuery->execute([$hashedPassword, $userId]);
    
    if ($result) {
        echo "Contraseña actualizada con éxito.\n";
        echo "Nueva contraseña: $newPassword\n";
    } else {
        echo "Error al actualizar la contraseña.\n";
    }
    
} catch (PDOException $e) {
    echo "Error en la base de datos: " . $e->getMessage();
}
?>
