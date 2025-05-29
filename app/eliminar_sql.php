<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Permitir CORS
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/html; charset=UTF-8');

// Conexión a la base de datos
$db_host = 'localhost';
$db_name = 'salutia';
$db_user = 'root';
$db_pass = '';

// Obtener ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

echo "<h1>Eliminación de cita ID: $id</h1>";

if ($id <= 0) {
    echo "<p style='color:red'>ID no válido</p>";
    exit;
}

// Conectar a la base de datos
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Verificar conexión
if ($mysqli->connect_error) {
    echo "<p style='color:red'>Error de conexión: " . $mysqli->connect_error . "</p>";
    exit;
}

echo "<p>Conexión a la base de datos establecida</p>";

// Verificar si la tabla existe
$result = $mysqli->query("SHOW TABLES LIKE 'appointments'");
if ($result->num_rows == 0) {
    echo "<p style='color:red'>La tabla appointments no existe</p>";
    exit;
}

echo "<p>Tabla appointments encontrada</p>";

// Mostrar la cita antes de eliminar
$check_query = "SELECT * FROM appointments WHERE id = $id";
$check_result = $mysqli->query($check_query);

echo "<h2>Cita a eliminar:</h2>";
if ($check_result && $check_result->num_rows > 0) {
    $appointment = $check_result->fetch_assoc();
    echo "<pre>" . print_r($appointment, true) . "</pre>";
} else {
    echo "<p style='color:orange'>No se encontró la cita con ID: $id</p>";
}

// Ejecutar la eliminación
$delete_query = "DELETE FROM appointments WHERE id = $id";
echo "<p>Ejecutando consulta: <code>$delete_query</code></p>";

if ($mysqli->query($delete_query)) {
    $affected_rows = $mysqli->affected_rows;
    echo "<p style='color:green'>Cita eliminada correctamente. Filas afectadas: $affected_rows</p>";
} else {
    echo "<p style='color:red'>Error al eliminar la cita: " . $mysqli->error . "</p>";
}

// Verificar después de eliminar
$check_after_query = "SELECT * FROM appointments WHERE id = $id";
$check_after_result = $mysqli->query($check_after_query);

echo "<h2>Verificación después de eliminar:</h2>";
if ($check_after_result && $check_after_result->num_rows > 0) {
    $appointment = $check_after_result->fetch_assoc();
    echo "<p style='color:red'>¡La cita sigue existiendo!</p>";
    echo "<pre>" . print_r($appointment, true) . "</pre>";
} else {
    echo "<p style='color:green'>La cita ya no existe en la base de datos</p>";
}

// Mostrar todas las citas restantes
$all_query = "SELECT * FROM appointments";
$all_result = $mysqli->query($all_query);

echo "<h2>Todas las citas restantes:</h2>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Paciente</th><th>Doctor</th><th>Fecha</th><th>Estado</th></tr>";

if ($all_result && $all_result->num_rows > 0) {
    while ($row = $all_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['patient_name'] . "</td>";
        echo "<td>" . $row['doctor_name'] . "</td>";
        echo "<td>" . $row['appointment_date'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5'>No hay citas</td></tr>";
}

echo "</table>";

// Cerrar conexión
$mysqli->close();
?>
