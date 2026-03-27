@echo off
echo Setting up for first super admin creation...
powershell -ExecutionPolicy Bypass -File "%~dp0setup-first-super-admin.ps1"
pause

