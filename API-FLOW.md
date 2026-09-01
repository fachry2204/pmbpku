# Alur API

Pendaftaran memvalidasi data dan file, mengunci periode aktif, membentuk nomor registrasi, menyimpan dokumen privat, lalu mengantre notifikasi setelah commit. `submission_uuid` membuat submit ulang idempoten.

Pembayaran dibuat server-to-server. Browser return URL tidak pernah menjadi bukti pembayaran. Callback Tripay diverifikasi dari raw body, diproses dalam transaction dan row lock, lalu memperbarui dimensi payment tanpa mencampur document/selection status.

Cek status harus mencocokkan email dan WhatsApp sekaligus, menerapkan rate limit, lalu mengirim OTP yang di-hash, berlaku lima menit, dibatasi percobaan, dan single-use sebelum menampilkan detail.
