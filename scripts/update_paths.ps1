# Script para actualizar rutas en archivos HTML, PHP y JS

# 1. Actualizar referencias a archivos CSS
Get-ChildItem -Path . -Include *.html,*.php -Recurse | ForEach-Object {
    $content = Get-Content $_.FullName -Raw
    
    # Actualizar referencias a CSS
    $content = $content -replace '(?<prefix><link[^>]*href=["''])(?![a-z]+:)([^"'']*\.css)(?=["''][^>]*>)', "`${1}frontend/css/`${2}"
    
    # Actualizar referencias a JS
    $content = $content -replace '(?<prefix><script[^>]*src=["''])(?![a-z]+:)([^"'']*\.js)(?=["''][^>]*>)', "`${1}frontend/js/`${2}"
    
    # Actualizar referencias a imágenes
    $content = $content -replace '(?<prefix><img[^>]*src=["''])(?![a-z]+:)([^"'']*\.(?:png|jpg|jpeg|gif|svg|ico))(?=["''][^>]*>)', "`${1}frontend/img/`${2}"
    
    # Actualizar referencias a la API
    $content = $content -replace '(?<prefix>["''])/api/', "`${1}backend/api/"
    
    # Guardar cambios
    Set-Content -Path $_.FullName -Value $content -NoNewline
}

# 2. Actualizar rutas en archivos JavaScript
Get-ChildItem -Path . -Include *.js -Recurse | ForEach-Object {
    $content = Get-Content $_.FullName -Raw
    
    # Actualizar rutas de API
    $content = $content -replace '(?<![\w/])(?<!/)/api/', '/backend/api/'
    
    # Actualizar rutas de recursos
    $content = $content -replace '(?<![\w/])(?<!/)/(css|js|img)/', '/frontend/$1/'
    
    # Guardar cambios
    Set-Content -Path $_.FullName -Value $content -NoNewline
}

Write-Host "Rutas actualizadas correctamente" -ForegroundColor Green
