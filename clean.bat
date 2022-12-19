@ECHO OFF
COPY /Y storage\meta\services.json storage\meta\services.json.bak
php artisan cache:clear
REN storage\meta\services.json.bak services.json
FOR /F %%f IN ('DIR /B /A storage\sessions\*.*') DO IF NOT "%%f"==".gitignore" DEL /F /Q "storage\sessions\%%f"
FOR /F %%f IN ('DIR /B /A storage\views\*.*') DO IF NOT "%%f"==".gitignore" DEL /F /Q "storage\views\%%f"
FOR /F %%i IN ('DIR /B /A:D public_html\files\pdf\output\*') DO RMDIR /S /Q "public_html\files\pdf\output\%%i"
DEL /F /Q storage\logs\*.log
