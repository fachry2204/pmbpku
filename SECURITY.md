# Keamanan

Berkas pendaftar berada di private storage dengan nama UUID. SVG ditolak. Batasi MIME/ukuran di backend dan integrasikan ClamAV melalui scanner saat tersedia. Webhook Tripay merupakan satu-satunya pengecualian CSRF dan wajib lolos signature, reference, serta nominal.

Gunakan HTTPS, secure/HTTP-only/SameSite cookie, CSP, HSTS setelah HTTPS stabil, backup terenkripsi, audit akses dokumen dan perubahan status, serta rotasi credential saat insiden. Jangan log OTP, KTP, password, token, API/private key, authorization header, atau file.

Endpoint `/health` hanya menampilkan status komponen generik. Security headers mencakup CSP, frame protection, content-type protection, referrer policy, permissions policy, dan HSTS ketika request HTTPS. Tinjau CSP setiap kali menambah origin aset baru.
