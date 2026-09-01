# Integrasi

Tripay memakai credential sandbox/production server-side. Callback HTTPS adalah `/webhooks/tripay`; signature HMAC SHA-256 dihitung dari raw body dengan private key. UAT wajib menggunakan credential pemilik: ambil channel, buat transaksi, simulasikan PAID dan callback duplikat, expired/failed, timeout, lalu pastikan perubahan hanya sekali.

Fonnte memakai POST `https://api.fonnte.com/send`, token pada header Authorization, serta target, message, dan countryCode=62. Gmail memakai SMTP TLS port 587 dan Google App Password. Semua secret hanya berada di `.env` atau setting terenkripsi, tidak di Vue/log.

Super admin dapat memasukkan credential di `/admin/settings`. Nilai rahasia ditampilkan masked; kolom kosong atau masked mempertahankan secret lama. Setelah perubahan credential, uji sandbox sebelum mengaktifkan mode production.

## Checklist UAT pemilik

1. Masukkan credential sandbox Tripay, ambil channel, dan buat satu invoice uji.
2. Jalankan simulasi PAID dari console sandbox; pastikan callback, timeline, dan notifikasi tercatat satu kali.
3. Kirim callback identik dua kali, lalu uji EXPIRED dan FAILED.
4. Putuskan akses provider sementara dan jalankan `php artisan payments:reconcile` untuk memeriksa pesan gagal yang aman serta pemulihan.
5. Kirim email tes menggunakan Gmail App Password dan pesan Fonnte ke nomor panitia.
6. Jalankan worker database dan periksa halaman log notifikasi, retry, serta audit log.
7. Jangan mengaktifkan production sebelum callback HTTPS dan seluruh langkah sandbox berhasil.
