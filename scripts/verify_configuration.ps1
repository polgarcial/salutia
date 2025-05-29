# Script de verificación simplificado

# 1. Verificar archivos requeridos
Write-Host "=== Verificando archivos requeridos ==="

$files = @(
    "public\.htaccess",
    "backend\config\database.php",
    "public\index.php"
)

foreach ($file in $files) {
    if (Test-Path $file) {
        Write-Host "[OK] $file" -ForegroundColor Green
    } else {
        Write-Host "[FALTA] $file" -ForegroundColor Red
    }
}

# 2. Verificar programas
Write-Host "`n=== Verificando programas requeridos ==="

$programs = @(
    @{Name="PHP"; Cmd="php -v"},
    @{Name="Node.js"; Cmd="node --version"},
    @{Name="npm"; Cmd="npm --version"}
)

foreach ($prog in $programs) {
    $output = cmd /c $prog.Cmd 2>&1 | Select-Object -First 1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "[OK] $($prog.Name): $output" -ForegroundColor Green
    } else {
        Write-Host "[NO INSTALADO] $($prog.Name)" -ForegroundColor Red
    }
}

Write-Host "`nVerificación completada."
