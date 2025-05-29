<?php
// Script de diagnóstico para la base de datos y API de Salutia
header('Content-Type: text/html; charset=utf-8');

// Habilitar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnóstico de la base de datos Salutia</h1>";

// Conectar a la base de datos
require_once __DIR__ . '/../backend/config/database_class.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color:green'>✓ Conexión a la base de datos establecida correctamente.</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error al conectar a la base de datos: " . $e->getMessage() . "</p>";
    exit;
}

// Verificar tabla appointments
echo "<h2>Verificando tabla 'appointments'</h2>";
try {
    // Comprobar si la tabla existe
    $stmt = $db->query("SHOW TABLES LIKE 'appointments'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>✓ La tabla 'appointments' existe.</p>";
        
        // Comprobar estructura de la tabla
        $stmt = $db->query("DESCRIBE appointments");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<h3>Columnas en la tabla 'appointments':</h3>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>$column</li>";
        }
        echo "</ul>";
        
        // Verificar columnas específicas
        $requiredColumns = ['id', 'doctor_id', 'patient_id'];
        $dateColumns = ['appointment_date', 'appointment_time', 'date', 'time'];
        
        $missingColumns = [];
        foreach ($requiredColumns as $column) {
            if (!in_array($column, $columns)) {
                $missingColumns[] = $column;
            }
        }
        
        $foundDateColumns = [];
        foreach ($dateColumns as $column) {
            if (in_array($column, $columns)) {
                $foundDateColumns[] = $column;
            }
        }
        
        if (count($missingColumns) > 0) {
            echo "<p style='color:red'>✗ Faltan columnas requeridas: " . implode(", ", $missingColumns) . "</p>";
        } else {
            echo "<p style='color:green'>✓ Todas las columnas requeridas están presentes.</p>";
        }
        
        if (count($foundDateColumns) > 0) {
            echo "<p style='color:green'>✓ Columnas de fecha encontradas: " . implode(", ", $foundDateColumns) . "</p>";
        } else {
            echo "<p style='color:red'>✗ No se encontraron columnas de fecha (appointment_date/time o date/time).</p>";
        }
        
        // Verificar datos en la tabla
        $stmt = $db->query("SELECT COUNT(*) as count FROM appointments");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>Total de citas en la base de datos: $count</p>";
        
        if ($count > 0) {
            echo "<h3>Muestra de datos en 'appointments':</h3>";
            $stmt = $db->query("SELECT * FROM appointments LIMIT 3");
            $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' cellpadding='5'>";
            // Encabezados
            echo "<tr>";
            foreach (array_keys($appointments[0]) as $header) {
                echo "<th>$header</th>";
            }
            echo "</tr>";
            
            // Datos
            foreach ($appointments as $appointment) {
                echo "<tr>";
                foreach ($appointment as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p style='color:red'>✗ La tabla 'appointments' no existe.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>✗ Error al verificar la tabla 'appointments': " . $e->getMessage() . "</p>";
}

// Verificar tabla users (para doctores)
echo "<h2>Verificando tabla 'users'</h2>";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>✓ La tabla 'users' existe.</p>";
        
        // Comprobar estructura
        $stmt = $db->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<h3>Columnas en la tabla 'users':</h3>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>$column</li>";
        }
        echo "</ul>";
        
        // Verificar usuarios doctores
        $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'doctor'");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>Total de médicos en la base de datos: $count</p>";
        
        if ($count > 0) {
            echo "<h3>Médicos registrados:</h3>";
            $stmt = $db->query("SELECT id, email, role FROM users WHERE role = 'doctor' LIMIT 5");
            $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' cellpadding='5'>";
            // Encabezados
            echo "<tr><th>ID</th><th>Email</th><th>Role</th></tr>";
            
            // Datos
            foreach ($doctors as $doctor) {
                echo "<tr>";
                echo "<td>" . $doctor['id'] . "</td>";
                echo "<td>" . $doctor['email'] . "</td>";
                echo "<td>" . $doctor['role'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p style='color:red'>✗ La tabla 'users' no existe.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>✗ Error al verificar la tabla 'users': " . $e->getMessage() . "</p>";
}

// Verificar API endpoints
echo "<h2>Verificando API endpoints</h2>";

$apiEndpoints = [
    'get_doctor_stats.php',
    'get_doctor_appointments.php'
];

foreach ($apiEndpoints as $endpoint) {
    $filePath = 'backend/api/' . $endpoint;
    if (file_exists($filePath)) {
        echo "<p style='color:green'>✓ El archivo '$endpoint' existe.</p>";
        
        // Mostrar primeras líneas del archivo para verificar
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $firstLines = array_slice($lines, 0, 10);
        
        echo "<details>";
        echo "<summary>Primeras líneas de $endpoint</summary>";
        echo "<pre>";
        foreach ($firstLines as $line) {
            echo htmlspecialchars($line) . "\n";
        }
        echo "</pre>";
        echo "</details>";
    } else {
        echo "<p style='color:red'>✗ El archivo '$endpoint' no existe.</p>";
    }
}

// Script para corregir problemas comunes
echo "<h2>Script de corrección</h2>";

echo "<p>Si la tabla 'appointments' tiene problemas con los nombres de columnas, puedes ejecutar el siguiente script SQL:</p>";

echo "<pre>
-- Verificar si existen las columnas appointment_date y appointment_time
SET @appointment_date_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'appointments' AND COLUMN_NAME = 'appointment_date');

SET @appointment_time_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'appointments' AND COLUMN_NAME = 'appointment_time');

SET @date_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'appointments' AND COLUMN_NAME = 'date');

SET @time_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'appointments' AND COLUMN_NAME = 'time');

-- Si date existe pero appointment_date no, crear appointment_date
SET @sql = IF(@date_exists > 0 AND @appointment_date_exists = 0, 
    'ALTER TABLE appointments ADD COLUMN appointment_date DATE, ALGORITHM=INPLACE, LOCK=NONE', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Si time existe pero appointment_time no, crear appointment_time
SET @sql = IF(@time_exists > 0 AND @appointment_time_exists = 0, 
    'ALTER TABLE appointments ADD COLUMN appointment_time TIME, ALGORITHM=INPLACE, LOCK=NONE', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Copiar datos de date a appointment_date si ambos existen
SET @sql = IF(@date_exists > 0 AND @appointment_date_exists > 0, 
    'UPDATE appointments SET appointment_date = date WHERE appointment_date IS NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Copiar datos de time a appointment_time si ambos existen
SET @sql = IF(@time_exists > 0 AND @appointment_time_exists > 0, 
    'UPDATE appointments SET appointment_time = time WHERE appointment_time IS NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
</pre>";

echo "<p>También puedes ejecutar este script PHP para corregir problemas:</p>";

echo "<pre>
&lt;?php
// Script para corregir problemas en la base de datos
require_once __DIR__ . '/../backend/config/database_class.php';

try {
    \$database = new Database();
    \$db = \$database->getConnection();
    
    // Verificar y corregir columnas en appointments
    \$stmt = \$db->query(\"SHOW COLUMNS FROM appointments LIKE 'appointment_date'\");
    \$hasAppointmentDate = \$stmt->rowCount() > 0;
    
    \$stmt = \$db->query(\"SHOW COLUMNS FROM appointments LIKE 'date'\");
    \$hasDate = \$stmt->rowCount() > 0;
    
    if (\$hasDate && !\$hasAppointmentDate) {
        // Crear appointment_date y copiar datos
        \$db->exec(\"ALTER TABLE appointments ADD COLUMN appointment_date DATE\");
        \$db->exec(\"UPDATE appointments SET appointment_date = date\");
        echo \"Columna appointment_date creada y datos copiados.\\n\";
    }
    
    \$stmt = \$db->query(\"SHOW COLUMNS FROM appointments LIKE 'appointment_time'\");
    \$hasAppointmentTime = \$stmt->rowCount() > 0;
    
    \$stmt = \$db->query(\"SHOW COLUMNS FROM appointments LIKE 'time'\");
    \$hasTime = \$stmt->rowCount() > 0;
    
    if (\$hasTime && !\$hasAppointmentTime) {
        // Crear appointment_time y copiar datos
        \$db->exec(\"ALTER TABLE appointments ADD COLUMN appointment_time TIME\");
        \$db->exec(\"UPDATE appointments SET appointment_time = time\");
        echo \"Columna appointment_time creada y datos copiados.\\n\";
    }
    
    echo \"Correcciones completadas con éxito.\";
} catch (Exception \$e) {
    echo \"Error: \" . \$e->getMessage();
}
?&gt;
</pre>";

echo "<p><a href='fix_database.php' style='display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>Ejecutar script de corrección</a></p>";

// Crear script de corrección
$fixScript = '<?php
// Script para corregir problemas en la base de datos
header("Content-Type: text/html; charset=utf-8");
echo "<h1>Corrigiendo problemas en la base de datos</h1>";

require_once __DIR__ . "/../backend/config/database_class.php";

try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<p>Conexión a la base de datos establecida.</p>";
    
    // Verificar y corregir columnas en appointments
    $stmt = $db->query("SHOW TABLES LIKE \'appointments\'");
    if ($stmt->rowCount() == 0) {
        echo "<p style=\'color:red\'>La tabla appointments no existe. No se pueden hacer correcciones.</p>";
        exit;
    }
    
    $stmt = $db->query("SHOW COLUMNS FROM appointments LIKE \'appointment_date\'");
    $hasAppointmentDate = $stmt->rowCount() > 0;
    
    $stmt = $db->query("SHOW COLUMNS FROM appointments LIKE \'date\'");
    $hasDate = $stmt->rowCount() > 0;
    
    if ($hasDate && !$hasAppointmentDate) {
        // Crear appointment_date y copiar datos
        $db->exec("ALTER TABLE appointments ADD COLUMN appointment_date DATE");
        $db->exec("UPDATE appointments SET appointment_date = date");
        echo "<p style=\'color:green\'>✓ Columna appointment_date creada y datos copiados.</p>";
    } elseif ($hasAppointmentDate) {
        echo "<p>La columna appointment_date ya existe.</p>";
    }
    
    $stmt = $db->query("SHOW COLUMNS FROM appointments LIKE \'appointment_time\'");
    $hasAppointmentTime = $stmt->rowCount() > 0;
    
    $stmt = $db->query("SHOW COLUMNS FROM appointments LIKE \'time\'");
    $hasTime = $stmt->rowCount() > 0;
    
    if ($hasTime && !$hasAppointmentTime) {
        // Crear appointment_time y copiar datos
        $db->exec("ALTER TABLE appointments ADD COLUMN appointment_time TIME");
        $db->exec("UPDATE appointments SET appointment_time = time");
        echo "<p style=\'color:green\'>✓ Columna appointment_time creada y datos copiados.</p>";
    } elseif ($hasAppointmentTime) {
        echo "<p>La columna appointment_time ya existe.</p>";
    }
    
    // Verificar doctor_id en appointments
    $stmt = $db->query("SHOW COLUMNS FROM appointments LIKE \'doctor_id\'");
    if ($stmt->rowCount() == 0) {
        echo "<p style=\'color:red\'>La columna doctor_id no existe en appointments. Esto puede causar problemas.</p>";
    } else {
        echo "<p style=\'color:green\'>✓ La columna doctor_id existe en appointments.</p>";
    }
    
    echo "<p style=\'color:green; font-weight:bold\'>Correcciones completadas.</p>";
    echo "<p><a href=\'doctor_dashboard.html\' style=\'display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px;\'>Volver al dashboard</a></p>";
} catch (Exception $e) {
    echo "<p style=\'color:red\'>Error: " . $e->getMessage() . "</p>";
}
?>';

file_put_contents('fix_database.php', $fixScript);

echo "<p>Se ha creado el script 'fix_database.php' para corregir problemas en la base de datos.</p>";
