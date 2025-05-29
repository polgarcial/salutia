<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Conexión directa a la base de datos
$db_host = 'localhost';
$db_name = 'salutia';
$db_user = 'root';
$db_pass = '';

// Crear conexión
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Verificar conexión
if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}

echo "<h1>Prueba de eliminación de citas</h1>";

// Obtener ID de la cita a eliminar
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

// Mostrar todas las citas
echo "<h2>Citas actuales</h2>";
$result = $mysqli->query("SELECT * FROM appointments");

if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Paciente</th><th>Doctor</th><th>Fecha</th><th>Hora</th><th>Estado</th><th>Acción</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['patient_name'] . "</td>";
        echo "<td>" . $row['doctor_name'] . "</td>";
        echo "<td>" . $row['appointment_date'] . "</td>";
        echo "<td>" . $row['appointment_time'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td><a href='?id=" . $row['id'] . "'>Eliminar</a></td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No hay citas en la base de datos</p>";
}

// Eliminar cita si se proporciona un ID
if ($id !== null) {
    echo "<h2>Eliminando cita con ID: $id</h2>";
    
    $query = "DELETE FROM appointments WHERE id = $id";
    
    if ($mysqli->query($query)) {
        echo "<p style='color: green;'>Cita eliminada correctamente. Filas afectadas: " . $mysqli->affected_rows . "</p>";
    } else {
        echo "<p style='color: red;'>Error al eliminar la cita: " . $mysqli->error . "</p>";
    }
    
    // Mostrar citas después de eliminar
    echo "<h2>Citas después de eliminar</h2>";
    $result = $mysqli->query("SELECT * FROM appointments");
    
    if ($result && $result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Paciente</th><th>Doctor</th><th>Fecha</th><th>Hora</th><th>Estado</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['patient_name'] . "</td>";
            echo "<td>" . $row['doctor_name'] . "</td>";
            echo "<td>" . $row['appointment_date'] . "</td>";
            echo "<td>" . $row['appointment_time'] . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No hay citas en la base de datos</p>";
    }
}

// Cerrar conexión
$mysqli->close();
?>
