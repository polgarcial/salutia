# Script para restaurar la estructura original del proyecto Salutia

# Mover archivos de vistas de doctor de vuelta a la raíz
if (Test-Path "views\doctor\doctor_patients.html") {
    Move-Item -Path "views\doctor\doctor_patients.html" -Destination "."
    Write-Host "Movido doctor_patients.html a la raíz"
}

if (Test-Path "views\doctor\doctor_dashboard.html") {
    Move-Item -Path "views\doctor\doctor_dashboard.html" -Destination "."
    Write-Host "Movido doctor_dashboard.html a la raíz"
}

if (Test-Path "views\doctor\doctor_schedule_manager.html") {
    Move-Item -Path "views\doctor\doctor_schedule_manager.html" -Destination "."
    Write-Host "Movido doctor_schedule_manager.html a la raíz"
}

# Mover archivos de vistas de paciente de vuelta a la raíz
if (Test-Path "views\patient\solicitar_cita.html") {
    Move-Item -Path "views\patient\solicitar_cita.html" -Destination "."
    Write-Host "Movido solicitar_cita.html a la raíz"
}

if (Test-Path "views\patient\patient_dashboard.html") {
    Move-Item -Path "views\patient\patient_dashboard.html" -Destination "."
    Write-Host "Movido patient_dashboard.html a la raíz"
}

if (Test-Path "views\patient\patient_appointment_booking.html") {
    Move-Item -Path "views\patient\patient_appointment_booking.html" -Destination "."
    Write-Host "Movido patient_appointment_booking.html a la raíz"
}

# Mover archivos de autenticación de vuelta a la raíz
if (Test-Path "views\auth\login.html") {
    Move-Item -Path "views\auth\login.html" -Destination "."
    Write-Host "Movido login.html a la raíz"
}

if (Test-Path "views\auth\registro.html") {
    Move-Item -Path "views\auth\registro.html" -Destination "."
    Write-Host "Movido registro.html a la raíz"
}

if (Test-Path "views\auth\doctor_login.html") {
    Move-Item -Path "views\auth\doctor_login.html" -Destination "."
    Write-Host "Movido doctor_login.html a la raíz"
}

if (Test-Path "views\auth\simple_register.html") {
    Move-Item -Path "views\auth\simple_register.html" -Destination "."
    Write-Host "Movido simple_register.html a la raíz"
}

# Mover archivos de chat de vuelta a la raíz
if (Test-Path "views\chat\chat.html") {
    Move-Item -Path "views\chat\chat.html" -Destination "."
    Write-Host "Movido chat.html a la raíz"
}

# Mover archivos JavaScript de doctor de vuelta a la carpeta js
if (Test-Path "js\doctor\doctor_schedule_manager.js") {
    Move-Item -Path "js\doctor\doctor_schedule_manager.js" -Destination "js\"
    Write-Host "Movido doctor_schedule_manager.js a la carpeta js"
}

if (Test-Path "js\doctor\doctor_schedule_manager_fixed.js") {
    Move-Item -Path "js\doctor\doctor_schedule_manager_fixed.js" -Destination "js\"
    Write-Host "Movido doctor_schedule_manager_fixed.js a la carpeta js"
}

# Mover archivos JavaScript de autenticación de vuelta a la carpeta js
if (Test-Path "js\auth\auth.js") {
    Move-Item -Path "js\auth\auth.js" -Destination "js\"
    Write-Host "Movido auth.js a la carpeta js"
}

if (Test-Path "js\auth\register.js") {
    Move-Item -Path "js\auth\register.js" -Destination "js\"
    Write-Host "Movido register.js a la carpeta js"
}

# Mover archivos JavaScript de paciente de vuelta a la carpeta js
if (Test-Path "js\patient\appointments.js") {
    Move-Item -Path "js\patient\appointments.js" -Destination "js\"
    Write-Host "Movido appointments.js a la carpeta js"
}

# Eliminar carpetas vacías
if (Test-Path "views") {
    Get-ChildItem -Path "views" -Directory | ForEach-Object {
        if ((Get-ChildItem -Path $_.FullName -Recurse -File).Count -eq 0) {
            Remove-Item -Path $_.FullName -Recurse
            Write-Host "Eliminada carpeta vacía: $($_.FullName)"
        }
    }
    if ((Get-ChildItem -Path "views" -Recurse -File).Count -eq 0) {
        Remove-Item -Path "views" -Recurse
        Write-Host "Eliminada carpeta vacía: views"
    }
}

if (Test-Path "js") {
    Get-ChildItem -Path "js" -Directory | ForEach-Object {
        if ((Get-ChildItem -Path $_.FullName -Recurse -File).Count -eq 0) {
            Remove-Item -Path $_.FullName -Recurse
            Write-Host "Eliminada carpeta vacía: $($_.FullName)"
        }
    }
}

Write-Host "`nEstructura del proyecto restaurada a su estado original." -ForegroundColor Green
