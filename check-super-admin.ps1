# Check if super admin exists in database

Write-Host "Checking for existing super admins..." -ForegroundColor Cyan

$backendPath = Join-Path $PSScriptRoot "backend"
if (-not (Test-Path $backendPath)) {
    Write-Host "Error: backend directory not found" -ForegroundColor Red
    exit 1
}

Set-Location $backendPath

# Run tinker command to check
$result = php artisan tinker --execute="echo App\Models\User::where('role', 'super_admin')->count();" 2>&1

if ($LASTEXITCODE -eq 0) {
    $count = $result.Trim()
    if ([int]$count -gt 0) {
        Write-Host "`n⚠️  Found $count super admin(s) in the database" -ForegroundColor Yellow
        Write-Host "`nTo create additional super admins, you must:" -ForegroundColor Cyan
        Write-Host "  1. Log in as an existing super admin" -ForegroundColor White
        Write-Host "  2. Go to User Management page" -ForegroundColor White
        Write-Host "  3. Click 'Create Super Admin' button" -ForegroundColor White
        Write-Host "`nTo reset and create the first super admin:" -ForegroundColor Cyan
        Write-Host "  1. Run: php artisan migrate:fresh" -ForegroundColor White
        Write-Host "  2. This will delete all users and reset the database" -ForegroundColor Yellow
    } else {
        Write-Host "`n✓ No super admins found in database" -ForegroundColor Green
        Write-Host "You can create the first super admin using the secret key." -ForegroundColor Cyan
    }
} else {
    Write-Host "Error checking database: $result" -ForegroundColor Red
}

