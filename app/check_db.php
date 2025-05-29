<?php
// Incluir archivos necesarios
require_once 'database_class.php';
require_once 'debug_log.php';

// Crear instancia de la base de datos
$database = new Database();
$db = $database->getConnection();

// Verificar la estructura de la tabla users
function checkUsersTable($db) {
    try {
        // Verificar si la tabla existe
        $stmt = $db->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() == 0) {
            return ["success" => false, "message" => "La tabla 'users' no existe"];
        }

        // Obtener la estructura de la tabla
        $stmt = $db->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $columnNames = [];
        foreach ($columns as $column) {
            $columnNames[] = $column['Field'];
        }
        
        // Verificar columnas necesarias
        $requiredColumns = ['id', 'email', 'password', 'name', 'role'];
        $missingColumns = [];
        
        foreach ($requiredColumns as $requiredColumn) {
            if (!in_array($requiredColumn, $columnNames)) {
                $missingColumns[] = $requiredColumn;
            }
        }
        
        if (!empty($missingColumns)) {
            return [
                "success" => false, 
                "message" => "Faltan columnas en la tabla 'users'", 
                "missing" => $missingColumns,
                "existing" => $columnNames
            ];
        }
        
        return ["success" => true, "message" => "La tabla 'users' tiene la estructura correcta", "columns" => $columnNames];
    } catch (PDOException $e) {
        return ["success" => false, "message" => "Error al verificar la tabla 'users': " . $e->getMessage()];
    }
}

// Verificar si hay usuarios en la base de datos
function checkUsers($db) {
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            "success" => true,
            "message" => "Hay " . $result['count'] . " usuarios en la base de datos"
        ];
    } catch (PDOException $e) {
        return ["success" => false, "message" => "Error al verificar usuarios: " . $e->getMessage()];
    }
}

// Ejecutar verificaciones
$usersTableCheck = checkUsersTable($db);
debug_log("Verificación de tabla users", $usersTableCheck);

$usersCheck = null;
if ($usersTableCheck['success']) {
    $usersCheck = checkUsers($db);
    debug_log("Verificación de usuarios", $usersCheck);
}

// Mostrar resultados
header('Content-Type: application/json');
echo json_encode([
    "usersTable" => $usersTableCheck,
    "users" => $usersCheck
]);
?>
