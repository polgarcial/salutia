# Script para limpieza final y optimización de la estructura

# 1. Mover archivos estáticos a la carpeta public
$staticFiles = @(
    "*.css",
    "*.js",
    "*.png",
    "*.jpg",
    "*.jpeg",
    "*.gif",
    "*.ico",
    "*.svg",
    "*.woff",
    "*.woff2",
    "*.ttf",
    "*.eot"
)

foreach ($pattern in $staticFiles) {
    Get-ChildItem -Path . -Filter $pattern -File -Recurse | 
        Where-Object { 
            $_.DirectoryName -notlike "*\public*" -and 
            $_.DirectoryName -notlike "*\node_modules*" -and
            $_.Name -notin @('cleanup_final.ps1', 'reorganize.ps1')
        } | 
        ForEach-Object {
            $relativePath = $_.FullName.Substring((Get-Location).Path.Length + 1)
            $destination = Join-Path "public" $relativePath
            
            # Crear directorio de destino si no existe
            $destinationDir = [System.IO.Path]::GetDirectoryName($destination)
            if (-not (Test-Path $destinationDir)) {
                New-Item -ItemType Directory -Path $destinationDir -Force | Out-Null
            }
            
            Write-Host "Moviendo $($_.FullName) a $destination"
            Move-Item $_.FullName -Destination $destination -Force
        }
}

# 2. Eliminar archivos temporales y de respaldo
$patternsToRemove = @(
    "*.tmp",
    "*.temp",
    "*.bak",
    "*.backup",
    "*~",
    "*.swp",
    "*.swo",
    "Thumbs.db",
    ".DS_Store",
    "desktop.ini"
)

foreach ($pattern in $patternsToRemove) {
    Get-ChildItem -Path . -Filter $pattern -Recurse -File -ErrorAction SilentlyContinue | Remove-Item -Force
}

# 3. Eliminar directorios vacíos
function Remove-EmptyDirectories {
    param(
        [string]$path
    )
    
    $dirs = Get-ChildItem -Path $path -Directory -Recurse -ErrorAction SilentlyContinue | 
            Where-Object { $_.GetFiles().Count -eq 0 -and $_.GetDirectories().Count -eq 0 }
    
    foreach ($dir in $dirs) {
        Write-Host "Eliminando directorio vacío: $($dir.FullName)"
        Remove-Item -Path $dir.FullName -Force -Recurse
    }
    
    # Verificar si el directorio actual está vacío
    if ((Get-ChildItem -Path $path -Force | Measure-Object).Count -eq 0) {
        Write-Host "Eliminando directorio raíz vacío: $path"
        Remove-Item -Path $path -Force -Recurse
    }
}

# 4. Eliminar archivos de depuración y desarrollo
$devFiles = @(
    "*.log",
    "*.sublime-*",
    "*.sublime-project",
    "*.sublime-workspace",
    ".idea",
    ".vscode",
    "*.code-workspace"
)

foreach ($pattern in $devFiles) {
    if ($pattern -match "\*\.") {
        # Es un patrón de archivo
        Get-ChildItem -Path . -Filter $pattern -Recurse -File -ErrorAction SilentlyContinue | Remove-Item -Force
    } else {
        # Es un directorio
        Get-ChildItem -Path . -Directory -Include $pattern -Recurse -ErrorAction SilentlyContinue | Remove-Item -Force -Recurse
    }
}

# 5. Eliminar directorios de dependencias (pueden ser reconstruidos)
$dependencyDirs = @(
    "node_modules",
    "vendor",
    "bower_components",
    ".cache",
    "build",
    "dist"
)

foreach ($dir in $dependencyDirs) {
    if (Test-Path $dir) {
        Write-Host "Eliminando directorio de dependencias: $dir"
        Remove-Item -Path $dir -Recurse -Force -ErrorAction SilentlyContinue
    }
}

# 6. Eliminar archivos específicos innecesarios
$specificFiles = @(
    "composer.lock",
    "package-lock.json",
    "yarn.lock",
    "phpunit.xml",
    ".phpunit.result.cache"
)

foreach ($file in $specificFiles) {
    if (Test-Path $file) {
        Remove-Item -Path $file -Force -ErrorAction SilentlyContinue
    }
}

# 7. Eliminar directorios vacíos recursivamente
$maxDepth = 10
for ($i = 0; $i -lt $maxDepth; $i++) {
    Remove-EmptyDirectories -path .
}

Write-Host "Limpieza final completada. La estructura actual es:" -ForegroundColor Green
Get-ChildItem -Path . -Recurse -Directory | Select-Object FullName | Sort-Object FullName
