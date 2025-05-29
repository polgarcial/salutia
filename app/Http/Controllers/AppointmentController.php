<?php
/**
 * Appointment Controller
 * 
 * Handles all appointment-related operations including scheduling,
 * rescheduling, cancellation, and retrieval of appointments.
 */

class AppointmentController {
    private $db;
    
    public function __construct() {
        $this->db = getDbConnection();
    }
    
    /**
     * Get all appointments with optional filtering
     */
    public function getAll() {
        try {
            // Get query parameters for filtering
            $patientId = isset($_GET['patient_id']) ? $_GET['patient_id'] : null;
            $doctorId = isset($_GET['doctor_id']) ? $_GET['doctor_id'] : null;
            $status = isset($_GET['status']) ? $_GET['status'] : null;
            $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
            $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;
            
            // Build query
            $query = "
                SELECT a.*, 
                       p.first_name as patient_first_name, p.last_name as patient_last_name,
                       d.first_name as doctor_first_name, d.last_name as doctor_last_name,
                       d.specialty as doctor_specialty
                FROM appointments a
                JOIN users p ON a.patient_id = p.id
                JOIN users d ON a.doctor_id = d.id
                WHERE 1=1
            ";
            
            $params = [];
            
            // Add filters
            if ($patientId) {
                $query .= " AND a.patient_id = :patient_id";
                $params[':patient_id'] = $patientId;
            }
            
            if ($doctorId) {
                $query .= " AND a.doctor_id = :doctor_id";
                $params[':doctor_id'] = $doctorId;
            }
            
            if ($status) {
                $query .= " AND a.status = :status";
                $params[':status'] = $status;
            }
            
            if ($startDate) {
                $query .= " AND a.date >= :start_date";
                $params[':start_date'] = $startDate;
            }
            
            if ($endDate) {
                $query .= " AND a.date <= :end_date";
                $params[':end_date'] = $endDate;
            }
            
            // Order by date and time
            $query .= " ORDER BY a.date ASC, a.time ASC";
            
            // Prepare and execute query
            $stmt = $this->db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            $appointments = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $appointments]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to retrieve appointments: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Get a specific appointment by ID
     */
    public function get($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, 
                       p.first_name as patient_first_name, p.last_name as patient_last_name,
                       d.first_name as doctor_first_name, d.last_name as doctor_last_name,
                       d.specialty as doctor_specialty
                FROM appointments a
                JOIN users p ON a.patient_id = p.id
                JOIN users d ON a.doctor_id = d.id
                WHERE a.id = :id
            ");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $appointment = $stmt->fetch();
            
            if (!$appointment) {
                http_response_code(404);
                echo json_encode(['error' => 'Appointment not found']);
                return;
            }
            
            echo json_encode(['success' => true, 'data' => $appointment]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to retrieve appointment: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Create a new appointment
     */
    public function create() {
        try {
            // Get request body
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (!isset($data['patient_id']) || !isset($data['doctor_id']) || 
                !isset($data['date']) || !isset($data['time'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                return;
            }
            
            // Check if patient exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE id = :id AND role = 'patient'");
            $stmt->bindParam(':id', $data['patient_id']);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Patient not found']);
                return;
            }
            
            // Check if doctor exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE id = :id AND role = 'doctor'");
            $stmt->bindParam(':id', $data['doctor_id']);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Doctor not found']);
                return;
            }
            
            // Check doctor availability
            $dayOfWeek = date('l', strtotime($data['date']));
            $stmt = $this->db->prepare("
                SELECT * FROM doctor_availability 
                WHERE doctor_id = :doctor_id 
                AND day_of_week = :day_of_week
                AND :appointment_time BETWEEN start_time AND end_time
            ");
            $stmt->bindParam(':doctor_id', $data['doctor_id']);
            $stmt->bindParam(':day_of_week', $dayOfWeek);
            $stmt->bindParam(':appointment_time', $data['time']);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Doctor is not available at this time']);
                return;
            }
            
            // Check for conflicting appointments
            $stmt = $this->db->prepare("
                SELECT * FROM appointments 
                WHERE doctor_id = :doctor_id 
                AND date = :date
                AND (
                    (time <= :start_time AND ADDTIME(time, SEC_TO_TIME(duration * 60)) > :start_time)
                    OR
                    (time >= :start_time AND time < ADDTIME(:start_time, SEC_TO_TIME(:duration * 60)))
                )
                AND status != 'cancelled'
            ");
            $stmt->bindParam(':doctor_id', $data['doctor_id']);
            $stmt->bindParam(':date', $data['date']);
            $stmt->bindParam(':start_time', $data['time']);
            $stmt->bindValue(':duration', $data['duration'] ?? 30);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                http_response_code(409);
                echo json_encode(['error' => 'There is a conflicting appointment at this time']);
                return;
            }
            
            // Insert appointment
            $stmt = $this->db->prepare("
                INSERT INTO appointments (
                    patient_id, doctor_id, date, time, duration, status, reason, notes
                ) VALUES (
                    :patient_id, :doctor_id, :date, :time, :duration, :status, :reason, :notes
                )
            ");
            
            $stmt->bindParam(':patient_id', $data['patient_id']);
            $stmt->bindParam(':doctor_id', $data['doctor_id']);
            $stmt->bindParam(':date', $data['date']);
            $stmt->bindParam(':time', $data['time']);
            $stmt->bindValue(':duration', $data['duration'] ?? 30);
            $stmt->bindValue(':status', $data['status'] ?? 'scheduled');
            $stmt->bindValue(':reason', $data['reason'] ?? null);
            $stmt->bindValue(':notes', $data['notes'] ?? null);
            
            $stmt->execute();
            $appointmentId = $this->db->lastInsertId();
            
            // Create notification for patient
            $this->createNotification(
                $data['patient_id'],
                'New Appointment',
                'You have a new appointment scheduled on ' . $data['date'] . ' at ' . $data['time'],
                'appointment'
            );
            
            // Create notification for doctor
            $this->createNotification(
                $data['doctor_id'],
                'New Appointment',
                'You have a new appointment scheduled on ' . $data['date'] . ' at ' . $data['time'],
                'appointment'
            );
            
            // Return success response
            http_response_code(201);
            echo json_encode([
                'success' => true, 
                'message' => 'Appointment created successfully',
                'data' => ['id' => $appointmentId]
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create appointment: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Update an existing appointment
     */
    public function update($id) {
        try {
            // Get request body
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Check if appointment exists
            $stmt = $this->db->prepare("SELECT * FROM appointments WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $appointment = $stmt->fetch();
            
            if (!$appointment) {
                http_response_code(404);
                echo json_encode(['error' => 'Appointment not found']);
                return;
            }
            
            // Build update query
            $query = "UPDATE appointments SET ";
            $params = [];
            
            // Add fields to update
            if (isset($data['date'])) {
                $query .= "date = :date, ";
                $params[':date'] = $data['date'];
            }
            
            if (isset($data['time'])) {
                $query .= "time = :time, ";
                $params[':time'] = $data['time'];
            }
            
            if (isset($data['duration'])) {
                $query .= "duration = :duration, ";
                $params[':duration'] = $data['duration'];
            }
            
            if (isset($data['status'])) {
                $query .= "status = :status, ";
                $params[':status'] = $data['status'];
            }
            
            if (isset($data['reason'])) {
                $query .= "reason = :reason, ";
                $params[':reason'] = $data['reason'];
            }
            
            if (isset($data['notes'])) {
                $query .= "notes = :notes, ";
                $params[':notes'] = $data['notes'];
            }
            
            // Remove trailing comma and space
            $query = rtrim($query, ", ");
            
            // Add WHERE clause
            $query .= " WHERE id = :id";
            $params[':id'] = $id;
            
            // Execute update if there are fields to update
            if (count($params) > 1) { // More than just the ID parameter
                $stmt = $this->db->prepare($query);
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                $stmt->execute();
                
                // Create notifications if status changed
                if (isset($data['status']) && $data['status'] != $appointment['status']) {
                    $statusMessage = '';
                    
                    switch ($data['status']) {
                        case 'completed':
                            $statusMessage = 'Your appointment has been marked as completed';
                            break;
                        case 'cancelled':
                            $statusMessage = 'Your appointment has been cancelled';
                            break;
                        case 'scheduled':
                            $statusMessage = 'Your appointment has been rescheduled';
                            break;
                        default:
                            $statusMessage = 'Your appointment status has been updated to ' . $data['status'];
                    }
                    
                    // Notify patient
                    $this->createNotification(
                        $appointment['patient_id'],
                        'Appointment Update',
                        $statusMessage,
                        'appointment'
                    );
                    
                    // Notify doctor
                    $this->createNotification(
                        $appointment['doctor_id'],
                        'Appointment Update',
                        $statusMessage,
                        'appointment'
                    );
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Appointment updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => true, 
                    'message' => 'No fields to update'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update appointment: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Delete (cancel) an appointment
     */
    public function delete($id) {
        try {
            // Check if appointment exists
            $stmt = $this->db->prepare("SELECT * FROM appointments WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $appointment = $stmt->fetch();
            
            if (!$appointment) {
                http_response_code(404);
                echo json_encode(['error' => 'Appointment not found']);
                return;
            }
            
            // Update appointment status to cancelled
            $stmt = $this->db->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            // Create notifications
            $this->createNotification(
                $appointment['patient_id'],
                'Appointment Cancelled',
                'Your appointment on ' . $appointment['date'] . ' at ' . $appointment['time'] . ' has been cancelled',
                'appointment'
            );
            
            $this->createNotification(
                $appointment['doctor_id'],
                'Appointment Cancelled',
                'Your appointment on ' . $appointment['date'] . ' at ' . $appointment['time'] . ' has been cancelled',
                'appointment'
            );
            
            echo json_encode([
                'success' => true, 
                'message' => 'Appointment cancelled successfully'
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to cancel appointment: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Create a notification
     */
    private function createNotification($userId, $title, $message, $type) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, title, message, type)
                VALUES (:user_id, :title, :message, :type)
            ");
            
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':message', $message);
            $stmt->bindParam(':type', $type);
            
            $stmt->execute();
        } catch (PDOException $e) {
            // Log error but don't stop execution
            error_log("Failed to create notification: " . $e->getMessage());
        }
    }
    
    /**
     * Get available time slots for a doctor on a specific date
     */
    public function getAvailableTimeSlots() {
        try {
            // Get query parameters
            $doctorId = isset($_GET['doctor_id']) ? $_GET['doctor_id'] : null;
            $date = isset($_GET['date']) ? $_GET['date'] : null;
            
            // Validate required parameters
            if (!$doctorId || !$date) {
                http_response_code(400);
                echo json_encode(['error' => 'Doctor ID and date are required']);
                return;
            }
            
            // Check if doctor exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE id = :id AND role = 'doctor'");
            $stmt->bindParam(':id', $doctorId);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Doctor not found']);
                return;
            }
            
            // Get doctor's availability for the day of week
            $dayOfWeek = date('l', strtotime($date));
            $stmt = $this->db->prepare("
                SELECT start_time, end_time 
                FROM doctor_availability 
                WHERE doctor_id = :doctor_id 
                AND day_of_week = :day_of_week
            ");
            $stmt->bindParam(':doctor_id', $doctorId);
            $stmt->bindParam(':day_of_week', $dayOfWeek);
            $stmt->execute();
            
            $availabilities = $stmt->fetchAll();
            
            if (empty($availabilities)) {
                echo json_encode(['success' => true, 'data' => []]);
                return;
            }
            
            // Get booked appointments for the date
            $stmt = $this->db->prepare("
                SELECT time, duration 
                FROM appointments 
                WHERE doctor_id = :doctor_id 
                AND date = :date 
                AND status != 'cancelled'
            ");
            $stmt->bindParam(':doctor_id', $doctorId);
            $stmt->bindParam(':date', $date);
            $stmt->execute();
            
            $bookedAppointments = $stmt->fetchAll();
            
            // Generate time slots (assuming 30-minute slots)
            $timeSlots = [];
            $slotDuration = 30; // minutes
            
            foreach ($availabilities as $availability) {
                $startTime = strtotime($availability['start_time']);
                $endTime = strtotime($availability['end_time']);
                
                // Generate slots
                for ($time = $startTime; $time < $endTime; $time += $slotDuration * 60) {
                    $slotStart = date('H:i:s', $time);
                    $isAvailable = true;
                    
                    // Check if slot conflicts with booked appointments
                    foreach ($bookedAppointments as $appointment) {
                        $appointmentStart = strtotime($appointment['time']);
                        $appointmentEnd = $appointmentStart + ($appointment['duration'] * 60);
                        $slotEnd = $time + ($slotDuration * 60);
                        
                        if (($time >= $appointmentStart && $time < $appointmentEnd) ||
                            ($slotEnd > $appointmentStart && $slotEnd <= $appointmentEnd) ||
                            ($time <= $appointmentStart && $slotEnd >= $appointmentEnd)) {
                            $isAvailable = false;
                            break;
                        }
                    }
                    
                    if ($isAvailable) {
                        $timeSlots[] = $slotStart;
                    }
                }
            }
            
            echo json_encode(['success' => true, 'data' => $timeSlots]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to retrieve available time slots: ' . $e->getMessage()]);
        }
    }
}
