# Script para configurar el entorno de desarrollo

# 1. Crear archivo de configuración de base de datos si no existe
$dbConfigPath = "backend\config\database.php"
if (-not (Test-Path $dbConfigPath)) {
    $dbConfig = @"
<?php
return [
    'host' => 'localhost',
    'database' => 'salutia',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
"@
    Set-Content -Path $dbConfigPath -Value $dbConfig
}

# 2. Crear archivo .htaccess si no existe
$htaccessPath = "public\.htaccess"
if (-not (Test-Path $htaccessPath)) {
    $htaccessContent = @"
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
"@
    Set-Content -Path $htaccessPath -Value $htaccessContent
}

# 3. Crear directorios necesarios
$directories = @(
    "public/uploads",
    "storage/logs",
    "storage/framework/sessions",
    "storage/framework/views",
    "storage/framework/cache"
)

foreach ($dir in $directories) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
    }
}

# 4. Establecer permisos (solo para entornos Unix/Linux)
if ($IsLinux -or $IsMacOS) {
    chmod -R 755 storage
    chmod -R 755 bootstrap/cache
}

Write-Host "Configuración del entorno completada" -ForegroundColor Green
