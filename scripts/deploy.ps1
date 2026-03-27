#!/usr/bin/env pwsh
# Sustainable deployment script for AgencyHQ frontend
# Usage: ./scripts/deploy.ps1

$ErrorActionPreference = "Stop"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "AgencyHQ Frontend Deployment" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# Change to backend directory
Set-Location $PSScriptRoot\..\backend

# Step 1: Build frontend
Write-Host "`n[1/3] Building frontend..." -ForegroundColor Yellow
npm run build
if ($LASTEXITCODE -ne 0) {
    Write-Host "Build failed!" -ForegroundColor Red
    exit 1
}

# Step 2: Sync to S3
Write-Host "`n[2/3] Syncing to S3..." -ForegroundColor Yellow
aws s3 sync public/build s3://agenchq-frontend --region us-east-1 --delete
if ($LASTEXITCODE -ne 0) {
    Write-Host "S3 sync failed!" -ForegroundColor Red
    exit 1
}

# Step 3: Invalidate CloudFront
Write-Host "`n[3/3] Invalidating CloudFront..." -ForegroundColor Yellow
$invalidationId = aws cloudfront create-invalidation --distribution-id E16JXL5MNZFIYK --paths "/*" --query "Invalidation.Id" --output text
if ($LASTEXITCODE -ne 0) {
    Write-Host "CloudFront invalidation failed!" -ForegroundColor Red
    exit 1
}

Write-Host "`n========================================" -ForegroundColor Green
Write-Host "Deployment Complete!" -ForegroundColor Green
Write-Host "Invalidation ID: $invalidationId" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
