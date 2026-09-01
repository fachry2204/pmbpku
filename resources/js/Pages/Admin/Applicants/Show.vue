<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
const props = defineProps<{ applicant: any }>();
const review = (document: any, status: string) => { const note = status === 'revision_required' ? window.prompt('Catatan perbaikan yang wajib ditampilkan ke pendaftar:') || '' : null; if (status === 'revision_required' && !note) return; router.patch(`/admin/applicants/${props.applicant.id}/documents/${document.id}`, { verification_status: status, review_note: note }, { preserveScroll: true }); };
const statuses: Record<string, [string, string]> = {
  unpaid:['Belum Bayar','bg-red-100 text-red-700'],pending:['Menunggu','bg-amber-100 text-amber-800'],paid:['Lunas','bg-emerald-100 text-emerald-700'],failed:['Gagal','bg-red-100 text-red-700'],
  pending_review:['Menunggu Review','bg-amber-100 text-amber-800'],complete:['Berkas Lengkap','bg-emerald-100 text-emerald-700'],incomplete:['Belum Lengkap','bg-red-100 text-red-700'],
  not_scheduled:['Belum Dijadwalkan','bg-slate-100 text-slate-700'],scheduled:['Dijadwalkan','bg-blue-100 text-blue-700'],passed:['Diterima','bg-emerald-100 text-emerald-700'],not_passed:['Tidak Diterima','bg-red-100 text-red-700'],
};
const badge = (value: string) => statuses[value] || [value?.replaceAll('_',' ') || '-', 'bg-slate-100 text-slate-700'];
const documentNames: Record<string,string> = { recommendation_letter:'Surat Rekomendasi',diploma:'Ijazah',photo_4x6:'Foto 4×6',identity_card:'KTP',pddikti_screenshot:'Screenshot PDDIKTI',payment_proof:'Bukti Pembayaran' };
</script>

<template>
  <Head :title="applicant.registration_number" />
  <main class="min-h-screen bg-slate-100 p-5"><section class="mx-auto max-w-5xl space-y-5">
    <Link href="/admin/applicants" class="font-semibold text-emerald-700">← Data pendaftar</Link>
    <article class="rounded-2xl bg-white p-7 shadow-sm">
      <p class="font-semibold text-emerald-700">{{ applicant.registration_number }}</p><h1 class="text-3xl font-bold text-emerald-950">{{ applicant.full_name }}</h1>
      <div class="mt-5 grid gap-4 rounded-2xl bg-slate-50 p-5 md:grid-cols-2"><p><b>Email</b><span class="mt-1 block text-slate-600">{{ applicant.email }}</span></p><p><b>WhatsApp</b><span class="mt-1 block text-slate-600">{{ applicant.whatsapp_display }}</span></p><p><b>Tempat, tanggal lahir</b><span class="mt-1 block text-slate-600">{{ applicant.birth_place }}, {{ applicant.birth_date }}</span></p><p><b>Alamat</b><span class="mt-1 block whitespace-pre-line text-slate-600">{{ applicant.address }}</span></p></div>
      <div class="mt-5 grid gap-3 sm:grid-cols-3"><div class="rounded-xl border p-4"><small class="text-slate-500">Pembayaran</small><span class="mt-2 block w-fit rounded-full px-3 py-1 text-xs font-bold" :class="badge(applicant.payment_status)[1]">{{ badge(applicant.payment_status)[0] }}</span></div><div class="rounded-xl border p-4"><small class="text-slate-500">Berkas</small><span class="mt-2 block w-fit rounded-full px-3 py-1 text-xs font-bold" :class="badge(applicant.document_status)[1]">{{ badge(applicant.document_status)[0] }}</span></div><div class="rounded-xl border p-4"><small class="text-slate-500">Seleksi</small><span class="mt-2 block w-fit rounded-full px-3 py-1 text-xs font-bold" :class="badge(applicant.selection_status)[1]">{{ badge(applicant.selection_status)[0] }}</span></div></div>
    </article>
    <article class="rounded-2xl bg-white p-7 shadow-sm"><h2 class="text-xl font-bold">Dokumen Pendaftar</h2><div class="mt-3 divide-y"><div v-for="d in applicant.documents" :key="d.id" class="flex flex-col justify-between gap-3 py-4 md:flex-row md:items-center"><span class="font-semibold">{{ documentNames[d.type] || d.type }}</span><span class="flex flex-wrap items-center gap-2"><a :href="`/admin/documents/${d.id}/download`" class="rounded-lg border px-3 py-1 text-emerald-800">Unduh</a><button type="button" @click="review(d,'valid')" class="rounded-lg bg-emerald-700 px-3 py-1 text-white">Valid</button><button type="button" @click="review(d,'revision_required')" class="rounded-lg bg-amber-100 px-3 py-1 text-amber-900">Perbaikan</button><b class="text-sm">{{ badge(d.verification_status)[0] }}</b></span></div></div></article>
  </section></main>
</template>
