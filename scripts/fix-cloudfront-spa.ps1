#!/usr/bin/env pwsh
# Fix CloudFront for SPA routing and wildcard subdomain support
# This script updates CloudFront distribution to:
# 1. Add custom error responses for SPA deep-linking
# 2. Add wildcard subdomain alias (*.agenchq.com)
# 3. Invalidate cache

$ErrorActionPreference = "Stop"

$DISTRIBUTION_ID = "E16JXL5MNZFIYK"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "CloudFront SPA & Wildcard Fix" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# Step 1: Get current distribution config
Write-Host "`n[1/4] Getting current CloudFront config..." -ForegroundColor Yellow
$currentConfig = aws cloudfront get-distribution-config --id $DISTRIBUTION_ID --output json | ConvertFrom-Json
$ETag = $currentConfig.ETag
$config = $currentConfig.DistributionConfig

Write-Host "Current aliases: $($config.Aliases.Items -join ', ')"
Write-Host "Current custom error responses: $($config.CustomErrorResponses.Quantity)"

# Step 2: Update config with SPA error responses and wildcard alias
Write-Host "`n[2/4] Updating CloudFront config..." -ForegroundColor Yellow

# Add wildcard subdomain to aliases
$aliases = @("agenchq.com", "*.agenchq.com", "app.agenchq.com") | Sort-Object -Unique
$config.Aliases.Items = $aliases
$config.Aliases.Quantity = $aliases.Count

# Add custom error responses for SPA routing
$config.CustomErrorResponses = @{
    Quantity = 2
    Items = @(
        @{
            ErrorCode = 403
            ResponsePagePath = "/index.html"
            ResponseCode = 200
            ErrorCachingMinTTL = 0
        },
        @{
            ErrorCode = 404
            ResponsePagePath = "/index.html"
            ResponseCode = 200
            ErrorCachingMinTTL = 0
        }
    )
}

# Reduce cache TTL for index.html to prevent stale HTML
$config.DefaultCacheBehavior.DefaultTTL = 3600  # 1 hour instead of 24 hours

# Convert to JSON and update
$configJson = $config | ConvertTo-Json -Depth 10

# Save config to temp file
$tempFile = New-TemporaryFile
$configJson | Out-File -FilePath $tempFile -Encoding UTF8

Write-Host "New aliases: $($aliases -join ', ')"
Write-Host "New custom error responses: 403->index.html, 404->index.html"

# Step 3: Apply update
Write-Host "`n[3/4] Applying CloudFront update..." -ForegroundColor Yellow
try {
    $result = aws cloudfront update-distribution --id $DISTRIBUTION_ID --if-match $ETag --distribution-config "file://$tempFile" 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Update failed: $result" -ForegroundColor Red
        throw "CloudFront update failed"
    }
    Write-Host "CloudFront config updated successfully!" -ForegroundColor Green
} finally {
    Remove-Item $tempFile -Force
}

# Step 4: Invalidate cache
Write-Host "`n[4/4] Invalidating CloudFront cache..." -ForegroundColor Yellow
$invalidationId = aws cloudfront create-invalidation --distribution-id $DISTRIBUTION_ID --paths "/*" --query "Invalidation.Id" --output text
Write-Host "Invalidation ID: $invalidationId" -ForegroundColor Green

Write-Host "`n========================================" -ForegroundColor Green
Write-Host "CloudFront Fix Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green

Write-Host "`nIMPORTANT: You must also:" -ForegroundColor Yellow
Write-Host "1. Verify ACM certificate includes *.agenchq.com" -ForegroundColor Yellow
Write-Host "2. Add DNS wildcard record (*.agenchq.com -> CloudFront)" -ForegroundColor Yellow
Write-Host "3. Wait for CloudFront distribution to deploy (15-20 min)" -ForegroundColor Yellow
