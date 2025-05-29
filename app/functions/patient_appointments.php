<?php
/**
 * Funciones para gestionar las citas de pacientes
 */

/**
 * Obtiene las citas de un paciente específico
 * 
 * @param int $patientId ID del paciente
 * @param string $status Filtro de estado: 'all', 'upcoming', 'past'
 * @param PDO $db Conexión a la base de datos (opcional)
 * @return array Arreglo con las citas del paciente
 */
function getPatientAppointments($patientId, $status = 'all', $db = null) {
    // Si no se proporciona una conexión, crear una nueva
    if ($db === null) {
        require_once __DIR__ . '/../../config/database.php';
        $db = getDbConnection();
    }
    
    // Validar parámetros
    $patientId = intval($patientId);
    if ($patientId <= 0) {
        throw new InvalidArgumentException('ID de paciente inválido');
    }
    
    // Construir consulta SQL
    $sql = "
        SELECT a.id, a.appointment_date, a.start_time, a.end_time, a.reason, a.status, a.notes,
               d.name as doctor_name, d.id as doctor_id, ds.specialty
        FROM appointments a
        LEFT JOIN doctors d ON a.doctor_id = d.id
        LEFT JOIN doctor_specialties ds ON a.doctor_id = ds.doctor_id
        WHERE a.patient_id = :patient_id
    ";
    
    // Filtrar por estado si se especifica
    if ($status === 'upcoming') {
        $sql .= " AND (a.appointment_date > CURDATE() OR (a.appointment_date = CURDATE() AND a.start_time >= CURTIME()))
                  AND a.status IN ('pending', 'confirmed', 'accepted')
                  ORDER BY a.appointment_date ASC, a.start_time ASC";
    } elseif ($status === 'past') {
        $sql .= " AND (a.appointment_date < CURDATE() OR (a.appointment_date = CURDATE() AND a.start_time < CURTIME()))
                  OR a.status IN ('completed', 'cancelled', 'rejected')
                  ORDER BY a.appointment_date DESC, a.start_time DESC";
    } else {
        $sql .= " ORDER BY a.appointment_date DESC, a.start_time DESC";
    }
    
    // Preparar y ejecutar consulta
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':patient_id', $patientId, PDO::PARAM_INT);
    $stmt->execute();
    
    // Obtener resultados
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear fechas y horas para mejor visualización
    foreach ($appointments as &$appointment) {
        // Convertir fechas al formato español (DD/MM/YYYY)
        if (isset($appointment['appointment_date'])) {
            $date = $appointment['appointment_date'];
            if (strpos($date, '-') !== false) {
                $date_parts = explode('-', $date);
                if (count($date_parts) === 3) {
                    $appointment['formatted_date'] = $date_parts[2] . '/' . $date_parts[1] . '/' . $date_parts[0];
                } else {
                    $appointment['formatted_date'] = $date;
                }
            } else {
                $appointment['formatted_date'] = $date;
            }
        }
        
        // Convertir estado a texto legible
        switch ($appointment['status']) {
            case 'pending':
                $appointment['status_text'] = 'Pendiente';
                break;
            case 'confirmed':
            case 'accepted':
                $appointment['status_text'] = 'Confirmada';
                break;
            case 'completed':
                $appointment['status_text'] = 'Completada';
                break;
            case 'cancelled':
                $appointment['status_text'] = 'Cancelada';
                break;
            case 'rejected':
                $appointment['status_text'] = 'Rechazada';
                break;
            default:
                $appointment['status_text'] = ucfirst($appointment['status']);
        }
    }
    
    return $appointments;
}

/**
 * Obtiene una cita específica por su ID
 * 
 * @param int $appointmentId ID de la cita
 * @param int $patientId ID del paciente (opcional, para verificar permisos)
 * @param PDO $db Conexión a la base de datos (opcional)
 * @return array|false Datos de la cita o false si no se encuentra
 */
function getPatientAppointmentById($appointmentId, $patientId = null, $db = null) {
    // Si no se proporciona una conexión, crear una nueva
    if ($db === null) {
        require_once __DIR__ . '/../../config/database.php';
        $db = getDbConnection();
    }
    
    // Validar parámetros
    $appointmentId = intval($appointmentId);
    if ($appointmentId <= 0) {
        throw new InvalidArgumentException('ID de cita inválido');
    }
    
    // Construir consulta SQL
    $sql = "
        SELECT a.id, a.patient_id, a.doctor_id, a.appointment_date, a.start_time, a.end_time, 
               a.reason, a.status, a.notes, a.created_at,
               d.name as doctor_name, ds.specialty,
               p.name as patient_name, p.email as patient_email
        FROM appointments a
        LEFT JOIN doctors d ON a.doctor_id = d.id
        LEFT JOIN doctor_specialties ds ON a.doctor_id = ds.doctor_id
        LEFT JOIN patients p ON a.patient_id = p.id
        WHERE a.id = :appointment_id
    ";
    
    // Si se proporciona un ID de paciente, verificar que la cita pertenezca a ese paciente
    if ($patientId !== null) {
        $sql .= " AND a.patient_id = :patient_id";
    }
    
    // Preparar y ejecutar consulta
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':appointment_id', $appointmentId, PDO::PARAM_INT);
    
    if ($patientId !== null) {
        $patientId = intval($patientId);
        $stmt->bindParam(':patient_id', $patientId, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    
    // Obtener resultado
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($appointment) {
        // Formatear fecha al formato español (DD/MM/YYYY)
        if (isset($appointment['appointment_date'])) {
            $date = $appointment['appointment_date'];
            if (strpos($date, '-') !== false) {
                $date_parts = explode('-', $date);
                if (count($date_parts) === 3) {
                    $appointment['formatted_date'] = $date_parts[2] . '/' . $date_parts[1] . '/' . $date_parts[0];
                } else {
                    $appointment['formatted_date'] = $date;
                }
            } else {
                $appointment['formatted_date'] = $date;
            }
        }
        
        // Convertir estado a texto legible
        switch ($appointment['status']) {
            case 'pending':
                $appointment['status_text'] = 'Pendiente';
                break;
            case 'confirmed':
            case 'accepted':
                $appointment['status_text'] = 'Confirmada';
                break;
            case 'completed':
                $appointment['status_text'] = 'Completada';
                break;
            case 'cancelled':
                $appointment['status_text'] = 'Cancelada';
                break;
            case 'rejected':
                $appointment['status_text'] = 'Rechazada';
                break;
            default:
                $appointment['status_text'] = ucfirst($appointment['status']);
        }
    }
    
    return $appointment;
}
