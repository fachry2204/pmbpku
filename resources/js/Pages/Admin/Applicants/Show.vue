<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
const props = defineProps<{ applicant: any }>();
const page = usePage() as any;
const canManage = computed(() => ['super_admin', 'admin_pmb'].includes(page.props.auth.user.role));
const photoDocument = computed(() => props.applicant.documents?.find((document: any) => document.type === 'photo_4x6' && String(document.mime_type || '').startsWith('image/')));
const formatDate = (value: string) => { const match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})/); return match ? `${match[3]}/${match[2]}/${match[1]}` : '-'; };
const reviewingId = ref<string | null>(null);
const remove = () => { if (window.confirm(`Hapus permanen ${props.applicant.full_name} beserta seluruh data dan dokumennya? Tindakan ini tidak dapat dibatalkan.`)) router.delete(`/admin/applicants/${props.applicant.id}`); };
const review = (document: any, status: string) => { const note = status === 'revision_required' ? window.prompt('Catatan perbaikan yang wajib ditampilkan ke pendaftar:') || '' : null; if (status === 'revision_required' && !note) return; reviewingId.value = document.id; router.patch(`/admin/applicants/${props.applicant.id}/documents/${document.id}`, { verification_status: status, review_note: note }, { preserveScroll: true, onFinish: () => reviewingId.value = null }); };
const statuses: Record<string, [string, string, string]> = {
  unpaid:['Belum Bayar','bg-rose-50 text-rose-700 ring-rose-200','bg-rose-500'], pending:['Menunggu','bg-amber-50 text-amber-800 ring-amber-200','bg-amber-500'], paid:['Lunas','bg-emerald-50 text-emerald-700 ring-emerald-200','bg-emerald-500'], failed:['Gagal','bg-rose-50 text-rose-700 ring-rose-200','bg-rose-500'],
  pending_review:['Menunggu Review','bg-amber-50 text-amber-800 ring-amber-200','bg-amber-500'], complete:['Berkas Lengkap','bg-emerald-50 text-emerald-700 ring-emerald-200','bg-emerald-500'], incomplete:['Belum Lengkap','bg-rose-50 text-rose-700 ring-rose-200','bg-rose-500'], valid:['Valid','bg-emerald-50 text-emerald-700 ring-emerald-200','bg-emerald-500'], revision_required:['Perlu Perbaikan','bg-amber-50 text-amber-800 ring-amber-200','bg-amber-500'],
  not_scheduled:['Belum Dijadwalkan','bg-slate-50 text-slate-700 ring-slate-200','bg-slate-400'], scheduled:['Dijadwalkan','bg-blue-50 text-blue-700 ring-blue-200','bg-blue-500'], passed:['Diterima','bg-emerald-50 text-emerald-700 ring-emerald-200','bg-emerald-500'], not_passed:['Tidak Diterima','bg-rose-50 text-rose-700 ring-rose-200','bg-rose-500'],
};
const badge = (value: string) => statuses[value] || [value?.replaceAll('_',' ') || '-', 'bg-slate-50 text-slate-700 ring-slate-200', 'bg-slate-400'];
const documentNames: Record<string,string> = { recommendation_letter:'Surat Rekomendasi', diploma:'Ijazah', photo_4x6:'Foto 4×6', identity_card:'KTP', pddikti_screenshot:'Screenshot PDDIKTI', payment_proof:'Bukti Pembayaran' };
const stage = computed(() => {
  if (props.applicant.selection_status === 'passed') return 5;
  if (props.applicant.selection_status === 'scheduled') return 4;
  if (props.applicant.document_status === 'complete') return 3;
  if (props.applicant.payment_status === 'paid') return 2;
  return 1;
});
const stageItems = ['Belum Bayar', 'Sudah Bayar', 'Berkas Lengkap', 'Tahap Seleksi', 'Lulus Seleksi'];
const currentStatus = computed(() => stageItems[stage.value - 1]);
const statusDescription = computed(() => ({
  1: 'Pembayaran belum terverifikasi. Proses berikutnya dapat dilanjutkan setelah pembayaran berhasil.',
  2: 'Pembayaran telah diterima. Dokumen pendaftar sedang menunggu pemeriksaan panitia.',
  3: 'Seluruh berkas telah dinyatakan lengkap dan pendaftar siap mengikuti tahap seleksi.',
  4: 'Pendaftar telah memasuki tahap seleksi. Pantau jadwal dan hasil seleksi pada panel ini.',
  5: 'Pendaftar dinyatakan lulus seleksi Pendidikan Kader Ulama.',
}[stage.value]));
</script>

<template>
  <Head :title="applicant.registration_number" />
  <main class="applicant-detail-page min-h-screen px-4 py-6 sm:px-6 lg:px-8 lg:py-9">
    <section class="relative z-10 mx-auto max-w-[1120px] space-y-4">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <Link href="/admin/applicants" class="group inline-flex w-fit items-center gap-2 text-sm font-bold text-white/90 transition hover:text-white"><span class="grid h-10 w-10 place-items-center rounded-xl border border-white/25 bg-white/10 transition group-hover:-translate-x-1 group-hover:bg-white/20">←</span>Kembali ke Data Pendaftar</Link>
        <div class="flex flex-wrap gap-2">
          <a :href="`/admin/applicants/${applicant.id}/download`" class="action-btn border border-white/30 bg-white/10 text-white hover:bg-white/20"><svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 20h14" stroke-linecap="round" stroke-linejoin="round"/></svg>Download</a>
          <Link v-if="canManage" :href="`/admin/applicants/${applicant.id}/edit`" class="action-btn bg-[#d8b24a] text-emerald-950 shadow-lg shadow-black/10 hover:bg-[#e8c55b]"><svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"><path d="m14 5 5 5M4 20l3.5-.7L19 7.8a2 2 0 0 0-2.8-2.8L4.7 16.5 4 20Z" stroke-linecap="round" stroke-linejoin="round"/></svg>Edit Data</Link>
          <button v-if="canManage" type="button" class="action-btn border border-rose-200/50 bg-rose-950/20 text-rose-100 hover:bg-rose-700" @click="remove"><svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v5m4-5v5" stroke-linecap="round" stroke-linejoin="round"/></svg>Hapus</button>
        </div>
      </div>

      <article class="overflow-hidden rounded-[28px] border border-white/70 bg-[#fcfdfb] shadow-2xl shadow-black/25">
        <header class="profile-header grid gap-5 px-5 py-6 sm:px-7 lg:grid-cols-[150px_1fr] lg:px-8">
          <div class="mx-auto h-[190px] w-[140px] overflow-hidden rounded-xl bg-gradient-to-br from-emerald-700 to-emerald-950 shadow-lg ring-1 ring-emerald-950/20 lg:row-span-2"><img v-if="photoDocument" :src="`/admin/documents/${photoDocument.id}/view`" :alt="`Pas foto ${applicant.full_name}`" class="h-full w-full object-cover transition duration-500 hover:scale-105"/><span v-else class="grid h-full w-full place-items-center text-5xl font-black text-white/90">{{ applicant.full_name?.charAt(0) }}</span></div>
          <div class="self-end text-center lg:text-left"><p class="text-[10px] font-bold uppercase tracking-[.12em] text-emerald-900/65">Nomor Pendaftaran</p><p class="mt-1 font-mono text-base font-black text-emerald-700">{{ applicant.registration_number }}</p><h1 class="mt-0.5 text-2xl font-black tracking-tight text-emerald-950 sm:text-3xl lg:text-4xl">{{ applicant.full_name }}</h1></div>
          <div class="status-banner grid gap-4 rounded-xl border border-amber-300 bg-gradient-to-r from-[#fff4d5] via-[#fff9e9] to-[#ffefbf] p-4 lg:grid-cols-[220px_1fr] lg:items-center"><div class="flex items-center gap-3"><span class="grid h-12 w-12 shrink-0 place-items-center rounded-full border border-amber-300 bg-white/70 text-amber-700 shadow-sm"><svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 3h9l4 4v14H6zM15 3v5h5M9 12h6M9 16h4" stroke-linecap="round" stroke-linejoin="round"/></svg></span><div><p class="text-xs font-semibold text-amber-950/70">Status Saat Ini</p><p class="text-xl font-black text-amber-950">{{ currentStatus }}</p></div></div><p class="border-amber-300 text-xs leading-5 text-amber-950/80 lg:border-l lg:pl-5">{{ statusDescription }}</p></div>
        </header>

        <div class="mx-5 rounded-xl border border-slate-200 bg-white px-4 py-4 shadow-md shadow-slate-900/5 sm:mx-7 sm:px-6">
          <div class="grid grid-cols-5"><div v-for="(item, index) in stageItems" :key="item" class="relative flex min-w-0 flex-col items-center text-center"><div v-if="index < 4" class="absolute left-1/2 right-[-50%] top-4 h-px" :class="stage > index + 1 ? 'bg-emerald-700' : 'border-t border-dashed border-slate-300'"></div><span class="relative z-10 grid h-8 w-8 place-items-center rounded-full text-xs font-black ring-4 ring-white" :class="stage >= index + 1 ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-500 ring-1 ring-inset ring-slate-200'">{{ index + 1 }}</span><b class="mt-3 text-[10px] leading-4 sm:text-xs" :class="stage >= index + 1 ? 'text-emerald-800' : 'text-slate-500'">{{ item }}</b><small v-if="stage === index + 1" class="mt-0.5 hidden text-[10px] text-slate-400 sm:block">Tahap saat ini</small></div></div>
        </div>

        <div class="grid gap-4 p-5 sm:p-7 lg:grid-cols-2">
          <section class="detail-panel lg:col-span-2"><h2 class="panel-title"><span class="panel-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span>Data Calon Mahasiswa</h2><dl class="mt-2 grid gap-x-7 md:grid-cols-2"><div class="data-row"><dt>Nama Lengkap</dt><dd>{{ applicant.full_name }}</dd></div><div class="data-row"><dt>Tempat, Tanggal Lahir</dt><dd>{{ applicant.birth_place }}, {{ formatDate(applicant.birth_date) }}</dd></div><div class="data-row"><dt>Email</dt><dd><a :href="`mailto:${applicant.email}`">{{ applicant.email }}</a></dd></div><div class="data-row"><dt>Nomor WhatsApp</dt><dd><a :href="`https://wa.me/${applicant.whatsapp_normalized}`" target="_blank">{{ applicant.whatsapp_display }}</a></dd></div><div class="data-row md:col-span-2"><dt>Alamat</dt><dd class="whitespace-pre-line">{{ applicant.address }}</dd></div></dl></section>

          <section class="detail-panel"><div class="flex items-center justify-between"><h2 class="panel-title"><span class="panel-icon"><svg viewBox="0 0 24 24"><path d="M6 3h9l4 4v14H6zM15 3v5h5M9 12h6M9 16h5"/></svg></span>Dokumen Pendaftaran</h2><span class="text-xs font-bold text-emerald-700">{{ applicant.documents.length }} file</span></div><div v-if="applicant.documents.length" class="mt-3 divide-y divide-slate-200/80"><div v-for="d in applicant.documents" :key="d.id" class="document-row"><div class="min-w-0"><p class="truncate text-sm font-bold text-slate-700">{{ documentNames[d.type] || d.type }}</p><span class="mt-1 inline-flex rounded-md px-2 py-0.5 text-[10px] font-bold ring-1 ring-inset" :class="badge(d.verification_status)[1]">{{ badge(d.verification_status)[0] }}</span></div><div class="flex shrink-0 gap-1"><a :href="`/admin/documents/${d.id}/download`" class="mini-btn" title="Unduh dokumen">↓</a><button type="button" :disabled="reviewingId === d.id" class="mini-btn text-emerald-700" title="Tandai valid" @click="review(d,'valid')">✓</button><button type="button" :disabled="reviewingId === d.id" class="mini-btn text-amber-700" title="Minta perbaikan" @click="review(d,'revision_required')">!</button></div></div></div><div v-else class="mt-4 rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">Belum ada dokumen yang diunggah.</div></section>

          <aside class="detail-panel"><h2 class="panel-title"><span class="panel-icon"><svg viewBox="0 0 24 24"><path d="M3 12h4l2-7 4 14 2-7h6"/></svg></span>Status Proses</h2><div class="mt-4 space-y-3"><div v-for="item in [{ label: 'Pembayaran', value: applicant.payment_status, note: applicant.payment_status === 'paid' ? 'Pembayaran telah terverifikasi.' : 'Menunggu pembayaran terverifikasi.' }, { label: 'Berkas', value: applicant.document_status, note: applicant.document_status === 'complete' ? 'Berkas dinyatakan lengkap.' : 'Berkas sedang dalam peninjauan.' }, { label: 'Seleksi', value: applicant.selection_status, note: applicant.selection_status === 'passed' ? 'Pendaftar dinyatakan lulus.' : 'Jadwal dan hasil akan diperbarui.' }]" :key="item.label" class="process-card"><div class="flex items-center justify-between gap-2"><b class="text-sm text-emerald-950">{{ item.label }}</b><span class="rounded-full px-2 py-0.5 text-[10px] font-bold ring-1 ring-inset" :class="badge(item.value)[1]">{{ badge(item.value)[0] }}</span></div><p class="mt-1 text-xs leading-5 text-slate-500">{{ item.note }}</p></div></div></aside>
        </div>

        <footer class="mx-5 mb-5 flex items-center gap-3 rounded-xl border border-emerald-900/10 bg-emerald-50/70 px-4 py-3 sm:mx-7 sm:mb-7"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white text-emerald-800 shadow-sm"><svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 10V7a5 5 0 0 1 10 0v3M5 10h14v11H5zM12 14v3" stroke-linecap="round" stroke-linejoin="round"/></svg></span><div><p class="text-xs font-black text-emerald-950">Data Pendaftar Terlindungi</p><p class="mt-0.5 text-[11px] leading-4 text-slate-600">Gunakan informasi pribadi dan dokumen hanya untuk proses administrasi dan seleksi PMB Pendidikan Kader Ulama.</p></div></footer>
      </article>
    </section>
  </main>
</template>

<style scoped>
.applicant-detail-page { position: relative; overflow: hidden; background: radial-gradient(circle at 12% 18%, rgba(21, 128, 91, .46), transparent 29%), radial-gradient(circle at 88% 72%, rgba(7, 89, 64, .65), transparent 28%), linear-gradient(135deg, #022c22 0%, #064e3b 52%, #01372c 100%); }
.applicant-detail-page::before { content: ''; position: fixed; inset: 0; pointer-events: none; opacity: .11; background-image: url('/images/islamic-geometric-bg.png'); background-size: 430px; }
.applicant-detail-page::after { content: ''; position: fixed; width: 380px; height: 380px; right: -170px; top: 16%; border: 2px solid rgba(216, 178, 74, .35); border-radius: 42% 58% 48% 52%; transform: rotate(28deg); pointer-events: none; }
.profile-header { background-color: #fff; background-image: radial-gradient(circle at 88% 16%, rgba(6, 95, 70, .08), transparent 30%), url('/images/islamic-geometric-bg.png'); background-size: auto, 360px; background-blend-mode: normal, soft-light; }
.action-btn { @apply inline-flex items-center justify-center gap-2 rounded-lg px-3.5 py-2 text-xs font-bold transition duration-200 hover:-translate-y-0.5 active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:ring-offset-2 focus:ring-offset-emerald-950; }
.detail-panel { @apply min-w-0 rounded-xl border border-slate-200 bg-white p-4 shadow-md shadow-slate-900/5; }
.panel-title { @apply flex items-center gap-2 text-sm font-black text-emerald-950; }
.panel-icon { @apply grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-800; }
.panel-icon svg { @apply h-4 w-4; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
.data-row { @apply grid gap-1 border-b border-slate-200/80 py-2.5 text-xs sm:grid-cols-[125px_1fr]; }
.data-row dt { @apply font-semibold text-slate-500; }
.data-row dd { @apply min-w-0 break-words font-semibold leading-5 text-slate-700; }
.data-row a { @apply transition hover:text-emerald-700 hover:underline; }
.document-row { @apply flex items-center justify-between gap-3 py-2.5; }
.mini-btn { @apply grid h-7 w-7 place-items-center rounded-md border border-slate-200 bg-white text-[10px] font-black text-slate-600 transition hover:-translate-y-0.5 hover:border-emerald-300 hover:bg-emerald-50 disabled:opacity-40; }
.process-card { @apply rounded-lg border border-slate-200 bg-slate-50/70 p-3 transition duration-200 hover:-translate-y-0.5 hover:border-emerald-200 hover:bg-white hover:shadow-md; }
@media (prefers-reduced-motion: reduce) { .action-btn, .mini-btn, .process-card { transition: none; transform: none !important; } }
</style>
