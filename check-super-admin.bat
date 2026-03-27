@echo off
echo Checking for existing super admins...
powershell -ExecutionPolicy Bypass -File "%~dp0check-super-admin.ps1"
pause

