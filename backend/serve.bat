@echo off
cd /d "%~dp0"
echo ========================================================
echo  Menjalankan Laravel Backend Server Violincello (Port 8000)
echo ========================================================
"C:\Users\MyBook Hype AMD\.config\herd\bin\php.bat" artisan serve --port=8000 %*
