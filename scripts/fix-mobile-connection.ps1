# Quick Fix Script for Mobile Connection Issues
# This script stops any existing servers and restarts them correctly

Write-Host "🔧 Fixing Mobile Connection Issues..." -ForegroundColor Yellow
Write-Host ""

# Get local IP address
$ipAddress = (Get-NetIPAddress -AddressFamily IPv4 | Where-Object { 
    $_.IPAddress -like "192.168.*" -or $_.IPAddress -like "10.*" -or $_.IPAddress -like "172.16*" 
} | Select-Object -First 1).IPAddress

if (-not $ipAddress) {
    Write-Host "⚠️  Could not detect local IP address. Using 192.168.88.196" -ForegroundColor Yellow
    $ipAddress = "192.168.88.196"
}

Write-Host "📍 Detected IP: $ipAddress" -ForegroundColor Cyan
Write-Host ""

# Stop any existing PHP/Laravel processes
Write-Host "🛑 Stopping existing backend servers..." -ForegroundColor Yellow
Get-Process | Where-Object { $_.ProcessName -eq "php" } | ForEach-Object {
    Write-Host "   Stopping PHP process (PID: $($_.Id))" -ForegroundColor Gray
    Stop-Process -Id $_.Id -Force -ErrorAction SilentlyContinue
}

# Stop any Node/Vite processes
Write-Host "🛑 Stopping existing frontend servers..." -ForegroundColor Yellow
Get-Process | Where-Object { $_.ProcessName -eq "node" } | ForEach-Object {
    Write-Host "   Stopping Node process (PID: $($_.Id))" -ForegroundColor Gray
    Stop-Process -Id $_.Id -Force -ErrorAction SilentlyContinue
}

Start-Sleep -Seconds 2

# Update .env files
Write-Host "📝 Updating configuration files..." -ForegroundColor Yellow

# Frontend .env
$frontendEnv = "frontend\.env"
$frontendContent = @()
if (Test-Path $frontendEnv) {
    $frontendContent = Get-Content $frontendEnv
    $frontendContent = $frontendContent | Where-Object { $_ -notmatch '^VITE_API_BASE_URL=' }
}
$frontendContent += "VITE_API_BASE_URL=http://$ipAddress`:8000/api"
$frontendContent | Set-Content $frontendEnv
Write-Host "   ✅ Frontend .env updated" -ForegroundColor Green

# Backend .env
$backendEnv = "Agenchq\.env"
$backendContent = @()
if (Test-Path $backendEnv) {
    $backendContent = Get-Content $backendEnv
    $backendContent = $backendContent | Where-Object { $_ -notmatch '^FRONTEND_URL=' }
}
$backendContent += "FRONTEND_URL=http://$ipAddress`:5173"
$backendContent | Set-Content $backendEnv
Write-Host "   ✅ Backend .env updated" -ForegroundColor Green

Write-Host ""

# Start backend server
Write-Host "🚀 Starting Laravel backend on 0.0.0.0:8000..." -ForegroundColor Green
$backendProcess = Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PWD\Agenchq'; Write-Host 'Laravel Backend Server' -ForegroundColor Cyan; Write-Host 'Listening on: http://0.0.0.0:8000' -ForegroundColor Green; Write-Host 'Network access: http://$ipAddress`:8000' -ForegroundColor Yellow; Write-Host ''; php artisan serve --host=0.0.0.0 --port=8000" -PassThru

Start-Sleep -Seconds 3

# Verify backend is listening
$listening = netstat -an | Select-String "0.0.0.0:8000.*LISTENING"
if ($listening) {
    Write-Host "   ✅ Backend is listening on 0.0.0.0:8000" -ForegroundColor Green
} else {
    Write-Host "   ⚠️  Backend may not have started correctly" -ForegroundColor Yellow
}

# Start frontend server
Write-Host "🚀 Starting Vite frontend on 0.0.0.0:5173..." -ForegroundColor Green
$frontendProcess = Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PWD\frontend'; Write-Host 'Vite Frontend Server' -ForegroundColor Cyan; Write-Host 'Listening on: http://0.0.0.0:5173' -ForegroundColor Green; Write-Host 'Network access: http://$ipAddress`:5173' -ForegroundColor Yellow; Write-Host ''; npm run dev" -PassThru

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "✅ Servers Started Successfully!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "📱 Mobile Access:" -ForegroundColor Yellow
Write-Host "   Frontend: http://$ipAddress`:5173" -ForegroundColor White
Write-Host "   Backend:  http://$ipAddress`:8000" -ForegroundColor White
Write-Host ""
Write-Host "💡 Tips:" -ForegroundColor Yellow
Write-Host "   - Make sure your phone is on the same Wi-Fi network" -ForegroundColor Gray
Write-Host "   - Check Windows Firewall allows ports 8000 and 5173" -ForegroundColor Gray
Write-Host "   - If connection fails, check the server windows for errors" -ForegroundColor Gray
Write-Host ""
Write-Host "Press any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

