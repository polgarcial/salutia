<?php
/**
 * API Endpoint: Gestión de pacientes
 * 
 * Este endpoint permite a los médicos gestionar sus pacientes:
 * - Obtener lista de pacientes
 * - Añadir nuevos pacientes
 * - Actualizar información de pacientes
 * - Ver detalles de un paciente específico
 */

// Permitir solicitudes desde cualquier origen (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Para solicitudes OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir configuración de base de datos
require_once '../config/database.php';

// Obtener conexión a la base de datos
$db = getDbConnection();

// Obtener el ID del médico de la solicitud (en producción, esto vendría del token JWT)
$doctor_id = isset($_GET['doctor_id']) ? $_GET['doctor_id'] : null;

// Verificar si se proporcionó un ID de paciente específico
$patient_id = isset($_GET['patient_id']) ? $_GET['patient_id'] : null;

// Manejar diferentes tipos de solicitudes
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Si se proporciona un ID de paciente, obtener detalles de ese paciente
        if ($patient_id) {
            getPatientDetails($db, $patient_id, $doctor_id);
        } else {
            // De lo contrario, obtener lista de pacientes
            getPatientsList($db, $doctor_id);
        }
        break;
        
    case 'POST':
        // Añadir un nuevo paciente
        addPatient($db, $doctor_id);
        break;
        
    case 'PUT':
        // Actualizar información de un paciente existente
        if (!$patient_id) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Se requiere ID de paciente"]);
            exit;
        }
        updatePatient($db, $patient_id, $doctor_id);
        break;
        
    case 'DELETE':
        // Eliminar un paciente (o desasociarlo del médico)
        if (!$patient_id) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Se requiere ID de paciente"]);
            exit;
        }
        deletePatient($db, $patient_id, $doctor_id);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Método no permitido"]);
        break;
}

/**
 * Obtener lista de pacientes para un médico
 */
function getPatientsList($db, $doctor_id) {
    try {
        // En un sistema real, filtraríamos por el doctor_id
        // Por ahora, devolvemos todos los pacientes para pruebas
        $sql = "SELECT id, name, email, phone, date_of_birth, notes FROM users WHERE role = 'patient'";
        $params = [];
        
        // Si se proporciona un doctor_id, filtrar por pacientes de ese médico
        if ($doctor_id) {
            $sql = "SELECT u.id, u.name, u.email, u.phone, u.date_of_birth, u.notes 
                    FROM users u
                    JOIN doctor_patients dp ON u.id = dp.patient_id
                    WHERE dp.doctor_id = ? AND u.role = 'patient'";
            $params = [$doctor_id];
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        $patients = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Obtener la última visita y próxima cita para cada paciente
            $lastVisit = getLastVisit($db, $row['id']);
            $nextAppointment = getNextAppointment($db, $row['id']);
            
            $patients[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'email' => $row['email'],
                'phone' => $row['phone'] ?? '',
                'dob' => $row['date_of_birth'] ?? '',
                'notes' => $row['notes'] ?? '',
                'lastVisit' => $lastVisit,
                'nextAppointment' => $nextAppointment
            ];
        }
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "patients" => $patients
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Error al obtener pacientes: " . $e->getMessage()
        ]);
    }
}

/**
 * Obtener detalles de un paciente específico
 */
function getPatientDetails($db, $patient_id, $doctor_id) {
    try {
        // Obtener información básica del paciente
        $sql = "SELECT id, name, email, phone, date_of_birth, notes FROM users WHERE id = ? AND role = 'patient'";
        $stmt = $db->prepare($sql);
        $stmt->execute([$patient_id]);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Paciente no encontrado"]);
            exit;
        }
        
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Obtener historial de citas del paciente
        $appointments = getPatientAppointments($db, $patient_id, $doctor_id);
        
        // Obtener última visita y próxima cita
        $lastVisit = getLastVisit($db, $patient_id);
        $nextAppointment = getNextAppointment($db, $patient_id);
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "patient" => [
                'id' => $patient['id'],
                'name' => $patient['name'],
                'email' => $patient['email'],
                'phone' => $patient['phone'] ?? '',
                'dob' => $patient['date_of_birth'] ?? '',
                'notes' => $patient['notes'] ?? '',
                'lastVisit' => $lastVisit,
                'nextAppointment' => $nextAppointment,
                'appointments' => $appointments
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Error al obtener detalles del paciente: " . $e->getMessage()
        ]);
    }
}

/**
 * Añadir un nuevo paciente
 */
function addPatient($db, $doctor_id) {
    try {
        // Obtener datos del cuerpo de la solicitud
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validar datos requeridos
        if (!isset($data['name']) || !isset($data['email'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Faltan datos requeridos"]);
            exit;
        }
        
        // Verificar si el email ya existe
        $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$data['email']]);
        
        if ($checkStmt->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Ya existe un usuario con ese email"]);
            exit;
        }
        
        // Generar contraseña aleatoria para el paciente
        $password = bin2hex(random_bytes(4)); // 8 caracteres hexadecimales
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insertar nuevo usuario con rol de paciente
        $sql = "INSERT INTO users (name, email, password, role, phone, date_of_birth, notes) 
                VALUES (?, ?, ?, 'patient', ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['email'],
            $hashedPassword,
            $data['phone'] ?? null,
            $data['dob'] ?? null,
            $data['notes'] ?? null
        ]);
        
        $patient_id = $db->lastInsertId();
        
        // Si se proporciona un doctor_id, asociar el paciente con el médico
        if ($doctor_id) {
            $assocSql = "INSERT INTO doctor_patients (doctor_id, patient_id) VALUES (?, ?)";
            $assocStmt = $db->prepare($assocSql);
            $assocStmt->execute([$doctor_id, $patient_id]);
        }
        
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Paciente añadido correctamente",
            "patient_id" => $patient_id,
            "temp_password" => $password // En producción, esto se enviaría por email
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Error al añadir paciente: " . $e->getMessage()
        ]);
    }
}

/**
 * Actualizar información de un paciente
 */
function updatePatient($db, $patient_id, $doctor_id) {
    try {
        // Obtener datos del cuerpo de la solicitud
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validar datos requeridos
        if (empty($data)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "No se proporcionaron datos para actualizar"]);
            exit;
        }
        
        // Construir consulta de actualización dinámicamente
        $updateFields = [];
        $params = [];
        
        if (isset($data['name'])) {
            $updateFields[] = "name = ?";
            $params[] = $data['name'];
        }
        
        if (isset($data['email'])) {
            // Verificar si el nuevo email ya existe para otro usuario
            $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $checkStmt->execute([$data['email'], $patient_id]);
            
            if ($checkStmt->rowCount() > 0) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Ya existe otro usuario con ese email"]);
                exit;
            }
            
            $updateFields[] = "email = ?";
            $params[] = $data['email'];
        }
        
        if (isset($data['phone'])) {
            $updateFields[] = "phone = ?";
            $params[] = $data['phone'];
        }
        
        if (isset($data['dob'])) {
            $updateFields[] = "date_of_birth = ?";
            $params[] = $data['dob'];
        }
        
        if (isset($data['notes'])) {
            $updateFields[] = "notes = ?";
            $params[] = $data['notes'];
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "No se proporcionaron campos válidos para actualizar"]);
            exit;
        }
        
        // Añadir patient_id al final de los parámetros
        $params[] = $patient_id;
        
        $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Paciente no encontrado o no se realizaron cambios"]);
            exit;
        }
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Información del paciente actualizada correctamente"
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Error al actualizar paciente: " . $e->getMessage()
        ]);
    }
}

/**
 * Eliminar un paciente o desasociarlo de un médico
 */
function deletePatient($db, $patient_id, $doctor_id) {
    try {
        // Si se proporciona un doctor_id, solo desasociar el paciente del médico
        if ($doctor_id) {
            $sql = "DELETE FROM doctor_patients WHERE doctor_id = ? AND patient_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$doctor_id, $patient_id]);
            
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Paciente desasociado correctamente del médico"
            ]);
        } else {
            // Si no hay doctor_id, eliminar completamente el paciente
            // Primero eliminar asociaciones
            $assocSql = "DELETE FROM doctor_patients WHERE patient_id = ?";
            $assocStmt = $db->prepare($assocSql);
            $assocStmt->execute([$patient_id]);
            
            // Luego eliminar citas
            $appointmentsSql = "DELETE FROM appointments WHERE patient_id = ?";
            $appointmentsStmt = $db->prepare($appointmentsSql);
            $appointmentsStmt->execute([$patient_id]);
            
            // Finalmente eliminar el usuario
            $sql = "DELETE FROM users WHERE id = ? AND role = 'patient'";
            $stmt = $db->prepare($sql);
            $stmt->execute([$patient_id]);
            
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "Paciente no encontrado"]);
                exit;
            }
            
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Paciente eliminado correctamente"
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Error al eliminar paciente: " . $e->getMessage()
        ]);
    }
}

/**
 * Obtener historial de citas de un paciente
 */
function getPatientAppointments($db, $patient_id, $doctor_id = null) {
    try {
        // Registrar para depuración
        error_log("Obteniendo citas para paciente ID: $patient_id" . ($doctor_id ? ", doctor ID: $doctor_id" : ""));
        
        $sql = "SELECT a.id, a.doctor_id, a.appointment_date, a.start_time, a.end_time, a.reason, a.status, a.created_at,
                       u.name as doctor_name 
                FROM appointments a
                LEFT JOIN users u ON a.doctor_id = u.id 
                WHERE a.patient_id = ?";
        $params = [$patient_id];
        
        // Si se proporciona un doctor_id, filtrar por citas con ese médico
        if ($doctor_id) {
            $sql .= " AND a.doctor_id = ?";
            $params[] = $doctor_id;
        }
        
        $sql .= " ORDER BY a.appointment_date DESC, a.start_time DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        $appointments = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Formatear hora para mostrar solo HH:MM
            $timeFormatted = substr($row['start_time'], 0, 5);
            
            $appointments[] = [
                'id' => $row['id'],
                'doctor_id' => $row['doctor_id'],
                'doctor_name' => $row['doctor_name'],
                'date' => $row['appointment_date'],
                'time' => $timeFormatted,
                'reason' => $row['reason'],
                'status' => $row['status'],
                'created_at' => $row['created_at']
            ];
        }
        
        error_log("Se encontraron " . count($appointments) . " citas para el paciente ID: $patient_id");
        return $appointments;
    } catch (PDOException $e) {
        error_log("Error al obtener citas del paciente: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener la fecha de la última visita de un paciente
 */
function getLastVisit($db, $patient_id) {
    try {
        $sql = "SELECT appointment_date 
                FROM appointments 
                WHERE patient_id = ? AND status = 'completed' 
                ORDER BY appointment_date DESC, appointment_time DESC 
                LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$patient_id]);
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['appointment_date'];
        }
        
        return null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Obtener la próxima cita de un paciente
 */
function getNextAppointment($db, $patient_id) {
    try {
        // Registrar para depuración
        error_log("Buscando próxima cita para paciente ID: $patient_id");
        
        // Obtener la fecha actual
        $today = date('Y-m-d');
        error_log("Fecha actual: $today");
        
        // Consulta SQL mejorada para obtener la próxima cita pendiente
        $sql = "SELECT a.id, a.appointment_date, a.start_time, a.end_time, a.reason, a.status, 
                       u.name as doctor_name
                FROM appointments a
                LEFT JOIN users u ON a.doctor_id = u.id
                WHERE a.patient_id = ? AND a.status = 'pending' AND a.appointment_date >= ? 
                ORDER BY a.appointment_date ASC, a.start_time ASC 
                LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$patient_id, $today]);
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Próxima cita encontrada: " . $row['appointment_date'] . " " . $row['start_time'] . " con " . ($row['doctor_name'] ?? 'Doctor desconocido'));
            
            // Devolver la fecha de la cita para mantener compatibilidad
            return $row['appointment_date'];
        } else {
            // Verificar si hay citas para este paciente (para depuración)
            $checkSql = "SELECT COUNT(*) as total FROM appointments WHERE patient_id = ?";
            $checkStmt = $db->prepare($checkSql);
            $checkStmt->execute([$patient_id]);
            $totalCitas = $checkStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            error_log("No se encontró próxima cita para el paciente ID: $patient_id. Total de citas del paciente: $totalCitas");
            return null;
        }
    } catch (PDOException $e) {
        error_log("Error al obtener próxima cita: " . $e->getMessage());
        return null;
    }
}
?>
