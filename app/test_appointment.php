<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Función para registrar mensajes de depuración
function debug_log($message, $data = []) {
    $log_file = __DIR__ . '/debug_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}";
    
    if (!empty($data)) {
        $log_message .= ': ' . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND);
}

// Función para registrar errores
function error_log_custom($message, $data = []) {
    $log_file = __DIR__ . '/error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] ERROR: {$message}";
    
    if (!empty($data)) {
        $log_message .= ': ' . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND);
}

// Si es una solicitud POST, procesar los datos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Conectar a la base de datos
        $host = 'localhost';
        $dbname = 'salutia';
        $username = 'root';
        $password = '';
        
        debug_log('Conectando a la base de datos');
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        debug_log('Conexión establecida correctamente');
        
        // Verificar si existen las tablas necesarias y crearlas si no existen
        // 1. Tabla patients
        $patients_check = $conn->query("SHOW TABLES LIKE 'patients'");
        if ($patients_check->rowCount() == 0) {
            debug_log('Creando tabla patients');
            $create_patients_sql = "CREATE TABLE patients (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $conn->exec($create_patients_sql);
            
            // Insertar un paciente de prueba
            $insert_patient_sql = "INSERT INTO patients (id, name, email) VALUES (1, 'Paciente de Prueba', 'paciente@ejemplo.com')";
            $conn->exec($insert_patient_sql);
            debug_log('Paciente de prueba insertado');
        }
        
        // 2. Tabla doctors
        $doctors_check = $conn->query("SHOW TABLES LIKE 'doctors'");
        if ($doctors_check->rowCount() == 0) {
            debug_log('Creando tabla doctors');
            $create_doctors_sql = "CREATE TABLE doctors (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                specialty VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $conn->exec($create_doctors_sql);
            
            // Insertar un médico de prueba
            $insert_doctor_sql = "INSERT INTO doctors (id, name, specialty) VALUES (1, 'Dr. Juan Pérez', 'Cardiología')";
            $conn->exec($insert_doctor_sql);
            debug_log('Médico de prueba insertado');
        }
        
        // 3. Tabla appointments
        $appointments_check = $conn->query("SHOW TABLES LIKE 'appointments'");
        if ($appointments_check->rowCount() == 0) {
            debug_log('Creando tabla appointments');
            $create_appointments_sql = "CREATE TABLE appointments (
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
            $conn->exec($create_appointments_sql);
            debug_log('Tabla appointments creada');
        } else {
            // Verificar si hay restricciones de clave foránea y eliminarlas
            try {
                $check_constraints = $conn->query("SHOW CREATE TABLE appointments");
                $table_def = $check_constraints->fetch(PDO::FETCH_ASSOC);
                
                if (isset($table_def['Create Table']) && strpos($table_def['Create Table'], 'FOREIGN KEY') !== false) {
                    debug_log('Encontradas restricciones de clave foránea, recreando tabla');
                    
                    // Crear tabla temporal
                    $conn->exec("CREATE TABLE appointments_temp LIKE appointments");
                    
                    // Copiar datos
                    $conn->exec("INSERT INTO appointments_temp SELECT * FROM appointments");
                    
                    // Eliminar tabla original
                    $conn->exec("DROP TABLE appointments");
                    
                    // Crear nueva tabla sin restricciones
                    $create_appointments_sql = "CREATE TABLE appointments (
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
                    $conn->exec($create_appointments_sql);
                    
                    // Copiar datos de la tabla temporal
                    $conn->exec("INSERT INTO appointments SELECT * FROM appointments_temp");
                    
                    // Eliminar tabla temporal
                    $conn->exec("DROP TABLE appointments_temp");
                    
                    debug_log('Tabla appointments recreada sin restricciones');
                }
            } catch (Exception $e) {
                debug_log('Error al verificar/eliminar restricciones', ['error' => $e->getMessage()]);
            }
        }
        
        // Obtener los datos del formulario
        $patient_id = isset($_POST['patient_id']) ? $_POST['patient_id'] : 1;
        $patient_name = isset($_POST['patient_name']) ? $_POST['patient_name'] : 'Paciente de Prueba';
        $patient_email = isset($_POST['patient_email']) ? $_POST['patient_email'] : 'paciente@ejemplo.com';
        $doctor_id = isset($_POST['doctor_id']) ? $_POST['doctor_id'] : 1;
        $doctor_name = isset($_POST['doctor_name']) ? $_POST['doctor_name'] : 'Dr. Juan Pérez';
        $reason = isset($_POST['reason']) ? $_POST['reason'] : 'Consulta general';
        $date_str = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');
        $time_str = isset($_POST['time']) ? $_POST['time'] : '09:00';
        $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
        
        debug_log('Datos recibidos', [
            'patient_id' => $patient_id,
            'patient_name' => $patient_name,
            'doctor_id' => $doctor_id,
            'doctor_name' => $doctor_name,
            'date' => $date_str,
            'time' => $time_str
        ]);
        
        // Convertir fecha y hora a formatos adecuados
        $appointment_date = date('Y-m-d', strtotime($date_str));
        $start_time = date('H:i:s', strtotime($time_str));
        $end_time = date('H:i:s', strtotime($time_str) + 30 * 60); // 30 minutos después
        
        // Preparar la consulta SQL
        $sql = "INSERT INTO appointments 
                (patient_id, patient_name, patient_email, doctor_id, doctor_name, reason, 
                date, time, appointment_date, appointment_time, start_time, end_time, notes) 
                VALUES 
                (:patient_id, :patient_name, :patient_email, :doctor_id, :doctor_name, :reason, 
                :date, :time, :appointment_date, :appointment_time, :start_time, :end_time, :notes)";
        
        $stmt = $conn->prepare($sql);
        
        // Convertir IDs a enteros
        $patient_id_int = intval($patient_id);
        $doctor_id_int = intval($doctor_id);
        
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
        
        $appointment_id = $conn->lastInsertId();
        debug_log('Cita creada correctamente', ['id' => $appointment_id]);
        
        // Devolver respuesta exitosa
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Cita creada correctamente',
            'appointment_id' => $appointment_id
        ]);
        
    } catch (PDOException $e) {
        error_log_custom('Error de base de datos', ['error' => $e->getMessage()]);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error al crear la cita: ' . $e->getMessage()
        ]);
    } catch (Exception $e) {
        error_log_custom('Error general', ['error' => $e->getMessage()]);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
        ]);
    }
    
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Citas - Salutia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #4e73df;
        }
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2e59d9;
        }
    </style>
</head>
<body>
    <div class="container mt-5 mb-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header text-white">
                        <h3 class="mb-0">Formulario de Prueba para Citas</h3>
                    </div>
                    <div class="card-body">
                        <form id="testForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <h4 class="mb-3">Información del Paciente</h4>
                                    <div class="mb-3">
                                        <label for="patient_id" class="form-label">ID del Paciente:</label>
                                        <input type="number" class="form-control" id="patient_id" name="patient_id" value="1" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="patient_name" class="form-label">Nombre del Paciente:</label>
                                        <input type="text" class="form-control" id="patient_name" name="patient_name" value="Paciente de Prueba" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="patient_email" class="form-label">Email del Paciente:</label>
                                        <input type="email" class="form-control" id="patient_email" name="patient_email" value="paciente@ejemplo.com" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h4 class="mb-3">Información del Doctor</h4>
                                    <div class="mb-3">
                                        <label for="doctor_id" class="form-label">ID del Doctor:</label>
                                        <input type="number" class="form-control" id="doctor_id" name="doctor_id" value="1" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="doctor_name" class="form-label">Nombre del Doctor:</label>
                                        <input type="text" class="form-control" id="doctor_name" name="doctor_name" value="Dr. Juan Pérez" required>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <h4 class="mb-3">Detalles de la Cita</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="reason" class="form-label">Motivo de la Consulta:</label>
                                        <select class="form-select" id="reason" name="reason" required>
                                            <option value="Consulta general">Consulta general</option>
                                            <option value="Consulta por dolor">Consulta por dolor</option>
                                            <option value="Seguimiento">Seguimiento</option>
                                            <option value="Urgencia">Urgencia</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="date" class="form-label">Fecha:</label>
                                        <input type="date" class="form-control" id="date" name="date" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="time" class="form-label">Hora:</label>
                                        <input type="time" class="form-control" id="time" name="time" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notas Adicionales:</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">Enviar Solicitud</button>
                            </div>
                        </form>
                        
                        <div class="mt-4" id="result"></div>
                        
                        <div class="mt-4">
                            <h5>Instrucciones:</h5>
                            <p>Este formulario de prueba te permite crear citas directamente en la base de datos sin pasar por la interfaz normal de la aplicación. Es útil para diagnosticar problemas con el sistema de citas.</p>
                            <p>Los campos vienen pre-rellenados con valores de prueba que deberían funcionar correctamente. Simplemente haz clic en "Enviar Solicitud" para crear una cita de prueba.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Establecer la fecha actual como valor predeterminado
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        document.getElementById('date').value = `${yyyy}-${mm}-${dd}`;
        
        // Establecer una hora predeterminada
        document.getElementById('time').value = '09:00';
        
        // Manejar el envío del formulario
        document.getElementById('testForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<div class="alert alert-info">Enviando solicitud...</div>';
            
            const formData = new FormData(this);
            
            fetch('test_appointment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h5>¡Éxito!</h5>
                            <p>${data.message}</p>
                            <p>ID de la cita: ${data.appointment_id}</p>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <h5>Error</h5>
                            <p>${data.message}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h5>Error de conexión</h5>
                        <p>${error.message}</p>
                    </div>
                `;
            });
        });
    });
    </script>
</body>
</html>
