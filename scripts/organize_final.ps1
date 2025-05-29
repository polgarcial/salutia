# Script para organizar el proyecto Salutia

# 1. Crear estructura de directorios
$directories = @(
    "app/Http/Controllers",
    "app/Models",
    "config",
    "database/migrations",
    "public/css",
    "public/js",
    "public/images",
    "public/uploads",
    "resources/views",
    "routes/api",
    "scripts",
    "storage/logs",
    "storage/framework/sessions",
    "storage/framework/views",
    "storage/framework/cache",
    "docs"
)

foreach ($dir in $directories) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
    }
}

# 2. Mover archivos de configuración
$configFiles = @("*.php", "*.env*", "*.json")
foreach ($ext in $configFiles) {
    Get-ChildItem -Path . -Filter $ext -File -Recurse | 
    Where-Object { $_.DirectoryName -notlike "*\app*" -and $_.DirectoryName -notlike "*\config*" } |
    ForEach-Object {
        $dest = Join-Path "config" $_.Name
        if (-not (Test-Path $dest)) {
            Move-Item -Path $_.FullName -Destination $dest -Force
            Write-Host "Movido a config: $($_.Name)"
        }
    }
}

# 3. Mover controladores
Get-ChildItem -Path . -Filter "*Controller.php" -Recurse | 
Where-Object { $_.DirectoryName -notlike "*\app\Http\Controllers*" } |
ForEach-Object {
    $dest = Join-Path "app\Http\Controllers" $_.Name
    if (-not (Test-Path $dest)) {
        Move-Item -Path $_.FullName -Destination $dest -Force
        Write-Host "Movido controlador: $($_.Name)"
    }
}

# 4. Mover migraciones
Get-ChildItem -Path . -Filter "*.sql" -Recurse | 
Where-Object { $_.DirectoryName -notlike "*\database\migrations*" } |
ForEach-Object {
    $migrationName = "migration_$(Get-Date -Format 'yyyyMMddHHmmss')_$($_.Name)"
    $dest = Join-Path "database\migrations" $migrationName
    Move-Item -Path $_.FullName -Destination $dest -Force
    Write-Host "Movida migración: $($_.Name)"
}

# 5. Mover scripts
$scriptFiles = @("*.ps1", "*.bat", "*.sh")
foreach ($ext in $scriptFiles) {
    Get-ChildItem -Path . -Filter $ext -File | 
    Where-Object { $_.DirectoryName -notlike "*\scripts*" } |
    ForEach-Object {
        $dest = Join-Path "scripts" $_.Name
        if (-not (Test-Path $dest)) {
            Move-Item -Path $_.FullName -Destination $dest -Force
            Write-Host "Movido script: $($_.Name)"
        }
    }
}

# 6. Mover archivos frontend
$frontendFiles = @("*.html", "*.css", "*.js", "*.png", "*.jpg", "*.jpeg", "*.gif", "*.ico")
foreach ($ext in $frontendFiles) {
    Get-ChildItem -Path . -Filter $ext -File -Recurse | 
    Where-Object { $_.DirectoryName -notlike "*\public*" -and $_.DirectoryName -notlike "*\node_modules*" } |
    ForEach-Object {
        $subdir = switch ($_.Extension) {
            ".css" { "css" }
            ".js"  { "js" }
            ".png" { "images" }
            ".jpg" { "images" }
            ".jpeg" { "images" }
            ".gif" { "images" }
            ".ico" { "images" }
            default { "" }
        }
        
        $destDir = Join-Path "public" $subdir
        if (-not (Test-Path $destDir)) {
            New-Item -ItemType Directory -Path $destDir -Force | Out-Null
        }
        
        $dest = Join-Path $destDir $_.Name
        if (-not (Test-Path $dest)) {
            Move-Item -Path $_.FullName -Destination $dest -Force
            Write-Host "Movido a public\$subdir: $($_.Name)"
        }
    }
}

# 7. Mover documentación
Get-ChildItem -Path . -Filter "*.md" -File | 
Where-Object { $_.DirectoryName -notlike "*\docs*" } |
ForEach-Object {
    $dest = Join-Path "docs" $_.Name
    if (-not (Test-Path $dest)) {
        Move-Item -Path $_.FullName -Destination $dest -Force
        Write-Host "Movido a docs: $($_.Name)"
    }
}

# 8. Mover archivos .htaccess
Get-ChildItem -Path . -Filter ".htaccess" -File -Recurse | 
Where-Object { $_.DirectoryName -ne (Join-Path (Get-Location) "public") } |
ForEach-Object {
    $dest = Join-Path "public" $_.Name
    if (-not (Test-Path $dest)) {
        Move-Item -Path $_.FullName -Destination $dest -Force
        Write-Host "Movido .htaccess a carpeta public"
    }
}

Write-Host "`n=== Organización completada ===" -ForegroundColor Green
Write-Host "Estructura del proyecto:" -ForegroundColor Cyan
Get-ChildItem -Path . -Recurse -Directory | Select-Object FullName | Sort-Object FullName
