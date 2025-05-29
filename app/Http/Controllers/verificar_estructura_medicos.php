<?php
// Script para verificar y corregir la estructura de las tablas relacionadas con médicos
header("Content-Type: text/html; charset=UTF-8");

echo "<html><head><title>Verificar Estructura Médicos - Salutia</title>
<style>
    body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
    h1, h2, h3 { color: #0066cc; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .button {
        display: inline-block;
        background-color: #0066cc;
        color: white;
        padding: 10px 20px;
        text-decoration: none;
        border-radius: 5px;
        margin: 10px 0;
    }
    pre {
        background-color: #f5f5f5;
        padding: 10px;
        border-radius: 5px;
        overflow-x: auto;
    }
    .code {
        font-family: monospace;
        background-color: #f5f5f5;
        padding: 2px 4px;
        border-radius: 3px;
    }
</style>
</head><body>
<h1>Verificar Estructura de Tablas para Médicos - Salutia</h1>";

try {
    // Incluir la configuración de la base de datos
    require_once __DIR__ . '/../backend/config/database_class.php';
    
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p class='success'>✓ Conexión a la base de datos establecida correctamente.</p>";
    
    // Verificar la tabla doctors
    $stmt = $db->query("SHOW TABLES LIKE 'doctors'");
    $doctorsTableExists = $stmt->rowCount() > 0;
    
    if ($doctorsTableExists) {
        echo "<h2>Estructura de la tabla 'doctors'</h2>";
        
        // Obtener la estructura actual
        $stmt = $db->query("DESCRIBE doctors");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Predeterminado</th><th>Extra</th></tr>";
        
        $columnNames = [];
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . ($column['Default'] === null ? '<span class="warning">NULL</span>' : $column['Default']) . "</td>";
            echo "<td>" . $column['Extra'] . "</td>";
            echo "</tr>";
            
            $columnNames[$column['Field']] = true;
        }
        
        echo "</table>";
        
        // Verificar si faltan columnas necesarias
        $requiredColumns = [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'name' => 'VARCHAR(100) NOT NULL',
            'specialty' => 'VARCHAR(100)',
            'email' => 'VARCHAR(100)',
            'phone' => 'VARCHAR(20)',
            'active' => 'BOOLEAN DEFAULT 1',
            'first_name' => 'VARCHAR(50)',
            'last_name' => 'VARCHAR(50)'
        ];
        
        $missingColumns = [];
        foreach ($requiredColumns as $column => $type) {
            if (!isset($columnNames[$column])) {
                $missingColumns[$column] = $type;
            }
        }
        
        if (!empty($missingColumns)) {
            echo "<h3>Columnas faltantes en la tabla 'doctors'</h3>";
            echo "<ul>";
            foreach ($missingColumns as $column => $type) {
                echo "<li><span class='code'>{$column}</span> ({$type})</li>";
            }
            echo "</ul>";
            
            echo "<h3>SQL para añadir las columnas faltantes:</h3>";
            echo "<pre>";
            foreach ($missingColumns as $column => $type) {
                echo "ALTER TABLE `doctors` ADD COLUMN `{$column}` {$type};\n";
            }
            echo "</pre>";
            
            // Formulario para añadir las columnas
            echo "<form method='post'>";
            echo "<input type='hidden' name='add_doctor_columns' value='1'>";
            echo "<button type='submit' class='button'>Añadir columnas faltantes a la tabla 'doctors'</button>";
            echo "</form>";
        } else {
            echo "<p class='success'>✓ La tabla 'doctors' tiene todas las columnas necesarias.</p>";
        }
    } else {
        echo "<p class='error'>✗ La tabla 'doctors' no existe en la base de datos.</p>";
        
        // Crear la tabla doctors
        echo "<h3>SQL para crear la tabla 'doctors':</h3>";
        echo "<pre>";
        echo "CREATE TABLE `doctors` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `specialty` VARCHAR(100),
  `email` VARCHAR(100),
  `phone` VARCHAR(20),
  `active` BOOLEAN DEFAULT 1,
  `first_name` VARCHAR(50),
  `last_name` VARCHAR(50),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);</pre>";
        
        // Formulario para crear la tabla
        echo "<form method='post'>";
        echo "<input type='hidden' name='create_doctors_table' value='1'>";
        echo "<button type='submit' class='button'>Crear tabla 'doctors'</button>";
        echo "</form>";
    }
    
    // Verificar la tabla doctor_availability
    $stmt = $db->query("SHOW TABLES LIKE 'doctor_availability'");
    $availabilityTableExists = $stmt->rowCount() > 0;
    
    if ($availabilityTableExists) {
        echo "<h2>Estructura de la tabla 'doctor_availability'</h2>";
        
        // Obtener la estructura actual
        $stmt = $db->query("DESCRIBE doctor_availability");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Predeterminado</th><th>Extra</th></tr>";
        
        $columnNames = [];
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . ($column['Default'] === null ? '<span class="warning">NULL</span>' : $column['Default']) . "</td>";
            echo "<td>" . $column['Extra'] . "</td>";
            echo "</tr>";
            
            $columnNames[$column['Field']] = true;
        }
        
        echo "</table>";
        
        // Verificar si faltan columnas necesarias
        $requiredColumns = [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'doctor_id' => 'INT NOT NULL',
            'date' => 'DATE NOT NULL',
            'time_slot' => 'TIME NOT NULL',
            'is_available' => 'BOOLEAN DEFAULT 1',
            'notes' => 'TEXT'
        ];
        
        $missingColumns = [];
        foreach ($requiredColumns as $column => $type) {
            if (!isset($columnNames[$column])) {
                $missingColumns[$column] = $type;
            }
        }
        
        if (!empty($missingColumns)) {
            echo "<h3>Columnas faltantes en la tabla 'doctor_availability'</h3>";
            echo "<ul>";
            foreach ($missingColumns as $column => $type) {
                echo "<li><span class='code'>{$column}</span> ({$type})</li>";
            }
            echo "</ul>";
            
            echo "<h3>SQL para añadir las columnas faltantes:</h3>";
            echo "<pre>";
            foreach ($missingColumns as $column => $type) {
                echo "ALTER TABLE `doctor_availability` ADD COLUMN `{$column}` {$type};\n";
            }
            echo "</pre>";
            
            // Formulario para añadir las columnas
            echo "<form method='post'>";
            echo "<input type='hidden' name='add_availability_columns' value='1'>";
            echo "<button type='submit' class='button'>Añadir columnas faltantes a la tabla 'doctor_availability'</button>";
            echo "</form>";
        } else {
            echo "<p class='success'>✓ La tabla 'doctor_availability' tiene todas las columnas necesarias.</p>";
        }
    } else {
        echo "<p class='error'>✗ La tabla 'doctor_availability' no existe en la base de datos.</p>";
        
        // Crear la tabla doctor_availability
        echo "<h3>SQL para crear la tabla 'doctor_availability':</h3>";
        echo "<pre>";
        echo "CREATE TABLE `doctor_availability` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `doctor_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `time_slot` TIME NOT NULL,
  `is_available` BOOLEAN DEFAULT 1,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `doctor_date_time` (`doctor_id`, `date`, `time_slot`)
);</pre>";
        
        // Formulario para crear la tabla
        echo "<form method='post'>";
        echo "<input type='hidden' name='create_availability_table' value='1'>";
        echo "<button type='submit' class='button'>Crear tabla 'doctor_availability'</button>";
        echo "</form>";
    }
    
    // Procesar las solicitudes POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Crear tabla doctors
        if (isset($_POST['create_doctors_table'])) {
            try {
                $sql = "CREATE TABLE `doctors` (
                  `id` INT AUTO_INCREMENT PRIMARY KEY,
                  `name` VARCHAR(100) NOT NULL,
                  `specialty` VARCHAR(100),
                  `email` VARCHAR(100),
                  `phone` VARCHAR(20),
                  `active` BOOLEAN DEFAULT 1,
                  `first_name` VARCHAR(50),
                  `last_name` VARCHAR(50),
                  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                
                $db->exec($sql);
                echo "<p class='success'>✓ Tabla 'doctors' creada correctamente. <a href=''>Actualizar página</a></p>";
            } catch (PDOException $e) {
                echo "<p class='error'>✗ Error al crear la tabla 'doctors': " . $e->getMessage() . "</p>";
            }
        }
        
        // Añadir columnas a la tabla doctors
        if (isset($_POST['add_doctor_columns'])) {
            try {
                foreach ($missingColumns as $column => $type) {
                    $sql = "ALTER TABLE `doctors` ADD COLUMN `{$column}` {$type}";
                    $db->exec($sql);
                }
                echo "<p class='success'>✓ Columnas añadidas a la tabla 'doctors'. <a href=''>Actualizar página</a></p>";
            } catch (PDOException $e) {
                echo "<p class='error'>✗ Error al añadir columnas a la tabla 'doctors': " . $e->getMessage() . "</p>";
            }
        }
        
        // Crear tabla doctor_availability
        if (isset($_POST['create_availability_table'])) {
            try {
                $sql = "CREATE TABLE `doctor_availability` (
                  `id` INT AUTO_INCREMENT PRIMARY KEY,
                  `doctor_id` INT NOT NULL,
                  `date` DATE NOT NULL,
                  `time_slot` TIME NOT NULL,
                  `is_available` BOOLEAN DEFAULT 1,
                  `notes` TEXT,
                  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                  UNIQUE KEY `doctor_date_time` (`doctor_id`, `date`, `time_slot`)
                )";
                
                $db->exec($sql);
                echo "<p class='success'>✓ Tabla 'doctor_availability' creada correctamente. <a href=''>Actualizar página</a></p>";
            } catch (PDOException $e) {
                echo "<p class='error'>✗ Error al crear la tabla 'doctor_availability': " . $e->getMessage() . "</p>";
            }
        }
        
        // Añadir columnas a la tabla doctor_availability
        if (isset($_POST['add_availability_columns'])) {
            try {
                foreach ($missingColumns as $column => $type) {
                    $sql = "ALTER TABLE `doctor_availability` ADD COLUMN `{$column}` {$type}";
                    $db->exec($sql);
                }
                echo "<p class='success'>✓ Columnas añadidas a la tabla 'doctor_availability'. <a href=''>Actualizar página</a></p>";
            } catch (PDOException $e) {
                echo "<p class='error'>✗ Error al añadir columnas a la tabla 'doctor_availability': " . $e->getMessage() . "</p>";
            }
        }
    }
    
    // Verificar el API para cargar disponibilidad
    echo "<h2>Verificar API para cargar disponibilidad</h2>";
    
    // Verificar si existe el archivo
    $apiFile = 'backend/api/doctor_availability.php';
    if (file_exists($apiFile)) {
        echo "<p class='success'>✓ El archivo API '{$apiFile}' existe.</p>";
        
        // Mostrar el contenido del archivo
        echo "<h3>Contenido del archivo:</h3>";
        echo "<pre>" . htmlspecialchars(file_get_contents($apiFile)) . "</pre>";
    } else {
        echo "<p class='error'>✗ El archivo API '{$apiFile}' no existe.</p>";
        
        // Crear el archivo API
        echo "<h3>Crear archivo API para disponibilidad de médicos:</h3>";
        
        $apiContent = '<?php
// API para gestionar la disponibilidad de los médicos
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Incluir la configuración de la base de datos
require_once "../config/database_class.php";

// Crear conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Obtener el método HTTP
$method = $_SERVER["REQUEST_METHOD"];

// Procesar según el método
switch ($method) {
    case "GET":
        // Obtener disponibilidad
        getAvailability($db);
        break;
    case "POST":
        // Crear o actualizar disponibilidad
        updateAvailability($db);
        break;
    default:
        // Método no permitido
        http_response_code(405);
        echo json_encode(["success" => false, "error" => "Método no permitido"]);
        break;
}

// Función para obtener la disponibilidad de un médico
function getAvailability($db) {
    // Obtener parámetros
    $doctor_id = isset($_GET["doctor_id"]) ? $_GET["doctor_id"] : null;
    $start_date = isset($_GET["start_date"]) ? $_GET["start_date"] : null;
    $end_date = isset($_GET["end_date"]) ? $_GET["end_date"] : null;
    
    // Validar parámetros
    if (!$doctor_id) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Se requiere el ID del médico"]);
        return;
    }
    
    try {
        // Construir la consulta SQL
        $sql = "SELECT da.*, d.name AS doctor_name 
                FROM doctor_availability da
                JOIN doctors d ON da.doctor_id = d.id
                WHERE da.doctor_id = :doctor_id";
        
        $params = [":doctor_id" => $doctor_id];
        
        // Añadir filtro de fechas si se proporcionan
        if ($start_date) {
            $sql .= " AND da.date >= :start_date";
            $params[":start_date"] = $start_date;
        }
        
        if ($end_date) {
            $sql .= " AND da.date <= :end_date";
            $params[":end_date"] = $end_date;
        }
        
        // Ordenar por fecha y hora
        $sql .= " ORDER BY da.date, da.time_slot";
        
        // Preparar y ejecutar la consulta
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        // Obtener resultados
        $availability = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Devolver respuesta
        echo json_encode([
            "success" => true,
            "availability" => $availability
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "error" => "Error al obtener disponibilidad: " . $e->getMessage()
        ]);
    }
}

// Función para actualizar la disponibilidad de un médico
function updateAvailability($db) {
    // Obtener datos del cuerpo de la solicitud
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Validar datos
    if (!isset($data["doctor_id"]) || !isset($data["slots"])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Se requieren doctor_id y slots"]);
        return;
    }
    
    $doctor_id = $data["doctor_id"];
    $slots = $data["slots"];
    
    try {
        // Iniciar transacción
        $db->beginTransaction();
        
        // Procesar cada franja horaria
        foreach ($slots as $slot) {
            if (!isset($slot["date"]) || !isset($slot["time_slot"]) || !isset($slot["is_available"])) {
                continue; // Saltar slots incompletos
            }
            
            // Verificar si ya existe esta franja
            $stmt = $db->prepare("SELECT id FROM doctor_availability 
                                  WHERE doctor_id = :doctor_id 
                                  AND date = :date 
                                  AND time_slot = :time_slot");
            $stmt->bindValue(":doctor_id", $doctor_id);
            $stmt->bindValue(":date", $slot["date"]);
            $stmt->bindValue(":time_slot", $slot["time_slot"]);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // Actualizar franja existente
                $id = $stmt->fetch(PDO::FETCH_ASSOC)["id"];
                
                $stmt = $db->prepare("UPDATE doctor_availability 
                                      SET is_available = :is_available, 
                                          notes = :notes
                                      WHERE id = :id");
                $stmt->bindValue(":is_available", $slot["is_available"]);
                $stmt->bindValue(":notes", $slot["notes"] ?? null);
                $stmt->bindValue(":id", $id);
                $stmt->execute();
            } else {
                // Crear nueva franja
                $stmt = $db->prepare("INSERT INTO doctor_availability 
                                      (doctor_id, date, time_slot, is_available, notes) 
                                      VALUES (:doctor_id, :date, :time_slot, :is_available, :notes)");
                $stmt->bindValue(":doctor_id", $doctor_id);
                $stmt->bindValue(":date", $slot["date"]);
                $stmt->bindValue(":time_slot", $slot["time_slot"]);
                $stmt->bindValue(":is_available", $slot["is_available"]);
                $stmt->bindValue(":notes", $slot["notes"] ?? null);
                $stmt->execute();
            }
        }
        
        // Confirmar transacción
        $db->commit();
        
        // Devolver respuesta exitosa
        echo json_encode([
            "success" => true,
            "message" => "Disponibilidad actualizada correctamente"
        ]);
        
    } catch (PDOException $e) {
        // Revertir transacción en caso de error
        $db->rollBack();
        
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "error" => "Error al actualizar disponibilidad: " . $e->getMessage()
        ]);
    }
}
';
        
        echo "<pre>" . htmlspecialchars($apiContent) . "</pre>";
        
        // Formulario para crear el archivo API
        echo "<form method='post'>";
        echo "<input type='hidden' name='create_api_file' value='1'>";
        echo "<button type='submit' class='button'>Crear archivo API para disponibilidad de médicos</button>";
        echo "</form>";
        
        // Procesar la creación del archivo
        if (isset($_POST['create_api_file'])) {
            try {
                // Asegurarse de que el directorio existe
                $dir = dirname($apiFile);
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                
                // Escribir el archivo
                if (file_put_contents($apiFile, $apiContent)) {
                    echo "<p class='success'>✓ Archivo API creado correctamente. <a href=''>Actualizar página</a></p>";
                } else {
                    echo "<p class='error'>✗ Error al crear el archivo API.</p>";
                }
            } catch (Exception $e) {
                echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    echo "<div style='margin-top: 20px;'>";
    echo "<a href='doctor_dashboard.html' class='button'>Volver al Dashboard de Médicos</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p class='error'>Error de base de datos: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
