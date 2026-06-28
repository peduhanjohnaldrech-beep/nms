@echo off
echo ================================
echo  NMS - Nutrition Monitoring System
echo ================================
echo.
echo Server starting at http://127.0.0.1:3000
echo.
echo DO NOT CLOSE THIS WINDOW.
echo Open your browser and go to: http://127.0.0.1:3000
echo.
"%~dp0php\php.exe" -S 127.0.0.1:3000 -t "%~dp0public" "%~dp0public\router.php"
echo.
echo Server stopped.
pause
