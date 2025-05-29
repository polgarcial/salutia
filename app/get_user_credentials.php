<?php
/**
 * Script para obtener las credenciales de un usuario específico
 */

// Incluir configuración de base de datos
require_once '../config/database.php';

// Obtener el nombre a buscar de la URL
$searchName = isset($_GET['name']) ? $_GET['name'] : 'María López';

try {
    // Obtener conexión a la base de datos
    $db = getDbConnection();
    
    // Buscar usuarios con el nombre especificado
    $sql = "SELECT * FROM users WHERE 
            (first_name LIKE ? AND last_name LIKE ?) OR 
            name LIKE ? OR 
            email LIKE ?";
    
    $nameParts = explode(' ', $searchName);
    $firstName = isset($nameParts[0]) ? $nameParts[0] : '';
    $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        '%' . $firstName . '%', 
        '%' . $lastName . '%', 
        '%' . $searchName . '%',
        '%' . strtolower(str_replace(' ', '.', $searchName)) . '%'
    ]);
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "No se encontraron usuarios con el nombre: $searchName\n\n";
        
        // Mostrar algunos usuarios disponibles
        echo "Algunos usuarios disponibles en el sistema:\n\n";
        
        $sampleUsers = $db->query("SELECT id, name, first_name, last_name, email, role FROM users LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($sampleUsers as $user) {
            $displayName = isset($user['name']) ? $user['name'] : ($user['first_name'] . ' ' . $user['last_name']);
            echo "ID: " . $user['id'] . "\n";
            echo "Nombre: " . $displayName . "\n";
            echo "Email: " . $user['email'] . "\n";
            echo "Rol: " . $user['role'] . "\n";
            echo "------------------------\n";
        }
    } else {
        echo "Usuarios encontrados para: $searchName\n\n";
        
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
    
} catch (PDOException $e) {
    echo "Error al buscar usuario: " . $e->getMessage();
}
?>
