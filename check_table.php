<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Conexión a la base de datos
try {
    $host = 'localhost';
    $dbname = 'salutia';
    $username = 'root';
    $password = '';
    
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Conexión exitosa a la base de datos\n";
    
    // Obtener la estructura de la tabla appointments
    $query = "DESCRIBE appointments";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    echo "\nEstructura de la tabla appointments:\n";
    echo "Campo | Tipo | Nulo | Predeterminado | Extra\n";
    echo "------|------|------|----------------|------\n";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . 
             ($row['Default'] === null ? "NULL" : $row['Default']) . " | " . $row['Extra'] . "\n";
    }
    
    // Obtener algunos registros de ejemplo
    $query = "SELECT * FROM appointments LIMIT 3";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($rows) > 0) {
        echo "\nRegistros de ejemplo:\n";
        print_r($rows);
    } else {
        echo "\nNo hay registros en la tabla appointments\n";
    }
    
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage() . "\n";
}
?>
