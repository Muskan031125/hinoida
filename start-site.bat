@echo off
title HiNoida server - keep this window open
cd /d "%~dp0"
echo Starting HiNoida at http://localhost:8080 ...
start "" http://localhost:8080/
"C:\xampp\php\php.exe" spark serve
pause
