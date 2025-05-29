# Script para reorganizar la estructura del proyecto Salutia

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
    "app\Http\Middleware",
    "app\Models",
    "config",
    "database",
    "database\migrations",
    "database\seeders",
    "resources",
    "resources\views",
    "routes",
    "tests",
    "storage",
    "storage\app",
    "storage\logs"
)

foreach ($dir in $directories) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir | Out-Null
    }
}

# 2. Mover archivos frontend a public/
$frontendFiles = @(
    "*.html"
    "*.css"
    "*.js"
    "*.png"
    "*.jpg"
    "*.jpeg"
    "*.gif"
    "*.ico"
    "*.svg"
)

foreach ($pattern in $frontendFiles) {
    Get-ChildItem -Path . -Filter $pattern -File | 
        Where-Object { $_.DirectoryName -notlike "*\public*" -and $_.Name -ne "reorganize.ps1" } | 
        ForEach-Object {
            $destination = Join-Path "public" $_.Name
            if (Test-Path $destination) {
                Remove-Item $destination -Force
            }
            Move-Item $_.FullName -Destination "public\" -Force
        }
}

# 3. Mover archivos de backend
# Mover controladores
if (Test-Path "backend\api\controllers") {
    Get-ChildItem -Path "backend\api\controllers" -Filter "*.php" | 
        ForEach-Object {
            $destination = Join-Path "app\Http\Controllers" $_.Name
            if (Test-Path $destination) {
                Remove-Item $destination -Force
            }
            Move-Item $_.FullName -Destination $destination -Force
        }
}

# Mover modelos
if (Test-Path "backend\models") {
    if (-not (Test-Path "app\Models")) {
        New-Item -ItemType Directory -Path "app\Models" | Out-Null
    }
    Get-ChildItem -Path "backend\models" -Filter "*.php" | 
        ForEach-Object {
            $destination = Join-Path "app\Models" $_.Name
            if (Test-Path $destination) {
                Remove-Item $destination -Force
            }
            Move-Item $_.FullName -Destination $destination -Force
        }
}

# Mover configuraciones
if (Test-Path "backend\config") {
    Get-ChildItem -Path "backend\config" -Filter "*.php" | 
        ForEach-Object {
            $destination = Join-Path "config" $_.Name
            if (Test-Path $destination) {
                Remove-Item $destination -Force
            }
            Move-Item $_.FullName -Destination $destination -Force
        }
}

# Mover migraciones
if (Test-Path "backend\database") {
    Get-ChildItem -Path "backend\database" -Filter "*.sql" | 
        ForEach-Object {
            $migrationName = "migration_$(Get-Date -Format 'yyyyMMddHHmmss')_$($_.Name)"
            $destination = Join-Path "database\migrations" $migrationName
            if (Test-Path $destination) {
                Remove-Item $destination -Force
            }
            Move-Item $_.FullName -Destination $destination -Force
        }
}

# 4. Limpiar archivos innecesarios
$filesToRemove = @(
    "*.bak",
    "*.tmp",
    "*.log",
    "Thumbs.db",
    ".DS_Store",
    "*.swo",
    "*.swp",
    "*~",
    "*.old"
)

foreach ($pattern in $filesToRemove) {
    Get-ChildItem -Path . -Filter $pattern -Recurse -File | Remove-Item -Force
}

# 5. Eliminar directorios vacíos
Get-ChildItem -Path . -Directory -Recurse | 
    Where-Object { $_.GetFiles().Count -eq 0 -and $_.GetDirectories().Count -eq 0 } | 
    Remove-Item -Force -Recurse

Write-Host "Reorganización completada. La nueva estructura es:" -ForegroundColor Green
Get-ChildItem -Path . -Recurse -Directory | Select-Object FullName | Sort-Object FullName
