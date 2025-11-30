@echo off
REM Auto-Forfeit Expired Requests - Daily Task Runner
REM This batch file runs the auto-forfeit script and can be scheduled in Windows Task Scheduler

cd /d "%~dp0"
"C:\xampp\php\php.exe" auto_forfeit_expired_requests.php

REM Optional: Log when the task ran
echo Task executed at %date% %time% >> forfeit_task_log.txt
