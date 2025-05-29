<?php
// Script para guardar las citas de demostración en la base de datos
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir la configuración de la base de datos
require_once __DIR__ . '/../config/database_class.php';

// Función para verificar si una cita ya existe
function appointmentExists($conn, $doctorId, $date, $time) {
    $query = "SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$doctorId, $date, $time]);
    return $stmt->rowCount() > 0;
}

// Función para guardar una cita
function saveAppointment($conn, $appointment) {
    // Verificar si la cita ya existe
    if (appointmentExists($conn, $appointment['doctor_id'], $appointment['date'], $appointment['time'])) {
        return false; // La cita ya existe
    }
    
    // Preparar la consulta SQL para insertar la cita
    $query = "INSERT INTO appointments (doctor_id, patient_id, appointment_date, appointment_time, reason, status) 
              VALUES (?, ?, ?, ?, ?, 'confirmed')";
    
    $stmt = $conn->prepare($query);
    $result = $stmt->execute([
        $appointment['doctor_id'], 
        $appointment['patient_id'], 
        $appointment['date'], 
        $appointment['time'], 
        $appointment['reason']
    ]);
    
    return $result;
}

// Conectar a la base de datos
$database = new Database();
$conn = $database->getConnection();

// Verificar la conexión
if (!$conn) {
    die(json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']));
}

// Verificar si la tabla de citas existe
$query = "SHOW TABLES LIKE 'appointments'";
$stmt = $conn->prepare($query);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    // Si la tabla no existe, crearla
    $createTableQuery = "CREATE TABLE appointments (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        doctor_id INT(11) NOT NULL,
        patient_id INT(11) NOT NULL,
        appointment_date DATE NOT NULL,
        appointment_time TIME NOT NULL,
        reason VARCHAR(255) NOT NULL,
        status ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (!$conn->query($createTableQuery)) {
        die(json_encode(['success' => false, 'message' => 'Error al crear la tabla de citas: ' . $conn->error]));
    }
}

// Verificar si la tabla de pacientes existe
$tableCheckQuery = "SHOW TABLES LIKE 'patients'";
$tableExists = $conn->query($tableCheckQuery)->num_rows > 0;

// Si la tabla no existe, crearla
if (!$tableExists) {
    $createTableQuery = "CREATE TABLE patients (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        age INT(3),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    try {
        $conn->exec($createTableQuery);
    } catch (PDOException $e) {
        die(json_encode(['success' => false, 'message' => 'Error al crear la tabla de pacientes: ' . $e->getMessage()]));
    }
}

// Obtener datos de ejemplo
$demoPatients = [
    ['id' => 101, 'name' => 'María García', 'email' => 'maria@ejemplo.com', 'phone' => '600123456', 'age' => 42],
    ['id' => 102, 'name' => 'Carlos Rodríguez', 'email' => 'carlos@ejemplo.com', 'phone' => '600234567', 'age' => 35],
    ['id' => 103, 'name' => 'Ana Martínez', 'email' => 'ana@ejemplo.com', 'phone' => '600345678', 'age' => 28],
    ['id' => 104, 'name' => 'Juan López', 'email' => 'juan@ejemplo.com', 'phone' => '600456789', 'age' => 56],
    ['id' => 105, 'name' => 'Sofía Fernández', 'email' => 'sofia@ejemplo.com', 'phone' => '600567890', 'age' => 31],
    ['id' => 106, 'name' => 'Miguel Sánchez', 'email' => 'miguel@ejemplo.com', 'phone' => '600678901', 'age' => 45],
    ['id' => 107, 'name' => 'Laura Gómez', 'email' => 'laura@ejemplo.com', 'phone' => '600789012', 'age' => 39]
];

// Insertar pacientes de ejemplo si no existen
foreach ($demoPatients as $patient) {
    $query = "SELECT id FROM patients WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$patient['id']]);
    $result = $stmt->fetch();

    if (!$result) {
        $query = "INSERT INTO patients (id, name, email, phone, age) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$patient['id'], $patient['name'], $patient['email'], $patient['phone'], $patient['age']]);
    }
}

// Obtener el ID del médico (asumimos que existe al menos un médico)
$doctorQuery = "SELECT id FROM doctors LIMIT 1";
$stmt = $conn->prepare($doctorQuery);
$stmt->execute();
$doctorResult = $stmt->fetch();

if (!$doctorResult) {
    // Si no hay médicos, crear uno de ejemplo
    $insertDoctorQuery = "INSERT INTO doctors (id, name, specialty, email, phone) VALUES (1, 'Dr. Example', 'General', 'doctor@example.com', '600111222')";
    try {
        $conn->exec($insertDoctorQuery);
    } catch (PDOException $e) {
        die(json_encode(['success' => false, 'message' => 'Error al crear el médico de ejemplo: ' . $e->getMessage()]));
    }
    $doctorId = 1;
} else {
    $doctorId = $doctorResult['id'];
}

// Generar citas para los próximos 10 días
$today = new DateTime();
$appointments = [];
$appointmentsCreated = 0;

for ($i = 0; $i < 10; $i++) {
    $date = clone $today;
    $date->modify("+$i days");
    $formattedDate = $date->format('Y-m-d');
    
    // Generar entre 1 y 3 citas para cada día
    $numAppointments = rand(1, 3);
    
    for ($j = 0; $j < $numAppointments; $j++) {
        // Seleccionar un paciente aleatorio
        $patient = $demoPatients[array_rand($demoPatients)];
        
        // Generar una hora aleatoria entre 9:00 y 17:00
        $hour = 9 + floor(rand(0, 8));
        $minutes = (rand(0, 1) === 0) ? "00" : "30";
        $formattedTime = sprintf("%02d:%s:00", $hour, $minutes);
        
        // Crear el objeto de cita
        $appointment = [
            'doctor_id' => $doctorId,
            'patient_id' => $patient['id'],
            'date' => $formattedDate,
            'time' => $formattedTime,
            'reason' => $patient['id'] === 101 ? 'Revisión anual' : 
                       ($patient['id'] === 102 ? 'Dolor de espalda' : 
                       ($patient['id'] === 103 ? 'Consulta dermatológica' : 
                       ($patient['id'] === 104 ? 'Control de hipertensión' : 
                       ($patient['id'] === 105 ? 'Dolor de cabeza recurrente' : 
                       ($patient['id'] === 106 ? 'Revisión post-operatoria' : 'Análisis de resultados')))))
        ];
        
        // Guardar la cita en la base de datos
        if (saveAppointment($conn, $appointment)) {
            $appointments[] = $appointment;
            $appointmentsCreated++;
        }
    }
}

// Cerrar la conexión
$conn->close();

// Devolver la respuesta
echo json_encode([
    'success' => true, 
    'message' => "Se han creado $appointmentsCreated citas de demostración", 
    'appointments' => $appointments
]);
?>
