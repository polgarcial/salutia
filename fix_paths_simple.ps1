# Script simple para actualizar rutas en los archivos del proyecto

# 1. Actualizar rutas en archivos PHP
Write-Host "Actualizando rutas en archivos PHP..." -ForegroundColor Cyan

# Obtener todos los archivos PHP
$phpFiles = Get-ChildItem -Path . -Filter "*.php" -Recurse -File

foreach ($file in $phpFiles) {
    $content = Get-Content -Path $file.FullName -Raw -Encoding UTF8
    $originalContent = $content
    
    # Actualizar rutas de inclusión
    $patterns = @{
        "require_once\s*['\"]([^'\"]*?backend/[^'\"]*?)['\"]" = "require_once __DIR__ . '/../`$1'"
        "require\s*['\"]([^'\"]*?backend/[^'\"]*?)['\"]" = "require __DIR__ . '/../`$1'"
        "include_once\s*['\"]([^'\"]*?backend/[^'\"]*?)['\"]" = "include_once __DIR__ . '/../`$1'"
        "include\s*['\"]([^'\"]*?backend/[^'\"]*?)['\"]" = "include __DIR__ . '/../`$1'"
        
        "require_once\s*['\"]([^'\"]*?config/[^'\"]*?)['\"]" = "require_once __DIR__ . '/../config/`$1'"
        "require\s*['\"]([^'\"]*?config/[^'\"]*?)['\"]" = "require __DIR__ . '/../config/`$1'"
        
        "require_once\s*['\"]([^'\"]*?app/[^'\"]*?)['\"]" = "require_once __DIR__ . '/../app/`$1'"
        "require\s*['\"]([^'\"]*?app/[^'\"]*?)['\"]" = "require __DIR__ . '/../app/`$1'"
    }
    
    foreach ($pattern in $patterns.GetEnumerator()) {
        $content = $content -replace $pattern.Key, $pattern.Value
    }
    
    # Actualizar rutas de redirección
    $content = $content -replace "header\s*\(\s*['\"]Location:\s*/[^/]*?/([^'\"]*?)['\"]\s*\)", 'header("Location: /$1")'
    
    # Guardar cambios si hubo modificaciones
    if ($content -ne $originalContent) {
        $content | Set-Content -Path $file.FullName -NoNewline -Encoding UTF8
        Write-Host "Actualizado: $($file.FullName)" -ForegroundColor Green
    }
}

# 2. Actualizar rutas en archivos HTML/JS
Write-Host "`nActualizando rutas en archivos HTML/JS..." -ForegroundColor Cyan

$webFiles = Get-ChildItem -Path . -Include "*.html","*.js" -Recurse -File | 
    Where-Object { $_.DirectoryName -like "*\public*" }

foreach ($file in $webFiles) {
    $content = Get-Content -Path $file.FullName -Raw -Encoding UTF8
    $originalContent = $content
    
    # Actualizar referencias a archivos estáticos
    $patterns = @{
        '(href|src)="([^"]*?)/?css/' = '$1="/css/'
        '(href|src)="([^"]*?)/?js/' = '$1="/js/'
        '(href|src)="([^"]*?)/?images/' = '$1="/images/'
        '(href|src)="([^"]*?)/?uploads/' = '$1="/uploads/'
        '"/api/' = '"/api/'
    }
    
    foreach ($pattern in $patterns.GetEnumerator()) {
        $content = $content -replace $pattern.Key, $pattern.Value
    }
    
    # Guardar cambios si hubo modificaciones
    if ($content -ne $originalContent) {
        $content | Set-Content -Path $file.FullName -NoNewline -Encoding UTF8
        Write-Host "Actualizado: $($file.FullName)" -ForegroundColor Green
    }
}

# 3. Configurar .htaccess
$htaccessPath = Join-Path -Path $PSScriptRoot -ChildPath "public\.htaccess"
if (-not (Test-Path $htaccessPath)) {
    $htaccessContent = @"
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Permitir acceso directo a archivos y directorios existentes
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    
    # Redirigir todo a index.php
    RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
</IfModule>

# Configuración básica de PHP
php_value upload_max_filesize 20M
php_value post_max_size 20M
php_value max_execution_time 300
php_value max_input_time 300
"@
    
    $htaccessContent | Out-File -FilePath $htaccessPath -Encoding UTF8
    Write-Host "Creado archivo .htaccess en: $htaccessPath" -ForegroundColor Green
}

Write-Host "`n=== Actualización de rutas completada ===" -ForegroundColor Green
Write-Host "Estructura del proyecto:" -ForegroundColor Cyan
tree /F /A
