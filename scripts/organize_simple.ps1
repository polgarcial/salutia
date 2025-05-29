# Script simple para organizar archivos

# 1. Crear carpetas principales
$folders = @("app", "config", "database", "public", "resources", "routes", "scripts", "storage", "docs")
foreach ($folder in $folders) {
    if (-not (Test-Path $folder)) {
        New-Item -ItemType Directory -Path $folder -Force | Out-Null
    }
}

# 2. Mover archivos por extensión
$fileTypes = @{
    "*.php" = "app"
    "*.sql" = "database"
    "*.md" = "docs"
    "*.ps1" = "scripts"
    "*.bat" = "scripts"
    "*.html" = "public"
    "*.css" = "public\css"
    "*.js" = "public\js"
    "*.png|*.jpg|*.jpeg|*.gif|*.ico" = "public\images"
}

foreach ($ext in $fileTypes.Keys) {
    $targetDir = $fileTypes[$ext]
    if (-not (Test-Path $targetDir)) {
        New-Item -ItemType Directory -Path $targetDir -Force | Out-Null
    }
    
    Get-ChildItem -Path . -Include $ext.Split('|') -File -Recurse | 
    Where-Object { $_.DirectoryName -notlike "*\$targetDir*" -and $_.DirectoryName -notlike "*\node_modules*" } |
    ForEach-Object {
        $dest = Join-Path $targetDir $_.Name
        if (-not (Test-Path $dest)) {
            Move-Item -Path $_.FullName -Destination $dest -Force
            Write-Host "Movido: $($_.Name) -> $targetDir"
        }
    }
}

# 3. Crear subcarpetas necesarias
$subfolders = @("app\Http\Controllers", "database\migrations", "public\uploads", "resources\views", "routes\api")
foreach ($folder in $subfolders) {
    if (-not (Test-Path $folder)) {
        New-Item -ItemType Directory -Path $folder -Force | Out-Null
    }
}

# 4. Mover archivos específicos a sus ubicaciones finales
# Mover controladores
Get-ChildItem -Path . -Filter "*Controller.php" -Recurse | 
Where-Object { $_.DirectoryName -notlike "*\app\Http\Controllers*" } |
ForEach-Object {
    $dest = Join-Path "app\Http\Controllers" $_.Name
    Move-Item -Path $_.FullName -Destination $dest -Force
}

# Mover archivos de la API
if (Test-Path "backend\api") {
    Get-ChildItem -Path "backend\api" -Recurse -File | 
    ForEach-Object {
        $relativePath = $_.FullName.Substring($_.FullName.IndexOf("api") + 4)
        $dest = Join-Path "routes\api" $relativePath
        $destDir = [System.IO.Path]::GetDirectoryName($dest)
        if (-not (Test-Path $destDir)) {
            New-Item -ItemType Directory -Path $destDir -Force | Out-Null
        }
        Move-Item -Path $_.FullName -Destination $dest -Force
    }
}

# 5. Limpiar directorios vacíos
Get-ChildItem -Path . -Directory -Recurse | 
Where-Object { $_.GetFiles().Count -eq 0 -and $_.GetDirectories().Count -eq 0 } | 
Remove-Item -Force -Recurse -ErrorAction SilentlyContinue

Write-Host "`n=== Organización completada ===" -ForegroundColor Green
Write-Host "Estructura del proyecto:" -ForegroundColor Cyan
tree /F /A
