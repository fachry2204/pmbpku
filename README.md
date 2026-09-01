# PMB Pendidikan Kader Ulama

Aplikasi PMB berbasis Laravel 12, Inertia.js, Vue 3, TypeScript, Tailwind, MySQL, database queue, Tripay, Gmail SMTP, dan Fonnte.

## Setup lokal

1. Gunakan PHP 8.2+ (produksi disarankan 8.3), Composer 2, Node 20+, dan MySQL 8/MariaDB.
2. Salin `.env.example` ke `.env`, isi koneksi MySQL, lalu jalankan `php artisan key:generate`.
3. Jalankan `composer install`, `npm ci`, `php artisan migrate`, dan buat periode PMB aktif.
4. Jalankan `npm run dev`, `php artisan serve`, serta `php artisan queue:work database`.

Dokumen pendaftar disimpan di disk `local` privat dan hanya boleh dilayani melalui controller berotorisasi. Jangan jalankan `storage:link` untuk berkas pendaftar.

Modul yang tersedia meliputi registrasi, pembayaran Tripay, callback idempoten, cek status dengan OTP, revisi dokumen, dashboard dan review admin, jadwal/hasil tes, pengguna berbasis role, laporan CSV, pengaturan integration terenkripsi, serta queue email/Fonnte.

## Pemeriksaan

`composer validate`, `php artisan test`, `npm run typecheck`, `npm run test`, `npm run build`, `php artisan route:list`, dan `php artisan schedule:list`.
