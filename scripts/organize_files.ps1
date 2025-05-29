# Script para organizar los archivos del proyecto Salutia

# 1. Crear estructura de directorios necesaria
$directories = @(
    "public",
    "public\assets",
    "public\css",
    "public\js",
    "public\images",
    "app",
    "app\Http",
    "app\Http\Controllers",
    "app\Models",
    "config",
    "database\migrations",
    "resources\views",
    "routes"
)

foreach ($dir in $directories) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
    }
}

# 2. Mover archivos frontend a public/
$frontendFiles = Get-ChildItem -Path . -Include *.html,*.css,*.js -Recurse | 
    Where-Object { $_.DirectoryName -notlike "*\public*" -and $_.Name -ne "organize_files.ps1" }

foreach ($file in $frontendFiles) {
    $relativePath = $file.FullName.Substring((Get-Location).Path.Length + 1)
    $destination = Join-Path "public" $relativePath
    
    # Crear directorio de destino si no existe
    $destinationDir = [System.IO.Path]::GetDirectoryName($destination)
    if (-not (Test-Path $destinationDir)) {
        New-Item -ItemType Directory -Path $destinationDir -Force | Out-Null
    }
    
    if (Test-Path $file.FullName) {
        Move-Item -Path $file.FullName -Destination $destination -Force
        Write-Host "Movido: $($file.FullName) -> $destination"
    }
}

# 3. Mover controladores a app/Http/Controllers
$controllerFiles = Get-ChildItem -Path . -Include *Controller.php -Recurse | 
    Where-Object { $_.DirectoryName -notlike "*\app\Http\Controllers*" }

foreach ($file in $controllerFiles) {
    $destination = Join-Path "app\Http\Controllers" $file.Name
    if (Test-Path $file.FullName) {
        Move-Item -Path $file.FullName -Destination $destination -Force
        Write-Host "Movido controlador: $($file.Name) -> $destination"
    }
}

# 4. Mover archivos de configuración
$configFiles = Get-ChildItem -Path . -Include *.php -Recurse | 
    Where-Object { $_.Name -like "*config*.php" -or $_.Name -like "*database*.php" } |
    Where-Object { $_.DirectoryName -notlike "*\config*" }

foreach ($file in $configFiles) {
    $destination = Join-Path "config" $file.Name
    if (Test-Path $file.FullName) {
        Move-Item -Path $file.FullName -Destination $destination -Force
        Write-Host "Movido archivo de configuración: $($file.Name) -> $destination"
    }
}

# 5. Mover migraciones de base de datos
$migrationFiles = Get-ChildItem -Path . -Include *.sql -Recurse | 
    Where-Object { $_.DirectoryName -notlike "*\database\migrations*" }

foreach ($file in $migrationFiles) {
    $migrationName = "migration_$(Get-Date -Format 'yyyyMMddHHmmss')_$($file.Name)"
    $destination = Join-Path "database\migrations" $migrationName
    if (Test-Path $file.FullName) {
        Move-Item -Path $file.FullName -Destination $destination -Force
        Write-Host "Movido archivo de migración: $($file.Name) -> $destination"
    }
}

# 6. Mover archivos de rutas
$routeFiles = Get-ChildItem -Path . -Include *.php -Recurse | 
    Where-Object { $_.Name -like "*routes*.php" -or $_.Name -like "*api.php" } |
    Where-Object { $_.DirectoryName -notlike "*\routes*" }

foreach ($file in $routeFiles) {
    $destination = Join-Path "routes" $file.Name
    if (Test-Path $file.FullName) {
        Move-Item -Path $file.FullName -Destination $destination -Force
        Write-Host "Movido archivo de rutas: $($file.Name) -> $destination"
    }
}

# 7. Mover archivos de la API
$apiFiles = Get-ChildItem -Path "backend\api" -Include *.php -Recurse

foreach ($file in $apiFiles) {
    $relativePath = $file.FullName.Substring($file.FullName.IndexOf("api") + 4)
    $destination = Join-Path "routes\api" $relativePath
    
    # Crear directorio de destino si no existe
    $destinationDir = [System.IO.Path]::GetDirectoryName($destination)
    if (-not (Test-Path $destinationDir)) {
        New-Item -ItemType Directory -Path $destinationDir -Force | Out-Null
    }
    
    if (Test-Path $file.FullName) {
        Move-Item -Path $file.FullName -Destination $destination -Force
        Write-Host "Movido archivo de API: $($file.Name) -> $destination"
    }
}

Write-Host "`nOrganización completada. La nueva estructura es:" -ForegroundColor Green
Get-ChildItem -Path . -Recurse -Directory | Select-Object FullName | Sort-Object FullName
