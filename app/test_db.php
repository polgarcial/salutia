<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Intentar conexión directa a MySQL
echo "<h2>Prueba de conexión a MySQL</h2>";

try {
    $mysqli = new mysqli('localhost', 'root', '', 'salutia', 3306);
    
    if ($mysqli->connect_error) {
        throw new Exception("Error de conexión: " . $mysqli->connect_error);
    }
    
    echo "<p style='color:green'>✓ Conexión exitosa a MySQL usando mysqli</p>";
    
    // Verificar si la base de datos existe
    $result = $mysqli->query("SHOW DATABASES LIKE 'salutia'");
    if ($result->num_rows > 0) {
        echo "<p style='color:green'>✓ Base de datos 'salutia' encontrada</p>";
    } else {
        echo "<p style='color:red'>✗ Base de datos 'salutia' no encontrada</p>";
    }
    
    $mysqli->close();
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error con mysqli: " . $e->getMessage() . "</p>";
}

// Intentar conexión con PDO
try {
    $pdo = new PDO('mysql:host=localhost;dbname=salutia;port=3306;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green'>✓ Conexión exitosa a MySQL usando PDO</p>";
    
    // Verificar la tabla users
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>✓ Tabla 'users' encontrada</p>";
        
        // Verificar la estructura de la tabla
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Columnas en la tabla users: " . implode(", ", $columns) . "</p>";
    } else {
        echo "<p style='color:red'>✗ Tabla 'users' no encontrada</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>✗ Error con PDO: " . $e->getMessage() . "</p>";
}

// Información del sistema
echo "<h2>Información del sistema</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Extensiones cargadas: " . implode(", ", get_loaded_extensions()) . "</p>";
?>
