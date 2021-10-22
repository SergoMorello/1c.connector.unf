@ECHO OFF
cd ./
echo Enter server port (default 8000)
set Data=8000
set /p Data="Port: "
CLS
php -S 0.0.0.0:%Data% -t public/
@pause