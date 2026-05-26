@echo off
setlocal

echo.
echo Vite LAN server
echo ---------------
echo Jalankan file ini di terminal kedua jika CSS/JS tidak muncul saat memakai npm run dev.
echo Tekan Ctrl+C untuk menghentikan Vite.
echo.

npm run dev -- --host 0.0.0.0
