@echo off
REM Descarga la libreria del escaner QR (solo una vez, requiere internet).
REM Ajusta la ruta de PHP si XAMPP esta en otra carpeta.
set PHP=C:\xampp\php\php.exe
if not exist "%PHP%" set PHP=php
"%PHP%" "%~dp0backend\descargar_libreria_qr.php"
pause
