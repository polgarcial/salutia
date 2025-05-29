# Script para corregir las rutas del proyecto Salutia

# Mover archivos HTML de public\views a la raíz
if (Test-Path "public\views\doctor\doctor_patients.html") {
    Move-Item -Path "public\views\doctor\doctor_patients.html" -Destination "."
    Write-Host "Movido doctor_patients.html a la raíz"
}

if (Test-Path "public\views\doctor\doctor_dashboard.html") {
    Move-Item -Path "public\views\doctor\doctor_dashboard.html" -Destination "."
    Write-Host "Movido doctor_dashboard.html a la raíz"
}

if (Test-Path "public\views\doctor\doctor_schedule_manager.html") {
    Move-Item -Path "public\views\doctor\doctor_schedule_manager.html" -Destination "."
    Write-Host "Movido doctor_schedule_manager.html a la raíz"
}

if (Test-Path "public\views\patient\solicitar_cita.html") {
    Move-Item -Path "public\views\patient\solicitar_cita.html" -Destination "."
    Write-Host "Movido solicitar_cita.html a la raíz"
}

if (Test-Path "public\views\patient\patient_dashboard.html") {
    Move-Item -Path "public\views\patient\patient_dashboard.html" -Destination "."
    Write-Host "Movido patient_dashboard.html a la raíz"
}

if (Test-Path "public\views\patient\patient_appointment_booking.html") {
    Move-Item -Path "public\views\patient\patient_appointment_booking.html" -Destination "."
    Write-Host "Movido patient_appointment_booking.html a la raíz"
}

if (Test-Path "public\views\auth\login.html") {
    Move-Item -Path "public\views\auth\login.html" -Destination "."
    Write-Host "Movido login.html a la raíz"
}

if (Test-Path "public\views\auth\registro.html") {
    Move-Item -Path "public\views\auth\registro.html" -Destination "."
    Write-Host "Movido registro.html a la raíz"
}

if (Test-Path "public\views\auth\doctor_login.html") {
    Move-Item -Path "public\views\auth\doctor_login.html" -Destination "."
    Write-Host "Movido doctor_login.html a la raíz"
}

if (Test-Path "public\views\auth\simple_register.html") {
    Move-Item -Path "public\views\auth\simple_register.html" -Destination "."
    Write-Host "Movido simple_register.html a la raíz"
}

if (Test-Path "public\views\chat\chat.html") {
    Move-Item -Path "public\views\chat\chat.html" -Destination "."
    Write-Host "Movido chat.html a la raíz"
}

# Mover otros archivos HTML de public a la raíz
if (Test-Path "public\citas_db.html") {
    Move-Item -Path "public\citas_db.html" -Destination "."
    Write-Host "Movido citas_db.html a la raíz"
}

if (Test-Path "public\dashboard.html") {
    Move-Item -Path "public\dashboard.html" -Destination "."
    Write-Host "Movido dashboard.html a la raíz"
}

if (Test-Path "public\index.html") {
    Move-Item -Path "public\index.html" -Destination "."
    Write-Host "Movido index.html a la raíz"
}

if (Test-Path "public\setup.html") {
    Move-Item -Path "public\setup.html" -Destination "."
    Write-Host "Movido setup.html a la raíz"
}

if (Test-Path "public\sistema_citas.html") {
    Move-Item -Path "public\sistema_citas.html" -Destination "."
    Write-Host "Movido sistema_citas.html a la raíz"
}

if (Test-Path "public\solicitar_cita_new.html") {
    Move-Item -Path "public\solicitar_cita_new.html" -Destination "."
    Write-Host "Movido solicitar_cita_new.html a la raíz"
}

if (Test-Path "public\terminos.html") {
    Move-Item -Path "public\terminos.html" -Destination "."
    Write-Host "Movido terminos.html a la raíz"
}

# Mover archivos de test a la raíz
if (Test-Path "public\test_api.html") {
    Move-Item -Path "public\test_api.html" -Destination "."
    Write-Host "Movido test_api.html a la raíz"
}

if (Test-Path "public\test_json.html") {
    Move-Item -Path "public\test_json.html" -Destination "."
    Write-Host "Movido test_json.html a la raíz"
}

if (Test-Path "public\test_register_form.html") {
    Move-Item -Path "public\test_register_form.html" -Destination "."
    Write-Host "Movido test_register_form.html a la raíz"
}

if (Test-Path "public\update_database.html") {
    Move-Item -Path "public\update_database.html" -Destination "."
    Write-Host "Movido update_database.html a la raíz"
}

# Mover archivos de doctor a la raíz
if (Test-Path "public\doctor\dashboard.html") {
    Move-Item -Path "public\doctor\dashboard.html" -Destination "."
    Write-Host "Movido doctor\dashboard.html a la raíz"
}

if (Test-Path "public\doctor\schedule_manager.html") {
    Move-Item -Path "public\doctor\schedule_manager.html" -Destination "."
    Write-Host "Movido doctor\schedule_manager.html a la raíz"
}

# Verificar archivos JavaScript
if (Test-Path "public\js") {
    # Mover archivos JavaScript a la carpeta js en la raíz
    if (-not (Test-Path "js")) {
        New-Item -ItemType Directory -Force -Path "js"
    }
    
    Get-ChildItem -Path "public\js" -Recurse -File | ForEach-Object {
        $relativePath = $_.FullName.Replace("$PSScriptRoot\public\js\", "")
        $targetDir = Split-Path -Path "js\$relativePath" -Parent
        
        if (-not (Test-Path $targetDir)) {
            New-Item -ItemType Directory -Force -Path $targetDir
        }
        
        Move-Item -Path $_.FullName -Destination "js\$relativePath" -Force
        Write-Host "Movido $($_.Name) a js\$relativePath"
    }
}

# Eliminar carpetas vacías
if (Test-Path "public\views") {
    Get-ChildItem -Path "public\views" -Directory -Recurse | Sort-Object -Property FullName -Descending | ForEach-Object {
        if ((Get-ChildItem -Path $_.FullName -Recurse -File).Count -eq 0) {
            Remove-Item -Path $_.FullName -Recurse
            Write-Host "Eliminada carpeta vacía: $($_.FullName)"
        }
    }
    
    if ((Get-ChildItem -Path "public\views" -Recurse -File).Count -eq 0) {
        Remove-Item -Path "public\views" -Recurse
        Write-Host "Eliminada carpeta vacía: public\views"
    }
}

if (Test-Path "public") {
    Get-ChildItem -Path "public" -Directory | ForEach-Object {
        if ((Get-ChildItem -Path $_.FullName -Recurse -File).Count -eq 0) {
            Remove-Item -Path $_.FullName -Recurse
            Write-Host "Eliminada carpeta vacía: $($_.FullName)"
        }
    }
    
    if ((Get-ChildItem -Path "public" -Recurse -File).Count -eq 0) {
        Remove-Item -Path "public" -Recurse
        Write-Host "Eliminada carpeta vacía: public"
    }
}

Write-Host "`nEstructura del proyecto corregida y restaurada a su estado original." -ForegroundColor Green
