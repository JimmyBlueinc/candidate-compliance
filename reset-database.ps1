# Reset database and remove all users (including super admins)

Write-Host "⚠️  WARNING: This will delete ALL data from the database!" -ForegroundColor Red
Write-Host "This includes all users, credentials, and other data." -ForegroundColor Yellow
Write-Host ""
$confirm = Read-Host "Type 'yes' to continue, or anything else to cancel"

if ($confirm -ne 'yes') {
    Write-Host "Cancelled." -ForegroundColor Yellow
    exit 0
}

$backendPath = Join-Path $PSScriptRoot "backend"
if (-not (Test-Path $backendPath)) {
    Write-Host "Error: backend directory not found" -ForegroundColor Red
    exit 1
}

Set-Location $backendPath

Write-Host "`nResetting database..." -ForegroundColor Cyan
php artisan migrate:fresh

Write-Host "`n✓ Database reset complete!" -ForegroundColor Green
Write-Host "You can now create the first super admin using the secret key." -ForegroundColor Cyan

