@echo off
setlocal

set PORT=8000

for /f "tokens=2 delims=:" %%A in ('ipconfig ^| findstr /c:"IPv4 Address" /c:"Alamat IPv4"') do (
    set IP=%%A
    goto :found_ip
)

:found_ip
set IP=%IP: =%

echo.
echo Finance app LAN server
echo ----------------------
if defined IP (
    echo Buka dari laptop lain: http://%IP%:%PORT%
) else (
    echo IP lokal tidak terdeteksi otomatis. Jalankan ipconfig lalu pakai IPv4 laptop ini.
)
echo.
echo Jika tidak bisa dibuka, izinkan port %PORT% di Windows Firewall.
echo Tekan Ctrl+C untuk menghentikan server.
echo.

php artisan serve --host=0.0.0.0 --port=%PORT%
