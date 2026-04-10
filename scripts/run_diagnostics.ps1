Set-Location "$PSScriptRoot/../apps/laravel-web"
php artisan app:verify-attendance-integrity
php artisan route:list | Select-String "attendance|qr|notifications|analytics|diagnostics"
