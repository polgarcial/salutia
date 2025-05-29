<?php
// Script para crear un médico con contraseña específica
header("Content-Type: text/html; charset=UTF-8");

echo "<html><head><title>Crear Médico - Salutia</title>
<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
    h1, h2 { color: #0066cc; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
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
<h1>Crear Médico en Salutia</h1>";

try {
    // Incluir la configuración de la base de datos
    require_once __DIR__ . '/../backend/config/database_class.php';
    
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p class='success'>✓ Conexión a la base de datos establecida correctamente.</p>";
    
    // Verificar si existe la tabla users
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    $usersTableExists = $stmt->rowCount() > 0;
    
    if ($usersTableExists) {
        // Verificar si la tabla users tiene las columnas necesarias
        $stmt = $db->query("DESCRIBE users");
        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[$row['Field']] = $row;
        }
        
        echo "<h2>Estructura de la tabla 'users'</h2>";
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
        
        // Crear usuario médico con contraseña 123456
        echo "<h2>Creando médico con contraseña '123456'...</h2>";
        
        // Verificar si el email ya existe
        $email = 'doctor@salutia.com';
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo "<p class='error'>✗ Ya existe un usuario con el email '$email'. Se actualizará su contraseña.</p>";
            
            // Actualizar contraseña
            $password = password_hash('123456', PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = :password, role = 'doctor' WHERE email = :email");
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            echo "<p class='success'>✓ Contraseña actualizada correctamente.</p>";
        } else {
            // Crear consulta SQL según las columnas disponibles
            $sql = "INSERT INTO users (";
            $values = " VALUES (";
            $params = [];
            
            // Añadir campos según la estructura de la tabla
            if (isset($columns['first_name'])) {
                $sql .= "first_name, ";
                $values .= ":first_name, ";
                $params[':first_name'] = 'Dr. Juan';
            }
            
            if (isset($columns['last_name'])) {
                $sql .= "last_name, ";
                $values .= ":last_name, ";
                $params[':last_name'] = 'Médico';
            }
            
            if (isset($columns['name']) && !isset($columns['first_name'])) {
                $sql .= "name, ";
                $values .= ":name, ";
                $params[':name'] = 'Dr. Juan Médico';
            }
            
            // Campos obligatorios
            $sql .= "email, password, role)";
            $values .= ":email, :password, :role)";
            $params[':email'] = $email;
            $params[':password'] = password_hash('123456', PASSWORD_DEFAULT);
            $params[':role'] = 'doctor';
            
            $stmt = $db->prepare($sql . $values);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            echo "<p class='success'>✓ Médico creado correctamente con la contraseña '123456'.</p>";
        }
        
        // Verificar si existe la tabla doctors
        $stmt = $db->query("SHOW TABLES LIKE 'doctors'");
        $doctorsTableExists = $stmt->rowCount() > 0;
        
        if ($doctorsTableExists) {
            // Obtener el ID del usuario médico
            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $userId = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
            
            // Verificar la estructura de la tabla doctors
            $stmt = $db->query("DESCRIBE doctors");
            $doctorColumns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $doctorColumns[$row['Field']] = $row;
            }
            
            // Verificar si ya existe el médico (solo si la columna email existe)
            $doctorExists = false;
            if (isset($doctorColumns['email'])) {
                $stmt = $db->prepare("SELECT id FROM doctors WHERE email = :email");
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                $doctorExists = $stmt->rowCount() > 0;
            }
            
            if ($doctorExists) {
                echo "<p class='error'>✗ Ya existe un médico con el email '$email'.</p>";
            } else {
                // Crear médico en la tabla doctors
                $sql = "INSERT INTO doctors (";
                $values = " VALUES (";
                $doctorParams = [];
                
                // Añadir campos según la estructura de la tabla
                if (isset($doctorColumns['name'])) {
                    $sql .= "name, ";
                    $values .= ":name, ";
                    $doctorParams[':name'] = 'Dr. Juan Médico';
                }
                
                if (isset($doctorColumns['specialty'])) {
                    $sql .= "specialty, ";
                    $values .= ":specialty, ";
                    $doctorParams[':specialty'] = 'Medicina General';
                }
                
                if (isset($doctorColumns['email'])) {
                    $sql .= "email, ";
                    $values .= ":email, ";
                    $doctorParams[':email'] = $email;
                }
                
                if (isset($doctorColumns['phone'])) {
                    $sql .= "phone, ";
                    $values .= ":phone, ";
                    $doctorParams[':phone'] = '123456789';
                }
                
                if (isset($doctorColumns['active'])) {
                    $sql .= "active, ";
                    $values .= ":active, ";
                    $doctorParams[':active'] = 1;
                }
                
                // Eliminar la última coma
                $sql = rtrim($sql, ", ");
                $values = rtrim($values, ", ");
                
                $sql .= ")";
                $values .= ")";
                
                $stmt = $db->prepare($sql . $values);
                foreach ($doctorParams as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                $stmt->execute();
                
                echo "<p class='success'>✓ Médico creado en la tabla 'doctors'.</p>";
            }
        } else {
            echo "<p class='error'>✗ La tabla 'doctors' no existe en la base de datos. No se puede crear el médico en esta tabla.</p>";
        }
        
        // Mostrar credenciales
        echo "<h2>Credenciales del médico</h2>";
        echo "<table>";
        echo "<tr><th>Email</th><th>Contraseña</th><th>Rol</th></tr>";
        echo "<tr><td>$email</td><td>123456</td><td>doctor</td></tr>";
        echo "</table>";
        
    } else {
        echo "<p class='error'>✗ La tabla 'users' no existe en la base de datos. No se puede crear el médico.</p>";
        
        // Crear la tabla users
        echo "<h2>Creando tabla 'users'...</h2>";
        
        $sql = "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(50),
            last_name VARCHAR(50),
            role VARCHAR(20) DEFAULT 'patient',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $db->exec($sql);
        echo "<p class='success'>✓ Tabla 'users' creada correctamente.</p>";
        echo "<p>Recarga esta página para crear el médico.</p>";
    }
    
    echo "<div style='margin-top: 20px;'>";
    echo "<a href='doctor_login.html' class='button'>Ir a la página de inicio de sesión para médicos</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
