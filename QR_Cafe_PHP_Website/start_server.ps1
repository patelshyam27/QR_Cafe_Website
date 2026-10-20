$phpExe = "C:\Users\Shyam\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe"

Write-Host "==========================================================" -ForegroundColor DarkYellow
Write-Host "             ☕ QR CAFE PHP SERVER ☕                    " -ForegroundColor Yellow
Write-Host "==========================================================" -ForegroundColor DarkYellow
Write-Host "Server running at: http://localhost:8000" -ForegroundColor Green
Write-Host ""
Write-Host "Pages to open in your browser:"
Write-Host "  - Customer Site:     http://localhost:8000/" -ForegroundColor Cyan
Write-Host "  - Digital Menu:      http://localhost:8000/menu.php" -ForegroundColor Cyan
Write-Host "  - Table QR Codes:    http://localhost:8000/qr_generator.php" -ForegroundColor Cyan
Write-Host "  - Kitchen KDS:       http://localhost:8000/admin/kitchen.php" -ForegroundColor Cyan
Write-Host "  - Admin Dashboard:   http://localhost:8000/admin/index.php" -ForegroundColor Cyan
Write-Host "  - DB Inspector:      http://localhost:8000/admin/db_viewer.php" -ForegroundColor Cyan
Write-Host ""
Write-Host "Press Ctrl + C to stop the server." -ForegroundColor Gray
Write-Host "==========================================================" -ForegroundColor DarkYellow

& $phpExe -S localhost:8000
