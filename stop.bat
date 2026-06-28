@echo off
echo Stopping NMS server...
taskkill /f /im php.exe >nul 2>&1
echo Server stopped.
timeout /t 2 /nobreak >nul
