# Script para restaurar la estructura original del proyecto Salutia

# Crear estructura de carpetas original
New-Item -ItemType Directory -Force -Path "public"
New-Item -ItemType Directory -Force -Path "public\views\doctor", "public\views\patient", "public\views\auth", "public\views\chat"
New-Item -ItemType Directory -Force -Path "public\doctor"

# Mover archivos HTML de la raíz a sus ubicaciones originales
if (Test-Path "doctor_patients.html") {
    Move-Item -Path "doctor_patients.html" -Destination "public\views\doctor\" -Force
    Write-Host "Movido doctor_patients.html a public\views\doctor\"
}

if (Test-Path "doctor_dashboard.html") {
    Move-Item -Path "doctor_dashboard.html" -Destination "public\views\doctor\" -Force
    Write-Host "Movido doctor_dashboard.html a public\views\doctor\"
}

if (Test-Path "doctor_schedule_manager.html") {
    Move-Item -Path "doctor_schedule_manager.html" -Destination "public\views\doctor\" -Force
    Write-Host "Movido doctor_schedule_manager.html a public\views\doctor\"
}

if (Test-Path "solicitar_cita.html") {
    Move-Item -Path "solicitar_cita.html" -Destination "public\views\patient\" -Force
    Write-Host "Movido solicitar_cita.html a public\views\patient\"
}

if (Test-Path "patient_dashboard.html") {
    Move-Item -Path "patient_dashboard.html" -Destination "public\views\patient\" -Force
    Write-Host "Movido patient_dashboard.html a public\views\patient\"
}

if (Test-Path "patient_appointment_booking.html") {
    Move-Item -Path "patient_appointment_booking.html" -Destination "public\views\patient\" -Force
    Write-Host "Movido patient_appointment_booking.html a public\views\patient\"
}

if (Test-Path "login.html") {
    Move-Item -Path "login.html" -Destination "public\views\auth\" -Force
    Write-Host "Movido login.html a public\views\auth\"
}

if (Test-Path "registro.html") {
    Move-Item -Path "registro.html" -Destination "public\views\auth\" -Force
    Write-Host "Movido registro.html a public\views\auth\"
}

if (Test-Path "doctor_login.html") {
    Move-Item -Path "doctor_login.html" -Destination "public\views\auth\" -Force
    Write-Host "Movido doctor_login.html a public\views\auth\"
}

if (Test-Path "simple_register.html") {
    Move-Item -Path "simple_register.html" -Destination "public\views\auth\" -Force
    Write-Host "Movido simple_register.html a public\views\auth\"
}

if (Test-Path "chat.html") {
    Move-Item -Path "chat.html" -Destination "public\views\chat\" -Force
    Write-Host "Movido chat.html a public\views\chat\"
}

# Mover otros archivos HTML a public
if (Test-Path "citas_db.html") {
    Move-Item -Path "citas_db.html" -Destination "public\" -Force
    Write-Host "Movido citas_db.html a public\"
}

if (Test-Path "dashboard.html") {
    Move-Item -Path "dashboard.html" -Destination "public\" -Force
    Write-Host "Movido dashboard.html a public\"
}

if (Test-Path "index.html") {
    Move-Item -Path "index.html" -Destination "public\" -Force
    Write-Host "Movido index.html a public\"
}

if (Test-Path "setup.html") {
    Move-Item -Path "setup.html" -Destination "public\" -Force
    Write-Host "Movido setup.html a public\"
}

if (Test-Path "sistema_citas.html") {
    Move-Item -Path "sistema_citas.html" -Destination "public\" -Force
    Write-Host "Movido sistema_citas.html a public\"
}

if (Test-Path "solicitar_cita_new.html") {
    Move-Item -Path "solicitar_cita_new.html" -Destination "public\" -Force
    Write-Host "Movido solicitar_cita_new.html a public\"
}

if (Test-Path "terminos.html") {
    Move-Item -Path "terminos.html" -Destination "public\" -Force
    Write-Host "Movido terminos.html a public\"
}

if (Test-Path "test_api.html") {
    Move-Item -Path "test_api.html" -Destination "public\" -Force
    Write-Host "Movido test_api.html a public\"
}

if (Test-Path "test_json.html") {
    Move-Item -Path "test_json.html" -Destination "public\" -Force
    Write-Host "Movido test_json.html a public\"
}

if (Test-Path "test_register_form.html") {
    Move-Item -Path "test_register_form.html" -Destination "public\" -Force
    Write-Host "Movido test_register_form.html a public\"
}

if (Test-Path "update_database.html") {
    Move-Item -Path "update_database.html" -Destination "public\" -Force
    Write-Host "Movido update_database.html a public\"
}

if (Test-Path "schedule_manager.html") {
    Move-Item -Path "schedule_manager.html" -Destination "public\doctor\" -Force
    Write-Host "Movido schedule_manager.html a public\doctor\"
}

Write-Host "`nEstructura del proyecto restaurada al estado original." -ForegroundColor Green
