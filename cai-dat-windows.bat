@echo off
chcp 65001 >nul
rem Cai website ban nhom dinh hinh len Windows (XAMPP / Laragon), khong can Docker.
cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\install-windows.ps1"
echo.
pause
