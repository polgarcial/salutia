<?php
// Script para corregir automáticamente la estructura de la base de datos
header("Content-Type: application/json");

try {
    // Incluir la configuración de la base de datos
    require_once __DIR__ . '/../backend/config/database_class.php';
    
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar si existe la tabla patients
    $stmt = $db->query("SHOW TABLES LIKE 'patients'");
    $tableExists = $stmt->rowCount() > 0;
    
    $messages = [];
    
    if (!$tableExists) {
        $messages[] = "Creando tabla patients...";
        
        // Crear la tabla patients
        $sql = "CREATE TABLE patients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            first_name VARCHAR(100),
            last_name VARCHAR(100),
            phone VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $db->exec($sql);
        $messages[] = "Tabla patients creada correctamente.";
    } else {
        $messages[] = "La tabla patients existe.";
        
        // Verificar si existen las columnas first_name y last_name
        $stmt = $db->query("DESCRIBE patients");
        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $row['Field'];
        }
        
        if (!in_array('first_name', $columns)) {
            $messages[] = "Añadiendo columna first_name...";
            
            // Añadir la columna first_name
            $sql = "ALTER TABLE patients ADD COLUMN first_name VARCHAR(100) AFTER user_id";
            $db->exec($sql);
            $messages[] = "Columna first_name añadida correctamente.";
        } else {
            $messages[] = "La columna first_name existe.";
        }
        
        if (!in_array('last_name', $columns)) {
            $messages[] = "Añadiendo columna last_name...";
            
            // Añadir la columna last_name
            $sql = "ALTER TABLE patients ADD COLUMN last_name VARCHAR(100) AFTER first_name";
            $db->exec($sql);
            $messages[] = "Columna last_name añadida correctamente.";
        } else {
            $messages[] = "La columna last_name existe.";
        }
    }
    
    // Verificar la estructura final
    $stmt = $db->query("DESCRIBE patients");
    $finalColumns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $finalColumns[] = $row['Field'];
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'columns' => $finalColumns
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
