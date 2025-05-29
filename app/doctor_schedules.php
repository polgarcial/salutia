<?php
// Habilitar todos los errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Asegurarse de que no hay salida antes de los headers
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once('../config/database.php');

try {
    $conn = getDbConnection();
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
    exit();
}

// Manejar solicitud OPTIONS (para CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verificar autenticación
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado: Falta el encabezado Authorization']);
    exit();
}

// Extraer el token
$authHeader = $headers['Authorization'];
$token = str_replace('Bearer ', '', $authHeader);

// Verificar el token
try {
    require_once('../config/jwt_helper.php');
    $decoded = decodeJWT($token);
    if (!$decoded || !isset($decoded->user_id)) {
        throw new Exception('Token inválido');
    }
    $doctorId = $decoded->user_id; // Usar el user_id del token como doctorId
    
    // Verificar que el usuario sea un médico
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'doctor'");
    $stmt->execute([$doctorId]);
    if ($stmt->rowCount() === 0) {
        throw new Exception("El usuario no tiene permisos de médico");
    }
} catch (Exception $e) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Token inválido: ' . $e->getMessage()]);
    exit();
}

// Función para guardar horarios semanales
function saveWeeklySchedule($conn, $doctorId, $weekNumber, $year, $schedules) {
    try {
        // Verificar si la tabla existe
        $stmt = $conn->query("SHOW TABLES LIKE 'doctor_weekly_schedules'");
        if ($stmt->rowCount() === 0) {
            // Crear la tabla si no existe
            $conn->exec("CREATE TABLE doctor_weekly_schedules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                week_number INT NOT NULL,
                year INT NOT NULL,
                day_of_week INT NOT NULL,
                start_time TIME NOT NULL,
                end_time TIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (user_id, week_number, year)
            )");
            error_log("Tabla doctor_weekly_schedules creada");
        }

        // Validar que el user_id exista en la tabla users con rol de doctor
        $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'doctor'");
        $stmt->execute([$doctorId]);
        if ($stmt->rowCount() === 0) {
            throw new Exception("El usuario con ID $doctorId no existe o no es un doctor");
        }
        
        // Registrar para depuración
        error_log("Guardando horarios para el doctor ID: $doctorId");

        // Validar datos de entrada
        if (!is_numeric($weekNumber) || $weekNumber < 1 || $weekNumber > 53) {
            throw new Exception("Número de semana inválido: $weekNumber");
        }
        if (!is_numeric($year) || $year < 2000 || $year > 9999) {
            throw new Exception("Año inválido: $year");
        }
        if (empty($schedules) || !is_array($schedules)) {
            throw new Exception("Los horarios proporcionados son inválidos o están vacíos");
        }

        // Comenzar transacción
        $conn->beginTransaction();
        
        try {
            // Eliminar horarios existentes para esa semana
            $stmt = $conn->prepare("DELETE FROM doctor_weekly_schedules WHERE user_id = ? AND week_number = ? AND year = ?");
            $stmt->execute([$doctorId, $weekNumber, $year]);
            
            // Insertar nuevos horarios
            $stmt = $conn->prepare("INSERT INTO doctor_weekly_schedules (user_id, week_number, year, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?)");
            
            foreach ($schedules as $schedule) {
                // Validar formato de los horarios
                if (!isset($schedule['dayOfWeek']) || !isset($schedule['startTime']) || !isset($schedule['endTime'])) {
                    throw new Exception("Formato de horario inválido: faltan campos requeridos");
                }
                if (!is_numeric($schedule['dayOfWeek']) || $schedule['dayOfWeek'] < 1 || $schedule['dayOfWeek'] > 7) {
                    throw new Exception("Día de la semana inválido: " . $schedule['dayOfWeek']);
                }
                
                // Validar y formatear las horas
                $startTime = $schedule['startTime'];
                $endTime = $schedule['endTime'];
                
                // Registrar para depuración
                error_log("Horario a guardar: " . json_encode($schedule));
                
                $stmt->execute([
                    $doctorId,
                    $weekNumber,
                    $year,
                    $schedule['dayOfWeek'],
                    $startTime,
                    $endTime
                ]);
            }
            
            // Confirmar transacción
            $conn->commit();
            error_log("Transacción completada con éxito");
            return true;
        } catch (Exception $e) {
            // Revertir cambios si hay error durante la transacción
            if ($conn->inTransaction()) {
                $conn->rollBack();
                error_log("Transacción revertida: " . $e->getMessage());
            }
            throw $e; // Re-lanzar la excepción para manejarla en el bloque catch externo
        }
    } catch (Exception $e) {
        error_log("Error en saveWeeklySchedule: " . $e->getMessage());
        throw new Exception("Error guardando horario: " . $e->getMessage());
    }
}

// Función para obtener horarios semanales
function getWeeklySchedule($conn, $doctorId, $weekNumber, $year) {
    try {
        // Validar parámetros
        if (!is_numeric($weekNumber) || $weekNumber < 1 || $weekNumber > 53) {
            throw new Exception("Número de semana inválido: $weekNumber");
        }
        if (!is_numeric($year) || $year < 2000 || $year > 9999) {
            throw new Exception("Año inválido: $year");
        }

        $stmt = $conn->prepare("SELECT day_of_week, start_time, end_time 
                               FROM doctor_weekly_schedules 
                               WHERE user_id = ? AND week_number = ? AND year = ?");
        $stmt->execute([$doctorId, $weekNumber, $year]);
        
        $schedules = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $schedules[] = [
                'dayOfWeek' => (int)$row['day_of_week'],
                'startTime' => $row['start_time'],
                'endTime' => $row['end_time']
            ];
        }
        return $schedules;
        
    } catch (Exception $e) {
        throw new Exception("Error obteniendo horarios: " . $e->getMessage());
    }
}

// Manejar solicitud POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Registrar datos recibidos para depuración
    error_log("Datos POST recibidos: " . print_r($data, true));
    
    try {
        // Validar datos de entrada
        if (!isset($data['weekNumber']) || !isset($data['year']) || !isset($data['schedules'])) {
            throw new Exception("Faltan parámetros requeridos: weekNumber, year o schedules");
        }
        
        // Ignorar cualquier ID de médico que venga en los datos y usar siempre el del token
        if (isset($data['doctorId'])) {
            error_log("Advertencia: Se ignoró el doctorId proporcionado en los datos. Se usará el ID del token.");
        }

        // Usar $doctorId del token en lugar de $data['doctorId'] para mayor seguridad
        if (saveWeeklySchedule($conn, $doctorId, $data['weekNumber'], $data['year'], $data['schedules'])) {
            ob_clean();
            echo json_encode(['success' => true, 'message' => 'Horarios guardados correctamente para el médico ID: ' . $doctorId]);
        }
    } catch (Exception $e) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Validar parámetros de la solicitud GET
    if (!isset($_GET['weekNumber']) || !isset($_GET['year'])) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Faltan parámetros requeridos: weekNumber o year']);
        exit();
    }
    
    // Si se proporciona un doctorId en la URL, verificar que coincida con el token o que sea un administrador
    if (isset($_GET['doctorId']) && $_GET['doctorId'] != $doctorId) {
        // Verificar si el usuario actual es un administrador
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$doctorId]);
        $userRole = $stmt->fetchColumn();
        
        if ($userRole !== 'admin') {
            ob_clean();
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No tiene permisos para ver los horarios de otro médico']);
            exit();
        }
        
        // Si es admin, usar el doctorId solicitado
        $requestedDoctorId = $_GET['doctorId'];
        error_log("Administrador solicitando horarios del médico ID: $requestedDoctorId");
    } else {
        // Usar siempre el ID del médico del token
        $requestedDoctorId = $doctorId;
        error_log("Médico solicitando sus propios horarios. ID: $doctorId");
    }
    
    try {
        $weekNumber = $_GET['weekNumber'];
        $year = $_GET['year'];
        $schedules = getWeeklySchedule($conn, $requestedDoctorId, $weekNumber, $year);
        ob_clean();
        echo json_encode(['success' => true, 'doctorId' => $requestedDoctorId, 'schedules' => $schedules]);
    } catch (Exception $e) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// Cerrar la conexión
$conn = null;
ob_end_flush();
?>