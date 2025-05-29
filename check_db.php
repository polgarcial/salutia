<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Conexión directa a MySQL
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'salutia';

try {
    // Conectar a MySQL
    $conn = new mysqli($host, $user, $password, $database);
    
    // Verificar conexión
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    
    echo "Conexión exitosa a la base de datos $database<br>";
    
    // Verificar si la tabla appointments existe
    $result = $conn->query("SHOW TABLES LIKE 'appointments'");
    
    if ($result->num_rows > 0) {
        echo "La tabla appointments existe<br>";
        
        // Mostrar la estructura de la tabla
        $result = $conn->query("DESCRIBE appointments");
        
        echo "<h3>Estructura de la tabla appointments:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Predeterminado</th><th>Extra</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row["Field"] . "</td>";
            echo "<td>" . $row["Type"] . "</td>";
            echo "<td>" . $row["Null"] . "</td>";
            echo "<td>" . $row["Key"] . "</td>";
            echo "<td>" . $row["Default"] . "</td>";
            echo "<td>" . $row["Extra"] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Mostrar algunos registros
        $result = $conn->query("SELECT * FROM appointments LIMIT 5");
        
        if ($result->num_rows > 0) {
            echo "<h3>Primeros 5 registros de la tabla appointments:</h3>";
            echo "<table border='1'>";
            
            // Encabezados de la tabla
            echo "<tr>";
            $fields = $result->fetch_fields();
            foreach ($fields as $field) {
                echo "<th>" . $field->name . "</th>";
            }
            echo "</tr>";
            
            // Datos
            $result->data_seek(0);
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . $value . "</td>";
                }
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "No hay registros en la tabla appointments<br>";
        }
    } else {
        echo "La tabla appointments no existe<br>";
        
        // Crear la tabla
        $sql = "CREATE TABLE appointments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id VARCHAR(50) NOT NULL,
            patient_name VARCHAR(100) NOT NULL,
            patient_email VARCHAR(100) NOT NULL,
            doctor_id VARCHAR(50) NOT NULL,
            doctor_name VARCHAR(100) NOT NULL,
            reason TEXT NOT NULL,
            date VARCHAR(20) NOT NULL,
            time VARCHAR(10) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($sql) === TRUE) {
            echo "Tabla appointments creada correctamente<br>";
        } else {
            echo "Error al crear la tabla: " . $conn->error . "<br>";
        }
    }
    
    // Cerrar conexión
    $conn->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
