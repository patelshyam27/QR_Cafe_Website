@echo off
title QR Cafe PHP Server
echo ====================================================================
echo             ☕ STARTING QR CAFE PHP WEBSITE ☕
echo ====================================================================
echo.
echo Server running at: http://localhost:8000
echo.
echo Pages:
echo   - Customer Website:      http://localhost:8000/
echo   - Digital Menu:          http://localhost:8000/menu.php
echo   - Reserve Table:         http://localhost:8000/book.php
echo   - Table QR Standees:     http://localhost:8000/qr_generator.php
echo   - Admin Dashboard:       http://localhost:8000/admin/index.php
echo   - Kitchen Screen (KDS):  http://localhost:8000/admin/kitchen.php
echo   - Database Inspector:    http://localhost:8000/admin/db_viewer.php
echo.
echo Press Ctrl + C to stop the server.
echo ====================================================================
echo.

"C:\Users\Shyam\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe" -S localhost:8000
pause
