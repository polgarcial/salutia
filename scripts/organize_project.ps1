# Script para organizar el proyecto Salutia

# 1. Crear estructura de directorios
$directories = @(
    "app\Models",
    "config",
    "database\migrations",
    "database\seeds",
    "public\css",
    "public\js",
    "public\images",
    "public\uploads",
    "resources\views",
    "routes\api",
    "storage\logs",
    "storage\framework\sessions",
    "storage\framework\views",
    "storage\framework\cache"
)

foreach ($dir in $directories) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
    }
}

# 2. Mover archivos de configuración
$configFiles = Get-ChildItem -Path . -Include *.php -Recurse | 
    Where-Object { $_.Name -like "*config*.php" -or $_.Name -like "*database*.php" -or $_.Name -like "*_config.php" }

foreach ($file in $configFiles) {
    if ($file.DirectoryName -notlike "*\config" -and $file.DirectoryName -notlike "*\vendor" -and $file.DirectoryName -notlike "*\storage" -and $file.DirectoryName -notlike "*\public") {
        $destination = Join-Path "config" $file.Name
        if (-not (Test-Path $destination)) {
            Move-Item -Path $file.FullName -Destination $destination -Force
            Write-Host "Movido archivo de configuración: $($file.Name) -> config\$($file.Name)"
        }
    }
}

# 3. Mover migraciones de base de datos
$migrationFiles = Get-ChildItem -Path . -Include *.sql -Recurse | 
    Where-Object { $_.DirectoryName -notlike "*\database\migrations*" }

foreach ($file in $migrationFiles) {
    $migrationName = "migration_$(Get-Date -Format 'yyyyMMddHHmmss')_$($file.Name)"
    $destination = Join-Path "database\migrations" $migrationName
    Move-Item -Path $file.FullName -Destination $destination -Force
    Write-Host "Movido archivo de migración: $($file.Name) -> $destination"
}

# 4. Mover controladores
$controllerFiles = Get-ChildItem -Path . -Include *Controller.php -Recurse | 
    Where-Object { $_.DirectoryName -notlike "*\app\Http\Controllers*" }

foreach ($file in $controllerFiles) {
    $destination = Join-Path "app\Http\Controllers" $file.Name
    if (-not (Test-Path $destination)) {
        Move-Item -Path $file.FullName -Destination $destination -Force
        Write-Host "Movido controlador: $($file.Name) -> app/Http/Controllers/$($file.Name)"
    }
}

# 5. Mover archivos de la API
$apiFiles = Get-ChildItem -Path "backend\api" -Include *.php -Recurse -ErrorAction SilentlyContinue
if ($apiFiles) {
    foreach ($file in $apiFiles) {
        $relativePath = $file.FullName.Substring($file.FullName.IndexOf("api") + 4)
        $destination = Join-Path "routes\api" $relativePath
        
        $destinationDir = [System.IO.Path]::GetDirectoryName($destination)
        if (-not (Test-Path $destinationDir)) {
            New-Item -ItemType Directory -Path $destinationDir -Force | Out-Null
        }
        
        Move-Item -Path $file.FullName -Destination $destination -Force
        Write-Host "Movido archivo de API: $($file.Name) -> routes/api/$relativePath"
    }
}

# 6. Mover archivos frontend
$frontendFiles = Get-ChildItem -Path . -Include *.html,*.css,*.js -Recurse | 
    Where-Object { $_.DirectoryName -notlike "*\public*" -and $_.Name -ne "organize_project.ps1" }

foreach ($file in $frontendFiles) {
    $ext = $file.Extension.TrimStart('.')
    $destinationDir = Join-Path "public" $ext
    
    if (-not (Test-Path $destinationDir)) {
        New-Item -ItemType Directory -Path $destinationDir -Force | Out-Null
    }
    
    $destination = Join-Path $destinationDir $file.Name
    if (Test-Path $file.FullName) {
        Move-Item -Path $file.FullName -Destination $destination -Force
        Write-Host "Movido archivo frontend: $($file.Name) -> public/$ext/$($file.Name)"
    }
}

# 7. Limpiar directorios vacíos
Get-ChildItem -Path . -Directory -Recurse | 
    Where-Object { $_.GetFiles().Count -eq 0 -and $_.GetDirectories().Count -eq 0 } | 
    Remove-Item -Force -Recurse -ErrorAction SilentlyContinue

Write-Host "`n=== Organización completada ===" -ForegroundColor Green
Write-Host "Estructura actual del proyecto:" -ForegroundColor Cyan
Get-ChildItem -Path . -Recurse -Directory | Select-Object FullName | Sort-Object FullName
