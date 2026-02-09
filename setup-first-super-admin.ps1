# Automated setup for first super admin

Write-Host "Setting up for first super admin creation..." -ForegroundColor Green
Write-Host ""

$backendPath = Join-Path $PSScriptRoot "backend"
if (-not (Test-Path $backendPath)) {
    Write-Host "Error: backend directory not found" -ForegroundColor Red
    exit 1
}

Set-Location $backendPath

# Step 1: Reset database
Write-Host "Step 1: Resetting database..." -ForegroundColor Cyan
php artisan migrate:fresh
if ($LASTEXITCODE -ne 0) {
    Write-Host "Error: Database reset failed" -ForegroundColor Red
    exit 1
}
Write-Host "Database reset complete" -ForegroundColor Green
Write-Host ""

# Step 2: Ensure secret key exists
Write-Host "Step 2: Checking secret key..." -ForegroundColor Cyan
Set-Location ..
$envContent = Get-Content "backend\.env" -Raw -ErrorAction SilentlyContinue

if ($envContent -and $envContent -match "SUPER_ADMIN_SECRET_KEY\s*=\s*(.+)") {
    $secretKey = $matches[1].Trim()
    Write-Host "Secret key found" -ForegroundColor Green
} else {
    Write-Host "Generating new secret key..." -ForegroundColor Yellow
    Add-Type -AssemblyName System.Security
    $rng = New-Object System.Security.Cryptography.RNGCryptoServiceProvider
    $bytes = New-Object byte[] 32
    $rng.GetBytes($bytes)
    $secretKey = [System.BitConverter]::ToString($bytes).Replace("-", "").ToLower()
    
    if ($envContent -notmatch "\n$") {
        Add-Content -Path "backend\.env" -Value "`n"
    }
    Add-Content -Path "backend\.env" -Value "`n# Super Admin Secret Key`nSUPER_ADMIN_SECRET_KEY=$secretKey"
    Write-Host "Secret key generated and added" -ForegroundColor Green
}

Write-Host ""
Write-Host "===========================================================" -ForegroundColor Cyan
Write-Host "Setup Complete!" -ForegroundColor Green
Write-Host "===========================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Your Secret Key:" -ForegroundColor Yellow
Write-Host $secretKey -ForegroundColor White -BackgroundColor DarkBlue
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Cyan
Write-Host "  1. Visit: http://localhost:5173/create-super-admin" -ForegroundColor White
Write-Host "  2. Enter the secret key above" -ForegroundColor White
Write-Host "  3. Fill in your details (name, email, password)" -ForegroundColor White
Write-Host "  4. Submit to create your first super admin account" -ForegroundColor White
Write-Host ""
Write-Host "WARNING: Keep this secret key secure!" -ForegroundColor Yellow
Write-Host ""

