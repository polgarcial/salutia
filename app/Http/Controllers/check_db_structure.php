<?php
// Script para verificar la estructura de la base de datos
header("Content-Type: text/html; charset=UTF-8");

echo "<html><head><title>Verificación de Base de Datos - Salutia</title>
<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
    h1, h2 { color: #0066cc; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    .success { color: green; }
    .error { color: red; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>
</head><body>
<h1>Verificación de Estructura de Base de Datos - Salutia</h1>";

try {
    // Incluir la configuración de la base de datos
    require_once __DIR__ . '/../backend/config/database_class.php';
    
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p class='success'>✓ Conexión a la base de datos establecida correctamente.</p>";
    
    // Verificar tablas existentes
    echo "<h2>Tablas en la base de datos</h2>";
    
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Verificar estructura de la tabla appointments
    echo "<h2>Estructura de la tabla appointments</h2>";
    
    if (in_array('appointments', $tables)) {
        $stmt = $db->query("DESCRIBE appointments");
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
        
        // Verificar si existe la columna user_id
        $hasUserId = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'user_id') {
                $hasUserId = true;
                break;
            }
        }
        
        if ($hasUserId) {
            echo "<p class='success'>✓ La tabla appointments tiene la columna user_id.</p>";
        } else {
            echo "<p class='error'>✗ La tabla appointments NO tiene la columna user_id.</p>";
            
            // Verificar si existe la columna patient_id
            $hasPatientId = false;
            foreach ($columns as $column) {
                if ($column['Field'] === 'patient_id') {
                    $hasPatientId = true;
                    break;
                }
            }
            
            if ($hasPatientId) {
                echo "<p class='success'>✓ La tabla appointments tiene la columna patient_id en lugar de user_id.</p>";
            }
        }
    } else {
        echo "<p class='error'>✗ La tabla appointments no existe en la base de datos.</p>";
    }
    
    // Verificar estructura de la tabla patients
    echo "<h2>Estructura de la tabla patients</h2>";
    
    if (in_array('patients', $tables)) {
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
        
        // Verificar si existe la columna first_name
        $hasFirstName = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'first_name') {
                $hasFirstName = true;
                break;
            }
        }
        
        if ($hasFirstName) {
            echo "<p class='success'>✓ La tabla patients tiene la columna first_name.</p>";
        } else {
            echo "<p class='error'>✗ La tabla patients NO tiene la columna first_name.</p>";
            echo "<p>Es necesario añadir esta columna para que el registro funcione correctamente.</p>";
            
            // Ofrecer solución para añadir la columna
            echo "<h3>Solución</h3>";
            echo "<pre>ALTER TABLE patients ADD COLUMN first_name VARCHAR(100) AFTER user_id;</pre>";
            echo "<pre>ALTER TABLE patients ADD COLUMN last_name VARCHAR(100) AFTER first_name;</pre>";
        }
    } else {
        echo "<p class='error'>✗ La tabla patients no existe en la base de datos.</p>";
        
        // Ofrecer solución para crear la tabla
        echo "<h3>Solución</h3>";
        echo "<pre>CREATE TABLE patients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  phone VARCHAR(20),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);</pre>";
    }
    
    // Verificar datos de ejemplo en la tabla appointments
    echo "<h2>Datos de ejemplo en la tabla appointments</h2>";
    
    if (in_array('appointments', $tables)) {
        $stmt = $db->query("SELECT * FROM appointments LIMIT 5");
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($appointments) > 0) {
            echo "<pre>";
            print_r($appointments);
            echo "</pre>";
        } else {
            echo "<p>No hay datos en la tabla appointments.</p>";
        }
    }
    
    echo "<h2>Acciones disponibles</h2>";
    echo "<p><a href='citas_db.html'>Volver al sistema de citas</a></p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>Error de conexión a la base de datos: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
