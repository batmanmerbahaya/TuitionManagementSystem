@echo off
REM ---------------------------------------------------------------------------
REM  Starts Anjung Ilmu TMS on http://127.0.0.1:8000
REM
REM  - Same as "php artisan serve", but allows uploads up to 16 MB
REM    (PHP's default limit is only 2 MB, which is too small for
REM    photos of payment receipts taken with a phone).
REM  - Also starts the scheduler in a second (minimised) window, which creates each
REM    month's tuition bills automatically. Close that window too when you are done.
REM ---------------------------------------------------------------------------
cd /d "%~dp0"
start "TMS scheduler (monthly bills)" /min php artisan schedule:work

cd /d "%~dp0public"
echo Anjung Ilmu TMS running at http://127.0.0.1:8000  (press Ctrl+C to stop)
php -d upload_max_filesize=16M -d post_max_size=20M -S 127.0.0.1:8000 "%~dp0vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php"
