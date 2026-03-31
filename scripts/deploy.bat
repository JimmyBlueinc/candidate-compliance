@echo off
REM Sustainable deployment script for AgencyHQ frontend
REM Usage: scripts\deploy.bat

echo ========================================
echo AgencyHQ Frontend Deployment
echo ========================================

cd /d "%~dp0..\Agenchq"

echo.
echo [1/3] Building frontend...
call npm run build
if %ERRORLEVEL% neq 0 (
    echo Build failed!
    exit /b 1
)

echo.
echo [2/3] Syncing to S3...
aws s3 sync public/build s3://agenchq-frontend --region us-east-1 --delete
if %ERRORLEVEL% neq 0 (
    echo S3 sync failed!
    exit /b 1
)

echo.
echo [3/3] Invalidating CloudFront...
aws cloudfront create-invalidation --distribution-id E16JXL5MNZFIYK --paths "/*"
if %ERRORLEVEL% neq 0 (
    echo CloudFront invalidation failed!
    exit /b 1
)

echo.
echo ========================================
echo Deployment Complete!
echo ========================================
