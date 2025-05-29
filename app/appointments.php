<?php
// Desactivar la salida de errores HTML para evitar que se mezcle con JSON
ini_set('display_errors', 0);
error_reporting(0);

// Configuración de CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
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

// Función para verificar el token JWT
function verifyToken($token) {
    // En una implementación real, verificaríamos la firma del token
    // Por ahora, simplemente extraemos los datos
    $jwt_secret = 'your_jwt_secret_key_here';
    
    $tokenParts = explode('.', $token);
    if (count($tokenParts) != 3) {
        return false;
    }
    
    $payload = json_decode(base64_decode($tokenParts[1]), true);
    
    // Verificar si el token ha expirado
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }
    
    return $payload;
}

// Obtener el token de autorización
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    // Para pruebas, permitimos acceso sin token
    $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    if ($userId <= 0) {
        sendJsonResponse(401, ['error' => 'No autorizado']);
    }
} else {
    $token = $matches[1];
    $payload = verifyToken($token);
    
    if (!$payload) {
        sendJsonResponse(401, ['error' => 'Token inválido o expirado']);
    }
    
    $userId = $payload['data']['id'];
}

// Configuración de la base de datos
$db_host = 'localhost';
$db_name = 'salutia';
$db_user = 'root';
$db_pass = '';

// Conectar a la base de datos
try {
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $db = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    sendJsonResponse(500, ['error' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
}

// Manejar diferentes métodos HTTP
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Obtener citas del usuario
        getAppointments($db, $userId);
        break;
    case 'POST':
        // Crear nueva cita
        createAppointment($db, $userId);
        break;
    case 'PUT':
        // Actualizar cita existente
        updateAppointment($db, $userId);
        break;
    case 'DELETE':
        // Cancelar cita
        cancelAppointment($db, $userId);
        break;
    default:
        sendJsonResponse(405, ['error' => 'Método no permitido']);
}

// Función para obtener las citas del usuario
function getAppointments($db, $userId) {
    try {
        // Verificar si el usuario es paciente o médico
        $stmt = $db->prepare("SELECT role FROM users WHERE id = :id");
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            sendJsonResponse(404, ['error' => 'Usuario no encontrado']);
        }
        
        $user = $stmt->fetch();
        $isDoctor = ($user['role'] === 'doctor');
        
        // Consulta SQL según el rol del usuario
        if ($isDoctor) {
            $sql = "
                SELECT a.*, 
                       p.first_name as patient_first_name, 
                       p.last_name as patient_last_name
                FROM appointments a
                JOIN users p ON a.patient_id = p.id
                WHERE a.doctor_id = :user_id
                ORDER BY a.appointment_date ASC, a.appointment_time ASC
            ";
            $params = [':user_id' => $userId];
        } else {
            $sql = "
                SELECT a.*, 
                       d.first_name as doctor_first_name, 
                       d.last_name as doctor_last_name,
                       d.specialty as doctor_specialty
                FROM appointments a
                JOIN users d ON a.doctor_id = d.id
                WHERE a.patient_id = :user_id
                ORDER BY a.appointment_date ASC, a.appointment_time ASC
            ";
            $params = [':user_id' => $userId];
        }
        
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $appointments = $stmt->fetchAll();
        
        // Si no hay citas, devolver datos de ejemplo para pruebas
        if (count($appointments) === 0) {
            // Datos de ejemplo para pruebas
            $currentDate = date('Y-m-d');
            $nextWeek = date('Y-m-d', strtotime('+7 days'));
            
            $sampleAppointments = [
                [
                    'id' => 1,
                    'appointment_date' => $currentDate,
                    'appointment_time' => '10:30:00',
                    'duration' => 30,
                    'status' => 'scheduled',
                    'reason' => 'Consulta general',
                    'doctor_first_name' => 'Joan',
                    'doctor_last_name' => 'Metge',
                    'doctor_specialty' => 'Medicina General'
                ],
                [
                    'id' => 2,
                    'appointment_date' => $nextWeek,
                    'appointment_time' => '16:00:00',
                    'duration' => 45,
                    'status' => 'scheduled',
                    'reason' => 'Revisión cardiológica',
                    'doctor_first_name' => 'Laura',
                    'doctor_last_name' => 'Martínez',
                    'doctor_specialty' => 'Cardiología'
                ]
            ];
            
            sendJsonResponse(200, [
                'success' => true,
                'message' => 'Datos de ejemplo para pruebas',
                'is_sample_data' => true,
                'appointments' => $sampleAppointments
            ]);
        }
        
        sendJsonResponse(200, [
            'success' => true,
            'appointments' => $appointments
        ]);
    } catch (Exception $e) {
        sendJsonResponse(500, ['error' => 'Error al obtener citas: ' . $e->getMessage()]);
    }
}

// Función para crear una nueva cita
function createAppointment($db, $userId) {
    try {
        // Obtener los datos enviados
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);
        
        // Verificar datos requeridos
        if (!isset($data['doctor_id']) || !isset($data['date']) || !isset($data['time'])) {
            sendJsonResponse(400, ['error' => 'Faltan datos requeridos']);
        }
        
        $doctorId = intval($data['doctor_id']);
        $appointmentDate = $data['date'];
        $appointmentTime = $data['time'];
        
        // Validar fecha y hora
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointmentDate)) {
            sendJsonResponse(400, ['error' => 'Formato de fecha inválido. Use YYYY-MM-DD']);
        }
        
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $appointmentTime)) {
            sendJsonResponse(400, ['error' => 'Formato de hora inválido. Use HH:MM o HH:MM:SS']);
        }
        
        // Asegurarse de que el formato de hora sea consistente (HH:MM:SS)
        if (strlen($appointmentTime) === 5) {
            $appointmentTime .= ':00';
        }
        
        // Verificar que el médico esté disponible en ese horario
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM doctor_availability 
            WHERE doctor_id = ? AND date = ? AND time_slot = ? AND is_available = 1
        ");
        $stmt->execute([$doctorId, $appointmentDate, $appointmentTime]);
        $isAvailable = ($stmt->fetchColumn() > 0);
        
        if (!$isAvailable) {
            // Verificar si el horario existe pero ya está ocupado por otra cita
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM appointments 
                WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ?
            ");
            $stmt->execute([$doctorId, $appointmentDate, $appointmentTime]);
            $hasAppointment = ($stmt->fetchColumn() > 0);
            
            if ($hasAppointment) {
                sendJsonResponse(409, ['error' => 'El horario seleccionado ya está ocupado por otra cita']);
            } else {
                sendJsonResponse(404, ['error' => 'El médico no está disponible en el horario seleccionado']);
            }
        }
        
        // Iniciar transacción
        $db->beginTransaction();
        
        // Insertar la nueva cita
        $stmt = $db->prepare("
            INSERT INTO appointments (
                patient_id, 
                doctor_id, 
                appointment_date, 
                appointment_time, 
                status, 
                reason
            ) VALUES (
                :patient_id, 
                :doctor_id, 
                :appointment_date, 
                :appointment_time, 
                'scheduled', 
                :reason
            )
        ");
        
        $reason = isset($data['reason']) ? $data['reason'] : '';
        
        $stmt->bindParam(':patient_id', $userId);
        $stmt->bindParam(':doctor_id', $doctorId);
        $stmt->bindParam(':appointment_date', $appointmentDate);
        $stmt->bindParam(':appointment_time', $appointmentTime);
        $stmt->bindParam(':reason', $reason);
        
        if ($stmt->execute()) {
            $appointmentId = $db->lastInsertId();
            
            // Obtener los datos de la cita creada
            $stmt = $db->prepare("
                SELECT a.*, 
                       u.name as doctor_name,
                       u.specialty as doctor_specialty
                FROM appointments a
                LEFT JOIN doctors u ON a.doctor_id = u.id
                WHERE a.id = :id
            ");
            $stmt->bindParam(':id', $appointmentId);
            $stmt->execute();
            
            $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Si no encontramos los datos del médico en la tabla doctors, buscar en users
            if (!$appointment['doctor_name']) {
                $stmt = $db->prepare("
                    SELECT CONCAT(first_name, ' ', last_name) as doctor_name, specialty as doctor_specialty
                    FROM users 
                    WHERE id = :doctor_id AND role = 'doctor'
                ");
                $stmt->bindParam(':doctor_id', $doctorId);
                $stmt->execute();
                
                $doctorInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($doctorInfo) {
                    $appointment['doctor_name'] = $doctorInfo['doctor_name'];
                    $appointment['doctor_specialty'] = $doctorInfo['doctor_specialty'];
                }
            }
            
            // Confirmar transacción
            $db->commit();
            
            sendJsonResponse(201, [
                'success' => true,
                'message' => 'Cita creada correctamente',
                'appointment' => $appointment
            ]);
        } else {
            // Revertir transacción en caso de error
            $db->rollBack();
            sendJsonResponse(500, ['error' => 'No se pudo crear la cita']);
        }
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        sendJsonResponse(500, ['error' => 'Error al crear cita: ' . $e->getMessage()]);
    }
}

// Función para actualizar una cita existente
function updateAppointment($db, $userId) {
    try {
        // Obtener los datos enviados
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);
        
        // Verificar datos requeridos
        if (!isset($data['id'])) {
            sendJsonResponse(400, ['error' => 'ID de cita requerido']);
        }
        
        // Verificar que la cita pertenezca al usuario
        $stmt = $db->prepare("
            SELECT * FROM appointments 
            WHERE id = :id AND (patient_id = :user_id OR doctor_id = :user_id)
        ");
        $stmt->bindParam(':id', $data['id']);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            sendJsonResponse(403, ['error' => 'No tienes permiso para modificar esta cita']);
        }
        
        // Construir la consulta de actualización
        $updateFields = [];
        $params = [':id' => $data['id']];
        
        if (isset($data['date'])) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date'])) {
                sendJsonResponse(400, ['error' => 'Formato de fecha inválido. Use YYYY-MM-DD']);
            }
            $updateFields[] = 'date = :date';
            $params[':date'] = $data['date'];
        }
        
        if (isset($data['time'])) {
            if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $data['time'])) {
                sendJsonResponse(400, ['error' => 'Formato de hora inválido. Use HH:MM o HH:MM:SS']);
            }
            $updateFields[] = 'time = :time';
            $params[':time'] = $data['time'];
        }
        
        if (isset($data['duration'])) {
            $updateFields[] = 'duration = :duration';
            $params[':duration'] = $data['duration'];
        }
        
        if (isset($data['status'])) {
            $validStatus = ['scheduled', 'completed', 'cancelled', 'no-show'];
            if (!in_array($data['status'], $validStatus)) {
                sendJsonResponse(400, ['error' => 'Estado inválido']);
            }
            $updateFields[] = 'status = :status';
            $params[':status'] = $data['status'];
        }
        
        if (isset($data['reason'])) {
            $updateFields[] = 'reason = :reason';
            $params[':reason'] = $data['reason'];
        }
        
        if (isset($data['notes'])) {
            $updateFields[] = 'notes = :notes';
            $params[':notes'] = $data['notes'];
        }
        
        if (empty($updateFields)) {
            sendJsonResponse(400, ['error' => 'No hay campos para actualizar']);
        }
        
        // Actualizar la cita
        $sql = "UPDATE appointments SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = $db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        if ($stmt->execute()) {
            // Obtener los datos actualizados de la cita
            $stmt = $db->prepare("
                SELECT a.*, 
                       d.first_name as doctor_first_name, 
                       d.last_name as doctor_last_name,
                       d.specialty as doctor_specialty,
                       p.first_name as patient_first_name,
                       p.last_name as patient_last_name
                FROM appointments a
                JOIN users d ON a.doctor_id = d.id
                JOIN users p ON a.patient_id = p.id
                WHERE a.id = :id
            ");
            $stmt->bindParam(':id', $data['id']);
            $stmt->execute();
            
            $appointment = $stmt->fetch();
            
            sendJsonResponse(200, [
                'success' => true,
                'message' => 'Cita actualizada correctamente',
                'appointment' => $appointment
            ]);
        } else {
            sendJsonResponse(500, ['error' => 'No se pudo actualizar la cita']);
        }
    } catch (Exception $e) {
        sendJsonResponse(500, ['error' => 'Error al actualizar cita: ' . $e->getMessage()]);
    }
}

// Función para cancelar una cita
function cancelAppointment($db, $userId) {
    try {
        // Obtener ID de la cita
        $appointmentId = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($appointmentId <= 0) {
            sendJsonResponse(400, ['error' => 'ID de cita inválido']);
        }
        
        // Verificar que la cita pertenezca al usuario
        $stmt = $db->prepare("
            SELECT * FROM appointments 
            WHERE id = :id AND (patient_id = :user_id OR doctor_id = :user_id)
        ");
        $stmt->bindParam(':id', $appointmentId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            sendJsonResponse(403, ['error' => 'No tienes permiso para cancelar esta cita']);
        }
        
        // Cancelar la cita
        $stmt = $db->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = :id");
        $stmt->bindParam(':id', $appointmentId);
        
        if ($stmt->execute()) {
            sendJsonResponse(200, [
                'success' => true,
                'message' => 'Cita cancelada correctamente'
            ]);
        } else {
            sendJsonResponse(500, ['error' => 'No se pudo cancelar la cita']);
        }
    } catch (Exception $e) {
        sendJsonResponse(500, ['error' => 'Error al cancelar cita: ' . $e->getMessage()]);
    }
}
