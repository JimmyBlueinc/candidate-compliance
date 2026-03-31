@echo off
echo Displaying SUPER_ADMIN_SECRET_KEY from Agenchq/.env...
echo.
powershell -ExecutionPolicy Bypass -File "%~dp0show-super-admin-key.ps1"
pause

