@echo off
REM Copies your local HiNoida database (hinoidanew) into Railway's MySQL.
REM Get the values from Railway: MySQL service -> Variables / Connect (public network).
cd /d "%~dp0"
set MYSQLBIN=C:\xampp\mysql\bin
echo Make sure XAMPP MySQL is running.
echo.
set /p RHOST=Railway public host (e.g. xxxx.proxy.rlwy.net): 
set /p RPORT=Railway public port: 
set /p RDB=Railway database name (usually railway): 
echo.
echo 1/2 Exporting local database...
"%MYSQLBIN%\mysqldump.exe" -u root --default-character-set=utf8mb4 --routines hinoidanew > hinoida-railway.sql || goto fail
echo 2/2 Importing into Railway - type the Railway MySQL password when asked...
"%MYSQLBIN%\mysql.exe" -h %RHOST% -P %RPORT% -u root -p --default-character-set=utf8mb4 %RDB% < hinoida-railway.sql || goto fail
echo.
echo Done. Database copied to Railway.
del hinoida-railway.sql
pause
exit /b
:fail
echo Something failed - send Claude a screenshot of this window.
pause
