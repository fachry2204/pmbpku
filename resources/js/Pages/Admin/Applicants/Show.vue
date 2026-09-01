<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
const props = defineProps<{ applicant: any }>();
const page = usePage() as any;
const canManage = computed(() => ['super_admin', 'admin_pmb'].includes(page.props.auth.user.role));
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
</script>

<template>
  <Head :title="applicant.registration_number" />
  <main class="min-h-screen bg-[radial-gradient(circle_at_top_right,_#dff5e9_0,_#f8fafc_34%,_#f1f5f9_100%)] px-4 py-6 sm:px-6 lg:px-8 lg:py-9">
    <section class="mx-auto max-w-6xl space-y-6">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <Link href="/admin/applicants" class="group inline-flex w-fit items-center gap-2 text-sm font-bold text-emerald-800 transition hover:text-emerald-600"><span class="grid h-9 w-9 place-items-center rounded-full bg-white shadow-sm ring-1 ring-slate-200 transition group-hover:-translate-x-1 group-hover:ring-emerald-300">←</span>Kembali ke Data Pendaftar</Link>
        <div class="flex flex-wrap gap-2">
          <a :href="`/admin/applicants/${applicant.id}/download`" class="action-btn border border-emerald-200 bg-white text-emerald-800 hover:border-emerald-400 hover:bg-emerald-50"><svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 20h14" stroke-linecap="round" stroke-linejoin="round"/></svg>Download</a>
          <Link v-if="canManage" :href="`/admin/applicants/${applicant.id}/edit`" class="action-btn bg-blue-600 text-white shadow-sm shadow-blue-200 hover:bg-blue-700 hover:shadow-md"><svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"><path d="m14 5 5 5M4 20l3.5-.7L19 7.8a2 2 0 0 0-2.8-2.8L4.7 16.5 4 20Z" stroke-linecap="round" stroke-linejoin="round"/></svg>Edit Data</Link>
          <button v-if="canManage" type="button" class="action-btn border border-rose-200 bg-white text-rose-700 hover:border-rose-300 hover:bg-rose-50" @click="remove"><svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v5m4-5v5" stroke-linecap="round" stroke-linejoin="round"/></svg>Hapus</button>
        </div>
      </div>

      <article class="overflow-hidden rounded-3xl bg-white shadow-xl shadow-emerald-950/5 ring-1 ring-slate-200/80">
        <header class="relative overflow-hidden bg-gradient-to-br from-emerald-950 via-emerald-800 to-teal-700 px-6 py-8 text-white sm:px-9">
          <div class="absolute -right-16 -top-24 h-64 w-64 rounded-full border border-white/10"></div><div class="absolute -right-6 -top-14 h-64 w-64 rounded-full border border-amber-300/20"></div>
          <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center"><div class="grid h-16 w-16 shrink-0 place-items-center rounded-2xl bg-white/15 text-2xl font-black shadow-inner ring-1 ring-white/20 backdrop-blur">{{ applicant.full_name?.charAt(0) }}</div><div class="min-w-0 flex-1"><p class="font-mono text-sm font-semibold tracking-wide text-emerald-100">{{ applicant.registration_number }}</p><h1 class="mt-1 truncate text-2xl font-black tracking-tight sm:text-4xl">{{ applicant.full_name }}</h1><p class="mt-2 text-sm text-emerald-100">Detail lengkap dan progres pendaftaran calon peserta.</p></div></div>
        </header>
        <div class="grid gap-7 p-6 sm:p-9 lg:grid-cols-[1fr_320px]">
          <section><h2 class="section-title">Informasi Pribadi</h2><div class="mt-4 grid gap-3 sm:grid-cols-2">
            <div class="info-tile"><span class="icon-box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 6h16v12H4zM4 7l8 6 8-6"/></svg></span><div><p class="info-label">Email</p><a :href="`mailto:${applicant.email}`" class="info-value hover:text-emerald-700">{{ applicant.email }}</a></div></div>
            <div class="info-tile"><span class="icon-box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M7 3h3l2 5-2 1.5a14 14 0 0 0 4.5 4.5L16 12l5 2v3c0 2-2 4-4 4C9 20 4 15 3 7c0-2 2-4 4-4Z"/></svg></span><div><p class="info-label">WhatsApp</p><a :href="`https://wa.me/${applicant.whatsapp_normalized}`" target="_blank" class="info-value hover:text-emerald-700">{{ applicant.whatsapp_display }}</a></div></div>
            <div class="info-tile"><span class="icon-box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M8 3v3m8-3v3M4 9h16M5 5h14v16H5z"/></svg></span><div><p class="info-label">Tempat, tanggal lahir</p><p class="info-value">{{ applicant.birth_place }}, {{ applicant.birth_date }}</p></div></div>
            <div class="info-tile sm:col-span-2"><span class="icon-box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 21s7-6 7-12a7 7 0 1 0-14 0c0 6 7 12 7 12Z"/><circle cx="12" cy="9" r="2"/></svg></span><div><p class="info-label">Alamat</p><p class="info-value whitespace-pre-line leading-relaxed">{{ applicant.address }}</p></div></div>
          </div></section>
          <aside class="rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-200"><h2 class="section-title">Progres Pendaftaran</h2><div class="mt-4 space-y-3"><div v-for="item in [{ label: 'Pembayaran', value: applicant.payment_status }, { label: 'Berkas', value: applicant.document_status }, { label: 'Seleksi', value: applicant.selection_status }]" :key="item.label" class="flex items-center justify-between gap-3 rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-200/70 transition hover:-translate-y-0.5 hover:shadow-md"><span class="flex items-center gap-2 text-sm font-semibold text-slate-600"><i class="h-2.5 w-2.5 rounded-full" :class="badge(item.value)[2]"></i>{{ item.label }}</span><span class="rounded-full px-2.5 py-1 text-xs font-bold ring-1 ring-inset" :class="badge(item.value)[1]">{{ badge(item.value)[0] }}</span></div></div></aside>
        </div>
      </article>

      <article class="rounded-3xl bg-white p-6 shadow-lg shadow-slate-900/5 ring-1 ring-slate-200/80 sm:p-8">
        <div class="flex items-end justify-between gap-4"><div><h2 class="text-xl font-black text-slate-900">Dokumen Pendaftar</h2><p class="mt-1 text-sm text-slate-500">Periksa dan validasi setiap dokumen yang dikirim.</p></div><span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800">{{ applicant.documents.length }} dokumen</span></div>
        <div v-if="applicant.documents.length" class="mt-5 space-y-3"><div v-for="(d, index) in applicant.documents" :key="d.id" class="group flex flex-col gap-4 rounded-2xl border border-slate-200 bg-slate-50/70 p-4 transition duration-200 hover:-translate-y-0.5 hover:border-emerald-200 hover:bg-white hover:shadow-lg hover:shadow-emerald-950/5 md:flex-row md:items-center md:justify-between"><div class="flex items-center gap-3"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-white font-bold text-emerald-700 shadow-sm ring-1 ring-slate-200 transition group-hover:bg-emerald-700 group-hover:text-white">{{ String(index + 1).padStart(2, '0') }}</span><div><p class="font-bold text-slate-900">{{ documentNames[d.type] || d.type }}</p><span class="mt-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 ring-inset" :class="badge(d.verification_status)[1]">{{ badge(d.verification_status)[0] }}</span></div></div><div class="flex flex-wrap gap-2 md:justify-end"><a :href="`/admin/documents/${d.id}/download`" class="doc-btn border-slate-200 bg-white text-slate-700 hover:border-emerald-300 hover:text-emerald-700">Unduh</a><button type="button" :disabled="reviewingId === d.id" @click="review(d,'valid')" class="doc-btn border-emerald-700 bg-emerald-700 text-white hover:bg-emerald-800 disabled:opacity-50">✓ Valid</button><button type="button" :disabled="reviewingId === d.id" @click="review(d,'revision_required')" class="doc-btn border-amber-200 bg-amber-50 text-amber-900 hover:bg-amber-100 disabled:opacity-50">Perlu Perbaikan</button></div></div></div>
        <div v-else class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center"><p class="font-bold text-slate-700">Belum ada dokumen</p><p class="mt-1 text-sm text-slate-500">Pendaftar ini belum mengunggah dokumen.</p></div>
      </article>
    </section>
  </main>
</template>

<style scoped>
.action-btn { @apply inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold transition duration-200 hover:-translate-y-0.5 active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2; }
.section-title { @apply text-base font-black tracking-tight text-slate-900; }
.info-tile { @apply flex min-w-0 gap-3 rounded-2xl border border-slate-200 bg-slate-50/70 p-4 transition duration-200 hover:border-emerald-200 hover:bg-white hover:shadow-md; }
.icon-box { @apply grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-100 text-emerald-800; }
.icon-box svg { @apply h-5 w-5; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
.info-label { @apply text-xs font-bold uppercase tracking-wider text-slate-400; }
.info-value { @apply mt-1 block break-words font-semibold text-slate-700 transition; }
.doc-btn { @apply rounded-lg border px-3 py-2 text-xs font-bold transition duration-200 hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-emerald-300; }
@media (prefers-reduced-motion: reduce) { .action-btn, .info-tile, .doc-btn { transition: none; transform: none !important; } }
</style>
