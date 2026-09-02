<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Html5Qrcode } from 'html5-qrcode';
import { nextTick, ref } from 'vue';
const props = defineProps<{ initialIdentifier?: string }>();
const lookup = useForm({ identifier: props.initialIdentifier || '' });
const scanning = ref(false);
const scanner = ref<Html5Qrcode | null>(null);
const stopScan = async () => { if (scanner.value) { await scanner.value.stop().catch(() => undefined); scanner.value.clear(); scanner.value = null; } scanning.value = false; };
const scan = async () => {
  if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) { window.alert('Kamera hanya dapat digunakan melalui koneksi HTTPS dan browser yang mendukung akses kamera.'); return; }
  try {
    const permissionStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
    const deviceId = permissionStream.getVideoTracks()[0]?.getSettings().deviceId;
    permissionStream.getTracks().forEach((track) => track.stop());
    scanning.value = true;
    await nextTick();
    scanner.value = new Html5Qrcode('status-qr-reader');
    await scanner.value.start(deviceId ? { deviceId: { exact: deviceId } } : { facingMode: 'environment' }, { fps: 10, qrbox: { width: 220, height: 220 } }, async (value: string) => { if (value.includes('/cek-status/email/')) { await stopScan(); window.location.href = value; return; } lookup.identifier = value; await stopScan(); }, () => undefined);
  } catch (error) {
    await stopScan();
    const denied = error instanceof DOMException && ['NotAllowedError', 'PermissionDeniedError'].includes(error.name);
    window.alert(denied ? 'Izin kamera ditolak. Buka pengaturan situs di browser, izinkan Kamera, lalu coba kembali.' : 'Kamera tidak dapat dibuka. Pastikan kamera tidak sedang digunakan aplikasi lain.');
  }
};
</script>

<template>
  <Head title="Cek Status" />
  <main class="islamic-gradient-page grid min-h-screen place-items-center p-5">
    <section class="islamic-glass-card islamic-ornament-card w-full max-w-lg rounded-3xl p-8">
      <Link href="/" class="text-sm font-semibold text-emerald-700">← Kembali ke halaman utama</Link>
      <p class="mt-6 font-semibold text-emerald-700">PMB Pendidikan Kader Ulama</p>
      <h1 class="mt-1 text-3xl font-bold text-emerald-950">Cek status pendaftaran</h1>
      <p class="mt-3 text-sm leading-6 text-slate-600">Masukkan nomor pendaftaran, email, atau nomor HP/WhatsApp yang digunakan saat mendaftar.</p>

      <form class="mt-7 space-y-4" @submit.prevent="lookup.post('/cek-status')">
        <label class="block">
          <span class="text-sm font-semibold">Nomor pendaftaran, email, atau nomor HP</span>
          <input v-model="lookup.identifier" type="text" required autofocus autocomplete="off" placeholder="" class="mt-2 w-full rounded-xl border-slate-300" />
          <small v-if="lookup.errors.identifier" class="mt-2 block text-sm text-red-700">{{ lookup.errors.identifier }}</small>
        </label>
        <div class="grid gap-2 sm:grid-cols-2"><button type="button" class="w-full rounded-xl border border-emerald-700 px-4 py-3 font-bold text-emerald-800" @click="scan">Scan QR</button><button :disabled="lookup.processing" class="w-full rounded-xl bg-emerald-800 px-4 py-3 font-bold text-white disabled:opacity-50">
          {{ lookup.processing ? 'Mencari…' : 'Tampilkan Status Pendaftaran' }}
        </button></div>
        <Teleport to="body"><div v-if="scanning" class="fixed inset-0 z-[100] flex flex-col bg-black"><div class="flex items-center justify-between bg-black/90 px-4 py-3 text-white"><div><b class="block">Scan QR Pendaftaran</b><span class="text-xs text-white/70">Arahkan kamera ke QR pada dokumen pendaftaran</span></div><button type="button" class="rounded-xl border border-white/40 px-4 py-2 text-sm font-bold" @click="stopScan">Tutup</button></div><div class="grid min-h-0 flex-1 place-items-center overflow-hidden"><div id="status-qr-reader" class="w-full max-w-2xl overflow-hidden bg-black"></div></div></div></Teleport>
      </form>
    </section>
  </main>
</template>
