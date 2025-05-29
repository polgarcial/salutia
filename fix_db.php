<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Conexión directa a MySQL
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'salutia';

echo "Iniciando script de corrección de base de datos...\n";

try {
    // Conectar a MySQL
    $conn = new mysqli($host, $user, $password, $database);
    
    // Verificar conexión
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error . "\n");
    }
    
    echo "Conexión exitosa a la base de datos $database\n";
    
    // Verificar si la tabla appointments existe
    $result = $conn->query("SHOW TABLES LIKE 'appointments'");
    
    if ($result->num_rows > 0) {
        echo "La tabla appointments existe\n";
        
        // Verificar la estructura actual
        $result = $conn->query("DESCRIBE appointments");
        $columns = [];
        
        while ($row = $result->fetch_assoc()) {
            $columns[$row['Field']] = $row;
            echo "Columna encontrada: " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
        
        // Verificar y añadir columnas faltantes
        $required_columns = [
            'id' => "ALTER TABLE appointments ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY",
            'patient_id' => "ALTER TABLE appointments ADD COLUMN patient_id VARCHAR(50) NOT NULL",
            'patient_name' => "ALTER TABLE appointments ADD COLUMN patient_name VARCHAR(100) NOT NULL",
            'patient_email' => "ALTER TABLE appointments ADD COLUMN patient_email VARCHAR(100) NOT NULL",
            'doctor_id' => "ALTER TABLE appointments ADD COLUMN doctor_id VARCHAR(50) NOT NULL",
            'doctor_name' => "ALTER TABLE appointments ADD COLUMN doctor_name VARCHAR(100) NOT NULL",
            'reason' => "ALTER TABLE appointments ADD COLUMN reason TEXT NOT NULL",
            'date' => "ALTER TABLE appointments ADD COLUMN date VARCHAR(20) NOT NULL",
            'time' => "ALTER TABLE appointments ADD COLUMN time VARCHAR(10) NOT NULL",
            'status' => "ALTER TABLE appointments ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending'",
            'created_at' => "ALTER TABLE appointments ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
        ];
        
        foreach ($required_columns as $column => $query) {
            if (!isset($columns[$column])) {
                echo "Añadiendo columna faltante: $column\n";
                
                if ($conn->query($query)) {
                    echo "Columna $column añadida correctamente\n";
                } else {
                    echo "Error al añadir columna $column: " . $conn->error . "\n";
                }
            } else {
                echo "La columna $column ya existe\n";
            }
        }
    } else {
        echo "La tabla appointments no existe, creándola...\n";
        
        // Crear la tabla appointments
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
            echo "Tabla appointments creada correctamente\n";
        } else {
            echo "Error al crear la tabla: " . $conn->error . "\n";
        }
    }
    
    // Verificar la estructura final
    echo "\nEstructura final de la tabla appointments:\n";
    $result = $conn->query("DESCRIBE appointments");
    
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    // Cerrar conexión
    $conn->close();
    
    echo "\nProceso completado correctamente.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
