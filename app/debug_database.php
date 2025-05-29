<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir archivos necesarios
require_once 'database_class.php';

// Habilitar CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Crear instancia de la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar la estructura de la base de datos
    $tables = [];
    
    // Obtener lista de tablas
    $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
    $tableNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tableNames as $tableName) {
        // Obtener estructura de la tabla
        $stmt = $db->query("PRAGMA table_info(" . $tableName . ")");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener número de registros
        $stmt = $db->query("SELECT COUNT(*) as count FROM " . $tableName);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Guardar información de la tabla
        $tables[$tableName] = [
            'columns' => $columns,
            'count' => $count
        ];
        
        // Si es la tabla doctor_specialties, obtener datos
        if ($tableName === 'doctor_specialties') {
            $stmt = $db->query("SELECT * FROM doctor_specialties LIMIT 10");
            $tables[$tableName]['sample_data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Si es la tabla doctor_availability, obtener datos
        if ($tableName === 'doctor_availability') {
            $stmt = $db->query("SELECT * FROM doctor_availability LIMIT 10");
            $tables[$tableName]['sample_data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    // Devolver información
    echo json_encode([
        'success' => true,
        'tables' => $tables
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
