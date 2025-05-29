<?php
// Configuración de CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Manejar solicitudes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir archivo de configuración de base de datos
require_once '../config/database_class.php';

// Función para devolver respuesta JSON
function sendJsonResponse($status, $data) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Crear conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Verificar si la tabla de disponibilidad existe, si no, crearla
function ensureTableExists($db) {
    $query = "SHOW TABLES LIKE 'doctor_availability'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // La tabla no existe, crearla
        $query = "CREATE TABLE doctor_availability (
            id INT AUTO_INCREMENT PRIMARY KEY,
            doctor_id INT NOT NULL,
            date DATE NOT NULL,
            time_slot VARCHAR(8) NOT NULL,
            is_available TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_slot (doctor_id, date, time_slot)
        )";
        
        $db->exec($query);
    }
}

// Verificar si la tabla de citas existe, si no, crearla
function ensureAppointmentsTableExists($db) {
    $query = "SHOW TABLES LIKE 'appointments'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // La tabla no existe, crearla
        $query = "CREATE TABLE appointments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            doctor_id INT NOT NULL,
            patient_id INT NOT NULL,
            appointment_date DATE NOT NULL,
            appointment_time VARCHAR(8) NOT NULL,
            status VARCHAR(20) DEFAULT 'scheduled',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_appointment (doctor_id, appointment_date, appointment_time)
        )";
        
        $db->exec($query);
    }
}

// Asegurar que las tablas existan
ensureTableExists($db);
ensureAppointmentsTableExists($db);

// Manejar diferentes métodos de solicitud
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Obtener parámetros
    $doctorId = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d', strtotime('+14 days'));
    
    if ($doctorId <= 0) {
        sendJsonResponse(400, ['error' => 'ID de médico inválido']);
    }
    
    try {
        // Obtener disponibilidad del médico
        $query = "SELECT date, time_slot FROM doctor_availability 
                 WHERE doctor_id = ? AND date BETWEEN ? AND ? AND is_available = 1";
        $stmt = $db->prepare($query);
        $stmt->execute([$doctorId, $startDate, $endDate]);
        
        $availability = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $date = $row['date'];
            $timeSlot = $row['time_slot'];
            
            if (!isset($availability[$date])) {
                $availability[$date] = [];
            }
            
            $availability[$date][] = $timeSlot;
        }
        
        // Si no hay disponibilidad configurada, devolver un objeto vacío
        // Esto hará que la cuadrícula se muestre en blanco inicialmente
        if (empty($availability)) {
            // No generamos horarios predeterminados, dejamos que el usuario los seleccione manualmente
            // o que aplique una plantilla desde la interfaz
            $availability = [];
        }
        
        // Obtener citas existentes para excluirlas de la disponibilidad
        $query = "SELECT appointment_date, appointment_time FROM appointments 
                 WHERE doctor_id = ? AND appointment_date BETWEEN ? AND ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$doctorId, $startDate, $endDate]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $date = $row['appointment_date'];
            $time = $row['appointment_time'];
            
            // Si existe la fecha y el slot de tiempo, eliminarlo de la disponibilidad
            if (isset($availability[$date])) {
                $key = array_search($time, $availability[$date]);
                if ($key !== false) {
                    unset($availability[$date][$key]);
                    // Reindexar el array
                    $availability[$date] = array_values($availability[$date]);
                }
            }
        }
        
        // Obtener información del médico
        try {
            // Primero intentar obtener de la tabla users
            $query = "SELECT id, CONCAT(first_name, ' ', last_name) as name, specialty FROM users WHERE id = ? AND role = 'doctor'";
            $stmt = $db->prepare($query);
            $stmt->execute([$doctorId]);
            $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Si no se encuentra en users, intentar en la tabla doctors si existe
            if (!$doctor) {
                // Verificar si la tabla doctors existe
                $tableQuery = "SHOW TABLES LIKE 'doctors'";
                $tableStmt = $db->prepare($tableQuery);
                $tableStmt->execute();
                
                if ($tableStmt->rowCount() > 0) {
                    // Verificar las columnas disponibles en la tabla doctors
                    $columnsQuery = "SHOW COLUMNS FROM doctors";
                    $columnsStmt = $db->prepare($columnsQuery);
                    $columnsStmt->execute();
                    $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    // Construir la consulta basada en las columnas disponibles
                    $select = "id";
                    if (in_array('name', $columns)) {
                        $select .= ", name";
                    } else if (in_array('first_name', $columns) && in_array('last_name', $columns)) {
                        $select .= ", CONCAT(first_name, ' ', last_name) as name";
                    } else {
                        $select .= ", 'Dr.' as name";
                    }
                    
                    if (in_array('specialty', $columns)) {
                        $select .= ", specialty";
                    } else {
                        $select .= ", 'Medicina General' as specialty";
                    }
                    
                    $query = "SELECT $select FROM users WHERE role = 'doctor' AND id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$doctorId]);
                    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            }
        } catch (PDOException $e) {
            // Ignorar errores de estructura de tabla
        }
        
        // Si no se encontró el médico en ninguna tabla, usar valores predeterminados
        if (!$doctor) {
            $doctor = [
                'id' => $doctorId,
                'name' => 'Dr. Juan Médico',  // Valor predeterminado
                'specialty' => 'Medicina General'
            ];
        }
        
        // Devolver la disponibilidad
        sendJsonResponse(200, [
            'success' => true,
            'doctor' => $doctor,
            'availability' => $availability
        ]);
        
    } catch (PDOException $e) {
        sendJsonResponse(500, ['error' => 'Error en la base de datos: ' . $e->getMessage()]);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del cuerpo de la solicitud
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data || !isset($data['doctor_id']) || !isset($data['availability'])) {
        sendJsonResponse(400, ['error' => 'Datos inválidos']);
    }
    
    $doctorId = intval($data['doctor_id']);
    $availabilityData = $data['availability'];
    
    if ($doctorId <= 0) {
        sendJsonResponse(400, ['error' => 'ID de médico inválido']);
    }
    
    try {
        // Iniciar transacción
        $db->beginTransaction();
        
        // Eliminar disponibilidad existente para este médico
        $query = "DELETE FROM doctor_availability WHERE doctor_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$doctorId]);
        
        // Insertar nueva disponibilidad
        $query = "INSERT INTO doctor_availability (doctor_id, date, time_slot, is_available) VALUES (?, ?, ?, 1)";
        $stmt = $db->prepare($query);
        
        $insertedCount = 0;
        foreach ($availabilityData as $slot) {
            if (isset($slot['date']) && isset($slot['time_slot'])) {
                $stmt->execute([$doctorId, $slot['date'], $slot['time_slot']]);
                $insertedCount++;
            }
        }
        
        // Confirmar transacción
        $db->commit();
        
        sendJsonResponse(200, [
            'success' => true,
            'message' => 'Horarios guardados correctamente',
            'inserted_count' => $insertedCount
        ]);
        
    } catch (PDOException $e) {
        // Revertir transacción en caso de error
        $db->rollBack();
        sendJsonResponse(500, ['error' => 'Error en la base de datos: ' . $e->getMessage()]);
    }
    
} else {
    sendJsonResponse(405, ['error' => 'Método no permitido']);
}
