# Script para limpiar y organizar el proyecto Salutia

# Eliminar archivos de prueba y temporales
Remove-Item -Path "generate_test_token.php" -ErrorAction SilentlyContinue
Remove-Item -Path "test_api.php" -ErrorAction SilentlyContinue
Remove-Item -Path "test_doctors.php" -ErrorAction SilentlyContinue
Remove-Item -Path "test_system.php" -ErrorAction SilentlyContinue
Remove-Item -Path "test_and_cleanup.php" -ErrorAction SilentlyContinue

# Eliminar archivos duplicados
Remove-Item -Path "backend\api\request_appointment_fixed.php" -ErrorAction SilentlyContinue
Remove-Item -Path "backend\api\get_doctor_appointments_fixed.php" -ErrorAction SilentlyContinue
Remove-Item -Path "backend\api\get_doctor_stats_fixed.php" -ErrorAction SilentlyContinue

# Crear estructura de directorios necesarios
$directories = @(
    "backend\logs",
    "backend\migrations",
    "backend\templates",
    "backend\utils",
    "docs",
    "tests\unit",
    "tests\integration"
)

foreach ($dir in $directories) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir | Out-Null
    }
}

Write-Host "Limpieza y organización completadas. Se han eliminado archivos innecesarios y creado la estructura de directorios." -ForegroundColor Green
