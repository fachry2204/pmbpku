<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Field = { key: string; label: string; type: string; hint?: string };
type TabKey = 'registration' | 'subjects' | 'payment' | 'drive' | 'notifications';
type Section = { tab: TabKey; title: string; description: string; accent: string; icon: string; fields: Field[] };

const props = defineProps<{ values: Record<string, any>; callbackUrl: string; tripayCallbackUrl: string; midtransCallbackUrl: string; returnUrl: string; unreadableSettings: string[] }>();
const page = usePage() as any;
const form = useForm(Object.fromEntries(Object.entries(props.values).map(([key, value]) => [key.replaceAll('.', '_'), value])) as Record<string, any>);
const testForm = useForm({ test_email_recipient: '' });
const copied = ref('');
const activeTab = ref<TabKey>('registration');
const tabs: { key: TabKey; label: string; description: string }[] = [
  { key: 'registration', label: 'Pendaftaran', description: 'Biaya & formulir' },
  { key: 'subjects', label: 'Mapel', description: 'Nama kolom nilai' },
  { key: 'payment', label: 'Payment Gateway', description: 'Duitku, Tripay & Midtrans' },
  { key: 'drive', label: 'Google Drive', description: 'Penyimpanan rclone' },
  { key: 'notifications', label: 'Notifikasi', description: 'WhatsApp & Email' },
];
const copyUrl = async (label: string, value: string) => {
  await navigator.clipboard.writeText(value);
  copied.value = label;
  window.setTimeout(() => { copied.value = ''; }, 1800);
};

const sections = computed<Section[]>(() => [
  { tab: 'registration', title: 'Pendaftaran', description: 'Atur biaya dan langkah upload dokumen pada formulir pendaftaran.', accent: 'bg-amber-50 text-amber-700', icon: 'Rp', fields: [
    { key: 'pmb_registration_year', label: 'Tahun Pendaftaran', type: 'year', hint: 'Tahun ini digunakan pada periode PMB dan nomor pendaftaran baru.' },
    { key: 'pmb_registration_fee', label: 'Harga Pendaftaran (Rp)', type: 'number', hint: 'Nominal biaya pendaftaran sebelum biaya layanan.' },
    { key: 'pmb_selection_location', label: 'Lokasi Seleksi', type: 'text', hint: 'Lokasi ini otomatis disimpan ke jadwal seleksi baru dan ditampilkan pada status serta kartu peserta.' },
    { key: 'registration_document_upload_disabled', label: 'Nonaktifkan Upload Dokumen', type: 'toggle', hint: 'Jika aktif, langkah upload dokumen disembunyikan dan dokumen tidak wajib. Jika nonaktif, semua dokumen wajib diunggah.' },
  ]},
  { tab: 'subjects', title: 'Mapel / Komponen Nilai', description: 'Ubah nama empat kolom nilai tanpa mengubah atau menghapus nilai pendaftar yang sudah tersimpan.', accent: 'bg-cyan-50 text-cyan-700', icon: 'N', fields: [
    { key: 'scores_label_1', label: 'Komponen Nilai 1 · Bobot 25%', type: 'text', hint: 'Default: Tes Tulis Wawasan Keislaman.' },
    { key: 'scores_label_2', label: 'Komponen Nilai 2 · Bobot 10%', type: 'text', hint: 'Default: Membaca Al Qur’an.' },
    { key: 'scores_label_3', label: 'Komponen Nilai 3 · Bobot 50%', type: 'text', hint: 'Default: Qiroatul Kutub & Muhadatsah Bahasa Arab.' },
    { key: 'scores_label_4', label: 'Komponen Nilai 4 · Bobot 15%', type: 'text', hint: 'Default: Wawancara. Mengubah nama tidak mengubah bobot.' },
  ]},
  { tab: 'payment', title: 'Payment Gateway', description: 'Konfigurasi lingkungan dan kredensial pembayaran otomatis.', accent: 'bg-blue-50 text-blue-700', icon: 'PG', fields: [
    { key: 'payment_provider', label: 'Penyedia Payment Gateway', type: 'provider', hint: 'Pilih satu provider yang digunakan pada halaman pendaftaran.' },
    ...(form.payment_provider === 'tripay' ? [
      { key: 'tripay_mode', label: 'Mode Tripay', type: 'select', hint: 'Gunakan kredensial yang sesuai dengan mode Sandbox atau Production.' },
      { key: 'tripay_merchant_code', label: 'Merchant Code Tripay', type: 'password', hint: 'Kode merchant dari dashboard Tripay.' },
      { key: 'tripay_api_key', label: 'API Key Tripay', type: 'password', hint: 'Digunakan sebagai Bearer token untuk mengakses API Tripay.' },
      { key: 'tripay_private_key', label: 'Private Key Tripay', type: 'password', hint: 'Kunci rahasia untuk signature transaksi dan validasi callback.' },
    ] : form.payment_provider === 'midtrans' ? [
      { key: 'midtrans_mode', label: 'Mode Midtrans', type: 'select', hint: 'Gunakan Sandbox untuk pengujian dan Production setelah akun Midtrans aktif.' },
      { key: 'midtrans_server_key', label: 'Server Key Midtrans', type: 'password', hint: 'Kunci rahasia backend dari menu Settings > Access Keys di dashboard Midtrans.' },
      { key: 'midtrans_client_key', label: 'Client Key Midtrans', type: 'password', hint: 'Client Key dari dashboard Midtrans. Disimpan untuk kebutuhan integrasi Snap.' },
    ] : [
      { key: 'duitku_mode', label: 'Mode Duitku', type: 'select', hint: 'Gunakan kredensial yang sesuai dengan mode Sandbox atau Production.' },
      { key: 'duitku_merchant_code', label: 'Merchant Code Duitku', type: 'password', hint: 'Kode merchant dari dashboard Duitku.' },
      { key: 'duitku_api_key', label: 'API Key Duitku', type: 'password', hint: 'Kunci rahasia untuk autentikasi transaksi dan callback.' },
    ]),
  ]},
  { tab: 'drive', title: 'Google Drive / rclone', description: 'Pindahkan dokumen pendaftar ke Google Drive untuk menghemat ruang hosting. Pas foto tetap lokal dan hanya disalin sebagai cadangan.', accent: 'bg-sky-50 text-sky-700', icon: 'GD', fields: [
    { key: 'storage_google_drive_enabled', label: 'Aktifkan Google Drive', type: 'toggle', hint: 'Jika aktif, file baru diproses melalui antrean setelah pendaftaran berhasil disimpan.' },
    { key: 'rclone_binary_path', label: 'Lokasi rclone', type: 'text', hint: 'Contoh server Linux/Plesk: /usr/local/bin/rclone.' },
    { key: 'rclone_remote', label: 'Nama Remote', type: 'text', hint: 'Nama remote yang dibuat saat menjalankan rclone config, misalnya gdrive.' },
    { key: 'rclone_config_path', label: 'Lokasi Config rclone', type: 'text', hint: 'Opsional jika menggunakan lokasi config default milik user server.' },
    { key: 'rclone_root_folder', label: 'Folder Utama Google Drive', type: 'text', hint: 'Di dalam folder ini sistem membuat satu folder untuk setiap nomor pendaftaran.' },
  ]},
  { tab: 'notifications', title: 'WhatsApp / Fonnte', description: 'Hubungkan layanan pengiriman notifikasi WhatsApp.', accent: 'bg-emerald-50 text-emerald-700', icon: 'WA', fields: [
    { key: 'notifications_whatsapp_enabled', label: 'Aktifkan Notifikasi WhatsApp', type: 'toggle', hint: 'Jika nonaktif, sistem tidak membuat antrean atau mengirim pesan WhatsApp.' },
    { key: 'fonnte_base_url', label: 'Base URL Fonnte', type: 'url', hint: 'Alamat API utama, biasanya https://api.fonnte.com.' },
    { key: 'fonnte_token', label: 'Token Fonnte', type: 'password', hint: 'Token perangkat aktif dari dashboard Fonnte.' },
  ]},
  { tab: 'notifications', title: 'Email / SMTP', description: 'Atur server email untuk pemberitahuan sistem.', accent: 'bg-violet-50 text-violet-700', icon: '@', fields: [
    { key: 'notifications_email_enabled', label: 'Aktifkan Notifikasi Email', type: 'toggle', hint: 'Jika nonaktif, sistem tidak membuat antrean atau mengirim email otomatis.' },
    { key: 'mail_host', label: 'SMTP Host', type: 'text', hint: 'Contoh: smtp.gmail.com atau server email domain.' },
    { key: 'mail_port', label: 'SMTP Port', type: 'number', hint: 'Umumnya 587 untuk TLS atau 465 untuk SSL.' },
    { key: 'mail_username', label: 'SMTP Username', type: 'password', hint: 'Nama pengguna atau alamat email akun SMTP.' },
    { key: 'mail_password', label: 'SMTP App Password', type: 'password', hint: 'Gunakan app password, bukan password akun utama.' },
    { key: 'mail_from_address', label: 'From Address', type: 'email', hint: 'Alamat pengirim yang terlihat oleh penerima email.' },
    { key: 'mail_from_name', label: 'Nama Pengirim', type: 'text', hint: 'Contoh: Panitia PMB Pendidikan Kader Ulama.' },
  ]},
]);
const visibleSections = computed(() => sections.value.filter(section => section.tab === activeTab.value));
</script>

<template>
  <Head title="Pengaturan Integrasi" />
  <main class="min-h-screen bg-slate-100 p-5 md:p-8">
    <section class="mx-auto max-w-5xl">
      <Link href="/admin/dashboard" class="text-sm font-bold text-emerald-700">← Dashboard</Link>
      <h1 class="mt-3 text-3xl font-extrabold text-emerald-950">Pengaturan Sistem</h1>
      <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Pilih kategori pengaturan agar konfigurasi lebih ringkas dan mudah ditemukan. Nilai rahasia terenkripsi; biarkan nilai bertanda titik untuk mempertahankan secret lama.</p>

      <nav class="mt-7 grid gap-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm sm:grid-cols-2 lg:grid-cols-5" aria-label="Kategori pengaturan">
        <button v-for="tab in tabs" :key="tab.key" type="button" class="rounded-xl px-4 py-3 text-left transition" :class="activeTab === tab.key ? 'bg-emerald-800 text-white shadow-md' : 'text-slate-600 hover:bg-emerald-50 hover:text-emerald-900'" @click="activeTab = tab.key">
          <span class="block text-sm font-extrabold">{{ tab.label }}</span>
          <span class="mt-0.5 block text-xs" :class="activeTab === tab.key ? 'text-emerald-100' : 'text-slate-400'">{{ tab.description }}</span>
        </button>
      </nav>

      <form class="mt-7 space-y-6" @submit.prevent="form.put('/admin/settings')">
        <div v-if="page.props.flash?.success" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 font-semibold text-emerald-800">{{ page.props.flash.success }}</div>
        <div v-if="page.props.flash?.error" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-800">{{ page.props.flash.error }}</div>
        <div v-if="unreadableSettings.length" class="rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
          <b>Beberapa kredensial lama tidak dapat dibaca.</b> Kemungkinan APP_KEY server berubah. Masukkan ulang kolom rahasia berikut lalu simpan: {{ unreadableSettings.join(', ') }}.
        </div>
        <div v-if="Object.keys(form.errors).length" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
          <b>Pengaturan belum tersimpan.</b> Periksa kolom yang ditandai merah di bawah, lalu simpan kembali.
        </div>
        <section v-for="section in visibleSections" :key="section.title" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
          <header class="flex items-start gap-4 border-b border-slate-100 px-6 py-5">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl text-sm font-black" :class="section.accent">{{ section.icon }}</span>
            <div><h2 class="text-lg font-extrabold text-slate-900">{{ section.title }}</h2><p class="mt-1 text-sm text-slate-500">{{ section.description }}</p></div>
          </header>
          <div class="grid gap-5 p-6 md:grid-cols-2">
            <label v-for="field in section.fields" :key="field.key" class="block" :class="section.fields.length === 1 ? 'md:max-w-md' : ''">
              <span class="text-sm font-bold text-slate-700">{{ field.label }}</span>
              <label v-if="field.type === 'toggle'" class="mt-3 flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <input v-model="form[field.key]" type="checkbox" class="h-5 w-5 rounded border-slate-300 text-emerald-700 focus:ring-emerald-600" />
                <span class="font-semibold" :class="form[field.key] ? 'text-emerald-700' : 'text-slate-500'">{{ form[field.key] ? 'Aktif' : 'Nonaktif' }}</span>
              </label>
              <select v-else-if="field.type === 'provider'" v-model="form[field.key]" class="mt-2 w-full rounded-xl border-slate-300 bg-white px-4 py-3 focus:border-emerald-600 focus:ring-emerald-600"><option value="duitku">Duitku</option><option value="tripay">Tripay</option><option value="midtrans">Midtrans</option></select>
              <select v-else-if="field.type === 'select'" v-model="form[field.key]" class="mt-2 w-full rounded-xl border-slate-300 bg-white px-4 py-3 focus:border-emerald-600 focus:ring-emerald-600"><option value="sandbox">Sandbox</option><option value="production">Production</option></select>
              <select v-else-if="field.type === 'year'" v-model="form[field.key]" class="mt-2 w-full rounded-xl border-slate-300 bg-white px-4 py-3 focus:border-emerald-600 focus:ring-emerald-600"><option v-for="year in Array.from({ length: 11 }, (_, index) => new Date().getFullYear() - 2 + index)" :key="year" :value="year">{{ year }}</option></select>
              <input v-else v-model="form[field.key]" :type="field.type" class="mt-2 w-full rounded-xl border-slate-300 bg-white px-4 py-3 focus:border-emerald-600 focus:ring-emerald-600" autocomplete="off" />
              <small v-if="field.hint" class="mt-2 block text-xs leading-5 text-slate-400">{{ field.hint }}</small>
              <small v-if="form.errors[field.key]" class="mt-2 block text-xs font-semibold text-red-700">{{ form.errors[field.key] }}</small>
            </label>
          </div>
          <div v-if="section.title === 'Payment Gateway'" class="border-t border-slate-100 bg-blue-50/50 p-6">
            <div class="flex items-start gap-3">
              <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-blue-100 font-black text-blue-700">↗</span>
              <div><h3 class="font-extrabold text-slate-900">URL Integrasi</h3><p class="mt-1 text-sm leading-6 text-slate-500">Salin URL berikut ke dashboard penyedia pembayaran. Pastikan domain sudah menggunakan HTTPS.</p></div>
            </div>
            <div class="mt-5 grid gap-4">
              <div>
                <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Callback URL</label>
                <div class="mt-2 flex gap-2"><input :value="form.payment_provider === 'tripay' ? tripayCallbackUrl : form.payment_provider === 'midtrans' ? midtransCallbackUrl : callbackUrl" readonly class="min-w-0 flex-1 rounded-xl border-blue-200 bg-white px-4 py-3 font-mono text-sm text-slate-700"/><button type="button" class="shrink-0 rounded-xl bg-blue-700 px-4 text-sm font-bold text-white" @click="copyUrl('callback', form.payment_provider === 'tripay' ? tripayCallbackUrl : form.payment_provider === 'midtrans' ? midtransCallbackUrl : callbackUrl)">{{ copied === 'callback' ? 'Tersalin ✓' : 'Salin' }}</button></div>
                <p class="mt-2 text-xs leading-5 text-slate-500">Digunakan sistem pembayaran untuk mengirim status transaksi secara otomatis.</p>
              </div>
              <div>
                <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Return URL</label>
                <div class="mt-2 flex gap-2"><input :value="returnUrl" readonly class="min-w-0 flex-1 rounded-xl border-blue-200 bg-white px-4 py-3 font-mono text-sm text-slate-700"/><button type="button" class="shrink-0 rounded-xl border border-blue-700 px-4 text-sm font-bold text-blue-700" @click="copyUrl('return', returnUrl)">{{ copied === 'return' ? 'Tersalin ✓' : 'Salin' }}</button></div>
                <p class="mt-2 text-xs leading-5 text-slate-500">Halaman tujuan pengguna setelah menyelesaikan proses pembayaran.</p>
              </div>
            </div>
          </div>
          <div v-if="section.title === 'Email / SMTP'" class="border-t border-slate-100 bg-violet-50/50 p-6">
            <div class="flex items-start gap-3">
              <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-violet-100 font-black text-violet-700">✉</span>
              <div><h3 class="font-extrabold text-slate-900">Tes Kirim Email</h3><p class="mt-1 text-sm leading-6 text-slate-500">Simpan pengaturan SMTP terlebih dahulu, lalu kirim email percobaan untuk memastikan koneksi berfungsi.</p></div>
            </div>
            <div class="mt-5 flex flex-col gap-3 sm:flex-row">
              <div class="min-w-0 flex-1"><input v-model="testForm.test_email_recipient" type="email" placeholder="Alamat email tujuan pengujian" class="w-full rounded-xl border-violet-200 bg-white px-4 py-3 focus:border-violet-600 focus:ring-violet-600"/><small v-if="testForm.errors.test_email_recipient" class="mt-2 block text-xs font-semibold text-red-700">{{ testForm.errors.test_email_recipient }}</small></div>
              <button type="button" :disabled="testForm.processing || !testForm.test_email_recipient" class="rounded-xl bg-violet-700 px-6 py-3 font-bold text-white transition hover:bg-violet-600 disabled:cursor-not-allowed disabled:opacity-50" @click="testForm.post('/admin/settings/test-email', { preserveScroll: true })">{{ testForm.processing ? 'Mengirim…' : 'Kirim Email Tes' }}</button>
            </div>
          </div>
          <div v-if="section.title === 'Google Drive / rclone'" class="border-t border-slate-100 bg-sky-50/50 p-6">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
              <div class="max-w-2xl">
                <h3 class="font-extrabold text-slate-900">Cara menghubungkan Google Drive</h3>
                <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm leading-6 text-slate-600">
                  <li>Pasang rclone di server, lalu buka SSH/Terminal Plesk.</li>
                  <li>Jalankan <code class="rounded bg-slate-900 px-2 py-1 text-xs text-white">rclone config</code>, buat remote Google Drive, dan lakukan login satu kali.</li>
                  <li>Simpan pengaturan di atas, lalu tekan <b>Tes Koneksi Drive</b>.</li>
                  <li>Untuk dokumen lama jalankan <code class="rounded bg-slate-900 px-2 py-1 text-xs text-white">php artisan files:migrate-to-drive --retry-failed</code>.</li>
                </ol>
                <p class="mt-3 text-xs font-semibold text-amber-700">Jangan menghapus file lokal secara manual. Sistem baru menghapus dokumen selain pas foto setelah file Drive terverifikasi.</p>
              </div>
              <button type="button" class="shrink-0 rounded-xl border border-sky-700 bg-white px-5 py-3 font-bold text-sky-700 transition hover:bg-sky-700 hover:text-white" @click="router.post('/admin/settings/test-drive', {}, { preserveScroll: true })">Tes Koneksi Drive</button>
            </div>
          </div>
        </section>

        <div class="sticky bottom-4 flex justify-end rounded-2xl border border-emerald-900/10 bg-white/90 p-4 shadow-xl backdrop-blur">
          <button :disabled="form.processing" class="rounded-xl bg-emerald-800 px-7 py-3 font-bold text-white shadow-lg shadow-emerald-900/15 transition hover:bg-emerald-700 disabled:opacity-50">{{ form.processing ? 'Menyimpan…' : 'Simpan Semua Pengaturan' }}</button>
        </div>
      </form>
    </section>
  </main>
</template>
