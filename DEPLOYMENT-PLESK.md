# Deployment Plesk

Document root domain harus menunjuk ke folder `public`. Aktifkan HTTPS, PHP 8.3 beserta bcmath, ctype, curl, dom/xml, fileinfo, intl, mbstring, openssl, pdo_mysql, tokenizer, zip, dan gd/imagick.

Jalankan `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`, isi `.env` production (`APP_DEBUG=false`), lalu `php artisan migrate --force` dan `php artisan optimize`. `APP_KEY` hanya dibuat pada instalasi pertama dan tidak boleh diganti setelah secret terenkripsi dipakai. Permission tulis hanya untuk `storage` dan `bootstrap/cache`; jangan gunakan 777.

Cron scheduler: `* * * * * cd /var/www/vhosts/DOMAIN/httpdocs && php artisan schedule:run >> /dev/null 2>&1`.

Worker utama: `php artisan queue:work database --sleep=3 --tries=3 --timeout=90 --max-time=3600`. Fallback scheduled task: `php artisan queue:work database --stop-when-empty --tries=3 --timeout=90`.

Rollback kode ke rilis sebelumnya, jalankan migration rollback hanya jika migration tersebut terbukti aman, lalu `php artisan optimize:clear`. Backup database dan private storage terenkripsi sebelum rilis.
