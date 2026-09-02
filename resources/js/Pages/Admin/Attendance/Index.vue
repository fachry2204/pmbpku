<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{ identifier: string; notFound: boolean; applicant: any | null }>();
const page = usePage() as any;
const search = ref(props.identifier || '');
const scanning = ref(false);
const video = ref<HTMLVideoElement | null>(null);
let stream: MediaStream | null = null;
const form = useForm({ applicant_id: props.applicant?.id || '', registration_number: props.applicant?.registration_number || '' });
const isAttended = computed(() => props.applicant?.selection_status === 'attending_test' || props.applicant?.schedule?.attendance_status === 'attended');
const canAttend = computed(() => props.applicant?.selection_status === 'scheduled' && !!props.applicant?.schedule);
const find = () => router.get('/absen', { identifier: search.value }, { preserveState: false, replace: true });
const attend = () => { form.applicant_id = props.applicant.id; form.registration_number = props.applicant.registration_number; form.post('/absen', { preserveScroll: true }); };
const stopScan = () => { stream?.getTracks().forEach((track) => track.stop()); stream = null; scanning.value = false; };
const scan = async () => {
  const Detector = (window as any).BarcodeDetector;
  if (!Detector || !navigator.mediaDevices?.getUserMedia) { window.alert('Pemindaian QR tidak didukung browser ini. Masukkan nomor pendaftaran secara manual.'); return; }
  scanning.value = true;
  try {
    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
    if (video.value) video.value.srcObject = stream;
    const detector = new Detector({ formats: ['qr_code'] });
    const read = async () => { if (!scanning.value || !video.value) return; const codes = await detector.detect(video.value); if (codes[0]?.rawValue) { try { const url = new URL(codes[0].rawValue); search.value = url.searchParams.get('identifier') || codes[0].rawValue; } catch { search.value = codes[0].rawValue; } stopScan(); find(); return; } requestAnimationFrame(read); };
    await video.value?.play(); read();
  } catch { stopScan(); window.alert('Kamera tidak dapat diakses. Pastikan izin kamera diberikan.'); }
};
</script>

<template>
  <Head title="Absensi Peserta Seleksi" />
  <main class="min-h-screen px-4 py-8 sm:px-6 lg:px-8">
    <section class="mx-auto max-w-3xl">
      <header class="text-center text-white"><p class="text-xs font-black uppercase tracking-[.2em] text-amber-300">Petugas Seleksi</p><h1 class="mt-2 text-3xl font-black">Absensi Peserta</h1><p class="mt-2 text-sm text-emerald-50/80">Scan QR pada kartu peserta atau cari menggunakan nomor pendaftaran, nomor telepon, maupun email.</p></header>

      <form class="mt-7 flex flex-wrap gap-2 rounded-2xl border border-white/20 bg-white p-3 shadow-2xl" @submit.prevent="find"><input v-model="search" autofocus required class="min-w-0 flex-1 rounded-xl border-slate-300 px-4 py-3 text-sm focus:border-emerald-600 focus:ring-emerald-600" placeholder="Nomor pendaftaran / telepon / email"><button type="button" class="rounded-xl border border-emerald-700 px-4 py-3 text-sm font-black text-emerald-800 hover:bg-emerald-50" @click="scan">▣ Scan QR</button><button class="rounded-xl bg-emerald-800 px-5 py-3 text-sm font-black text-white hover:bg-emerald-700">Cari Peserta</button></form>
      <div v-if="scanning" class="mt-4 rounded-2xl bg-slate-950 p-4 text-center"><video ref="video" class="mx-auto max-h-64 w-full rounded-xl" muted playsinline></video><button class="mt-3 rounded-lg bg-white px-4 py-2 text-sm font-bold text-slate-800" @click="stopScan">Tutup Kamera</button></div>

      <div v-if="page.props.flash?.success" class="mt-4 rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-sm font-bold text-emerald-800">✓ {{ page.props.flash.success }}</div>
      <div v-if="notFound" class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-700">Data peserta tidak ditemukan. Periksa kembali data yang dimasukkan.</div>
      <div v-if="form.errors.applicant_id" class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-700">{{ form.errors.applicant_id }}</div>

      <article v-if="applicant" class="mt-5 overflow-hidden rounded-3xl border border-white/70 bg-white shadow-2xl">
        <div class="bg-gradient-to-r from-emerald-950 to-emerald-700 p-6 text-white"><div class="flex items-center gap-5"><div class="h-28 w-24 shrink-0 overflow-hidden rounded-xl border-4 border-white/80 bg-emerald-900"><img v-if="applicant.photo_url" :src="applicant.photo_url" class="h-full w-full object-cover"><span v-else class="grid h-full place-items-center text-3xl font-black">{{ applicant.full_name.charAt(0) }}</span></div><div class="min-w-0"><p class="text-xs font-bold uppercase tracking-widest text-amber-300">{{ applicant.registration_number }}</p><h2 class="mt-1 text-2xl font-black">{{ applicant.full_name }}</h2><p class="mt-2 break-all text-xs text-emerald-100">{{ applicant.email }} · {{ applicant.whatsapp }}</p></div></div></div>
        <div class="space-y-5 p-6">
          <section v-if="applicant.schedule" class="grid gap-3 rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4 sm:grid-cols-3"><div><span class="data-label">Tanggal</span><b>{{ applicant.schedule.date }}</b></div><div><span class="data-label">Waktu</span><b>{{ applicant.schedule.time }}</b></div><div><span class="data-label">Lokasi</span><b>{{ applicant.schedule.location }}</b></div></section>
          <div v-if="isAttended" class="rounded-2xl border border-emerald-300 bg-emerald-50 p-5 text-center"><span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-emerald-700 text-2xl font-black text-white">✓</span><h3 class="mt-3 font-black text-emerald-900">Peserta Sudah Hadir</h3><p class="mt-1 text-sm text-emerald-700">Tercatat {{ applicant.schedule?.attended_at || 'pada sesi seleksi ini' }}.</p></div>
          <button v-else-if="canAttend" :disabled="form.processing" class="w-full rounded-xl bg-amber-400 px-5 py-4 font-black text-emerald-950 shadow-lg transition hover:-translate-y-0.5 hover:bg-amber-300 disabled:opacity-60" @click="attend">{{ form.processing ? 'Menyimpan Kehadiran…' : 'Konfirmasi Peserta Hadir' }}</button>
          <div v-else class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-center text-sm font-semibold text-amber-800">Peserta ini belum berstatus Seleksi Terjadwal dan belum dapat diabsen.</div>
        </div>
      </article>
    </section>
  </main>
</template>

<style scoped>
.data-label{display:block;margin-bottom:.35rem;color:#64748b;font-size:.68rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.data-label+b{display:block;color:#064e3b;font-size:.8rem;line-height:1.35rem}
</style>
