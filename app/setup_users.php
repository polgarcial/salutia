<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir archivos necesarios
require_once 'database_class.php';
require_once 'debug_log.php';

// Crear instancia de la base de datos
$database = new Database();

// SQL para crear la tabla users si no existe
$createUsersTableSQL = "
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` enum('admin','doctor','patient') NOT NULL DEFAULT 'patient',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Crear la tabla si no existe
try {
    $db = $database->getConnection();
    debug_log("Conexión a la base de datos establecida correctamente");
    
    // Crear la tabla users
    $result = $database->ensureTableExists('users', $createUsersTableSQL);
    if ($result) {
        debug_log("Tabla 'users' verificada/creada correctamente");
    }
    
    // Verificar si hay usuarios en la tabla
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    debug_log("Número de usuarios en la base de datos: " . $userCount);
    
    // Si no hay usuarios, crear un usuario de prueba
    if ($userCount == 0) {
        // Crear usuario admin
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (email, password, name, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin@salutia.com', $adminPassword, 'Administrador', 'admin']);
        debug_log("Usuario administrador creado");
        
        // Crear usuario doctor
        $doctorPassword = password_hash('doctor123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (email, password, name, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['doctor@salutia.com', $doctorPassword, 'Dr. García', 'doctor']);
        debug_log("Usuario doctor creado");
        
        // Crear usuario paciente
        $patientPassword = password_hash('123456', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (email, password, name, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['poli@gmail.com', $patientPassword, 'Pol García', 'patient']);
        debug_log("Usuario paciente creado");
        
        echo "<p style='color:green'>✓ Usuarios de prueba creados correctamente</p>";
        echo "<p>Admin: admin@salutia.com / admin123</p>";
        echo "<p>Doctor: doctor@salutia.com / doctor123</p>";
        echo "<p>Paciente: poli@gmail.com / 123456</p>";
    } else {
        echo "<p style='color:green'>✓ Ya existen usuarios en la base de datos</p>";
    }
    
    echo "<p style='color:green'>✓ Configuración completada correctamente</p>";
    echo "<p><a href='../public/login.html'>Ir a la página de inicio de sesión</a></p>";
} catch (PDOException $e) {
    debug_log("Error al configurar la base de datos: " . $e->getMessage());
    echo "<p style='color:red'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<p>Por favor, asegúrese de que el servidor MySQL esté en funcionamiento y que las credenciales sean correctas.</p>";
}
?>
