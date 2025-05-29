<?php
// Script para corregir la estructura de la tabla patients
header("Content-Type: text/html; charset=UTF-8");

echo "<html><head><title>Corrección de Base de Datos - Salutia</title>
<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
    h1, h2 { color: #0066cc; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    .button {
        display: inline-block;
        background-color: #0066cc;
        color: white;
        padding: 10px 20px;
        text-decoration: none;
        border-radius: 5px;
        margin: 10px 0;
    }
</style>
</head><body>
<h1>Corrección de Estructura de Base de Datos - Salutia</h1>";

try {
    // Incluir la configuración de la base de datos
    require_once __DIR__ . '/../backend/config/database_class.php';
    
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p class='success'>✓ Conexión a la base de datos establecida correctamente.</p>";
    
    // Verificar si existe la tabla patients
    $stmt = $db->query("SHOW TABLES LIKE 'patients'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "<p class='warning'>⚠ La tabla patients no existe. Creando tabla...</p>";
        
        // Crear la tabla patients
        $sql = "CREATE TABLE patients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        
        $db->exec($sql);
        echo "<p class='success'>✓ Tabla patients creada correctamente.</p>";
    } else {
        echo "<p class='success'>✓ La tabla patients existe.</p>";
        
        // Verificar si existen las columnas first_name y last_name
        $stmt = $db->query("DESCRIBE patients");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $hasFirstName = in_array('first_name', $columns);
        $hasLastName = in_array('last_name', $columns);
        
        if (!$hasFirstName) {
            echo "<p class='warning'>⚠ La columna first_name no existe. Añadiendo columna...</p>";
            
            // Añadir la columna first_name
            $sql = "ALTER TABLE patients ADD COLUMN first_name VARCHAR(100) AFTER user_id";
            $db->exec($sql);
            echo "<p class='success'>✓ Columna first_name añadida correctamente.</p>";
        } else {
            echo "<p class='success'>✓ La columna first_name existe.</p>";
        }
        
        if (!$hasLastName) {
            echo "<p class='warning'>⚠ La columna last_name no existe. Añadiendo columna...</p>";
            
            // Añadir la columna last_name
            $sql = "ALTER TABLE patients ADD COLUMN last_name VARCHAR(100) AFTER first_name";
            $db->exec($sql);
            echo "<p class='success'>✓ Columna last_name añadida correctamente.</p>";
        } else {
            echo "<p class='success'>✓ La columna last_name existe.</p>";
        }
    }
    
    echo "<h2>Estructura actual de la tabla patients</h2>";
    
    $stmt = $db->query("DESCRIBE patients");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Predeterminado</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<p class='success'>✓ La estructura de la tabla patients ha sido corregida correctamente.</p>";
    echo "<p>Ahora el sistema de registro debería funcionar sin problemas.</p>";
    
    echo "<a href='index.html' class='button'>Volver a la página principal</a>";
    
} catch (PDOException $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
