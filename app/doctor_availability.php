<?php
// Desactivar la salida de errores HTML para evitar que se mezcle con JSON
ini_set('display_errors', 0);
error_reporting(0);

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

// Función para devolver respuesta JSON
function sendJsonResponse($status, $data) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Configuración de la base de datos
$db_host = 'localhost';
$db_name = 'salutia';
$db_user = 'root';
$db_pass = '';

// Función para conectar a la base de datos
function connectDB() {
    global $db_host, $db_name, $db_user, $db_pass;
    
    try {
        $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        return new PDO($dsn, $db_user, $db_pass, $options);
    } catch (PDOException $e) {
        sendJsonResponse(500, ['error' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
    }
}

// Función para verificar que el médico existe
function verifyDoctor($db, $doctorId) {
    $stmt = $db->prepare("SELECT id, name, specialty FROM users WHERE id = :id AND role = 'doctor'");
    $stmt->bindParam(':id', $doctorId);
    $stmt->execute();
    
    $doctor = $stmt->fetch();
    
    if (!$doctor) {
        sendJsonResponse(404, ['error' => 'Médico no encontrado']);
    }
    
    return $doctor;
}

// Función para obtener la disponibilidad del médico
function getAvailability() {
    // Obtener parámetros
    $doctorId = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d', strtotime('+14 days'));

    // Validar parámetros
    if ($doctorId <= 0) {
        sendJsonResponse(400, ['error' => 'ID de médico inválido']);
    }

    // Validar fechas
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        sendJsonResponse(400, ['error' => 'Formato de fecha inválido. Use YYYY-MM-DD']);
    }
    
    try {
        // Conectar a la base de datos
        $db = connectDB();
        
        // Verificar que el médico existe
        $doctor = verifyDoctor($db, $doctorId);
        
        // Crear la tabla si no existe
        $db->exec("CREATE TABLE IF NOT EXISTS doctor_availability (
            id INT AUTO_INCREMENT PRIMARY KEY,
            doctor_id INT NOT NULL,
            date DATE NOT NULL,
            time_slot TIME NOT NULL,
            UNIQUE KEY unique_availability (doctor_id, date, time_slot)
        )");
        
        // Obtener disponibilidad existente
        $stmt = $db->prepare("SELECT date, time_slot FROM doctor_availability WHERE doctor_id = ? AND date BETWEEN ? AND ?");
        $stmt->execute([$doctorId, $startDate, $endDate]);
        $availabilityData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organizar la disponibilidad por fecha
        $availability = [];
        foreach ($availabilityData as $slot) {
            $date = $slot['date'];
            if (!isset($availability[$date])) {
                $availability[$date] = [];
            }
            $availability[$date][] = $slot['time_slot'];
        }
        
        // Si no hay disponibilidad, generar horarios predeterminados
        if (empty($availability)) {
            $currentDate = new DateTime($startDate);
            $lastDate = new DateTime($endDate);
            
            while ($currentDate <= $lastDate) {
                $dateStr = $currentDate->format('Y-m-d');
                $dayOfWeek = $currentDate->format('N'); // 1 (lunes) a 7 (domingo)
                
                // Solo generar horarios para días laborables (lunes a viernes)
                if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                    $availability[$dateStr] = [];
                    
                    // Generar slots cada 30 minutos de 9:00 a 17:00
                    for ($hour = 9; $hour < 17; $hour++) {
                        $availability[$dateStr][] = sprintf('%02d:00:00', $hour);
                        $availability[$dateStr][] = sprintf('%02d:30:00', $hour);
                    }
                }
                
                // Avanzar al siguiente día
                $currentDate->add(new DateInterval('P1D'));
            }
        }
        
        // Devolver la disponibilidad
        sendJsonResponse(200, [
            'success' => true,
            'doctor' => [
                'id' => $doctor['id'],
                'name' => $doctor['name'],
                'specialty' => $doctor['specialty'] ?? ''
            ],
            'availability' => $availability
        ]);
        
    } catch (Exception $e) {
        sendJsonResponse(500, ['error' => 'Error al obtener disponibilidad: ' . $e->getMessage()]);
    }
}

// Función para guardar la disponibilidad del médico
function saveAvailability() {
    try {
        // Obtener el cuerpo de la solicitud
        $requestBody = file_get_contents('php://input');
        $data = json_decode($requestBody, true);
        
        // Validar datos
        if (!$data) {
            sendJsonResponse(400, ['error' => 'Datos JSON inválidos']);
        }
        
        // Validar parámetros requeridos
        if (!isset($data['doctor_id']) || !isset($data['slots'])) {
            sendJsonResponse(400, ['error' => 'Faltan parámetros requeridos: doctor_id y slots']);
        }
        
        $doctorId = intval($data['doctor_id']);
        $slots = $data['slots'];
        
        // Validar ID del médico
        if ($doctorId <= 0) {
            sendJsonResponse(400, ['error' => 'ID de médico inválido']);
        }
        
        // Validar formato de slots
        if (!is_array($slots)) {
            sendJsonResponse(400, ['error' => 'El formato de slots es inválido']);
        }
        
        // Conectar a la base de datos
        $db = connectDB();
        
        // Verificar que el médico existe
        $doctor = verifyDoctor($db, $doctorId);
        
        // Crear la tabla si no existe
        $db->exec("CREATE TABLE IF NOT EXISTS doctor_availability (
            id INT AUTO_INCREMENT PRIMARY KEY,
            doctor_id INT NOT NULL,
            date DATE NOT NULL,
            time_slot TIME NOT NULL,
            UNIQUE KEY unique_availability (doctor_id, date, time_slot)
        )");
        
        // Iniciar transacción
        $db->beginTransaction();
        
        try {
            // Recopilar las fechas únicas para eliminarlas primero
            $dates = [];
            foreach ($slots as $slot) {
                if (isset($slot['date']) && !in_array($slot['date'], $dates)) {
                    $dates[] = $slot['date'];
                }
            }
            
            // Eliminar los slots existentes para estas fechas
            if (!empty($dates)) {
                $placeholders = implode(',', array_fill(0, count($dates), '?'));
                $deleteStmt = $db->prepare("DELETE FROM doctor_availability WHERE doctor_id = ? AND date IN ($placeholders)");
                
                $params = [$doctorId];
                foreach ($dates as $date) {
                    $params[] = $date;
                }
                
                $deleteStmt->execute($params);
            }
            
            // Insertar los nuevos slots
            $stmt = $db->prepare("INSERT INTO doctor_availability (doctor_id, date, time_slot) VALUES (?, ?, ?)");
            
            foreach ($slots as $slot) {
                if (!isset($slot['date']) || !isset($slot['time_slot'])) {
                    continue; // Saltar slots incompletos
                }
                
                $date = $slot['date'];
                $timeSlot = $slot['time_slot'];
                
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    throw new Exception("Formato de fecha inválido: $date");
                }
                
                if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $timeSlot)) {
                    throw new Exception("Formato de hora inválido: $timeSlot");
                }
                
                $stmt->execute([$doctorId, $date, $timeSlot]);
            }
            
            // Confirmar transacción
            $db->commit();
            
            // Devolver respuesta exitosa
            sendJsonResponse(200, [
                'success' => true,
                'message' => 'Horarios guardados correctamente'
            ]);
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $db->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        sendJsonResponse(500, ['error' => 'Error al guardar disponibilidad: ' . $e->getMessage()]);
    }
}

// Manejar diferentes métodos de solicitud
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Obtener disponibilidad del médico
    getAvailability();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Guardar disponibilidad del médico
    saveAvailability();
} else {
    sendJsonResponse(405, ['error' => 'Método no permitido']);
}
