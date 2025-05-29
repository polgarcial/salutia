<?php
// Script simple para corregir la estructura de la tabla patients
header("Content-Type: text/html; charset=UTF-8");

echo "<html><head><title>Corrección de Base de Datos - Salutia</title>
<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
    h1 { color: #0066cc; }
    .success { color: green; }
    .error { color: red; }
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
    
    // Ejecutar consultas SQL directamente
    $queries = [
        // Verificar si existe la tabla patients, si no, crearla
        "CREATE TABLE IF NOT EXISTS patients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            phone VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        // Intentar añadir la columna first_name
        "ALTER TABLE patients ADD COLUMN first_name VARCHAR(100) AFTER user_id",
        
        // Intentar añadir la columna last_name
        "ALTER TABLE patients ADD COLUMN last_name VARCHAR(100) AFTER first_name"
    ];
    
    foreach ($queries as $sql) {
        try {
            $db->exec($sql);
            echo "<p class='success'>✓ Consulta ejecutada correctamente: " . htmlspecialchars($sql) . "</p>";
        } catch (PDOException $e) {
            // Ignorar errores específicos como "columna ya existe"
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "<p>Información: La columna ya existe.</p>";
            } else {
                echo "<p class='error'>Error en consulta: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }
    
    // Mostrar la estructura actual de la tabla
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
    echo "<p class='error'>Error general: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
