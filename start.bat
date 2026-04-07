@echo off
:: start.bat — Run the dashboard locally on Windows
:: Requires PHP installed: https://windows.php.net/download/
:: Add PHP to your PATH, then double-click this file or run it in terminal.

title AIFAESA Dashboard — Local Server

echo.
echo  ╔══════════════════════════════════════╗
echo  ║   AIFAESA Dashboard — Dev Server     ║
echo  ║   http://localhost:8000              ║
echo  ║   Press Ctrl+C to stop              ║
echo  ╚══════════════════════════════════════╝
echo.

:: Check PHP is available
where php >nul 2>&1
if %ERRORLEVEL% neq 0 (
    echo  ERROR: PHP not found in PATH.
    echo  Download PHP from https://windows.php.net/download/
    echo  Extract it, then add its folder to your system PATH.
    pause
    exit /b 1
)

:: Show PHP version
php -r "echo ' PHP ' . PHP_VERSION . PHP_EOL;"

:: Open browser after 1.5s
start "" /b cmd /c "timeout /t 2 >nul && start http://localhost:8000"

:: Start the built-in server
php -S localhost:8000 router.php

pause
