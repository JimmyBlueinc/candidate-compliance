@echo off
echo Resetting database (WARNING: This will delete all data!)
powershell -ExecutionPolicy Bypass -File "%~dp0reset-database.ps1"
pause

