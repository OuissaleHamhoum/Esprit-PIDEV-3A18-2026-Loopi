$ErrorActionPreference = "Stop"

Write-Host "Starting embedded QR login server on http://localhost:8081" -ForegroundColor Green
Write-Host "This serves the same Symfony public/ directory (port 8081)." -ForegroundColor Gray
Write-Host "Stop with Ctrl+C" -ForegroundColor Yellow

php -S localhost:8081 -t public

