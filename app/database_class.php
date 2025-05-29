<?php
/**
 * Database Configuration Class
 * 
 * Esta clase proporciona una interfaz para conectarse a la base de datos MySQL
 * utilizando PDO. Es compatible con los scripts que hemos creado para el sistema
 * de citas médicas.
 */

class Database {
    // Propiedades de conexión
    private $host = '127.0.0.1'; // Cambiado de 'localhost' a IP directa
    private $db_name = 'salutia';
    private $username = 'root';
    private $password = '';
    private $port = 3306;
    private $conn;
    private $connected = false;
    
    /**
     * Constructor - Intenta verificar la disponibilidad del servidor MySQL
     */
    public function __construct() {
        // Verificar si el servidor MySQL está disponible
        try {
            $socket = @fsockopen($this->host, $this->port, $errno, $errstr, 1);
            if ($socket) {
                fclose($socket);
                $this->connected = true;
            } else {
                error_log("MySQL no está disponible en {$this->host}:{$this->port} - Error: $errstr ($errno)");
            }
        } catch (Exception $e) {
            error_log("Error al verificar disponibilidad de MySQL: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener conexión a la base de datos
     * 
     * @return PDO Conexión a la base de datos
     */
    public function getConnection() {
        $this->conn = null;
        
        if (!$this->connected) {
            throw new PDOException("No se puede conectar al servidor MySQL. Verifique que el servicio esté activo.");
        }
        
        try {
            // Primero intentar conectar sin especificar la base de datos
            try {
                $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";charset=utf8mb4";
                $tempConn = new PDO($dsn, $this->username, $this->password);
                
                // Verificar si la base de datos existe
                $stmt = $tempConn->query("SHOW DATABASES LIKE '{$this->db_name}'");
                if ($stmt->rowCount() == 0) {
                    // La base de datos no existe, intentar crearla
                    $tempConn->exec("CREATE DATABASE IF NOT EXISTS {$this->db_name} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    error_log("Base de datos '{$this->db_name}' creada automáticamente");
                }
                
                // Cerrar la conexión temporal
                $tempConn = null;
            } catch (PDOException $e) {
                error_log("Error al verificar/crear base de datos: " . $e->getMessage());
                // Continuar con el intento de conexión normal
            }
            
            // Conectar a la base de datos específica
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";port=" . $this->port . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5, // Timeout de 5 segundos
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            return $this->conn;
        } catch (PDOException $e) {
            // Registrar error detallado
            $errorMsg = "Error de conexión a la base de datos: " . $e->getMessage();
            error_log($errorMsg);
            
            // Proporcionar un mensaje más amigable y detallado
            $userMsg = "Error de conexión a la base de datos. ";
            
            if (strpos($e->getMessage(), "Access denied") !== false) {
                $userMsg .= "Credenciales incorrectas. Verifique el usuario y contraseña.";
            } else if (strpos($e->getMessage(), "Unknown database") !== false) {
                $userMsg .= "La base de datos '{$this->db_name}' no existe.";
            } else if (strpos($e->getMessage(), "Connection refused") !== false) {
                $userMsg .= "Conexión rechazada. Verifique que el servidor MySQL esté activo.";
            } else if (strpos($e->getMessage(), "2002") !== false) {
                $userMsg .= "No se pudo establecer la conexión. Verifique que el servidor MySQL esté activo en {$this->host}:{$this->port}.";
            }
            
            throw new PDOException($userMsg);
        }
    }
    
    /**
     * Verificar si la tabla existe y crearla si no
     * 
     * @param string $tableName Nombre de la tabla
     * @param string $createSQL SQL para crear la tabla
     * @return bool True si la tabla existe o fue creada
     */
    public function ensureTableExists($tableName, $createSQL) {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->query("SHOW TABLES LIKE '{$tableName}'");
            
            if ($stmt->rowCount() == 0) {
                // La tabla no existe, crearla
                $conn->exec($createSQL);
                error_log("Tabla '{$tableName}' creada automáticamente");
                return true;
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Error al verificar/crear tabla '{$tableName}': " . $e->getMessage());
            return false;
        }
    }
}
?>
