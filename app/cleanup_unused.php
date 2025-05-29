<?php
// Script para eliminar archivos no utilizados en el proyecto Salutia
header('Content-Type: text/plain; charset=UTF-8');

// Lista de archivos que se sabe que no se utilizan
$unusedFiles = [
    // Archivos de corrección que ya han cumplido su propósito
    'backend/api/fix_database.php',
    'backend/api/fix_tables.php',
    'backend/api/fix_users_table.php',
    'backend/database/emergency_fix.php',
    'backend/database/fix_doctor_availability.php',
    'backend/database/fix_patients_final.php',
    
    // Archivos temporales o de demostración
    'doctor_dashboard_demo.html',
    'doctor_dashboard_backup.html',
    'analyze_unused_files.php',
    'cleanup_files.bat',
    'cleanup_files_updated.bat'
];

// Contador de archivos eliminados
$deletedCount = 0;

// Eliminar cada archivo no utilizado
foreach ($unusedFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    
    if (file_exists($fullPath)) {
        if (unlink($fullPath)) {
            echo "✓ Eliminado: $file\n";
            $deletedCount++;
        } else {
            echo "✗ Error al eliminar: $file\n";
        }
    } else {
        echo "- No encontrado: $file\n";
    }
}

echo "\nSe han eliminado $deletedCount archivos no utilizados.";
?>
