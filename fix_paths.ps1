# Script para actualizar rutas en los archivos del proyecto

# 1. Actualizar rutas en archivos PHP
$phpFiles = Get-ChildItem -Path . -Include *.php -Recurse -File

foreach ($file in $phpFiles) {
    $content = Get-Content -Path $file.FullName -Raw
    
    # Actualizar includes/requires
    $content = $content -replace 'require_once\s*[\'\"]([^\'\"]*?backend\/[^\'\"]*?)[\'\"]', "require_once __DIR__ . '/../`$1'"
    $content = $content -replace 'require_once\s*[\'\"]([^\'\"]*?config\/[^\'\"]*?)[\'\"]', "require_once __DIR__ . '/../config/`$1'"
    $content = $content -replace 'require_once\s*[\'\"]([^\'\"]*?app\/[^\'\"]*?)[\'\"]', "require_once __DIR__ . '/../app/`$1'"
    
    # Actualizar rutas de redirección
    $content = $content -replace 'header\s*\(\s*[\'\"]Location:\s*\/[^\'\"]*?\/([^\'\"]*?)[\'\"]\)', "header('Location: /`$1')"
    
    # Guardar cambios
    $content | Set-Content -Path $file.FullName -NoNewline
    Write-Host "Actualizado: $($file.FullName)"
}

# 2. Actualizar rutas en archivos HTML/JS
$webFiles = Get-ChildItem -Path . -Include *.html,*.js -Recurse -File | 
    Where-Object { $_.DirectoryName -like "*\public*" }

foreach ($file in $webFiles) {
    $content = Get-Content -Path $file.FullName -Raw
    
    # Actualizar referencias a archivos CSS/JS/Imágenes
    $content = $content -replace '(href|src)="([^"]*?)/?css/', '$1="/css/'
    $content = $content -replace '(href|src)="([^"]*?)/?js/', '$1="/js/'
    $content = $content -replace '(href|src)="([^"]*?)/?images/', '$1="/images/'
    
    # Actualizar rutas de API
    $content = $content -replace '"/api/', '"/api/'
    
    # Guardar cambios
    $content | Set-Content -Path $file.FullName -NoNewline
    Write-Host "Actualizado: $($file.FullName)"
}

# 3. Actualizar .htaccess
$htaccessPath = Join-Path $PSScriptRoot "public\.htaccess"
if (Test-Path $htaccessPath) {
    $htaccess = @"
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Redirigir todo a la carpeta public
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ public/$1 [L]
    
    # Manejo de rutas en public
    RewriteBase /public/
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
</IfModule>
"@
    $htaccess | Set-Content -Path $htaccessPath -NoNewline
    Write-Host "Actualizado: $htaccessPath"
}

# 4. Crear archivo index.php en la raíz del proyecto
$indexPhpPath = Join-Path $PSScriptRoot "public\index.php"
if (Test-Path $indexPhpPath) {
    $indexPhp = @"
<?php
// Cargar el autoload de Composer si existe
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
}

// Definir constantes de la aplicación
define('BASE_PATH', realpath(__DIR__ . '/..'));
define('APP_PATH', BASE_PATH . '/app');

try {
    // Incluir el archivo de configuración
    require_once APP_PATH . '/config/config.php';
    
    // Iniciar la aplicación
    require_once APP_PATH . '/bootstrap.php';
    
    // Aquí iría el enrutamiento de tu aplicación
    // Por ejemplo, usando un enrutador simple:
    \$request = \$_SERVER['REQUEST_URI'];
    \$basePath = str_replace('/public', '', parse_url(\$_SERVER['REQUEST_URI'], PHP_URL_PATH));
    
    // Ejemplo de enrutamiento básico
    switch (\$basePath) {
        case '/':
            require __DIR__ . '/index.html';
            break;
        case '/api/pacientes':
            require APP_PATH . '/controllers/PacienteController.php';
            break;
        // Agrega más rutas según sea necesario
        default:
            http_response_code(404);
            echo '404 Not Found';
            break;
    }
    
} catch (\Exception \$e) {
    // Manejo de errores
    http_response_code(500);
    echo 'Error: ' . \$e->getMessage();
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo '<pre>' . \$e->getTraceAsString() . '</pre>';
    }
}
"@
    $indexPhp | Set-Content -Path $indexPhpPath -NoNewline
    Write-Host "Creado: $indexPhpPath"
}

Write-Host "`n=== Actualización de rutas completada ===" -ForegroundColor Green
