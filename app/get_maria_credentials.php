<?php
/**
 * Script para obtener las credenciales de María García
 */

// Incluir configuración de base de datos
require_once '../config/database.php';

try {
    // Obtener conexión a la base de datos
    $db = getDbConnection();
    
    // Buscar usuarios con apellido García
    $sql = "SELECT * FROM users WHERE 
            last_name LIKE ? OR 
            name LIKE ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute(['%García%', '%García%']);
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        // Si no se encuentra, buscar a todos los pacientes
        echo "No se encontraron usuarios con apellido García. Buscando pacientes:\n\n";
        
        $patients = $db->query("SELECT id, name, first_name, last_name, email, role FROM users WHERE role = 'patient' LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($patients as $p) {
            $displayName = isset($p['name']) ? $p['name'] : ($p['first_name'] . ' ' . $p['last_name']);
            echo "ID: " . $p['id'] . "\n";
            echo "Nombre: " . $displayName . "\n";
            echo "Email: " . $p['email'] . "\n";
            echo "Rol: " . $p['role'] . "\n";
            echo "Contraseña: password123 (contraseña por defecto)\n";
            echo "------------------------\n";
        }
    } else {
        // Mostrar información de usuarios con apellido García
        echo "Usuarios con apellido García encontrados:\n\n";
        
        foreach ($users as $user) {
            $displayName = isset($user['name']) ? $user['name'] : ($user['first_name'] . ' ' . $user['last_name']);
            echo "ID: " . $user['id'] . "\n";
            echo "Nombre: " . $displayName . "\n";
            echo "Email: " . $user['email'] . "\n";
            echo "Rol: " . $user['role'] . "\n";
            echo "Contraseña: password123 (contraseña por defecto para todos los usuarios de prueba)\n";
            echo "------------------------\n";
        }
    }
    
    // Si no se encontró específicamente a María García, buscar por nombre María
    $mariaFound = false;
    foreach ($users as $user) {
        $firstName = isset($user['first_name']) ? $user['first_name'] : (isset($user['name']) ? explode(' ', $user['name'])[0] : '');
        if (stripos($firstName, 'María') !== false || stripos($firstName, 'Maria') !== false) {
            $mariaFound = true;
            break;
        }
    }
    
    if (!$mariaFound) {
        echo "\nNo se encontró específicamente a María García. Buscando usuarios con nombre María:\n\n";
        
        $sql = "SELECT * FROM users WHERE 
                first_name LIKE ? OR 
                name LIKE ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(['%María%', '%Maria%']);
        
        $marias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($marias as $maria) {
            $displayName = isset($maria['name']) ? $maria['name'] : ($maria['first_name'] . ' ' . $maria['last_name']);
            echo "ID: " . $maria['id'] . "\n";
            echo "Nombre: " . $displayName . "\n";
            echo "Email: " . $maria['email'] . "\n";
            echo "Rol: " . $maria['role'] . "\n";
            echo "Contraseña: password123 (contraseña por defecto)\n";
            echo "------------------------\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Error al buscar usuario: " . $e->getMessage();
}
?>
