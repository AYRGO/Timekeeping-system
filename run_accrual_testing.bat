@echo off
echo ===============================================
echo Auto-Accrual Testing Mode Runner
echo ===============================================
echo This script will run the accrual cron every 10 seconds
echo Press Ctrl+C to stop
echo ===============================================
echo.

:loop
php "C:\xampp\htdocs\Timekeeping-system\Public\cron\auto_accrual_cron.php"
echo.
echo Waiting 10 seconds...
timeout /t 10 /nobreak > nul
goto loop
