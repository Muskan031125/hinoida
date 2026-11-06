@echo off
REM HiNoida -> Railway. Double-click this file.
cd /d "%~dp0"
where railway >nul 2>nul || (echo Installing Railway CLI... & call npm i -g @railway/cli)
echo.
echo Step 1: a browser window opens - log in to Railway there.
call railway login
echo.
echo Step 2: pick your HiNoida project and the web service when asked.
call railway link
echo.
echo Step 3: uploading the site (takes a few minutes)...
call railway up
echo.
echo Done. Open your Railway project to see the site link.
pause
