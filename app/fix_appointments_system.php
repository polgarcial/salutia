<?php
/**
 * Script para corregir el sistema de citas médicas de Salutia
 * Este script corrige la estructura de la base de datos y actualiza los archivos necesarios
 */

// Configuración de la salida
header("Content-Type: text/html; charset=UTF-8");
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Reparación del Sistema de Citas - Salutia</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        h1, h2, h3 {
            color: #0d6efd;
        }
        .success {
            color: #198754;
            font-weight: bold;
        }
        .warning {
            color: #fd7e14;
            font-weight: bold;
        }
        .error {
            color: #dc3545;
            font-weight: bold;
        }
        .code-block {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin: 15px 0;
            overflow-x: auto;
            font-family: monospace;
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            display: inline-block;
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <h1>Reparación del Sistema de Citas Médicas - Salutia</h1>
    <p>Este script corregirá los problemas en el sistema de citas médicas.</p>
    <hr>";

// Función para mostrar mensajes
function printMessage($message, $type = 'info') {
    echo "<p class='$type'>$message</p>";
}

// Función para ejecutar consultas SQL de forma segura
function executeQuery($db, $sql, $params = []) {
    try {
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt;
    } catch (PDOException $e) {
        printMessage("Error al ejecutar la consulta: " . $e->getMessage(), 'error');
        return false;
    }
}

try {
    // Paso 1: Conectar a la base de datos
    printMessage("Conectando a la base de datos...");
    
    require_once __DIR__ . '/../backend/config/database_class.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("No se pudo conectar a la base de datos");
    }
    
    printMessage("Conexión a la base de datos establecida correctamente", 'success');
    
    // Paso 2: Verificar si existe la tabla appointments
    $stmt = $db->query("SHOW TABLES LIKE 'appointments'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        printMessage("La tabla 'appointments' no existe. Creando tabla...", 'warning');
        
        // Crear la tabla appointments
        $sql = "CREATE TABLE appointments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            doctor_id INT NOT NULL,
            appointment_date DATE NOT NULL,
            appointment_time VARCHAR(10) NOT NULL,
            notes TEXT,
            status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $db->exec($sql);
        printMessage("Tabla 'appointments' creada correctamente", 'success');
    } else {
        printMessage("La tabla 'appointments' existe", 'success');
        
        // Verificar la estructura de la tabla
        $stmt = $db->query("DESCRIBE appointments");
        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[$row['Field']] = $row;
        }
        
        // Verificar si existen las columnas date y time
        if (isset($columns['date']) && !isset($columns['appointment_date'])) {
            printMessage("Renombrando columna 'date' a 'appointment_date'...", 'warning');
            $db->exec("ALTER TABLE appointments CHANGE COLUMN `date` `appointment_date` DATE NOT NULL");
            printMessage("Columna 'date' renombrada a 'appointment_date'", 'success');
        }
        
        if (isset($columns['time']) && !isset($columns['appointment_time'])) {
            printMessage("Renombrando columna 'time' a 'appointment_time'...", 'warning');
            $db->exec("ALTER TABLE appointments CHANGE COLUMN `time` `appointment_time` VARCHAR(10) NOT NULL");
            printMessage("Columna 'time' renombrada a 'appointment_time'", 'success');
        }
        
        // Verificar si existe la columna user_id en lugar de patient_id
        if (isset($columns['user_id']) && !isset($columns['patient_id'])) {
            printMessage("Renombrando columna 'user_id' a 'patient_id'...", 'warning');
            $db->exec("ALTER TABLE appointments CHANGE COLUMN `user_id` `patient_id` INT NOT NULL");
            printMessage("Columna 'user_id' renombrada a 'patient_id'", 'success');
        }
        
        // Añadir columnas que faltan
        $requiredColumns = [
            'patient_id' => 'INT NOT NULL',
            'doctor_id' => 'INT NOT NULL',
            'appointment_date' => 'DATE NOT NULL',
            'appointment_time' => 'VARCHAR(10) NOT NULL',
            'notes' => 'TEXT',
            'status' => "ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending'",
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ];
        
        foreach ($requiredColumns as $column => $type) {
            if (!isset($columns[$column])) {
                printMessage("Añadiendo columna '$column'...", 'warning');
                $db->exec("ALTER TABLE appointments ADD COLUMN `$column` $type");
                printMessage("Columna '$column' añadida correctamente", 'success');
            }
        }
    }
    
    // Paso 3: Verificar si existe la tabla doctors
    $stmt = $db->query("SHOW TABLES LIKE 'doctors'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        printMessage("La tabla 'doctors' no existe. Creando tabla...", 'warning');
        
        // Crear la tabla doctors
        $sql = "CREATE TABLE doctors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            specialty VARCHAR(50) NOT NULL,
            email VARCHAR(100) UNIQUE,
            phone VARCHAR(20),
            active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $db->exec($sql);
        printMessage("Tabla 'doctors' creada correctamente", 'success');
        
        // Insertar médicos de ejemplo
        $sql = "INSERT INTO doctors (name, specialty, email, phone) VALUES
            ('Dr. Joan Metge', 'Medicina General', 'joan.metge@salutia.com', '123456789'),
            ('Dra. Ana Cardióloga', 'Cardiología', 'ana.cardio@salutia.com', '234567890'),
            ('Dr. Pedro Dermatologo', 'Dermatología', 'pedro.derm@salutia.com', '345678901'),
            ('Dra. María Pediatra', 'Pediatría', 'maria.pediatra@salutia.com', '456789012'),
            ('Dra. Laura Ginecóloga', 'Ginecología', 'laura.gineco@salutia.com', '567890123');";
        
        $db->exec($sql);
        printMessage("Médicos de ejemplo insertados correctamente", 'success');
    } else {
        printMessage("La tabla 'doctors' existe", 'success');
    }
    
    // Paso 4: Verificar si existe la tabla doctor_availability
    $stmt = $db->query("SHOW TABLES LIKE 'doctor_availability'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        printMessage("La tabla 'doctor_availability' no existe. Creando tabla...", 'warning');
        
        // Crear la tabla doctor_availability
        $sql = "CREATE TABLE doctor_availability (
            id INT AUTO_INCREMENT PRIMARY KEY,
            doctor_id INT NOT NULL,
            date DATE NOT NULL,
            time_slot VARCHAR(10) NOT NULL,
            is_available BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
            UNIQUE KEY unique_doctor_slot (doctor_id, date, time_slot)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $db->exec($sql);
        printMessage("Tabla 'doctor_availability' creada correctamente", 'success');
        
        // Generar disponibilidad para los próximos 30 días
        printMessage("Generando disponibilidad para los médicos...");
        
        // Obtener todos los médicos
        $stmt = $db->query("SELECT id FROM doctors WHERE active = 1");
        $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Generar horarios para los próximos 30 días
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+30 days'));
        $timeSlots = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', 
                      '16:00', '16:30', '17:00', '17:30', '18:00', '18:30'];
        
        $insertAvailability = $db->prepare("
            INSERT INTO doctor_availability (doctor_id, date, time_slot, is_available)
            VALUES (:doctor_id, :date, :time_slot, 1)
            ON DUPLICATE KEY UPDATE is_available = 1
        ");
        
        $currentDate = $startDate;
        $count = 0;
        
        while ($currentDate <= $endDate) {
            $dayOfWeek = date('w', strtotime($currentDate));
            
            // Saltar fines de semana (0 = domingo, 6 = sábado)
            if ($dayOfWeek != 0 && $dayOfWeek != 6) {
                foreach ($doctors as $doctor) {
                    foreach ($timeSlots as $timeSlot) {
                        $insertAvailability->execute([
                            ':doctor_id' => $doctor['id'],
                            ':date' => $currentDate,
                            ':time_slot' => $timeSlot
                        ]);
                        $count++;
                    }
                }
            }
            
            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }
        
        printMessage("Se generaron $count horarios disponibles para los médicos", 'success');
    } else {
        printMessage("La tabla 'doctor_availability' existe", 'success');
    }
    
    // Paso 5: Crear o actualizar los archivos API necesarios
    printMessage("Actualizando archivos API...");
    
    // Archivo API para obtener citas
    $getAppointmentsApi = 'backend/api/get_appointments.php';
    $getAppointmentsContent = '<?php
// API para obtener las citas de un usuario
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Configuración de la base de datos
require_once "../config/database_class.php";
$database = new Database();
$db = $database->getConnection();

// Obtener parámetros
$userId = isset($_GET["user_id"]) ? intval($_GET["user_id"]) : 0;
$status = isset($_GET["status"]) ? $_GET["status"] : null;

if ($userId <= 0) {
    echo json_encode(["success" => false, "message" => "ID de usuario no válido"]);
    exit;
}

try {
    // Consulta base
    $sql = "
        SELECT a.*, 
               d.name as doctor_name, 
               d.specialty as doctor_specialty
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        WHERE a.patient_id = :user_id
    ";
    
    $params = [":user_id" => $userId];
    
    // Filtrar por estado si se proporciona
    if ($status && $status !== "all") {
        $sql .= " AND a.status = :status";
        $params[":status"] = $status;
    }
    
    // Ordenar por fecha y hora
    $sql .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC";
    
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "success" => true,
        "appointments" => $appointments
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error al obtener las citas: " . $e->getMessage()
    ]);
}
?>';

    file_put_contents($getAppointmentsApi, $getAppointmentsContent);
    printMessage("Archivo API para obtener citas actualizado: $getAppointmentsApi", 'success');
    
    // Archivo API para crear citas
    $createAppointmentApi = 'backend/api/create_appointment.php';
    $createAppointmentContent = '<?php
// API para crear una nueva cita
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Manejar solicitudes OPTIONS (preflight)
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

// Verificar método de solicitud
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

// Configuración de la base de datos
require_once "../config/database_class.php";
$database = new Database();
$db = $database->getConnection();

// Obtener datos enviados
$data = json_decode(file_get_contents("php://input"), true);

// Validar datos requeridos
if (!isset($data["patient_id"]) || !isset($data["doctor_id"]) || 
    !isset($data["appointment_date"]) || !isset($data["appointment_time"])) {
    echo json_encode(["success" => false, "message" => "Faltan datos requeridos"]);
    exit;
}

try {
    // Verificar disponibilidad del médico
    $checkAvailability = $db->prepare("
        SELECT * FROM doctor_availability 
        WHERE doctor_id = :doctor_id 
        AND date = :date 
        AND time_slot = :time_slot 
        AND is_available = 1
    ");
    
    $checkAvailability->bindParam(":doctor_id", $data["doctor_id"]);
    $checkAvailability->bindParam(":date", $data["appointment_date"]);
    $checkAvailability->bindParam(":time_slot", $data["appointment_time"]);
    $checkAvailability->execute();
    
    if ($checkAvailability->rowCount() === 0) {
        echo json_encode(["success" => false, "message" => "El horario seleccionado no está disponible"]);
        exit;
    }
    
    // Verificar si ya existe una cita para ese médico en ese horario
    $checkExisting = $db->prepare("
        SELECT * FROM appointments 
        WHERE doctor_id = :doctor_id 
        AND appointment_date = :date 
        AND appointment_time = :time 
        AND status != \'cancelled\'
    ");
    
    $checkExisting->bindParam(":doctor_id", $data["doctor_id"]);
    $checkExisting->bindParam(":date", $data["appointment_date"]);
    $checkExisting->bindParam(":time", $data["appointment_time"]);
    $checkExisting->execute();
    
    if ($checkExisting->rowCount() > 0) {
        echo json_encode(["success" => false, "message" => "Ya existe una cita para este horario"]);
        exit;
    }
    
    // Crear la cita
    $insertAppointment = $db->prepare("
        INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, notes, status)
        VALUES (:patient_id, :doctor_id, :date, :time, :notes, \'pending\')
    ");
    
    $insertAppointment->bindParam(":patient_id", $data["patient_id"]);
    $insertAppointment->bindParam(":doctor_id", $data["doctor_id"]);
    $insertAppointment->bindParam(":date", $data["appointment_date"]);
    $insertAppointment->bindParam(":time", $data["appointment_time"]);
    $insertAppointment->bindParam(":notes", $data["notes"]);
    $insertAppointment->execute();
    
    $appointmentId = $db->lastInsertId();
    
    // Actualizar disponibilidad del médico
    $updateAvailability = $db->prepare("
        UPDATE doctor_availability 
        SET is_available = 0 
        WHERE doctor_id = :doctor_id 
        AND date = :date 
        AND time_slot = :time_slot
    ");
    
    $updateAvailability->bindParam(":doctor_id", $data["doctor_id"]);
    $updateAvailability->bindParam(":date", $data["appointment_date"]);
    $updateAvailability->bindParam(":time_slot", $data["appointment_time"]);
    $updateAvailability->execute();
    
    echo json_encode([
        "success" => true,
        "message" => "Cita creada correctamente",
        "appointment_id" => $appointmentId
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error al crear la cita: " . $e->getMessage()
    ]);
}
?>';

    file_put_contents($createAppointmentApi, $createAppointmentContent);
    printMessage("Archivo API para crear citas actualizado: $createAppointmentApi", 'success');
    
    // Archivo API para obtener médicos
    $getDoctorsApi = 'backend/api/get_doctors.php';
    $getDoctorsContent = '<?php
// API para obtener los médicos disponibles
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Configuración de la base de datos
require_once "../config/database_class.php";
$database = new Database();
$db = $database->getConnection();

// Obtener parámetros
$specialty = isset($_GET["specialty"]) ? $_GET["specialty"] : null;

try {
    // Consulta base
    $sql = "SELECT * FROM doctors WHERE active = 1";
    $params = [];
    
    // Filtrar por especialidad si se proporciona
    if ($specialty) {
        $sql .= " AND specialty = :specialty";
        $params[":specialty"] = $specialty;
    }
    
    // Ordenar por nombre
    $sql .= " ORDER BY name ASC";
    
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "success" => true,
        "doctors" => $doctors
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error al obtener los médicos: " . $e->getMessage()
    ]);
}
?>';

    file_put_contents($getDoctorsApi, $getDoctorsContent);
    printMessage("Archivo API para obtener médicos actualizado: $getDoctorsApi", 'success');
    
    // Archivo API para obtener horarios disponibles
    $getDoctorSlotsApi = 'backend/api/get_doctor_slots.php';
    $getDoctorSlotsContent = '<?php
// API para obtener los horarios disponibles de un médico
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Configuración de la base de datos
require_once "../config/database_class.php";
$database = new Database();
$db = $database->getConnection();

// Obtener parámetros
$doctorId = isset($_GET["doctor_id"]) ? intval($_GET["doctor_id"]) : 0;
$date = isset($_GET["date"]) ? $_GET["date"] : date("Y-m-d");

if ($doctorId <= 0) {
    echo json_encode(["success" => false, "message" => "ID de médico no válido"]);
    exit;
}

try {
    // Obtener horarios disponibles
    $sql = "
        SELECT time_slot 
        FROM doctor_availability 
        WHERE doctor_id = :doctor_id 
        AND date = :date 
        AND is_available = 1
        ORDER BY time_slot ASC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(":doctor_id", $doctorId);
    $stmt->bindParam(":date", $date);
    $stmt->execute();
    
    $availableSlots = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $availableSlots[] = $row["time_slot"];
    }
    
    echo json_encode([
        "success" => true,
        "available_slots" => $availableSlots
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error al obtener los horarios disponibles: " . $e->getMessage()
    ]);
}
?>';

    file_put_contents($getDoctorSlotsApi, $getDoctorSlotsContent);
    printMessage("Archivo API para obtener horarios disponibles actualizado: $getDoctorSlotsApi", 'success');
    
    // Paso 6: Actualizar la página de citas
    printMessage("Actualizando la página de citas...");
    
    $citasHtml = 'citas_db.html';
    $citasContent = file_get_contents($citasHtml);
    
    // Reemplazar referencias a las columnas
    $replacements = [
        'appointment.date' => 'appointment.appointment_date',
        'appointment.time' => 'appointment.appointment_time',
        'date: selectedDate' => 'appointment_date: selectedDate',
        'time: selectedTimeSlot' => 'appointment_time: selectedTimeSlot',
        'time_slot: selectedTimeSlot' => 'appointment_time: selectedTimeSlot'
    ];
    
    $newCitasContent = $citasContent;
    foreach ($replacements as $search => $replace) {
        $newCitasContent = str_replace($search, $replace, $newCitasContent);
    }
    
    if ($newCitasContent !== $citasContent) {
        file_put_contents($citasHtml, $newCitasContent);
        printMessage("Página de citas actualizada: $citasHtml", 'success');
    } else {
        printMessage("No fue necesario actualizar la página de citas", 'warning');
    }
    
    // Mostrar resumen de la estructura de la base de datos
    echo "<h2>Estructura actual de las tablas</h2>";
    
    $tables = ['appointments', 'doctors', 'doctor_availability'];
    
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<h3>Tabla: $table</h3>";
            
            $stmt = $db->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table>";
            echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Predeterminado</th><th>Extra</th></tr>";
            
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>" . $column['Field'] . "</td>";
                echo "<td>" . $column['Type'] . "</td>";
                echo "<td>" . $column['Null'] . "</td>";
                echo "<td>" . $column['Key'] . "</td>";
                echo "<td>" . $column['Default'] . "</td>";
                echo "<td>" . $column['Extra'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
    }
    
    // Finalizar
    echo "<h2>¡Reparación completada!</h2>";
    echo "<p class='success'>El sistema de citas médicas ha sido reparado correctamente.</p>";
    echo "<p>Ahora puedes acceder al sistema de citas y utilizarlo sin problemas.</p>";
    echo "<a href='citas_db.html' class='btn-primary'>Ir al Sistema de Citas</a>";
    
} catch (Exception $e) {
    printMessage("Error: " . $e->getMessage(), 'error');
}

echo "</body></html>";
?>
