<?php
// Script para verificar la estructura de las tablas en la base de datos
header("Content-Type: text/html; charset=UTF-8");

echo "<html><head><title>Verificar Tablas - Salutia</title>
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
</style>
</head><body>
<h1>Verificar Tablas en Salutia</h1>";

try {
    // Incluir la configuración de la base de datos
    require_once __DIR__ . '/../backend/config/database_class.php';
    
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p class='success'>✓ Conexión a la base de datos establecida correctamente.</p>";
    
    // Obtener todas las tablas
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Tablas encontradas: " . count($tables) . "</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Verificar cada tabla
    foreach ($tables as $table) {
        echo "<h2>Estructura de la tabla '$table'</h2>";
        
        // Obtener la estructura de la tabla
        $stmt = $db->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Predeterminado</th><th>Extra</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . ($column['Default'] === null ? '<span class="warning">NULL</span>' : $column['Default']) . "</td>";
            echo "<td>" . $column['Extra'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Verificar si hay campos obligatorios sin valor predeterminado
        $problemFields = [];
        foreach ($columns as $column) {
            if ($column['Null'] === 'NO' && $column['Default'] === null && $column['Extra'] !== 'auto_increment') {
                $problemFields[] = $column['Field'];
            }
        }
        
        if (!empty($problemFields)) {
            echo "<p class='error'>✗ La tabla '$table' tiene campos obligatorios sin valor predeterminado: " . implode(', ', $problemFields) . "</p>";
            
            // Sugerir solución
            echo "<h3>Posibles soluciones:</h3>";
            echo "<ol>";
            echo "<li>Modificar la tabla para permitir valores NULL en estos campos</li>";
            echo "<li>Añadir un valor predeterminado a estos campos</li>";
            echo "<li>Asegurarse de proporcionar un valor para estos campos al insertar registros</li>";
            echo "</ol>";
            
            echo "<h3>SQL para modificar la tabla:</h3>";
            echo "<pre>";
            foreach ($problemFields as $field) {
                echo "ALTER TABLE `$table` MODIFY `$field` " . getColumnType($columns, $field) . " NULL;\n";
            }
            echo "</pre>";
            
            echo "<h3>O añadir valores predeterminados:</h3>";
            echo "<pre>";
            foreach ($problemFields as $field) {
                $defaultValue = suggestDefaultValue($field);
                echo "ALTER TABLE `$table` MODIFY `$field` " . getColumnType($columns, $field) . " DEFAULT $defaultValue;\n";
            }
            echo "</pre>";
        } else {
            echo "<p class='success'>✓ La tabla '$table' no tiene problemas de campos obligatorios sin valor predeterminado.</p>";
        }
    }
    
    // Verificar específicamente el problema de user_id
    if (in_array('users', $tables)) {
        echo "<h2>Verificando el problema de 'user_id'</h2>";
        
        // Buscar tablas que tengan un campo user_id
        $tablesWithUserId = [];
        foreach ($tables as $table) {
            $stmt = $db->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($columns as $column) {
                if ($column['Field'] === 'user_id' && $column['Null'] === 'NO' && $column['Default'] === null) {
                    $tablesWithUserId[] = $table;
                    break;
                }
            }
        }
        
        if (!empty($tablesWithUserId)) {
            echo "<p class='error'>✗ Las siguientes tablas tienen un campo 'user_id' obligatorio sin valor predeterminado: " . implode(', ', $tablesWithUserId) . "</p>";
            
            echo "<h3>SQL para solucionar el problema:</h3>";
            echo "<pre>";
            foreach ($tablesWithUserId as $table) {
                echo "ALTER TABLE `$table` MODIFY `user_id` INT NULL;\n";
                // Alternativa: echo "ALTER TABLE `$table` MODIFY `user_id` INT DEFAULT 1;\n";
            }
            echo "</pre>";
            
            // Botón para ejecutar la solución
            echo "<form method='post'>";
            echo "<input type='hidden' name='fix_user_id' value='1'>";
            echo "<button type='submit' class='button'>Aplicar solución (permitir NULL en user_id)</button>";
            echo "</form>";
        } else {
            echo "<p class='success'>✓ No se encontraron tablas con un campo 'user_id' obligatorio sin valor predeterminado.</p>";
        }
    }
    
    // Ejecutar la solución si se solicita
    if (isset($_POST['fix_user_id'])) {
        echo "<h2>Aplicando solución...</h2>";
        
        foreach ($tablesWithUserId as $table) {
            try {
                $db->exec("ALTER TABLE `$table` MODIFY `user_id` INT NULL");
                echo "<p class='success'>✓ Se modificó el campo 'user_id' en la tabla '$table' para permitir NULL.</p>";
            } catch (PDOException $e) {
                echo "<p class='error'>✗ Error al modificar la tabla '$table': " . $e->getMessage() . "</p>";
            }
        }
    }
    
    echo "<div style='margin-top: 20px;'>";
    echo "<a href='crear_medico.php' class='button'>Volver a Crear Médico</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

// Función para obtener el tipo de columna
function getColumnType($columns, $fieldName) {
    foreach ($columns as $column) {
        if ($column['Field'] === $fieldName) {
            return $column['Type'];
        }
    }
    return 'VARCHAR(255)';
}

// Función para sugerir un valor predeterminado basado en el nombre del campo
function suggestDefaultValue($fieldName) {
    $fieldName = strtolower($fieldName);
    
    if (strpos($fieldName, 'id') !== false) {
        return '1';
    } elseif (strpos($fieldName, 'date') !== false || strpos($fieldName, 'time') !== false) {
        return 'CURRENT_TIMESTAMP';
    } elseif (strpos($fieldName, 'active') !== false || strpos($fieldName, 'status') !== false) {
        return '1';
    } elseif (strpos($fieldName, 'email') !== false) {
        return "'example@example.com'";
    } elseif (strpos($fieldName, 'name') !== false) {
        return "'Default Name'";
    } else {
        return "''";
    }
}

echo "</body></html>";
?>
