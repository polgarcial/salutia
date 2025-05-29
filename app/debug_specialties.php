<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir archivos necesarios
require_once 'database_class.php';
require_once 'debug_log.php';

echo "<h1>Depuración de Especialidades</h1>";

try {
    // Crear instancia de la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar si la tabla doctor_specialties existe
    $stmt = $db->query("SHOW TABLES LIKE 'doctor_specialties'");
    $tableExists = $stmt->rowCount() > 0;
    
    echo "<p>Tabla doctor_specialties existe: " . ($tableExists ? "Sí" : "No") . "</p>";
    
    if ($tableExists) {
        // Contar registros en la tabla
        $stmt = $db->query("SELECT COUNT(*) FROM doctor_specialties");
        $count = $stmt->fetchColumn();
        
        echo "<p>Número de registros en doctor_specialties: $count</p>";
        
        // Obtener todas las especialidades
        $stmt = $db->query("SELECT DISTINCT specialty FROM doctor_specialties ORDER BY specialty");
        $specialties = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<p>Especialidades encontradas: " . count($specialties) . "</p>";
        
        if (count($specialties) > 0) {
            echo "<ul>";
            foreach ($specialties as $specialty) {
                echo "<li>$specialty</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>No se encontraron especialidades.</p>";
        }
        
        // Mostrar médicos y sus especialidades
        $stmt = $db->query("
            SELECT u.id, u.name, ds.specialty
            FROM users u
            JOIN doctor_specialties ds ON u.id = ds.doctor_id
            WHERE u.role = 'doctor'
            ORDER BY u.name, ds.specialty
        ");
        
        $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h2>Médicos y sus especialidades</h2>";
        
        if (count($doctors) > 0) {
            $currentDoctor = '';
            echo "<ul>";
            
            foreach ($doctors as $doctor) {
                if ($currentDoctor != $doctor['name']) {
                    if ($currentDoctor != '') {
                        echo "</ul></li>";
                    }
                    echo "<li><strong>" . htmlspecialchars($doctor['name']) . "</strong> (ID: " . $doctor['id'] . ")<ul>";
                    $currentDoctor = $doctor['name'];
                }
                
                echo "<li>" . htmlspecialchars($doctor['specialty']) . "</li>";
            }
            
            echo "</ul></li></ul>";
        } else {
            echo "<p>No se encontraron médicos con especialidades.</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
    debug_log('Error en debug_specialties.php: ' . $e->getMessage());
}
?>
