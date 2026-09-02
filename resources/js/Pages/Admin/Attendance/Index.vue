<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Html5Qrcode } from 'html5-qrcode';

const props = defineProps<{ identifier: string; notFound: boolean; applicant: any | null }>();
const page = usePage() as any;
const search = ref(props.identifier || '');
const scanning = ref(false);
const scanner = ref<Html5Qrcode | null>(null);
const showApplicant = ref(!!props.applicant);
const notFoundModal = ref(props.notFound);
const form = useForm({ applicant_id: props.applicant?.id || '', registration_number: props.applicant?.registration_number || '' });
let previousViewport = '';
onMounted(() => {
  const viewport = document.querySelector<HTMLMetaElement>('meta[name="viewport"]');
  if (!viewport) return;
  previousViewport = viewport.content;
  viewport.content = 'width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no, viewport-fit=cover';
});
onBeforeUnmount(() => {
  const viewport = document.querySelector<HTMLMetaElement>('meta[name="viewport"]');
  if (viewport && previousViewport) viewport.content = previousViewport;
  void stopScan();
});
const isAttended = computed(() => props.applicant?.selection_status === 'attending_test' || props.applicant?.schedule?.attendance_status === 'attended');
const canAttend = computed(() => props.applicant?.selection_status === 'scheduled' && !!props.applicant?.schedule);
watch(() => props.applicant, (applicant) => { showApplicant.value = !!applicant; });
watch(() => props.notFound, (notFound) => { notFoundModal.value = notFound; });
const find = async () => {
  showApplicant.value = false;
  notFoundModal.value = false;
  await stopScan();
  router.get('/absen', { identifier: search.value }, { preserveState: false, replace: true });
};
const attend = () => { form.applicant_id = props.applicant.id; form.registration_number = props.applicant.registration_number; form.post('/absen', { preserveScroll: true }); };
const stopScan = async () => { if (scanner.value) { await scanner.value.stop().catch(() => undefined); scanner.value.clear(); scanner.value = null; } scanning.value = false; };
const scan = async () => {
  if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
    window.alert('Kamera hanya dapat digunakan melalui koneksi HTTPS dan browser yang mendukung akses kamera.');
    return;
  }
  try {
    const permissionStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
    const deviceId = permissionStream.getVideoTracks()[0]?.getSettings().deviceId;
    permissionStream.getTracks().forEach((track) => track.stop());
    scanning.value = true;
    await nextTick();
    scanner.value = new Html5Qrcode('attendance-qr-reader');
    await scanner.value.start(deviceId ? { deviceId: { exact: deviceId } } : { facingMode: 'environment' }, { fps: 10, qrbox: { width: 220, height: 220 } }, async (value: string) => { try { const url = new URL(value); search.value = url.searchParams.get('identifier') || value; } catch { search.value = value; } await stopScan(); find(); }, () => undefined);
  } catch (error) {
    await stopScan();
    const denied = error instanceof DOMException && ['NotAllowedError', 'PermissionDeniedError'].includes(error.name);
    window.alert(denied ? 'Izin kamera ditolak. Buka pengaturan situs di browser, izinkan Kamera, lalu coba kembali.' : 'Kamera tidak dapat dibuka. Pastikan kamera tidak sedang digunakan aplikasi lain.');
  }
};
</script>

<template>
  <Head title="Absensi Peserta Seleksi"><meta head-key="viewport" name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no, viewport-fit=cover" /></Head>
  <main class="attendance-page min-h-[100svh] px-4 pb-32 pt-8 sm:px-6 lg:px-8">
    <section class="mx-auto max-w-3xl">
      <header class="text-center text-white"><p class="text-xs font-black uppercase tracking-[.2em] text-amber-300">Petugas Seleksi</p><h1 class="mt-2 text-3xl font-black">Absensi Peserta</h1><p class="mt-2 text-sm text-emerald-50/80">Scan QR pada kartu seleksi untuk menampilkan dan mencatat kehadiran peserta.</p></header>
      <div v-if="!applicant || !showApplicant" class="mx-auto mt-8 max-w-md rounded-3xl border border-white/20 bg-white/10 p-6 text-center text-white shadow-2xl backdrop-blur-sm"><span class="mx-auto grid h-16 w-16 place-items-center rounded-full border border-amber-300/50 bg-amber-300/15 text-3xl">▣</span><h2 class="mt-4 text-lg font-black">Siap Memindai Peserta</h2><p class="mt-2 text-sm leading-6 text-emerald-50/75">Tekan tombol Scan Peserta di bawah, lalu arahkan kamera ke QR pada kartu seleksi.</p></div>
      <Teleport to="body"><div v-if="scanning" class="fixed inset-0 z-[100] flex flex-col bg-black"><div class="flex items-center justify-between bg-black/90 px-4 py-3 text-white"><div><b class="block">Scan QR Peserta</b><span class="text-xs text-white/70">Arahkan kamera ke QR pada kartu peserta</span></div><button type="button" class="rounded-xl border border-white/40 px-4 py-2 text-sm font-bold" @click="stopScan">Tutup</button></div><div class="grid min-h-0 flex-1 place-items-center overflow-hidden"><div id="attendance-qr-reader" class="w-full max-w-2xl overflow-hidden bg-black"></div></div></div></Teleport>

      <div v-if="page.props.flash?.success" class="mt-4 rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-sm font-bold text-emerald-800">✓ {{ page.props.flash.success }}</div>
      <Teleport to="body"><div v-if="notFoundModal" class="fixed inset-0 z-[110] grid place-items-center bg-slate-950/75 p-5" @click.self="notFoundModal=false"><div class="w-full max-w-sm rounded-3xl bg-white p-6 text-center shadow-2xl"><span class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-rose-100 text-2xl font-black text-rose-700">!</span><h2 class="mt-4 text-xl font-black text-slate-900">Data Peserta Tidak Ditemukan</h2><p class="mt-2 text-sm leading-6 text-slate-600">Periksa kembali nomor pendaftaran, nomor telepon, atau email. Jika data tetap tidak ditemukan, hubungi Admin.</p><button type="button" class="mt-5 w-full rounded-xl bg-emerald-800 px-5 py-3 font-black text-white" @click="notFoundModal=false">Tutup</button></div></div></Teleport>
      <div v-if="form.errors.applicant_id" class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-700">{{ form.errors.applicant_id }}</div>

      <article v-if="applicant && showApplicant" class="mt-5 overflow-hidden rounded-3xl border border-white/70 bg-white shadow-2xl">
        <div class="bg-gradient-to-r from-emerald-950 to-emerald-700 p-6 text-white"><div class="flex items-center gap-5"><div class="h-28 w-24 shrink-0 overflow-hidden rounded-xl border-4 border-white/80 bg-emerald-900"><img v-if="applicant.photo_url" :src="applicant.photo_url" class="h-full w-full object-cover"><span v-else class="grid h-full place-items-center text-3xl font-black">{{ applicant.full_name.charAt(0) }}</span></div><div class="min-w-0"><p class="text-xs font-bold uppercase tracking-widest text-amber-300">{{ applicant.registration_number }}</p><h2 class="mt-1 text-2xl font-black">{{ applicant.full_name }}</h2><p class="mt-2 break-all text-xs text-emerald-100">{{ applicant.email }} · {{ applicant.whatsapp }}</p></div></div></div>
        <div class="space-y-5 p-6">
          <section v-if="applicant.schedule" class="grid gap-3 rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4 sm:grid-cols-3"><div><span class="data-label">Tanggal</span><b>{{ applicant.schedule.date }}</b></div><div><span class="data-label">Waktu</span><b>{{ applicant.schedule.time }}</b></div><div><span class="data-label">Lokasi</span><b>{{ applicant.schedule.location }}</b></div></section>
          <div v-if="isAttended" class="rounded-2xl border border-emerald-300 bg-emerald-50 p-5 text-center"><span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-emerald-700 text-2xl font-black text-white">✓</span><h3 class="mt-3 font-black text-emerald-900">Peserta Sudah Hadir</h3><p class="mt-1 text-sm text-emerald-700">Tercatat {{ applicant.schedule?.attended_at || 'pada sesi seleksi ini' }}.</p></div>
          <button v-else-if="canAttend" :disabled="form.processing" class="w-full rounded-xl bg-amber-400 px-5 py-4 font-black text-emerald-950 shadow-lg transition hover:-translate-y-0.5 hover:bg-amber-300 disabled:opacity-60" @click="attend">{{ form.processing ? 'Menyimpan Kehadiran…' : 'Konfirmasi Peserta Hadir' }}</button>
          <div v-else class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-center text-sm font-semibold text-amber-800">Peserta ini belum berstatus Seleksi Terjadwal dan belum dapat diabsen.</div>
        </div>
      </article>
    </section>
    <div class="fixed inset-x-0 bottom-0 z-40 border-t border-white/70 bg-white/95 px-4 pt-3 shadow-[0_-12px_35px_rgba(2,44,34,.28)] backdrop-blur-xl" style="padding-bottom:calc(env(safe-area-inset-bottom) + .75rem)"><button type="button" class="mx-auto flex w-full max-w-xs items-center justify-center gap-3 rounded-2xl bg-emerald-800 px-6 py-4 font-black text-white shadow-lg shadow-emerald-950/25 transition active:scale-[.98]" @click="scan"><span class="text-xl">▣</span>Scan Peserta</button></div>
  </main>
</template>

<style scoped>
.attendance-page{touch-action:pan-x pan-y;overscroll-behavior:none}
.data-label{display:block;margin-bottom:.35rem;color:#64748b;font-size:.68rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.data-label+b{display:block;color:#064e3b;font-size:.8rem;line-height:1.35rem}
</style>
