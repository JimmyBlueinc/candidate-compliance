# Start Laravel backend server for mobile access

Write-Host "Starting Laravel backend server..." -ForegroundColor Cyan

# Check if already running
$phpProcess = Get-Process | Where-Object {$_.ProcessName -like "*php*" -and $_.Path -like "*php.exe*"} | Select-Object -First 1

if ($phpProcess) {
    Write-Host "PHP process found. Stopping existing server..." -ForegroundColor Yellow
    Stop-Process -Id $phpProcess.Id -Force
    Start-Sleep -Seconds 2
}

# Navigate to backend directory
$backendPath = Join-Path $PSScriptRoot "backend"
if (-not (Test-Path $backendPath)) {
    Write-Host "Error: backend directory not found at $backendPath" -ForegroundColor Red
    exit 1
}

Set-Location $backendPath

# Start Laravel server on all interfaces
Write-Host "Starting server on http://0.0.0.0:8000..." -ForegroundColor Green
Start-Process powershell -ArgumentList "-NoExit", "-Command", "php artisan serve --host=0.0.0.0 --port=8000"

Write-Host "Backend server started!" -ForegroundColor Green
Write-Host "Server should be accessible at http://192.168.88.196:8000" -ForegroundColor Cyan
Write-Host "Press any key to exit this window (server will continue running)..." -ForegroundColor Yellow
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

