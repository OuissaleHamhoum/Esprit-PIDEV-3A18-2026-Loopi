# start-qr-server.ps1
Write-Host "Starting QR Login Helper Server on port 8081..." -ForegroundColor Green
Write-Host "Make sure your Symfony app is running on port 8080" -ForegroundColor Yellow
Write-Host "Press Ctrl+C to stop the server" -ForegroundColor Yellow
Write-Host ""

php -S 0.0.0.0:8081 -t public