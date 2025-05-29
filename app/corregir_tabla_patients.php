<?php
// Script para corregir la estructura de la tabla patients
header("Content-Type: text/html; charset=UTF-8");

echo "<html><head><title>Corrección de Base de Datos - Salutia</title>
<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
    h1, h2 { color: #0066cc; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
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
            first_name VARCHAR(100),
            last_name VARCHAR(100),
            phone VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $db->exec($sql);
        echo "<p class='success'>✓ Tabla patients creada correctamente.</p>";
    } else {
        echo "<p class='success'>✓ La tabla patients existe.</p>";
        
        // Obtener la estructura actual de la tabla
        $stmt = $db->query("DESCRIBE patients");
        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[$row['Field']] = $row;
        }
        
        // Verificar y añadir columnas necesarias
        $requiredColumns = [
            'user_id' => 'INT NOT NULL',
            'first_name' => 'VARCHAR(100)',
            'last_name' => 'VARCHAR(100)',
            'phone' => 'VARCHAR(20)',
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
        ];
        
        foreach ($requiredColumns as $column => $type) {
            if (!isset($columns[$column])) {
                echo "<p class='warning'>⚠ La columna $column no existe. Añadiendo columna...</p>";
                
                try {
                    $sql = "ALTER TABLE patients ADD COLUMN $column $type";
                    $db->exec($sql);
                    echo "<p class='success'>✓ Columna $column añadida correctamente.</p>";
                } catch (PDOException $e) {
                    echo "<p class='error'>✗ Error al añadir columna $column: " . $e->getMessage() . "</p>";
                }
            } else {
                echo "<p class='success'>✓ La columna $column existe.</p>";
            }
        }
    }
    
    // Mostrar la estructura actual de la tabla
    echo "<h2>Estructura actual de la tabla patients</h2>";
    
    $stmt = $db->query("DESCRIBE patients");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
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
    
    // Verificar si hay registros en la tabla
    $stmt = $db->query("SELECT COUNT(*) FROM patients");
    $count = $stmt->fetchColumn();
    
    echo "<p>La tabla patients contiene $count registros.</p>";
    
    echo "<p class='success'>✓ La estructura de la tabla patients ha sido verificada y corregida.</p>";
    echo "<p>Ahora el sistema de registro debería funcionar sin problemas.</p>";
    
    echo "<div style='margin-top: 20px;'>";
    echo "<a href='index.html' class='button'>Volver a la página principal</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
