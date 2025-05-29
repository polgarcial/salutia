<?php
/**
 * Script para eliminar todas las citas de la base de datos
 * SOLO PARA PRUEBAS - NO USAR EN PRODUCCIÓN
 */

// Conectar a la base de datos usando mysqli
$db_host = 'localhost';
$db_name = 'salutia';
$db_user = 'root';
$db_pass = '';

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Verificar conexión
if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}

echo "<h1>Eliminación de citas</h1>";

// Verificar si la tabla existe
$result = $mysqli->query("SHOW TABLES LIKE 'appointments'");
if ($result->num_rows == 0) {
    echo "<p>La tabla appointments no existe</p>";
    exit();
}

echo "<p>Tabla appointments encontrada</p>";

// Mostrar todas las citas antes de eliminar
$show_query = "SELECT * FROM appointments";
$show_result = $mysqli->query($show_query);

echo "<h2>Citas antes de eliminar:</h2>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Paciente</th><th>Doctor</th><th>Fecha</th><th>Estado</th></tr>";

if ($show_result && $show_result->num_rows > 0) {
    while ($row = $show_result->fetch_assoc()) {
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

// Formulario para eliminar una cita específica
echo "<h2>Eliminar cita específica</h2>";
echo "<form method='post'>";
echo "<label>ID de la cita: <input type='number' name='cita_id' required></label>";
echo "<button type='submit' name='eliminar_cita'>Eliminar cita</button>";
echo "</form>";

// Formulario para eliminar todas las citas
echo "<h2>Eliminar todas las citas</h2>";
echo "<form method='post'>";
echo "<button type='submit' name='eliminar_todas' style='background-color: red; color: white;'>ELIMINAR TODAS LAS CITAS</button>";
echo "</form>";

// Procesar eliminación de cita específica
if (isset($_POST['eliminar_cita']) && isset($_POST['cita_id'])) {
    $id = intval($_POST['cita_id']);
    
    $delete_query = "DELETE FROM appointments WHERE id = $id";
    
    if ($mysqli->query($delete_query)) {
        echo "<p style='color: green;'>Cita con ID $id eliminada correctamente. Filas afectadas: " . $mysqli->affected_rows . "</p>";
    } else {
        echo "<p style='color: red;'>Error al eliminar la cita: " . $mysqli->error . "</p>";
    }
}

// Procesar eliminación de todas las citas
if (isset($_POST['eliminar_todas'])) {
    $delete_all_query = "DELETE FROM appointments";
    
    if ($mysqli->query($delete_all_query)) {
        echo "<p style='color: green;'>Todas las citas eliminadas correctamente. Filas afectadas: " . $mysqli->affected_rows . "</p>";
    } else {
        echo "<p style='color: red;'>Error al eliminar todas las citas: " . $mysqli->error . "</p>";
    }
}

// Mostrar todas las citas después de eliminar
$show_query = "SELECT * FROM appointments";
$show_result = $mysqli->query($show_query);

echo "<h2>Citas después de eliminar:</h2>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Paciente</th><th>Doctor</th><th>Fecha</th><th>Estado</th></tr>";

if ($show_result && $show_result->num_rows > 0) {
    while ($row = $show_result->fetch_assoc()) {
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
