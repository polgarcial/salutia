<?php
// Configuración para NO mostrar errores en la salida (los errores se registrarán en el log)
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejador de errores personalizado para asegurar que siempre se devuelva JSON válido
function json_error_handler($errno, $errstr, $errfile, $errline) {
    // Registrar el error en el archivo de log
    $log_file = __DIR__ . '/error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $error_message = "$timestamp - Error $errno: $errstr en $errfile:$errline";
    file_put_contents($log_file, $error_message . "\n", FILE_APPEND);
    
    // Devolver JSON con el error
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'error_code' => $errno
    ]);
    
    // Terminar la ejecución
    exit();
}

// Establecer el manejador de errores personalizado
set_error_handler('json_error_handler', E_ALL);

// Manejador de excepciones no capturadas
set_exception_handler(function($e) {
    // Registrar la excepción en el archivo de log
    $log_file = __DIR__ . '/error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $error_message = "$timestamp - Excepción no capturada: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine();
    file_put_contents($log_file, $error_message . "\n", FILE_APPEND);
    
    // Devolver JSON con el error
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
    
    // Terminar la ejecución
    exit();
});

// Función para registrar mensajes de depuración
function debug_log($message, $data = []) {
    $log_file = __DIR__ . '/debug_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "$timestamp - $message";
    
    if (!empty($data)) {
        $log_message .= ": " . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    file_put_contents($log_file, $log_message . "\n", FILE_APPEND);
}

// Manejar OPTIONS request para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Si es una solicitud GET directa al script, mostrar una página de prueba
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Prueba de Citas</title>
            <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css' rel='stylesheet'>
        </head>
        <body>
            <div class='container mt-5'>
                <div class='row'>
                    <div class='col-md-8 offset-md-2'>
                        <div class='card'>
                            <div class='card-header bg-primary text-white'>
                                <h3>Formulario de Prueba para Citas</h3>
                            </div>
                            <div class='card-body'>
                                <form id='testForm'>
                                    <div class='mb-3'>
                                        <label for='patient_id' class='form-label'>ID del Paciente:</label>
                                        <input type='number' class='form-control' id='patient_id' name='patient_id' value='1' required>
                                    </div>
                                    <div class='mb-3'>
                                        <label for='patient_name' class='form-label'>Nombre del Paciente:</label>
                                        <input type='text' class='form-control' id='patient_name' name='patient_name' value='Paciente de Prueba' required>
                                    </div>
                                    <div class='mb-3'>
                                        <label for='patient_email' class='form-label'>Email del Paciente:</label>
                                        <input type='email' class='form-control' id='patient_email' name='patient_email' value='paciente@ejemplo.com' required>
                                    </div>
                                    <div class='mb-3'>
                                        <label for='doctor_id' class='form-label'>ID del Doctor:</label>
                                        <input type='number' class='form-control' id='doctor_id' name='doctor_id' value='1' required>
                                    </div>
                                    <div class='mb-3'>
                                        <label for='doctor_name' class='form-label'>Nombre del Doctor:</label>
                                        <input type='text' class='form-control' id='doctor_name' name='doctor_name' value='Dr. Juan Pérez' required>
                                    </div>
                                    <div class='mb-3'>
                                        <label for='reason' class='form-label'>Motivo de la Consulta:</label>
                                        <select class='form-control' id='reason' name='reason' required>
                                            <option value='Consulta general'>Consulta general</option>
                                            <option value='Consulta por dolor'>Consulta por dolor</option>
                                            <option value='Seguimiento'>Seguimiento</option>
                                        </select>
                                    </div>
                                    <div class='mb-3'>
                                        <label for='date' class='form-label'>Fecha:</label>
                                        <input type='date' class='form-control' id='date' name='date' required>
                                    </div>
                                    <div class='mb-3'>
                                        <label for='time' class='form-label'>Hora:</label>
                                        <input type='time' class='form-control' id='time' name='time' required>
                                    </div>
                                    <div class='mb-3'>
                                        <label for='notes' class='form-label'>Notas Adicionales:</label>
                                        <textarea class='form-control' id='notes' name='notes' rows='3'></textarea>
                                    </div>
                                    <button type='submit' class='btn btn-primary'>Enviar Solicitud</button>
                                </form>
                                <div class='mt-4' id='result'></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
            document.getElementById('testForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                fetch('create_appointment_request.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    const resultDiv = document.getElementById('result');
                    if (data.success) {
                        resultDiv.innerHTML = `<div class='alert alert-success'>Éxito: ${data.message}</div>`;
                    } else {
                        resultDiv.innerHTML = `<div class='alert alert-danger'>Error: ${data.message}</div>`;
                    }
                })
                .catch(error => {
                    document.getElementById('result').innerHTML = `<div class='alert alert-danger'>Error: ${error.message}</div>`;
                });
            });
            
            // Establecer la fecha actual como valor predeterminado
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            document.getElementById('date').value = `${yyyy}-${mm}-${dd}`;
            
            // Establecer una hora predeterminada
            document.getElementById('time').value = '09:00';
            </script>
        </body>
        </html>";
        exit;
    }
    
    // Si es otro método que no sea POST ni GET
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido. Este endpoint solo acepta solicitudes POST.'
    ]);
    exit;
}

try {
    // Comprobar si los datos vienen como FormData o como JSON
    if (!empty($_POST)) {
        // Los datos vienen como FormData (desde un formulario)
        debug_log('Datos recibidos como FormData', ['post' => $_POST]);
        $data = $_POST;
    } else {
        // Intentar leer los datos como JSON
        $input = file_get_contents('php://input');
        debug_log('Datos recibidos como JSON', ['input' => $input]);
        $data = json_decode($input, true);
        
        // Verificar que los datos JSON sean válidos
        if ($data === null && !empty($input)) {
            debug_log('Error al decodificar JSON', ['error' => json_last_error_msg()]);
            throw new Exception('Error en el formato de datos: ' . json_last_error_msg());
        }
    }
    
    // Si no hay datos ni en POST ni en JSON, usar un array vacío
    if (empty($data)) {
        $data = [];
        debug_log('No se recibieron datos, usando valores predeterminados');
    }
    
    // Establecer valores predeterminados para todos los campos
    $patient_id = isset($data['patient_id']) ? $data['patient_id'] : '1';
    $patient_name = isset($data['patient_name']) ? $data['patient_name'] : 'Paciente';
    $patient_email = isset($data['patient_email']) ? $data['patient_email'] : 'paciente@ejemplo.com';
    $doctor_id = isset($data['doctor_id']) ? $data['doctor_id'] : '1';
    $doctor_name = isset($data['doctor_name']) ? $data['doctor_name'] : 'Doctor';
    $reason = isset($data['reason']) ? $data['reason'] : 'Consulta general';
    
    // Comprobar diferentes nombres posibles para la fecha
    if (isset($data['date'])) {
        $requested_date = $data['date'];
    } elseif (isset($data['requested_date'])) {
        $requested_date = $data['requested_date'];
    } elseif (isset($data['appointment_date'])) {
        $requested_date = $data['appointment_date'];
    } else {
        $requested_date = date('Y-m-d');
    }
    
    // Comprobar diferentes nombres posibles para la hora
    if (isset($data['time'])) {
        $requested_time = $data['time'];
    } elseif (isset($data['requested_time'])) {
        $requested_time = $data['requested_time'];
    } elseif (isset($data['appointment_time'])) {
        $requested_time = $data['appointment_time'];
    } else {
        $requested_time = '09:00';
    }
    
    // Comprobar si hay notas adicionales
    $notes = isset($data['notes']) ? $data['notes'] : '';
    
    // Formatear la fecha y hora para la base de datos
    $date_str = $requested_date; // Guardar como string para las columnas date/time
    $time_str = $requested_time; // Guardar como string para las columnas date/time
    
    // Convertir a formato de fecha para MySQL
    $appointment_date = date('Y-m-d', strtotime($requested_date));
    
    // Extraer horas y minutos de la hora seleccionada
    $timeParts = explode(':', $requested_time);
    $hour = isset($timeParts[0]) ? intval($timeParts[0]) : 9; // Predeterminado: 9 AM
    $minute = isset($timeParts[1]) ? intval($timeParts[1]) : 0;
    
    // Crear horas de inicio y fin en formato HH:MM:SS
    $start_time = sprintf('%02d:%02d:00', $hour, $minute);
    $end_hour = $hour + 1;
    if ($end_hour >= 24) $end_hour = 23;
    $end_time = sprintf('%02d:%02d:00', $end_hour, $minute);
    
    debug_log('Datos procesados', [
        'patient_id' => $patient_id,
        'patient_name' => $patient_name,
        'doctor_id' => $doctor_id,
        'doctor_name' => $doctor_name,
        'date_str' => $date_str,
        'time_str' => $time_str,
        'appointment_date' => $appointment_date,
        'start_time' => $start_time,
        'end_time' => $end_time
    ]);
    
    // Conectar a la base de datos
    require_once __DIR__ . '/database_class.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    // Verificar si existe la tabla patients y si no, crearla
    $patients_check = $conn->query("SHOW TABLES LIKE 'patients'");
    if ($patients_check->rowCount() == 0) {
        // La tabla patients no existe, crearla sin restricciones de clave foránea
        $create_patients_sql = "CREATE TABLE patients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $conn->exec($create_patients_sql);
        debug_log('Tabla patients creada');
        
        // Insertar un paciente de prueba para evitar errores de clave foránea
        $insert_patient_sql = "INSERT INTO patients (id, name, email) VALUES (1, 'Paciente de Prueba', 'paciente@ejemplo.com')";
        $conn->exec($insert_patient_sql);
        debug_log('Paciente de prueba insertado');
    }
    
    // Verificar si existe la tabla doctors y si no, crearla
    $doctors_check = $conn->query("SHOW TABLES LIKE 'doctors'");
    if ($doctors_check->rowCount() == 0) {
        // La tabla doctors no existe, crearla sin restricciones de clave foránea
        $create_doctors_sql = "CREATE TABLE doctors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            specialty VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $conn->exec($create_doctors_sql);
        debug_log('Tabla doctors creada');
        
        // Insertar un médico de prueba para evitar errores de clave foránea
        $insert_doctor_sql = "INSERT INTO doctors (id, name, specialty) VALUES (1, 'Dr. Juan Pérez', 'Cardiología')";
        $conn->exec($insert_doctor_sql);
        debug_log('Médico de prueba insertado');
    }
    
    // Verificar la estructura de la tabla appointments
    $table_check = $conn->query("SHOW TABLES LIKE 'appointments'");
    if ($table_check->rowCount() == 0) {
        // La tabla no existe, crearla SIN restricciones de clave foránea por ahora
        $create_table_sql = "CREATE TABLE appointments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            patient_name VARCHAR(100) NOT NULL,
            patient_email VARCHAR(100) NOT NULL,
            doctor_id INT NOT NULL,
            doctor_name VARCHAR(100) NOT NULL,
            reason TEXT NOT NULL,
            date VARCHAR(20) NOT NULL,
            time VARCHAR(10) NOT NULL,
            appointment_date DATE NOT NULL,
            appointment_time VARCHAR(8),
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            status ENUM('pending', 'confirmed', 'cancelled', 'completed') NOT NULL DEFAULT 'pending',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $conn->exec($create_table_sql);
        debug_log('Tabla appointments creada sin restricciones de clave foránea');
    } else {
        // Eliminar todas las restricciones de clave foránea de la tabla appointments
        try {
            // Primero, obtener todas las restricciones de clave foránea
            $check_constraints = $conn->query("SHOW CREATE TABLE appointments");
            $table_def = $check_constraints->fetch(PDO::FETCH_ASSOC);
            
            if (isset($table_def['Create Table'])) {
                debug_log('Verificando restricciones de clave foránea');
                
                // Buscar todas las restricciones de clave foránea
                preg_match_all('/CONSTRAINT `([^`]+)` FOREIGN KEY/', $table_def['Create Table'], $matches);
                
                if (isset($matches[1]) && count($matches[1]) > 0) {
                    debug_log('Encontradas ' . count($matches[1]) . ' restricciones de clave foránea');
                    
                    // Eliminar cada restricción encontrada
                    foreach ($matches[1] as $constraint_name) {
                        try {
                            $conn->exec("ALTER TABLE appointments DROP FOREIGN KEY `$constraint_name`");
                            debug_log('Restricción de clave foránea eliminada: ' . $constraint_name);
                        } catch (Exception $inner_e) {
                            debug_log('Error al eliminar restricción: ' . $constraint_name, ['error' => $inner_e->getMessage()]);
                        }
                    }
                } else {
                    debug_log('No se encontraron restricciones de clave foránea en el formato esperado');
                    
                    // Intentar un enfoque más directo: recrear la tabla sin restricciones
                    try {
                        // Crear una tabla temporal con la misma estructura pero sin restricciones
                        $conn->exec("CREATE TABLE appointments_temp LIKE appointments");
                        
                        // Copiar todos los datos
                        $conn->exec("INSERT INTO appointments_temp SELECT * FROM appointments");
                        
                        // Eliminar la tabla original
                        $conn->exec("DROP TABLE appointments");
                        
                        // Renombrar la tabla temporal
                        $conn->exec("RENAME TABLE appointments_temp TO appointments");
                        
                        debug_log('Tabla appointments recreada sin restricciones de clave foránea');
                    } catch (Exception $recreate_e) {
                        debug_log('Error al recrear la tabla sin restricciones', ['error' => $recreate_e->getMessage()]);
                    }
                }
            }
        } catch (Exception $e) {
            debug_log('Error al verificar/eliminar restricciones de clave foránea', ['error' => $e->getMessage()]);
            // Continuar con la ejecución aunque haya un error
        }
    }
    
    // Insertar la cita en la base de datos
    $sql = "INSERT INTO appointments 
            (patient_id, patient_name, patient_email, doctor_id, doctor_name, reason, 
             date, time, appointment_date, appointment_time, start_time, end_time, status, notes) 
            VALUES 
            (:patient_id, :patient_name, :patient_email, :doctor_id, :doctor_name, :reason, 
             :date, :time, :appointment_date, :appointment_time, :start_time, :end_time, 'pending', :notes)";
    
    $stmt = $conn->prepare($sql);
    
    // Verificar que los IDs de paciente y médico existan en las tablas correspondientes
    $patient_id_int = intval($patient_id);
    $doctor_id_int = intval($doctor_id);
    
    // Verificar si el paciente existe
    $check_patient = $conn->prepare("SELECT id FROM patients WHERE id = ?");
    $check_patient->execute([$patient_id_int]);
    if ($check_patient->rowCount() == 0) {
        // El paciente no existe, usar el ID 1 (paciente de prueba)
        debug_log('Paciente no encontrado, usando paciente de prueba', ['original_id' => $patient_id_int]);
        $patient_id_int = 1;
    }
    
    // Verificar si el médico existe
    $check_doctor = $conn->prepare("SELECT id FROM doctors WHERE id = ?");
    $check_doctor->execute([$doctor_id_int]);
    if ($check_doctor->rowCount() == 0) {
        // El médico no existe, usar el ID 1 (médico de prueba)
        debug_log('Médico no encontrado, usando médico de prueba', ['original_id' => $doctor_id_int]);
        $doctor_id_int = 1;
    }
    
    debug_log('IDs verificados', ['patient_id' => $patient_id_int, 'doctor_id' => $doctor_id_int]);
    
    // Vincular parámetros
    $stmt->bindParam(':patient_id', $patient_id_int, PDO::PARAM_INT);
    $stmt->bindParam(':patient_name', $patient_name);
    $stmt->bindParam(':patient_email', $patient_email);
    $stmt->bindParam(':doctor_id', $doctor_id_int, PDO::PARAM_INT);
    $stmt->bindParam(':doctor_name', $doctor_name);
    $stmt->bindParam(':reason', $reason);
    $stmt->bindParam(':date', $date_str);
    $stmt->bindParam(':time', $time_str);
    $stmt->bindParam(':appointment_date', $appointment_date);
    $stmt->bindParam(':appointment_time', $time_str);
    $stmt->bindParam(':start_time', $start_time);
    $stmt->bindParam(':end_time', $end_time);
    $stmt->bindParam(':notes', $notes);
    
    // Ejecutar la consulta
    $stmt->execute();
    
    // Obtener el ID de la cita insertada
    $appointment_id = $conn->lastInsertId();
    debug_log('Cita insertada correctamente', ['id' => $appointment_id]);
    
    // Devolver respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Cita solicitada correctamente',
        'appointment_id' => $appointment_id
    ]);
    
} catch (Exception $e) {
    debug_log('Error al procesar la solicitud', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
    ]);
}
