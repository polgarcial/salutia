<?php
// Script para ver los médicos registrados en la base de datos
header("Content-Type: text/html; charset=UTF-8");

echo "<html><head><title>Médicos Registrados - Salutia</title>
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
<h1>Médicos Registrados en Salutia</h1>";

try {
    // Incluir la configuración de la base de datos
    require_once __DIR__ . '/../backend/config/database_class.php';
    
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p class='success'>✓ Conexión a la base de datos establecida correctamente.</p>";
    
    // Verificar si existe la tabla doctors
    $stmt = $db->query("SHOW TABLES LIKE 'doctors'");
    $doctorsTableExists = $stmt->rowCount() > 0;
    
    // Verificar si existe la tabla users
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    $usersTableExists = $stmt->rowCount() > 0;
    
    if ($doctorsTableExists) {
        echo "<h2>Médicos en la tabla 'doctors'</h2>";
        
        $stmt = $db->query("SELECT * FROM doctors");
        $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($doctors) > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Nombre</th><th>Especialidad</th><th>Email</th><th>Teléfono</th><th>Estado</th></tr>";
            
            foreach ($doctors as $doctor) {
                echo "<tr>";
                echo "<td>" . $doctor['id'] . "</td>";
                echo "<td>" . $doctor['name'] . "</td>";
                echo "<td>" . $doctor['specialty'] . "</td>";
                echo "<td>" . $doctor['email'] . "</td>";
                echo "<td>" . $doctor['phone'] . "</td>";
                echo "<td>" . ($doctor['active'] ? "Activo" : "Inactivo") . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
            
            // Crear credenciales de ejemplo para iniciar sesión
            echo "<h3>Credenciales de ejemplo para iniciar sesión</h3>";
            echo "<p>Puedes utilizar las siguientes credenciales para probar el inicio de sesión:</p>";
            
            echo "<table>";
            echo "<tr><th>Email</th><th>Contraseña</th></tr>";
            
            foreach ($doctors as $doctor) {
                echo "<tr>";
                echo "<td>" . $doctor['email'] . "</td>";
                echo "<td>password123</td>"; // Contraseña de ejemplo
                echo "</tr>";
            }
            
            echo "</table>";
            
            echo "<p><strong>Nota:</strong> Estas son contraseñas de ejemplo. En un entorno real, las contraseñas estarían encriptadas y no serían visibles.</p>";
        } else {
            echo "<p>No hay médicos registrados en la tabla 'doctors'.</p>";
            
            // Insertar médicos de ejemplo
            echo "<h3>Creando médicos de ejemplo...</h3>";
            
            $sql = "INSERT INTO doctors (name, specialty, email, phone, active) VALUES
                ('Dr. Joan Metge', 'Medicina General', 'joan.metge@salutia.com', '123456789', 1),
                ('Dra. Ana Cardióloga', 'Cardiología', 'ana.cardio@salutia.com', '234567890', 1),
                ('Dr. Pedro Dermatologo', 'Dermatología', 'pedro.derm@salutia.com', '345678901', 1),
                ('Dra. María Pediatra', 'Pediatría', 'maria.pediatra@salutia.com', '456789012', 1),
                ('Dra. Laura Ginecóloga', 'Ginecología', 'laura.gineco@salutia.com', '567890123', 1)";
            
            $db->exec($sql);
            echo "<p class='success'>✓ Médicos de ejemplo insertados correctamente. Recarga esta página para verlos.</p>";
        }
    } else {
        echo "<p class='error'>✗ La tabla 'doctors' no existe en la base de datos.</p>";
        
        // Crear la tabla doctors
        echo "<h3>Creando tabla 'doctors'...</h3>";
        
        $sql = "CREATE TABLE doctors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            specialty VARCHAR(50) NOT NULL,
            email VARCHAR(100) UNIQUE,
            phone VARCHAR(20),
            active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $db->exec($sql);
        echo "<p class='success'>✓ Tabla 'doctors' creada correctamente.</p>";
        
        // Insertar médicos de ejemplo
        echo "<h3>Creando médicos de ejemplo...</h3>";
        
        $sql = "INSERT INTO doctors (name, specialty, email, phone, active) VALUES
            ('Dr. Joan Metge', 'Medicina General', 'joan.metge@salutia.com', '123456789', 1),
            ('Dra. Ana Cardióloga', 'Cardiología', 'ana.cardio@salutia.com', '234567890', 1),
            ('Dr. Pedro Dermatologo', 'Dermatología', 'pedro.derm@salutia.com', '345678901', 1),
            ('Dra. María Pediatra', 'Pediatría', 'maria.pediatra@salutia.com', '456789012', 1),
            ('Dra. Laura Ginecóloga', 'Ginecología', 'laura.gineco@salutia.com', '567890123', 1)";
        
        $db->exec($sql);
        echo "<p class='success'>✓ Médicos de ejemplo insertados correctamente. Recarga esta página para verlos.</p>";
    }
    
    if ($usersTableExists) {
        echo "<h2>Usuarios con rol de médico en la tabla 'users'</h2>";
        
        $stmt = $db->query("SELECT * FROM users WHERE role = 'doctor'");
        $doctorUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($doctorUsers) > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th></tr>";
            
            foreach ($doctorUsers as $user) {
                echo "<tr>";
                echo "<td>" . $user['id'] . "</td>";
                echo "<td>" . ($user['first_name'] ?? '') . " " . ($user['last_name'] ?? '') . "</td>";
                echo "<td>" . $user['email'] . "</td>";
                echo "<td>" . $user['role'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
            
            // Crear credenciales de ejemplo para iniciar sesión
            echo "<h3>Credenciales de ejemplo para iniciar sesión</h3>";
            echo "<p>Puedes utilizar las siguientes credenciales para probar el inicio de sesión:</p>";
            
            echo "<table>";
            echo "<tr><th>Email</th><th>Contraseña</th></tr>";
            
            foreach ($doctorUsers as $user) {
                echo "<tr>";
                echo "<td>" . $user['email'] . "</td>";
                echo "<td>password123</td>"; // Contraseña de ejemplo
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>No hay usuarios con rol de médico en la tabla 'users'.</p>";
            
            // Insertar usuarios médicos de ejemplo
            echo "<h3>Creando usuarios médicos de ejemplo...</h3>";
            
            // Verificar si la tabla users tiene las columnas necesarias
            $stmt = $db->query("DESCRIBE users");
            $columns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $columns[$row['Field']] = $row;
            }
            
            if (isset($columns['email']) && isset($columns['password']) && isset($columns['role'])) {
                // Crear consulta SQL según las columnas disponibles
                $sql = "INSERT INTO users (";
                $values = " VALUES (";
                
                if (isset($columns['first_name'])) {
                    $sql .= "first_name, ";
                    $values .= "'Dr. Juan', ";
                }
                
                if (isset($columns['last_name'])) {
                    $sql .= "last_name, ";
                    $values .= "'Médico', ";
                }
                
                if (isset($columns['name']) && !isset($columns['first_name'])) {
                    $sql .= "name, ";
                    $values .= "'Dr. Juan Médico', ";
                }
                
                $sql .= "email, password, role)";
                $values .= "'doctor@salutia.com', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'doctor')";
                
                $db->exec($sql . $values);
                echo "<p class='success'>✓ Usuario médico de ejemplo insertado correctamente. Recarga esta página para verlo.</p>";
            } else {
                echo "<p class='error'>✗ La tabla 'users' no tiene las columnas necesarias para crear usuarios médicos.</p>";
            }
        }
    } else {
        echo "<p class='error'>✗ La tabla 'users' no existe en la base de datos.</p>";
    }
    
    echo "<div style='margin-top: 20px;'>";
    echo "<a href='doctor_login.html' class='button'>Ir a la página de inicio de sesión para médicos</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
