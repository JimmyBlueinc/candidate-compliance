# Goodwill Staffing - Frontend Deployment Script (PowerShell)
# This script builds the React frontend for production deployment

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Frontend Production Deployment" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if we're in the frontend directory
if (-not (Test-Path "package.json")) {
    Write-Host "Error: This script must be run from the frontend directory" -ForegroundColor Red
    Write-Host "Please run: cd frontend" -ForegroundColor Yellow
    exit 1
}

# Check for required tools
Write-Host "Checking prerequisites..." -ForegroundColor Yellow

# Check Node.js
try {
    $nodeVersion = node -v
    $nodeMajor = [int]($nodeVersion -replace "v(\d+)\..*", '$1')
    if ($nodeMajor -lt 18) {
        Write-Host "Error: Node.js 18+ required. Found: $nodeVersion" -ForegroundColor Red
        exit 1
    }
    Write-Host "✓ Node.js $nodeVersion found" -ForegroundColor Green
} catch {
    Write-Host "Error: Node.js not found. Please install Node.js 18+" -ForegroundColor Red
    exit 1
}

# Check npm
try {
    $npmVersion = npm -v
    Write-Host "✓ npm $npmVersion found" -ForegroundColor Green
} catch {
    Write-Host "Error: npm not found" -ForegroundColor Red
    exit 1
}

Write-Host ""

# Step 1: Check environment configuration
Write-Host "Step 1: Checking environment configuration..." -ForegroundColor Yellow
if (-not (Test-Path ".env.production")) {
    if (Test-Path ".env") {
        Write-Host "⚠ .env.production not found, using .env" -ForegroundColor Yellow
    } else {
        Write-Host "Creating .env.production template..." -ForegroundColor Yellow
        $apiUrl = Read-Host "Enter your production API URL (e.g., https://api.yourdomain.com/api)"
        "VITE_API_BASE_URL=$apiUrl" | Out-File -FilePath ".env.production" -Encoding utf8
        Write-Host "✓ .env.production created" -ForegroundColor Green
    }
} else {
    Write-Host "✓ .env.production found" -ForegroundColor Green
    $content = Get-Content ".env.production" -Raw
    if ($content -match "VITE_API_BASE_URL") {
        Write-Host "  API URL: $($content -match 'VITE_API_BASE_URL=(.+)')" -ForegroundColor Cyan
    }
}
Write-Host ""

# Step 2: Install dependencies
Write-Host "Step 2: Installing dependencies..." -ForegroundColor Yellow
npm install
if ($LASTEXITCODE -ne 0) {
    Write-Host "Error: Failed to install dependencies" -ForegroundColor Red
    exit 1
}
Write-Host "✓ Dependencies installed" -ForegroundColor Green
Write-Host ""

# Step 3: Build for production
Write-Host "Step 3: Building for production..." -ForegroundColor Yellow
Write-Host "This may take a few minutes..." -ForegroundColor Cyan
npm run build
if ($LASTEXITCODE -ne 0) {
    Write-Host "Error: Build failed" -ForegroundColor Red
    exit 1
}
Write-Host "✓ Build completed successfully" -ForegroundColor Green
Write-Host ""

# Step 4: Check build output
Write-Host "Step 4: Verifying build output..." -ForegroundColor Yellow
if (Test-Path "dist") {
    $distSize = (Get-ChildItem -Path "dist" -Recurse | Measure-Object -Property Length -Sum).Sum / 1MB
    Write-Host "✓ Build output found in dist/ folder ($([math]::Round($distSize, 2)) MB)" -ForegroundColor Green
    
    if (Test-Path "dist/index.html") {
        Write-Host "✓ index.html found" -ForegroundColor Green
    } else {
        Write-Host "⚠ Warning: index.html not found in dist/" -ForegroundColor Yellow
    }
} else {
    Write-Host "Error: dist/ folder not found. Build may have failed." -ForegroundColor Red
    exit 1
}
Write-Host ""

# Summary
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Build Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Build output location: dist/" -ForegroundColor Yellow
Write-Host ""
Write-Host "Deployment options:" -ForegroundColor Yellow
Write-Host "1. Static Hosting (Vercel, Netlify, etc.)" -ForegroundColor White
Write-Host "   - Upload dist/ folder or connect repository" -ForegroundColor Gray
Write-Host "   - Set build command: npm run build" -ForegroundColor Gray
Write-Host "   - Set output directory: dist" -ForegroundColor Gray
Write-Host ""
Write-Host "2. Traditional Web Server (Nginx/Apache)" -ForegroundColor White
Write-Host "   - Copy contents of dist/ to web server root" -ForegroundColor Gray
Write-Host "   - Configure server to serve index.html for all routes (SPA)" -ForegroundColor Gray
Write-Host ""
Write-Host "3. CDN/Cloud Storage (AWS S3, Cloudflare Pages, etc.)" -ForegroundColor White
Write-Host "   - Upload dist/ folder contents" -ForegroundColor Gray
Write-Host "   - Configure for SPA routing" -ForegroundColor Gray
Write-Host ""
Write-Host "For detailed instructions, see DEPLOYMENT_GUIDE.md" -ForegroundColor Cyan
Write-Host ""

