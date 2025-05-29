# Script simplificado para actualizar rutas

# 1. Actualizar rutas en archivos PHP
Write-Host "Actualizando rutas en archivos PHP..." -ForegroundColor Cyan

# Obtener todos los archivos PHP excluyendo vendor/
$phpFiles = Get-ChildItem -Path . -Filter "*.php" -Recurse -File | 
    Where-Object { $_.FullName -notlike '*\vendor\*' }

foreach ($file in $phpFiles) {
    try {
        $content = Get-Content -Path $file.FullName -Raw -Encoding UTF8
        $original = $content
        
        # Actualizar rutas de inclusión
        $replacements = @{
            "require_once 'backend/" = "require_once __DIR__ . '/../backend/"
            "require 'backend/" = "require __DIR__ . '/../backend/"
            "include_once 'backend/" = "include_once __DIR__ . '/../backend/"
            "include 'backend/" = "include __DIR__ . '/../backend/"
            
            'require_once "backend/' = 'require_once __DIR__ . "/../backend/'
            'require "backend/' = 'require __DIR__ . "/../backend/'
            'include_once "backend/' = 'include_once __DIR__ . "/../backend/'
            'include "backend/' = 'include __DIR__ . "/../backend/'
            
            "header('Location: /backend/" = "header('Location: /"
            'header("Location: /backend/' = 'header("Location: /'
        }
        
        foreach ($key in $replacements.Keys) {
            $content = $content -replace [regex]::Escape($key), $replacements[$key]
        }
        
        # Guardar cambios si hubo modificaciones
        if ($content -ne $original) {
            $content | Set-Content -Path $file.FullName -NoNewline -Encoding UTF8
            Write-Host "Actualizado: $($file.FullName)" -ForegroundColor Green
        }
    } catch {
        Write-Host "Error procesando $($file.FullName): $_" -ForegroundColor Red
    }
}

# 2. Actualizar rutas en archivos HTML/JS
Write-Host "`nActualizando rutas en archivos HTML/JS..." -ForegroundColor Cyan

$webFiles = Get-ChildItem -Path . -Include "*.html","*.js" -Recurse -File | 
    Where-Object { $_.FullName -like '*\public*' -and $_.FullName -notlike '*\node_modules\*' }

foreach ($file in $webFiles) {
    try {
        $content = Get-Content -Path $file.FullName -Raw -Encoding UTF8
        $original = $content
        
        # Actualizar referencias a archivos estáticos
        $replacements = @{
            'href="/backend/' = 'href="/'
            'src="/backend/' = 'src="/'
            'url("/backend/' = 'url("/'
            '"/api/' = '"/api/'
        }
        
        foreach ($key in $replacements.Keys) {
            $content = $content -replace [regex]::Escape($key), $replacements[$key]
        }
        
        # Guardar cambios si hubo modificaciones
        if ($content -ne $original) {
            $content | Set-Content -Path $file.FullName -NoNewline -Encoding UTF8
            Write-Host "Actualizado: $($file.FullName)" -ForegroundColor Green
        }
    } catch {
        Write-Host "Error procesando $($file.FullName): $_" -ForegroundColor Red
    }
}

# 3. Crear .htaccess si no existe
$htaccessPath = Join-Path -Path $PSScriptRoot -ChildPath "public\.htaccess"
if (-not (Test-Path $htaccessPath)) {
    @'
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
'@ | Out-File -FilePath $htaccessPath -Encoding UTF8
    
    Write-Host "Creado archivo .htaccess en: $htaccessPath" -ForegroundColor Green
}

Write-Host "`n=== Actualización de rutas completada ===" -ForegroundColor Green
Write-Host "Estructura del proyecto:" -ForegroundColor Cyan
Get-ChildItem -Path . -Recurse -Directory | Select-Object FullName | Sort-Object FullName
