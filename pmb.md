# MASTER BRIEF CODEX — Sistem Informasi PMB Pendidikan Kader Ulama

> Dokumen ini adalah instruksi implementasi lengkap untuk Codex. Bangun aplikasi sampai dapat dijalankan, diuji, dan dipasang di Plesk. Jangan hanya membuat mockup atau pseudocode.

## 1. Peran dan tujuan

Anda adalah senior full-stack developer, software architect, DevOps engineer, QA engineer, dan security reviewer. Bangun **Sistem Informasi Penerimaan Mahasiswa Baru (PMB) Pendidikan Kader Ulama**, siap produksi, mudah dipasang di Plesk, responsif di desktop/mobile, dan berbahasa Indonesia.

Tujuan utama:

- Calon mahasiswa mengisi formulir, mengunggah berkas, membayar biaya pendaftaran melalui Tripay, dan mengecek status.
- Admin memverifikasi pembayaran dan berkas, mengatur tahapan tes/kelulusan, mengelola pengguna admin, konfigurasi aplikasi, SMTP Gmail, Fonnte, dan Tripay.
- Sistem mengirim notifikasi email dan WhatsApp secara asynchronous serta mencatat seluruh hasil pengiriman.
- Tidak ada secret, bukti identitas, atau file pendaftar yang dapat diakses publik.

## 2. Stack wajib

- Backend: **Laravel 12**, PHP 8.3 atau versi stabil yang kompatibel dengan Plesk.
- Frontend: **Vue 3** + TypeScript + Vite.
- Integrasi frontend: Inertia.js agar satu proyek Laravel mudah di-deploy ke Plesk.
- Database: **MySQL 8 / MariaDB yang kompatibel** menggunakan Eloquent dan migration Laravel.
- UI: Tailwind CSS, Headless UI/Radix Vue yang relevan, Lucide Icons.
- Form: Composition API, validasi frontend untuk UX dan Laravel Form Request sebagai validasi final.
- Queue: database queue sebagai default agar mudah di Plesk; Redis bersifat opsional.
- Scheduler: Laravel Scheduler melalui cron Plesk.
- Authentication admin: Laravel Fortify/Breeze dengan Inertia Vue, session cookie, CSRF, rate limiting.
- Testing: Pest atau PHPUnit untuk backend dan Vitest untuk unit frontend; Playwright opsional untuk alur kritis.
- Jangan gunakan Prisma, SQLite, Firebase, Supabase, atau database lain.
- Jangan menggunakan localStorage untuk autentikasi admin atau data sensitif.

## 3. Identitas visual

Tema Islamic modern dan profesional:

- Warna utama: hijau tua `#064E3B`, hijau `#047857`, hijau muda `#D1FAE5`, aksen emas `#D4AF37`, putih gading `#FFFCF2`.
- Background menggunakan gradasi hijau muda ke hijau tua secara halus.
- Ornamen geometri Islam tipis, bukan gambar ramai.
- Font: Plus Jakarta Sans/Inter; font Arab opsional hanya untuk ornamen singkat.
- Kontras minimum WCAG AA, fokus keyboard terlihat, label form jelas, error tidak hanya dibedakan berdasarkan warna.
- Layout maksimal 1280px, tidak full-width berlebihan.
- Mobile-first, tabel admin berubah menjadi card/scroll yang tetap mudah dipakai.

## 4. Arsitektur aplikasi

Gunakan modular monolith agar stabil di shared hosting/Plesk:

```text
app/
  Actions/
  Enums/
  Events/
  Http/Controllers/Public/
  Http/Controllers/Admin/
  Http/Controllers/Webhooks/
  Http/Requests/
  Jobs/
  Mail/
  Models/
  Notifications/
  Policies/
  Services/Tripay/
  Services/Fonnte/
  Support/
resources/js/
  Components/
  Layouts/
  Pages/Public/
  Pages/Admin/
  composables/
  types/
routes/
  web.php
  admin.php
  webhooks.php
tests/Feature/
tests/Unit/
```

Gunakan service layer untuk Tripay dan Fonnte. Controller harus tipis. Gunakan transaction database pada proses registrasi, pembuatan invoice, dan callback pembayaran.

## 5. Halaman publik

### 5.1 Landing page

Bagian yang wajib:

1. Header/logo dan navigasi.
2. Hero “Penerimaan Mahasiswa Baru Pendidikan Kader Ulama”.
3. CTA **Daftar Sekarang** dan **Cek Status Pendaftaran**.
4. Informasi program, persyaratan, alur pendaftaran, jadwal penting, biaya pendaftaran, FAQ, kontak panitia.
5. Semua konten tersebut bisa diubah dari admin tanpa edit kode.
6. Footer, kebijakan privasi, dan persetujuan pemrosesan data pribadi.

### 5.2 Form pendaftaran

Buat wizard dengan autosave draft aman di server setelah email/OTP atau token draft dibuat. Jika implementasi autosave menambah risiko/kompleksitas, gunakan form multi-step dalam session sampai submit final. Jangan menyimpan nomor KTP atau file di localStorage.

Field wajib:

1. Nama lengkap.
2. Tempat lahir.
3. Tanggal lahir.
4. Alamat lengkap (textarea; opsional dipecah provinsi/kota/kecamatan/kelurahan/kode pos).
5. Nomor WhatsApp.
6. Email.
7. Surat rekomendasi/keterangan — JPG, JPEG, PNG, atau PDF.
8. Ijazah S1/sederajat/Pondok Pesantren — JPG, JPEG, PNG, atau PDF.
9. Foto 4×6 — JPG, JPEG, PNG, atau PDF (berikan peringatan bahwa gambar lebih disarankan).
10. KTP — JPG, JPEG, PNG, atau PDF.
11. Bukti transfer pendaftaran — JPG, JPEG, PNG, atau PDF; **hanya wajib untuk metode pembayaran manual/fallback**, tidak diwajibkan jika memilih Tripay.
12. Screenshot PDDIKTI — JPG, JPEG, PNG, atau PDF.
13. Checkbox pernyataan kebenaran data dan persetujuan kebijakan privasi.

Ketentuan validasi:

- Nama: 3–150 karakter.
- Tempat lahir: 2–100 karakter.
- Tanggal lahir: tanggal valid, tidak boleh di masa depan.
- Email: valid, lowercase, maksimal 190, unik per periode PMB aktif.
- WhatsApp: normalisasi ke format `62xxxxxxxxxx`; 9–15 digit; unik per periode PMB aktif.
- File: periksa MIME nyata dan ekstensi; maksimal default 5 MB per file dan dapat diatur admin sampai batas aman 10 MB.
- PDF maksimum 10 halaman bila memungkinkan; tolak file terenkripsi/password-protected.
- Nama file acak UUID; jangan memakai nama asli unggahan sebagai path.
- Jangan percaya validasi frontend.
- Tampilkan preview file dan progress upload.
- Form tidak dapat disubmit dua kali; gunakan idempotency token/client submission UUID.

Setelah berhasil:

- Buat nomor pendaftaran unik, contoh `PKU-2026-000001`.
- Buat akun/status lookup tanpa membuat akun mahasiswa penuh.
- Arahkan ke halaman ringkasan dan pilihan pembayaran.
- Kirim email dan WhatsApp konfirmasi pendaftaran melalui queue.

### 5.3 Pembayaran Tripay

Alur:

1. Backend mengambil channel pembayaran aktif dari Tripay dan menyimpan cache singkat.
2. Pendaftar memilih channel.
3. Backend mengambil biaya pendaftaran dari setting server, menghitung fee sesuai kebijakan, membuat `merchant_ref` unik, lalu meminta transaksi Tripay.
4. Simpan reference Tripay, checkout URL, nominal, fee, total, metode, payload aman, expiry, dan status.
5. Frontend diarahkan ke checkout/payment instruction.
6. Status pembayaran hanya berubah melalui callback terverifikasi atau rekonsiliasi server-to-server—bukan query string redirect dari browser.
7. Callback menangani `PAID`, `UNPAID`, `EXPIRED`, `FAILED`, `REFUND` jika tersedia. Mapping harus terpusat di enum/service.
8. Jika status `PAID`, set `paid_at`, ubah status pembayaran pendaftar menjadi `paid`, buat audit log, lalu kirim notifikasi satu kali.

Keamanan Tripay wajib:

- API key, private key, dan merchant code hanya ada di server dan terenkripsi saat disimpan di database.
- Validasi `X-Callback-Event` dan `X-Callback-Signature`.
- Signature callback: HMAC SHA-256 terhadap **raw request body** menggunakan private key; bandingkan dengan `hash_equals`.
- Callback idempotent: callback yang sama boleh masuk berkali-kali tanpa menggandakan perubahan, log, atau notifikasi.
- Cocokkan `merchant_ref`, reference, nominal, dan transaksi internal.
- Gunakan row lock/database transaction saat memproses callback.
- Simpan callback event dengan unique fingerprint/reference+status.
- Response callback cepat; pekerjaan email/WA dimasukkan queue setelah commit.
- Sediakan mode sandbox dan production melalui setting; tampilkan badge mode pada admin.
- Endpoint callback dikecualikan dari CSRF hanya untuk route spesifik, bukan seluruh grup route.
- Sediakan tombol admin untuk tes koneksi, sinkronisasi status satu transaksi, dan rekonsiliasi terjadwal transaksi pending.
- Gunakan timeout HTTP, retry eksponensial hanya untuk kegagalan sementara, dan logging tersanitasi tanpa secret.

Referensi implementasi wajib diverifikasi kembali saat coding: dokumentasi resmi Tripay `https://tripay.co.id/developer`.

### 5.4 Cek status pendaftaran

Di landing page sediakan form dengan:

- Nomor WhatsApp.
- Email terdaftar.
- CAPTCHA/Turnstile opsional via setting.

Keduanya harus cocok dengan satu pendaftar pada periode aktif/terpilih. Jangan mengungkap apakah email atau nomor tertentu terdaftar secara terpisah. Terapkan rate limit ketat per IP dan fingerprint/session. Setelah cocok, kirim OTP 6 digit ke email atau WhatsApp (opsi channel) sebelum menampilkan detail sensitif. OTP di-hash, kedaluwarsa 5 menit, maksimal percobaan, dan single-use.

Setelah verifikasi tampilkan:

- Nomor pendaftaran dan nama tersamarkan seperlunya.
- Status pembayaran.
- Status kelengkapan berkas serta catatan berkas yang perlu diperbaiki.
- Status seleksi/tes.
- Timeline perubahan status.
- Jadwal/lokasi/link tes jika sudah tersedia.
- Tombol melanjutkan pembayaran bila belum bayar atau transaksi kedaluwarsa.
- Tombol unggah ulang hanya untuk dokumen yang ditandai perlu perbaikan.
- Tombol unduh kartu peserta tes setelah status memenuhi syarat.

## 6. Model status yang benar

Jangan memasukkan semua keadaan ke satu kolom status. Gunakan tiga dimensi agar tidak terjadi kombinasi ambigu:

### `payment_status`

- `unpaid` — Belum Bayar.
- `pending` — Menunggu Pembayaran/Verifikasi.
- `paid` — Sudah Bayar.
- `expired` — Kedaluwarsa.
- `failed` — Gagal.
- `refunded` — Dikembalikan (bila digunakan).

### `document_status`

- `pending_review` — Menunggu Pemeriksaan.
- `complete` — Berkas Komplit.
- `incomplete` — Berkas Tidak Komplit.
- `revision_submitted` — Perbaikan Berkas Dikirim.

### `selection_status`

- `not_scheduled` — Belum Dijadwalkan.
- `scheduled` — Dijadwalkan Tes.
- `attending_test` — Mengikuti Tes.
- `passed` — Diterima.
- `not_passed` — Tidak Diterima (tambahan operasional yang diperlukan).
- `withdrawn` — Mengundurkan Diri (opsional).

UI tetap menampilkan label sesuai permintaan: Belum Bayar, Sudah Bayar, Berkas Komplit/Tidak Komplit, Mengikuti Tes, dan Diterima. Terapkan aturan transisi; misalnya admin tidak boleh menjadwalkan tes jika pembayaran belum `paid` atau berkas belum `complete`, kecuali override dengan alasan dan audit log.

## 7. Dashboard admin

### 7.1 Ringkasan

Card statistik:

- Total pendaftar.
- Laki-laki/perempuan hanya jika field jenis kelamin ditambahkan dan diaktifkan; jangan tampilkan statistik palsu bila field tidak ada.
- Belum bayar, sudah bayar.
- Berkas menunggu review, komplit, tidak komplit.
- Mengikuti tes, diterima, tidak diterima.
- Pendapatan pendaftaran dan tren harian.
- Notifikasi gagal dan transaksi perlu rekonsiliasi.

Chart dapat difilter per periode PMB dan rentang tanggal.

### 7.2 Data pendaftar

- Tabel server-side dengan search, filter, sort, pagination.
- Filter periode, tanggal, payment status, document status, selection status.
- Detail lengkap, preview/download aman, riwayat status, pembayaran, notifikasi, dan audit.
- Verifikasi setiap dokumen secara individual: valid/perlu perbaikan dengan catatan.
- Tombol “Berkas Komplit” hanya aktif jika seluruh dokumen wajib valid.
- Ubah status massal dengan konfirmasi dan audit; jangan sediakan bulk delete permanen.
- Ekspor CSV/XLSX yang menghormati filter dan permission; ekspor tidak menyertakan file atau secret.
- Cetak kartu peserta dan rekap PDF bila diperlukan.
- Soft delete pendaftar; restore hanya untuk super admin.

### 7.3 Pembayaran

- Daftar invoice/transaksi, reference, merchant_ref, channel, nominal, fee, status, expiry, paid_at.
- Detail request/response tersanitasi.
- Rekonsiliasi manual ke Tripay.
- Pembayaran manual memiliki verifikasi dua langkah bila diaktifkan: upload bukti lalu admin menerima/menolak beserta catatan.
- Admin tidak boleh mengubah transaksi Tripay menjadi paid tanpa permission khusus, alasan, dan audit log.

### 7.4 Jadwal tes dan hasil

- CRUD gelombang/periode PMB.
- CRUD sesi tes: nama, tanggal, jam, lokasi, link maps/meeting, kapasitas, instruksi.
- Assign satu/banyak pendaftar yang memenuhi syarat.
- Tandai hadir/mengikuti tes.
- Input nilai/catatan internal opsional.
- Tetapkan diterima/tidak diterima dan kirim notifikasi.

### 7.5 Pengguna admin dan akses

Role minimum:

- `super_admin`: seluruh akses dan setting integrasi.
- `admin_pmb`: kelola pendaftar, dokumen, tes, laporan.
- `finance`: pembayaran dan rekonsiliasi.
- `reviewer`: pemeriksaan dokumen saja.
- `viewer`: baca data yang diizinkan.

Gunakan policy/permission di backend. Menyembunyikan menu di frontend tidak cukup. Fitur:

- Tambah/edit/nonaktifkan admin.
- Reset password via email.
- Paksa ganti password saat login pertama.
- 2FA TOTP untuk super admin (sangat disarankan).
- Riwayat login dan audit log.
- Super admin terakhir tidak dapat dihapus/dinonaktifkan.

### 7.6 Pengaturan

Kelompok setting:

1. **Profil lembaga:** nama, logo, alamat, kontak, media sosial.
2. **PMB:** periode aktif, kuota, tanggal buka/tutup, biaya, syarat, jadwal, metode pembayaran manual.
3. **Landing page:** hero, deskripsi, FAQ, kontak, pengumuman.
4. **SMTP Gmail:** host, port, encryption, username, app password, from name/address; tombol kirim email tes.
5. **Fonnte:** base URL, token, country code, status aktif; tombol tes perangkat/pesan.
6. **Tripay:** mode sandbox/production, merchant code, API key, private key, callback URL read-only, return URL, expiry; tombol tes koneksi dan channel.
7. **Upload:** ukuran maksimum dan MIME yang diizinkan dalam batas server.
8. **Keamanan:** rate limit, OTP expiry, session timeout, maintenance registration.
9. **Template notifikasi:** template setiap event dengan variabel yang diizinkan.

Secret harus dienkripsi menggunakan Laravel encrypted cast atau envelope encryption yang memanfaatkan `APP_KEY`. Saat edit, tampilkan masked value; nilai kosong berarti mempertahankan secret lama. Jangan pernah mengirim secret asli ke Vue, log, export, debug bar, atau error response.

## 8. Notifikasi email Gmail dan WhatsApp Fonnte

Event notifikasi minimum:

- Pendaftaran berhasil.
- Instruksi pembayaran dibuat.
- Pembayaran berhasil/gagal/kedaluwarsa.
- Berkas tidak komplit + catatan.
- Berkas komplit.
- Jadwal tes.
- Pengingat tes.
- Status diterima/tidak diterima.
- OTP cek status.

SMTP Gmail:

- Gunakan SMTP Laravel Mail, TLS, port 587 sebagai default.
- Gunakan **Google App Password**, bukan password akun Gmail biasa.
- Kredensial bisa berasal dari `.env`; setting database boleh override secara aman setelah validasi.
- Sediakan test connection/test email dan error yang ramah tanpa membocorkan secret.

Fonnte:

- Kirim hanya dari backend dengan `POST https://api.fonnte.com/send`.
- Token dikirim melalui header `Authorization`.
- Parameter utama `target`, `message`, dan `countryCode=62`.
- Normalisasi nomor WhatsApp sebelum pengiriman.
- Simpan `requestid`, status respons, jumlah percobaan, error tersanitasi.
- Gunakan queue, timeout, circuit breaker sederhana, dan retry terbatas.
- Jangan gunakan endpoint GET karena berisiko mengekspos token.
- Referensi wajib diverifikasi kembali saat coding: `https://docs.fonnte.com/api-send-message/`.

Semua notifikasi harus:

- Dikendalikan oleh event/domain action.
- Dikirim setelah transaction commit.
- Memiliki unique key agar tidak terkirim dua kali.
- Memiliki status `queued/sent/failed/skipped`.
- Dapat di-retry admin jika gagal.
- Tidak menggagalkan registrasi atau callback pembayaran jika provider notifikasi sedang down.

## 9. Skema database minimum

Buat migration, foreign key, index, enum/string yang terkendali, timestamps, dan soft delete bila relevan.

### `users`

`id`, `name`, `email` unique, `password`, `role/permissions`, `is_active`, `must_change_password`, `two_factor_*`, `last_login_at`, timestamps.

### `admission_periods`

`id`, `name`, `year`, `registration_prefix`, `starts_at`, `ends_at`, `quota`, `registration_fee`, `is_active`, timestamps. Pastikan hanya satu periode aktif melalui service/transaction.

### `applicants`

`id` UUID/ULID, `admission_period_id`, `registration_number`, `full_name`, `birth_place`, `birth_date`, `address`, `whatsapp_normalized`, `whatsapp_display`, `email`, `payment_status`, `document_status`, `selection_status`, `consented_at`, `submitted_at`, `paid_at`, `accepted_at`, `lookup_secret_hash` bila digunakan, timestamps, soft delete.

Unique composite:

- `(admission_period_id, registration_number)`.
- `(admission_period_id, email)`.
- `(admission_period_id, whatsapp_normalized)`.

### `applicant_documents`

`id`, `applicant_id`, `type`, `disk`, `path`, `original_name`, `mime_type`, `extension`, `size`, `sha256`, `page_count`, `verification_status`, `review_note`, `reviewed_by`, `reviewed_at`, `version`, timestamps, soft delete.

Tipe: `recommendation_letter`, `diploma`, `photo_4x6`, `identity_card`, `payment_proof`, `pddikti_screenshot`.

### `payments`

`id` ULID, `applicant_id`, `provider`, `merchant_ref`, `provider_reference`, `payment_method`, `base_amount`, `fee_merchant`, `fee_customer`, `total_amount`, `status`, `checkout_url`, `instructions_json`, `expires_at`, `paid_at`, `last_synced_at`, `request_payload_redacted`, `response_payload_redacted`, timestamps.

Index dan unique pada `merchant_ref`; `provider_reference` unique nullable sesuai MySQL behavior.

### `payment_webhook_events`

`id`, `provider`, `event_key` unique, `provider_reference`, `event`, `signature_valid`, `payload_redacted`, `received_at`, `processed_at`, `processing_status`, `error`, timestamps.

### `document_reviews`

Riwayat setiap perubahan verifikasi dokumen, reviewer, before/after, catatan, timestamps.

### `test_sessions`

`id`, `admission_period_id`, `name`, `starts_at`, `ends_at`, `location`, `maps_url`, `meeting_url`, `capacity`, `instructions`, timestamps.

### `applicant_test_sessions`

`applicant_id`, `test_session_id`, `attendance_status`, `score`, `internal_note`, `assigned_at`, `attended_at`, timestamps; unique pasangan applicant-session.

### `status_histories`

`id`, `applicant_id`, `dimension`, `from_status`, `to_status`, `note`, `changed_by_type`, `changed_by_id`, `created_at`.

### `settings`

`id`, `group`, `key` unique, `value`, `is_encrypted`, `type`, timestamps. Akses hanya lewat typed settings service dengan cache invalidation.

### `notification_logs`

`id`, `applicant_id`, `channel`, `event_type`, `recipient_masked`, `unique_key` unique, `provider_request_id`, `status`, `attempts`, `last_error`, `sent_at`, timestamps.

### `otp_challenges`

`id`, `applicant_id`, `purpose`, `channel`, `code_hash`, `expires_at`, `attempts`, `consumed_at`, `ip_hash`, timestamps.

### `audit_logs`

`id`, `user_id`, `action`, `auditable_type/id`, `before_json`, `after_json`, `ip`, `user_agent`, `created_at`. Redaksi otomatis untuk password, token, private key, API key, OTP, dan file bytes.

## 10. Routes minimum

Public:

```text
GET  /
GET  /pendaftaran
POST /pendaftaran
GET  /pendaftaran/{registrationNumber}/berhasil
GET  /pembayaran/{registrationNumber}
POST /pembayaran/{registrationNumber}/tripay
GET  /cek-status
POST /cek-status/request-otp
POST /cek-status/verify-otp
POST /cek-status/{applicant}/documents/{type}/revision
```

Webhook:

```text
POST /webhooks/tripay
POST /webhooks/fonnte/status   # hanya jika status webhook digunakan
```

Admin prefix `/admin`:

```text
/login
/dashboard
/applicants
/applicants/{applicant}
/payments
/test-sessions
/reports
/users
/settings/*
/audit-logs
/notification-logs
```

Gunakan route model binding yang tidak mengekspos incremental ID untuk area publik. Seluruh route admin dilindungi auth, active user, permission, dan session security middleware.

## 11. Keamanan dan privasi wajib

- File pendaftar disimpan pada private disk di luar `public/`; akses lewat controller terotorisasi atau temporary signed URL singkat.
- Matikan directory listing.
- Cegah path traversal dan SVG/script upload; SVG tidak diizinkan.
- Jalankan antivirus ClamAV jika tersedia; jika tidak, desain hook `FileScanner` dan tandai keterbatasan di dokumentasi.
- Terapkan CSP, HSTS setelah HTTPS stabil, `X-Content-Type-Options`, frame protection, Referrer Policy, secure/HTTP-only/SameSite cookies.
- CSRF untuk seluruh form browser kecuali webhook spesifik yang memakai signature.
- Rate limit registrasi, login, OTP, cek status, pembuatan transaksi, dan webhook abuse.
- CAPTCHA opsional untuk form publik.
- Gunakan prepared statements/Eloquent, output escaping, sanitasi rich text admin.
- Jangan log KTP lengkap, token, password, private key, authorization header, OTP, atau payload file.
- Masking email/nomor WA pada log dan UI yang tidak membutuhkan data penuh.
- Backup database dan private storage terenkripsi; dokumentasikan retensi dan penghapusan data.
- `APP_DEBUG=false` di production.
- Paksa HTTPS dan trusted proxy dikonfigurasi benar di Plesk.
- Audit seluruh perubahan status, setting, pembayaran manual, akses/unduh dokumen sensitif.
- Hindari mass assignment; gunakan DTO/Form Request dan explicit fields.

## 12. Reliability dan performa

- Gunakan queue untuk email/WA, export, preview dokumen, dan pekerjaan berat.
- Hindari N+1 dengan eager loading dan query terukur.
- Index semua kolom pencarian/filter utama.
- Pagination server-side.
- Cache landing page/settings/channel Tripay dengan invalidasi.
- Transaksi database untuk operasi multi-tabel.
- Job harus idempotent dan menggunakan unique lock bila relevan.
- Health check aplikasi mencakup database, cache, storage writable, dan queue heartbeat tanpa membocorkan konfigurasi.
- Logging channel harian dengan retention.
- Tangani provider timeout/degradasi dengan pesan ramah dan retry aman.

## 13. Deployment Plesk

Buat `DEPLOYMENT-PLESK.md` di repository dengan langkah konkret. Target domain/subdomain diarahkan ke folder `public` Laravel.

Persyaratan server:

- PHP 8.3 dengan extensions: bcmath, ctype, curl, dom/xml, fileinfo, intl, mbstring, openssl, pdo_mysql, tokenizer, zip, gd atau imagick.
- MySQL 8/MariaDB kompatibel.
- Composer 2.
- Node.js hanya diperlukan saat build; asset final dapat dibangun sebelum upload bila Node tidak tersedia.
- HTTPS aktif.

Prosedur deploy:

1. Clone/upload repository di luar document root bila memungkinkan.
2. Arahkan document root ke `/public`.
3. Buat `.env` dari `.env.example`, isi `APP_ENV=production`, `APP_DEBUG=false`, URL HTTPS, MySQL, mail, queue.
4. `composer install --no-dev --optimize-autoloader`.
5. `npm ci && npm run build` pada build server/Plesk Node.
6. `php artisan key:generate` hanya saat instalasi pertama; jangan mengganti `APP_KEY` setelah secret/data terenkripsi dipakai.
7. `php artisan migrate --force`.
8. `php artisan storage:link` hanya untuk aset publik seperti logo; dokumen pendaftar tetap private.
9. `php artisan optimize` dan `php artisan event:cache` bila kompatibel.
10. Set permission hanya untuk `storage` dan `bootstrap/cache` kepada user subscription; jangan `777`.
11. Buat super admin melalui command artisan interaktif/parameter aman, bukan seeder password default.
12. Daftarkan callback Tripay HTTPS.

Cron Plesk:

```bash
* * * * * cd /var/www/vhosts/DOMAIN/httpdocs && php artisan schedule:run >> /dev/null 2>&1
```

Queue production (pilih salah satu dan dokumentasikan):

- Plesk Supervisor/extension: `php artisan queue:work database --sleep=3 --tries=3 --timeout=90 --max-time=3600`.
- Jika Supervisor tidak tersedia, scheduled task setiap menit memakai `php artisan queue:work database --stop-when-empty --tries=3 --timeout=90` sebagai fallback.

Tambahkan scheduled jobs:

- Rekonsiliasi transaksi pending setiap 10–15 menit.
- Tandai invoice expired.
- Kirim pengingat tes.
- Bersihkan OTP/draft/rate-limit records kedaluwarsa.
- Monitor queue heartbeat.

Sertakan contoh konfigurasi Nginx/Apache hanya bila diperlukan oleh Plesk. Jangan mengubah konfigurasi server secara otomatis.

## 14. Environment variables

Buat `.env.example` tanpa nilai secret:

```dotenv
APP_NAME="PMB Pendidikan Kader Ulama"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pku_pmb
DB_USERNAME=
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="${APP_NAME}"

FONNTE_BASE_URL=https://api.fonnte.com
FONNTE_TOKEN=
FONNTE_COUNTRY_CODE=62

TRIPAY_MODE=sandbox
TRIPAY_MERCHANT_CODE=
TRIPAY_API_KEY=
TRIPAY_PRIVATE_KEY=
TRIPAY_CALLBACK_URL="${APP_URL}/webhooks/tripay"
TRIPAY_RETURN_URL="${APP_URL}/cek-status"
```

Jika setting integrasi disimpan di database, nilai `.env` menjadi fallback/bootstrap. Jangan commit `.env`.

## 15. Testing wajib

Jangan menyatakan “siap produksi” sebelum test berikut lulus.

### Unit test

- Normalisasi nomor WhatsApp Indonesia.
- Pembentukan nomor registrasi tanpa race condition.
- Tripay transaction signature.
- Tripay callback HMAC dari raw body.
- Mapping status Tripay.
- Aturan transisi status.
- Redaksi log dan enkripsi/dekripsi setting.
- OTP hashing, expiry, attempt limit.

### Feature/integration test

- Registrasi sukses dengan seluruh file valid.
- Validasi field/file gagal dengan pesan Indonesia.
- Duplicate submit idempotent.
- Email/WA masuk queue, kegagalan provider tidak membatalkan registrasi.
- Pembuatan transaksi Tripay mocked: payload, signature, nominal, merchant_ref benar.
- Callback signature invalid menghasilkan 401/403 dan tidak mengubah data.
- Callback valid `PAID` mengubah satu transaksi satu kali.
- Callback `PAID` duplikat tidak menggandakan history/notifikasi.
- Callback dengan nominal/reference tidak cocok ditolak dan diaudit.
- Endpoint status tidak membocorkan data tanpa OTP.
- OTP rate limit dan brute force protection.
- Admin tanpa permission ditolak walau memanggil endpoint langsung.
- Dokumen private tidak dapat dibuka melalui URL publik.
- Verifikasi semua dokumen menghasilkan `complete`; satu dokumen invalid menghasilkan `incomplete`.
- Rekonsiliasi Tripay memperbaiki transaksi yang callback-nya terlewat.

### UAT sandbox Tripay

Gunakan credential sandbox milik pemilik sistem—jangan membuat atau menebak credential:

1. Ambil channel pembayaran sandbox.
2. Buat transaksi dengan nominal tes.
3. Buka checkout/instruksi.
4. Simulasikan `PAID` melalui console/callback tester resmi Tripay.
5. Pastikan signature valid, payment `paid`, timeline tercatat, notifikasi hanya sekali.
6. Ulangi callback identik untuk menguji idempotensi.
7. Simulasikan expired/failed.
8. Uji timeout provider dan rekonsiliasi.

### Pemeriksaan build

Jalankan dan perbaiki seluruh error:

```bash
composer validate
php artisan test
npm run typecheck
npm run test
npm run build
php artisan route:list
php artisan migrate:fresh --seed --env=testing
```

Jalankan formatter/linter yang dikonfigurasi. Jangan menonaktifkan test untuk membuat pipeline hijau.

## 16. Seed dan demo

- Seeder hanya berisi role, permission, periode demo, setting non-secret, FAQ, dan data dummy yang jelas fiktif.
- Jangan seed password admin default pada production.
- Buat command `php artisan admin:create` untuk membuat super admin dengan input aman.
- Buat factory untuk applicant/payment/document dalam test.

## 17. Dokumentasi yang harus dibuat

Repository final wajib berisi:

- `README.md`: fitur, stack, persyaratan, local setup, command test/build.
- `DEPLOYMENT-PLESK.md`: panduan produksi dan rollback.
- `INTEGRATIONS.md`: setup Gmail App Password, Fonnte, Tripay sandbox/production, callback, troubleshooting.
- `SECURITY.md`: penyimpanan file, secret, permission, backup, incident basics.
- `API-FLOW.md`: alur registrasi, pembayaran, callback, status lookup.
- `.env.example` lengkap tanpa secret.
- OpenAPI/Postman collection untuk endpoint webhook/integrasi internal bila membantu.

## 18. Definition of Done

Pekerjaan dianggap selesai hanya jika:

- Semua halaman publik dan admin berfungsi nyata, bukan placeholder.
- Migration berjalan dari database kosong.
- Tidak menggunakan Prisma atau SQLite.
- Registrasi menyimpan data dan file secara aman.
- Tripay sandbox telah diuji dengan mock otomatis; tersedia langkah UAT credential nyata.
- Callback signature, idempotensi, nominal, dan reference tervalidasi.
- Gmail dan Fonnte dapat dites dari admin; kegagalan masuk log/retry.
- Status lookup dilindungi rate limit dan OTP.
- Role/permission berlaku di backend.
- Secret dimask/enkripsi dan tidak bocor ke frontend/log.
- Test, typecheck, lint, dan production build lulus.
- Aplikasi dapat dipasang di Plesk sesuai dokumentasi.
- Tidak ada password/token/API key hard-coded atau masuk Git.
- Tidak ada TODO kritis, halaman kosong, atau tombol palsu.

## 19. Urutan kerja Codex

1. Audit folder/repository dan baca `AGENTS.md` jika ada.
2. Buat rencana implementasi dan daftar risiko integrasi.
3. Scaffold Laravel + Vue/Inertia + TypeScript dan authentication.
4. Implement schema, enum, policy, service, dan test fondasi.
5. Implement registrasi dan private document storage.
6. Implement Tripay sandbox service, callback, idempotensi, rekonsiliasi, dan test.
7. Implement status lookup + OTP.
8. Implement dashboard/admin modules.
9. Implement SMTP Gmail dan Fonnte via queue.
10. Terapkan UI Islamic responsive dan accessibility.
11. Jalankan test/build, perbaiki error sampai bersih.
12. Buat dokumentasi Plesk dan integrasi.
13. Laporkan file yang dibuat, command yang dijalankan, hasil test, dan hal yang hanya dapat diuji pemilik menggunakan credential sandbox/production.

## 20. Larangan

- Jangan memakai Prisma.
- Jangan mengganti MySQL dengan SQLite, termasuk diam-diam untuk production. Database test boleh memakai MySQL test agar perilaku konsisten.
- Jangan menaruh token Tripay/Fonnte/Gmail di Vue, repository, screenshot, atau log.
- Jangan menjadikan return URL browser sebagai bukti pembayaran.
- Jangan membuat file pendaftar dapat ditebak atau diakses langsung dari `/storage` publik.
- Jangan menandai pembayaran `paid` hanya karena bukti transfer diunggah.
- Jangan menangkap exception lalu mengabaikannya.
- Jangan mengklaim integrasi eksternal telah diuji nyata tanpa credential dan bukti hasil sandbox.
- Jangan menjalankan migrasi destruktif atau menghapus data produksi otomatis.

---

Mulai implementasi sekarang. Jika repository kosong, scaffold proyek di folder aktif. Jika sudah ada kode, pertahankan perubahan pengguna dan integrasikan secara bertahap. Bila credential Tripay/Fonnte/Gmail belum tersedia, selesaikan integrasi menggunakan interface, mock/fake, automated tests, dan halaman setting; lalu berikan checklist UAT yang harus dijalankan pemilik dengan credential sandbox sebelum aktivasi production.
